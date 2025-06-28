<?php
session_start();
header('Content-Type: application/json');

//$servername = "myshared2380";
//$username = "cadastrosouza";
//$password = "Souza@7498"; 
//$dbname = "cadastrosouza";

$servername = "localhost"; // Use "localhost" se estiver rodando localmente ou o nome do servidor se for remoto
$username = "root";
$password = ""; // Assuming no password for the user "cadastrosouza" based on the provided data.
$dbname = "cadastrosouza";

// Nova conexão MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    // Se houver um erro na conexão, retorna um JSON de erro e encerra o script
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar e obter os dados do formulário
    // Usando FILTER_UNSAFE_RAW para campos de texto simples
    $nome = filter_input(INPUT_POST, 'nome', FILTER_UNSAFE_RAW);
    $telefone = filter_input(INPUT_POST, 'telefone', FILTER_UNSAFE_RAW);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL); 
    $tipo_usuario = filter_input(INPUT_POST, 'tipo_usuario', FILTER_UNSAFE_RAW);
    $endereco = filter_input(INPUT_POST, 'endereco', FILTER_UNSAFE_RAW);

    // Validação básica dos campos obrigatórios
    if (empty($nome) || empty($telefone) || empty($email) || empty($tipo_usuario)) {
        $response['message'] = 'Por favor, preencha todos os campos obrigatórios.';
        echo json_encode($response);
        $conn->close(); // Fechar conexão antes de sair
        exit();
    }

    // Valida o formato do email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Formato de e-mail inválido.';
        echo json_encode($response);
        $conn->close(); // Fechar conexão antes de sair
        exit();
    }

    // Senha padrão e seu hash seguro
    $senha_padrao = '12345';
    $hash_senha_padrao = password_hash($senha_padrao, PASSWORD_DEFAULT);

    // Definir status padrão do usuário e o valor para 'senha_alterada'
    $status_usuario = 'Ativo'; 
    $senha_alterada_val = 0; // Valor inteiro para tinyint(1)

    // Preparar e executar a inserção no banco de dados usando MySQLi Prepared Statements
    try {
        // Prepara a declaração SQL com os nomes corretos das colunas
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, telefone, senha, email, senha_alterada, tipo_usuario, status_usuario, endereco) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Verifica se a preparação da declaração falhou
        if ($stmt === false) {
            throw new Exception('Erro ao preparar a declaração SQL: ' . $conn->error);
        }

        // 'ssssiiss' - Tipos dos parâmetros: 
        // s: string (nome, telefone, senha, email, tipo_usuario, status_usuario, endereco)
        // i: integer (senha_alterada)
        $stmt->bind_param("ssssisss", 
            $nome, 
            $telefone, 
            $hash_senha_padrao, 
            $email, 
            $senha_alterada_val, // Passa o valor inteiro 0
            $tipo_usuario, 
            $status_usuario, 
            $endereco
        );
        
        // Executa a declaração
        $stmt->execute();

        // Verifica se houve erros na execução
        if ($stmt->error) {
            throw new Exception('Erro ao executar a declaração: ' . $stmt->error);
        }

        $response['success'] = true;
        $response['message'] = 'Usuário cadastrado com sucesso! A senha padrão é "12345".';

        $stmt->close(); // Fecha a declaração preparada
    } catch (Exception $e) {
        // Captura erros e define a mensagem de resposta
        // Verifica se o erro é de e-mail já em uso (ex: duplicata de chave única)
        if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'for key \'email\'') !== false) { 
            $response['message'] = 'Erro: Este e-mail já está em uso.';
        } else {
            $response['message'] = 'Erro ao cadastrar usuário: ' . $e->getMessage();
        }
        // Log do erro para depuração (muito importante em ambiente de desenvolvimento)
        error_log('Erro ao cadastrar usuário: ' . $e->getMessage());
    } finally {
        // Garante que a conexão com o banco de dados seja fechada
        if ($conn->ping()) { // Verifica se a conexão ainda está ativa antes de fechar
            $conn->close();
        }
    }

} else {
    $response['message'] = 'Método de requisição inválido.';
}

echo json_encode($response);
// É crucial NÃO ter a tag de fechamento ?>
