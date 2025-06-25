<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro";

// nova conexão mysqli
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]));
}

header('Content-Type: application/json'); // Define o cabeçalho para JSON

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