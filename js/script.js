const verificarForm = document.getElementById('formulario');
const mensagemDiv = document.getElementById('mensagem');
const telefoneInput = document.getElementById('telefone');
const senhaInput = document.getElementById('hidden');
const pesquisaTelefoneButton = document.getElementById('pesquisaTelefone');
const botaoVoltar = document.getElementsByClassName('voltar-inicio')
const termoPrivacidade = document.getElementById('termoPrivacidade');

telefoneInput.addEventListener('input', function () {
    const telefoneDigitado = telefoneInput.value;

    fetch('verificar_telefone-usuario.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `telefone=${telefoneDigitado}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.existe) {
            senhaInput.style.display = 'block';
            termoPrivacidade.style.display = 'none';
            pesquisaTelefoneButton.textContent = 'Entrar';
        } else {
            senhaInput.style.display = 'none';
            termoPrivacidade.style.display = 'block';
            pesquisaTelefoneButton.textContent = 'Próximo';
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        mensagemDiv.textContent = 'Erro ao verificar o telefone.';
        mensagemDiv.style.color = 'red';
    });
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

    if (senhaInput.style.display === 'block') {
        const senha = senhaInput.value;
        if (senha === '') {
            mensagemDiv.textContent = 'Por favor, insira a senha.';
            mensagemDiv.style.color = 'red';
            return;
        }

        fetch('verificar_senha.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `senha=${senha}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.senhaCorreta) {
                window.location.href = 'lista-clientes.html'; // Redirecionar para a lista
            } else {
                mensagemDiv.textContent = 'Senha incorreta.';
                mensagemDiv.style.color = 'red';
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            mensagemDiv.textContent = 'Erro ao verificar a senha.';
            mensagemDiv.style.color = 'red';
        });
        //criar botao voltar
        
    } else {
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
                    // Redireciona para sorteado.html com nome e numeroSorteado
                    window.location.href = `sorteado.html?nome=${encodeURIComponent(data.nome)}&numeroSorteado=${encodeURIComponent(data.numeroSorteado)}`;
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