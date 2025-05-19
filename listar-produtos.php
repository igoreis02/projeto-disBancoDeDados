<?php
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "cadastro";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

// Verifica se o ID foi enviado via POST
$sql = "SELECT 
id_produtos, 
nome, 
preco, 
quantidade, 
imagem 
FROM produtos";
$result = $conn->query($sql);


// Verifica se há resultados
$produtos = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $produtos[] = $row;
    }
} else {

    echo json_encode(array("success" => false, "message" => "Nenhum produto encontrado."));
    $conn->close();
    exit;
}

echo json_encode($produtos);
$conn->close();
?>