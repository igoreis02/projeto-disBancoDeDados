<?php
// conexao.php
//$servername = "myshared2380";
//$username = "cadastrosouza";
//$password = "Souza@7498"; 
//$dbname = "cadastrosouza";

$servername = "localhost"; // Use "localhost" se estiver rodando localmente ou o nome do servidor se for remoto
$username = "root";
$password = ""; // Assuming no password for the user "cadastrosouza" based on the provided data.
$dbname = "cadastrosouza";
$charset = 'utf8mb4';

$dsn = "mysql:host=$servername;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
   $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // Para depuração, você pode registrar o erro, mas em produção evite expor detalhes
    error_log('Erro de conexão com o banco de dados: ' . $e->getMessage());
    // E então, exiba uma mensagem genérica para o usuário
    die(json_encode(['success' => false, 'message' => 'Erro interno do servidor. Tente novamente mais tarde.']));
}
// Não há tag de fechamento ?> para evitar problemas de espaços em branco