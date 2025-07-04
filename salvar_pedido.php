<?php
header('Content-Type: application/json'); // Responde sempre com JSON

//$servername = "myshared2380";
//$username = "cadastrosouza";
//$password = "Souza@7498"; 
//$dbname = "cadastrosouza";

$servername = "localhost"; // Use "localhost" se estiver rodando localmente ou o nome do servidor se for remoto
$username = "root";
$password = ""; // Assuming no password for the user "cadastrosouza" based on the provided data.
$dbname = "cadastrosouza";
// Conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

// Verifica se a requisição é POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pega os dados do corpo da requisição JSON
    $data = json_decode(file_get_contents("php://input"), true);

    $telefone_cliente = isset($data['telefone']) ? $data['telefone'] : '';
    $valor_total = isset($data['total']) ? (float)$data['total'] : 0.00;
    $forma_pagamento = isset($data['forma_pagamento']) ? $data['forma_pagamento'] : '';
    $produtos_selecionados = isset($data['produtos']) ? $data['produtos'] : [];
    $valor_pago = isset($data['valor_pago']) ? (float)$data['valor_pago'] : null;
    $id_pedido_existente = isset($data['id_pedido_existente']) ? (int)$data['id_pedido_existente'] : 0; // ID do pedido existente (0 se for novo)
    $has_gas_product = isset($data['has_gas_product']) ? (bool)$data['has_gas_product'] : false; // Tem produto de gás (true ou false)

    // 1. Obter o id_cliente baseado no telefone
    $id_cliente = null;
    $nome_cliente = ''; // Inicializa o nome do cliente
    $stmt_cliente = $conn->prepare("SELECT id, nome FROM clientes WHERE telefone = ?");
    if (!$stmt_cliente) {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar a busca do cliente: ' . $conn->error]);
        exit();
    }
    $stmt_cliente->bind_param("s", $telefone_cliente);
    $stmt_cliente->execute();
    $result_cliente = $stmt_cliente->get_result();
    if ($result_cliente->num_rows > 0) {
        $row_cliente = $result_cliente->fetch_assoc();
        $id_cliente = $row_cliente['id'];
        $nome_cliente = $row_cliente['nome'];
    }
    $stmt_cliente->close();

    if ($id_cliente === null) {
        echo json_encode(['success' => false, 'message' => 'Cliente não encontrado. Por favor, cadastre-se primeiro.']);
        exit();
    }

    // Iniciar transação para garantir atomicidade
    $conn->begin_transaction();

    try {
        $id_pedido = $id_pedido_existente; // Assume que é uma atualização se id_pedido_existente for > 0
        $status_inicial = "Pendente";

        if ($id_pedido_existente > 0) {
            // É uma atualização de pedido existente
            // Atualiza o pedido principal
            $stmt_pedido = $conn->prepare("UPDATE pedidos SET valor_total = ?, forma_pagamento = ?, valor_pago = ?, status_pedido = ? WHERE id_pedido = ? AND id_cliente = ?");
            if (!$stmt_pedido) {
                throw new Exception("Erro ao preparar a atualização do pedido: " . $conn->error);
            }
           $stmt_pedido->bind_param("dsdsii", $valor_total, $forma_pagamento, $valor_pago, $status_inicial, $id_pedido, $id_cliente); // 's' para status_pedido
            if (!$stmt_pedido->execute()) {
                throw new Exception("Erro ao executar a atualização do pedido: " . $stmt_pedido->error);
            }
            $stmt_pedido->close();

            // Remove os itens antigos do pedido para inserir os novos
            $stmt_delete_itens = $conn->prepare("DELETE FROM itens_pedido WHERE id_pedido = ?");
            if (!$stmt_delete_itens) {
                throw new Exception("Erro ao preparar a exclusão de itens antigos: " . $conn->error);
            }
            $stmt_delete_itens->bind_param("i", $id_pedido);
            if (!$stmt_delete_itens->execute()) {
                throw new Exception("Erro ao executar a exclusão de itens antigos: " . $stmt_delete_itens->error);
            }
            $stmt_delete_itens->close();

        } else {
            // É um novo pedido
            // 2. Inserir o pedido na tabela 'pedidos'
            
            $stmt_pedido = $conn->prepare("INSERT INTO pedidos (id_cliente, valor_total, forma_pagamento, status_pedido, valor_pago) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt_pedido) {
                throw new Exception("Erro ao preparar a inserção do pedido: " . $conn->error);
            }
            $stmt_pedido->bind_param("idssd", $id_cliente, $valor_total, $forma_pagamento, $status_inicial, $valor_pago); // 's' para status_pedido
            if (!$stmt_pedido->execute()) {
                throw new Exception("Erro ao executar a inserção do pedido: " . $stmt_pedido->error);
            }
            
            $id_pedido = $stmt_pedido->insert_id; // Obtém o ID do novo pedido
            $stmt_pedido->close();

        }

        // 3. Inserir/Atualizar os itens do pedido na tabela 'itens_pedido'
        $stmt_item = $conn->prepare("INSERT INTO itens_pedido (id_pedido, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
        if (!$stmt_item) {
            throw new Exception("Erro ao preparar a inserção do item do pedido: " . $conn->error);
        }

        foreach ($produtos_selecionados as $produto) {
            $id_produto = $produto['id'];
            $quantidade = $produto['quantidade'];
            $preco_unitario = $produto['preco'];

            $stmt_item->bind_param("iiid", $id_pedido, $id_produto, $quantidade, $preco_unitario);
            if (!$stmt_item->execute()) {
                throw new Exception("Erro ao executar a inserção do item: " . $stmt_item->error);
            }
        }
        $stmt_item->close();

        // 4. Lógica de sorteio de número: Sorteia um NOVO número para CADA pedido com gás
        $numero_sorteado = null;
        $message = 'Pedido finalizado e salvo com sucesso!';
        $redirect_url = 'confirmacao_sem_sorteio.html?nome=' . urlencode(explode(' ', $nome_cliente)[0]);

        if ($has_gas_product && $id_pedido_existente == 0) { // Se o pedido contém um produto de gás
            $min = 100;
            $max = 10000;
            
            // Função auxiliar para sortear um número único
            function sortearNumeroUnico($conn, $min, $max) {
                while (true) {
                    $numero = rand($min, $max);
                    // Verifica se o número já existe na tabela 'sorteio'
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM sorteio WHERE numeroSorteado = ?");
                    $stmt->bind_param("i", $numero);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_row();
                    $count = $row[0];
                    $stmt->close();

                    if ($count == 0) {
                        return $numero;
                    }
                }
            }
            
            $numero_sorteado = sortearNumeroUnico($conn, $min, $max);

            // Salva o NOVO número sorteado na tabela 'sorteio', incluindo o id_pedido
            // Note que aqui SEMPRE INSERIMOS um novo registro se o pedido tiver gás
            $stmt_insert_sorteio = $conn->prepare("INSERT INTO sorteio (id_cliente, numeroSorteado, id_pedido) VALUES (?, ?, ?)");
            if (!$stmt_insert_sorteio) {
                throw new Exception("Erro ao preparar a inserção do sorteio: " . $conn->error);
            }
            $stmt_insert_sorteio->bind_param("iii", $id_cliente, $numero_sorteado, $id_pedido);
            if (!$stmt_insert_sorteio->execute()) {
                throw new Exception("Erro ao executar a inserção do sorteio: " . $stmt_insert_sorteio->error);
            }
            $stmt_insert_sorteio->close();
            
            $message = 'Pedido finalizado e salvo com sucesso! Seu número da sorte é: ' . $numero_sorteado;
            $redirect_url = 'sorteio.html?nome=' . urlencode($nome_cliente) . '&numeroSorteado=' . urlencode($numero_sorteado);
        }

        // Se tudo ocorreu bem, commita a transação
        $conn->commit();
        echo json_encode(['success' => true, 'message' => $message, 'redirect_url' => $redirect_url]);

    } catch (Exception $e) {
        // Se algo deu errado, faz rollback
        $conn->rollback();
        error_log("Erro ao finalizar pedido: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao finalizar o pedido: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}

$conn->close();
?>