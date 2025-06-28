<?php
// Database connection details
//$servername = "myshared2380";
//$username = "cadastrosouza";
//$password = "Souza@7498"; 
//$dbname = "cadastrosouza";

$servername = "localhost"; // Use "localhost" se estiver rodando localmente ou o nome do servidor se for remoto
$username = "root";
$password = ""; // Assuming no password for the user "cadastrosouza" based on the provided data.
$dbname = "cadastrosouza";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telefone = $_POST['telefone'] ?? '';

    if (empty($telefone)) {
        echo json_encode(['success' => false, 'message' => 'Telefone não fornecido para exclusão.']);
        $conn->close();
        exit();
    }

    // Prepare a DELETE statement
    // IMPORTANT: Only delete if 'endereco' and other address fields are empty or NULL,
    // to ensure you're only deleting "incomplete" registrations.
    $sql = "DELETE FROM clientes WHERE telefone = ? AND endereco = '' AND quadra = '' AND lote = '' AND setor = '' AND complemento = '' AND cidade = ''";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar a declaração SQL para exclusão: ' . $conn->error]);
        $conn->close();
        exit();
    }

    $stmt->bind_param("s", $telefone);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Dados pessoais excluídos com sucesso.']);
        } else {
            // This might happen if the record was already completed (had address data) or didn't exist
            echo json_encode(['success' => false, 'message' => 'Nenhum dado pessoal encontrado ou excluído (possivelmente já completo).']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir dados pessoais: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}

$conn->close();
?>