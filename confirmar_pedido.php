<?php
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "cadastro";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get data from URL parameters
$telefone = isset($_GET['telefone']) ? $_GET['telefone'] : '';
$total_pedido = isset($_GET['total']) ? (float)$_GET['total'] : 0.00; // Convert to float for calculations
$forma_pagamento = isset($_GET['forma_pagamento']) ? $_GET['forma_pagamento'] : 'Não informado';
$produtos_json = isset($_GET['produtos']) ? $_GET['produtos'] : '[]';
$produtos_selecionados = json_decode($produtos_json, true);

$valor_pago = null; // Inicializa como null
if ($forma_pagamento === 'dinheiro' && isset($_GET['valor_pago'])) {
    $valor_pago = (float)$_GET['valor_pago'];
    // Adicione um console.log aqui para depuração no servidor
    error_log("confirmar_pedido.php: Valor Pago recebido: " . $valor_pago);
}

// Calculate troco
$troco = 0;
if ($forma_pagamento === 'dinheiro' && $valor_pago !== null) {
    $troco = $valor_pago - $total_pedido;
    // Adicione um console.log aqui para depuração no servidor
    error_log("confirmar_pedido.php: Troco calculado: " . $troco);
}


// Fetch client details
$cliente_nome = "Não encontrado";
$cliente_endereco = "Não encontrado";

if (!empty($telefone)) {
    // CORREÇÃO AQUI: Se a chave primária da tabela 'clientes' é 'id', use 'id'
    $stmt = $conn->prepare("SELECT nome, endereco, quadra, lote, setor, complemento, cidade FROM clientes WHERE telefone = ?");
    if ($stmt) { // Verifica se a preparação foi bem-sucedida
        $stmt->bind_param("s", $telefone);
        $stmt->execute();
        $stmt->bind_result($nome, $endereco, $quadra, $lote, $setor, $complemento, $cidade);
        $stmt->fetch();
        $stmt->close();

        if ($nome) {
            $cliente_nome = ucwords($nome); // Capitalize first letter of each word
            $cliente_endereco = ucwords("$endereco, Qd $quadra, Lt $lote, Setor $setor");
            if (!empty($complemento)) {
                $cliente_endereco .= ", Complemento: " . ucwords($complemento);
            }
            $cliente_endereco .= ", " . ucwords($cidade);
        }
    } else {
        // Log ou exiba um erro se a preparação da query falhar
        error_log("Erro ao preparar a query para buscar cliente: " . $conn->error);
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
</head>
<body>
    <div class="background"></div>
    <div class="card">
        <img class="logo" src="imagens/logo.png" alt="Logo" />
        <h2>Confirme seu Pedido</h2>

        <div class="confirmation-details">
            <p><strong>Nome:</strong> <?php echo htmlspecialchars($cliente_nome); ?></p>
            <p><strong>Endereço de Entrega:</strong> <?php echo htmlspecialchars($cliente_endereco); ?></p>

            <h3>Produtos Selecionados:</h3>
            <ul>
                <?php if (!empty($produtos_selecionados)): ?>
                    <?php foreach ($produtos_selecionados as $produto): ?>
                        <li>
                            <?php echo htmlspecialchars($produto['quantidade']); ?> - <?php echo htmlspecialchars(ucwords($produto['nome'])); ?> -
                            Valor Unitário: R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?> -
                            valor do pedido: R$ <?php echo number_format($produto['preco'] * $produto['quantidade'], 2, ',', '.'); ?>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>Nenhum produto selecionado.</li>
                <?php endif; ?>
            </ul>

            <p><strong>Valor Total do Pedido:</strong> R$ <?php echo number_format($total_pedido, 2, ',', '.'); ?></p>
            <p><strong>Forma de Pagamento:</strong> <?php echo htmlspecialchars(ucwords($forma_pagamento)); ?></p>

            <?php if ($forma_pagamento === 'dinheiro' && $valor_pago !== null && $valor_pago >= $total_pedido): ?>
        <p><strong>Valor Pago:</strong> R$ <?php echo number_format($valor_pago, 2, ',', '.'); ?></p>
        <p><strong>Troco:</strong> R$ <?php echo number_format($troco, 2, ',', '.'); ?></p>
    <?php
    // Opcional: Adicionar uma mensagem se o valor pago for insuficiente na tela de confirmação
    elseif ($forma_pagamento === 'dinheiro' && $valor_pago !== null && $valor_pago < $total_pedido):
    ?>
        <p style="color: red;"><strong>Valor Pago Insuficiente:</strong> R$ <?php echo number_format($valor_pago, 2, ',', '.'); ?></p>
        <p style="color: red;"><strong>Faltam:</strong> R$ <?php echo number_format($total_pedido - $valor_pago, 2, ',', '.'); ?></p>
    <?php endif; ?>

    <button id="finalizarPedidoBtn">Finalizar Pedido</button>
    <a href="pedido.html?telefone=<?php echo htmlspecialchars($telefone); ?>" class="voltar-btn">Voltar e Editar Pedido</a>

    <div class="footer">
      <p>&copy; 2025 Souza Gás. Todos os direitos reservados.</p>
    </div>

    <script>
        // Este é o JavaScript que lida com o clique no botão Finalizar Pedido
        document.getElementById('finalizarPedidoBtn').addEventListener('click', function() {
            // As variáveis PHP são injetadas diretamente no JavaScript aqui
            const telefoneCliente = "<?php echo htmlspecialchars($telefone); ?>";
            const totalPedido = "<?php echo htmlspecialchars($total_pedido); ?>";
            const formaPagamento = "<?php echo htmlspecialchars($forma_pagamento); ?>";
            const produtosSelecionados = <?php echo json_encode($produtos_selecionados); ?>;
            const valorPago = <?php echo ($valor_pago !== null) ? htmlspecialchars($valor_pago) : 'null'; ?>;

            const dadosDoPedido = {
                telefone: telefoneCliente,
                total: totalPedido,
                forma_pagamento: formaPagamento,
                produtos: produtosSelecionados,
                valor_pago: valorPago
            };

            fetch('salvar_pedido.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dadosDoPedido)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message); // Exibe a mensagem de sucesso
                    // AQUI É A MUDANÇA CRÍTICA: Redireciona usando a URL recebida do servidor
                    window.location.href = data.redirect_url;
                } else {
                    alert('Erro ao finalizar o pedido: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                alert('Ocorreu um erro ao tentar finalizar o pedido. Por favor, tente novamente.');
            });
        });
    </script>
</body>
</html>