<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Your existing database connection and query code starts here
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
    // This die() will likely be what you're seeing if connection fails.
    // It outputs HTML by default.
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

$id = $_POST['id_produtos']; // Make sure this is correct based on your JS
$id = $conn->real_escape_string($id); // Good practice for sanitization

$sql = "DELETE FROM produtos WHERE id_produtos = $id";

if ($conn->query($sql) === TRUE) {
    echo json_encode(array("success" => true, "message" => "Produto excluído com sucesso."));
} else {
    echo json_encode(array("success" => false, "message" => "Erro ao excluir produto: " . $conn->error));
}

$conn->close();
?>