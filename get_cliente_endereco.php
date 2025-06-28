<?php
// Define o cabeçalho para indicar que a resposta é JSON.
// É crucial que não haja NADA (nem mesmo espaços ou linhas em branco) antes desta tag PHP.
header('Content-Type: application/json');

//$servername = "myshared2380";
//$username = "cadastrosouza";
//$password = "Souza@7498"; 
//$dbname = "cadastrosouza";

$servername = "localhost"; // Use "localhost" se estiver rodando localmente ou o nome do servidor se for remoto
$username = "root";
$password = ""; // Assuming no password for the user "cadastrosouza" based on the provided data.
$dbname = "cadastrosouza";

// Conexão com o banco de dados.
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão.
if ($conn->connect_error) {
    // Em caso de erro na conexão, retorna um JSON de erro e sai.
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

// Obtém o telefone da requisição GET.
$telefone = isset($_GET['telefone']) ? $_GET['telefone'] : '';

$response = ['success' => false, 'message' => 'Telefone não fornecido.'];

if (!empty($telefone)) {
    // Prepara a query para buscar os dados do cliente.
    $sql = "SELECT nome, endereco, quadra, lote, setor, complemento, cidade FROM clientes WHERE telefone = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $telefone); // 's' indica que o parâmetro é uma string.
        $stmt->execute();
        $result = $stmt->get_result(); // Obtém o resultado da query.

        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc(); // Pega a linha como um array associativo.
            $response = ['success' => true, 'data' => $data];
        } else {
            $response = ['success' => false, 'message' => 'Cliente não encontrado.'];
        }
        $stmt->close(); // Fecha a declaração.
    } else {
        $response = ['success' => false, 'message' => 'Erro ao preparar a query: ' . $conn->error];
    }
}

$conn->close(); // Fecha a conexão com o banco de dados.
echo json_encode($response); // Retorna a resposta em formato JSON.
?>
