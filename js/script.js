const verificarForm = document.getElementById('formulario');
const mensagemDiv = document.getElementById('mensagem');
const telefoneInput = document.getElementById('telefone');
const senhaInput = document.getElementById('hidden');
const pesquisaTelefoneButton = document.getElementById('pesquisaTelefone');
const botaoVoltar = document.getElementsByClassName('voltar-inicio')
const privacidade = document.getElementById('privacidade');
const privacidadeInput = document.querySelector('.checkbox');

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
            privacidadeInput.style.display = 'none';
            privacidade.checked = true; // Marcar o checkbox
            pesquisaTelefoneButton.textContent = 'Entrar';
        } else {
            senhaInput.style.display = 'none';
            privacidadeInput.style.display = 'block';
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
                window.location.href = 'menu.html'; // Redirecionar para o menu
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
                  // Redireciona para confirmar_endereco.php com os dados do usuário
                    window.location.href = `confirmar_endereco.php?telefone=${encodeURIComponent(telefone)}&nome=${encodeURIComponent(data.nome)}&endereco=${encodeURIComponent(data.endereco)}&quadra=${encodeURIComponent(data.quadra)}&lote=${encodeURIComponent(data.lote)}&setor=${encodeURIComponent(data.setor)}&complemento=${encodeURIComponent(data.complemento)}&cidade=${encodeURIComponent(data.cidade)}`;
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

