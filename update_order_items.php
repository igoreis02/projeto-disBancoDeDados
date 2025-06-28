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

$data = json_decode(file_get_contents("php://input"), true);

$id_pedido = isset($data['id_pedido']) ? (int)$data['id_pedido'] : 0;
$product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
$quantity = isset($data['quantity']) ? (int)$data['quantity'] : 0;
$price_unit = isset($data['price_unit']) ? (float)$data['price_unit'] : 0.00;

// Iniciar transação para garantir atomicidade
$conn->begin_transaction();

try {
    // 1. Verificar se o item já existe para este pedido
    $stmt_check = $conn->prepare("SELECT id_item_pedido, quantidade FROM itens_pedido WHERE id_pedido = ? AND id_produto = ?");
    $stmt_check->bind_param("ii", $id_pedido, $product_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // Item existe, atualizar quantidade
        $row = $result_check->fetch_assoc();
        $id_item = $row['id_item_pedido'];
        $old_quantity = $row['quantidade'];

        $stmt_update = $conn->prepare("UPDATE itens_pedido SET quantidade = ?, preco_unitario = ? WHERE id_item_pedido = ?");
        $stmt_update->bind_param("idi", $quantity, $price_unit, $id_item);
        $stmt_update->execute();
        if ($stmt_update->error) {
            throw new Exception("Erro ao atualizar item do pedido: " . $stmt_update->error);
        }
        $stmt_update->close();
    } else {
        // Item não existe, inserir novo
        $stmt_insert = $conn->prepare("INSERT INTO itens_pedido (id_pedido, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("iiid", $id_pedido, $product_id, $quantity, $price_unit);
        $stmt_insert->execute();
        if ($stmt_insert->error) {
            throw new Exception("Erro ao inserir novo item do pedido: " . $stmt_insert->error);
        }
        $stmt_insert->close();
    }

    // 2. Recalcular o valor total do pedido
    $stmt_total = $conn->prepare("SELECT SUM(quantidade * preco_unitario) AS novo_total FROM itens_pedido WHERE id_pedido = ?");
    $stmt_total->bind_param("i", $id_pedido);
    $stmt_total->execute();
    $result_total = $stmt_total->get_result();
    $row_total = $result_total->fetch_assoc();
    $novo_total = $row_total['novo_total'] ?? 0.00;
    $stmt_total->close();

    $stmt_update_pedido_total = $conn->prepare("UPDATE pedidos SET valor_total = ? WHERE id_pedido = ?");
    $stmt_update_pedido_total->bind_param("di", $novo_total, $id_pedido);
    $stmt_update_pedido_total->execute();
    if ($stmt_update_pedido_total->error) {
        throw new Exception("Erro ao atualizar valor total do pedido: " . $stmt_update_pedido_total->error);
    }
    $stmt_update_pedido_total->close();

    // Se tudo ocorreu bem, commita a transação
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Produto do pedido atualizado com sucesso!', 'novo_total' => $novo_total]);

} catch (Exception $e) {
    // Se algo deu errado, faz rollback
    $conn->rollback();
    error_log("Erro em update_order_items.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar produto do pedido: ' . $e->getMessage()]);
}

$conn->close();
?>
