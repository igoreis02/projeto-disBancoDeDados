<?php
// Obtém os dados da URL
$telefone = isset($_GET['telefone']) ? htmlspecialchars($_GET['telefone']) : '';
$nome = isset($_GET['nome']) ? htmlspecialchars($_GET['nome']) : '';


// Capitaliza a primeira letra do nome
$primeiroNome = explode(' ', $nome)[0];
$primeiroNome = ucwords($primeiroNome);


// Conexão com o banco de dados para buscar o endereço completo
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "cadastro";

$conn = new mysqli($servername, $username, $password, $dbname);

$endereco_completo_formatado = 'Endereço não disponível';
if ($conn->connect_error) {
    error_log("Erro na conexão com o banco de dados em confirmar_endereco.php: " . $conn->connect_error);
} else {
    // Busca o id_cliente baseado no telefone
    $id_cliente = null;
    $stmt_cliente_id = $conn->prepare("SELECT id FROM clientes WHERE telefone = ?");
    if ($stmt_cliente_id) {
        $stmt_cliente_id->bind_param("s", $telefone);
        $stmt_cliente_id->execute();
        $result_cliente_id = $stmt_cliente_id->get_result();
        if ($result_cliente_id->num_rows > 0) {
            $row_cliente_id = $result_cliente_id->fetch_assoc();
            $id_cliente = $row_cliente_id['id'];
        }
        $stmt_cliente_id->close();
    }

    if ($id_cliente !== null) {
        $sql_endereco = "SELECT endereco, quadra, lote, setor, complemento, cidade FROM clientes WHERE id = ?";
        $stmt_endereco = $conn->prepare($sql_endereco);
        if ($stmt_endereco) {
            $stmt_endereco->bind_param("i", $id_cliente);
            $stmt_endereco->execute();
            $stmt_endereco->bind_result($endereco, $quadra, $lote, $setor, $complemento, $cidade);
            $stmt_endereco->fetch();
            $stmt_endereco->close();

            $endereco_completo_formatado = ucwords(htmlspecialchars($endereco)) . ', Qd ' . htmlspecialchars($quadra) . ', Lt ' . htmlspecialchars($lote);
            if (!empty($setor)) {
                $endereco_completo_formatado .= '<br>Setor: ' . ucwords(htmlspecialchars($setor));
            }
            if (!empty($complemento)) {
                $endereco_completo_formatado .= '<br>Complemento: ' . ucwords(htmlspecialchars($complemento));
            }
            $endereco_completo_formatado .= '<br>' . ucwords(htmlspecialchars($cidade));
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido na Loja</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
                .card {
            text-align: center;
            padding: 30px;
        }
        .card h1 {
            color: var(--cor-titulo);
            margin-bottom: 0;
            padding-top: 90px; /* Adicionado para espaçamento superior */
        }
        /* Estilo para a frase específica */
        .card .message-paragraph {
            margin-bottom: 10px;
            font-size: 1.1em;
            margin-top: 25px; /* Aumentado o ajuste para empurrar o parágrafo para baixo */
        }
        .pedido-details {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 20px auto;
            max-width: 400px;
            text-align: left;
        }
        .pedido-details strong {
            color: var(--cor-principal);
        }
        .form-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 30px;
        }
        .form-buttons button, .form-buttons a {
            padding: 12px 25px;
            font-size: 1.1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: white;
            transition: background-color 0.3s ease;
            display: block; /* Garante que ocupem a largura total */
            width: 100%; /* Garante que ocupem a largura total */
            box-sizing: border-box; /* Inclui padding e borda na largura total */
        }
        .form-buttons .edit-button {
            background-color: var(--cor-principal);
        }
        .form-buttons .edit-button:hover {
            background-color: var(--cor-secundaria);
        }
        .form-buttons .exit-button {
            background-color:   #eb9f25; /* Vermelho para sair */
        }
        .form-buttons .exit-button:hover {
            background-color: rgb(197, 133, 31);
        }
    </style>
</head>
<body>
    <div class="background"></div>
    <div class="card">
        <img class="logo" src="imagens/logo.png" alt="Logo" />
        <h1>Olá, <?php echo $primeiroNome; ?>!</h1>
        <p class="message-paragraph">Confirme o seu endereço:</p>
        
        <div class="pedido-details">
            <p><strong>Endereço:</strong> <?php echo $endereco_completo_formatado; ?></p>
        </div>
        <div class="form-buttons">
            <form action="editar_endereco.php" method="GET">
                <input type="hidden" name="telefone" value="<?php echo htmlspecialchars($telefone); ?>">
                <button type="submit" class="edit-button">Editar Endereço</button>
            </form>
            <form action="pedido.html?" method="GET">
                <input type="hidden" name="telefone" value="<?php echo htmlspecialchars($telefone); ?>">   
                <input type="hidden" name="nome" value="<?php echo htmlspecialchars($nome); ?>">  
                <button type="submit" class="edit-button">Confirmar Endereço</button>
            </form>
            <a href="index.html" class="exit-button">Sair</a>
        </div>
    </div>
</body>
</html>
