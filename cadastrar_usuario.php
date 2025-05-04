<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro";

$min = 100;
$max = 10000;

// Cria uma nova conexão mysqli
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica se a conexão foi estabelecida com sucesso
if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

// Verifica se o método da requisição é POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupera os dados do formulário
    $telefone = $_POST['telefone'];
    $nome = $_POST['nome'];
    // Formata a data corretamente
    $dt_nascimento = isset($_POST['dt_nascimento']) ? date('Y-m-d', strtotime($_POST['dt_nascimento'])) : null;
    $endereco = $_POST['endereco'];
    $quadra = $_POST['quadra'];
    $lote = $_POST['lote'];
    $setor = $_POST['setor'];
    $complemento = $_POST['complemento'];
    $cidade = $_POST['cidade'];
    $sexo = $_POST['sexo'];
    // Verifica se o termo foi aceito (checkbox)
    $termoSorteio = isset($_POST['termoSorteio']) ? 1 : 0; // 1 para aceito, 0 para não aceito

    // Sorteia um número único
    $numeroUnico = sortearNumeroUnico($conn, $min, $max);

    // Prepara a query para inserir os dados do cliente
    $stmt_cliente = $conn->prepare("INSERT INTO clientes (telefone, nome, dt_nascimento, endereco, quadra, lote, setor, complemento, cidade, sexo, termoSorteio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    // Vincula os parâmetros com os tipos de dados corretos
    $stmt_cliente->bind_param("sssssssssss", $telefone, $nome, $dt_nascimento, $endereco, $quadra, $lote, $setor, $complemento, $cidade, $sexo, $termoSorteio);

    // Executa a inserção dos dados do cliente
    if ($stmt_cliente->execute()) {
        // Prepara a query para inserir o número sorteado na tabela de sorteio
        $stmt_sorteio = $conn->prepare("INSERT INTO sorteio (numeroSorteado) VALUES (?)");
        // Vincula o parâmetro com o tipo de dado correto
        $stmt_sorteio->bind_param("i", $numeroUnico);
        // Executa a inserção do número sorteado
        if ($stmt_sorteio->execute()) {
            echo "<div style='font-family: sans-serif; background-color: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 20px auto; max-width: 500px; text-align: center;'>Cadastro realizado com sucesso! Número para sorteio: " . $numeroUnico . "</div>";
            echo "<p style='font-family: sans-serif; text-align: center;'><a href='index.html'>Voltar para a página inicial</a></p>";
        } else {
            // Se houver erro ao inserir no sorteio, exclui o cadastro do cliente (rollback)
            $cliente_id = $conn->insert_id;
            $conn->query("DELETE FROM clientes WHERE id = $cliente_id");
            echo "<div style='font-family: sans-serif; background-color: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px auto; max-width: 500px; text-align: center;'>Erro ao cadastrar número para sorteio: " . $stmt_sorteio->error . " Cadastro do cliente desfeito.</div>";
            echo "<p style='font-family: sans-serif; text-align: center;'><a href='cadastro.html?telefone=" . $telefone . "'>Voltar ao formulário de cadastro</a></p>";
            $stmt_sorteio->close();
        }
    } else {
        echo "<div style='font-family: sans-serif; background-color: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px auto; max-width: 500px; text-align: center;'>Erro ao cadastrar cliente: " . $stmt_cliente->error . "</div>";
        echo "<p style='font-family: sans-serif; text-align: center;'><a href='cadastro.html?telefone=" . $telefone . "'>Voltar ao formulário de cadastro</a></p>";
    }

    // Fecha as statements
    $stmt_cliente->close();
}

function sortearNumeroUnico($conn, $min, $max) {
    $numero = rand($min, $max);
    $stmt = $conn->prepare("SELECT COUNT(*) FROM sorteio WHERE numeroSorteado = ?");
    $stmt->bind_param("i", $numero);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        return sortearNumeroUnico($conn, $min, $max); // Chama recursivamente até encontrar um número único
    } else {
        return $numero;
    }
}

// Fecha a conexão com o banco de dados
$conn->close();
?>