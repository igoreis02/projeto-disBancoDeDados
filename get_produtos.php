<?php
header('Content-Type: application/json'); // Importante: informa que o conteúdo é JSON

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

$sql = "SELECT id_produtos, nome, preco, imagem FROM produtos";
$resultado = $conn->query($sql);

$produtos = array();
if ($resultado->num_rows > 0) {
    while($row = $resultado->fetch_assoc()) {
        $produtos[] = $row;
    }
}

echo json_encode($produtos); // Retorna os dados como JSON

$conn->close();
?>