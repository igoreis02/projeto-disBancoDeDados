<?php
header('Content-Type: application/json');

// Conexão com o banco de dados
$servername = "localhost";
$username = "root"; // Altere se o seu usuário do banco de dados for diferente
$password = "";     // Altere se sua senha do banco de dados for diferente
$dbname = "cadastro";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

// Verifica se os dados foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recebe os dados do formulário
    $nome = $_POST['nomeEntregador'] ?? '';
    $cpf = $_POST['cpfEntregador'] ?? '';
    $data_nascimento = $_POST['dataNascimentoEntregador'] ?? '';
    $telefone = $_POST['telefoneEntregador'] ?? '';
    $cnh = $_POST['cnhEntregador'] ?? '';

    // Validação básica dos dados
    if (empty($nome) || empty($cpf) || empty($data_nascimento) || empty($telefone) || empty($cnh)) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios.']);
        $conn->close();
        exit();
    }

    // Prepara a query SQL para inserção de um novo entregador
    // 'id_entregador' é geralmente AUTO_INCREMENT, então não o incluímos na inserção
    $sql = "INSERT INTO entregadores (nome, cpf, data_nascimento, telefone, cnh) VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    // Verifica se a preparação da query foi bem-sucedida
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar a declaração SQL: ' . $conn->error]);
        $conn->close();
        exit();
    }

    // Associa os parâmetros e executa a query
    // 'sssss' indica que todos os 5 parâmetros são strings
    $stmt->bind_param("sssss", $nome, $cpf, $data_nascimento, $telefone, $cnh);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Entregador cadastrado com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar entregador: ' . $stmt->error]);
    }

    // Fecha a declaração
    $stmt->close();

} else {
    // Se a requisição não for POST
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}

// Fecha a conexão com o banco de dados
$conn->close();

?>