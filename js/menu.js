document.addEventListener('DOMContentLoaded', function() {
    // === Lógica para o Modal de Redefinição de Senha (existente) ===
    const redefinirSenhaModal = document.getElementById('redefinirSenhaModal');
    const closeRedefinirSenhaModal = document.getElementById('closeRedefinirSenhaModal');
    const formRedefinirSenha = document.getElementById('formRedefinirSenha');
    const mensagemRedefinirSenha = document.getElementById('mensagemRedefinirSenha');

    // Funções para habilitar/desabilitar o menu (opcional, mas boa prática se o modal for bloqueante)
    function desabilitarMenu() {
        const menuButtonsContainer = document.getElementById('menuButtons');
        const voltarBtn = document.querySelector('.voltar-btn');
        if (menuButtonsContainer) {
            menuButtonsContainer.style.pointerEvents = 'none';
            menuButtonsContainer.style.opacity = '0.5';
        }
        if (voltarBtn) {
            voltarBtn.style.pointerEvents = 'none';
            voltarBtn.style.opacity = '0.5';
        }
    }

    function habilitarMenu() {
        const menuButtonsContainer = document.getElementById('menuButtons');
        const voltarBtn = document.querySelector('.voltar-btn');
        if (menuButtonsContainer) {
            menuButtonsContainer.style.pointerEvents = 'auto';
            menuButtonsContainer.style.opacity = '1';
        }
        if (voltarBtn) {
            voltarBtn.style.pointerEvents = 'auto';
            voltarBtn.style.opacity = '1';
        }
    }

    if (closeRedefinirSenhaModal) {
        closeRedefinirSenhaModal.addEventListener('click', function() {
            redefinirSenhaModal.classList.remove('is-active'); // Esconde o modal
            habilitarMenu(); // Re-habilita o menu
        });
    }

    if (formRedefinirSenha) {
        formRedefinirSenha.addEventListener('submit', function(event) {
            event.preventDefault();

            const novaSenha = document.getElementById('novaSenha').value;
            const confirmarNovaSenha = document.getElementById('confirmarNovaSenha').value;

            if (novaSenha !== confirmarNovaSenha) {
                mensagemRedefinirSenha.textContent = 'As senhas não coincidem!';
                mensagemRedefinirSenha.style.color = 'red';
                return;
            }

            if (novaSenha.length < 6) {
                mensagemRedefinirSenha.textContent = 'A senha deve ter no mínimo 6 caracteres!';
                mensagemRedefinirSenha.style.color = 'red';
                return;
            }

            const formData = new FormData(formRedefinirSenha);

            fetch('redefinir_senha.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mensagemRedefinirSenha.textContent = data.message;
                    mensagemRedefinirSenha.style.color = 'green';
                    setTimeout(() => {
                        redefinirSenhaModal.classList.remove('is-active'); // Esconde o modal
                        habilitarMenu(); // Re-habilita o menu
                    }, 2000);
                } else {
                    mensagemRedefinirSenha.textContent = data.message;
                    mensagemRedefinirSenha.style.color = 'red';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mensagemRedefinirSenha.textContent = 'Erro ao redefinir a senha.';
                mensagemRedefinirSenha.style.color = 'red';
            });
        });
    }

    // === NOVA Lógica para o Modal de Novo Usuário ===
    const novoUsuarioButton = document.getElementById('novoUsuario');
    const novoUsuarioModal = document.getElementById('novoUsuarioModal');
    const closeNovoUsuarioModal = document.getElementById('closeNovoUsuarioModal');
    const formNovoUsuario = document.getElementById('formNovoUsuario');
    const mensagemNovoUsuario = document.getElementById('mensagemNovoUsuario');

    if (novoUsuarioButton) {
        novoUsuarioButton.addEventListener('click', function() {
            novoUsuarioModal.classList.add('is-active'); // Exibe o modal adicionando a classe
            mensagemNovoUsuario.textContent = ''; // Limpa mensagens anteriores
            formNovoUsuario.reset(); // Limpa o formulário
        });
    }

    if (closeNovoUsuarioModal) {
        closeNovoUsuarioModal.addEventListener('click', function() {
            novoUsuarioModal.classList.remove('is-active'); // Esconde o modal removendo a classe
        });
    }

    // Fecha os modais se o usuário clicar fora deles
    window.addEventListener('click', function(event) {
        if (event.target == novoUsuarioModal) {
            novoUsuarioModal.classList.remove('is-active');
        }
        if (event.target == redefinirSenhaModal) {
            // Este caso só deve acontecer se o modal de redefinição não for obrigatório
            // ou se o usuário já tiver redefinido a senha e o modal estiver sendo usado de forma opcional.
            // Para o cenário de senha obrigatória, o "clique fora" pode não ser desejado.
            // Ajuste esta lógica conforme a UX desejada.
            redefinirSenhaModal.classList.remove('is-active');
            habilitarMenu(); // Re-habilita o menu se o modal de redefinição for fechado
        }
    });


    if (formNovoUsuario) {
        formNovoUsuario.addEventListener('submit', function(event) {
            event.preventDefault(); // Evita o envio padrão do formulário

            const formData = new FormData(formNovoUsuario);

            // Adiciona um valor padrão para status, se não estiver definido
            if (!formData.has('status')) {
                formData.append('status', 'Ativo'); 
            }

            fetch('cadastra_usuario_sistema.php', { // Envia para o novo script PHP
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mensagemNovoUsuario.textContent = data.message;
                    mensagemNovoUsuario.classList.add('success'); 
                    mensagemNovoUsuario.style.color = 'green';
                    formNovoUsuario.reset(); // Limpa o formulário após sucesso
                    setTimeout(() => {
                        novoUsuarioModal.classList.remove('is-active'); // Esconde o modal
                        mensagemNovoUsuario.classList.remove('success'); 
                    }, 3000); // Fecha o modal após 3 segundos
                } else {
                    mensagemNovoUsuario.textContent = data.message;
                    mensagemNovoUsuario.classList.remove('success'); 
                    mensagemNovoUsuario.style.color = 'red';
                }
            })
            .catch(error => {
                console.error('Erro ao cadastrar usuário:', error);
                mensagemNovoUsuario.textContent = 'Erro ao tentar cadastrar o usuário. Tente novamente.';
                mensagemNovoUsuario.classList.remove('success');
                mensagemNovoUsuario.style.color = 'red';
            });
        });
    }
    document.getElementById('clientes').addEventListener('click', function() {
            window.location.href = 'lista-clientes.html';
        });

        document.getElementById('produtos').addEventListener('click', function() {
            window.location.href = 'lista-produto.html';
        });

        document.getElementById('pedidos').addEventListener('click', function() {
            window.location.href = 'lista_pedidos.php';
        });

        // NEW EVENT LISTENER FOR ENTREGADORES
        document.getElementById('entregadores').addEventListener('click', function() {
            window.location.href = 'lista_entregadores.html';
        });
        document.getElementById('pedidosEmEntrega').addEventListener('click', function() {
            window.location.href = 'lista_pedidos_em_entrega.html';
        });
});