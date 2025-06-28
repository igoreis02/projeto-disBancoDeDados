<?php
// É crucial que não haja NADA (nem mesmo espaços ou linhas em branco) antes desta tag PHP.
// Isso garante que o cabeçalho Content-Type seja enviado corretamente antes de qualquer outra saída.
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
    // Se houver erro na conexão, retorna JSON de erro e sai.
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

// Verifica se a requisição é POST.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pega os dados do POST.
    $telefone = isset($_POST['telefone']) ? $_POST['telefone'] : '';
    // O nome é readonly no modal, então não precisa ser atualizado, mas vamos pegá-lo se for enviado.
    $nome = isset($_POST['nome']) ? strtolower(trim($_POST['nome'])) : '';
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
    // Removido 'nome = ?' da query UPDATE, pois o nome é readonly no modal e não deve ser alterado aqui.
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
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o endereço no banco de dados: ' . $stmt->error]);
        }
        $stmt->close(); // Fecha a declaração preparada.
    } else {
        // Erro na preparação da query: retorna JSON de erro com detalhes do erro de preparação.
        error_log("Erro ao preparar a query UPDATE no clientes: " . $conn->error); // Loga o erro para depuração no servidor.
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar a query de atualização: ' . $conn->error]);
    }
} else {
    // Método de requisição inválido: retorna JSON de erro.
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}

$conn->close(); // Fecha a conexão com o banco de dados.
?>
<!--
    O código acima é um script PHP que atualiza o endereço de um cliente no banco de dados.
    Ele verifica se a requisição é do tipo POST, valida os dados recebidos e executa a atualização.
    Caso haja algum erro, ele retorna uma mensagem de erro em formato JSON.
    Certifique-se de que os nomes das colunas e da tabela estão corretos no seu banco de dados.