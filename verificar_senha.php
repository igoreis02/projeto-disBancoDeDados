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

if (isset($_POST['telefone']) && isset($_POST['senha'])) {
 $telefone = $_POST['telefone'];
 $senha = $_POST['senha'];

 $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE telefone = ? AND senha = ?"); // Supondo que sua tabela de usuários tenha uma coluna 'senha'
 $stmt->execute([$telefone, $senha]);
 $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

 $response = array('senhaCorreta' => ($usuario !== false));
 echo json_encode($response);
}
?>