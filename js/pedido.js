document.addEventListener('DOMContentLoaded', function() {
    const sliderTrack = document.getElementById('productSliderTrack');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    const dotsContainer = document.getElementById('sliderDots');
    const totalPedidoDiv = document.getElementById('total-pedido');
    const paymentOptions = document.querySelector('.payment-options');
    const trocoField = document.getElementById('troco-field');
    const valorPagoInput = document.getElementById('valor-pago');
    const valorTrocoP = document.getElementById('valor-troco');

    let currentIndex = 0;
    let itemWidth = 0;
    let itemsPerView = 1;
    let produtos = [];
    let currentTotal = 0; // Variable to store the current total of the order

    // Buscar produtos do servidor
    fetch('get_produtos.php')
        .then(response => response.json())
        .then(data => {
            console.log("Produtos fetched:", data);
            produtos = data;
            renderProducts();
            setupSlider();
            atualizarTotalPedido(); // Initial calculation after products are rendered
        })
        .catch(error => console.error('Erro ao buscar produtos:', error));

    // Renderizar produtos no slider
    function renderProducts() {
        sliderTrack.innerHTML = '';

        produtos.forEach(produto => {
            const sliderItem = document.createElement('div');
            sliderItem.classList.add('slider-item');

            const inputId = `quantity-input-${produto.id_produto}`;

            sliderItem.innerHTML = `
                <div class="product-card">
                    <img src="${produto.imagem}" alt="${produto.nome}" class="imagem-produto">
                    <div class="product-info">
                        <h3>${produto.nome}</h3>
                        <p>R$ ${parseFloat(produto.preco).toFixed(2).replace('.', ',')}</p>
                    </div>
                    <div class="quantity-input-group">
                        <button type="button" class="quantity-btn minus-btn" data-product-id="${produto.id_produto}">-</button>
                        <input type="number" id="${inputId}" name="${produto.id_produto}" value="0" min="0" step="1" data-preco="${produto.preco}">
                        <button type="button" class="quantity-btn plus-btn" data-product-id="${produto.id_produto}">+</button>
                    </div>
                </div>
            `;

            sliderTrack.appendChild(sliderItem);

            const inputField = sliderItem.querySelector(`#${inputId}`);
            const minusBtn = sliderItem.querySelector(`.minus-btn[data-product-id="${produto.id_produto}"]`);
            const plusBtn = sliderItem.querySelector(`.plus-btn[data-product-id="${produto.id_produto}"]`);

            minusBtn.addEventListener('click', () => {
                let currentValue = parseInt(inputField.value, 10);
                if (currentValue > 0) {
                    inputField.value = currentValue - 1;
                    atualizarTotalPedido();
                }
            });

            plusBtn.addEventListener('click', () => {
                let currentValue = parseInt(inputField.value, 10);
                inputField.value = currentValue + 1;
                atualizarTotalPedido();
            });

            inputField.addEventListener('input', atualizarTotalPedido);
        });
    }

    // Configurar o slider (no change)
    function setupSlider() {
        const sliderItems = document.querySelectorAll('.slider-item');
        if (sliderItems.length === 0) return;

        itemWidth = sliderItems[0].offsetWidth;
        itemsPerView = Math.round(sliderTrack.offsetWidth / itemWidth);

        createDots();
        goToSlide(0);

        window.addEventListener('resize', handleResize);
    }

    // Criar dots de navegação (no change)
    function createDots() {
        dotsContainer.innerHTML = '';
        const sliderItems = document.querySelectorAll('.slider-item');
        if (sliderItems.length === 0) return;

        const dotCount = Math.ceil(sliderItems.length / itemsPerView);

        for (let i = 0; i < dotCount; i++) {
            const dot = document.createElement('div');
            dot.className = 'dot';
            if (i === 0) dot.classList.add('active');
            dot.addEventListener('click', () => goToSlide(i * itemsPerView));
            dotsContainer.appendChild(dot);
        }
    }

    // Atualizar dots ativos (no change)
    function updateDots() {
        const dots = document.querySelectorAll('.dot');
        const activeDotIndex = Math.floor(currentIndex / itemsPerView);

        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === activeDotIndex);
        });
    }

    // Mover para um slide específico (no change)
    function goToSlide(index) {
        const sliderItems = document.querySelectorAll('.slider-item');
        if (sliderItems.length === 0) return;

        const maxIndex = sliderItems.length - itemsPerView;
        currentIndex = Math.max(0, Math.min(index, maxIndex));

        const targetPosition = -currentIndex * itemWidth;
        sliderTrack.style.transform = `translateX(${targetPosition}px)`;

        updateDots();
    }

    // Slide anterior (no change)
    function prevSlide() {
        goToSlide(currentIndex - 1);
    }

    // Próximo slide (no change)
    function nextSlide() {
        goToSlide(currentIndex + 1);
    }

    // Recalcular dimensões ao redimensionar a janela (no change)
    function handleResize() {
        const sliderItems = document.querySelectorAll('.slider-item');
        if (sliderItems.length === 0) return;

        itemWidth = sliderItems[0].offsetWidth;
        itemsPerView = Math.round(sliderTrack.offsetWidth / itemWidth);
        goToSlide(currentIndex);
        createDots();
    }

    // Atualizar total do pedido
    function atualizarTotalPedido() {
        let total = 0;
        const quantityInputs = document.querySelectorAll('.quantity-input-group input[type="number"]');

        quantityInputs.forEach(inputField => {
            const preco = parseFloat(inputField.getAttribute('data-preco'));
            const quantidade = parseInt(inputField.value, 10);

            if (!isNaN(preco) && !isNaN(quantidade)) {
                total += preco * quantidade;
            }
        });

        currentTotal = total; // Store the calculated total
        totalPedidoDiv.textContent = `Total do Pedido: R$ ${total.toFixed(2).replace('.', ',')}`; // Format for Brazilian currency

        // Recalculate change if cash is selected
        if (document.querySelector('input[name="payment"]:checked')?.value === 'dinheiro') {
            calcularTroco();
        }
    }

    // Function to calculate and display change with validation
    function calcularTroco() {
        const valorPagoStr = valorPagoInput.value.trim(); // Get string value and trim whitespace
        let valorPago = parseFloat(valorPagoStr);
        let troco = 0;

        // If the input is empty or 0, treat it as exact payment (no change needed)
        if (valorPagoStr === '' || valorPago === 0) {
            valorTrocoP.textContent = `Troco: R$ 0,00 (Valor exato)`;
            valorTrocoP.style.color = 'inherit'; // Reset color
            return;
        }

        // Handle invalid or negative input (non-numeric, or explicitly negative)
        if (isNaN(valorPago) || valorPago < 0) {
            valorTrocoP.textContent = `Troco: R$ 0,00`; // Or "Por favor, insira um valor válido"
            valorTrocoP.style.color = 'inherit';
            return;
        }

        if (valorPago < currentTotal) {
            // Display a message if amount paid is insufficient
            valorTrocoP.textContent = `Valor insuficiente! Faltam R$ ${(currentTotal - valorPago).toFixed(2).replace('.', ',')}`;
            valorTrocoP.style.color = 'red'; // Make the text red for emphasis
        } else {
            troco = valorPago - currentTotal;
            valorTrocoP.textContent = `Troco: R$ ${troco.toFixed(2).replace('.', ',')}`;
            valorTrocoP.style.color = 'inherit'; // Reset color if sufficient
        }
    }

    // Event listener for payment method selection
    paymentOptions.addEventListener('change', function(event) {
        if (event.target.name === 'payment') {
            if (event.target.value === 'dinheiro') {
                trocoField.style.display = 'block';
                valorPagoInput.value = ''; // Clear previous input
                valorTrocoP.textContent = 'Troco: R$ 0,00'; // Reset troco display
                valorTrocoP.style.color = 'inherit'; // Reset color
                // Call calcularTroco to immediately show "Valor exato" if total is 0 or user just selected
                calcularTroco();
            } else {
                trocoField.style.display = 'none';
                valorPagoInput.value = ''; // Clear input when switching away from dinheiro
            }
        }
    });

    // Event listener for Valor Pago input
    valorPagoInput.addEventListener('input', calcularTroco);


    // Event listeners for slider navigation
    prevBtn.addEventListener('click', prevSlide);
    nextBtn.addEventListener('click', nextSlide);
});