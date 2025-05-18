<?php
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "cadastro";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$telefone = $_POST['telefone'];

$sql = "SELECT nome, endereco, quadra, lote, setor, complemento, cidade FROM clientes WHERE telefone = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $telefone);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($nome, $endereco, $quadra, $lote, $setor, $complemento, $cidade);
    $stmt->fetch();
    echo json_encode(['existe' => true, 'nome' => $nome, 'endereco' => $endereco, 'quadra' => $quadra, 'lote' => $lote, 'setor' => $setor, 'complemento' => $complemento, 'cidade' => $cidade]);
} else {
    echo json_encode(['existe' => false]);
}

$stmt->close();
$conn->close();
?>