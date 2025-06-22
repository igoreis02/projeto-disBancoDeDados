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

                    // Concatenate address details into a single cell
                    let enderecoCell = row.insertCell();
                    let fullAddress = `${cliente.endereco}, qd ${cliente.quadra}, lt ${cliente.lote}`;
                    if (cliente.setor) {
                        fullAddress += `<br>Setor: ${cliente.setor}`;
                    }
                    if (cliente.complemento) {
                        fullAddress += `<br>Complemento: ${cliente.complemento}`;
                    }
                    fullAddress += `<br>${cliente.cidade}`;
                    enderecoCell.innerHTML = fullAddress; // Use innerHTML to render line breaks

                    row.insertCell().textContent = cliente.sexo;
                    row.insertCell().textContent = cliente.termoSorteio ? 'Sim' : 'Não';

                    let tdAcoes = row.insertCell();

                    let btnEditar = document.createElement('button');
                    btnEditar.classList.add('edit-btn');
                    btnEditar.textContent = 'Editar';
                    btnEditar.addEventListener('click', () => editarCliente(cliente));
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
                cell.colSpan = 7; // Adjusted colspan for the new number of columns
                cell.textContent = "Nenhum cliente encontrado";
            }
        })
        .catch(error => console.error('Erro ao buscar clientes:', error));


    var modal = document.getElementById("editModal");

    var span = document.getElementsByClassName("close")[0];

    span.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    document.getElementById('editForm').addEventListener('submit', salvarCliente);
});

function editarCliente(cliente) {
    console.log('Editar cliente:', cliente);
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
    // Removed TermoSorteio from here as per previous request
    // document.getElementById('editTermoSorteio').value = cliente.termoSorteio; 

    document.getElementById('editModal').style.display = "block";
}

function salvarCliente(event) {
    event.preventDefault();

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
    // TermoSorteio is not part of the edit form anymore, so it's not included in the body
    // const termoSorteio = document.getElementById('editTermoSorteio').value; 

    fetch('editar-cliente.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}&telefone=${telefone}&nome=${nome}&dt_nascimento=${dt_nascimento}&endereco=${endereco}&quadra=${quadra}&lote=${lote}&setor=${setor}&complemento=${complemento}&cidade=${cidade}&sexo=${sexo}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                document.getElementById('editModal').style.display = "none";
                window.location.reload();
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