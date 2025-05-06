<?php
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "cadastro";

$min = 100;
$max = 10000;

// nova conexão mysqli
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupera os dados do formulário
    $telefone = strtolower($_POST['telefone']);
    $nome = strtolower(trim($_POST['nome']));
    $dt_nascimento = isset($_POST['dt_nascimento']) ? date('Y-m-d', strtotime($_POST['dt_nascimento'])) : null;
    $endereco = strtolower(trim($_POST['endereco']));
    $quadra = strtolower(trim($_POST['quadra']));
    $lote = strtolower(trim($_POST['lote']));
    $setor = strtolower(trim($_POST['setor']));
    $complemento = strtolower(trim($_POST['complemento']));
    $cidade = strtolower(trim($_POST['cidade']));
    $sexo = strtolower(trim($_POST['sexo']));
    $termoSorteio = isset($_POST['termoSorteio']) ? 1 : 0;

    // Validação (mantenha sua validação como está)
    $erros = array();
    if (empty($nome)) {
        $erros[] = "Nome é obrigatório.";
    }
    if (empty($dt_nascimento)) {
        $erros[] = "Data de nascimento é obrigatória.";
    }
    if (empty($endereco)) {
        $erros[] = "Endereço é obrigatório.";
    }
    if (empty($quadra)) {
        $erros[] = "Quadra é obrigatória.";
    }
    if (empty($lote)) {
        $erros[] = "Lote é obrigatória.";
    }
    if (empty($setor)) {
        $erros[] = "Setor é obrigatório.";
    }
    if (empty($cidade)) {
        $erros[] = "Cidade é obrigatória.";
    }
    if (empty($sexo)) {
        $erros[] = "Sexo é obrigatório.";
    }
    if ($termoSorteio == 0) {
        $erros[] = "Você deve aceitar os termos do sorteio.";
    }

    if (!empty($erros)) {
        $mensagem_erro = "<div style='font-family: sans-serif; background-color: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px auto; max-width: 500px; text-align: center;'>";
        $mensagem_erro .= "<b>Por favor, corrija os seguintes erros:</b><br>";
        $mensagem_erro .= "<ul>";
        foreach ($erros as $erro) {
            $mensagem_erro .= "<li>$erro</li>";
        }
        $mensagem_erro .= "</ul>";
        $mensagem_erro .= "</div>";
        echo $mensagem_erro;
        echo "<p style='font-family: sans-serif; text-align: center;'><a href='cadastro.html?telefone=" . $telefone . "'>Voltar ao formulário de cadastro</a></p>";
        exit;
    }

    // Sorteia um número único
    $numeroUnico = sortearNumeroUnico($conn, $min, $max);

    // Insere os dados do cliente
    $stmt_cliente = $conn->prepare("INSERT INTO clientes (telefone, nome, dt_nascimento, endereco, quadra, lote, setor, complemento, cidade, sexo, termoSorteio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_cliente->bind_param("sssssssssss", $telefone, $nome, $dt_nascimento, $endereco, $quadra, $lote, $setor, $complemento, $cidade, $sexo, $termoSorteio);

    if ($stmt_cliente->execute()) {
        $stmt_sorteio = $conn->prepare("INSERT INTO sorteio (numeroSorteado, id_cliente) VALUES (?, ?)");   // Prepara a inserção no sorteio
        $cliente_id = $conn->insert_id;     // Obtém o ID do cliente inserido
        $stmt_sorteio->bind_param("ii", $numeroUnico, $cliente_id);     // Usa o ID do cliente inserido

        if ($stmt_sorteio->execute()) {
            // Redireciona para sorteado.html com os dados
            header("Location: sorteio.html?nome=" . urlencode($nome) . "&numeroSorteado=" . $numeroUnico);
            exit; // sai após o redirecionamento
        } else {
            // Se houver erro ao inserir no sorteio, exclui o cadastro do cliente (rollback)
            $conn->query("DELETE FROM clientes WHERE id = $cliente_id");
            echo "<div style='font-family: sans-serif; background-color: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px auto; max-width: 500px; text-align: center;'>Erro ao cadastrar número para sorteio: " . $stmt_sorteio->error . " Cadastro do cliente desfeito.</div>";
            echo "<p style='font-family: sans-serif; text-align: center;'><a href='cadastro.html?telefone=" . $telefone . "'>Voltar ao formulário de cadastro</a></p>";
            $stmt_sorteio->close();
        }
    } else {
        echo "<div style='font-family: sans-serif; background-color: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px auto; max-width: 500px; text-align: center;'>Erro ao cadastrar cliente: " . $stmt_cliente->error . "</div>";
        echo "<p style='font-family: sans-serif; text-align: center;'><a href='cadastro.html?telefone=" . $telefone . "'>Voltar ao formulário de cadastro</a></p>";
    }

    // Fecha as declarações
    $stmt_cliente->close();
}

function sortearNumeroUnico($conn, $min, $max) {
    // Gera um número aleatório entre $min e $max
    // Verifica se o número já foi sorteado
    // Se já foi, gera outro número até encontrar um que não tenha sido sorteado
    // Retorna o número sorteado
    while (true) {
        $numero = rand($min, $max);
        $stmt = $conn->prepare("SELECT COUNT(*) FROM sorteio WHERE numeroSorteado = ?");
        $stmt->bind_param("i", $numero);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count == 0) {
            return $numero;
        }
    }
}

// Fecha a conexão
$conn->close();
?>
