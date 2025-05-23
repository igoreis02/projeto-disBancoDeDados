<?php
// É ABSOLUTAMENTE CRUCIAL que não haja NADA (nem mesmo espaços, quebras de linha ou caracteres invisíveis como BOM)
// antes desta tag de abertura '<?php'. Verifique a codificação do arquivo (UTF-8 sem BOM).
header('Content-Type: application/json');

// Para depuração: Desative a exibição de erros no navegador para evitar que eles corrompam o JSON.
// Em ambiente de produção, configure isso no php.ini (display_errors = Off).
ini_set('display_errors', 0);
ini_set('log_errors', 1); // Loga os erros para um arquivo de log do PHP.
error_reporting(E_ALL);

$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "cadastro";

// Conexão com o banco de dados.
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão.
if ($conn->connect_error) {
    // Se houver erro na conexão, retorna JSON de erro e sai.
    error_log("Erro na conexão com o banco de dados: " . $conn->connect_error); // Loga o erro.
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados.']);
    exit();
}

// Verifica se a requisição é POST.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pega os dados do POST.
    $telefone = isset($_POST['telefone']) ? $_POST['telefone'] : '';
    $endereco = isset($_POST['endereco']) ? strtolower(trim($_POST['endereco'])) : '';
    $quadra = isset($_POST['quadra']) ? strtolower(trim($_POST['quadra'])) : '';
    $lote = isset($_POST['lote']) ? strtolower(trim($_POST['lote'])) : '';
    $setor = isset($_POST['setor']) ? strtolower(trim($_POST['setor'])) : '';
    $complemento = isset($_POST['complemento']) ? strtolower(trim($_POST['complemento'])) : '';
    $cidade = isset($_POST['cidade']) ? strtolower(trim($_POST['cidade'])) : '';

    // Validação básica dos campos obrigatórios.
    if (empty($telefone) || empty($endereco) || empty($quadra) || empty($lote) || empty($setor) || empty($cidade)) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos obrigatórios (Endereço, Quadra, Lote, Setor, Cidade) devem ser preenchidos.']);
        exit();
    }

    // Prepara a query SQL para atualizar o endereço do cliente.
    // Certifique-se de que os nomes das colunas estão corretos na sua tabela 'clientes'.
    $sql = "UPDATE clientes SET endereco = ?, quadra = ?, lote = ?, setor = ?, complemento = ?, cidade = ? WHERE telefone = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // 'sssssss' -> 7 strings para os 7 parâmetros na ordem correta.
        $stmt->bind_param("sssssss", $endereco, $quadra, $lote, $setor, $complemento, $cidade, $telefone);

        if ($stmt->execute()) {
            // Sucesso: retorna JSON de sucesso.
            echo json_encode(['success' => true, 'message' => 'Endereço atualizado com sucesso!']);
        } else {
            // Erro na execução da query: retorna JSON de erro com detalhes do erro MySQL.
            error_log("Erro ao executar UPDATE no clientes: " . $stmt->error); // Loga o erro para depuração no servidor.
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o endereço no banco de dados.']); // Mensagem mais genérica para o usuário.
        }
        $stmt->close(); // Fecha a declaração preparada.
    } else {
        // Erro na preparação da query: retorna JSON de erro com detalhes do erro de preparação.
        error_log("Erro ao preparar a query UPDATE no clientes: " . $conn->error); // Loga o erro para depuração no servidor.
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar a query de atualização.']); // Mensagem mais genérica para o usuário.
    }
} else {
    // Método de requisição inválido: retorna JSON de erro.
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}

$conn->close(); // Fecha a conexão com o banco de dados.
?>
