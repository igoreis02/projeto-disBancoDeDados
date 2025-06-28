<?php
//$servername = "myshared2380";
//$username = "cadastrosouza";
//$password = "Souza@7498"; 
//$dbname = "cadastrosouza";

$servername = "localhost"; // Use "localhost" se estiver rodando localmente ou o nome do servidor se for remoto
$username = "root";
$password = ""; // Assuming no password for the user "cadastrosouza" based on the provided data.
$dbname = "cadastrosouza";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    $conn->begin_transaction(); // Inicia a transação

    // Excluir da tabela sorteio
    $sql_sorteio = "DELETE FROM sorteio WHERE id_cliente = ?";
    $stmt_sorteio = $conn->prepare($sql_sorteio);
    $stmt_sorteio->bind_param("i", $id);

    if ($stmt_sorteio->execute()) {
        $stmt_sorteio->close();     // Fecha o statement da tabela sorteio
        // Se a exclusão da tabela sorteio for bem-sucedida, prossegue para excluir da tabela clientes

        // Excluir da tabela clientes
        $sql_clientes = "DELETE FROM clientes WHERE id = ?";
        $stmt_clientes = $conn->prepare($sql_clientes);
        $stmt_clientes->bind_param("i", $id);

        if ($stmt_clientes->execute()) {
            $conn->commit(); // Confirma a transação
            echo json_encode(array("success" => true, "message" => "Cliente e dados de sorteio excluídos com sucesso."));
        } else {
            $conn->rollback(); // Reverte a transação
            echo json_encode(array("success" => false, "message" => "Erro ao excluir o cliente: " . $stmt_clientes->error));
        }

        $stmt_clientes->close();
    } else {
        $conn->rollback(); // Reverte a transação
        echo json_encode(array("success" => false, "message" => "Erro ao excluir dados de sorteio: " . $stmt_sorteio->error));
        $conn->close();
    }

    
} else {
    echo json_encode(array("success" => false, "message" => "ID não fornecido."));
}
$conn->close();
?>