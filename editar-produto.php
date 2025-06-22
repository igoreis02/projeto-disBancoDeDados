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

$id_produto = $_POST['id'] ?? null;
$nome = $_POST['nome'] ?? null;
$preco = $_POST['preco'] ?? null;

if (!$id_produto || !$nome || !isset($preco) || !is_numeric($preco)) {
    echo json_encode(['success' => false, 'message' => 'Dados de edição incompletos ou inválidos.']);
    exit();
}

// Prepara a query de atualização para nome e preço
$sql_update = "UPDATE produtos SET nome = ?, preco = ? WHERE id_produtos = ?";
$stmt = $conn->prepare($sql_update);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Erro na preparação da query: ' . $conn->error]);
    exit();
}

$stmt->bind_param("sdi", $nome, $preco, $id_produto); // s: string, d: double/float, i: integer

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Produto atualizado com sucesso.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhuma alteração foi feita no produto.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar produto: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>