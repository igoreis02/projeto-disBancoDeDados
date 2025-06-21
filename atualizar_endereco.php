<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $telefone = $_POST['telefone'];
    $nome = $_POST['nome'];
    $endereco = $_POST['endereco'];
    $quadra = $_POST['quadra'];
    $lote = $_POST['lote'];
    $setor = $_POST['setor'];
    $complemento = $_POST['complemento'];
    $cidade = $_POST['cidade'];

    $sql = "UPDATE clientes SET 
            nome = ?, 
            endereco = ?, 
            quadra = ?, 
            lote = ?, 
            setor = ?, 
            complemento = ?, 
            cidade = ? 
            WHERE telefone = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $nome, $endereco, $quadra, $lote, $setor, $complemento, $cidade, $telefone);

    if ($stmt->execute()) {
        echo "Endereço atualizado com sucesso!";
        header("Location: confirmar_endereco.php?telefone=$telefone&nome=$nome&endereco=$endereco&quadra=$quadra&lote=$lote&setor=$setor&complemento=$complemento&cidade=$cidade"); // Redirect back to confirmar_endereco.php
        exit();
    } else {
        echo "Erro ao atualizar o endereço: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>