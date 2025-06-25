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

$id_pedido = $_POST['id_pedido'] ?? null;
$status_novo = $_POST['status'] ?? null;
$id_entregador_post = $_POST['id_entregador'] ?? null; // Entregador enviado via POST (se aplicável)

if (!$id_pedido || !$status_novo) {
    echo json_encode(['success' => false, 'message' => 'ID do pedido ou novo status não fornecidos.']);
    exit();
}

// 1. Obter o status atual e o id_entregador atual do pedido no banco de dados para validação
$current_status = null;
$current_id_entregador = null;

$sql_get_current_info = "SELECT status_pedido, id_entregador FROM pedidos WHERE id_pedido = ?";
$stmt_get = $conn->prepare($sql_get_current_info);
if ($stmt_get) {
    $stmt_get->bind_param("i", $id_pedido);
    $stmt_get->execute();
    $stmt_get->bind_result($current_status, $current_id_entregador);
    $stmt_get->fetch();
    $stmt_get->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar a consulta de status atual: ' . $conn->error]);
    exit();
}

// Lógica de validação de transição de status
// Removendo a restrição de "Entrega" para "Aceito" para permitir a devolução
switch ($current_status) {
    case 'Pendente':
        if ($status_novo === 'Entrega' && $id_entregador_post === null) {
            echo json_encode(['success' => false, 'message' => 'Para colocar em "Entrega", um entregador deve ser selecionado.']);
            exit();
        }
        break;
    case 'Aceito':
        if ($status_novo === 'Pendente') {
            echo json_encode(['success' => false, 'message' => 'Não é possível voltar o status de "Aceito" para "Pendente".']);
            exit();
        }
        // Já verificamos se $status_novo === 'Entrega' e $id_entregador_post === null acima, essa é a validação
        // de que um entregador deve ser selecionado para ir para 'Entrega' a partir de 'Aceito'.
        if ($status_novo === 'Entrega' && $id_entregador_post === null) {
             echo json_encode(['success' => false, 'message' => 'Para colocar em "Entrega", um entregador deve ser selecionado.']);
             exit();
        }
        break;
    case 'Entrega':
        // Permitir a transição de "Entrega" para "Aceito"
        if ($status_novo === 'Pendente') {
            echo json_encode(['success' => false, 'message' => 'Não é possível voltar o status de "Entrega" para "Pendente".']);
            exit();
        }
        // Não há restrição para ir de "Entrega" para "Aceito" agora.
        break;
    case 'Concluido':
    case 'Cancelado':
        if ($status_novo !== $current_status) {
            echo json_encode(['success' => false, 'message' => 'Não é possível alterar o status de um pedido "Concluído" ou "Cancelado".']);
            exit();
        }
        break;
}

// Lógica para definir o id_entregador na atualização
$id_entregador_para_salvar = $current_id_entregador;

if ($status_novo === 'Entrega') {
    // Se o novo status é 'Entrega', use o id_entregador enviado no POST
    $id_entregador_para_salvar = $id_entregador_post;
} else if ($status_novo === 'Aceito' && $current_status === 'Entrega') {
    // Se o novo status é 'Aceito' E o status anterior era 'Entrega' (devolução),
    // o entregador deve ser removido.
    $id_entregador_para_salvar = null;
}
else if ($status_novo === 'Concluido') {
    // Se o novo status é 'Concluido', o entregador deve ser removido
    $id_entregador_para_salvar = null;
}
// Para qualquer outro status que não seja 'Entrega', 'Aceito' (vindo de Entrega) ou 'Concluido',
// o entregador deve ser nulo se nenhum foi explicitamente enviado.
// Por exemplo, de "Pendente" para "Aceito", não deve ter entregador.
else if ($id_entregador_post === null && $status_novo !== 'Entrega') {
    $id_entregador_para_salvar = null;
}


// AQUI COMEÇA A NOVA LÓGICA PARA REDUZIR O ESTOQUE
if ($status_novo === 'Concluido' && $current_status === 'Entrega') {
    // 2. Fetch order items
    $sql_get_items = "SELECT id_produto, quantidade FROM itens_pedido WHERE id_pedido = ?";
    $stmt_items = $conn->prepare($sql_get_items);
    if ($stmt_items === false) {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar a consulta de itens do pedido: ' . $conn->error]);
        exit();
    }
    $stmt_items->bind_param("i", $id_pedido);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();

    $products_to_update = [];
    while ($item = $result_items->fetch_assoc()) {
        $products_to_update[] = $item;
    }
    $stmt_items->close();

    // 3. Update product quantities
    $conn->begin_transaction(); // Inicia uma transação para garantir atomicidade

    try {
        $sql_update_product_qty = "UPDATE produtos SET quantidade = quantidade - ? WHERE id_produtos = ?";
        $stmt_update_qty = $conn->prepare($sql_update_product_qty);

        if ($stmt_update_qty === false) {
            throw new Exception('Erro ao preparar a atualização de quantidade do produto: ' . $conn->error);
        }

        foreach ($products_to_update as $product_item) {
            $quantidade_deduzir = $product_item['quantidade'];
            $id_produto = $product_item['id_produto'];

            $stmt_update_qty->bind_param("ii", $quantidade_deduzir, $id_produto);
            if (!$stmt_update_qty->execute()) {
                throw new Exception('Erro ao deduzir quantidade do produto ' . $id_produto . ': ' . $stmt_update_qty->error);
            }
        }
        $stmt_update_qty->close();

        // Se tudo ocorreu bem com a atualização de estoque, agora atualiza o status do pedido
        $sql_update_pedido = "UPDATE pedidos SET status_pedido = ?, id_entregador = ? WHERE id_pedido = ?";
        $stmt_pedido = $conn->prepare($sql_update_pedido);
        if ($stmt_pedido === false) {
            throw new Exception('Erro ao preparar a atualização do pedido: ' . $conn->error);
        }
        // Handle id_entregador_para_salvar for NULL
        if ($id_entregador_para_salvar === null) {
            $sql_update_pedido = "UPDATE pedidos SET status_pedido = ?, id_entregador = NULL WHERE id_pedido = ?";
            $stmt_pedido = $conn->prepare($sql_update_pedido);
            if ($stmt_pedido === false) {
                throw new Exception('Erro ao preparar a atualização do pedido (NULL entregador): ' . $conn->error);
            }
            $stmt_pedido->bind_param("si", $status_novo, $id_pedido);
        } else {
            $stmt_pedido->bind_param("sii", $status_novo, $id_entregador_para_salvar, $id_pedido);
        }

        if (!$stmt_pedido->execute()) {
            throw new Exception('Erro ao atualizar status do pedido: ' . $stmt_pedido->error);
        }
        $stmt_pedido->close();

        $conn->commit(); // Confirma todas as operações da transação
        echo json_encode(['success' => true, 'message' => 'Status do pedido atualizado e estoque deduzido com sucesso!']);

    } catch (Exception $e) {
        $conn->rollback(); // Reverte todas as operações em caso de erro
        error_log("Erro na transação de conclusão de pedido: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao concluir pedido e deduzir estoque: ' . $e->getMessage()]);
    }
} else {
    // Lógica para outros status (sem dedução de estoque)

    // Handle id_entregador_para_salvar for NULL
    if ($id_entregador_para_salvar === null) {
        $sql_update = "UPDATE pedidos SET status_pedido = ?, id_entregador = NULL WHERE id_pedido = ?";
        $stmt = $conn->prepare($sql_update);
        if ($stmt === false) {
            echo json_encode(['success' => false, 'message' => 'Erro ao preparar a declaração SQL (NULL entregador): ' . $conn->error]);
            $conn->close();
            exit();
        }
        $stmt->bind_param("si", $status_novo, $id_pedido);
    } else {
        $sql_update = "UPDATE pedidos SET status_pedido = ?, id_entregador = ? WHERE id_pedido = ?";
        $stmt = $conn->prepare($sql_update);
        if ($stmt === false) {
            echo json_encode(['success' => false, 'message' => 'Erro ao preparar a declaração SQL (com entregador): ' . $conn->error]);
            $conn->close();
            exit();
        }
        $stmt->bind_param("sii", $status_novo, $id_entregador_para_salvar, $id_pedido);
    }


    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Status do pedido atualizado com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status do pedido: ' . $stmt->error]);
    }

    $stmt->close();
}

$conn->close();

?>