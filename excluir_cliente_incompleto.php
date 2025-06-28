<?php
// excluir_cliente_incompleto.php
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

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

// Recebe o ID do cliente via POST
$id_cliente = $_POST['id_cliente'] ?? null;

if ($id_cliente === null) {
    echo json_encode(['success' => false, 'message' => 'ID do cliente não fornecido.']);
    $conn->close();
    exit();
}

// Inicia uma transação para garantir que a exclusão seja atômica
$conn->begin_transaction();

try {
    // Primeiro, exclua os itens de pedido associados a este cliente (se houver, para evitar FK issues)
    // Se você tiver outras tabelas que referenciam 'clientes.id', como 'pedidos',
    // precisará considerar a exclusão em cascata ou a exclusão manual aqui também.
    // Por simplicidade, vamos focar na tabela 'clientes' e 'itens_pedido' se o cliente tiver pedidos.
    // No entanto, para um cliente *incompleto* que está sendo excluído, é improvável que ele já tenha pedidos.
    // Apenas para garantir, se houver tabelas de relacionamento N:M, elas também precisariam ser limpas.

    // Excluir o cliente da tabela 'clientes'
    $sql = "DELETE FROM clientes WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception('Erro ao preparar a declaração SQL para exclusão: ' . $conn->error);
    }

    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();

    if ($stmt->error) {
        throw new Exception('Erro ao excluir cliente: ' . $stmt->error);
    }

    if ($stmt->affected_rows > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Cliente incompleto excluído com sucesso.']);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Cliente não encontrado ou já excluído.']);
    }

    $stmt->close();

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir cliente: ' . $e->getMessage()]);
    error_log('Erro em excluir_cliente_incompleto.php: ' . $e->getMessage());
} finally {
    $conn->close();
}
?>