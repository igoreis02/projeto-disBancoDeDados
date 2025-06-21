<?php
// deletar_entregador.php
require_once 'conexao.php';
header('Content-Type: application/json');

$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_entregador'])) {
    $id = (int)$_POST['id_entregador'];

    $sql = "DELETE FROM entregadores WHERE id_entregador = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = "Entregador excluído com sucesso!";
        } else {
            $response['message'] = "Entregador não encontrado.";
        }
    } else {
        $response['message'] = "Erro ao excluir entregador: " . $stmt->error;
    }
    $stmt->close();
} else {
    $response['message'] = "Requisição inválida.";
}

echo json_encode($response);
$conn->close();
?>