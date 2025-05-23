<?php
// Obtém os dados da URL
$telefone = isset($_GET['telefone']) ? htmlspecialchars($_GET['telefone']) : '';
$nome = isset($_GET['nome']) ? htmlspecialchars($_GET['nome']) : '';
$id_pedido = isset($_GET['id_pedido']) ? htmlspecialchars($_GET['id_pedido']) : '';
$status_pedido = isset($_GET['status_pedido']) ? htmlspecialchars($_GET['status_pedido']) : '';
$valor_total = isset($_GET['valor_total']) ? (float)$_GET['valor_total'] : 0.00;
$produtos_detalhes = isset($_GET['produtos_detalhes']) ? htmlspecialchars($_GET['produtos_detalhes']) : '';
$forma_pagamento = isset($_GET['forma_pagamento']) ? htmlspecialchars($_GET['forma_pagamento']) : '';
$valor_pago = isset($_GET['valor_pago']) ? (float)$_GET['valor_pago'] : null;
$troco = isset($_GET['troco']) ? (float)$_GET['troco'] : 0.00;

// Capitaliza a primeira letra do nome
$primeiroNome = explode(' ', $nome)[0];
$primeiroNome = ucwords($primeiroNome);

// Formata o valor total
$valor_total_formatado = number_format($valor_total, 2, ',', '.');

// Formata o valor pago e troco se existirem
$valor_pago_formatado = ($valor_pago !== null) ? number_format($valor_pago, 2, ',', '.') : '';
$troco_formatado = number_format($troco, 2, ',', '.');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido na Loja</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Regra para remover o pseudo-elemento ::before da classe .card nesta página */
        .card::before {
            content: none;
        }

        .card {
            text-align: center;
            padding: 30px;
        }
        .card h1 {
            color: var(--cor-titulo);
            margin-bottom: 20px;
        }
        /* Estilo para a frase específica */
        .card .message-paragraph {
            margin-bottom: 10px;
            font-size: 1.1em;
            margin-top: 50px; /* Aumentado o ajuste para empurrar o parágrafo para baixo */
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
            background-color: #dc3545; /* Vermelho para sair */
        }
        .form-buttons .exit-button:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="background"></div>
    <div class="card">
        <img class="logo" src="imagens/logo.png" alt="Logo" />
        <h1>Olá, <?php echo $primeiroNome; ?>!</h1>
        <p class="message-paragraph">Você tem um pedido que está na Loja esperando para sair:</p>
        
        <div class="pedido-details">
            <p><strong>Produtos:</strong> <?php echo $produtos_detalhes; ?></p>
            <p><strong>Valor Total:</strong> R$ <?php echo $valor_total_formatado; ?></p>
            <p><strong>Forma de Pagamento:</strong> <?php echo !empty($forma_pagamento) ? ucwords($forma_pagamento) : 'Não informado'; ?>
            <?php if ($forma_pagamento === 'dinheiro' && $valor_pago !== null): ?>
                (R$ <?php echo $valor_pago_formatado; ?>)
            <?php endif; ?>
            </p>
            <?php if ($forma_pagamento === 'dinheiro' && $valor_pago !== null): ?>
                <p><strong>Troco:</strong> R$ <?php echo $troco_formatado; ?></p>
            <?php endif; ?>
        </div>

        <div class="form-buttons">
            <a href="pedido.html?telefone=<?php echo $telefone; ?>&id_pedido=<?php echo $id_pedido; ?>" class="edit-button">Editar Pedido</a>
            <a href="index.html" class="exit-button">Sair</a>
        </div>
    </div>
</body>
</html>
