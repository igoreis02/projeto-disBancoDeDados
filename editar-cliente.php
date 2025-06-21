<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}
 
// Verifica se o ID foi enviado via POST

if (isset($_POST['id'])) {
    $id = $_POST['id'];
    $telefone = $_POST['telefone'];
    $nome = $_POST['nome'];
    $dt_nascimento = $_POST['dt_nascimento'];
    $endereco = $_POST['endereco'];
    $quadra = $_POST['quadra'];
    $lote = $_POST['lote'];
    $setor = $_POST['setor'];
    $complemento = $_POST['complemento'];
    $cidade = $_POST['cidade'];
    $sexo = $_POST['sexo'];
    $termoSorteio = $_POST['termoSorteio'];

    // Validação (mantenha sua validação como está)
    $sql = "UPDATE clientes SET 
            telefone = ?,
            nome = ?,
            dt_nascimento = ?,
            endereco = ?,
            quadra = ?,
            lote = ?,
            setor = ?,
            complemento = ?,
            cidade = ?,
            sexo = ?,
            termoSorteio = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssi", $telefone, $nome, $dt_nascimento, $endereco, $quadra, $lote, $setor, $complemento, $cidade, $sexo, $termoSorteio, $id);

    // Executa a consulta
    if ($stmt->execute()) {
        echo json_encode(array("success" => true, "message" => "Cliente atualizado com sucesso."));
    } else {
        echo json_encode(array("success" => false, "message" => "Erro ao atualizar cliente: " . $stmt->error));
    }

    $stmt->close();
} else {
    // Se o ID não foi enviado, retorna um erro
    echo json_encode(array("success" => false, "message" => "ID não fornecido."));
}

$conn->close();
?>