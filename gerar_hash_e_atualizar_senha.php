<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro";

// Nova conexão mysqli
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

// Dados do usuário para atualizar
$email_alvo = 'albert@souza.com.br';
$senha_em_texto_simples = '749870';
// Gerar o hash da senha
$senha_hash = password_hash($senha_em_texto_simples, PASSWORD_DEFAULT);

// Preparar e executar a atualização
$stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");

if ($stmt === false) {
    die("Erro na preparação da consulta: " . $conn->error);
}

$stmt->bind_param("ss", $senha_hash, $email_alvo);

if ($stmt->execute()) {
    echo "Senha de '" . $email_alvo . "' atualizada para hash com sucesso no banco de dados!";
} else {
    echo "Erro ao atualizar a senha para '" . $email_alvo . "': " . $stmt->error;
}

$stmt->close();
$conn->close();
?>