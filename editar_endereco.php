<?php
$telefone = $_GET['telefone'];

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

