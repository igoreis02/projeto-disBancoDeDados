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
    // Recebe os dados do formulário do entregador
    $nomeEntregador = $_POST['nomeEntregador'] ?? '';
    $cpf = $_POST['cpfEntregador'] ?? '';
    $data_nascimento = $_POST['dataNascimentoEntregador'] ?? '';
    $telefone = $_POST['telefoneEntregador'] ?? '';
    $cnh = $_POST['cnhEntregador'] ?? '';

    // Validação básica dos dados do entregador
    if (empty($nomeEntregador) || empty($cpf) || empty($data_nascimento) || empty($telefone) || empty($cnh)) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos do entregador são obrigatórios.']);
        $conn->close();
        exit();
    }

    // --- Lógica para o cadastro do USUÁRIO (automatico para o entregador) ---

    // Gera o email automaticamente: primeiro nome do entregador @souza.com.br
    $primeiroNome = explode(' ', trim($nomeEntregador))[0]; // Pega o primeiro nome
    $emailUsuario = strtolower(str_replace(' ', '', $primeiroNome)) . '@souza.com.br';

    // Regra da senha padrão
    $senha_padrao = '12345';
    $hash_senha_padrao = password_hash($senha_padrao, PASSWORD_DEFAULT);

    // Tipo de usuário fixo como 'entregador'
    $tipo_usuario = 'entregador';

    // Status do usuário fixo como 'Ativo'
    $status_usuario = 'Ativo';

    // Senha_alterada como 0 (para forçar redefinição no primeiro login)
    $senha_alterada = 0;

    // Endereço não é necessário, será NULL
    $endereco = NULL;

    // Inicia uma transação para garantir que ambas as inserções (usuario e entregador) ocorram com sucesso
    // ou nenhuma delas
    $conn->begin_transaction();

    try {
        // 1. Inserir na tabela 'usuarios'
        $sql_usuario = "INSERT INTO usuarios (nome, telefone, senha, email, senha_alterada, tipo_usuario, status_usuario, endereco) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_usuario = $conn->prepare($sql_usuario);

        if ($stmt_usuario === false) {
            throw new Exception('Erro ao preparar a declaração SQL para usuário: ' . $conn->error);
        }

        // 'ssssiiss' para (nome, telefone, senha, email, senha_alterada, tipo_usuario, status_usuario, endereco)
        $stmt_usuario->bind_param("ssssisss", $nomeEntregador, $telefone, $hash_senha_padrao, $emailUsuario, $senha_alterada, $tipo_usuario, $status_usuario, $endereco);
        
        $stmt_usuario->execute();

        if ($stmt_usuario->error) {
             // Se o erro for de e-mail duplicado, pode ser tratado especificamente
            if ($stmt_usuario->errno == 1062) { // Código de erro para entrada duplicada
                throw new Exception('Erro: O e-mail gerado para este entregador já está em uso (' . $emailUsuario . '). Por favor, tente um nome diferente ou ajuste o e-mail manualmente.');
            }
            throw new Exception('Erro ao cadastrar usuário: ' . $stmt_usuario->error);
        }

        $id_usuario_gerado = $conn->insert_id; // Obtém o ID do usuário recém-inserido

        $stmt_usuario->close();

        // 2. Inserir na tabela 'entregadores' (usando o id_usuario gerado)
        $sql_entregador = "INSERT INTO entregadores (nome, cpf, data_nascimento, telefone, cnh, id_usuario) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_entregador = $conn->prepare($sql_entregador);

        if ($stmt_entregador === false) {
            throw new Exception('Erro ao preparar a declaração SQL para entregador: ' . $conn->error);
        }

        // 'sssssi' para (nome, cpf, data_nascimento, telefone, cnh, id_usuario)
        $stmt_entregador->bind_param("sssssi", $nomeEntregador, $cpf, $data_nascimento, $telefone, $cnh, $id_usuario_gerado);
        
        $stmt_entregador->execute();

        if ($stmt_entregador->error) {
            throw new Exception('Erro ao cadastrar entregador: ' . $stmt_entregador->error);
        }

        $stmt_entregador->close();

        // Se tudo correu bem, comita a transação
        $conn->commit();

        $response['success'] = true;
        $response['message'] = 'Entregador e Usuário cadastrados com sucesso! O e-mail do usuário é: ' . $emailUsuario . ' e a senha padrão é "12345".';

    } catch (Exception $e) {
        // Se algo deu errado, reverte a transação
        $conn->rollback();
        $response['message'] = $e->getMessage();
        error_log('Erro na transação de cadastro de entregador/usuário: ' . $e->getMessage());
    } finally {
        $conn->close();
    }

} else {
    // Se a requisição não for POST
    $response['message'] = 'Método de requisição inválido.';
    $conn->close();
}

echo json_encode($response);

// A tag de fechamento ?>
