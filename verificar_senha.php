<?php
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "cadastro";

try {
 $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
 $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
 die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

if (isset($_POST['senha'])) {

 $senha = $_POST['senha'];

 $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE senha = ?"); // Supondo que sua tabela de usuários tenha uma coluna 'senha'
 $stmt->execute([$senha]);
 $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

 $response = array('senhaCorreta' => ($usuario !== false));
 echo json_encode($response);
}
?>