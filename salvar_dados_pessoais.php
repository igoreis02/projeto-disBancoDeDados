<?php
// Adicione estas duas linhas para depuração (REMOVA EM PRODUÇÃO!)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro";

// nova conexão mysqli
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]));
}

header('Content-Type: application/json'); // Define o cabeçalho para JSON

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupera os dados do formulário
    $telefone = strtolower($_POST['telefone']);
    $nome = strtolower(trim($_POST['nome']));
    $dt_nascimento = isset($_POST['dt_nascimento']) ? date('Y-m-d', strtotime($_POST['dt_nascimento'])) : null;
    $sexo = strtolower(trim($_POST['sexo']));
    $termoSorteio = isset($_POST['termoSorteio']) ? 1 : 0;

    // Validação básica (pode ser mais robusta)
    $erros = array();
    if (empty($telefone)) {
        $erros[] = "Telefone é obrigatório."; // Adicionei validação para telefone
    }
    if (empty($nome)) {
        $erros[] = "Nome é obrigatório.";
    }
    if (empty($dt_nascimento)) {
        $erros[] = "Data de nascimento é obrigatória.";
    } else {
        $data_atual = new DateTime();
        $data_nasc_obj = new DateTime($dt_nascimento);
        if ($data_nasc_obj > $data_atual) {
            $erros[] = "Data de nascimento inválida (não pode ser no futuro).";
        }
    }
    if (empty($sexo)) {
        $erros[] = "Sexo é obrigatório.";
    }
    if ($termoSorteio == 0) {
        $erros[] = "Você deve aceitar os termos do sorteio.";
    }

    if (!empty($erros)) {
        echo json_encode(['success' => false, 'message' => implode("<br>", $erros)]);
        $conn->close();
        exit;
    }

    // AQUI: Verifique se o telefone já existe ANTES de tentar inserir
    $sql_check_telefone = "SELECT COUNT(*) FROM clientes WHERE telefone = ?";
    $stmt_check = $conn->prepare($sql_check_telefone);
    $stmt_check->bind_param("s", $telefone);
    $stmt_check->execute();
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($count > 0) {
        // Se o telefone já existe, retorne sucesso, pois os dados pessoais já estariam cadastrados
        // E o cliente pode prosseguir para atualizar o endereço
        echo json_encode(['success' => true, 'telefone_cliente' => $telefone, 'message' => 'Cliente já possui cadastro pessoal. Prossiga para o endereço.']);
        $conn->close();
        exit;
    }

    // Insere os dados pessoais na tabela 'clientes'
    // Endereço, quadra, lote, setor, complemento, cidade, latitude, longitude serão nulos inicialmente
    $sql_cliente = "INSERT INTO clientes (telefone, nome, dt_nascimento, sexo, termoSorteio, endereco, quadra, lote, setor, complemento, cidade, latitude, longitude) VALUES (?, ?, ?, ?, ?, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL)";
    $stmt_cliente = $conn->prepare($sql_cliente);

    if ($stmt_cliente === false) {
        echo json_encode(['success' => false, 'message' => 'Erro na preparação da consulta: ' . $conn->error]);
        $conn->close();
        exit;
    }

    $stmt_cliente->bind_param("ssssi", $telefone, $nome, $dt_nascimento, $sexo, $termoSorteio);

    if ($stmt_cliente->execute()) {
        // Retorna o telefone como identificador, em vez de um ID autoincrementado
        echo json_encode(['success' => true, 'telefone_cliente' => $telefone]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar dados pessoais: ' . $stmt_cliente->error]);
    }

    $stmt_cliente->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
}

$conn->close();
?>