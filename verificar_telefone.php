<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

if (isset($_POST['telefone'])) {
    $telefone = $_POST['telefone'];
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE telefone = ?");
    $stmt->execute([$telefone]); // Execute usando array
    $existe = $stmt->fetch(PDO::FETCH_ASSOC);

    $response = array('existe' => ($existe !== false));
    echo json_encode($response);

}
?>