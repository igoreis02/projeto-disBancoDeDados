<?php
session_start();
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
    $userId = $_POST['userId'] ?? null;
    $novaSenha = $_POST['novaSenha'] ?? '';

    // Validação básica
    if (empty($userId) || empty($novaSenha)) {
        echo json_encode(['success' => false, 'message' => 'ID do usuário ou nova senha não fornecidos.']);
        $conn->close();
        exit();
    }

    // Hash da nova senha
    $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

    // Inicia uma transação para garantir que ambas as atualizações ocorram ou nenhuma ocorra
    $conn->begin_transaction();

    try {
        // 1. Atualiza a senha no banco de dados
        $stmt_senha = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id_usuario = ?");
        if ($stmt_senha === false) {
            throw new Exception('Erro na preparação da consulta de senha: ' . $conn->error);
        }
        $stmt_senha->bind_param("si", $novaSenhaHash, $userId);
        if (!$stmt_senha->execute()) {
            throw new Exception('Erro ao salvar a nova senha: ' . $stmt_senha->error);
        }
        $stmt_senha->close();

        // 2. Atualiza a flag senha_alterada para 1
        $stmt_flag = $conn->prepare("UPDATE usuarios SET senha_alterada = 1 WHERE id_usuario = ?");
        if ($stmt_flag === false) {
            throw new Exception('Erro na preparação da consulta de flag: ' . $conn->error);
        }
        $stmt_flag->bind_param("i", $userId);
        if (!$stmt_flag->execute()) {
            throw new Exception('Erro ao atualizar a flag senha_alterada: ' . $stmt_flag->error);
        }
        $stmt_flag->close();

        $conn->commit(); // Confirma ambas as operações

        // Senha e flag atualizadas com sucesso. Remover a flag de redefinição obrigatória da sessão.
        if (isset($_SESSION['redefinir_senha_obrigatoria'])) {
            unset($_SESSION['redefinir_senha_obrigatoria']);
        }
        echo json_encode(['success' => true, 'message' => 'Senha atualizada com sucesso!']);

    } catch (Exception $e) {
        $conn->rollback(); // Reverte se houver qualquer erro
        echo json_encode(['success' => false, 'message' => 'Erro ao redefinir a senha: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}

$conn->close();
?>