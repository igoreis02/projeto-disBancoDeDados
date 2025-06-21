<?php
// fetch_pedidos.php

// Define o fuso horário para garantir a data correta
date_default_timezone_set('America/Sao_Paulo');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro";

// Conexão com o banco de dados.
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão.
if ($conn->connect_error) {
    // Em caso de erro, retorna um JSON com erro.
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

// Obtém a data a ser filtrada (padrão para o dia atual se não for fornecida)
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');

// Obtém o status a ser filtrado (padrão para 'PendenteAceito' se não for fornecido)
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : 'PendenteAceito';

// Prepara a cláusula WHERE para o filtro de data
$where_clause = "WHERE DATE(p.data_pedido) = ? ";
$params = [$filter_date];
$types = "s";

// Adiciona filtro de status
if ($filter_status === 'PendenteAceito') {
    $where_clause .= "AND p.status_pedido IN ('Pendente', 'Aceito') ";
} elseif ($filter_status !== 'Todos') {
    $where_clause .= "AND p.status_pedido = ? ";
    $params[] = $filter_status;
    $types .= "s";
}

// Query para buscar todos os pedidos com detalhes do cliente e dos produtos.
$sql = "
    SELECT
        p.id_pedido,
        p.status_pedido,
        c.nome AS cliente_nome,
        c.telefone AS cliente_telefone,
        c.endereco,
        c.quadra,
        c.lote,
        c.setor,
        c.complemento,
        c.cidade,
        p.valor_total,
        p.forma_pagamento,
        p.data_pedido,
        p.valor_pago,
        GROUP_CONCAT(CONCAT(ip.quantidade, 'x ', prod.nome, ' (R$ ', FORMAT(ip.preco_unitario, 2, 'pt_BR'), ')') SEPARATOR '<br>') AS produtos_detalhes,
        p.id_cliente,
        e.nome AS entregador_nome
    FROM
        pedidos p
    JOIN
        clientes c ON p.id_cliente = c.id
    LEFT JOIN
        itens_pedido ip ON p.id_pedido = ip.id_pedido
    LEFT JOIN
        produtos prod ON ip.id_produto = prod.id_produtos
    LEFT JOIN
        entregadores e ON p.id_entregador = e.id_entregador
    {$where_clause}
    GROUP BY
        p.id_pedido
    ORDER BY
        p.data_pedido DESC;
";

$stmt = $conn->prepare($sql);

if ($types === "s") {
    $stmt->bind_param("s", $params[0]);
} else {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$pedidos = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Formata a data e hora para exibição
        $data_hora = new DateTime($row['data_pedido']);
        $row['data_pedido_display'] = $data_hora->format('d/m/Y H:i');

        // Formata o endereço completo conforme a nova lógica
        $endereco_parts_display = [];
        if (!empty($row['endereco'])) $endereco_parts_display[] = ucwords($row['endereco']);
        if (!empty($row['quadra'])) $endereco_parts_display[] = 'Qd. ' . ucwords($row['quadra']);
        if (!empty($row['lote'])) $endereco_parts_display[] = 'Lt. ' . ucwords($row['lote']);

        $formatted_address = implode(', ', $endereco_parts_display);

        if (!empty($row['setor'])) {
            $formatted_address .= '<br>Setor: ' . ucwords($row['setor']);
        }
        if (!empty($row['complemento'])) {
            $formatted_address .= '<br>Compl: ' . ucwords($row['complemento']);
        }
        if (!empty($row['cidade'])) {
            $formatted_address .= '<br>' . ucwords($row['cidade']);
        }
        $row['endereco_completo'] = $formatted_address;

        // Formata a forma de pagamento
        switch ($row['forma_pagamento']) {
            case 'dinheiro':
                $troco = $row['valor_pago'] - $row['valor_total'];
                $row['forma_pagamento_display'] = 'Dinheiro (Troco: R$ ' . number_format($troco, 2, ',', '.') . ')';
                break;
            case 'cartao_credito':
                $row['forma_pagamento_display'] = 'Cartão de Crédito';
                break;
            case 'cartao_debito':
                $row['forma_pagamento_display'] = 'Cartão de Débito';
                break;
            case 'pix':
                $row['forma_pagamento_display'] = 'PIX';
                break;
            default:
                $row['forma_pagamento_display'] = ucwords(str_replace('_', ' ', $row['forma_pagamento']));
                break;
        }

        // Formata o valor total
        $row['valor_total_display'] = 'R$ ' . number_format($row['valor_total'], 2, ',', '.');


        $pedidos[] = $row;
    }
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode(['success' => true, 'pedidos' => $pedidos]);
?>