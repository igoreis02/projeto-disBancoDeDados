document.addEventListener('DOMContentLoaded', function() {
    const entregadorTableBody = document.querySelector('#entregadorTable tbody');
    const addEntregadorBtn = document.getElementById('addEntregadorBtn');
    const entregadorModal = document.getElementById('entregadorModal');
    const closeBtn = entregadorModal.querySelector('.close');
    const entregadorForm = document.getElementById('entregadorForm');
    const entregadorIdInput = document.getElementById('entregadorId');
    const nomeEntregadorInput = document.getElementById('nomeEntregador');
    const cpfEntregadorInput = document.getElementById('cpfEntregador');
    const dataNascimentoEntregadorInput = document.getElementById('dataNascimentoEntregador');
    const telefoneEntregadorInput = document.getElementById('telefoneEntregador');
    const cnhEntregadorInput = document.getElementById('cnhEntregador');

    // Function to fetch and display entregadores
    function fetchEntregadores() {
        fetch('listar_entregadores.php')
            .then(response => response.json())
            .then(data => {
                entregadorTableBody.innerHTML = ''; // Clear existing rows
                if (data.length > 0) {
                    data.forEach(entregador => {
                        const row = entregadorTableBody.insertRow();
                        row.innerHTML = `
                            <td>${entregador.nome}</td>
                            <td>${entregador.cpf}</td>
                            <td>${entregador.data_nascimento}</td>
                            <td>${entregador.telefone}</td>
                            <td>${entregador.cnh}</td>
                            <td>
                                <button class="edit-btn" data-id="${entregador.id_entregador}">Editar</button>
                                <button class="delete-btn" data-id="${entregador.id_entregador}">Excluir</button>
                            </td>
                        `;
                    });
                } else {
                    const row = entregadorTableBody.insertRow();
                    row.innerHTML = `<td colspan="6">Nenhum entregador cadastrado.</td>`;
                }
            })
            .catch(error => console.error('Erro ao buscar entregadores:', error));
    }

    // Open Add Entregador Modal
    addEntregadorBtn.addEventListener('click', function() {
        entregadorForm.reset(); // Clear form fields
        entregadorIdInput.value = ''; // Ensure no ID is set for new entry
        entregadorModal.style.display = 'block';
        entregadorModal.querySelector('h2').textContent = 'Cadastrar Entregador';
    });

    // Close Modal
    closeBtn.addEventListener('click', function() {
        entregadorModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == entregadorModal) {
            entregadorModal.style.display = 'none';
        }
    });

    // Handle form submission (Add/Edit Entregador)
    entregadorForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(entregadorForm);
        const id = entregadorIdInput.value;
        const url = id ? 'atualizar_entregador.php' : 'cadastrar_entregador.php';

        if (id) {
            formData.append('id_entregador', id); // Add ID for update
        }

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                entregadorModal.style.display = 'none';
                fetchEntregadores(); // Refresh the list
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => console.error('Erro ao salvar entregador:', error));
    });

    // Handle Edit and Delete buttons
    entregadorTableBody.addEventListener('click', function(event) {
        if (event.target.classList.contains('edit-btn')) {
            const id = event.target.dataset.id;
            // Fetch entregador details to populate the form
            fetch(`listar_entregadores.php?id=${id}`) // Fetch single entregador by ID
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) { // Check if data is an array and not empty
                        const entregador = data[0]; // Get the first (and only) entregador
                        entregadorIdInput.value = entregador.id_entregador;
                        nomeEntregadorInput.value = entregador.nome;
                        cpfEntregadorInput.value = entregador.cpf;
                        dataNascimentoEntregadorInput.value = entregador.data_nascimento;
                        telefoneEntregadorInput.value = entregador.telefone;
                        cnhEntregadorInput.value = entregador.cnh;
                        entregadorModal.style.display = 'block';
                        entregadorModal.querySelector('h2').textContent = 'Editar Entregador';
                    } else {
                        alert('Entregador não encontrado.');
                    }
                })
                .catch(error => console.error('Erro ao buscar entregador para edição:', error));

        } else if (event.target.classList.contains('delete-btn')) {
            const id = event.target.dataset.id;
            if (confirm('Tem certeza que deseja excluir este entregador?')) {
                const formData = new FormData();
                formData.append('id_entregador', id);

                fetch('deletar_entregador.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        fetchEntregadores(); // Refresh the list
                    } else {
                        alert('Erro: ' + data.message);
                    }
                })
                .catch(error => console.error('Erro ao excluir entregador:', error));
            }
        }
    });

    // Initial fetch of entregadores when the page loads
    fetchEntregadores();
});