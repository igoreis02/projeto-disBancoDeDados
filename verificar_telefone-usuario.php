<?php
// conexao.php (Inclua seu arquivo de conexão)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

$telefone = $_POST['telefone'];

$sql = "SELECT id_usuario FROM usuarios WHERE telefone = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $telefone);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(array("existe" => true));
} else {
    echo json_encode(array("existe" => false));
}

$stmt->close();
$conn->close();
?>