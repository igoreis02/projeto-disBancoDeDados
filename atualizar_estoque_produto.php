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

$id_produto = $_POST['id_produtos'] ?? null;
$quantidade_adicionar = $_POST['quantidade'] ?? null;

if (!$id_produto || !isset($quantidade_adicionar) || !is_numeric($quantidade_adicionar)) {
    echo json_encode(['success' => false, 'message' => 'ID do produto ou quantidade inválida não fornecida.']);
    exit();
}

// Atualiza a quantidade do produto no banco de dados
$sql_update = "UPDATE produtos SET quantidade = quantidade + ? WHERE id_produtos = ?";
$stmt = $conn->prepare($sql_update);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Erro na preparação da query: ' . $conn->error]);
    exit();
}

$stmt->bind_param("ii", $quantidade_adicionar, $id_produto);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Estoque do produto atualizado com sucesso.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhum produto encontrado com o ID fornecido ou quantidade inalterada.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar estoque: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>