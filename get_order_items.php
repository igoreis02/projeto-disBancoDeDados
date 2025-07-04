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

$id_pedido = isset($_GET['id_pedido']) ? (int)$_GET['id_pedido'] : 0;

$items = [];

if ($id_pedido > 0) {
    $sql = "SELECT ip.id_produto, ip.quantidade, p.nome AS nome_produto, p.preco AS preco_unitario
            FROM itens_pedido ip
            JOIN produtos p ON ip.id_produto = p.id_produtos
            WHERE ip.id_pedido = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
    } else {
        error_log("Erro ao preparar a query para obter itens do pedido: " . $conn->error);
    }
}

$conn->close();
echo json_encode($items);
?>
