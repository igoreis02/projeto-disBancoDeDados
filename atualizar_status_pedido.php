<?php
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "cadastro";

// Conexão com o banco de dados.
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão.
if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pedido = isset($_POST['id_pedido']) ? intval($_POST['id_pedido']) : 0;
    $novo_status = isset($_POST['status']) ? $_POST['status'] : '';

    if ($id_pedido > 0 && !empty($novo_status)) {
        // Primeiro, obtenha o status atual do pedido para evitar deduções duplicadas
        $sql_current_status = "SELECT status_pedido FROM pedidos WHERE id_pedido = ?";
        $stmt_current_status = $conn->prepare($sql_current_status);
        $stmt_current_status->bind_param("i", $id_pedido);
        $stmt_current_status->execute();
        $result_current_status = $stmt_current_status->get_result();
        $current_status_row = $result_current_status->fetch_assoc();
        $current_status = $current_status_row['status_pedido'];
        $stmt_current_status->close();

        // Atualiza o status do pedido
        $sql_update_pedido = "UPDATE pedidos SET status_pedido = ? WHERE id_pedido = ?";
        $stmt_update_pedido = $conn->prepare($sql_update_pedido);
        $stmt_update_pedido->bind_param("si", $novo_status, $id_pedido);

        if ($stmt_update_pedido->execute()) {
            $response['success'] = true;
            $response['message'] = 'Status do pedido atualizado com sucesso.';

            // Lógica para reduzir o estoque APENAS se o novo status for 'Concluido'
            // e o status anterior NÃO ERA 'Concluido' (para evitar deduções duplicadas)
            if ($novo_status === 'Concluido' && $current_status !== 'Concluido') {
                // Obter os produtos e suas quantidades do pedido
                $sql_itens_pedido = "SELECT id_produto, quantidade FROM itens_pedido WHERE id_pedido = ?";
                $stmt_itens_pedido = $conn->prepare($sql_itens_pedido);
                $stmt_itens_pedido->bind_param("i", $id_pedido);
                $stmt_itens_pedido->execute();
                $result_itens_pedido = $stmt_itens_pedido->get_result();

                if ($result_itens_pedido->num_rows > 0) {
                    while ($item = $result_itens_pedido->fetch_assoc()) {
                        $id_produto = $item['id_produto'];
                        $quantidade_vendida = $item['quantidade'];

                        // Atualizar a quantidade em estoque do produto
                        // Certifique-se de que o nome da coluna de estoque na tabela `produtos` está correto (ex: `quantidade_estoque`)
                        $sql_update_estoque = "UPDATE produtos SET quantidade = quantidade - ? WHERE id_produtos = ?";
                        $stmt_update_estoque = $conn->prepare($sql_update_estoque);
                        $stmt_update_estoque->bind_param("ii", $quantidade_vendida, $id_produto);

                        if (!$stmt_update_estoque->execute()) {
                            $response['success'] = false;
                            $response['message'] = 'Status atualizado, mas erro ao reduzir estoque do produto ' . $id_produto . ': ' . $stmt_update_estoque->error;
                            // Você pode querer logar esse erro ou reverter o status do pedido, dependendo da sua regra de negócio
                            break; // Pare de processar se houver um erro no estoque
                        }
                        $stmt_update_estoque->close();
                    }
                }
                $stmt_itens_pedido->close();
            }
        } else {
            $response['message'] = 'Erro ao atualizar o status do pedido: ' . $stmt_update_pedido->error;
        }
        $stmt_update_pedido->close();
    } else {
        $response['message'] = 'Dados inválidos para atualização.';
    }
} else {
    $response['message'] = 'Método de requisição inválido.';
}

$conn->close();
echo json_encode($response);
?>