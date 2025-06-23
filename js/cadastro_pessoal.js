document.addEventListener('DOMContentLoaded', function () {
    const formularioCadastroPessoal = document.getElementById('formularioCadastroPessoal');
    const mensagemDivPessoal = document.getElementById('mensagemPessoal');
    const urlParams = new URLSearchParams(window.location.search);
    const telefone = urlParams.get('telefone');

    // Preenche o campo de telefone hidden
    document.getElementById('telefonePessoal').value = telefone;

    formularioCadastroPessoal.addEventListener('submit', function (event) {
        event.preventDefault(); // Impede o envio do formulário padrão

        // Recupera os valores dos campos
        const nomeInput = document.getElementById('nomePessoal');
        const dataNascimentoInput = document.getElementById('dt_NascimentoPessoal');
        const sexoInput = document.querySelector('input[name="sexo"]:checked');
        const termosSorteioInput = document.getElementById('sorteioPessoal').checked;

        let camposValidos = true;
        mensagemDivPessoal.textContent = ''; // Limpa mensagens anteriores
        mensagemDivPessoal.style.color = 'red';

        // Validação dos campos pessoais
        if (nomeInput.value.trim() === '') {
            mensagemDivPessoal.textContent = 'Por favor, preencha seu nome completo.';
            camposValidos = false;
        } else if (dataNascimentoInput.value === '') {
            mensagemDivPessoal.textContent = 'Por favor, preencha sua data de nascimento.';
            camposValidos = false;
        } else if (dataNascimentoInput.value > new Date().toISOString().split('T')[0]) {
            mensagemDivPessoal.textContent = 'Data de nascimento inválida.';
            camposValidos = false;
        } else if (!sexoInput) {
            mensagemDivPessoal.textContent = 'Por favor, selecione seu sexo.';
            camposValidos = false;
        } else if (!termosSorteioInput) {
            mensagemDivPessoal.textContent = 'Você deve aceitar os termos do sorteio.';
            camposValidos = false;
        }

        if (camposValidos) {
            // Se os campos são válidos, envie os dados pessoais via AJAX
            const formData = new FormData(formularioCadastroPessoal);

            fetch('salvar_dados_pessoais.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // AQUI: Usamos data.telefone_cliente para o redirecionamento
                    window.location.href = `cadastro_endereco.html?telefone=${encodeURIComponent(data.telefone_cliente)}`;
                } else {
                    // Exibe a mensagem de erro retornada pelo PHP
                    mensagemDivPessoal.textContent = data.message || 'Erro ao cadastrar dados pessoais.';
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                mensagemDivPessoal.textContent = 'Erro de comunicação com o servidor ao salvar dados pessoais.';
            });
        }
    });
});