<?php
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "cadastro";

// Conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

// Obtém o telefone da URL
$telefone = isset($_GET['telefone']) ? $_GET['telefone'] : '';

// Inicializa as variáveis do cliente
$nome = "";
$endereco = "";
$quadra = "";
$lote = "";
$setor = "";
$complemento = "";
$cidade = "";

// Se o telefone estiver presente, busca os dados do cliente no banco de dados
if (!empty($telefone)) {
    $sql = "SELECT nome, endereco, quadra, lote, setor, complemento, cidade FROM clientes WHERE telefone = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $telefone);
        $stmt->execute();
        $stmt->bind_result($nome, $endereco, $quadra, $lote, $setor, $complemento, $cidade);
        $stmt->fetch();
        $stmt->close();
    } else {
        // Em caso de erro na preparação da query
        error_log("Erro ao preparar a query para buscar cliente em confirmar_endereco.php: " . $conn->error);
    }
}

$conn->close();

// Inclui o HTML para exibir os dados
include 'confirmar_endereco.html';
?>
