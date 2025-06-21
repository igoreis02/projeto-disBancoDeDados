<?php
// Obtém os dados da URL
$telefone = isset($_GET['telefone']) ? htmlspecialchars($_GET['telefone']) : '';
$nome = isset($_GET['nome']) ? htmlspecialchars($_GET['nome']) : '';


// Capitaliza a primeira letra do nome
$primeiroNome = explode(' ', $nome)[0];
$primeiroNome = ucwords($primeiroNome);


// Conexão com o banco de dados para buscar o endereço completo
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro";

$conn = new mysqli($servername, $username, $password, $dbname);

$endereco_completo_formatado = 'Endereço não disponível';
$numerosSorteio = ''; // Initialize to an empty string
$hasLotteryNumbers = false; // Flag to check if numbers exist

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
        // Fetch address
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

        // Fetch lottery numbers
        $sql_sorteio = "SELECT numeroSorteado FROM sorteio WHERE id_cliente = ?";
        $stmt_sorteio = $conn->prepare($sql_sorteio);
        if ($stmt_sorteio) {
            $stmt_sorteio->bind_param("i", $id_cliente);
            $stmt_sorteio->execute();
            $result_sorteio = $stmt_sorteio->get_result();
            if ($result_sorteio->num_rows > 0) {
                $hasLotteryNumbers = true; // Set flag to true if numbers exist
                $numeros = [];
                while ($row_sorteio = $result_sorteio->fetch_assoc()) {
                    $numeros[] = '<div class="lottery-number-box">' . htmlspecialchars($row_sorteio['numeroSorteado']) . '</div>';
                }
                $numerosSorteio = implode('', $numeros); // Join numbers for display
            }
            $stmt_sorteio->close();
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
        /* New styles for lottery numbers */
        .lottery-numbers-container {
            display: flex;
            flex-wrap: wrap; /* Allow numbers to wrap to the next line */
            justify-content: center; /* Center the boxes horizontally */
            gap: 10px; /* Space between boxes */
            margin-top: 10px;
            max-width: 440px; /* Example: (50px min-width + 10px padding*2 + 2px border*2) * 4 + 10px*3 gap = 440px (adjust based on your actual box dimensions) */
            margin-left: auto; /* Center the container itself */
            margin-right: auto; /* Center the container itself */
        }
        .lottery-number-box {
            background-color: #e0f7fa; /* Light blue background for boxes */
            border: 1px solid #b2ebf2; /* Border for boxes */
            border-radius: 5px;
            padding: 8px 12px;
            font-size: 1.1em;
            font-weight: bold;
            color: #00796b; /* Darker text color */
            min-width: 50px; /* Minimum width for the boxes */
            text-align: center;
            flex: 1 1 calc(25% - 15px); /* This tries to make each box take 25% width minus some gap. Adjust '15px' based on your 'gap' and desired spacing */
            box-sizing: border-box; /* Include padding and border in the width calculation */
            max-width: 90px; /* Optional: A max-width to prevent boxes from getting too wide if there are fewer than 4 */
        }
        /* Style for the button to show/hide numbers */
        .show-numbers-button {
            background-color: #eb9f25; /* Green */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }
        .show-numbers-button:hover {
            background-color: rgb(197, 133, 31);
        }
        /* Initially hide the lottery numbers container */
        #lotteryNumbersDisplay {
            display: none;
        }
        .numeros-sorteio{
            text-align: center;
            margin-top: 20px;
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
            <?php if ($hasLotteryNumbers): ?>
            <div class="numeros-sorteio">
                <button class="show-numbers-button" id="toggleLotteryNumbers">Ver meus números do sorteio</button>
                <div class="pedido-details lottery-numbers-container" id="lotteryNumbersDisplay">
                    <?php echo $numerosSorteio; ?>
                </div>
            </div>
        <?php endif; ?>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('toggleLotteryNumbers');
            const lotteryNumbersDisplay = document.getElementById('lotteryNumbersDisplay');

            if (toggleButton && lotteryNumbersDisplay) {
                toggleButton.addEventListener('click', function() {
                    if (lotteryNumbersDisplay.style.display === 'none' || lotteryNumbersDisplay.style.display === '') {
                        lotteryNumbersDisplay.style.display = 'flex'; // Use flex to maintain box layout
                        toggleButton.textContent = 'Ocultar números';
                    } else {
                        lotteryNumbersDisplay.style.display = 'none';
                        toggleButton.textContent = 'Ver meus números do sorteio';
                    }
                });
            }
        });
    </script>
</body>
</html>