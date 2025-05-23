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

// Verifica se a requisição é POST e se os dados necessários foram enviados
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_pedido']) && isset($_POST['status'])) {
    $id_pedido = $_POST['id_pedido'];
    $novo_status = $_POST['status'];

    // Prepara a query SQL para atualizar o status do pedido
    $stmt = $conn->prepare("UPDATE pedidos SET status_pedido = ? WHERE id_pedido = ?");

    if ($stmt) {
        $stmt->bind_param("si", $novo_status, $id_pedido); // 's' para string (status), 'i' para integer (id_pedido)

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao executar a atualização: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar a query: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos ou requisição não POST.']);
}

$conn->close();
?>
