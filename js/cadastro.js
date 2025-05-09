document.addEventListener('DOMContentLoaded', function () {
    const formularioCadastro = document.getElementById('formularioCadastro');
    const mensagemDiv = document.getElementById('mensagem');
    const urlParams = new URLSearchParams(window.location.search);
    const telefone = urlParams.get('telefone');
    document.getElementById('telefoneCadastro').value = telefone;

    // Opcional: Limpar campos se o cadastro falhar e voltar
    if (urlParams.has('telefone')) {
        document.getElementById('nome').value = '';
        document.getElementById('dt_Nascimento').value = '';
        document.getElementById('endereco').value = '';
        document.getElementById('quadra').value = '';
        document.getElementById('lote').value = '';
        document.getElementById('setor').value = '';
        document.getElementById('complemento').value = '';
        document.getElementById('cidade').value = '';
        document.getElementById('sexo_masculino').checked = false;
        document.getElementById('sexo_feminino').checked = false;
        document.getElementById('sorteio').checked = false;
    }

    formularioCadastro.addEventListener('submit', function (event) {
        event.preventDefault(); // Impede o envio do formulário padrão  
        let camposValidos = true;

        const nomeInput = document.getElementById('nome');
        const enderecoInput = document.getElementById('endereco');
        const quadraInput = document.getElementById('quadra');
        const loteInput = document.getElementById('lote');
        const setorInput = document.getElementById('setor');
        const complementoInput = document.getElementById('complemento');
        const cidadeInput = document.getElementById('cidade');
        const sexoInput = document.querySelector('input[name="sexo"]:checked');
        const dataNascimentoInput = document.getElementById('dt_Nascimento');
        const termosSorteioInput = document.getElementById('sorteio').checked;

        mensagemDiv.textContent = ''; // Limpa mensagens anteriores
        mensagemDiv.style.color = 'red';

        if (!termosSorteioInput) {
            mensagemDiv.textContent = 'Você deve aceitar os termos do sorteio.';
            camposValidos = false;
        } else if (!sexoInput) {
            mensagemDiv.textContent = 'Por favor, selecione seu sexo.';
            camposValidos = false;
        } else if (nomeInput.value.trim() === '') {
            mensagemDiv.textContent = 'Por favor, preencha seu nome.';
            camposValidos = false;
        } else if (dataNascimentoInput.value === '') {
            mensagemDiv.textContent = 'Por favor, preencha sua data de nascimento.';
            camposValidos = false;
        } else if (dataNascimentoInput.value > new Date().toISOString().split('T')[0]) {
            mensagemDiv.textContent = 'Data de nascimento inválida.';
            camposValidos = false;
        } else if (enderecoInput.value.trim() === '') {
            mensagemDiv.textContent = 'Por favor, preencha seu endereço.';
            camposValidos = false;
        } else if (quadraInput.value.trim() === '') {
            mensagemDiv.textContent = 'Por favor, preencha a quadra.';
            camposValidos = false;
        } else if (loteInput.value.trim() === '') {
            mensagemDiv.textContent = 'Por favor, preencha o lote.';
            camposValidos = false;
        } else if (setorInput.value.trim() === '') {
            mensagemDiv.textContent = 'Por favor, preencha o setor.';
            camposValidos = false;
        } else if (cidadeInput.value.trim() === '') {
            mensagemDiv.textContent = 'Por favor, preencha a cidade.';
            camposValidos = false;
        }

        if (camposValidos) {
            formularioCadastro.submit(); // Envia o formulário se tudo estiver válido
        }
    });

});