<?php
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "cadastro";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

if (isset($_POST['telefone'])) {
    $telefone = $_POST['telefone'];

    // Verificar se o telefone existe na tabela clientes
    $stmt_clientes = $pdo->prepare("SELECT id, nome FROM clientes WHERE telefone = ?");
    $stmt_clientes->execute([$telefone]);
    $cliente = $stmt_clientes->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        // Se o cliente existe, buscar o número do sorteio
        $stmt_sorteio = $pdo->prepare("SELECT numeroSorteado FROM sorteio WHERE id_cliente = ?");
        $stmt_sorteio->execute([$cliente['id']]);
        $sorteio = $stmt_sorteio->fetch(PDO::FETCH_ASSOC);

        $response = array(
            'existe' => true,
            'nome' => $cliente['nome'],
            'numeroSorteado' => $sorteio ? $sorteio['numeroSorteado'] : null // Verifica se há número sorteado
        );
    } else {
        $response = array('existe' => false);
    }

    echo json_encode($response);
}
?>