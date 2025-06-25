<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, preencha todos os campos.']);
        $conn->close();
        exit();
    }

    // Adiciona 'senha_alterada' na seleção
    $stmt = $conn->prepare("SELECT id_usuario, senha, senha_alterada FROM usuarios WHERE email = ?");
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Erro na preparação da consulta: ' . $conn->error]);
        $conn->close();
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $stored_password_hash = $user['senha'];
        $senha_ja_alterada = $user['senha_alterada']; // Novo campo

        if (password_verify($senha, $stored_password_hash)) {
            session_start();
            $_SESSION['user_id'] = $user['id_usuario'];
            $_SESSION['user_email'] = $email;

            // ***** LÓGICA ATUALIZADA AQUI *****
            // Verifica se a senha usada é a senha padrão '12345' E se ela ainda não foi alterada
            // Se o usuário digitou '12345' E a flag 'senha_alterada' é 0
            if ($senha === '12345' && $senha_ja_alterada == 0) {
                 $_SESSION['redefinir_senha_obrigatoria'] = true;
            } else {
                 $_SESSION['redefinir_senha_obrigatoria'] = false;
            }
            // **********************************

            echo json_encode(['success' => true, 'message' => 'Login bem-sucedido!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'E-mail ou senha inválidos.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'E-mail ou senha inválidos.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}

$conn->close();
?>