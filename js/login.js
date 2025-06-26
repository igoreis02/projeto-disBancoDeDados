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
                // Se o login foi bem-sucedido, use a URL de redirecionamento fornecida pelo PHP
                if (data.redirect) {
                    window.location.href = data.redirect; 
                } else if (data.redefinir_senha) {
                    // Fallback para a lógica de redefinição de senha, embora 'redirect' deva ser preferencial
                    window.location.href = `menu.php`; 
                } else {
                    // Fallback se nenhum redirecionamento específico for fornecido (raro com a nova lógica PHP)
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