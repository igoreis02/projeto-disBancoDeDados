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

// Função para sortear um número único
function sortearNumeroUnico($conn, $min, $max) {
    while (true) {
        $numero = rand($min, $max);
        $stmt = $conn->prepare("SELECT COUNT(*) FROM sorteio WHERE numeroSorteado = ?");
        if (!$stmt) {
            error_log("Erro ao preparar query para sortearNumeroUnico: " . $conn->error);
            return false; // Retorna falso em caso de erro na preparação
        }
        $stmt->bind_param("i", $numero);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count == 0) {
            return $numero;
        }
    }
}

// Intervalo para os números do sorteio
$min_sorteio = 100;
$max_sorteio = 10000;

// Verifica se a requisição é POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pega os dados do corpo da requisição JSON
    $data = json_decode(file_get_contents("php://input"), true);

    $telefone_cliente = isset($data['telefone']) ? $data['telefone'] : '';
    $valor_total = isset($data['total']) ? (float)$data['total'] : 0.00;
    $forma_pagamento = isset($data['forma_pagamento']) ? $data['forma_pagamento'] : '';
    $produtos_selecionados = isset($data['produtos']) ? $data['produtos'] : [];
    $valor_pago = isset($data['valor_pago']) ? (float)$data['valor_pago'] : null;

    // 1. Obter o id_cliente e o nome do cliente baseado no telefone
    $id_cliente = null;
    $nome_cliente = "Cliente"; // Default name
    // Ajuste a coluna da sua tabela clientes para a chave primária correta (id ou id_clientes)
    $stmt_cliente_info = $conn->prepare("SELECT id, nome FROM clientes WHERE telefone = ?");
    if ($stmt_cliente_info) {
        $stmt_cliente_info->bind_param("s", $telefone_cliente);
        $stmt_cliente_info->execute();
        $stmt_cliente_info->bind_result($id_cliente, $db_nome);
        $stmt_cliente_info->fetch();
        $stmt_cliente_info->close();
        if ($id_cliente) {
            // Pegar apenas o primeiro nome e capitalizá-lo
            $primeiro_nome = explode(' ', $db_nome)[0];
            $nome_cliente = ucwords($primeiro_nome);
        }
    }

    if (is_null($id_cliente)) {
        echo json_encode(['success' => false, 'message' => 'Cliente não encontrado com o telefone fornecido.']);
        $conn->close();
        exit();
    }

    // Iniciar uma transação para garantir a integridade dos dados
    $conn->begin_transaction();

    try {
        // 2. Inserir o pedido na tabela 'pedidos'
        // Adicione 'valor_pago' na sua query INSERT e no bind_param
        $stmt_pedido = $conn->prepare("INSERT INTO pedidos (id_cliente, valor_total, forma_pagamento, valor_pago) VALUES (?, ?, ?, ?)");
        if (!$stmt_pedido) {
            throw new Exception("Erro ao preparar a inserção do pedido: " . $conn->error);
        }
        $stmt_pedido->bind_param("idsd", $id_cliente, $valor_total, $forma_pagamento, $valor_pago);
        if (!$stmt_pedido->execute()) {
            throw new Exception("Erro ao executar a inserção do pedido: " . $stmt_pedido->error);
        }
        $id_pedido = $stmt_pedido->insert_id; // Obtém o ID do pedido recém-inserido
        $stmt_pedido->close();

        // Variável para verificar se o "gás" foi incluído no pedido
        $contem_gas = false;

        // 3. Inserir os itens do pedido na tabela 'itens_pedido'
        $stmt_item = $conn->prepare("INSERT INTO itens_pedido (id_pedido, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
        if (!$stmt_item) {
            throw new Exception("Erro ao preparar a inserção do item do pedido: " . $conn->error);
        }

        foreach ($produtos_selecionados as $produto) {
            $id_produto = $produto['id'];
            $quantidade = $produto['quantidade'];
            $preco_unitario = $produto['preco'];
            $nome_produto_lower = strtolower($produto['nome']); // Converta para minúsculas para comparação

            // Verifica se o produto é "gás" (ou "gás P5", "gás P13", etc.)
            if (strpos($nome_produto_lower, 'gás') !== false || strpos($nome_produto_lower, 'gas') !== false) {
                $contem_gas = true;
            }

            $stmt_item->bind_param("iiid", $id_pedido, $id_produto, $quantidade, $preco_unitario);
            if (!$stmt_item->execute()) {
                throw new Exception("Erro ao executar a inserção do item: " . $stmt_item->error);
            }
        }
        $stmt_item->close();

        $numero_sorteado = null;
        // 4. Se o pedido contiver "gás", realizar o sorteio
        if ($contem_gas) {
            $numero_sorteado = sortearNumeroUnico($conn, $min_sorteio, $max_sorteio);
            if ($numero_sorteado === false) { // Erro na função de sorteio
                 throw new Exception("Não foi possível gerar um número para o sorteio.");
            }

            $stmt_sorteio = $conn->prepare("INSERT INTO sorteio (numeroSorteado, id_cliente, id_pedido) VALUES (?, ?, ?)");
            if (!$stmt_sorteio) {
                throw new Exception("Erro ao preparar a inserção no sorteio: " . $conn->error);
            }
            $stmt_sorteio->bind_param("iii", $numero_sorteado, $id_cliente, $id_pedido);
            if (!$stmt_sorteio->execute()) {
                throw new Exception("Erro ao inserir número no sorteio: " . $stmt_sorteio->error);
            }
            $stmt_sorteio->close();
        }

        // Se tudo ocorreu bem, commita a transação
        $conn->commit();

        $response_message = 'Pedido finalizado e salvo com sucesso!';
        $redirect_url = 'index.html'; // Default redirect URL

        if ($contem_gas && $numero_sorteado !== null) {
            $response_message .= ' Você foi incluído no sorteio!';
            // A URL para a página de sorteio com o primeiro nome do cliente e o número sorteado
            $redirect_url = "sorteio.html?nome=" . urlencode($nome_cliente) . "&numeroSorteado=" . $numero_sorteado;
        }

        echo json_encode([
            'success' => true,
            'message' => $response_message,
            'id_pedido' => $id_pedido,
            'numero_sorteado' => $numero_sorteado, // Retorna o número sorteado
            'redirect_url' => $redirect_url // Informa ao JS para onde redirecionar
        ]);

    } catch (Exception $e) {
        // Se algo deu errado, faz rollback
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Erro ao finalizar o pedido: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}

$conn->close();
?>