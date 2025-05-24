<?php
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "cadastro";

// Conexão com o banco de dados.
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão.
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]));
}

// Verifica se os dados foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtém os dados do POST
    $telefone = $_POST['telefone'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $quadra = $_POST['quadra'] ?? '';
    $lote = $_POST['lote'] ?? '';
    $setor = $_POST['setor'] ?? '';
    $complemento = $_POST['complemento'] ?? '';
    $cidade = $_POST['cidade'] ?? '';

    // Validação básica (você pode adicionar mais validações)
    if (empty($telefone) || empty($endereco) || empty($quadra) || empty($lote) || empty($setor) || empty($cidade)) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos obrigatórios devem ser preenchidos.']);
        $conn->close();
        exit;
    }

    // Prepara a consulta SQL para atualizar o endereço do cliente
    $sql = "UPDATE clientes SET endereco = ?, quadra = ?, lote = ?, setor = ?, complemento = ?, cidade = ? WHERE telefone = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar a consulta: ' . $conn->error]);
        $conn->close();
        exit;
    }

    $stmt->bind_param("sssssss", $endereco, $quadra, $lote, $setor, $complemento, $cidade, $telefone);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Endereço atualizado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Nenhuma alteração foi realizada.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao executar a atualização: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}

$conn->close();
?>