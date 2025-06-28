<?php
header('Content-Type: application/json');

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
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$id_pedido = $input['id_pedido'] ?? null;
$forma_pagamento = $input['forma_pagamento'] ?? null;
$valor_pago = $input['valor_pago'] ?? null; // Will be null if not cash

if (!$id_pedido || !$forma_pagamento) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos para atualização do pagamento.']);
    $conn->close();
    exit();
}

// Prepare the statement based on whether valor_pago is provided
if ($forma_pagamento === 'Dinheiro') {
    $stmt = $conn->prepare("UPDATE pedidos SET forma_pagamento = ?, valor_pago = ? WHERE id_pedido = ?");
    $stmt->bind_param("sdi", $forma_pagamento, $valor_pago, $id_pedido);
} else {
    $stmt = $conn->prepare("UPDATE pedidos SET forma_pagamento = ?, valor_pago = NULL WHERE id_pedido = ?");
    $stmt->bind_param("si", $forma_pagamento, $id_pedido);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Pagamento atualizado com sucesso.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar pagamento: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>