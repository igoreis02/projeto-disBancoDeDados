<?php
// Obtém o telefone da URL
$telefone = isset($_GET['telefone']) ? htmlspecialchars($_GET['telefone']) : '';

// Inicializa variáveis para os dados do cliente e pedido
$nome = '';
$primeiroNome = '';
$id_pedido = '';
$status_pedido = '';
$valor_total = 0.00;
$produtos_detalhes = 'Nenhum produto encontrado.';
$forma_pagamento = 'Não informado';
$valor_pago = null;
$troco = 0.00;
$endereco_completo_formatado = 'Endereço não disponível.';

// Conexão com o banco de dados
//$servername = "myshared2380";
//$username = "cadastrosouza";
//$password = "Souza@7498"; 
//$dbname = "cadastrosouza";

$servername = "localhost"; // Use "localhost" se estiver rodando localmente ou o nome do servidor se for remoto
$username = "root";
$password = ""; // Assuming no password for the user "cadastrosouza" based on the provided data.
$dbname = "cadastrosouza";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log("Erro na conexão com o banco de dados em pedido_em_entrega.php: " . $conn->connect_error);
    // Em caso de erro de conexão, os valores padrão serão usados.
} else {
    // 1. Buscar id_cliente e nome do cliente
    $id_cliente = null;
    $stmt_cliente = $conn->prepare("SELECT id, nome, endereco, quadra, lote, setor, complemento, cidade FROM clientes WHERE telefone = ?");
    if ($stmt_cliente) {
        $stmt_cliente->bind_param("s", $telefone);
        $stmt_cliente->execute();
        $result_cliente = $stmt_cliente->get_result();
        if ($result_cliente->num_rows > 0) {
            $cliente_data = $result_cliente->fetch_assoc();
            $id_cliente = $cliente_data['id'];
            $nome = $cliente_data['nome'];
            $primeiroNome = ucwords(explode(' ', $nome)[0]);

            // Formata o endereço completo
            $endereco_completo_formatado = ucwords(htmlspecialchars($cliente_data['endereco'])) . ', Qd ' . htmlspecialchars($cliente_data['quadra']) . ', Lt ' . htmlspecialchars($cliente_data['lote']);
            if (!empty($cliente_data['setor'])) {
                $endereco_completo_formatado .= '<br>Setor: ' . ucwords(htmlspecialchars($cliente_data['setor']));
            }
            if (!empty($cliente_data['complemento'])) {
                $endereco_completo_formatado .= '<br>Complemento: ' . ucwords(htmlspecialchars($cliente_data['complemento']));
            }
            $endereco_completo_formatado .= '<br>' . ucwords(htmlspecialchars($cliente_data['cidade']));

        }
        $stmt_cliente->close();
    } else {
        error_log("Erro ao preparar a busca de cliente em pedido_em_entrega.php: " . $conn->error);
    }

    // 2. Se o cliente foi encontrado, buscar o pedido em entrega mais recente
    if ($id_cliente !== null) {
        $sql_pedido = "
            SELECT
                p.id_pedido,
                p.status_pedido,
                p.valor_total,
                p.forma_pagamento,
                p.valor_pago,
                GROUP_CONCAT(CONCAT(ip.quantidade, 'x ', prod.nome) SEPARATOR ', ') AS produtos_detalhes
            FROM
                pedidos p
            JOIN
                itens_pedido ip ON p.id_pedido = ip.id_pedido
            JOIN
                produtos prod ON ip.id_produto = prod.id_produtos
            WHERE
                p.id_cliente = ? AND p.status_pedido = 'Entrega'
            GROUP BY
                p.id_pedido, p.status_pedido, p.valor_total, p.forma_pagamento, p.valor_pago
            ORDER BY
                p.data_pedido DESC
            LIMIT 1";

        $stmt_pedido = $conn->prepare($sql_pedido);
        if ($stmt_pedido) {
            $stmt_pedido->bind_param("i", $id_cliente);
            $stmt_pedido->execute();
            $result_pedido = $stmt_pedido->get_result();

            if ($result_pedido->num_rows > 0) {
                $pedido_data = $result_pedido->fetch_assoc();
                $id_pedido = $pedido_data['id_pedido'];
                $status_pedido = $pedido_data['status_pedido'];
                $valor_total = (float)$pedido_data['valor_total'];
                $produtos_detalhes = $pedido_data['produtos_detalhes'];
                $forma_pagamento = $pedido_data['forma_pagamento'] ?? 'Não informado';
                $valor_pago = $pedido_data['valor_pago'] ?? null;

                // Calcula o troco
                if ($forma_pagamento === 'dinheiro' && $valor_pago !== null) {
                    $troco = $valor_pago - $valor_total;
                }
            }
            $stmt_pedido->close();
        } else {
            error_log("Erro ao preparar a busca de pedido em pedido_em_entrega.php: " . $conn->error);
        }
    }
    $conn->close();
}

// Formata os valores para exibição
$valor_total_formatado = number_format($valor_total, 2, ',', '.');
$valor_pago_formatado = ($valor_pago !== null) ? number_format($valor_pago, 2, ',', '.') : '';
$troco_formatado = number_format($troco, 2, ',', '.');

// Construir a mensagem do WhatsApp
$whatsapp_message = "Olá, meu nome é " . $primeiroNome . "!%0A";
$whatsapp_message .= "Gostaria de saber sobre o meu pedido em entrega:%0A%0A";
$whatsapp_message .= "Endereço: " . strip_tags($endereco_completo_formatado) . "%0A"; // Remove HTML tags for WhatsApp
$whatsapp_message .= "Produtos: " . $produtos_detalhes . "%0A";
$whatsapp_message .= "Valor Total: R$ " . $valor_total_formatado . "%0A";
$whatsapp_message .= "Forma de Pagamento: " . ucwords($forma_pagamento);

if ($forma_pagamento === 'dinheiro' && $valor_pago !== null) {
    $whatsapp_message .= " (R$ " . $valor_pago_formatado . ")%0A";
    $whatsapp_message .= "Troco: R$ " . $troco_formatado . "%0A";
} else {
    $whatsapp_message .= "%0A";
}

// Número de WhatsApp da empresa (substitua pelo seu número)
$whatsapp_number = "5562993997054"; // Exemplo: 55 DDD NÚMERO (sem espaços ou hifens)
$whatsapp_link = "https://wa.me/" . $whatsapp_number . "?text=" . $whatsapp_message;

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido em Entrega</title>
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
        <p class="message-paragraph">Seu pedido está em entrega. Acompanhe os detalhes:</p>
        </div>
        <div class="pedido-details">
            <p><strong>Endereço:</strong> <?php echo $endereco_completo_formatado; ?></p>
            <p><strong>Produtos:</strong> <?php echo $produtos_detalhes; ?></p>
            <p><strong>Valor Total:</strong> R$ <?php echo $valor_total_formatado; ?></p>
            <p><strong>Forma de Pagamento:</strong> <?php echo ucwords($forma_pagamento); ?>
            <?php if ($forma_pagamento === 'dinheiro' && $valor_pago !== null): ?>
                (R$ <?php echo $valor_pago_formatado; ?>)
            <?php endif; ?>
            </p>
            <?php if ($forma_pagamento === 'dinheiro' && $valor_pago !== null): ?>
                <p><strong>Troco:</strong> R$ <?php echo $troco_formatado; ?></p>
            <?php endif; ?>
        </div>

        <div class="form-buttons">
            <a href="<?php echo $whatsapp_link; ?>" class="contato-btn" target="_blank"><img src="imagens/whatsapp_icon.webp" alt="WhatsApp" class="whatsapp-icon">Entrar em contato</a>
            <a href="pedido_cliente.html" class="exit-button">Sair</a>
        </div>
    </div>
</body>
</html>