<?php
// Adicione estas linhas para depuração (REMOVA EM PRODUÇÃO!)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

header('Content-Type: application/json');

// Consulta SQL para obter pedidos em entrega com dados do cliente e produtos
$sql = "
SELECT
    p.id_pedido,
    c.telefone AS telefone_cliente, -- Pegamos o telefone da tabela clientes
    p.data_pedido,
    p.status_pedido, -- Coluna atualizada para 'status_pedido'
    p.forma_pagamento,
    p.valor_total,
    c.nome AS nome_cliente,
    c.endereco AS endereco_cliente,
    c.quadra AS quadra_cliente,
    c.lote AS lote_cliente,
    c.setor AS setor_cliente,
    c.complemento AS complemento_cliente,
    c.cidade AS cidade_cliente,
    c.latitude AS latitude_cliente,
    c.longitude AS longitude_cliente,
    GROUP_CONCAT(
        JSON_OBJECT(
            'id_produto', pr.id_produtos,
            'nome_produto', pr.nome,
            'quantidade', ip.quantidade,
            'preco_unitario', ip.preco_unitario
        )
        ORDER BY pr.nome ASC
        SEPARATOR '|||'
    ) AS produtos_json
FROM
    pedidos p
JOIN
    clientes c ON p.id_cliente = c.id -- JOIN agora usa id_cliente = id
LEFT JOIN
    itens_pedido ip ON p.id_pedido = ip.id_pedido
LEFT JOIN
    produtos pr ON ip.id_produto = pr.id_produtos
WHERE
    p.status_pedido = 'Entrega' -- Filtra apenas pedidos com status 'Em Entrega', usando 'status_pedido'
GROUP BY
    p.id_pedido, c.telefone, p.data_pedido, p.status_pedido, p.forma_pagamento, p.valor_total,
    c.nome, c.endereco, c.quadra, c.lote, c.setor, c.complemento, c.cidade, c.latitude, c.longitude
ORDER BY
    p.data_pedido ASC;
";

$result = $conn->query($sql);

$pedidos = [];
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $produtos = [];
            if (!empty($row['produtos_json'])) {
                $produtos_data = explode('|||', $row['produtos_json']);
                foreach ($produtos_data as $produto_item) {
                    $produtos[] = json_decode($produto_item, true);
                }
            }
            $row['produtos'] = $produtos; // Adiciona o array de produtos ao objeto do pedido
            unset($row['produtos_json']); // Remove a string JSON bruta

            $pedidos[] = $row;
        }
        echo json_encode(['success' => true, 'pedidos' => $pedidos]);
    } else {
        echo json_encode(['success' => true, 'pedidos' => [], 'message' => 'Nenhum pedido em entrega encontrado.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Erro na consulta SQL: ' . $conn->error]);
}

$conn->close();
?>