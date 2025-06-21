<?php
require_once 'conexao.php'; // Include your database connection

header('Content-Type: application/json');

$entregadores = array();

// Check if a specific ID is requested for editing
if (isset($_GET['id'])) {
    $id_entregador = $conn->real_escape_string($_GET['id']);
    $sql = "SELECT id_entregador, nome, cpf, data_nascimento, telefone, cnh FROM entregadores WHERE id_entregador = '$id_entregador'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $entregadores[] = $result->fetch_assoc();
    }
} else {
    // Fetch all entregadores
    $sql = "SELECT id_entregador, nome, cpf, data_nascimento, telefone, cnh FROM entregadores";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $entregadores[] = $row;
        }
    }
}

echo json_encode($entregadores);

$conn->close();
?>