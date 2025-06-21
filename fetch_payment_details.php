<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

if (!isset($_GET['id_pedido'])) {
    echo json_encode(['success' => false, 'message' => 'ID do pedido não fornecido.']);
    $conn->close();
    exit();
}

$id_pedido = $_GET['id_pedido'];

$stmt = $conn->prepare("SELECT forma_pagamento, valor_total, valor_pago FROM pedidos WHERE id_pedido = ?");
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $pedido = $result->fetch_assoc();
    echo json_encode(['success' => true, 'forma_pagamento' => $pedido['forma_pagamento'], 'valor_total' => $pedido['valor_total'], 'valor_pago' => $pedido['valor_pago']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Pedido não encontrado.']);
}

$stmt->close();
$conn->close();
?>