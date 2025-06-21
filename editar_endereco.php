<?php
$telefone = $_GET['telefone'];

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

$sql = "SELECT nome, endereco, quadra, lote, setor, complemento, cidade FROM clientes WHERE telefone = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $telefone);
$stmt->execute();
$stmt->bind_result($nome, $endereco, $quadra, $lote, $setor, $complemento, $cidade);
$stmt->fetch();
$stmt->close();
$conn->close();

include 'editar_endereco.html';
?>

