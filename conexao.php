<?php
// conexao.php
$host = 'localhost'; // Seu host
$db = 'cadastro'; // Nome do seu banco de dados
$user = 'root'; // Seu usuário do banco
$pass = ''; // Sua senha do banco
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Para depuração, você pode registrar o erro, mas em produção evite expor detalhes
    error_log('Erro de conexão com o banco de dados: ' . $e->getMessage());
    // E então, exiba uma mensagem genérica para o usuário
    die(json_encode(['success' => false, 'message' => 'Erro interno do servidor. Tente novamente mais tarde.']));
}
// Não há tag de fechamento ?> para evitar problemas de espaços em branco