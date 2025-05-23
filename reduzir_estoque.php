<?php
header('Content-Type: application/json');

$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "cadastro";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

// Verifica se a requisição é POST e se os dados foram enviados
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['produtos'])) {
    $produtos_para_reduzir = json_decode($_POST['produtos'], true);

    if (!is_array($produtos_para_reduzir) || empty($produtos_para_reduzir)) {
        echo json_encode(['success' => false, 'message' => 'Dados de produtos inválidos.']);
        exit();
    }

    $conn->begin_transaction(); // Inicia a transação

    try {
        foreach ($produtos_para_reduzir as $produto) {
            $id_produto = $produto['id'];
            $quantidade_reduzir = $produto['quantidade'];

            // 1. Obter a quantidade atual do produto
            $stmt_select = $conn->prepare("SELECT quantidade FROM produtos WHERE id_produtos = ? FOR UPDATE"); // FOR UPDATE para bloquear a linha
            if (!$stmt_select) {
                throw new Exception("Erro ao preparar a seleção de estoque: " . $conn->error);
            }
            $stmt_select->bind_param("i", $id_produto);
            $stmt_select->execute();
            $result_select = $stmt_select->get_result();

            if ($result_select->num_rows === 0) {
                throw new Exception("Produto com ID " . $id_produto . " não encontrado.");
            }
            $row = $result_select->fetch_assoc();
            $quantidade_atual = $row['quantidade'];
            $stmt_select->close();

            // 2. Verificar se há estoque suficiente
            if ($quantidade_atual < $quantidade_reduzir) {
                throw new Exception("Estoque insuficiente para o produto ID " . $id_produto . ". Quantidade atual: " . $quantidade_atual . ", Tentativa de reduzir: " . $quantidade_reduzir);
            }

            // 3. Reduzir a quantidade no estoque
            $nova_quantidade = $quantidade_atual - $quantidade_reduzir;
            $stmt_update = $conn->prepare("UPDATE produtos SET quantidade = ? WHERE id_produtos = ?");
            if (!$stmt_update) {
                throw new Exception("Erro ao preparar a atualização de estoque: " . $conn->error);
            }
            $stmt_update->bind_param("ii", $nova_quantidade, $id_produto);
            if (!$stmt_update->execute()) {
                throw new Exception("Erro ao reduzir o estoque do produto ID " . $id_produto . ": " . $stmt_update->error);
            }
            $stmt_update->close();
        }

        $conn->commit(); // Confirma a transação
        echo json_encode(['success' => true, 'message' => 'Estoque reduzido com sucesso!']);

    } catch (Exception $e) {
        $conn->rollback(); // Reverte a transação em caso de erro
        error_log("Erro ao reduzir estoque: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao reduzir estoque: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos ou requisição não POST.']);
}

$conn->close();
?>
