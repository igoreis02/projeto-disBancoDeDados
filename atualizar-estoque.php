<?php
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

$id_pedido = $_POST['id_pedido'] ?? null;
$status_novo = $_POST['status'] ?? null;
$id_entregador = $_POST['id_entregador'] ?? null; // Obtém o id_entregador

if (!$id_pedido || !$status_novo) {
    echo json_encode(['success' => false, 'message' => 'ID do pedido ou novo status não fornecidos.']);
    exit();
}

// Prepara a query de atualização
$sql_update = "UPDATE pedidos SET status_pedido = ?";
$types = "s"; // Tipo para o status

// Se o status for 'Entrega' e um entregador foi fornecido, adicione a coluna id_entregador
if ($status_novo === 'Entrega' && $id_entregador !== null) {
    $sql_update .= ", id_entregador = ?";
    $types .= "i"; // Tipo para o id_entregador (inteiro)
} else {
    // Se o status NÃO for 'Entrega' ou nenhum entregador foi fornecido, garanta que id_entregador seja NULL
    // Isso evita que um entregador fique associado se o pedido não estiver em 'Entrega'
    $sql_update .= ", id_entregador = NULL";
}

$sql_update .= " WHERE id_pedido = ?";
$types .= "i"; // Tipo para o id_pedido

$stmt = $conn->prepare($sql_update);

// Bind dos parâmetros dinamicamente
if ($status_novo === 'Entrega' && $id_entregador !== null) {
    $stmt->bind_param($types, $status_novo, $id_entregador, $id_pedido);
} else {
    // Se não há id_entregador para bind (ou é NULL), apenas status e id_pedido
    $stmt->bind_param($types, $status_novo, $id_pedido);
}


if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Status do pedido atualizado com sucesso.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>