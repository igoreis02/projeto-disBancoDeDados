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
    const confirmarPedidoBtn = document.getElementById('confirmarPedido');
    const clienteTelefoneInput = document.getElementById('clienteTelefone');
    const erro = document.getElementById('mensagem');

    let currentIndex = 0;
    let itemWidth = 0; // Largura de um item, incluindo o gap "virtual" que ele ocupa
    let totalItems = 0; // Número total de produtos
    let produtos = [];
    let currentTotal = 0;

    const urlParams = new URLSearchParams(window.location.search);
    const telefoneFromURL = urlParams.get('telefone');
    if (telefoneFromURL) {
        clienteTelefoneInput.value = telefoneFromURL;
    }

    fetch('get_produtos.php')
        .then(response => response.json())
        .then(data => {
            console.log("Produtos fetched:", data);
            produtos = data;
            renderProducts();
            setupSlider(); // Chamar setupSlider após renderizar os produtos
            atualizarTotalPedido();
        })
        .catch(error => console.error('Erro ao buscar produtos:', error));

    function renderProducts() {
        sliderTrack.innerHTML = '';
        produtos.forEach(produto => {
            const sliderItem = document.createElement('div');
            sliderItem.classList.add('slider-item');

            const inputId = `quantity-input-${produto.id_produtos}`;

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
        totalItems = produtos.length; // Atualiza o total de itens após a renderização
    }

    function setupSlider() {
        const sliderItems = document.querySelectorAll('.slider-item');
        if (sliderItems.length === 0) {
            itemWidth = 0; // Reset itemWidth if no items
            dotsContainer.innerHTML = ''; // Clear dots if no items
            return;
        }

        // Obtém a largura computada de um item e o gap
        const firstItem = sliderItems[0];
        const computedStyle = window.getComputedStyle(sliderTrack);
        const gap = parseFloat(computedStyle.getPropertyValue('gap')) || 0;

        // Largura real do item (offsetWidth inclui padding e borda) + o gap que ele 'ocupa'
        itemWidth = firstItem.offsetWidth + gap;

        // Se houver apenas um item, ajuste o itemWidth para evitar problemas de cálculo de translação
        if (totalItems === 1) {
            itemWidth = firstItem.offsetWidth;
        }

        createDots();
        goToSlide(currentIndex); // Vai para o slide atual para aplicar a posição inicial
        window.addEventListener('resize', handleResize);
    }


    function createDots() {
        dotsContainer.innerHTML = '';
        if (totalItems === 0) return;

        // O número de pontos é igual ao número total de itens, pois avançamos um por um
        for (let i = 0; i < totalItems; i++) {
            const dot = document.createElement('div');
            dot.className = 'dot';
            if (i === currentIndex) dot.classList.add('active'); // Ativa o dot inicial
            dot.addEventListener('click', () => goToSlide(i));
            dotsContainer.appendChild(dot);
        }
    }

    function updateDots() {
        const dots = document.querySelectorAll('.dot');
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentIndex);
        });
    }

    function goToSlide(index) {
        if (totalItems === 0 || itemWidth === 0) return;

        // Calcula o índice máximo para garantir que o último item esteja visível
        // A largura visível do track
        const trackVisibleWidth = sliderTrack.offsetWidth;
        const lastItem = document.querySelector('.slider-item:last-child');
        let maxIndex = 0;

        if (lastItem) {
            // Calcula a posição da borda direita do último item se ele estivesse na posição 0
            const lastItemOriginalPosition = lastItem.offsetLeft; // Posição do último item sem translate

            // O slider pode ir até um ponto onde a borda direita do ÚLTIMO item
            // esteja alinhada com a borda direita da área visível do slider.
            // Para isso, a translação é: (largura total do track - largura visível do track)
            // Dividido pela largura de um item para obter o índice.
            // Arredondar para cima para garantir que o último item seja acessível.
            const totalContentWidth = itemWidth * totalItems; // Largura total de todos os itens + gaps
            if (totalContentWidth > trackVisibleWidth) {
                // maxScrollLeft é o quanto o sliderTrack precisa se deslocar para a esquerda
                // para que o último item visível seja o último item do array.
                const lastItemWidth = lastItem.offsetWidth;
                const gap = parseFloat(window.getComputedStyle(sliderTrack).getPropertyValue('gap')) || 0;

                // O offsetRight do último item quando totalmente visível.
                // Isso é complexo porque o `min-width` pode significar que nem sempre N itens cabem perfeitamente.
                // A melhor abordagem é calcular a quantidade de "scroll" necessária.
                // Se o último item estiver no final da tela, a translação é:
                // (total de itens * itemWidth) - (largura do track visível)
                let calculatedMaxScroll = (totalItems * itemWidth) - (trackVisibleWidth + gap);
                // Divida pelo itemWidth para obter o índice aproximado
                maxIndex = Math.ceil(calculatedMaxScroll / itemWidth);

                // Garante que o maxIndex não seja negativo e não ultrapasse o número de itens - 1
                maxIndex = Math.max(0, Math.min(maxIndex, totalItems - 1));
            } else {
                maxIndex = 0; // Se todos os itens couberem, não há rolagem
            }
        }


        // Garante que o índice não seja negativo e não ultrapasse o máximo possível
        currentIndex = Math.max(0, Math.min(index, maxIndex));

        // Posição de translação: -currentIndex * itemWidth
        // Adicionamos um ajuste aqui para garantir que o último item se encaixe
        // Se estiver no último slide, e o ultimo item estiver parcialmente visível, ajuste a translação
        let targetPosition = -currentIndex * itemWidth;

        // Ajuste para o último slide:
        // Se estamos no "último" slide (ou seja, currentIndex está próximo do maxIndex),
        // precisamos garantir que o último item esteja totalmente visível.
        // Isso pode exigir que a translação seja um pouco menor do que o calculado
        // para "empurrar" o último item para a vista.
        if (currentIndex === maxIndex && totalItems > 0) {
            const currentContentWidth = (totalItems * itemWidth); // Largura total dos itens
            const visibleWidth = sliderTrack.offsetWidth;
            const remainingSpace = currentContentWidth - visibleWidth;
            if (remainingSpace > 0) {
                // A posição final deve ser tal que o último item esteja visível.
                // Se o itemWidth * maxIndex não for o suficiente, empurre mais.
                // Ex: se 3 itens visíveis, e o último de 10 itens está no slide 8,
                // ele pode estar cortado. A translação deveria ser mais para a esquerda.
                targetPosition = -(currentContentWidth - visibleWidth + parseFloat(window.getComputedStyle(sliderTrack).gap || 0));
                // Certifique-se de que não estamos empurrando muito para a esquerda,
                // ou seja, a posição não deve ser menor do que o necessário para exibir o primeiro item
                targetPosition = Math.max(targetPosition, -(maxIndex * itemWidth));
            } else {
                targetPosition = 0; // Se tudo cabe, não precisa rolar
            }
        }
         // Adicionalmente, garantimos que o targetPosition não seja negativo se houver poucos itens
        if (targetPosition > 0) {
            targetPosition = 0;
        }

        sliderTrack.style.transform = `translateX(${targetPosition}px)`;
        updateDots();
    }

    function prevSlide() {
        goToSlide(currentIndex - 1); // Avança um item por vez
    }

    function nextSlide() {
        goToSlide(currentIndex + 1); // Avança um item por vez
    }

    function handleResize() {
        setupSlider(); // Recalcula tudo no redimensionamento
        goToSlide(currentIndex); // Garante que a posição seja ajustada após o redimensionamento
    }

    // --- Funções de Pedido (mantidas as originais) ---
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

    function calcularTroco() {
        const valorPagoStr = valorPagoInput.value.trim();
        let valorPago = parseFloat(valorPagoStr);
        let troco = 0;

        if (valorPagoStr === '') {
            valorTrocoP.textContent = `Troco: R$ 0,00 (Valor exato)`;
            valorTrocoP.style.color = 'inherit';
            return;
        }

        if (isNaN(valorPago) || valorPago < 0) {
            valorTrocoP.textContent = `Valor inválido!`;
            valorTrocoP.style.color = 'red';
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
                erro.textContent = '';
            }
        }
    });

    valorPagoInput.addEventListener('input', calcularTroco);

    prevBtn.addEventListener('click', prevSlide);
    nextBtn.addEventListener('click', nextSlide);

    confirmarPedidoBtn.addEventListener('click', function() {
        const selectedProducts = [];
        const quantityInputs = document.querySelectorAll('.quantity-input-group input[type="number"]');
        let hasSelectedProducts = false;

        erro.textContent = '';

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

        const formaPagamento = paymentMethod.value;
        let valorPagoParaTroco = 0;

        if (formaPagamento === 'dinheiro') {
            const valorPagoInputTrimmed = valorPagoInput.value.trim();

            if (valorPagoInputTrimmed === '') {
                valorPagoParaTroco = currentTotal;
            } else {
                valorPagoParaTroco = parseFloat(valorPagoInputTrimmed);

                if (isNaN(valorPagoParaTroco)) {
                    erro.textContent = 'Por favor, insira um valor válido para pagamento em dinheiro.';
                    erro.style.color = 'red';
                    return;
                }
                if (valorPagoParaTroco < currentTotal) {
                    erro.textContent = 'Para pagamento em dinheiro, o valor pago deve ser igual ou maior que o total do pedido.';
                    erro.style.color = 'red';
                    return;
                }
            }
        }

        const params = new URLSearchParams();
        params.append('telefone', clienteTelefoneInput.value);
        params.append('total', currentTotal.toFixed(2));
        params.append('forma_pagamento', formaPagamento);
        params.append('produtos', JSON.stringify(selectedProducts));

        if (formaPagamento === 'dinheiro') {
            params.append('valor_pago', valorPagoParaTroco.toFixed(2));
        }

        window.location.href = `confirmar_pedido.php?${params.toString()}`;
    });
});