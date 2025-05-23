document.addEventListener('DOMContentLoaded', function() {
    const sliderTrack = document.getElementById('productSliderTrack');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    const dotsContainer = document.getElementById('sliderDots');
    const totalPedidoDiv = document.getElementById('total-pedido');
    
    let currentIndex = 0;
    let itemWidth = 0;
    let itemsPerView = 1;
    let produtos = [];

    // Buscar produtos do servidor
    fetch('get_produtos.php')
        .then(response => response.json())
        .then(data => {
            produtos = data;
            renderProducts();
            setupSlider();
            setupQuantityControls();
            atualizarTotalPedido();
        })
        .catch(error => console.error('Erro ao buscar produtos:', error));

    // Renderizar produtos no slider
    function renderProducts() {
        sliderTrack.innerHTML = '';
        
        produtos.forEach(produto => {
            const sliderItem = document.createElement('div');
            sliderItem.classList.add('slider-item');
            
            sliderItem.innerHTML = `
                <div class="product-card">
                    <img src="${produto.imagem}" alt="${produto.nome}" class="imagem-produto">
                    <div class="product-info">
                        <h3>${produto.nome}</h3>
                        <p>R$ ${produto.preco}</p>
                    </div>
                    <div class="quantity-input-group">
                        <button type="button" class="quantity-btn" data-id="${produto.id_produto}" data-action="minus">-</button>
                        <input type="number" id="${produto.id_produto}" name="${produto.id_produto}" value="0" min="0" data-preco="${produto.preco}">
                        <button type="button" class="quantity-btn" data-id="${produto.id_produto}" data-action="plus">+</button>
                    </div>
                </div>
            `;
            
            sliderTrack.appendChild(sliderItem);
        });
    }

    // Configurar controles de quantidade
    function setupQuantityControls() {
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('quantity-btn')) {
                const productId = e.target.getAttribute('data-id');
                const action = e.target.getAttribute('data-action');
                const inputField = document.getElementById(productId);
                let currentValue = parseInt(inputField.value, 10);

                if (action === 'plus') {
                    currentValue++;
                } else if (action === 'minus' && currentValue > 0) {
                    currentValue--;
                }

                inputField.value = currentValue;
                atualizarTotalPedido();
            }
        });

        // Atualizar também quando o valor é digitado manualmente
        document.querySelectorAll('.quantity-input-group input').forEach(input => {
            input.addEventListener('input', atualizarTotalPedido);
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
        
        // Recalcular ao redimensionar
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
        produtos.forEach(produto => {
            const inputField = document.getElementById(produto.id_produto);
            if (inputField) {
                const preco = parseFloat(inputField.getAttribute('data-preco'));
                const quantidade = parseInt(inputField.value, 10);
                if (!isNaN(preco) && !isNaN(quantidade)) {
                    total += preco * quantidade;
                }
            }
        });
        totalPedidoDiv.textContent = `Total do Pedido: R$ ${total.toFixed(2)}`;
    }

    // Event listeners
    prevBtn.addEventListener('click', prevSlide);
    nextBtn.addEventListener('click', nextSlide);
});