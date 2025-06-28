<?php
//$servername = "myshared2380";
//$username = "cadastrosouza";
//$password = "Souza@7498"; 
//$dbname = "cadastrosouza";

$servername = "localhost"; // Use "localhost" se estiver rodando localmente ou o nome do servidor se for remoto
$username = "root";
$password = ""; // Assuming no password for the user "cadastrosouza" based on the provided data.
$dbname = "cadastrosouza";
// Conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Obtém os dados dos parâmetros da URL
$telefone = isset($_GET['telefone']) ? $_GET['telefone'] : '';
$total_pedido = isset($_GET['total']) ? (float)$_GET['total'] : 0.00; // Converte para float para cálculos
$forma_pagamento = isset($_GET['forma_pagamento']) ? $_GET['forma_pagamento'] : 'Não informado';
$produtos_json = isset($_GET['produtos']) ? $_GET['produtos'] : '[]';
$produtos_selecionados = json_decode($produtos_json, true);
$id_pedido_existente = isset($_GET['id_pedido_existente']) ? (int)$_GET['id_pedido_existente'] : 0; // ID do pedido existente
$has_gas_product = isset($_GET['has_gas_product']) ? (int)$_GET['has_gas_product'] : 0; // Tem produto de gás (0 ou 1)


// Obtém valor_pago apenas se estiver definido e a forma de pagamento for 'dinheiro'
$valor_pago = null;
if ($forma_pagamento === 'dinheiro' && isset($_GET['valor_pago'])) {
    $valor_pago = (float)$_GET['valor_pago'];
}

// Calcula o troco
$troco = 0;
if ($forma_pagamento === 'dinheiro' && $valor_pago !== null) {
    $troco = $valor_pago - $total_pedido;
}

// Variáveis para os dados do cliente e endereço
$cliente_nome = '';
$endereco_cliente = '';
$quadra_cliente = '';
$lote_cliente = '';
$setor_cliente = '';
$complemento_cliente = '';
$cidade_cliente = '';
$endereco_completo_formatado = '';

// Se o telefone estiver presente, busca os dados do cliente e endereço no banco de dados
if (!empty($telefone)) {
    $sql_cliente = "SELECT nome, endereco, quadra, lote, setor, complemento, cidade FROM clientes WHERE telefone = ?";
    $stmt_cliente = $conn->prepare($sql_cliente);
    
    if ($stmt_cliente) {
        $stmt_cliente->bind_param("s", $telefone);
        $stmt_cliente->execute();
        $stmt_cliente->bind_result($cliente_nome, $endereco_cliente, $quadra_cliente, $lote_cliente, $setor_cliente, $complemento_cliente, $cidade_cliente);
        $stmt_cliente->fetch();
        $stmt_cliente->close();

        // Formata o endereço completo
        $endereco_completo_formatado = ucwords(htmlspecialchars($endereco_cliente)) . ', Qd ' . htmlspecialchars($quadra_cliente) . ', Lt ' . htmlspecialchars($lote_cliente);

        if (!empty($setor_cliente)) {
            $endereco_completo_formatado .= '<br>Setor: ' . ucwords(htmlspecialchars($setor_cliente));
        }
        if (!empty($complemento_cliente)) {
            $endereco_completo_formatado .= '<br>Complemento: ' . ucwords(htmlspecialchars($complemento_cliente));
        }
        $endereco_completo_formatado .= '<br>' . ucwords(htmlspecialchars($cidade_cliente));

    } else {
        error_log("Erro ao preparar a busca de cliente em confirmar_pedido.php: " . $conn->error);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Pedido</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .card {
            text-align: center;
            padding: 30px;
        }

        .card p {
            margin-bottom: 10px;
            font-size: 1.1em;
        }
         .card .message-paragraph {
            margin-bottom: 10px;
            font-size: 1.1em;
            margin-top: 100px; /* Aumentado o ajuste para empurrar o parágrafo para baixo */
        }
        .nome-cliente{
            color:var(--cor-titulo)
        }
        .pedido-summary {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 20px auto;
            max-width: 400px;
            text-align: left;
        }
        .pedido-summary-header {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 10px auto;
            max-width: 400px;
            text-align: left;
        }
        .pedido-summary-header  {
            font-size: 1.2em;
        }

        .pedido-summary strong {
            color: var(--cor-principal);
        }
        .pedido-summary-title{
            color: var(--cor-titulo);
        }
        .form-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 30px;
        }
        .form-buttons button {
            padding: 12px 25px;
            font-size: 1.1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: white;
            transition: background-color 0.3s ease;
            display: block;
            width: 100%;
            box-sizing: border-box;
        }
        .form-buttons .confirm-button {
            background-color: var(--cor-principal);
        }
        .form-buttons .confirm-button:hover {
            background-color: var(--cor-secundaria);
        }
        .form-buttons .cancel-button {
            background-color:   #eb9f25;
        }
        .form-buttons .cancel-button:hover {
            background-color: rgb(197, 133, 31);
        }
    </style>
</head>
<body>
    <div class="background"></div>
    <div class="card">
        <img class="logo" src="imagens/logo.png" alt="Logo" />
        <p class="message-paragraph">Olá, <strong class="nome-cliente"><?php echo htmlspecialchars(ucwords(explode(' ', $cliente_nome)[0])); ?></strong>! <br/>Por favor, confirme os detalhes do seu pedido:</p>
        
        <div class="pedido-summary">
            <div class="pedido-summary-header">
            <strong class="pedido-summary-title">Endereço:</strong> 
            <p><?php echo $endereco_completo_formatado; ?></p>
            </div>
            <div class="pedido-summary-header">
            <p><strong>Produtos:</strong></p>
            <ul>
                <?php foreach ($produtos_selecionados as $produto): ?>
                    <li><?php echo htmlspecialchars($produto['quantidade']); ?>x <?php echo htmlspecialchars(ucwords($produto['nome'])); ?> (R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>)</li>
                <?php endforeach; ?>
            </ul>
            <p><strong>Total:</strong> R$ <?php echo number_format($total_pedido, 2, ',', '.'); ?></p>
            </div>
            <div class="pedido-summary-header">
            <strong>Forma de Pagamento:</strong> 
            <p><?php echo htmlspecialchars(ucwords($forma_pagamento)); ?></p>
            <?php if ($forma_pagamento === 'dinheiro' && $valor_pago !== null): ?>
                <p><strong>Valor Pago:</strong> R$ <?php echo number_format($valor_pago, 2, ',', '.'); ?></p>
                <p><strong>Troco:</strong> R$ <?php echo number_format($troco, 2, ',', '.'); ?></p>
            <?php endif; ?>
            </div>
        </div>

        <div class="form-buttons">
            <button class="confirm-button" id="finalizarPedidoBtn">Finalizar Pedido</button>
            <button class="cancel-button" onclick="window.history.back()">Voltar e Editar</button>
        </div>
    </div>

    <script>

        document.getElementById('finalizarPedidoBtn').addEventListener('click', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const telefoneCliente = "<?php echo htmlspecialchars($telefone); ?>";
            const totalPedido = "<?php echo htmlspecialchars($total_pedido); ?>";
            const formaPagamento = "<?php echo htmlspecialchars($forma_pagamento); ?>";
            const produtosJson = urlParams.get('produtos');
            const produtosSelecionados = JSON.parse(produtosJson);
            const valorPago = parseFloat(urlParams.get('valor_pago'));
            const idPedidoExistente = parseInt("<?php echo htmlspecialchars($id_pedido_existente); ?>"); // Converte para int
            const hasGasProduct = Boolean(parseInt("<?php echo htmlspecialchars($has_gas_product); ?>")); // Converte para boolean

            const dadosDoPedido = {
                telefone: telefoneCliente,
                total: totalPedido,
                forma_pagamento: formaPagamento,
                produtos: produtosSelecionados,
                valor_pago: valorPago,
                id_pedido_existente: idPedidoExistente, // Inclui o ID do pedido existente
                has_gas_product: hasGasProduct // Inclui a informação de gás
            };
            console.log('Dados do pedido:', dadosDoPedido); // Log para verificar os dados
            fetch('salvar_pedido.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(dadosDoPedido)
                    })
                    .then(response => {
                        // Log the raw response status and text for debugging
                        console.log('Raw response status:', response.status);
                        if (!response.ok) {
                            // If response is not OK (e.g., 500 Internal Server Error)
                            return response.text().then(text => {
                                throw new Error('HTTP error! Status: ' + response.status + ' - ' + text);
                            });
                        }
                        return response.json(); // Attempt to parse as JSON
                    })
                    .then(data => {
                        if (data.success) {
                            console.log(data.message);
                            window.location.replace(data.redirect_url);
                        } else {
                            console.log('Erro ao finalizar o pedido: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Erro na requisição:', error);
                        console.log('Ocorreu um erro ao tentar finalizar o pedido. Por favor, tente novamente. Detalhes no console.');
                    });
            });
    </script>
</body>
</html>
