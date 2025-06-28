<?php
// Adicione estas duas linhas para depuração (REMOVA EM PRODUÇÃO!)
error_reporting(E_ALL);
ini_set('display_errors', 1);
//$servername = "myshared2380";
//$username = "cadastrosouza";
//$password = "Souza@7498"; 
//$dbname = "cadastrosouza";

$servername = "localhost"; // Use "localhost" se estiver rodando localmente ou o nome do servidor se for remoto
$username = "root";
$password = ""; // Assuming no password for the user "cadastrosouza" based on the provided data.
$dbname = "cadastrosouza";
// nova conexão mysqli
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupera os dados do formulário
    // AQUI: Usamos o telefone como identificador
    $telefone = strtolower($_POST['telefone']);
    $endereco = strtolower(trim($_POST['endereco']));
    $quadra = strtolower(trim($_POST['quadra']));
    $lote = strtolower(trim($_POST['lote']));
    $setor = strtolower(trim($_POST['setor']));
    $complemento = strtolower(trim($_POST['complemento']));
    $cidade = strtolower(trim($_POST['cidade']));
    $latitude = isset($_POST['latitude']) ? $_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) ? $_POST['longitude'] : null;

    // Validação
    $erros = array();
    if (empty($telefone)) { // Adicionei validação para telefone
        $erros[] = "Telefone não fornecido.";
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
        // Redireciona de volta para a página de endereço com os parâmetros necessários
        echo "<p style='font-family: sans-serif; text-align: center;'><a href='cadastro_endereco.html?telefone=" . urlencode($telefone) . "'>Voltar ao formulário de endereço</a></p>";
        exit;
    }

    // Atualiza os dados de endereço na tabela 'clientes' usando o TELEFONE
    $sql_update = "UPDATE clientes SET endereco = ?, quadra = ?, lote = ?, setor = ?, complemento = ?, cidade = ?, latitude = ?, longitude = ? WHERE telefone = ?";
    $stmt_update = $conn->prepare($sql_update);

    if ($stmt_update === false) {
        die("Erro na preparação da consulta de atualização: " . $conn->error);
    }

    // 'sdssdsssd' - 6 strings, 2 doubles, 1 string (para o telefone)
    $stmt_update->bind_param("ssssssdds", $endereco, $quadra, $lote, $setor, $complemento, $cidade, $latitude, $longitude, $telefone);

    if ($stmt_update->execute()) {
        // Redireciona para a página de pedido ou sucesso
        header("Location: pedido.html?telefone=" . urlencode($telefone));
        exit;
    } else {
        echo "<div style='font-family: sans-serif; background-color: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px auto; max-width: 500px; text-align: center;'>Erro ao atualizar endereço: " . $stmt_update->error . "</div>";
        echo "<p style='font-family: sans-serif; text-align: center;'><a href='cadastro_endereco.html?telefone=" . urlencode($telefone) . "'>Voltar ao formulário de endereço</a></p>";
    }

    $stmt_update->close();
} else {
    echo "<div style='font-family: sans-serif; background-color: #f0ad4e; color: #333; padding: 15px; border: 1px solid #eea236; border-radius: 4px; margin: 20px auto; max-width: 500px; text-align: center;'>Requisição inválida.</div>";
    echo "<p style='font-family: sans-serif; text-align: center;'><a href='index.html'>Voltar para o Início</a></p>";
}

$conn->close();
?>