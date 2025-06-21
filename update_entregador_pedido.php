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

// Recebe os dados via POST (JSON)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$id_pedido = isset($data['id_pedido']) ? (int)$data['id_pedido'] : 0;
$id_entregador = isset($data['id_entregador']) ? (int)$data['id_entregador'] : 0;

if ($id_pedido <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID do pedido inválido.']);
    $conn->close();
    exit();
}

if ($id_entregador <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID do entregador inválido.']);
    $conn->close();
    exit();
}

$stmt = $conn->prepare("UPDATE pedidos SET id_entregador = ? WHERE id_pedido = ?");
$stmt->bind_param("ii", $id_entregador, $id_pedido);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Entregador do pedido atualizado com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhuma alteração feita ou pedido não encontrado.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar entregador: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
