<?php
session_start(); // Inicia ou resume a sessão
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Verifica se a redefinição de senha é obrigatória
$redefinir_senha_obrigatoria = isset($_SESSION['redefinir_senha_obrigatoria']) && $_SESSION['redefinir_senha_obrigatoria'] === true;

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Menu</title>
    <style>
        /* Novo contêiner principal para agrupar todas as linhas de botões */
        .menu-buttons-container {
            display: flex;
            flex-direction: column; /* Empilha as linhas de botões verticalmente */
            gap: 15px; /* Espaçamento entre as linhas de botões */
            width: 100%;
        }

        /* Estilos para cada linha de botões */
        .menu-row {
            display: flex;
            justify-content: space-between;
            gap: 15px; /* Espaçamento entre botões na mesma linha */
        }

        /* Para as linhas com três botões, ajustar o espaçamento */
        .menu-row.three-buttons .menu-button {
            flex: 1; /* Faz os botões ocuparem espaço igualmente */
            min-width: 0; /* Permite que eles diminuam se necessário */
        }

        /* Estilo básico para os botões do menu */
        .menu-button {
            flex: 1; /* Garante que os botões se expandam para preencher o espaço */
            padding: 20px;
            font-size: 1.2em;
            color: white;
            background-color: var(--cor-principal);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            text-align: center;
            text-decoration: none; /* Em caso de ser um <a> estilizado como botão */
            display: flex; /* Para centralizar o texto verticalmente */
            align-items: center;
            justify-content: center;
            box-sizing: border-box; /* Inclui padding na largura total */
        }

        .menu-button:hover {
            background-color: var(--cor-secundaria);
            transform: translateY(-3px);
        }

        /* Estilo para o botão "Sair" na parte inferior */
        .voltar-btn {
            display: block;
            width: 100%;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
            background-color: #eb9f25; /* Cor de destaque para sair */
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1.1em;
            transition: background-color 0.3s ease;
            box-sizing: border-box;
        }

        .voltar-btn:hover {
            background-color: #d18d20;
        }

        /* Estilos para os Modais (Redefinição de Senha e Novo Usuário) */
        .modal {
            display: none; /* Escondido por padrão */
            position: fixed; /* Fica por cima de tudo */
            z-index: 1000; /* Z-index alto para ficar acima de outros elementos */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto; /* Habilita scroll se o conteúdo for muito grande */
            background-color: rgba(0, 0, 0, 0.7); /* Fundo semi-transparente */
            /* Removemos display: flex; daqui, ele será aplicado apenas quando a classe is-active estiver presente */
        }

        .modal.is-active { /* Nova classe para quando o modal está ativo */
            display: flex; /* Exibe o modal e o centraliza */
            justify-content: center;
            align-items: center;
        }

        .modal-content.card {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%; /* Ajuste a largura conforme necessário */
            max-width: 500px; /* Largura máxima */
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: relative; /* Para o botão fechar */
        }
        
        /* Para o card no modal não ter o pseudo-elemento ::before */
        .modal-content.card::before {
            content: none;
        }

        .modal-content.card h2 {
            text-align: center;
            margin-bottom: 20px;
            color: var(--cor-principal);
        }

        .modal-content.card label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .modal-content.card input[type="text"],
        .modal-content.card input[type="email"],
        .modal-content.card input[type="password"],
        .modal-content.card select,
        .modal-content.card textarea { /* Adicionado textarea para endereço */
            width: calc(100% - 20px); /* 100% menos o padding */
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .modal-content.card button {
            width: 100%;
            padding: 12px;
            background-color: var(--cor-principal);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            transition: background-color 0.3s ease;
        }

        .modal-content.card button:hover {
            background-color: var(--cor-secundaria);
        }

        .modal-content.card .message {
            margin-top: 15px;
            text-align: center;
            color: red; /* Cor padrão para erro */
            font-weight: bold;
        }
        /* Para mensagens de sucesso no modal */
        .modal-content.card .message.success {
            color: green;
        }


        .modal-content.card .close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }

        .modal-content.card .close:hover,
        .modal-content.card .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Ajustes para telas menores */
        @media (max-width: 768px) {
            .menu-row {
                flex-direction: column; /* Empilha botões verticalmente em telas menores */
            }
            .menu-button {
                width: 100%; /* Botões ocupam a largura total em telas menores */
            }
            .modal-content.card {
                width: 95%; /* Ocupa mais largura em telas menores */
            }
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <img class="logo" src="imagens/logo.png" alt="Logo" />
        <h2>Menu Principal</h2>

        <div class="menu-buttons-container" id="menuButtons">
            <div class="menu-row">
                <button class="menu-button" id="pedidos">Pedidos</button>
            </div>
            <div class="menu-row three-buttons">
                <button class="menu-button" id="clientes">Clientes</button>
                <button class="menu-button" id="produtos">Produtos</button>
                <button class="menu-button" id="entregadores">Entregadores</button>
            </div>

            <div class="menu-row">
                <button class="menu-button" id="pedidosEmEntrega">Pedidos em Entrega</button>
            </div>
            <div class="menu-row">
                <button class="menu-button" id="novoUsuario">Novo Usuário</button>
            </div>
        </div>
        <a href="logout.php" class="voltar-btn">Sair</a>
    </div>
    <div class="footer">
        <p>&copy; 2025 Souza Gás. Todos os direitos reservados.</p>
    </div>

    <!-- Modal de Redefinição de Senha Existente -->
    <div id="redefinirSenhaModal" class="modal">
        <div class="modal-content card">
            <span class="close" id="closeRedefinirSenhaModal">&times;</span>
            <h2>Redefinir Senha</h2>
            <p>Por segurança, você deve redefinir sua senha.</p>
            <form id="formRedefinirSenha" class="form">
                <input type="hidden" id="userId" name="userId" value="<?php echo htmlspecialchars($user_id); ?>">
                <label for="novaSenha">Nova Senha:</label>
                <input type="password" id="novaSenha" name="novaSenha" required minlength="6"><br>

                <label for="confirmarNovaSenha">Confirmar Nova Senha:</label>
                <input type="password" id="confirmarNovaSenha" name="confirmarNovaSenha" required minlength="6"><br>
                
                <div id="mensagemRedefinirSenha" class="message"></div>
                <button type="submit">Salvar Nova Senha</button>
            </form>
        </div>
    </div>

    <!-- NOVO MODAL: Novo Usuário -->
    <div id="novoUsuarioModal" class="modal">
        <div class="modal-content card">
            <span class="close" id="closeNovoUsuarioModal">&times;</span>
            <h2>Novo Usuário</h2>
            <p>Preencha os dados para cadastrar um novo usuário. A senha padrão será "12345".</p>
            <form id="formNovoUsuario" class="form">
                <label for="novoNome">Nome:</label>
                <input type="text" id="novoNome" name="nome" required><br>

                <label for="novoTelefone">Telefone:</label>
                <input type="text" id="novoTelefone" name="telefone" required><br>

                <label for="novoEmail">Email:</label>
                <input type="email" id="novoEmail" name="email" required><br>

                <label for="novoTipoUsuario">Tipo de Usuário:</label>
                <select id="novoTipoUsuario" name="tipo_usuario" required>
                    <option value="">Selecione...</option>
                    <option value="administrador">Administrador</option>
                    <option value="socio">Sócio</option>
                    <option value="atendente">Atendente</option>
                    <option value="entregador">Entregador</option>
                </select><br>

                <label for="novoEndereco">Endereço:</label>
                <textarea id="novoEndereco" name="endereco" rows="3"></textarea><br>
                
                <div id="mensagemNovoUsuario" class="message"></div>
                <button type="submit">Cadastrar Usuário</button>
            </form>
        </div>
    </div>
    <!-- FIM NOVO MODAL -->

   <script src="js/menu.js"></script> 
    <script>
        // Lógica para desabilitar botões do menu se o modal de redefinição de senha for obrigatório
        window.onload = function() {
            const redefinirSenhaObrigatoria = <?php echo json_encode($redefinir_senha_obrigatoria); ?>;
            const redefinirSenhaModal = document.getElementById('redefinirSenhaModal');
            const menuButtonsContainer = document.getElementById('menuButtons');
            const voltarBtn = document.querySelector('.voltar-btn');

            if (redefinirSenhaObrigatoria) {
                redefinirSenhaModal.classList.add('is-active'); // Exibe o modal usando a nova classe
                if (menuButtonsContainer) {
                    menuButtonsContainer.style.pointerEvents = 'none'; // Desabilita cliques
                    menuButtonsContainer.style.opacity = '0.5'; // Deixa o menu transparente
                }
                if (voltarBtn) {
                    voltarBtn.style.pointerEvents = 'none'; // Desabilita o botão Sair
                    voltarBtn.style.opacity = '0.5'; // Deixa o botão Sair transparente
                }
            } else {
                // Se a redefinicao nao e obrigatoria, habilita o menu.
                // Isso e importante para garantir que o menu esteja sempre acessivel
                // se o usuario ja redefiniu a senha ou se a flag nao estava setada.
                if (menuButtonsContainer) {
                    menuButtonsContainer.style.pointerEvents = 'auto'; 
                    menuButtonsContainer.style.opacity = '1';
                }
                if (voltarBtn) {
                    voltarBtn.style.pointerEvents = 'auto'; 
                    voltarBtn.style.opacity = '1';
                }
            }
        };
    </script>
</body>

</html>
</body>

</html>