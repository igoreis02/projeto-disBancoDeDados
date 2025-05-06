document.addEventListener('DOMContentLoaded', function() {
    fetch('listar-clientes.php')
        .then(response => response.json())
        .then(clientes => {
            const tableBody = document.querySelector('#clientTable tbody');
            tableBody.innerHTML = '';

            if (clientes.length > 0) {
                clientes.forEach(cliente => {
                    let row = tableBody.insertRow();
                    row.insertCell().textContent = cliente.telefone;
                    row.insertCell().textContent = cliente.nome;
                    row.insertCell().textContent = cliente.dt_nascimento;
                    row.insertCell().textContent = cliente.endereco;
                    row.insertCell().textContent = cliente.quadra;
                    row.insertCell().textContent = cliente.lote;
                    row.insertCell().textContent = cliente.setor;
                    row.insertCell().textContent = cliente.complemento;
                    row.insertCell().textContent = cliente.cidade;
                    row.insertCell().textContent = cliente.sexo;
                    row.insertCell().textContent = cliente.termoSorteio ? 'Sim' : 'Não';
                    row.insertCell().textContent = cliente.numeroSorteado;

                    let tdAcoes = row.insertCell();

                    let btnEditar = document.createElement('button');
                    btnEditar.classList.add('edit-btn');
                    btnEditar.textContent = 'Editar';
                    btnEditar.addEventListener('click', () => editarCliente(cliente)); // Passa o objeto cliente
                    tdAcoes.appendChild(btnEditar);

                    let btnExcluir = document.createElement('button');
                    btnExcluir.textContent = 'Excluir';
                    btnExcluir.classList.add('delete-btn');
                    btnExcluir.addEventListener('click', () => excluirCliente(cliente.id));
                    tdAcoes.appendChild(btnExcluir);
                });
            } else {
                let row = tableBody.insertRow();
                let cell = row.insertCell();
                cell.colSpan = 12;
                cell.textContent = "Nenhum cliente encontrado";
            }
        })
        .catch(error => console.error('Erro ao buscar clientes:', error));

    // Get the modal
    var modal = document.getElementById("editModal");

    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];

    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = "none";
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Handle form submission
    document.getElementById('editForm').addEventListener('submit', salvarCliente);
});

function editarCliente(cliente) {
    console.log('Editar cliente:', cliente);
    // Populate the modal with the client's data
    document.getElementById('editId').value = cliente.id;
    document.getElementById('editTelefone').value = cliente.telefone;
    document.getElementById('editNome').value = cliente.nome;
    document.getElementById('editDt_nascimento').value = cliente.dt_nascimento;
    document.getElementById('editEndereco').value = cliente.endereco;
    document.getElementById('editQuadra').value = cliente.quadra;
    document.getElementById('editLote').value = cliente.lote;
    document.getElementById('editSetor').value = cliente.setor;
    document.getElementById('editComplemento').value = cliente.complemento;
    document.getElementById('editCidade').value = cliente.cidade;
    document.getElementById('editSexo').value = cliente.sexo;
    document.getElementById('editTermoSorteio').value = cliente.termoSorteio;

    // Show the modal
    document.getElementById('editModal').style.display = "block";
}

function salvarCliente(event) {
    event.preventDefault(); // Prevent the default form submission

    const id = document.getElementById('editId').value;
    const telefone = document.getElementById('editTelefone').value;
    const nome = document.getElementById('editNome').value;
    const dt_nascimento = document.getElementById('editDt_nascimento').value;
    const endereco = document.getElementById('editEndereco').value;
    const quadra = document.getElementById('editQuadra').value;
    const lote = document.getElementById('editLote').value;
    const setor = document.getElementById('editSetor').value;
    const complemento = document.getElementById('editComplemento').value;
    const cidade = document.getElementById('editCidade').value;
    const sexo = document.getElementById('editSexo').value;
    const termoSorteio = document.getElementById('editTermoSorteio').value;

    fetch('editar-cliente.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}&telefone=${telefone}&nome=${nome}&dt_nascimento=${dt_nascimento}&endereco=${endereco}&quadra=${quadra}&lote=${lote}&setor=${setor}&complemento=${complemento}&cidade=${cidade}&sexo=${sexo}&termoSorteio=${termoSorteio}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            document.getElementById('editModal').style.display = "none"; // Hide the modal
            window.location.reload(); // Refresh the page to show updated data
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Erro ao atualizar cliente:', error));
}

function excluirCliente(id) {
    if (confirm("Tem certeza que deseja excluir este cliente?")) {
        fetch('excluir-cliente.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                let errorMessage = data.message || 'Erro ao excluir cliente.';
                alert(errorMessage);
            }
        })
        .catch(error => {
            console.error('Erro ao excluir cliente:', error);
            alert('Erro ao excluir cliente: ' + error.message);
        });
    }
}