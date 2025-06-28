<?php
//$servername = "myshared2380";
//$username = "cadastrosouza";
//$password = "Souza@7498"; 
//$dbname = "cadastrosouza";

$servername = "localhost"; // Use "localhost" se estiver rodando localmente ou o nome do servidor se for remoto
$username = "root";
$password = ""; // Assuming no password for the user "cadastrosouza" based on the provided data.
$dbname = "cadastrosouza";
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

$id_pedido = isset($_GET['id_pedido']) ? intval($_GET['id_pedido']) : 0;

if ($id_pedido === 0) {
    die("ID do pedido não fornecido.");
}

$sql = "
    SELECT
        p.id_pedido,
        p.status_pedido,
        c.nome AS cliente_nome,
        c.telefone AS cliente_telefone,
        c.endereco,
        c.quadra,
        c.lote,
        c.setor,
        c.complemento,
        c.cidade,
        p.valor_total,
        p.forma_pagamento,
        p.data_pedido,
        p.valor_pago,
        GROUP_CONCAT(CONCAT(ip.quantidade, 'x ', prod.nome, ' (R$ ', FORMAT(ip.preco_unitario, 2, 'pt_BR'), ')') SEPARATOR '<br>') AS produtos_detalhes,
        p.id_cliente
    FROM
        pedidos p
    JOIN
        clientes c ON p.id_cliente = c.id
    LEFT JOIN
        itens_pedido ip ON p.id_pedido = ip.id_pedido
    LEFT JOIN
        produtos prod ON ip.id_produto = prod.id_produtos
    WHERE
        p.id_pedido = ?
    GROUP BY
        p.id_pedido, p.status_pedido, c.nome, c.telefone, c.endereco, c.quadra, c.lote, c.setor, c.complemento, c.cidade, p.valor_total, p.forma_pagamento, p.data_pedido, p.valor_pago, p.id_cliente
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$result = $stmt->get_result();
$pedido = null;

if ($result->num_rows > 0) {
    $pedido = $result->fetch_assoc();

    // Format address
    $endereco_completo = ucwords(htmlspecialchars($pedido['endereco']) . ', Qd ' . htmlspecialchars($pedido['quadra']) . ', Lt ' . htmlspecialchars($pedido['lote']));
    if (!empty($pedido['setor'])) {
        $endereco_completo .= '<br>Setor: ' . ucwords(htmlspecialchars($pedido['setor']));
    }
    if (!empty($pedido['complemento'])) {
        $endereco_completo .= '<br>Complemento: ' . ucwords(htmlspecialchars($pedido['complemento']));
    }
    $endereco_completo .= '<br>' . ucwords(htmlspecialchars($pedido['cidade']));
    $pedido['endereco_completo'] = $endereco_completo;

    // Format payment and change
    $forma_pagamento_display = ucwords($pedido['forma_pagamento']);
    $troco = 0;
    if ($pedido['forma_pagamento'] === 'dinheiro' && $pedido['valor_pago'] !== null) {
        $valor_pago_formatado = number_format($pedido['valor_pago'], 2, ',', '.');
        $troco = $pedido['valor_pago'] - $pedido['valor_total'];
        $troco_formatado = number_format($troco, 2, ',', '.');
        $forma_pagamento_display .= " (Pago: R$ {$valor_pago_formatado})";
        $forma_pagamento_display .= "<br>Troco: R$ {$troco_formatado}";
    }
    $pedido['forma_pagamento_display'] = $forma_pagamento_display;
    $pedido['troco'] = $troco; // Store raw troco for conditional display

    // Format data_pedido
    $pedido['data_pedido_display'] = htmlspecialchars(date('d/m/Y H:i', strtotime($pedido['data_pedido'])));

} else {
    die("Pedido não encontrado.");
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir Pedido #<?php echo htmlspecialchars($pedido['id_pedido']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .container {
            width: 80mm; /* A common width for thermal receipts */
            margin: 0 auto;
            border: 1px solid #ccc;
            padding: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            text-align: center;
            color: #000;
        }
        .section {
            margin-bottom: 10px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 5px;
        }
        .section:last-of-type {
            border-bottom: none;
        }
        p {
            margin: 2px 0;
        }
        .text-bold {
            font-weight: bold;
        }
        .text-center {
            text-align: center;
        }
        .items-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        .item span:first-child {
            flex-grow: 1;
        }
        .total {
            font-size: 1.2em;
            font-weight: bold;
            text-align: right;
            margin-top: 10px;
        }
        @media print {
            body {
                margin: 0;
            }
            .container {
                border: none;
                box-shadow: none;
                width: auto;
                padding: 0;
            }
            /* Adjust font size for thermal printer if needed */
            body, p, span, li {
                font-size: 10pt; /* Example font size for thermal printers */
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Souza Gás</h1>
        <p class="text-center">Seu Gás, nossa Prioridade!</p>
        <p class="text-center">-----------------------------------</p>

        <h2>Pedido #<?php echo htmlspecialchars($pedido['id_pedido']); ?></h2>

        <div class="section">
            <p><span class="text-bold">Data/Hora:</span> <?php echo $pedido['data_pedido_display']; ?></p>
            <p><span class="text-bold">Status:</span> <?php echo htmlspecialchars(ucwords($pedido['status_pedido'])); ?></p>
        </div>

        <div class="section">
            <h3>Dados do Cliente</h3>
            <p><span class="text-bold">Nome:</span> <?php echo htmlspecialchars(ucwords($pedido['cliente_nome'])); ?></p>
            <p><span class="text-bold">Telefone:</span> <?php echo htmlspecialchars($pedido['cliente_telefone']); ?></p>
            <p><span class="text-bold">Endereço:</span><br><?php echo $pedido['endereco_completo']; ?></p>
        </div>

        <div class="section">
            <h3>Produtos do Pedido</h3>
            <ul class="items-list">
                <?php
                // Split the products_detalhes string and format each item
                $produtos = explode('<br>', $pedido['produtos_detalhes']);
                foreach ($produtos as $item) {
                    // Remove leading/trailing spaces and check if empty
                    $item = trim($item);
                    if (!empty($item)) {
                        echo '<li class="item">' . $item . '</li>';
                    }
                }
                ?>
            </ul>
        </div>

        <div class="section">
            <p><span class="text-bold">Forma de Pagamento:</span> <?php echo $pedido['forma_pagamento_display']; ?></p>
            <?php if ($pedido['forma_pagamento'] === 'dinheiro' && $pedido['valor_pago'] !== null): ?>
                <p><span class="text-bold">Valor Recebido:</span> R$ <?php echo htmlspecialchars(number_format($pedido['valor_pago'], 2, ',', '.')); ?></p>
                <p><span class="text-bold">Troco:</span> R$ <?php echo htmlspecialchars(number_format($pedido['troco'], 2, ',', '.')); ?></p>
            <?php endif; ?>
            <p class="total">Valor Total: R$ <?php echo htmlspecialchars(number_format($pedido['valor_total'], 2, ',', '.')); ?></p>
        </div>

        <p class="text-center">-----------------------------------</p>
        <p class="text-center">Obrigado pela preferência!</p>
        <p class="text-center">&copy; 2025 Souza Gás</p>
    </div>

    <script>
        // Automatically trigger the print dialog when the page loads
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>