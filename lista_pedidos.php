<?php
// Define o fuso horário para garantir a data correta
date_default_timezone_set('America/Sao_Paulo');

// Estes são os valores iniciais para os filtros no carregamento da página.
// A busca real dos dados será feita via AJAX no JavaScript.
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : 'PendenteAceito';

// Conexão com o banco de dados (pode ser útil para outras funções PHP ou se você quiser
// carregar entregadores, etc., de forma inicial no PHP se preferir)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

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
            z-index: 10;
            white-space: nowrap;
            min-width: 80px;
            text-align: center;
        }

        .print-button {
            background-color: rgba(40, 167, 69, 0.7); /* Verde */
            right: 5px;
            top: 50%; /* Posiciona no meio da célula */
            transform: translateY(-50%); /* Ajusta para centralizar verticalmente */
            margin-bottom: 0; /* Garante que não haja margem extra */
        }

        .edit-address-button {
            background-color: rgba(76, 132, 121, 0.7); /* Azul Esverdeado */
            right: 5px;
            top: 50%; /* Posição do topo para o primeiro botão */
            transform: translateY(-50%); /* Ajusta para centralizar verticalmente */
        }

        .edit-product-button {
            background-color: rgba(235, 159, 37, 0.7); /* Laranja */
            right: 5px;
            top: 50%; /* Remove a propriedade top */
            transform: translateY(-50%); /* Ajusta para centralizar verticalmente */
        }

        /* Style for Edit Payment button */
        .edit-payment-button {
            background-color: rgba(0, 123, 255, 0.7); /* Blue */
            right: 5px;
            top: 50%; /* Position in the middle of the cell */
            transform: translateY(-50%); /* Adjust to vertically center */
            margin-bottom: 0; /* Ensure no extra margin */
        }
        /* NEW: Style for Edit Delivery button */
        .edit-delivery-button {
            background-color: rgba(108, 117, 125, 0.7); /* Cinza */
            right: 5px;
            top: 50%; /* Position in the middle of the cell */
            transform: translateY(-50%); /* Adjust to vertically center */
            margin-bottom: 0; /* Ensure no extra margin */
        }


        #pedidosTable tbody td.cliente-info-cell,
        #pedidosTable tbody td.endereco-cell,
        #pedidosTable tbody td.produtos-cell,
        #pedidosTable tbody td.pagamento-cell,
        #pedidosTable tbody td.entregador-cell { /* NEW: Add entregador-cell */
            position: relative;
            padding-right: 120px; /* Mantido para dar espaço aos botões */
        }

        /* Regra para mostrar os botões ao passar o mouse */
        #pedidosTable tbody tr:hover .action-button {
            opacity: 1;
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
        .tamanho-tabela {
            width: 90%; /* Ajustado para 100% para ser responsivo */
            border-collapse: collapse;
            margin-top: 20px;
        }
        /* Regras de estilo para o filtro de data */
        .filter-container { /* Renomeado de date-filter-container para ser mais genérico */
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: flex-end; /* Alinha à direita */
            padding-right: 10px; /* Espaço do lado direito */
        }

        .filter-container label {
            font-weight: bold;
            color: var(--cor-principal);
        }

        .filter-container input[type="date"],
        .filter-container select { /* Adicionado select aqui */
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }

        .filter-container button {
            background-color: var(--cor-principal);
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }

        .filter-container button:hover {
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
            justify-content: flex-end; /* Alterado para alinhar apenas o botão "Voltar ao Menu" à direita */
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
            margin-right: 10px;
        }

        .modal-form-button.cancel {
            background-color: #6c757d; /* Cinza para cancelar */
        }

        .modal-form-button:hover {
            opacity: 0.9;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header h1 {
            color: var(--cor-titulo); /* Supondo que você tenha uma variável CSS --cor-titulo */
            margin: 0;
        }

    </style>
</head>
<body>
    <audio id="pendingOrderSound" src="audio/novo_pedido.mp3" preload="auto" loop></audio>
        <div class="background"></div>
    <div class="card tamanho-tabela">
        <div class="header">
            <h1>Lista de Pedidos</h1>
            <a href="menu.html" class="voltar-menu-btn-right">Voltar ao Menu</a>
        </div>


        <div class="filter-container">
            <label for="filterDate">Filtrar por Data:</label>
            <input type="date" id="filterDate" value="<?php echo htmlspecialchars($filter_date); ?>">

            <label for="filterStatus">Filtrar por Status:</label>
            <select id="filterStatus">
                <option value="PendenteAceito" <?php echo ($filter_status == 'PendenteAceito') ? 'selected' : ''; ?>>Pendentes/Aceitos</option>
                <option value="Todos" <?php echo ($filter_status == 'Todos') ? 'selected' : ''; ?>>Todos</option>
                <option value="Pendente" <?php echo ($filter_status == 'Pendente') ? 'selected' : ''; ?>>Pendente</option>
                <option value="Aceito" <?php echo ($filter_status == 'Aceito') ? 'selected' : ''; ?>>Aceito</option>
                <option value="Entrega" <?php echo ($filter_status == 'Entrega') ? 'selected' : ''; ?>>Em Entrega</option>
                <option value="Concluido" <?php echo ($filter_status == 'Concluido') ? 'selected' : ''; ?>>Concluído</option>
                <option value="Cancelado" <?php echo ($filter_status == 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
            </select>
            <button id="applyFilterButton">Aplicar Filtro</button>
        </div>

        <div class="table-container">
            <table id="pedidosTable" class="tamanho-tabela">
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Status</th>
                        <th>Data/Hora</th>
                        <th>Cliente</th>
                        <th>Endereço</th>
                        <th>Produtos</th>
                        <th>Valor Total</th>
                        <th>Pagamento</th>
                        <th>Entregador</th>
                        </tr>
                </thead>
                <tbody id="pedidosTableBody">
                    <tr><td colspan="9">Carregando pedidos...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div id="editAddressModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeModalSpan">&times;</span>
            <h2>Editar Endereço do Cliente</h2>
            <form id="modalEditAddressForm" class="modal-form">
                <input type="hidden" id="modalTelefoneInput" name="telefone">
                <label for="modalNomeInput">Nome do Cliente:</label>
                <input type="text" id="modalNomeInput" name="nome_cliente" readonly>

                <label for="modalEnderecoInput">Endereço:</label>
                <input type="text" id="modalEnderecoInput" name="endereco" placeholder="Rua, Avenida, etc.">

                <label for="modalQuadraInput">Quadra:</label>
                <input type="text" id="modalQuadraInput" name="quadra" placeholder="Qd. 10">

                <label for="modalLoteInput">Lote:</label>
                <input type="text" id="modalLoteInput" name="lote" placeholder="Lt. 20">

                <label for="modalSetorInput">Setor/Bairro:</label>
                <input type="text" id="modalSetorInput" name="setor" placeholder="Setor Central">

                <label for="modalComplementoInput">Complemento:</label>
                <input type="text" id="modalComplementoInput" name="complemento" placeholder="Apto 101, Casa 2, Prox. ao mercado">

                <label for="modalCidadeInput">Cidade:</label>
                <input type="text" id="modalCidadeInput" name="cidade" placeholder="Cidade">

                <div id="modalMessage" style="margin-bottom: 10px;"></div>
                <button type="submit">Salvar Alterações</button>
                <button type="button" id="cancelModalBtn" class="modal-form-button cancel">Cancelar</button>
            </form>
        </div>
    </div>

    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeProductModalSpan">&times;</span>
            <h2>Editar Produtos do Pedido #<span id="modalProductOrderIdSpan"></span></h2>
            <form id="modalEditProductForm" class="modal-form">
                <input type="hidden" id="modalProductIdPedidoInput" name="id_pedido">
                <input type="hidden" id="modalProductClienteIdInput" name="id_cliente">

                <h3>Produtos Atuais:</h3>
                <div id="currentOrderItems">
                    </div>

                <h3>Adicionar/Atualizar Produto:</h3>
                <label for="selectProductDropdown">Produto:</label>
                <select id="selectProductDropdown" name="id_produto">
                    <option value="">Selecione um produto</option>
                </select>

                <label for="productQuantityInput">Quantidade:</label>
                <input type="number" id="productQuantityInput" name="quantidade" min="1" value="1">

                <div id="modalProductMessage" style="margin-bottom: 10px;"></div>
                <button type="button" id="addProductToOrderButton">Adicionar/Atualizar Produto</button>
                <button type="submit">Concluir Edição</button>
                <button type="button" id="cancelProductModalBtn" class="modal-form-button cancel">Cancelar</button>
            </form>
        </div>
    </div>

    <div id="editPaymentModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closePaymentModalSpan">&times;</span>
            <h2>Editar Pagamento do Pedido #<span id="modalPaymentOrderIdSpan"></span></h2>
            <form id="modalEditPaymentForm" class="modal-form">
                <input type="hidden" id="modalPaymentPedidoIdInput" name="id_pedido">
                <input type="hidden" id="modalPaymentValorTotalInput" name="valor_total">

                <label for="selectPaymentMethod">Forma de Pagamento:</label>
                <select id="selectPaymentMethod" name="forma_pagamento">
                    <option value="Dinheiro">Dinheiro</option>
                    <option value="Credito">Crédito</option>
                    <option value="Debito">Débito</option>
                    <option value="Pix">Pix</option>
                </select>

                <div id="trocoContainer" style="display: none;">
                    <label for="valorPagoInput">Valor Pago (Dinheiro):</label>
                    <input type="number" id="valorPagoInput" name="valor_pago" min="0" step="0.01" placeholder="Ex: 50.00">
                    <p>Valor Total do Pedido: R$ <span id="displayValorTotal">0.00</span></p>
                    <p>Troco: R$ <span id="displayTroco">0.00</span></p>
                </div>

                <div id="modalPaymentMessage" style="margin-bottom: 10px;"></div>
                <button type="submit">Salvar Alterações</button>
                <button type="button" id="cancelPaymentModalBtn" class="modal-form-button cancel">Cancelar</button>
            </form>
        </div>
    </div>
    <div id="confirmCompleteModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeCompleteModalSpan">&times;</span>
            <h2>Confirmar Conclusão do Pedido</h2>
            <p>Tem certeza que deseja marcar este pedido como "Concluído"?</p>
            <button id="confirmCompleteButton" class="modal-form-button">Sim, Concluir</button>
            <button id="cancelCompleteButton" class="modal-form-button cancel">Não, Voltar</button>
        </div>
    </div>

    <div id="confirmCancelModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeCancelModalSpan">&times;</span>
            <h2>Confirmar Cancelamento do Pedido</h2>
            <p>Tem certeza que deseja marcar este pedido como "Cancelado"?</p>
            <button id="confirmCancelButton" class="modal-form-button">Sim, Cancelar</button>
            <button id="backCancelButton" class="modal-form-button cancel">Não, Voltar</button>
        </div>
    </div>

    <div id="assignDeliveryModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeAssignModalSpan">&times;</span>
            <h2>Atribuir Entregador</h2>
            <p>Atribuir entregador para o Pedido #<span id="modalDeliveryOrderIdSpan"></span> (Status Atual: <span id="currentStatusDisplay"></span>)</p>
            <form id="modalAssignDeliveryForm" class="modal-form">
                <input type="hidden" id="modalDeliveryPedidoIdInput" name="id_pedido">
                <input type="hidden" id="modalDeliveryNewStatusInput" name="status">

                <label for="selectEntregadorDropdown">Selecionar Entregador:</label>
                <select id="selectEntregadorDropdown" name="id_entregador">
                    <option value="">Carregando entregadores...</option>
                </select>

                <div id="modalAssignMessage" style="margin-bottom: 10px;"></div>
                <button type="submit" id="confirmAssignDeliveryButton" class="modal-form-button">Atribuir para Entrega</button>
                <button type="button" id="cancelAssignDeliveryButton" class="modal-form-button cancel">Cancelar</button>
            </form>
        </div>
    </div>

    <div id="editDeliveryModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeEditDeliveryModalSpan">&times;</span>
            <h2>Editar Entregador do Pedido #<span id="modalEditDeliveryOrderIdSpan"></span></h2>
            <form id="modalEditDeliveryForm" class="modal-form">
                <input type="hidden" id="modalEditDeliveryPedidoIdInput" name="id_pedido">

                <label for="selectEditEntregadorDropdown">Selecionar Entregador:</label>
                <select id="selectEditEntregadorDropdown" name="id_entregador">
                    <option value="">Carregando entregadores...</option>
                </select>

                <div id="modalEditDeliveryMessage" style="margin-bottom: 10px;"></div>
                <button type="submit" class="modal-form-button">Salvar Alterações</button>
                <button type="button" id="cancelEditDeliveryButton" class="modal-form-button cancel">Cancelar</button>
            </form>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Referências aos elementos do DOM
            const pedidosTableBody = document.getElementById('pedidosTableBody'); // Novo: Referência ao tbody
            const filterDateInput = document.getElementById('filterDate');
            const filterStatusSelect = document.getElementById('filterStatus');
            const applyFilterButton = document.getElementById('applyFilterButton');

            // Garante que filterDateInput tem um valor ao carregar, se o PHP não o definiu.
            if (!filterDateInput.value) {
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                filterDateInput.value = `${year}-${month}-${day}`;
            }

            const editAddressModal = document.getElementById('editAddressModal');
            const closeModalSpan = document.getElementById('closeModalSpan');
            const modalEditAddressForm = document.getElementById('modalEditAddressForm');
            const modalTelefoneInput = document.getElementById('modalTelefoneInput');
            const modalNomeInput = document.getElementById('modalNomeInput');
            const modalEnderecoInput = document.getElementById('modalEnderecoInput');
            const modalQuadraInput = document.getElementById('modalQuadraInput');
            const modalLoteInput = document.getElementById('modalLoteInput');
            const modalSetorInput = document.getElementById('modalSetorInput');
            const modalComplementoInput = document.getElementById('modalComplementoInput');
            const modalCidadeInput = document.getElementById('modalCidadeInput');
            const modalMessageDiv = document.getElementById('modalMessage');
            const cancelModalBtn = document.getElementById('cancelModalBtn');

            const editProductModal = document.getElementById('editProductModal');
            const closeProductModalSpan = document.getElementById('closeProductModalSpan');
            const modalEditProductForm = document.getElementById('modalEditProductForm');
            const modalProductIdPedidoInput = document.getElementById('modalProductIdPedidoInput');
            const modalProductClienteIdInput = document.getElementById('modalProductClienteIdInput');
            const modalProductOrderIdSpan = document.getElementById('modalProductOrderIdSpan');
            const selectProductDropdown = document.getElementById('selectProductDropdown');
            const productQuantityInput = document.getElementById('productQuantityInput');
            const addProductToOrderButton = document.getElementById('addProductToOrderButton');
            const currentOrderItemsDiv = document.getElementById('currentOrderItems');
            const modalProductMessageDiv = document.getElementById('modalProductMessage');
            const cancelProductModalBtn = document.getElementById('cancelProductModalBtn');

            const confirmCompleteModal = document.getElementById('confirmCompleteModal');
            const closeCompleteModalSpan = document.getElementById('closeCompleteModalSpan');
            const confirmCompleteButton = document.getElementById('confirmCompleteButton');
            const cancelCompleteButton = document = document.getElementById('cancelCompleteButton');

            const confirmCancelModal = document.getElementById('confirmCancelModal');
            const closeCancelModalSpan = document.getElementById('closeCancelModalSpan');
            const confirmCancelButton = document.getElementById('confirmCancelButton');
            const backCancelButton = document.getElementById('backCancelButton');

            const assignDeliveryModal = document.getElementById('assignDeliveryModal');
            const closeAssignModalSpan = document.getElementById('closeAssignModalSpan');
            const modalDeliveryOrderIdSpan = document.getElementById('modalDeliveryOrderIdSpan');
            const modalDeliveryPedidoIdInput = document.getElementById('modalDeliveryPedidoIdInput');
            const modalDeliveryNewStatusInput = document.getElementById('modalDeliveryNewStatusInput');
            const selectEntregadorDropdown = document.getElementById('selectEntregadorDropdown');
            const modalAssignMessageDiv = document.getElementById('modalAssignMessage');
            const confirmAssignDeliveryButton = document.getElementById('confirmAssignDeliveryButton');
            const cancelAssignDeliveryButton = document.getElementById('cancelAssignDeliveryButton');
            const currentStatusDisplay = document.getElementById('currentStatusDisplay');

            // Payment Modal Elements
            const editPaymentModal = document.getElementById('editPaymentModal');
            const closePaymentModalSpan = document.getElementById('closePaymentModalSpan');
            const modalEditPaymentForm = document.getElementById('modalEditPaymentForm');
            const modalPaymentOrderIdSpan = document.getElementById('modalPaymentOrderIdSpan');
            const modalPaymentPedidoIdInput = document.getElementById('modalPaymentPedidoIdInput');
            const modalPaymentValorTotalInput = document.getElementById('modalPaymentValorTotalInput');
            const selectPaymentMethod = document.getElementById('selectPaymentMethod');
            const trocoContainer = document.getElementById('trocoContainer');
            const valorPagoInput = document.getElementById('valorPagoInput');
            const displayValorTotal = document.getElementById('displayValorTotal');
            const displayTroco = document.getElementById('displayTroco');
            const modalPaymentMessage = document.getElementById('modalPaymentMessage');
            const cancelPaymentModalBtn = document.getElementById('cancelPaymentModalBtn');

            // NEW: Edit Delivery Modal Elements
            const editDeliveryModal = document.getElementById('editDeliveryModal');
            const closeEditDeliveryModalSpan = document.getElementById('closeEditDeliveryModalSpan');
            const modalEditDeliveryForm = document.getElementById('modalEditDeliveryForm');
            const modalEditDeliveryOrderIdSpan = document.getElementById('modalEditDeliveryOrderIdSpan');
            const modalEditDeliveryPedidoIdInput = document.getElementById('modalEditDeliveryPedidoIdInput');
            const selectEditEntregadorDropdown = document.getElementById('selectEditEntregadorDropdown');
            const modalEditDeliveryMessage = document.getElementById('modalEditDeliveryMessage');
            const cancelEditDeliveryButton = document.getElementById('cancelEditDeliveryButton');


            const pendingOrderSound = document.getElementById('pendingOrderSound');
            let soundInterval;

            let allProducts = [];
            let currentChangingPedidoId = null;
            let previousPedidoStatus = null; // Para armazenar o status antes de selecionar "Concluído", "Cancelado" ou "Entrega"

            // Função auxiliar para capitalizar a primeira letra de cada palavra.
            function ucwords(str) {
                if (!str) return '';
                return str.toLowerCase().replace(/(^|\s)\S/g, function(firstChar) {
                    return firstChar.toUpperCase();
                });
            }

            // Função para escapar HTML (para evitar XSS ao injetar dados no DOM)
            function htmlspecialchars(str) {
                if (typeof str != 'string') return str; // Retorna números e outros tipos diretamente
                var map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return str.replace(/[&<>"']/g, function(m) { return map[m]; });
            }

            // FUNÇÃO PRINCIPAL: Busca e renderiza os pedidos
            async function fetchAndRenderPedidos() {
                const selectedDate = filterDateInput.value;
                const selectedStatus = filterStatusSelect.value;
                pedidosTableBody.innerHTML = '<tr><td colspan="9">Carregando pedidos...</td></tr>'; // Limpa e mostra carregando

                try {
                    const response = await fetch(`fetch_pedidos.php?filter_date=${encodeURIComponent(selectedDate)}&filter_status=${encodeURIComponent(selectedStatus)}`);
                    if (!response.ok) {
                        throw new Error(`Erro HTTP! status: ${response.status}`);
                    }
                    const data = await response.json();

                    if (data.success) {
                        pedidosTableBody.innerHTML = ''; // Limpa antes de adicionar as novas linhas
                        if (data.pedidos.length > 0) {
                            data.pedidos.forEach(pedido => {
                                const row = document.createElement('tr');

                                // Determine if edit buttons should be visible
                                const showEditButtons = (pedido.status_pedido === 'Pendente' || pedido.status_pedido === 'Aceito');
                                // NEW: Determine if edit delivery button should be visible
                                const showEditDeliveryButton = (pedido.status_pedido === 'Entrega');


                                row.innerHTML = `
                                    <td>${htmlspecialchars(pedido.id_pedido)}</td>
                                    <td>
                                        <select class="status-select" data-id-pedido="${htmlspecialchars(pedido.id_pedido)}" data-initial-status="${htmlspecialchars(pedido.status_pedido)}">
                                            </select>
                                    </td>
                                    <td>${htmlspecialchars(pedido.data_pedido_display)}</td>
                                    <td class="cliente-info-cell">
                                        <div class="cliente-text-content">
                                            ${htmlspecialchars(pedido.cliente_nome)}<br>
                                            ${htmlspecialchars(pedido.cliente_telefone)}
                                        </div>
                                        <button class="action-button print-button" data-id-pedido="${htmlspecialchars(pedido.id_pedido)}">Imprimir</button>
                                    </td>
                                    <td class="endereco-cell">
                                        <div class="endereco-text-content">
                                            ${pedido.endereco_completo}
                                        </div>
                                        ${showEditButtons ? `<button class="action-button edit-address-button" data-telefone="${htmlspecialchars(pedido.cliente_telefone)}">Editar Endereço</button>` : ''}
                                    </td>
                                    <td class="produtos-cell">
                                        <div class="produtos-text-content">
                                            ${pedido.produtos_detalhes}
                                        </div>
                                        ${showEditButtons ? `<button class="action-button edit-product-button" data-id-pedido="${htmlspecialchars(pedido.id_pedido)}" data-id-cliente="${htmlspecialchars(pedido.id_cliente)}">Editar Produtos</button>` : ''}
                                    </td>
                                    <td>${htmlspecialchars(pedido.valor_total_display)}</td>
                                    <td class="pagamento-cell">
                                        <div class="pagamento-text-content">
                                            ${htmlspecialchars(pedido.forma_pagamento_display)}
                                        </div>
                                        ${showEditButtons ? `<button class="action-button edit-payment-button" data-id-pedido="${htmlspecialchars(pedido.id_pedido)}">Editar Pagamento</button>` : ''}
                                    </td>
                                    <td class="entregador-cell">
                                        <div class="entregador-text-content">
                                            ${htmlspecialchars(pedido.entregador_nome ?? 'Não atribuído')}
                                        </div>
                                        ${showEditDeliveryButton && pedido.entregador_nome ? `<button class="action-button edit-delivery-button" data-id-pedido="${htmlspecialchars(pedido.id_pedido)}" data-current-entregador-id="${htmlspecialchars(pedido.id_entregador ?? '')}">Editar Entregador</button>` : ''}
                                    </td>
                                `;
                                pedidosTableBody.appendChild(row);
                            });
                        } else {
                            pedidosTableBody.innerHTML = '<tr><td colspan="9">Nenhum pedido encontrado para a data e status selecionados.</td></tr>';
                        }
                        // Re-anexa listeners aos novos elementos e aplica cores
                        attachEventListeners();
                        document.querySelectorAll('.status-select').forEach(selectElement => {
                            setStatusOptions(selectElement, selectElement.dataset.initialStatus);
                        });
                        checkPendingOrdersAndPlaySound(); // Verifica o som após a atualização da tabela
                    } else {
                        pedidosTableBody.innerHTML = `<tr><td colspan="9">Erro ao carregar pedidos: ${data.message}</td></tr>`;
                        console.error('Erro ao carregar pedidos:', data.message);
                    }
                } catch (error) {
                    pedidosTableBody.innerHTML = `<tr><td colspan="9">Ocorreu um erro ao carregar os pedidos.</td></tr>`;
                    console.error('Erro na requisição fetchAndRenderPedidos:', error);
                }
            }


            // Função para verificar pedidos pendentes e tocar o som
            function checkPendingOrdersAndPlaySound() {
                const currentFilterStatus = filterStatusSelect.value;
                const statusSelects = document.querySelectorAll('.status-select');
                let hasPendingOrder = false;

                statusSelects.forEach(select => {
                    if (select.value === 'Pendente') {
                        hasPendingOrder = true;
                    }
                });

                if ((currentFilterStatus === 'PendenteAceito' || currentFilterStatus === 'Todos') && hasPendingOrder) {
                    if (pendingOrderSound.paused) {
                        pendingOrderSound.play().catch(error => {
                            console.log('Erro ao tentar tocar o áudio (provavelmente autoplay bloqueado ou arquivo não encontrado):', error);
                        });
                    }
                } else {
                    if (!pendingOrderSound.paused) {
                        pendingOrderSound.pause();
                        pendingOrderSound.currentTime = 0;
                    }
                }
            }

            // Função para aplicar a cor do status
            function applyStatusColor(element) {
                const status = element.value;
                element.classList.remove('status-Pendente', 'status-Aceito', 'status-Entrega', 'status-Concluido', 'status-Cancelado');

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
                element.style.color = 'white';
            }

            // Função para preencher e configurar as opções do select de status
            function setStatusOptions(selectElement, currentStatus) {
                selectElement.innerHTML = '';
                const statusOrder = ['Pendente', 'Aceito', 'Entrega', 'Concluido', 'Cancelado'];
                const allowedTransitions = {
                    'Pendente': ['Pendente', 'Aceito', 'Entrega', 'Concluido', 'Cancelado'],
                    'Aceito': ['Aceito', 'Entrega', 'Concluido', 'Cancelado'],
                    'Entrega': ['Entrega', 'Concluido', 'Cancelado'],
                    'Concluido': ['Concluido'],
                    'Cancelado': ['Cancelado']
                };

                const validOptions = allowedTransitions[currentStatus] || [];

                statusOrder.forEach(statusValue => {
                    if (validOptions.includes(statusValue)) {
                        const option = document.createElement('option');
                        option.value = statusValue;
                        option.textContent = statusValue;
                        if (statusValue === currentStatus) {
                            option.selected = true;
                        }
                        selectElement.appendChild(option);
                    }
                });

                if (currentStatus === 'Concluido' || currentStatus === 'Cancelado') {
                    selectElement.disabled = true;
                } else {
                    selectElement.disabled = false;
                }
                applyStatusColor(selectElement);
            }

            // Função para anexar event listeners aos botões e selects (chamada após cada renderização)
            function attachEventListeners() {
                // Remove listeners antigos para evitar duplicação
                document.querySelectorAll('.print-button').forEach(button => {
                    button.removeEventListener('click', handlePrintClick);
                });
                document.querySelectorAll('.edit-address-button').forEach(button => {
                    button.removeEventListener('click', handleEditAddressClick);
                });
                document.querySelectorAll('.edit-product-button').forEach(button => {
                    button.removeEventListener('click', handleEditProductClick);
                });
                document.querySelectorAll('.edit-payment-button').forEach(button => {
                    button.removeEventListener('click', handleEditPaymentClick);
                });
                 document.querySelectorAll('.edit-delivery-button').forEach(button => { // NEW: Remove old delivery listeners
                    button.removeEventListener('click', handleEditDeliveryClick);
                });
                document.querySelectorAll('.status-select').forEach(selectElement => {
                    selectElement.removeEventListener('change', handleStatusChange);
                });

                // Adiciona novos listeners
                document.querySelectorAll('.print-button').forEach(button => {
                    button.addEventListener('click', handlePrintClick);
                });

                // Only attach listeners if the buttons actually exist in the DOM
                document.querySelectorAll('.edit-address-button').forEach(button => {
                    button.addEventListener('click', handleEditAddressClick);
                });

                document.querySelectorAll('.edit-product-button').forEach(button => {
                    button.addEventListener('click', handleEditProductClick);
                });

                document.querySelectorAll('.edit-payment-button').forEach(button => {
                    button.addEventListener('click', handleEditPaymentClick);
                });
                document.querySelectorAll('.edit-delivery-button').forEach(button => { // NEW: Add new delivery listeners
                    button.addEventListener('click', handleEditDeliveryClick);
                });

                document.querySelectorAll('.status-select').forEach(selectElement => {
                    selectElement.addEventListener('change', handleStatusChange);
                });
            }

            // --- Handlers de Eventos ---

            function handlePrintClick(event) {
                event.stopPropagation();
                const idPedido = this.dataset.idPedido;
                window.open(`imprimir_pedido.php?id_pedido=${idPedido}`, '_blank');
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

            // handleEditPaymentClick function
            function handleEditPaymentClick(event) {
                event.stopPropagation();
                const idPedido = this.dataset.idPedido;
                openEditPaymentModal(idPedido);
            }

            // NEW: handleEditDeliveryClick function
            function handleEditDeliveryClick(event) {
                event.stopPropagation();
                const idPedido = this.dataset.idPedido;
                const currentEntregadorId = this.dataset.currentEntregadorId;
                openEditDeliveryModal(idPedido, currentEntregadorId);
            }

            function handleStatusChange() {
                const idPedido = this.dataset.idPedido;
                const novoStatus = this.value;
                const initialStatus = this.dataset.initialStatus;

                currentChangingPedidoId = idPedido;
                previousPedidoStatus = initialStatus;

                if (novoStatus === 'Concluido') {
                    openConfirmCompleteModal();
                    confirmCompleteButton.onclick = () => {
                        updatePedidoStatus(idPedido, novoStatus, this);
                        closeConfirmCompleteModal();
                    };
                    cancelCompleteButton.onclick = () => {
                        this.value = previousPedidoStatus;
                        setStatusOptions(this, previousPedidoStatus);
                        closeConfirmCompleteModal();
                        currentChangingPedidoId = null;
                        previousPedidoStatus = null;
                    };
                } else if (novoStatus === 'Cancelado') {
                    openConfirmCancelModal();
                    confirmCancelButton.onclick = () => {
                        updatePedidoStatus(idPedido, novoStatus, this);
                        closeConfirmCancelModal();
                    };
                    backCancelButton.onclick = () => {
                        this.value = previousPedidoStatus;
                        setStatusOptions(this, previousPedidoStatus);
                        closeConfirmCancelModal();
                        currentChangingPedidoId = null;
                        previousPedidoStatus = null;
                    };
                } else if (novoStatus === 'Entrega') {
                    openAssignDeliveryModal(idPedido, novoStatus, initialStatus);
                } else {
                    updatePedidoStatus(idPedido, novoStatus, this);
                }
            }


            // --- Funções de Modal ---

            function openConfirmCompleteModal() {
                confirmCompleteModal.style.display = 'flex';
            }

            function closeConfirmCompleteModal() {
                confirmCompleteModal.style.display = 'none';
            }

            function openConfirmCancelModal() {
                confirmCancelModal.style.display = 'flex';
            }

            function closeConfirmCancelModal() {
                confirmCancelModal.style.display = 'none';
            }

            function openAssignDeliveryModal(idPedido, novoStatus, currentStatus) {
                modalAssignMessageDiv.textContent = '';
                modalDeliveryOrderIdSpan.textContent = idPedido;
                modalDeliveryPedidoIdInput.value = idPedido;
                modalDeliveryNewStatusInput.value = novoStatus;
                currentStatusDisplay.textContent = currentStatus;

                selectEntregadorDropdown.innerHTML = '<option value="">Carregando entregadores...</option>';
                confirmAssignDeliveryButton.textContent = 'Atribuir para Entrega';

                fetch('get_entregadores.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Erro HTTP! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            selectEntregadorDropdown.innerHTML = '<option value="">Selecione um entregador</option>';
                            data.entregadores.forEach(entregador => {
                                const option = document.createElement('option');
                                option.value = entregador.id_entregador;
                                option.textContent = entregador.nome;
                                selectEntregadorDropdown.appendChild(option);
                            });
                        } else {
                            modalAssignMessageDiv.textContent = 'Erro ao carregar entregadores: ' + data.message;
                            modalAssignMessageDiv.style.color = 'red';
                            selectEntregadorDropdown.innerHTML = '<option value="">Erro ao carregar</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao buscar entregadores:', error);
                        modalAssignMessageDiv.textContent = 'Ocorreu um erro ao carregar os entregadores.';
                        modalAssignMessageDiv.style.color = 'red';
                        selectEntregadorDropdown.innerHTML = '<option value="">Erro ao carregar</option>';
                    });

                assignDeliveryModal.style.display = 'flex';
            }

            function closeAssignDeliveryModal() {
                assignDeliveryModal.style.display = 'none';
                document.getElementById('modalAssignDeliveryForm').reset();
            }


            function openEditAddressModal(telefone) {
                modalMessageDiv.textContent = '';
                fetch(`get_cliente_endereco.php?telefone=${encodeURIComponent(telefone)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Erro HTTP! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
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
                                itemDiv.innerHTML = `${item.quantidade}x ${ucwords(item.nome_produto)} (R$ ${parseFloat(item.preco_unitario).toFixed(2).replace('.', ',')})
                                    <button onclick="removeProductFromOrder(${idPedido}, ${item.id_produto})" style="margin-left: 10px; background-color: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; padding: 2px 5px;">Remover</button>`;
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

            // openEditPaymentModal function
            function openEditPaymentModal(idPedido) {
                modalPaymentMessage.textContent = '';
                modalPaymentOrderIdSpan.textContent = idPedido;
                modalPaymentPedidoIdInput.value = idPedido;
                displayTroco.textContent = '0.00';
                valorPagoInput.value = '';
                trocoContainer.style.display = 'none';

                fetch(`fetch_payment_details.php?id_pedido=${encodeURIComponent(idPedido)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Erro HTTP! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            selectPaymentMethod.value = data.forma_pagamento;
                            modalPaymentValorTotalInput.value = parseFloat(data.valor_total).toFixed(2);
                            displayValorTotal.textContent = parseFloat(data.valor_total).toFixed(2).replace('.', ',');

                            if (data.forma_pagamento === 'Dinheiro') {
                                trocoContainer.style.display = 'block';
                                if (data.valor_pago) {
                                    valorPagoInput.value = parseFloat(data.valor_pago).toFixed(2);
                                    const troco = parseFloat(data.valor_pago) - parseFloat(data.valor_total);
                                    displayTroco.textContent = troco.toFixed(2).replace('.', ',');
                                }
                            }
                            editPaymentModal.style.display = 'flex';
                        } else {
                            console.error('Erro ao carregar detalhes do pagamento: ' + data.message);
                            modalPaymentMessage.textContent = 'Erro ao carregar detalhes do pagamento.';
                            modalPaymentMessage.style.color = 'red';
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao buscar detalhes do pagamento:', error);
                        modalPaymentMessage.textContent = 'Ocorreu um erro ao carregar os detalhes do pagamento.';
                        modalPaymentMessage.style.color = 'red';
                    });
            }

            // closeEditPaymentModal function
            function closeEditPaymentModal() {
                editPaymentModal.style.display = 'none';
                modalEditPaymentForm.reset();
                trocoContainer.style.display = 'none';
                modalPaymentMessage.textContent = '';
                displayTroco.textContent = '0.00';
            }

            // NEW: openEditDeliveryModal function
            function openEditDeliveryModal(idPedido, currentEntregadorId) {
                modalEditDeliveryMessage.textContent = '';
                modalEditDeliveryOrderIdSpan.textContent = idPedido;
                modalEditDeliveryPedidoIdInput.value = idPedido;
                selectEditEntregadorDropdown.innerHTML = '<option value="">Carregando entregadores...</option>';

                fetch('get_entregadores.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Erro HTTP! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            selectEditEntregadorDropdown.innerHTML = '<option value="">Selecione um entregador</option>';
                            data.entregadores.forEach(entregador => {
                                const option = document.createElement('option');
                                option.value = entregador.id_entregador;
                                option.textContent = entregador.nome;
                                if (entregador.id_entregador == currentEntregadorId) {
                                    option.selected = true;
                                }
                                selectEditEntregadorDropdown.appendChild(option);
                            });
                        } else {
                            modalEditDeliveryMessage.textContent = 'Erro ao carregar entregadores: ' + data.message;
                            modalEditDeliveryMessage.style.color = 'red';
                            selectEditEntregadorDropdown.innerHTML = '<option value="">Erro ao carregar</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao buscar entregadores para edição:', error);
                        modalEditDeliveryMessage.textContent = 'Ocorreu um erro ao carregar os entregadores.';
                        modalEditDeliveryMessage.style.color = 'red';
                        selectEditEntregadorDropdown.innerHTML = '<option value="">Erro ao carregar</option>';
                    });

                editDeliveryModal.style.display = 'flex';
            }

            // NEW: closeEditDeliveryModal function
            function closeEditDeliveryModal() {
                editDeliveryModal.style.display = 'none';
                modalEditDeliveryForm.reset();
                modalEditDeliveryMessage.textContent = '';
            }


            // --- Event Listeners para Modais (fechar) ---

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

            closeCompleteModalSpan.addEventListener('click', closeConfirmCompleteModal);
            window.addEventListener('click', function(event) {
                if (event.target == confirmCompleteModal) {
                    closeConfirmCompleteModal();
                }
            });

            closeCancelModalSpan.addEventListener('click', closeConfirmCancelModal);
            window.addEventListener('click', function(event) {
                if (event.target == confirmCancelModal) {
                    closeConfirmCancelModal();
                }
            });

            closeAssignModalSpan.addEventListener('click', function() {
                closeAssignDeliveryModal();
                if (currentChangingPedidoId) {
                    const selectElement = document.querySelector(`[data-id-pedido="${currentChangingPedidoId}"]`);
                    if (selectElement && previousPedidoStatus) {
                        selectElement.value = previousPedidoStatus;
                        setStatusOptions(selectElement, previousPedidoStatus);
                    }
                }
                currentChangingPedidoId = null;
                previousPedidoStatus = null;
            });
            cancelAssignDeliveryButton.addEventListener('click', function() {
                closeAssignDeliveryModal();
                if (currentChangingPedidoId) {
                    const selectElement = document.querySelector(`[data-id-pedido="${currentChangingPedidoId}"]`);
                    if (selectElement && previousPedidoStatus) {
                        selectElement.value = previousPedidoStatus;
                        setStatusOptions(selectElement, previousPedidoStatus);
                    }
                }
                currentChangingPedidoId = null;
                previousPedidoStatus = null;
            });
            window.addEventListener('click', function(event) {
                if (event.target == assignDeliveryModal) {
                    closeAssignDeliveryModal();
                    if (currentChangingPedidoId) {
                        const selectElement = document.querySelector(`[data-id-pedido="${currentChangingPedidoId}"]`);
                        if (selectElement && previousPedidoStatus) {
                            selectElement.value = previousPedidoStatus;
                            setStatusOptions(selectElement, previousPedidoStatus);
                        }
                    }
                    currentChangingPedidoId = null;
                    previousPedidoStatus = null;
                }
            });

            // Payment Modal close listeners
            closePaymentModalSpan.addEventListener('click', closeEditPaymentModal);
            cancelPaymentModalBtn.addEventListener('click', closeEditPaymentModal);
            window.addEventListener('click', function(event) {
                if (event.target == editPaymentModal) {
                    closeEditPaymentModal();
                }
            });

            // NEW: Edit Delivery Modal close listeners
            closeEditDeliveryModalSpan.addEventListener('click', closeEditDeliveryModal);
            cancelEditDeliveryButton.addEventListener('click', closeEditDeliveryModal);
            window.addEventListener('click', function(event) {
                if (event.target == editDeliveryModal) {
                    closeEditDeliveryModal();
                }
            });


            // --- Lógica de Submissão de Formulários e AJAX ---

            // Listener para o botão de aplicar filtro (agora chama fetchAndRenderPedidos)
            applyFilterButton.addEventListener('click', fetchAndRenderPedidos);

            // Listener para mudar o filtro de data (agora chama fetchAndRenderPedidos)
            filterDateInput.addEventListener('change', fetchAndRenderPedidos);

            // Listener para mudar o filtro de status (agora chama fetchAndRenderPedidos)
            filterStatusSelect.addEventListener('change', fetchAndRenderPedidos);


             modalEditAddressForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                modalMessageDiv.textContent = '';

                const formData = new FormData(modalEditAddressForm); // Obtém os dados do formulário como FormData

                try {
                    const response = await fetch('update_endereco_cliente.php', {
                        method: 'POST',
                        body: formData // Envia o objeto FormData diretamente
                    });
                    const result = await response.json();

                    if (result.success) {
                        modalMessageDiv.textContent = 'Endereço atualizado com sucesso!';
                        modalMessageDiv.style.color = 'green';
                        closeEditAddressModal(); // Fecha o modal
                        fetchAndRenderPedidos(); // Recarrega a tabela para ver a mudança
                    } else {
                        modalMessageDiv.textContent = 'Erro ao atualizar endereço: ' + result.message;
                        modalMessageDiv.style.color = 'red';
                    }
                } catch (error) {
                    console.error('Erro na requisição:', error);
                    modalMessageDiv.textContent = 'Ocorreu um erro ao tentar atualizar o endereço.';
                    modalMessageDiv.style.color = 'red';
                }
            });


            addProductToOrderButton.addEventListener('click', async function() {
                const idPedido = modalProductIdPedidoInput.value;
                const idCliente = modalProductClienteIdInput.value;
                const idProduto = selectProductDropdown.value;
                const quantidade = productQuantityInput.value;

                if (!idProduto || !quantidade || quantidade < 1) {
                    modalProductMessageDiv.textContent = 'Por favor, selecione um produto e insira uma quantidade válida.';
                    modalProductMessageDiv.style.color = 'red';
                    return;
                }

                const selectedProduct = allProducts.find(p => p.id_produtos == idProduto);
                if (!selectedProduct) {
                    modalProductMessageDiv.textContent = 'Produto não encontrado.';
                    modalProductMessageDiv.style.color = 'red';
                    return;
                }
                const precoUnitario = selectedProduct.preco;

                try {
                    const response = await fetch('add_update_order_item.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id_pedido: idPedido,
                            id_produto: idProduto,
                            quantidade: quantidade,
                            preco_unitario: precoUnitario
                        })
                    });
                    const result = await response.json();

                    if (result.success) {
                        modalProductMessageDiv.textContent = 'Produto adicionado/atualizado com sucesso!';
                        modalProductMessageDiv.style.color = 'green';
                        openEditProductModal(idPedido, idCliente); // Reabre o modal para atualizar a lista
                        fetchAndRenderPedidos(); // Recarrega a tabela principal
                    } else {
                        modalProductMessageDiv.textContent = 'Erro ao adicionar/atualizar produto: ' + result.message;
                        modalProductMessageDiv.style.color = 'red';
                    }
                } catch (error) {
                    console.error('Erro na requisição:', error);
                    modalProductMessageDiv.textContent = 'Ocorreu um erro ao tentar adicionar/atualizar o produto.';
                    modalProductMessageDiv.style.color = 'red';
                }
            });

            window.removeProductFromOrder = async function(idPedido, idProduto) {
                if (!confirm('Tem certeza que deseja remover este produto do pedido?')) {
                    return;
                }
                try {
                    const response = await fetch('remove_order_item.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id_pedido: idPedido,
                            id_produto: idProduto
                        })
                    });
                    const result = await response.json();

                    if (result.success) {
                        modalProductMessageDiv.textContent = 'Produto removido com sucesso!';
                        modalProductMessageDiv.style.color = 'green';
                        openEditProductModal(idPedido, modalProductClienteIdInput.value);
                        fetchAndRenderPedidos(); // Recarrega a tabela principal
                    } else {
                        modalProductMessageDiv.textContent = 'Erro ao remover produto: ' + result.message;
                        modalProductMessageDiv.style.color = 'red';
                    }
                } catch (error) {
                    console.error('Erro na requisição:', error);
                    modalProductMessageDiv.textContent = 'Ocorreu um erro ao tentar remover o produto.';
                    modalProductMessageDiv.style.color = 'red';
                }
            };

            modalEditProductForm.addEventListener('submit', function(event) {
                event.preventDefault();
                closeEditProductModal();
                fetchAndRenderPedidos(); // Recarrega a tabela principal
            });

            document.getElementById('modalAssignDeliveryForm').addEventListener('submit', async function(event) {
                event.preventDefault();
                const idPedido = modalDeliveryPedidoIdInput.value;
                const novoStatus = modalDeliveryNewStatusInput.value;
                const idEntregador = selectEntregadorDropdown.value;

                if (!idEntregador) {
                    modalAssignMessageDiv.textContent = 'Por favor, selecione um entregador.';
                    modalAssignMessageDiv.style.color = 'red';
                    return;
                }

                const selectElement = document.querySelector(`[data-id-pedido="${idPedido}"]`);
                await updatePedidoStatus(idPedido, novoStatus, selectElement, idEntregador); // Espera a atualização
                closeAssignDeliveryModal();
                currentChangingPedidoId = null;
                previousPedidoStatus = null;
            });

            // Payment method change listener
            selectPaymentMethod.addEventListener('change', function() {
                if (this.value === 'Dinheiro') {
                    trocoContainer.style.display = 'block';
                    valorPagoInput.value = ''; // Clear value when switching to Dinheiro
                    displayTroco.textContent = '0.00';
                } else {
                    trocoContainer.style.display = 'none';
                }
            });

            // Valor Pago input listener for change calculation
            valorPagoInput.addEventListener('input', function() {
                const valorTotal = parseFloat(modalPaymentValorTotalInput.value);
                const valorPago = parseFloat(this.value);
                if (!isNaN(valorTotal) && !isNaN(valorPago)) {
                    const troco = valorPago - valorTotal;
                    displayTroco.textContent = troco.toFixed(2).replace('.', ',');
                    if (troco < 0) {
                        displayTroco.style.color = 'red';
                    } else {
                        displayTroco.style.color = 'green';
                    }
                } else {
                    displayTroco.textContent = '0.00';
                    displayTroco.style.color = 'inherit';
                }
            });

            // Payment form submission
            modalEditPaymentForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                modalPaymentMessage.textContent = '';

                const idPedido = modalPaymentPedidoIdInput.value;
                const formaPagamento = selectPaymentMethod.value;
                let valorPago = null;

                if (formaPagamento === 'Dinheiro') {
                    valorPago = parseFloat(valorPagoInput.value);
                    if (isNaN(valorPago) || valorPago < 0) {
                        modalPaymentMessage.textContent = 'Por favor, insira um valor pago válido para pagamento em Dinheiro.';
                        modalPaymentMessage.style.color = 'red';
                        return;
                    }
                    const valorTotal = parseFloat(modalPaymentValorTotalInput.value);
                    if (valorPago < valorTotal) {
                        modalPaymentMessage.textContent = 'O valor pago é menor que o valor total do pedido. Por favor, ajuste.';
                        modalPaymentMessage.style.color = 'red';
                        return;
                    }
                }

                try {
                    const response = await fetch('update_payment_details.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id_pedido: idPedido,
                            forma_pagamento: formaPagamento,
                            valor_pago: valorPago
                        })
                    });
                    const result = await response.json();

                    if (result.success) {
                        modalPaymentMessage.textContent = 'Pagamento atualizado com sucesso!';
                        modalPaymentMessage.style.color = 'green';
                        closeEditPaymentModal();
                        fetchAndRenderPedidos(); // Reload table to reflect changes
                    } else {
                        modalPaymentMessage.textContent = 'Erro ao atualizar pagamento: ' + result.message;
                        modalPaymentMessage.style.color = 'red';
                    }
                } catch (error) {
                    console.error('Erro na requisição:', error);
                    modalPaymentMessage.textContent = 'Ocorreu um erro ao tentar atualizar o pagamento.';
                    modalPaymentMessage.style.color = 'red';
                }
            });

            // NEW: Edit Delivery Form Submission
            modalEditDeliveryForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                modalEditDeliveryMessage.textContent = '';

                const idPedido = modalEditDeliveryPedidoIdInput.value;
                const idEntregador = selectEditEntregadorDropdown.value;

                if (!idEntregador) {
                    modalEditDeliveryMessage.textContent = 'Por favor, selecione um entregador.';
                    modalEditDeliveryMessage.style.color = 'red';
                    return;
                }

                try {
                    const response = await fetch('update_entregador_pedido.php', { // You'll need to create this PHP file
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id_pedido: idPedido,
                            id_entregador: idEntregador
                        })
                    });
                    const result = await response.json();

                    if (result.success) {
                        modalEditDeliveryMessage.textContent = 'Entregador atualizado com sucesso!';
                        modalEditDeliveryMessage.style.color = 'green';
                        closeEditDeliveryModal();
                        fetchAndRenderPedidos(); // Recarrega a tabela para ver a mudança
                    } else {
                        modalEditDeliveryMessage.textContent = 'Erro ao atualizar entregador: ' + result.message;
                        modalEditDeliveryMessage.style.color = 'red';
                    }
                } catch (error) {
                    console.error('Erro na requisição:', error);
                    modalEditDeliveryMessage.textContent = 'Ocorreu um erro ao tentar atualizar o entregador.';
                    modalEditDeliveryMessage.style.color = 'red';
                }
            });


            async function updatePedidoStatus(idPedido, novoStatus, selectElement, idEntregador = null) {
                selectElement.disabled = true; // Desabilita o select enquanto espera

                try {
                    const response = await fetch('atualizar_status_pedido.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id_pedido=${encodeURIComponent(idPedido)}&status=${encodeURIComponent(novoStatus)}${idEntregador ? `&id_entregador=${encodeURIComponent(idEntregador)}` : ''}`
                    });
                    const data = await response.json();

                    if (data.success) {
                        console.log('Status atualizado com sucesso:', data.message);
                        selectElement.dataset.initialStatus = novoStatus;
                        // Não é mais necessário re-renderizar individualmente aqui, pois fetchAndRenderPedidos() fará isso.
                        // Apenas reabilita o select se não for um status final, para que o setInterval atualize.
                        if (novoStatus !== 'Concluido' && novoStatus !== 'Cancelado') {
                             selectElement.disabled = false;
                        }
                        fetchAndRenderPedidos(); // Recarrega a tabela principal para ver a mudança
                    } else {
                        console.error('Erro ao atualizar status: ' + data.message);
                        alert('Erro ao atualizar status: ' + data.message);
                        selectElement.value = previousPedidoStatus;
                        setStatusOptions(selectElement, previousPedidoStatus);
                        selectElement.disabled = false; // Reabilita em caso de erro
                    }
                } catch (error) {
                    console.error('Erro na requisição:', error);
                    alert('Ocorreu um erro de comunicação com o servidor ao tentar atualizar o status.');
                    selectElement.value = previousPedidoStatus;
                    setStatusOptions(selectElement, previousPedidoStatus);
                    selectElement.disabled = false; // Reabilita em caso de erro
                }
            }

            // --- Inicialização ---

            // Chama a função pela primeira vez para carregar os pedidos iniciais
            fetchAndRenderPedidos();

            // Configura o intervalo para recarregar os pedidos a cada 5 segundos
            setInterval(fetchAndRenderPedidos, 10000); // 5000 milissegundos = 5 segundos

        }); // Fim do DOMContentLoaded
    </script>
</body>
</html>
