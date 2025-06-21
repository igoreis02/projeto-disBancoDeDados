<?php
require_once 'conexao.php'; // Include your database connection

header('Content-Type: application/json');

$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_entregador = $conn->real_escape_string($_POST['id_entregador']);
    $nome = $conn->real_escape_string($_POST['nomeEntregador']);
    $cpf = $conn->real_escape_string($_POST['cpfEntregador']);
    $data_nascimento = $conn->real_escape_string($_POST['dataNascimentoEntregador']);
    $telefone = $conn->real_escape_string($_POST['telefoneEntregador']);
    $cnh = $conn->real_escape_string($_POST['cnhEntregador']);

    $sql = "UPDATE entregadores SET
            nome = '$nome',
            cpf = '$cpf',
            data_nascimento = '$data_nascimento',
            telefone = '$telefone',
            cnh = '$cnh'
            WHERE id_entregador = '$id_entregador'";

    if ($conn->query($sql) === TRUE) {
        $response['success'] = true;
        $response['message'] = "Entregador atualizado com sucesso!";
    } else {
        $response['message'] = "Erro ao atualizar entregador: " . $conn->error;
    }
} else {
    $response['message'] = "Método de requisição inválido.";
}

echo json_encode($response);

$conn->close();
?>