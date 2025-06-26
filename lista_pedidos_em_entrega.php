<?php
// Adicione estas linhas para depuração (REMOVA EM PRODUÇÃO!)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inicia ou resume a sessão para acessar o ID do usuário logado
session_start();

// Verifica se o usuário está logado e se é um entregador.
// Se não estiver logado ou não for um entregador, você pode decidir redirecionar ou retornar um erro.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'entregador') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas entregadores logados podem visualizar esta página.']);
    exit();
}

// O ID do usuário logado da sessão é o id_entregador a ser usado na consulta de pedidos
$id_entregador_logado = $_SESSION['user_id']; 

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
// Adiciona a condição para filtrar pelo id_entregador
$sql = "
SELECT
    p.id_pedido,
    c.telefone AS telefone_cliente,
    p.data_pedido,
    p.status_pedido,
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
    clientes c ON p.id_cliente = c.id
LEFT JOIN
    itens_pedido ip ON p.id_pedido = ip.id_pedido
LEFT JOIN
    produtos pr ON ip.id_produto = pr.id_produtos
WHERE
    p.status_pedido = 'Entrega' AND p.id_entregador = ? -- Filtra pelo id_entregador logado
GROUP BY
    p.id_pedido, c.telefone, p.data_pedido, p.status_pedido, p.forma_pagamento, p.valor_total,
    c.nome, c.endereco, c.quadra, c.lote, c.setor, c.complemento, c.cidade, c.latitude, c.longitude
ORDER BY
    p.data_pedido ASC;
";

// Prepara a consulta para evitar injeção de SQL e para vincular o parâmetro id_entregador
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar a consulta SQL: ' . $conn->error]);
    exit();
}

// Vincula o id_entregador logado como um parâmetro inteiro
$stmt->bind_param("i", $id_entregador_logado);
$stmt->execute();
$result = $stmt->get_result();

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
            $row['produtos'] = $produtos;
            unset($row['produtos_json']);

            $pedidos[] = $row;
        }
        echo json_encode(['success' => true, 'pedidos' => $pedidos]);
    } else {
        echo json_encode(['success' => true, 'pedidos' => [], 'message' => 'Nenhum pedido em entrega encontrado para este entregador.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Erro na execução da consulta SQL: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>