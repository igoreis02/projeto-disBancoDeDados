<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realizar Pedido</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .product-list {
            display: flex;
            margin-bottom: 20px;
        }

        .product-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            flex-direction: column;
        }

        .product-item label {
            margin-right: 10px;
        }

        .payment-options {
            margin-bottom: 20px;
        }

        .payment-options label {
            display: block;
            margin-bottom: 5px;
        }
        .imagem-produto{
            width: 25%;
        }
        .imagem-produtoGas{
            width: 35%;
        }

    </style>
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <img class="logo" src="imagens/logo.png" alt="Logo" />
        <h2>Realizar Pedido</h2>
        <h3>Selecione os Produtos e Quantidades:</h3>
        <div class="product-list">

            <div class="product-item" id="img">
                <div class="product-item">
                    <img class="imagem-produto" src="imagens/gasp13.png" alt="Gás P13">
                    <label for="gas">Gás P13:</label>
                </div>
                <div>
                <input type="number" id="gas" name="gas" value="0" min="0">
                </div>
            </div>
            <div class="product-item" id="img">
                <div class="product-item">
                    <img class="imagem-produto" src="imagens/aguaype.png" alt="Água Ypê">
                    <label for="agua_ype">Água Ypê:</label>
                </div>
                <div>
                    <input type="number" id="agua_ype" name="agua_ype" value="0" min="0">
                </div>
            </div>
            <div class="product-item" id="img">
                <div class="product-item">
                    <img class="imagem-produto" src="imagens/aguaNativa.png" alt="Água Nativa">
                    <label for="agua_nativa">Água Nativa:</label>
                </div>
                <div>
                    <input type="number" id="agua_nativa" name="agua_nativa" value="0" min="0">
                </div>
            </div>
        </div>

        <div class="payment-options">
            <h3>Selecione a Forma de Pagamento:</h3>
            <label><input type="radio" name="payment" value="dinheiro" required>Dinheiro</label>
            <label><input type="radio" name="payment" value="debito" required>Cartão de Débito</label>
            <label><input type="radio" name="payment" value="credito" required>Cartão de Crédito</label>
            <label><input type="radio" name="payment" value="pix" required>Pix</label>
        </div>

        <button id="confirmarPedido">Confirmar Pedido</button>
    </div>
    <script src="js/pedido.js"></script>
</body>

</html>