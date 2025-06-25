document.addEventListener('DOMContentLoaded', function () {
    const verificarFormLogin = document.getElementById('formularioLogin');
    const mensagemDivLogin = document.getElementById('mensagemLogin');
    const emailInputLogin = document.getElementById('emailLogin');
    const senhaInputLogin = document.getElementById('senhaLogin');
    const acessarSistemaBtn = document.getElementById('acessarSistemaBtn');

    verificarFormLogin.addEventListener('submit', function (event) {
        event.preventDefault(); // Impede o envio do formulário padrão

        const email = emailInputLogin.value.trim();
        const senha = senhaInputLogin.value;

        mensagemDivLogin.textContent = ''; // Limpa mensagens anteriores
        mensagemDivLogin.style.color = 'red'; // Cor padrão para erro

        if (email === '') {
            mensagemDivLogin.textContent = 'Por favor, digite seu e-mail.';
            return;
        }

        if (senha === '') {
            mensagemDivLogin.textContent = 'Por favor, digite sua senha.';
            return;
        }

        // Requisição AJAX para verificar o login (email e senha)
        fetch('verificar_login_usuario.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `email=${encodeURIComponent(email)}&senha=${encodeURIComponent(senha)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Se o login foi bem-sucedido
                if (data.redefinir_senha) {
                    // Se a redefinicao de senha e obrigatoria, redireciona para menu.php
                    // menu.php lidara com a exibicao do modal devido a flag de sessao
                    window.location.href = `menu.php`; 
                } else {
                    // Login bem-sucedido e senha ja redefinida, redireciona para menu.php
                    window.location.href = `menu.php`;
                }
            } else {
                // Login falhou
                mensagemDivLogin.textContent = data.message || 'E-mail ou senha inválidos.';
            }
        })
        .catch(error => {
            console.error('Erro na requisição de login:', error);
            mensagemDivLogin.textContent = 'Erro de comunicação com o servidor. Tente novamente.';
        });
    });
});
