document.addEventListener('DOMContentLoaded', function () {
    const formularioCadastroPessoal = document.getElementById('formularioCadastroPessoal');
    const mensagemDivPessoal = document.getElementById('mensagemPessoal');
    const urlParams = new URLSearchParams(window.location.search);
    const telefone = urlParams.get('telefone');
    const exitButton = document.querySelector('.exit-button');

    document.getElementById('telefonePessoal').value = telefone;

    formularioCadastroPessoal.addEventListener('submit', function (event) {
        event.preventDefault();

        const nomeInput = document.getElementById('nomePessoal');
        const dataNascimentoInput = document.getElementById('dt_NascimentoPessoal');
        const sexoInput = document.querySelector('input[name="sexo"]:checked');
        const termosSorteioInput = document.getElementById('sorteioPessoal').checked;

        let camposValidos = true;
        mensagemDivPessoal.textContent = '';
        mensagemDivPessoal.style.color = 'red';

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
            const formData = new FormData(formularioCadastroPessoal);

            fetch('salvar_dados_pessoais.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Use history.replaceState and then location.replace to avoid adding to history
                    const newUrl = `cadastro_endereco.html?telefone=${encodeURIComponent(data.telefone_cliente)}`; // assuming telefone is the correct parameter here.
                    window.history.replaceState(null, '', newUrl); // Replace current history entry
                    window.location.replace(newUrl); // Navigate without adding to history
                } else {
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