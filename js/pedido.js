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
    const confirmarPedidoBtn = document.getElementById('confirmarPedido'); // New
    const clienteTelefoneInput = document.getElementById('clienteTelefone'); // New
    const erro = document.getElementById('mensagem');

    let currentIndex = 0;
    let itemWidth = 0;
    let itemsPerView = 1;
    let produtos = [];
    let currentTotal = 0;

    // Get phone number from URL if available (from cadastro.html)
    const urlParams = new URLSearchParams(window.location.search);
    const telefoneFromURL = urlParams.get('telefone');
    if (telefoneFromURL) {
        clienteTelefoneInput.value = telefoneFromURL;
    }

    // Buscar produtos do servidor
    fetch('get_produtos.php')
        .then(response => response.json())
        .then(data => {
            console.log("Produtos fetched:", data);
            produtos = data;
            renderProducts();
            setupSlider();
            atualizarTotalPedido();
        })
        .catch(error => console.error('Erro ao buscar produtos:', error));

    // Renderizar produtos no slider
    function renderProducts() {
        sliderTrack.innerHTML = '';

        produtos.forEach(produto => {
            const sliderItem = document.createElement('div');
            sliderItem.classList.add('slider-item');

            const inputId = `quantity-input-${produto.id_produtos}`; // Corrected product ID attribute

            sliderItem.innerHTML = `
                <div class="product-card">
                    <img src="${produto.imagem}" alt="${produto.nome}" class="imagem-produto">
                    <div class="product-info">
                        <h3>${produto.nome}</h3>
                        <p>R$ ${parseFloat(produto.preco).toFixed(2).replace('.', ',')}</p>
                    </div>
                    <div class="quantity-input-group">
                        <button type="button" class="quantity-btn minus-btn" data-product-id="${produto.id_produtos}">-</button>
                        <input type="number" id="${inputId}" name="${produto.id_produtos}" value="0" min="0" step="1" data-preco="${produto.preco}">
                        <button type="button" class="quantity-btn plus-btn" data-product-id="${produto.id_produtos}">+</button>
                    </div>
                </div>
            `;

            sliderTrack.appendChild(sliderItem);

            const inputField = sliderItem.querySelector(`#${inputId}`);
            const minusBtn = sliderItem.querySelector(`.minus-btn[data-product-id="${produto.id_produtos}"]`);
            const plusBtn = sliderItem.querySelector(`.plus-btn[data-product-id="${produto.id_produtos}"]`);

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

    // Configurar o slider
    function setupSlider() {
        const sliderItems = document.querySelectorAll('.slider-item');
        if (sliderItems.length === 0) return;

        itemWidth = sliderItems[0].offsetWidth;
        itemsPerView = Math.round(sliderTrack.offsetWidth / itemWidth);

        createDots();
        goToSlide(0);

        window.addEventListener('resize', handleResize);
    }

    // Criar dots de navegação
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

    // Atualizar dots ativos
    function updateDots() {
        const dots = document.querySelectorAll('.dot');
        const activeDotIndex = Math.floor(currentIndex / itemsPerView);

        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === activeDotIndex);
        });
    }

    // Mover para um slide específico
    function goToSlide(index) {
        const sliderItems = document.querySelectorAll('.slider-item');
        if (sliderItems.length === 0) return;

        const maxIndex = sliderItems.length - itemsPerView;
        currentIndex = Math.max(0, Math.min(index, maxIndex));

        const targetPosition = -currentIndex * itemWidth;
        sliderTrack.style.transform = `translateX(${targetPosition}px)`;

        updateDots();
    }

    // Slide anterior
    function prevSlide() {
        goToSlide(currentIndex - 1);
    }

    // Próximo slide
    function nextSlide() {
        goToSlide(currentIndex + 1);
    }

    // Recalcular dimensões ao redimensionar a janela
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

        currentTotal = total;
        totalPedidoDiv.textContent = `Total do Pedido: R$ ${total.toFixed(2).replace('.', ',')}`;

        if (document.querySelector('input[name="payment"]:checked')?.value === 'dinheiro') {
            calcularTroco();
        }
    }

    // Function to calculate and display change with validation
    function calcularTroco() {
        const valorPagoStr = valorPagoInput.value.trim();
        let valorPago = parseFloat(valorPagoStr);
        let troco = 0;

        if (valorPagoStr === '' || valorPago === 0) {
            valorTrocoP.textContent = `Troco: R$ 0,00 (Valor exato)`;
            valorTrocoP.style.color = 'inherit';
            return;
        }

        if (isNaN(valorPago) || valorPago < 0) {
            valorTrocoP.textContent = `Troco: R$ 0,00`;
            valorTrocoP.style.color = 'inherit';
            return;
        }

        if (valorPago < currentTotal) {
            valorTrocoP.textContent = `Valor insuficiente! Faltam R$ ${(currentTotal - valorPago).toFixed(2).replace('.', ',')}`;
            valorTrocoP.style.color = 'red';
        } else {
            troco = valorPago - currentTotal;
            valorTrocoP.textContent = `Troco: R$ ${troco.toFixed(2).replace('.', ',')}`;
            valorTrocoP.style.color = 'inherit';
        }
    }

    // Event listener for payment method selection
    paymentOptions.addEventListener('change', function(event) {
        if (event.target.name === 'payment') {
            if (event.target.value === 'dinheiro') {
                trocoField.style.display = 'block';
                valorPagoInput.value = '';
                valorTrocoP.textContent = 'Troco: R$ 0,00';
                valorTrocoP.style.color = 'inherit';
                calcularTroco();
            } else {
                trocoField.style.display = 'none';
                valorPagoInput.value = '';
            }
        }
    });

    // Event listener for Valor Pago input
    valorPagoInput.addEventListener('input', calcularTroco);

    // Event listeners for slider navigation
    prevBtn.addEventListener('click', prevSlide);
    nextBtn.addEventListener('click', nextSlide);

    // New: Event listener for Confirmar Pedido button
  confirmarPedidoBtn.addEventListener('click', function() {
        const selectedProducts = [];
        const quantityInputs = document.querySelectorAll('.quantity-input-group input[type="number"]');
        let hasSelectedProducts = false;

        quantityInputs.forEach(input => {
            const quantity = parseInt(input.value, 10);
            if (quantity > 0) {
                hasSelectedProducts = true;
                const productId = input.name;
                const product = produtos.find(p => p.id_produtos == productId);
                if (product) {
                    selectedProducts.push({
                        id: product.id_produtos,
                        nome: product.nome,
                        preco: parseFloat(product.preco),
                        quantidade: quantity
                    });
                }
            }
        });

        if (!hasSelectedProducts) {
            erro.textContent = 'Por favor, selecione pelo menos um produto para o pedido.';
            erro.style.color = 'red';
            return;
        }

        const paymentMethod = document.querySelector('input[name="payment"]:checked');
        if (!paymentMethod) {
            erro.textContent = 'Por favor, selecione uma forma de pagamento.';
            erro.style.color = 'red';
            return;
        }

        const formaPagamento = paymentMethod ? paymentMethod.value : ''; // Garante que a formaPagamento não é null

        let valorPagoParaTroco = 0; // Initialize to 0

        // If payment is cash, get the paid amount
        if (formaPagamento === 'dinheiro') {
            valorPagoParaTroco = parseFloat(valorPagoInput.value);
            // Adicione console.log para depuração aqui
            console.log('Forma de pagamento: Dinheiro');
            console.log('Valor Pago no input:', valorPagoInput.value);
            console.log('Valor Pago parseado:', valorPagoParaTroco);
            console.log('Total atual:', currentTotal);


            if (isNaN(valorPagoParaTroco) || valorPagoParaTroco < currentTotal) {
                erro.textContent = 'Para pagamento em dinheiro, o valor pago deve ser igual ou maior que o total do pedido.';
                erro.style.color = 'red';
                return; // Impede o redirecionamento se a validação falhar
            }
        }


        // Constructing the URL with parameters
        const params = new URLSearchParams();
        params.append('telefone', clienteTelefoneInput.value);
        params.append('total', currentTotal.toFixed(2));
        params.append('forma_pagamento', formaPagamento);
        params.append('produtos', JSON.stringify(selectedProducts));

        // Add valor_pago parameter ONLY if payment is 'dinheiro'
        if (formaPagamento === 'dinheiro') {
            params.append('valor_pago', valorPagoParaTroco.toFixed(2));
        } 

        window.location.href = `confirmar_pedido.php?${params.toString()}`;
    });
});