<?php
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "cadastro";

// Conexão com o banco de dados.
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão.
if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

// Obtém a data a ser filtrada (padrão para o dia atual se não for fornecida)
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');

// Query para buscar todos os pedidos com detalhes do cliente e dos produtos.
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
        DATE(p.data_pedido) = ?
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

        // Format the data_pedido for display as date/time
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
    <title>Lista de Pedidos</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Estilos existentes */
        .status-select {
            border-radius: 5px;
            color: white;
            padding: 5px 10px;
            border: none;
            background-color: #f0f0f0;
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

        .status-select[value="Pendente"] { background-color: #FFC107; color: white; }
        .status-select[value="Entrega"] { background-color: #87CEEB; color: white; }
        .status-select[value="Concluido"] { background-color: #28a745; color: white; }
        .status-select[value="Cancelado"] { background-color: #dc3545; color: white; }
        .status-select[value="Aceito"] { background-color: #6c757d; color: white; }

        .status-select:hover { background-color: white !important; color: black !important; }

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
    </style>
</head>
<body>
    <div class="background"></div>
    <div class="card tamanho-tabela">
        <h1 class="titulo-tabela">Lista de Pedidos</h1>
         <a href="menu.html" class="voltar-menu-btn-right">Voltar ao Menu</a>

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
                <tbody>
                    <?php if (!empty($pedidos)): ?>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td><?php echo $pedido['data_pedido_display']; ?></td>
                                <td>
                                    <select class="status-select" data-id-pedido="<?php echo htmlspecialchars($pedido['id_pedido']); ?>">
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
                            <td colspan="7" style="text-align: center;">Nenhum pedido encontrado para a data selecionada.</td>
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


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelects = document.querySelectorAll('.status-select');
            const printButtons = document.querySelectorAll('.print-button');
            const editAddressButtons = document.querySelectorAll('.edit-address-button');
            const editProductButtons = document.querySelectorAll('.edit-product-button');

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

            let allProducts = [];

            function applyStatusColor(element) {
                const status = element.value;
                element.style.backgroundColor = '';
                element.style.color = 'white';

                if (status === 'Pendente') {
                    element.style.backgroundColor = '#FFC107';
                } else if (status === 'Entrega') {
                    element.style.backgroundColor = '#87CEEB';
                } else if (status === 'Concluido') {
                    element.style.backgroundColor = '#28a745';
                } else if (status === 'Cancelado') {
                    element.style.backgroundColor = '#dc3545';
                } else if (status === 'Aceito') {
                    element.style.backgroundColor = '#6c757d';
                }
            }

            function fetchAndRenderPedidos() {
                window.location.reload();
            }

            function attachEventListeners() {
                document.querySelectorAll('.status-select').forEach(select => {
                    applyStatusColor(select);
                    select.removeEventListener('change', handleStatusChange);
                    select.addEventListener('change', handleStatusChange);
                });

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

            function handleStatusChange() {
                const idPedido = this.dataset.idPedido;
                const novoStatus = this.value;
                applyStatusColor(this);

                fetch('atualizar_status_pedido.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id_pedido=${idPedido}&status=${novoStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Status do pedido atualizado com sucesso!');
                    } else {
                        alert('Erro ao atualizar o status do pedido: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição:', error);
                    alert('Ocorreu um erro na comunicação com o servidor. Tente novamente.');
                });
            }

            function handlePrintClick(event) {
                event.stopPropagation();
                const idPedido = this.dataset.idPedido;
                alert('Função de impressão para o Pedido ID: ' + idPedido + ' será implementada aqui.');
            }

            function handleEditAddressClick(event) {
                event.stopPropagation();
                const telefoneCliente = this.dataset.telefone;
                openEditAddressModal(telefoneCliente);
            }

            function handleEditProductClick(event) {
                event.stopPropagation();
                const idPedido = this.dataset.idPedido;
                const idCliente = this.dataset.idCliente;
                openEditProductModal(idPedido, idCliente);
            }

            function openEditAddressModal(telefone) {
                modalMessageDiv.textContent = '';
                fetch(`get_cliente_endereco.php?telefone=${encodeURIComponent(telefone)}`)
                    .then(response => {
                        console.log('Raw response from get_cliente_endereco.php:', response);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Parsed data from get_cliente_endereco.php:', data);
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
                            alert('Erro ao carregar dados do cliente: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao buscar dados do cliente para edição:', error);
                        alert('Ocorreu um erro ao carregar os dados para edição. Verifique o console para mais detalhes.');
                    });
            }

            function closeEditAddressModal() {
                editAddressModal.style.display = 'none';
                modalEditAddressForm.reset();
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
                            throw new Error(`HTTP error! status: ${response.status}`);
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
                            throw new Error(`HTTP error! status: ${response.status}`);
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

            closeModalSpan.addEventListener('click', closeEditAddressModal);
            cancelModalBtn.addEventListener('click', closeEditAddressModal);
            window.addEventListener('click', function(event) {
                if (event.target == editAddressModal) {
                    closeEditAddressModal();
                }
            });

            modalEditAddressForm.addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent default form submission

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
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        modalProductMessageDiv.textContent = 'Produto adicionado/atualizado com sucesso no pedido!';
                        modalProductMessageDiv.style.color = 'green';
                        openEditProductModal(idPedido, idCliente);
                        fetchAndRenderPedidos(); // Recarrega a lista principal para atualizar o total
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
                window.location.href = `lista_pedidos.php?filter_date=${selectedDate}`;
            };


            // Anexar event listeners inicialmente.
            attachEventListeners();
        });
    </script>
</body>
</html>