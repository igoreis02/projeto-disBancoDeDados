<?php
session_start();
header('Content-Type: application/json');

// Dados de conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro";

// Nova conexão MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Certifica-se de que o user_id está na sessão
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'Sessão de usuário não encontrada. Por favor, faça login novamente.';
        echo json_encode($response);
        $conn->close();
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $novaSenha = filter_input(INPUT_POST, 'novaSenha', FILTER_UNSAFE_RAW);
    $confirmarNovaSenha = filter_input(INPUT_POST, 'confirmarNovaSenha', FILTER_UNSAFE_RAW);

    if (empty($novaSenha) || empty($confirmarNovaSenha)) {
        $response['message'] = 'Por favor, preencha todos os campos da nova senha.';
        echo json_encode($response);
        $conn->close();
        exit();
    }

    if ($novaSenha !== $confirmarNovaSenha) {
        $response['message'] = 'As senhas não coincidem!';
        echo json_encode($response);
        $conn->close();
        exit();
    }

    if (strlen($novaSenha) < 6) {
        $response['message'] = 'A senha deve ter no mínimo 6 caracteres.';
        echo json_encode($response);
        $conn->close();
        exit();
    }

    // Gera o hash da nova senha
    $hashNovaSenha = password_hash($novaSenha, PASSWORD_DEFAULT);

    try {
        // Prepara a declaração para atualizar a senha e o status senha_alterada
        $stmt = $conn->prepare("UPDATE usuarios SET senha = ?, senha_alterada = 1 WHERE id_usuario = ?");
        
        if ($stmt === false) {
            throw new Exception('Erro ao preparar a declaração SQL: ' . $conn->error);
        }

        // 'si' - s: string (hash da nova senha), i: integer (id do usuário)
        $stmt->bind_param("si", $hashNovaSenha, $user_id);
        $stmt->execute();

        if ($stmt->error) {
            throw new Exception('Erro ao executar a declaração: ' . $stmt->error);
        }

        // Verifica se alguma linha foi afetada (se o usuário existe e a senha foi atualizada)
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Senha redefinida com sucesso!';
            // Remove a flag de redefinição de senha obrigatória da sessão
            unset($_SESSION['redefinir_senha_obrigatoria']);
        } else {
            $response['message'] = 'Falha ao redefinir a senha. Usuário não encontrado ou senha já atualizada.';
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'Erro interno do servidor ao redefinir a senha: ' . $e->getMessage();
        error_log('Erro ao redefinir senha: ' . $e->getMessage());
    } finally {
        $conn->close();
    }

} else {
    $response['message'] = 'Método de requisição inválido.';
}

echo json_encode($response);
// Nao fechar a tag PHP
