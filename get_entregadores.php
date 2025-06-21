<?php
// get_entregador.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Return a JSON error response if connection fails
    header('Content-Type: application/json');
    die(json_encode(["success" => false, "message" => "Erro na conexão com o banco de dados: " . $conn->connect_error]));
}

// Set charset to UTF-8
$conn->set_charset("utf8");
header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $sql = "SELECT id_entregador, nome, cpf, data_nascimento, telefone, cnh FROM entregadores WHERE id_entregador = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(["success" => false, "message" => "Erro na preparação da consulta: " . $conn->error]);
        $conn->close();
        exit();
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $entregador = $result->fetch_assoc();
    // Always return an object with success and data
    echo json_encode(["success" => true, "data" => $entregador]);
    $stmt->close();
} else {
    // If no specific ID is requested, fetch all deliverers
    $sql = "SELECT id_entregador, nome FROM entregadores"; // Only fetch necessary fields for the dropdown
    $result = $conn->query($sql);
    if ($result) {
        $entregadores = [];
        while ($row = $result->fetch_assoc()) {
            $entregadores[] = $row;
        }
        // Return a success object with an array of deliverers
        echo json_encode(["success" => true, "entregadores" => $entregadores]);
    } else {
        // Handle query error
        echo json_encode(["success" => false, "message" => "Erro ao buscar entregadores: " . $conn->error]);
    }
}
$conn->close();
?>