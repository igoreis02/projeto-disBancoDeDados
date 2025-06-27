<?php
header('Content-Type: application/json'); // Garante que a resposta é JSON


//$servername = "myshared2380";
//$username = "cadastrosouza";
//$password = "Souza@7498"; 
//$dbname = "cadastrosouza";

$servername = "localhost"; // Use "localhost" se estiver rodando localmente ou o nome do servidor se for remoto
$username = "root";
$password = ""; // Assumindo nenhuma senha para o usuário "cadastrosouza" com base nos dados fornecidos.
$dbname = "cadastrosouza";


$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro na conexão: ' . $conn->connect_error]);
    exit();
}

$telefone = $_POST['telefone'];

// Primeiro, verifica se o cliente existe e obtém seus dados
$sql_cliente = "SELECT id, nome, endereco, quadra, lote, setor, complemento, cidade FROM clientes WHERE telefone = ?";
$stmt_cliente = $conn->prepare($sql_cliente);
$stmt_cliente->bind_param("s", $telefone);
$stmt_cliente->execute();
$result_cliente = $stmt_cliente->get_result();

$response = ['existe' => false];

if ($result_cliente->num_rows > 0) {
    $cliente = $result_cliente->fetch_assoc();
    $response['existe'] = true;
    $response['nome'] = $cliente['nome'];
    $response['endereco'] = $cliente['endereco'];
    $response['quadra'] = $cliente['quadra'];
    $response['lote'] = $cliente['lote'];
    $response['setor'] = $cliente['setor'];
    $response['complemento'] = $cliente['complemento'];
    $response['cidade'] = $cliente['cidade'];

    // Adiciona verificação se o campo 'endereco' está nulo ou vazio
    $response['endereco_vazio'] = (empty($cliente['endereco']));

    // Agora, verifica se há pedidos pendentes, aceitos ou em entrega para este cliente
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
            p.id_cliente = ? AND p.status_pedido IN ('Pendente', 'Aceito', 'Entrega')
        GROUP BY
            p.id_pedido, p.status_pedido, p.valor_total, p.forma_pagamento, p.valor_pago
        ORDER BY
            p.data_pedido DESC
        LIMIT 1"; // Pega o pedido mais recente

    $stmt_pedido = $conn->prepare($sql_pedido);
    $stmt_pedido->bind_param("i", $cliente['id']);
    $stmt_pedido->execute();
    $result_pedido = $stmt_pedido->get_result();

    if ($result_pedido->num_rows > 0) {
        $pedido = $result_pedido->fetch_assoc();
        $response['id_pedido'] = $pedido['id_pedido'];
        $response['status_pedido'] = $pedido['status_pedido'];
        $response['valor_total'] = $pedido['valor_total'];
        $response['produtos_detalhes'] = $pedido['produtos_detalhes'];
        
        // Garante que a forma_pagamento e valor_pago sejam strings/números válidos, não NULL
        $response['forma_pagamento'] = $pedido['forma_pagamento'] ?? '';
        $response['valor_pago'] = $pedido['valor_pago'] ?? 0.00;

        // Debugging: Log values before sending
        error_log("DEBUG verificar_telefone.php: forma_pagamento do BD: " . ($pedido['forma_pagamento'] ?? 'NULL'));
        error_log("DEBUG verificar_telefone.php: valor_pago do BD: " . ($pedido['valor_pago'] ?? 'NULL'));
        error_log("DEBUG verificar_telefone.php: valor_total do BD: " . ($pedido['valor_total'] ?? 'NULL'));

        // Calcula o troco se o pagamento for em dinheiro
        $troco = 0;
        if ($response['forma_pagamento'] === 'dinheiro' && $response['valor_pago'] !== null) {
            $troco = $response['valor_pago'] - $response['valor_total'];
        }
        $response['troco'] = $troco;
        error_log("DEBUG verificar_telefone.php: troco calculado: " . $troco);


        if ($pedido['status_pedido'] == 'Pendente' || $pedido['status_pedido'] == 'Aceito') {
            $response['pedido_pendente_ou_aceito'] = true;
        } else if ($pedido['status_pedido'] == 'Entrega') {
            $response['pedido_em_entrega'] = true;
        }
    }
    $stmt_pedido->close();
}

$stmt_cliente->close();
$conn->close();

echo json_encode($response);
?>
