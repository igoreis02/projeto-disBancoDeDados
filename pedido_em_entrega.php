<?php
// Obtém os dados da URL
$telefone = isset($_GET['telefone']) ? htmlspecialchars($_GET['telefone']) : '';
$nome = isset($_GET['nome']) ? htmlspecialchars($_GET['nome']) : '';

// Capitaliza a primeira letra do nome
$primeiroNome = explode(' ', $nome)[0];
$primeiroNome = ucwords($primeiroNome);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido em Entrega</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .card {
            text-align: center;
            padding: 30px;
        }
        .card h1 {
            color: var(--cor-titulo);
            margin-bottom: 20px;
        }
        .card p {
            margin-bottom: 25px;
            font-size: 1.2em;
            line-height: 1.5;
        }
        .exit-button {
            background-color: var(--cor-principal);
            color: white;
            padding: 12px 25px;
            font-size: 1.1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease;
            display: inline-block; /* Para centralizar com text-align */
            margin-top: 20px;
        }
        .exit-button:hover {
            background-color: var(--cor-secundaria);
        }
    </style>
</head>
<body>
    <div class="background"></div>
    <div class="card">
        <img class="logo" src="imagens/logo.png" alt="Logo" />
        <h1>Olá, <?php echo $primeiroNome; ?>!</h1>
        <p>Seu pedido já saiu para entrega. Em alguns minutos deve estar chegando na sua residência.</p>
        <a href="index.html" class="exit-button">Sair</a>
    </div>
</body>
</html>
