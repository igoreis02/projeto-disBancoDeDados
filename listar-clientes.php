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
clientes.id, 
clientes.telefone, 
clientes.nome, 
clientes.dt_nascimento, 
clientes.endereco, 
clientes.quadra, 
clientes.lote, 
clientes.setor, 
clientes.complemento, 
clientes.cidade, 
clientes.sexo, 
clientes.termoSorteio, 
sorteio.numeroSorteado 
FROM clientes
LEFT JOIN sorteio ON clientes.id = sorteio.id_cliente";
$result = $conn->query($sql);


// Verifica se há resultados
$clientes = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
} else {

    echo json_encode(array("success" => false, "message" => "Nenhum cliente encontrado."));
    $conn->close();
    exit;
}

echo json_encode($clientes);
$conn->close();
?>