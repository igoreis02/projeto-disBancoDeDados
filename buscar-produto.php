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

$id_produto = $_GET['id'] ?? null;

if (!$id_produto) {
    echo json_encode(['success' => false, 'message' => 'ID do produto não fornecido.']);
    exit();
}

$sql = "SELECT id_produtos, nome, preco, quantidade, imagem FROM produtos WHERE id_produtos = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Erro na preparação da query: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $id_produto);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $produto = $result->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $produto]);
} else {
    echo json_encode(['success' => false, 'message' => 'Produto não encontrado.']);
}

$stmt->close();
$conn->close();
?>