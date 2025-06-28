<?php
// deletar_entregador.php
header('Content-Type: application/json');

//$servername = "myshared2380";
//$username = "cadastrosouza";
//$password = "Souza@7498"; 
//$dbname = "cadastrosouza";

$servername = "localhost"; // Use "localhost" se estiver rodando localmente ou o nome do servidor se for remoto
$username = "root";
$password = ""; // Assuming no password for the user "cadastrosouza" based on the provided data.
$dbname = "cadastrosouza";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_entregador'])) {
    $id_entregador = (int)$_POST['id_entregador'];

    // Inicia uma transação para garantir que ambas as exclusões ocorram ou nenhuma delas
    $conn->begin_transaction();

    try {
        // 1. Obter o id_usuario vinculado ao entregador antes de excluí-lo
        $sql_select_user_id = "SELECT id_usuario FROM entregadores WHERE id_entregador = ?";
        $stmt_select = $conn->prepare($sql_select_user_id);
        
        if ($stmt_select === false) {
            throw new Exception('Erro ao preparar seleção de id_usuario: ' . $conn->error);
        }

        $stmt_select->bind_param("i", $id_entregador);
        $stmt_select->execute();
        $stmt_select->bind_result($id_usuario);
        $stmt_select->fetch();
        $stmt_select->close();

        // Verifica se o entregador existe e se há um id_usuario vinculado
        if (!$id_usuario) {
            throw new Exception("Entregador não encontrado ou sem usuário vinculado.");
        }

        // 2. Excluir o entregador da tabela 'entregadores'
        $sql_delete_entregador = "DELETE FROM entregadores WHERE id_entregador = ?";
        $stmt_entregador = $conn->prepare($sql_delete_entregador);
        
        if ($stmt_entregador === false) {
            throw new Exception('Erro ao preparar exclusão de entregador: ' . $conn->error);
        }

        $stmt_entregador->bind_param("i", $id_entregador);
        $stmt_entregador->execute();

        if ($stmt_entregador->error) {
            throw new Exception('Erro ao excluir entregador: ' . $stmt_entregador->error);
        }

        if ($stmt_entregador->affected_rows === 0) {
            throw new Exception("Entregador não encontrado ou já excluído.");
        }
        $stmt_entregador->close();

        // 3. Excluir o usuário correspondente da tabela 'usuarios'
        $sql_delete_usuario = "DELETE FROM usuarios WHERE id_usuario = ?";
        $stmt_usuario = $conn->prepare($sql_delete_usuario);
        
        if ($stmt_usuario === false) {
            throw new Exception('Erro ao preparar exclusão de usuário: ' . $conn->error);
        }

        $stmt_usuario->bind_param("i", $id_usuario);
        $stmt_usuario->execute();

        if ($stmt_usuario->error) {
            throw new Exception('Erro ao excluir usuário: ' . $stmt_usuario->error);
        }

        if ($stmt_usuario->affected_rows === 0) {
            // Este caso pode acontecer se o usuário já foi excluído manualmente,
            // mas o entregador ainda estava vinculado. É um warning, não um erro fatal.
            error_log("Aviso: Usuário vinculado ao entregador (ID: {$id_entregador}) não encontrado na tabela de usuários para exclusão (ID do Usuário: {$id_usuario}).");
        }
        $stmt_usuario->close();

        // Se ambas as exclusões foram bem-sucedidas, comita a transação
        $conn->commit();
        $response['success'] = true;
        $response['message'] = "Entregador e usuário vinculados excluídos com sucesso!";

    } catch (Exception $e) {
        // Se algo deu errado, reverte a transação
        $conn->rollback();
        $response['message'] = "Erro ao excluir: " . $e->getMessage();
        error_log('Erro na transação de exclusão de entregador/usuário: ' . $e->getMessage());
    } finally {
        $conn->close();
    }

} else {
    $response['message'] = "Requisição inválida.";
    $conn->close();
}

echo json_encode($response);
// A tag de fechamento ?>
