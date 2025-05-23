<?php
header('Content-Type: application/json'); // Responde sempre com JSON

$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "cadastro";

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
    $has_gas_product = isset($data['has_gas_product']) ? (int)$data['has_gas_product'] : 0; // Tem produto de gás (0 ou 1)

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

        if ($id_pedido_existente > 0) {
            // É uma atualização de pedido existente
            // Atualiza o pedido principal
            $stmt_pedido = $conn->prepare("UPDATE pedidos SET valor_total = ?, forma_pagamento = ?, valor_pago = ? WHERE id_pedido = ? AND id_cliente = ?");
            if (!$stmt_pedido) {
                throw new Exception("Erro ao preparar a atualização do pedido: " . $conn->error);
            }
            $stmt_pedido->bind_param("dsdii", $valor_total, $forma_pagamento, $valor_pago, $id_pedido, $id_cliente);
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
            $status_inicial = "Pendente";
            $stmt_pedido = $conn->prepare("INSERT INTO pedidos (id_cliente, valor_total, forma_pagamento, data_pedido, status_pedido, valor_pago) VALUES (?, ?, ?, NOW(), ?, ?)");
            if (!$stmt_pedido) {
                throw new Exception("Erro ao preparar a inserção do pedido: " . $conn->error);
            }
            $stmt_pedido->bind_param("idsds", $id_cliente, $valor_total, $forma_pagamento, $status_inicial, $valor_pago);
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

        // 4. Lógica de sorteio de número (apenas para novos pedidos e se não houver gás)
        if ($id_pedido_existente == 0 && $has_gas_product == 0) { // Se for um novo pedido E não tiver gás
            $min = 100;
            $max = 10000;
            $numero_sorteado = null;

            // Verifica se o cliente já tem um número sorteado na tabela 'sorteio'
            $stmt_check_sorteio = $conn->prepare("SELECT numeroSorteado FROM sorteio WHERE id_cliente = ?");
            if (!$stmt_check_sorteio) {
                throw new Exception("Erro ao preparar a verificação de sorteio: " . $conn->error);
            }
            $stmt_check_sorteio->bind_param("i", $id_cliente);
            $stmt_check_sorteio->execute();
            $result_check_sorteio = $stmt_check_sorteio->get_result();
            if ($result_check_sorteio->num_rows > 0) {
                $row_sorteio = $result_check_sorteio->fetch_assoc();
                $numero_sorteado = $row_sorteio['numeroSorteado'];
            }
            $stmt_check_sorteio->close();

            // Se o cliente não tem número sorteado, sorteia um
            if ($numero_sorteado === null) {
                // Lógica de sorteio de número único (função auxiliar)
                function sortearNumeroUnico($conn, $min, $max) {
                    while (true) {
                        $numero = rand($min, $max);
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

                // Salva o número sorteado na tabela 'sorteio', incluindo o id_pedido
                $stmt_insert_sorteio = $conn->prepare("INSERT INTO sorteio (id_cliente, numeroSorteado, id_pedido) VALUES (?, ?, ?)");
                if (!$stmt_insert_sorteio) {
                    throw new Exception("Erro ao preparar a inserção do sorteio: " . $conn->error);
                }
                $stmt_insert_sorteio->bind_param("iii", $id_cliente, $numero_sorteado, $id_pedido); // Adicionado id_pedido
                if (!$stmt_insert_sorteio->execute()) {
                    throw new Exception("Erro ao executar a inserção do sorteio: " . $stmt_insert_sorteio->error);
                }
                $stmt_insert_sorteio->close();
            }
            // Redireciona para a página de sorteio
            $message = 'Pedido finalizado e salvo com sucesso! Seu número da sorte é: ' . $numero_sorteado;
            $redirect_url = 'sorteio.html?nome=' . urlencode($nome_cliente) . '&numeroSorteado=' . urlencode($numero_sorteado);

        } else {
            // Se for atualização de pedido OU se tiver gás, não sorteia e redireciona para o início
            $message = 'Pedido finalizado e salvo com sucesso!';
            $redirect_url = 'index.html';
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
