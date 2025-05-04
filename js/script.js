const verificarForm = document.getElementById('formulario');
const mensagemDiv = document.getElementById('mensagem');
const telefoneInput = document.getElementById('telefone');
const senhaInput = document.getElementById('hidden');
const pesquisaTelefoneButton = document.getElementById('pesquisaTelefone');





// Adiciona o evento de clique no botão "Próximo"
telefoneInput.addEventListener('input', function () {
    if (telefoneInput.value === '62993997054') {
        senhaInput.style.display = 'block';
        pesquisaTelefoneButton.textContent = 'Entrar';
    } else {
        pesquisaTelefoneButton.textContent = 'Próximo';
    }
});

verificarForm.addEventListener('submit', function (event) {
    event.preventDefault();
    const telefone = telefoneInput.value;
    const privacidade = document.getElementById('privacidade').checked;

    if (!privacidade) {
        mensagemDiv.textContent = 'Aceite os termos de privacidade.';
        mensagemDiv.style.color = 'red';
        return;
    }

    if (telefone === '') {
        mensagemDiv.textContent = 'Por favor, insira o telefone.';
        mensagemDiv.style.color = 'red';
        return;
    }
    if (telefone.length < 10 || telefone.length > 11) {
        mensagemDiv.textContent = 'O telefone deve ter entre 10 e 11 dígitos.';
        mensagemDiv.style.color = 'red';
        return;
    }
    if (!/^\d+$/.test(telefone)) {
        mensagemDiv.textContent = 'O telefone deve conter apenas números.';
        mensagemDiv.style.color = 'red';
        return;
    }


    if (telefone === '62993997054' && senhaInput.style.display === 'block') {
        const senha = senhaInput.value;
        console.log(senha);
        if (senha === '') {
            mensagemDiv.textContent = 'Por favor, insira a senha.';
            mensagemDiv.style.color = 'red';
            return;
        }


       /* fetch('verificar_senha.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `telefone=${telefone}&senha=${senha}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.senhaCorreta) {
                    window.location.href = 'lista_clientes.php'; // Redirecionar para a lista
                } else {
                    mensagemDiv.textContent = 'Senha incorreta.';
                    mensagemDiv.style.color = 'red';
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                mensagemDiv.textContent = 'Erro ao verificar a senha.';
                mensagemDiv.style.color = 'red';
            });*/
        // Simulação de verificação de senha (substitua pelo seu código real)      
        if (senha === '749870') { // Substitua pela lógica real de verificação de senha
            window.location.href = 'lista_clientes.php'; // Redirecionar para a lista
        } else {
            mensagemDiv.textContent = 'Senha incorreta.';
            mensagemDiv.style.color = 'red';
        }
    } else {
        // Restante do código para verificar o telefone e ir para cadastro.html
        fetch('verificar_telefone.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `telefone=${telefone}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.existe) {
                    window.location.href = `sorteado.html`;
                } else {
                    window.location.href = `cadastro.html?telefone=${telefone}`;
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                mensagemDiv.textContent = 'Erro ao verificar o telefone.';
                mensagemDiv.style.color = 'red';
            });
    }
});

// A


