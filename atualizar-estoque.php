<?php
header('Content-Type: application/json');

$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "cadastro";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(array("success" => false, "message" => "Erro na conexão com o banco de dados: " . $conn->connect_error)));
}

if (isset($_POST['id_produtos']) && isset($_POST['quantidade'])) {
    $id = $_POST['id_produtos'];
    $quantidade = $_POST['quantidade'];

    $sql_select = "SELECT quantidade FROM produtos WHERE id_produtos = $id";
    $result_select = $conn->query($sql_select);

    if ($result_select->num_rows > 0) {
        $row_select = $result_select->fetch_assoc();
        $quantidade_atual = $row_select['quantidade'];

        $nova_quantidade = $quantidade_atual + $quantidade;

        $sql_update = "UPDATE produtos SET quantidade = $nova_quantidade WHERE id_produtos = $id";
        if ($conn->query($sql_update) === TRUE) {
            echo json_encode(array("success" => true, "message" => "Estoque atualizado com sucesso."));
        } else {
            echo json_encode(array("success" => false, "message" => "Erro ao atualizar o estoque: " . $conn->error));
        }
    } else {
        echo json_encode(array("success" => false, "message" => "Produto não encontrado."));
    }
} else {
    echo json_encode(array("success" => false, "message" => "Dados de entrada inválidos."));
}

$conn->close();
?>