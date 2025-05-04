<?php
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "cadastro";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

$sql = "SELECT telefone, nome, dt_nascimento, endereco, quadra, lote, setor, complemento, cidade, sexo, termoSorteio FROM clientes";
$result = $conn->query($sql);

$clientes = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
}

echo json_encode($clientes);
$conn->close();
?>