const verificarForm = document.getElementById('formulario');
const mensagemDiv = document.getElementById('mensagem');
const telefoneInput = document.getElementById('telefone');
const senhaInput = document.getElementById('hidden'); // Assumindo que este é para um campo de senha, embora não seja usado na lógica aqui.
const pesquisaTelefoneButton = document.getElementById('pesquisaTelefone'); // Não usado neste trecho, mas mantido.
const privacidade = document.getElementById('privacidade'); // Assumindo que 'privacidade' é o elemento input do checkbox
// const privacidadeInput = document.querySelector('.checkbox'); // Isso pode ser redundante se 'privacidade' já se refere ao checkbox

// Listener de evento para formatação de número de telefone em tempo real
telefoneInput.addEventListener('input', function () {
    let telefone = telefoneInput.value;
    // Remove todos os caracteres não numéricos e atualiza o campo de entrada
    telefoneInput.value = telefone.replace(/\D/g, '');
});

// Listener de evento para envio do formulário
verificarForm.addEventListener('submit', function (event) {
    event.preventDefault(); // Previne o comportamento padrão de envio do formulário

    const telefone = telefoneInput.value; // Obtém o valor do número de telefone no momento do envio
    const privacidadeAceita = privacidade.checked; // Verifica se o checkbox de privacidade está marcado

    // --- Verificações de Validação ---

    // Verifica a aceitação da privacidade primeiro
    if (!privacidadeAceita) {
        mensagemDiv.textContent = 'Aceite os termos de privacidade.';
        mensagemDiv.style.color = 'red';
        return; // Interrompe a execução se não for aceito
    }

    // Verifica se o campo de telefone está vazio
    if (telefone === '') {
        mensagemDiv.textContent = 'Por favor, insira o telefone.';
        mensagemDiv.style.color = 'red';
        return;
    }

    // Verifica o comprimento do número de telefone
    if (telefone.length < 10 || telefone.length > 11) {
        mensagemDiv.textContent = 'O telefone deve ter entre 10 e 11 dígitos.';
        mensagemDiv.style.color = 'red';
        return;
    }

    // Verifica se o número de telefone contém apenas dígitos
    if (!/^\d+$/.test(telefone)) {
        mensagemDiv.textContent = 'O telefone deve conter apenas números.';
        mensagemDiv.style.color = 'red';
        return;
    }

    // Se todas as validações passarem, limpa mensagens anteriores e prossegue com o fetch
    mensagemDiv.textContent = '';
    mensagemDiv.style.color = ''; // Limpa qualquer cor vermelha anterior

    // --- Requisição Fetch para o Backend PHP ---
    fetch('verificar_telefone.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `telefone=${encodeURIComponent(telefone)}` // Garante que o telefone seja codificado para URL
    })
    .then(response => {
        // Verifica se a resposta está OK (status 200)
        if (!response.ok) {
            // Se não estiver OK, lança um erro para ser capturado pelo .catch()
            throw new Error(`Erro HTTP! status: ${response.status}`);
        }
        return response.json(); // Analisa a resposta JSON
    })
    .then(data => {
        console.log('Dados recebidos:', data); // Registra os dados recebidos para depuração

        if (data.existe) {
            // Se o telefone existe, verifica se o endereço está vazio
            if (data.endereco_vazio) {
                // Se o endereço estiver vazio, redireciona para a página de cadastro de endereço
                window.location.href = `cadastro_endereco.html?telefone=${encodeURIComponent(telefone)}&nome=${encodeURIComponent(data.nome || '')}`;
            } else if (data.pedido_pendente_ou_aceito) {
                // Redireciona para a página de pedido na loja
                window.location.href = `pedido_na_loja.php?telefone=${encodeURIComponent(telefone)}&nome=${encodeURIComponent(data.nome || '')}&id_pedido=${encodeURIComponent(data.id_pedido || '')}&status_pedido=${encodeURIComponent(data.status_pedido || '')}&valor_total=${encodeURIComponent(data.valor_total || '')}&produtos_detalhes=${encodeURIComponent(data.produtos_detalhes || '')}&forma_pagamento=${encodeURIComponent(data.forma_pagamento || '')}&valor_pago=${encodeURIComponent(data.valor_pago || '')}&troco=${encodeURIComponent(data.troco || '')}&endereco=${encodeURIComponent(data.endereco || '')}&quadra=${encodeURIComponent(data.quadra || '')}&lote=${encodeURIComponent(data.lote || '')}&setor=${encodeURIComponent(data.setor || '')}&complemento=${encodeURIComponent(data.complemento || '')}&cidade=${encodeURIComponent(data.cidade || '')}`;
            } else if (data.pedido_em_entrega) {
                // Redireciona para a página de pedido em entrega
                window.location.href = `pedido_em_entrega.php?telefone=${encodeURIComponent(telefone)}&nome=${encodeURIComponent(data.nome || '')}`;
            } else {
                // Se não houver pedidos pendentes/aceitos/em entrega, redireciona para confirmação de endereço
                window.location.href = `confirmar_endereco.php?telefone=${encodeURIComponent(telefone)}&nome=${encodeURIComponent(data.nome || '')}`;
            }
        } else {
            // Se o número de telefone não existe, redireciona para o cadastro pessoal
            window.location.href = `cadastro_pessoal.html?telefone=${encodeURIComponent(telefone)}`;
        }
    })
    .catch(error => {
        console.error('Erro na requisição ou processamento:', error);
        mensagemDiv.textContent = 'Erro ao verificar o telefone. Tente novamente.';
        mensagemDiv.style.color = 'red';
    });
});
