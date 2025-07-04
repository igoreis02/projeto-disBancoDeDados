<?php
// Obtém os dados da URL
$telefone = isset($_GET['telefone']) ? htmlspecialchars($_GET['telefone']) : '';
$nome = isset($_GET['nome']) ? htmlspecialchars($_GET['nome']) : '';
$id_pedido = isset($_GET['id_pedido']) ? htmlspecialchars($_GET['id_pedido']) : '';
$status_pedido = isset($_GET['status_pedido']) ? htmlspecialchars($_GET['status_pedido']) : '';
$valor_total = isset($_GET['valor_total']) ? (float)$_GET['valor_total'] : 0.00;
$produtos_detalhes = isset($_GET['produtos_detalhes']) ? htmlspecialchars($_GET['produtos_detalhes']) : '';
$forma_pagamento = isset($_GET['forma_pagamento']) ? htmlspecialchars($_GET['forma_pagamento']) : '';
$valor_pago = isset($_GET['valor_pago']) ? (float)$_GET['valor_pago'] : null;
$troco = isset($_GET['troco']) ? (float)$_GET['troco'] : 0.00;

// Capitaliza a primeira letra do nome
$primeiroNome = explode(' ', $nome)[0];
$primeiroNome = ucwords($primeiroNome);

// Formata o valor total
$valor_total_formatado = number_format($valor_total, 2, ',', '.');

// Formata o valor pago e troco se existirem
$valor_pago_formatado = ($valor_pago !== null) ? number_format($valor_pago, 2, ',', '.') : '';
$troco_formatado = number_format($troco, 2, ',', '.');

// Conexão com o banco de dados para buscar o endereço completo
//$servername = "myshared2380";
//$username = "cadastrosouza";
//$password = "Souza@7498"; 
//$dbname = "cadastrosouza";

$servername = "localhost"; // Use "localhost" se estiver rodando localmente ou o nome do servidor se for remoto
$username = "root";
$password = ""; // Assuming no password for the user "cadastrosouza" based on the provided data.
$dbname = "cadastrosouza";

$conn = new mysqli($servername, $username, $password, $dbname);

$endereco_completo_formatado = 'Endereço não disponível';
if ($conn->connect_error) {
    error_log("Erro na conexão com o banco de dados em pedido_na_loja.php: " . $conn->connect_error);
} else {
    // Busca o id_cliente baseado no telefone
    $id_cliente = null;
    $stmt_cliente_id = $conn->prepare("SELECT id FROM clientes WHERE telefone = ?");
    if ($stmt_cliente_id) {
        $stmt_cliente_id->bind_param("s", $telefone);
        $stmt_cliente_id->execute();
        $result_cliente_id = $stmt_cliente_id->get_result();
        if ($result_cliente_id->num_rows > 0) {
            $row_cliente_id = $result_cliente_id->fetch_assoc();
            $id_cliente = $row_cliente_id['id'];
        }
        $stmt_cliente_id->close();
    }

    if ($id_cliente !== null) {
        $sql_endereco = "SELECT endereco, quadra, lote, setor, complemento, cidade FROM clientes WHERE id = ?";
        $stmt_endereco = $conn->prepare($sql_endereco);
        if ($stmt_endereco) {
            $stmt_endereco->bind_param("i", $id_cliente);
            $stmt_endereco->execute();
            $stmt_endereco->bind_result($endereco, $quadra, $lote, $setor, $complemento, $cidade);
            $stmt_endereco->fetch();
            $stmt_endereco->close();

            $endereco_completo_formatado = ucwords(htmlspecialchars($endereco)) . ', Qd ' . htmlspecialchars($quadra) . ', Lt ' . htmlspecialchars($lote);
            if (!empty($setor)) {
                $endereco_completo_formatado .= '<br>Setor: ' . ucwords(htmlspecialchars($setor));
            }
            if (!empty($complemento)) {
                $endereco_completo_formatado .= '<br>Complemento: ' . ucwords(htmlspecialchars($complemento));
            }
            $endereco_completo_formatado .= '<br>' . ucwords(htmlspecialchars($cidade));
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido na Loja</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .card {
            text-align: center;
            padding: 30px;
        }
        .card h1 {
            color: var(--cor-principal);
            margin-bottom: 0;
            padding-top: 90px; /* Adicionado para espaçamento superior */
        }
        /* Estilo para a frase específica */
        .card .message-paragraph {
            font-size: 1.1em;
            font-size: 1.4em;
            font-weight: 900;
            color:var(--cor-titulo);
        }
        .pedido-details {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 20px auto;
            max-width: 400px;
            text-align: left;
        }
        .pedido-details strong {
            color: var(--cor-principal);
        }
        .pedido-info{
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 8px;
            margin: 20px auto;
            max-width: 400px;
            text-align:center;
        }
        .form-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 30px;
        }
        .form-buttons button, .form-buttons a {
            padding: 12px 25px;
            font-size: 1.1em;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            color: white;
            transition: background-color 0.3s ease;
            display: block; /* Garante que ocupem a largura total */
            width: 100%; /* Garante que ocupem a largura total */
            box-sizing: border-box; /* Inclui padding e borda na largura total */
        }
        .form-buttons .exit-button {
            background-color:  #eb9f25; /* Vermelho para sair */
        }
        .form-buttons .exit-button:hover {
            background-color:rgb(197, 133, 31);
        }

        .form-buttons .edit-button {
            background-color: var(--cor-principal);
        }
        .form-buttons .edit-button:hover {
            background-color: var(--cor-secundaria);
        }

        .form-buttons .contato-btn {
            background-color: var(--cor-principal);
            color: white;
        }

        .form-buttons .contato-btn:hover {
            background-color: var(--cor-secundaria);
        }
        .whatsapp-icon {
            width: 24px; /* Ajuste o tamanho conforme necessário */
            height: 24px;
            vertical-align: middle; /* Alinha o ícone com o texto */
            margin-right: 8px; /* Espaço entre o ícone e o texto */
        }
               @media (max-width: 480px) {
    body {
        padding: 5px; /* Padding for smaller screens */
        margin: 0;
        align-items: center; /* Center content */
        justify-content: center; /* Center content */
        display: flex; /* Use flexbox for centering */
        height: 100vh; /* Full viewport height */
        
    }
    
    .card, .cardPedido { /* Styles for larger screens */
        display: flex;
        flex-direction: column;
        justify-content: center;
        background-color: #fff; /* Background color for the card */
        margin: 0; /* Or auto to center */
        width: 95%; /* Fixed width for larger screens */
        height: auto; /* Adjust height as needed */
        padding: 10px; /* Restore original padding for larger screens */
        margin: auto;
    }
    .form {
        width: 100%;
    }
}
    </style>
</head>
<body>
    <div class="background"></div>
    <div class="card">
        <img class="logo" src="imagens/logo.png" alt="Logo" />
        <h1>Olá, <?php echo $primeiroNome; ?>!</h1>
        <div class="pedido-info">   
        <p class="message-paragraph">Você tem um pedido que está na Loja esperando para sair.</p>
        </div>
        <div class="pedido-details">
            <p><strong>Endereço:</strong> <?php echo $endereco_completo_formatado; ?></p> <p><strong>Produtos:</strong> <?php echo $produtos_detalhes; ?></p>
            <p><strong>Valor Total:</strong> R$ <?php echo $valor_total_formatado; ?></p>
            <p><strong>Forma de Pagamento:</strong> <?php echo !empty($forma_pagamento) ? ucwords($forma_pagamento) : 'Não informado'; ?>
            <?php if ($forma_pagamento === 'dinheiro' && $valor_pago !== null): ?>
                (R$ <?php echo $valor_pago_formatado; ?>)
            <?php endif; ?>
            </p>
            <?php if ($forma_pagamento === 'dinheiro' && $valor_pago !== null): ?>
                <p><strong>Troco:</strong> R$ <?php echo $troco_formatado; ?></p>
            <?php endif; ?>
        </div>

        <div class="form-buttons">
            <a href="#" class="edit-button" id="editarPedidoBtn">Editar Pedido</a>
            <a href="pedido_cliente.html" class="exit-button">Sair</a>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const editarPedidoBtn = document.getElementById('editarPedidoBtn');
        if (editarPedidoBtn) {
            editarPedidoBtn.addEventListener('click', function(event) {
                event.preventDefault(); // Impede o comportamento padrão do link

                const telefone = "<?php echo htmlspecialchars($telefone); ?>";
                const id_pedido = "<?php echo htmlspecialchars($id_pedido); ?>";
                const status_pedido = "<?php echo htmlspecialchars($status_pedido); ?>";
                const valor_total = "<?php echo htmlspecialchars($valor_total); ?>";
                const produtos_detalhes = "<?php echo urlencode($produtos_detalhes); ?>";
                const forma_pagamento = "<?php echo urlencode($forma_pagamento); ?>";
                const valor_pago = "<?php echo htmlspecialchars($valor_pago); ?>";
                const troco = "<?php echo htmlspecialchars($troco); ?>";

                const url = `pedido.html?telefone=${telefone}&id_pedido=${id_pedido}&status_pedido=${status_pedido}&valor_total=${valor_total}&produtos_detalhes=${produtos_detalhes}&forma_pagamento=${forma_pagamento}&valor_pago=${valor_pago}&troco=${troco}`;

                // Redireciona para a página de edição do pedido
                window.location.replace(url);
            });
        }
    });
</script>
</body>
</html>
