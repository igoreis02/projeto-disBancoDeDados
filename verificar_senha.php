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

if (isset($_POST['senha']) && isset($_POST['telefone'])) { // Adicionado verificação para telefone
    $senha = $_POST['senha'];
    $telefone = $_POST['telefone']; // Pega o telefone enviado

    // Prepara a consulta para verificar telefone E senha
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE telefone = ? AND senha = ?");
    $stmt->execute([$telefone, $senha]); // Passa ambos os parâmetros
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    $response = array('senhaCorreta' => ($usuario !== false));
    echo json_encode($response);
} else {
    // Se a senha ou telefone não foram fornecidos
    echo json_encode(array('senhaCorreta' => false, 'message' => 'Telefone e/ou senha não fornecidos.'));
}
?>