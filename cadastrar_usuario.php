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
    // Formate a data corretamente
    $dt_nascimento = isset($_POST['dt_nascimento']) ? date('Y-m-d', strtotime($_POST['dt_nascimento'])) : null;
    $endereco = $_POST['endereco'];
    $quadra = $_POST['quadra'];
    $lote = $_POST['lote'];
    $setor = $_POST['setor'];
    $complemento = $_POST['complemento'];
    $cidade = $_POST['cidade'];
    $sexo = $_POST['sexo'];
    // Verifique se o termo foi aceito (checkbox)
    $termoSorteio = isset($_POST['termoSorteio']) ? 1 : 0; // Ou 'Sim'/'Não', dependendo do seu banco

    $stmt = $conn->prepare("INSERT INTO clientes (telefone, nome, dt_nascimento, endereco, quadra, lote, setor, complemento, cidade, sexo, termoSorteio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssss", $telefone, $nome, $dt_nascimento, $endereco, $quadra, $lote, $setor, $complemento, $cidade, $sexo, $termoSorteio);

    if ($stmt->execute()) {
        echo "<div style='font-family: sans-serif; background-color: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 20px auto; max-width: 500px; text-align: center;'>Cadastro realizado com sucesso!</div>";
        echo "<p style='font-family: sans-serif; text-align: center;'><a href='index.html'>Voltar para a página inicial</a></p>";
    } else {
        echo "<div style='font-family: sans-serif; background-color: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px auto; max-width: 500px; text-align: center;'>Erro ao cadastrar: " . $stmt->error . "</div>";
        echo "<p style='font-family: sans-serif; text-align: center;'><a href='cadastro.html?telefone=" . $telefone . "'>Voltar ao formulário de cadastro</a></p>";
    }

    $stmt->close();
}

$conn->close();
?>