<?php
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

// Removido o JOIN com a tabela 'sorteio' e a seleção de 'numeroSorteado'
$sql = "SELECT
id,
telefone,
nome,
dt_nascimento,
endereco,
quadra,
lote,
setor,
complemento,
cidade,
sexo,
termoSorteio
FROM clientes"; // Apenas a tabela clientes

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