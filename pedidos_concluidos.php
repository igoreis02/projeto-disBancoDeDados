<?php
//$servername = "myshared2380";
//$username = "cadastrosouza";
//$password = "Souza@7498"; 
//$dbname = "cadastrosouza";

$servername = "localhost"; // Use "localhost" se estiver rodando localmente ou o nome do servidor se for remoto
$username = "root";
$password = ""; // Assuming no password for the user "cadastrosouza" based on the provided data.
$dbname = "cadastrosouza";
// Conexão com o banco de dados.
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão.
if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

// Obtém a data a ser filtrada (padrão para o dia atual se não for fornecida)
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');

// Query para buscar todos os pedidos com detalhes do cliente e dos produtos, SOMENTE CONCLUÍDOS.
$sql = "
    SELECT
        p.id_pedido,
        p.status_pedido,
        c.nome AS cliente_nome,
        c.telefone AS cliente_telefone,
        c.endereco,
        c.quadra,
        c.lote,
        c.setor,
        c.complemento,
        c.cidade,
        p.valor_total,
        p.forma_pagamento,
        p.data_pedido,
        p.valor_pago,
        GROUP_CONCAT(CONCAT(ip.quantidade, 'x ', prod.nome, ' (R$ ', FORMAT(ip.preco_unitario, 2, 'pt_BR'), ')') SEPARATOR '<br>') AS produtos_detalhes,
        p.id_cliente
    FROM
        pedidos p
    JOIN
        clientes c ON p.id_cliente = c.id
    LEFT JOIN
        itens_pedido ip ON p.id_pedido = ip.id_pedido
    LEFT JOIN
        produtos prod ON ip.id_produto = prod.id_produtos
    WHERE
        DATE(p.data_pedido) = ? AND p.status_pedido = 'Concluido'
    GROUP BY
        p.id_pedido, p.status_pedido, c.nome, c.telefone, c.endereco, c.quadra, c.lote, c.setor, c.complemento, c.cidade, p.valor_total, p.forma_pagamento, p.data_pedido, p.valor_pago, p.id_cliente
    ORDER BY
        p.data_pedido DESC;
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $filter_date);
$stmt->execute();
$result = $stmt->get_result();

$pedidos = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $endereco_completo = ucwords(htmlspecialchars($row['endereco']) . ', Qd ' . htmlspecialchars($row['quadra']) . ', Lt ' . htmlspecialchars($row['lote']));

        if (!empty($row['setor'])) {
            $endereco_completo .= '<br>Setor: ' . ucwords(htmlspecialchars($row['setor']));
        }
        if (!empty($row['complemento'])) {
            $endereco_completo .= '<br>Complemento: ' . ucwords(htmlspecialchars($row['complemento']));
        }
        $endereco_completo .= '<br>' . ucwords(htmlspecialchars($row['cidade']));

        $row['endereco_completo'] = $endereco_completo;

        $forma_pagamento_display = ucwords($row['forma_pagamento']);
        if ($row['forma_pagamento'] === 'dinheiro' && $row['valor_pago'] !== null) {
            $valor_pago_formatado = number_format($row['valor_pago'], 2, ',', '.');
            $troco = $row['valor_pago'] - $row['valor_total'];
            $troco_formatado = number_format($troco, 2, ',', '.');

            $forma_pagamento_display .= " (R$ {$valor_pago_formatado})";
            $forma_pagamento_display .= "<br>Troco: R$ {$troco_formatado}";
        }
        $row['forma_pagamento_display'] = $forma_pagamento_display;

        // Formata a data_pedido para exibição de data/hora
        $row['data_pedido_display'] = htmlspecialchars(date('d/m/Y H:i', strtotime($row['data_pedido'])));

        $pedidos[] = $row;
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Concluídos</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
      /* Estilos existentes */
        .status-select {
            border-radius: 5px;
            color: white;
            padding: 5px 10px;
            border: none;
            background-color: #f0f0f0; /* Cor de fundo padrão, será sobrescrita pelo JS ou CSS de atributo */
            cursor: pointer;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23000000%22%20d%3D%22M287%2C197.915c-3.6%2C3.6-7.8%2C5.4-12.4%2C5.4s-8.8-1.8-12.4-5.4L146.2%2C82.815L30.2%2C197.915c-3.6%2C3.6-7.8%2C5.4-12.4%2C5.4s-8.8-1.8-12.4-5.4c-7.2-7.2-7.2-18.9%2C0-26.1l128.6-128.6c3.6-3.6%2C7.8-5.4%2C12.4-5.4s8.8%2C1.8%2C12.4%2C5.4l128.6%2C128.6C294.2%2C179.015%2C294.2%2C190.715%2C287%2C197.915z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 12px;
            padding-right: 25px;
        }

        /* REMOVIDAS as regras CSS com [value="..."] para evitar conflitos */
        /* .status-select[value="Pendente"] { background-color: #FFC107; color: white; } */
        /* .status-select[value="Entrega"] { background-color: #87CEEB; color: white; } */
        /* .status-select[value="Concluido"] { background-color: #28a745; color: white; } */
        /* .status-select[value="Cancelado"] { background-color: #dc3545; color: white; } */
        /* .status-select[value="Aceito"] { background-color: #6c757d; color: white; } */

        /* Novas regras CSS para as classes adicionadas pelo JavaScript (mantidas) */
        .status-select.status-Pendente { background-color: #FFC107; }
        .status-select.status-Aceito { background-color: #6c757d; }
        .status-select.status-Entrega { background-color: #87CEEB; }
        .status-select.status-Concluido { background-color: #28a745; }
        .status-select.status-Cancelado { background-color: #dc3545; }


        .status-select:hover { background-color: white !important; color: black !important; }

        /* As opções dentro do select também podem ter suas cores, mas o estilo principal é no select */
        .status-select option[value="Pendente"] { background-color: #FFC107; color: white; }
        .status-select option[value="Entrega"] { background-color: #87CEEB; color: white; }
        .status-select option[value="Concluido"] { background-color: #28a745; color: white; }
        .status-select option[value="Cancelado"] { background-color: #dc3545; color: white; }
        .status-select option[value="Aceito"] { background-color: #6c757d; color: white; }

        .action-button {
            border-radius: 5px;
            color: white;
            padding: 8px 12px;
            border: none;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s ease, transform 0.3s ease;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            white-space: nowrap;
            min-width: 80px;
            text-align: center;
        }

        .print-button {
            background-color: rgba(40, 167, 69, 0.7);
            right: 5px;
        }

        .edit-address-button {
            background-color: rgba(76, 132, 121, 0.7);
            right: 5px;
        }

        .edit-product-button {
            background-color: rgba(235, 159, 37, 0.7);
            right: 5px;
            margin-top: 5px;
        }

        #pedidosTable tbody tr:hover .action-button {
            opacity: 1;
        }

        #pedidosTable tbody td.cliente-info-cell,
        #pedidosTable tbody td.endereco-cell,
        #pedidosTable tbody td.produtos-cell {
            position: relative;
            padding-right: 90px;
        }

        .cliente-text-content,
        .endereco-text-content,
        .produtos-text-content {
            position: relative;
            z-index: 5;
        }

        #pedidosTable td {
            padding: 8px;
        }

        .card::before {
            content: none;
        }

        .card.tamanho-tabela {
            margin-top: 20px;
            margin-left: auto;
            margin-right: auto;
            padding-top: 50px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 10px;
            position: relative;
            box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2), 0 6px 20px 0 rgba(0,0,0,0.19);
            animation-name: animatetop;
            animation-duration: 0.4s
        }

        @keyframes animatetop {
            from {top: -300px; opacity: 0}
            to {top: 0; opacity: 1}
        }

        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close-modal:hover,
        .close-modal:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: var(--cor-principal);
        }

        .modal-form input[type="text"],
        .modal-form input[type="number"],
        .modal-form select {
            width: calc(100% - 20px);
            padding: 8px 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .modal-form button {
            background-color: var(--cor-principal);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }

        .modal-form button:hover {
            background-color: var(--cor-secundaria);
        }

        #editProductModal .modal-content {
            max-width: 600px;
        }
        #currentOrderItems {
            margin-bottom: 20px;
            border: 1px solid #eee;
            padding: 10px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        #currentOrderItems div {
            padding: 5px 0;
            border-bottom: 1px dashed #eee;
        }
        #currentOrderItems div:last-child {
            border-bottom: none;
        }
        .tamanho-tabela{
            width: 80%;
            border-collapse: collapse;
        }
        /* Novas regras de estilo para o filtro de data */
        .date-filter-container {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: flex-end; /* Alinha à direita */
            padding-right: 10px; /* Espaço do lado direito */
        }

        .date-filter-container label {
            font-weight: bold;
            color: var(--cor-principal);
        }

        .date-filter-container input[type="date"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }

        .date-filter-container button {
            background-color: var(--cor-principal);
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }

        .date-filter-container button:hover {
            background-color: var(--cor-secundaria);
        }
        .voltar-menu-btn-right {
            background-color: var(--cor-titulo);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-left: auto;
            margin-right: 10px;
            align-self: flex-end;/* Aligns itself to the end of the flex container */
            margin-bottom: 20px; /* Space below the button */
        }
        .voltar-menu-btn-right:hover {
            opacity: 0.9;
        }
         .card.tamanho-tabela .header-buttons {
            width: 100%;
            display: flex;
            justify-content: space-between; /* Distribute items with space between them */
            align-items: center;
            margin-bottom: 20px;
        }

        /* Estilos para os botões do modal de confirmação */
        .modal-form-button {
            background-color: var(--cor-principal);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 5px; /* Ajuste a margem para espaçamento */
        }

        .modal-form-button:hover {
            background-color: var(--cor-secundaria);
        }

        .modal-form-button.cancel-modal-btn {
            background-color: #dc3545; /* Vermelho para cancelar */
        }

        .modal-form-button.cancel-modal-btn:hover {
            background-color: #c82333;
        }

        /* NEW STYLE: Button for "Pedidos Concluídos" */
        .btn-pedidos-concluidos {
            background-color: #007bff; /* Example blue color */
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: auto; /* Aligns to the left */
            margin-bottom: 20px;
        }

        .btn-pedidos-concluidos:hover {
            opacity: 0.9;
        }

    </style>
</head>
<body>
    <div class="background"></div>
    <div class="card tamanho-tabela">
        <h1 class="titulo-tabela">Pedidos Concluídos</h1>
        <div class="header-buttons">
            <a href="lista_pedidos.php" class="btn-pedidos-concluidos">Ver Pedidos Ativos</a>
            <a href="menu.html" class="voltar-menu-btn-right">Voltar ao Menu</a>
        </div>

        <div class="date-filter-container">
            <label for="filterDate">Filtrar por Data:</label>
            <input type="date" id="filterDate" value="<?php echo htmlspecialchars($filter_date); ?>">
            <button onclick="applyDateFilter()">Aplicar Filtro</button>
        </div>

        <div class="table-container">
            <table id="pedidosTable">
                <thead>
                    <tr>
                        <th>Data Pedido/Hora</th>
                        <th>Status</th>
                        <th>Cliente e Telefone</th>
                        <th>Endereço</th>
                        <th>Produtos</th>
                        <th>Valor Total</th>
                        <th>Forma Pagamento</th>
                        </tr>
                </thead>
                <tbody id="pedidosTableBody">
                    <?php if (!empty($pedidos)): ?>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td><?php echo $pedido['data_pedido_display']; ?></td>
                                <td>
                                    <select class="status-select" data-id-pedido="<?php echo htmlspecialchars($pedido['id_pedido']); ?>" value="<?php echo htmlspecialchars($pedido['status_pedido']); ?>" data-initial-status="<?php echo htmlspecialchars($pedido['status_pedido']); ?>" <?php echo ($pedido['status_pedido'] == 'Concluido' || $pedido['status_pedido'] == 'Cancelado') ? 'disabled' : ''; ?>>
                                        <option value="Pendente" <?php echo ($pedido['status_pedido'] == 'Pendente') ? 'selected' : ''; ?>>Pendente</option>
                                        <option value="Aceito" <?php echo ($pedido['status_pedido'] == 'Aceito') ? 'selected' : ''; ?>>Aceito</option>
                                        <option value="Entrega" <?php echo ($pedido['status_pedido'] == 'Entrega') ? 'selected' : ''; ?>>Entrega</option>
                                        <option value="Concluido" <?php echo ($pedido['status_pedido'] == 'Concluido') ? 'selected' : ''; ?>>Concluído</option>
                                        <option value="Cancelado" <?php echo ($pedido['status_pedido'] == 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                                    </select>
                                </td>
                                <td class="cliente-info-cell">
                                    <div class="cliente-text-content">
                                        <?php echo htmlspecialchars(ucwords($pedido['cliente_nome'])); ?><br>
                                        <?php echo htmlspecialchars($pedido['cliente_telefone']); ?>
                                    </div>
                                    <button class="print-button action-button" data-id-pedido="<?php echo htmlspecialchars($pedido['id_pedido']); ?>">Imprimir</button>
                                </td>
                                <td class="endereco-cell">
                                    <div class="endereco-text-content">
                                        <?php echo $pedido['endereco_completo']; ?>
                                    </div>
                                    <button class="edit-address-button action-button" data-telefone="<?php echo htmlspecialchars($pedido['cliente_telefone']); ?>">Editar Endereço</button>
                                </td>
                                <td class="produtos-cell"> <div class="produtos-text-content">
                                        <?php echo $pedido['produtos_detalhes']; ?>
                                    </div>
                                    <button class="edit-product-button action-button" data-id-pedido="<?php echo htmlspecialchars($pedido['id_pedido']); ?>" data-id-cliente="<?php echo htmlspecialchars($pedido['id_cliente']); ?>">Editar Produto</button>
                                </td>
                                <td>R$ <?php echo htmlspecialchars(number_format($pedido['valor_total'], 2, ',', '.')); ?></td>
                                <td><?php echo $pedido['forma_pagamento_display']; ?></td>
                                </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">Nenhum pedido concluído encontrado para a data selecionada.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
    <div class="footer">
        <p>&copy; 2025 Souza Gás. Todos os direitos reservados.</p>
    </div>

    <div id="editAddressModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Editar Endereço do Cliente</h2>
            <form id="modalEditAddressForm" class="modal-form">
                <input type="hidden" id="modalTelefone" name="telefone">
                <label for="modalNome">Nome:</label>
                <input type="text" id="modalNome" name="nome" readonly>

                <label for="modalEndereco">Endereço:</label>
                <input type="text" id="modalEndereco" name="endereco" required>

                <label for="modalQuadra">Quadra:</label>
                <input type="text" id="modalQuadra" name="quadra" required>

                <label for="modalLote">Lote:</label>
                <input type="text" id="modalLote" name="lote" required>

                <label for="modalSetor">Setor:</label>
                <input type="text" id="modalSetor" name="setor" required>

                <label for="modalComplemento">Complemento:</label>
                <input type="text" id="modalComplemento" name="complemento">

                <label for="modalCidade">Cidade:</label>
                <input type="text" id="modalCidade" name="cidade" required>

                <button type="submit">Salvar Alterações</button>
                <button type="button" class="cancel-modal-btn">Cancelar</button>
            </form>
            <div id="modal-message" style="color: red; margin-top: 10px;"></div>
        </div>
    </div>

    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <span class="close-modal close-product-modal">&times;</span>
            <h2>Editar Produtos do Pedido <span id="modalProductOrderId"></span></h2>
            <form id="modalEditProductForm" class="modal-form">
                <input type="hidden" id="modalProductIdPedido" name="id_pedido">
                <input type="hidden" id="modalProductClienteId" name="id_cliente">

                <h3>Produtos Atuais do Pedido:</h3>
                <div id="currentOrderItems">
                    Nenhum produto no pedido.
                </div>

                <h3>Adicionar/Atualizar Produto:</h3>
                <label for="selectProduct">Produto:</label>
                <select id="selectProduct" name="id_produto" required>
                    <option value="">Selecione um produto</option>
                    </select>

                <label for="productQuantity">Quantidade:</label>
                <input type="number" id="productQuantity" name="quantidade" min="1" value="1" required>

                <button type="button" id="addProductToOrder">Adicionar/Atualizar Produto no Pedido</button>
                <button type="submit">Finalizar Edição de Produtos</button>
                <button type="button" class="cancel-modal-btn close-product-modal">Cancelar</button>
            </form>
            <div id="modal-product-message" style="color: red; margin-top: 10px;"></div>
        </div>
    </div>

    <div id="confirmCompleteModal" class="modal">
        <div class="modal-content">
            <span class="close-modal close-complete-modal">&times;</span>
            <h2>Concluir Pedido</h2>
            <p>Tem certeza que deseja concluir este pedido? Uma vez concluído, o status não poderá ser alterado.</p>
            <div style="display: flex; justify-content: space-around; margin-top: 20px;">
                <button id="confirmCompleteButton" class="modal-form-button">Concluir</button>
                <button id="cancelCompleteButton" class="modal-form-button cancel-modal-btn">Cancelar</button>
            </div>
        </div>
    </div>

    <div id="confirmCancelModal" class="modal">
        <div class="modal-content">
            <span class="close-modal close-cancel-modal">&times;</span>
            <h2>Cancelar Pedido</h2>
            <p>Deseja cancelar este pedido? Uma vez cancelado, o status não poderá ser alterado.</p>
            <div style="display: flex; justify-content: space-around; margin-top: 20px;">
                <button id="confirmCancelButton" class="modal-form-button cancel-modal-btn">Cancelar</button>
                <button id="backCancelButton" class="modal-form-button">Voltar</button>
            </div>
        </div>
    </div>

    <audio id="pendingOrderSound" loop>
        <source src="audio/novo_pedido.mp3" type="audio/mpeg">
        Seu navegador não suporta o elemento de áudio.
    </audio>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos do Modal de Edição de Endereço
            const editAddressModal = document.getElementById('editAddressModal');
            const closeModalSpan = document.querySelector('.close-modal');
            const cancelModalBtn = document.querySelector('.cancel-modal-btn');
            const modalEditAddressForm = document.getElementById('modalEditAddressForm');
            const modalMessageDiv = document.getElementById('modal-message');

            const modalTelefoneInput = document.getElementById('modalTelefone');
            const modalNomeInput = document.getElementById('modalNome');
            const modalEnderecoInput = document.getElementById('modalEndereco');
            const modalQuadraInput = document.getElementById('modalQuadra');
            const modalLoteInput = document.getElementById('modalLote');
            const modalSetorInput = document.getElementById('modalSetor');
            const modalComplementoInput = document.getElementById('modalComplemento');
            const modalCidadeInput = document.getElementById('modalCidade');

            // Elementos do Modal de Edição de Produto
            const editProductModal = document.getElementById('editProductModal');
            const closeProductModalSpan = document.querySelector('.close-product-modal');
            const cancelProductModalBtn = document.querySelector('#editProductModal .cancel-modal-btn');
            const modalEditProductForm = document.getElementById('modalEditProductForm');
            const modalProductMessageDiv = document.getElementById('modal-product-message');
            const modalProductIdPedidoInput = document.getElementById('modalProductIdPedido');
            const modalProductClienteIdInput = document.getElementById('modalProductClienteId');
            const modalProductOrderIdSpan = document.getElementById('modalProductOrderId');
            const selectProductDropdown = document.getElementById('selectProduct');
            const productQuantityInput = document.getElementById('productQuantity');
            const addProductToOrderButton = document.getElementById('addProductToOrder');
            const currentOrderItemsDiv = document.getElementById('currentOrderItems');

            // Elementos para o Modal de Confirmação de Conclusão
            const confirmCompleteModal = document.getElementById('confirmCompleteModal');
            const closeCompleteModalSpan = document.querySelector('.close-complete-modal');
            const confirmCompleteButton = document.getElementById('confirmCompleteButton');
            const cancelCompleteButton = document.getElementById('cancelCompleteButton');

            // NOVO: Elementos para o Modal de Confirmação de Cancelamento
            const confirmCancelModal = document.getElementById('confirmCancelModal');
            const closeCancelModalSpan = document.querySelector('.close-cancel-modal');
            const confirmCancelButton = document.getElementById('confirmCancelButton');
            const backCancelButton = document.getElementById('backCancelButton');

            // Audio element for pending orders
            const pendingOrderSound = document.getElementById('pendingOrderSound');
            let soundInterval;

            let allProducts = [];
            let currentChangingPedidoId = null;
            let previousPedidoStatus = null; // Para armazenar o status antes de selecionar "Concluído" ou "Cancelado"

            // Função para verificar pedidos pendentes e tocar o som
            function checkPendingOrdersAndPlaySound() {
                const pendingOrders = document.querySelectorAll('.status-select[value="Pendente"]');
                if (pendingOrders.length > 0) {
                    if (pendingOrderSound.paused) {
                        pendingOrderSound.play().catch(error => {
                            console.log('Erro ao tentar tocar o áudio (provavelmente autoplay bloqueado):', error);
                            // Pode exibir uma mensagem ao usuário para interagir com a página
                            // ou adicionar um botão de "Ativar Sons"
                        });
                    }
                } else {
                    if (!pendingOrderSound.paused) {
                        pendingOrderSound.pause();
                        pendingOrderSound.currentTime = 0; // Reinicia o áudio
                    }
                }
            }

            // Inicia a verificação a cada 5 segundos (você pode ajustar o intervalo)
            soundInterval = setInterval(checkPendingOrdersAndPlaySound, 5000); // Check every 5 seconds

            // Função para abrir o modal de confirmação de conclusão
            function openConfirmCompleteModal() {
                confirmCompleteModal.style.display = 'flex';
            }

            // Função para fechar o modal de confirmação de conclusão
            function closeConfirmCompleteModal() {
                confirmCompleteModal.style.display = 'none';
            }

            // NOVO: Função para abrir o modal de confirmação de cancelamento
            function openConfirmCancelModal() {
                confirmCancelModal.style.display = 'flex';
            }

            // NOVO: Função para fechar o modal de confirmação de cancelamento
            function closeConfirmCancelModal() {
                confirmCancelModal.style.display = 'none';
            }


            // Função para aplicar a cor do status
            function applyStatusColor(element) {
                const status = element.value;
                // Remove quaisquer classes de cor anteriores para evitar conflitos
                element.classList.remove('status-Pendente', 'status-Aceito', 'status-Entrega', 'status-Concluido', 'status-Cancelado');

                // Adiciona a classe correspondente ao status atual
                if (status === 'Pendente') {
                    element.classList.add('status-Pendente');
                } else if (status === 'Aceito') {
                    element.classList.add('status-Aceito');
                } else if (status === 'Entrega') {
                    element.classList.add('status-Entrega');
                } else if (status === 'Concluido') {
                    element.classList.add('status-Concluido');
                } else if (status === 'Cancelado') {
                    element.classList.add('status-Cancelado');
                }
                // Garante que a cor do texto seja branca para todos os status coloridos
                element.style.color = 'white';
            }

            // Função para recarregar os pedidos (recarrega a página para simplicidade)
            function fetchAndRenderPedidos() {
                window.location.reload();
            }

            // Função para anexar event listeners aos botões
            function attachEventListeners() {
                document.querySelectorAll('.print-button').forEach(button => {
                    button.removeEventListener('click', handlePrintClick);
                    button.addEventListener('click', handlePrintClick);
                });

                document.querySelectorAll('.edit-address-button').forEach(button => {
                    button.removeEventListener('click', handleEditAddressClick);
                    button.addEventListener('click', handleEditAddressClick);
                });

                document.querySelectorAll('.edit-product-button').forEach(button => {
                    button.removeEventListener('click', handleEditProductClick);
                    button.addEventListener('click', handleEditProductClick);
                });
            }

            // Handler para o botão de imprimir
            function handlePrintClick(event) {
                event.stopPropagation();
                const idPedido = this.dataset.idPedido;
                window.open(`imprimir_pedido.php?id_pedido=${idPedido}`, '_blank');
            }

            // Handlers para edição de endereço
            function handleEditAddressClick(event) {
                event.stopPropagation();
                const telefoneCliente = this.dataset.telefone;
                openEditAddressModal(telefoneCliente);
            }

            function openEditAddressModal(telefone) {
                modalMessageDiv.textContent = '';
                fetch(`get_cliente_endereco.php?telefone=${encodeURIComponent(telefone)}`)
                    .then(response => {
                        console.log('Resposta bruta de get_cliente_endereco.php:', response);
                        if (!response.ok) {
                            throw new Error(`Erro HTTP! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Dados processados de get_cliente_endereco.php:', data);
                        if (data.success) {
                            const cliente = data.data;
                            modalTelefoneInput.value = telefone;
                            modalNomeInput.value = ucwords(cliente.nome);
                            modalEnderecoInput.value = ucwords(cliente.endereco);
                            modalQuadraInput.value = ucwords(cliente.quadra);
                            modalLoteInput.value = ucwords(cliente.lote);
                            modalSetorInput.value = ucwords(cliente.setor);
                            modalComplementoInput.value = ucwords(cliente.complemento);
                            modalCidadeInput.value = ucwords(cliente.cidade);
                            editAddressModal.style.display = 'flex';
                        } else {
                            console.error('Erro ao carregar dados do cliente: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao buscar dados do cliente para edição:', error);
                        modalMessageDiv.textContent = 'Ocorreu um erro ao carregar os dados para edição. Verifique o console para mais detalhes.';
                        modalMessageDiv.style.color = 'red';
                    });
            }

            function closeEditAddressModal() {
                editAddressModal.style.display = 'none';
                modalEditAddressForm.reset();
            }

            // Handlers para edição de produto
            function handleEditProductClick(event) {
                event.stopPropagation();
                const idPedido = this.dataset.idPedido;
                const idCliente = this.dataset.idCliente;
                openEditProductModal(idPedido, idCliente);
            }

            function openEditProductModal(idPedido, idCliente) {
                modalProductMessageDiv.textContent = '';
                modalProductIdPedidoInput.value = idPedido;
                modalProductClienteIdInput.value = idCliente;
                modalProductOrderIdSpan.textContent = idPedido;

                selectProductDropdown.innerHTML = '<option value="">Selecione um produto</option>';
                fetch('get_all_products.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Erro HTTP! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(products => {
                        allProducts = products;
                        products.forEach(product => {
                            const option = document.createElement('option');
                            option.value = product.id_produtos;
                            option.textContent = `${ucwords(product.nome)} (R$ ${parseFloat(product.preco).toFixed(2).replace('.', ',')})`;
                            selectProductDropdown.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Erro ao buscar produtos disponíveis:', error);
                        modalProductMessageDiv.textContent = 'Erro ao carregar produtos disponíveis.';
                        modalProductMessageDiv.style.color = 'red';
                    });

                currentOrderItemsDiv.innerHTML = 'Carregando produtos do pedido...';
                fetch(`get_order_items.php?id_pedido=${encodeURIComponent(idPedido)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Erro HTTP! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(items => {
                        currentOrderItemsDiv.innerHTML = '';
                        if (items.length > 0) {
                            items.forEach(item => {
                                const itemDiv = document.createElement('div');
                                itemDiv.innerHTML = `${item.quantidade}x ${ucwords(item.nome_produto)} (R$ ${parseFloat(item.preco_unitario).toFixed(2).replace('.', ',')})`;
                                currentOrderItemsDiv.appendChild(itemDiv);
                            });
                        } else {
                            currentOrderItemsDiv.textContent = 'Nenhum produto neste pedido.';
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao buscar itens do pedido:', error);
                        currentOrderItemsDiv.textContent = 'Erro ao carregar produtos do pedido.';
                    });


                editProductModal.style.display = 'flex';
            }

            function closeEditProductModal() {
                editProductModal.style.display = 'none';
                modalEditProductForm.reset();
                currentOrderItemsDiv.innerHTML = '';
            }

            // Função auxiliar para capitalizar a primeira letra de cada palavra.
            function ucwords(str) {
                if (!str) return '';
                return str.toLowerCase().replace(/(^|\s)\S/g, function(firstChar) {
                    return firstChar.toUpperCase();
                });
            }

            // Função para aplicar o filtro de data
            window.applyDateFilter = function() {
                const selectedDate = document.getElementById('filterDate').value;
                window.location.href = `pedidos_concluidos.php?filter_date=${selectedDate}`;
            };


            // --- Event Listeners Principais ---

            // Event listeners para fechar modais
            closeModalSpan.addEventListener('click', closeEditAddressModal);
            cancelModalBtn.addEventListener('click', closeEditAddressModal);
            window.addEventListener('click', function(event) {
                if (event.target == editAddressModal) {
                    closeEditAddressModal();
                }
            });

            closeProductModalSpan.addEventListener('click', closeEditProductModal);
            cancelProductModalBtn.addEventListener('click', closeEditProductModal);
            window.addEventListener('click', function(event) {
                if (event.target == editProductModal) {
                    closeEditProductModal();
                }
            });

            // Event listeners para fechar o modal de confirmação de conclusão
            closeCompleteModalSpan.addEventListener('click', closeConfirmCompleteModal);
            window.addEventListener('click', function(event) {
                if (event.target == confirmCompleteModal) {
                    closeConfirmCompleteModal();
                }
            });

            // NOVO: Event listeners para fechar o modal de confirmação de cancelamento
            closeCancelModalSpan.addEventListener('click', closeConfirmCancelModal);
            window.addEventListener('click', function(event) {
                if (event.target == confirmCancelModal) {
                    closeConfirmCancelModal();
                }
            });


            // Lógica para submissão do formulário de edição de endereço
            modalEditAddressForm.addEventListener('submit', function(event) {
                event.preventDefault();

                const formData = new FormData(modalEditAddressForm);

                fetch('update_endereco_cliente.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modalMessageDiv.textContent = data.message;
                        modalMessageDiv.style.color = 'green';
                        setTimeout(() => {
                            closeEditAddressModal();
                            fetchAndRenderPedidos();
                        }, 1500);
                    } else {
                        modalMessageDiv.textContent = 'Erro: ' + data.message;
                        modalMessageDiv.style.color = 'red';
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição de atualização de endereço:', error);
                    modalMessageDiv.textContent = 'Ocorreu um erro na comunicação com o servidor. Tente novamente.';
                    modalMessageDiv.style.color = 'red';
                });
            });

            // Lógica para adicionar/atualizar produto no pedido (dentro do modal de edição de produto)
            addProductToOrderButton.addEventListener('click', function() {
                const selectedProductId = selectProductDropdown.value;
                const quantity = parseInt(productQuantityInput.value);
                const idPedido = modalProductIdPedidoInput.value;
                const idCliente = modalProductClienteIdInput.value;

                if (!selectedProductId || isNaN(quantity) || quantity <= 0) {
                    modalProductMessageDiv.textContent = 'Por favor, selecione um produto e uma quantidade válida.';
                    modalProductMessageDiv.style.color = 'red';
                    return;
                }

                const selectedProduct = allProducts.find(p => p.id_produtos == selectedProductId);
                if (!selectedProduct) {
                    modalProductMessageDiv.textContent = 'Produto selecionado inválido.';
                    modalProductMessageDiv.style.color = 'red';
                    return;
                }

                fetch('update_order_items.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id_pedido: idPedido,
                        id_cliente: idCliente,
                        product_id: selectedProductId,
                        quantity: quantity,
                        price_unit: selectedProduct.preco
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erro HTTP! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        modalProductMessageDiv.textContent = 'Produto adicionado/atualizado com sucesso no pedido!';
                        modalProductMessageDiv.style.color = 'green';
                        openEditProductModal(idPedido, idCliente); // Recarrega os itens do modal
                        fetchAndRenderPedidos(); // Recarrega a lista principal para atualizar o total e produtos
                    } else {
                        modalProductMessageDiv.textContent = 'Erro ao adicionar/atualizar produto: ' + data.message;
                        modalProductMessageDiv.style.color = 'red';
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição de atualização de produto:', error);
                    modalProductMessageDiv.textContent = 'Ocorreu um erro ao salvar o produto. Verifique o console.';
                    modalProductMessageDiv.style.color = 'red';
                });
            });

            // Lógica para finalizar edição de produtos (o submit do formulário do modal)
            modalEditProductForm.addEventListener('submit', function(event) {
                event.preventDefault();
                closeEditProductModal();
                fetchAndRenderPedidos();
            });


            // Anexar event listeners para botões (imprimir, editar endereço, editar produto)
            attachEventListeners();

            // Event listener ÚNICO para mudança de status do pedido (delegação de eventos)
            document.getElementById('pedidosTableBody').addEventListener('change', function(event) {
                // Verifica se o evento veio de um select de status
                if (event.target.classList.contains('status-select')) {
                    const selectElement = event.target;
                    const idPedido = selectElement.dataset.idPedido;
                    const novoStatus = selectElement.value;

                    // Obtém o status antes da mudança atual
                    const currentStatusBeforeChange = selectElement.dataset.initialStatus;

                    // Se o novo status é 'Concluido', mostra o modal de confirmação de conclusão
                    if (novoStatus === 'Concluido') {
                        currentChangingPedidoId = idPedido;
                        previousPedidoStatus = currentStatusBeforeChange; // Armazena o status anterior
                        openConfirmCompleteModal();
                        // Reverte o select visualmente por enquanto, até ser confirmado
                        selectElement.value = previousPedidoStatus;
                        applyStatusColor(selectElement); // Reaplica a cor para o status anterior
                    } else if (novoStatus === 'Cancelado') {
                        // Se o novo status é 'Cancelado', mostra o modal de confirmação de cancelamento
                        currentChangingPedidoId = idPedido;
                        previousPedidoStatus = currentStatusBeforeChange; // Armazena o status anterior
                        openConfirmCancelModal();
                        // Reverte o select visualmente por enquanto, até ser confirmado
                        selectElement.value = previousPedidoStatus;
                        applyStatusColor(selectElement); // Reaplica a cor para o status anterior
                    } else {
                        // Para outras mudanças de status, procede diretamente
                        applyStatusColor(selectElement); // Aplica a cor imediatamente para feedback visual
                        updatePedidoStatus(idPedido, novoStatus, selectElement);
                    }
                }
            });

            // Event listener para o botão "Concluir" no modal de confirmação de conclusão
            confirmCompleteButton.addEventListener('click', function() {
                if (currentChangingPedidoId) {
                    const selectElement = document.querySelector(`[data-id-pedido="${currentChangingPedidoId}"]`);
                    if (selectElement) {
                        updatePedidoStatus(currentChangingPedidoId, 'Concluido', selectElement);
                    }
                    closeConfirmCompleteModal();
                    currentChangingPedidoId = null;
                    previousPedidoStatus = null;
                }
            });

            // Event listener para o botão "Cancelar" no modal de confirmação de conclusão
            cancelCompleteButton.addEventListener('click', function() {
                if (currentChangingPedidoId) {
                    const selectElement = document.querySelector(`[data-id-pedido="${currentChangingPedidoId}"]`);
                    if (selectElement && previousPedidoStatus) {
                        selectElement.value = previousPedidoStatus; // Reverte para o status anterior
                        applyStatusColor(selectElement); // Reaplica a cor
                    }
                }
                closeConfirmCompleteModal();
                currentChangingPedidoId = null;
                previousPedidoStatus = null;
            });

            // NOVO: Event listener para o botão "Cancelar" no modal de confirmação de cancelamento
            confirmCancelButton.addEventListener('click', function() {
                if (currentChangingPedidoId) {
                    const selectElement = document.querySelector(`[data-id-pedido="${currentChangingPedidoId}"]`);
                    if (selectElement) {
                        updatePedidoStatus(currentChangingPedidoId, 'Cancelado', selectElement);
                    }
                    closeConfirmCancelModal();
                    currentChangingPedidoId = null;
                    previousPedidoStatus = null;
                }
            });

            // NOVO: Event listener para o botão "Voltar" no modal de confirmação de cancelamento
            backCancelButton.addEventListener('click', function() {
                if (currentChangingPedidoId) {
                    const selectElement = document.querySelector(`[data-id-pedido="${currentChangingPedidoId}"]`);
                    if (selectElement && previousPedidoStatus) {
                        selectElement.value = previousPedidoStatus; // Reverte para o status anterior
                        applyStatusColor(selectElement); // Reaplica a cor
                    }
                }
                closeConfirmCancelModal();
                currentChangingPedidoId = null;
                previousPedidoStatus = null;
            });


            // Função auxiliar para lidar com a chamada fetch real de atualização de status
            function updatePedidoStatus(idPedido, status, selectElement) {
                fetch('atualizar_status_pedido.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id_pedido=${idPedido}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Resposta do servidor:', data);
                    if (data.success) {
                        // Atualiza o atributo data-initial-status após a atualização bem-sucedida
                        selectElement.dataset.initialStatus = status;

                        if (status === 'Concluido' || status === 'Cancelado') {
                            selectElement.disabled = true; // Desabilita o select se o status for Concluído ou Cancelado
                            console.log(`Pedido ${status.toLowerCase()} com sucesso! O status não pode mais ser alterado.`);
                        } else {
                            console.log('Status atualizado com sucesso!');
                        }
                        // Recarrega a página para refletir todas as mudanças (ex: estoque)
                        fetchAndRenderPedidos();
                        // Re-check pending orders after status update
                        checkPendingOrdersAndPlaySound();
                    } else {
                        console.error('Erro ao atualizar status: ' + data.message);
                        // Se a atualização falhar, reverte o select para o seu status inicial
                        selectElement.value = selectElement.dataset.initialStatus;
                        applyStatusColor(selectElement);
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição:', error);
                    console.error('Ocorreu um erro ao tentar atualizar o status.');
                    // Reverte em caso de erro de rede também
                    selectElement.value = selectElement.dataset.initialStatus;
                    applyStatusColor(selectElement);
                });
            }

            // Aplica a cor do status a todos os selects existentes no carregamento da página
            document.querySelectorAll('.status-select').forEach(selectElement => {
                applyStatusColor(selectElement);
            });

            // Initial check for pending orders when the page loads
            checkPendingOrdersAndPlaySound();

        }); // Fim do DOMContentLoaded
    </script>
</body>
</html>