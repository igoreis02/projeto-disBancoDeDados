document.addEventListener('DOMContentLoaded', function() {
    fetch('listar-clientes.php')
        .then(response => response.json())
        .then(clientes => {
            const tableBody = document.querySelector('#clientTable tbody');
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
                });
            } else {
                let row = tableBody.insertRow();
                let cell = row.insertCell();
                cell.colSpan = 11;
                cell.textContent = "Nenhum cliente encontrado";
            }
        })
        .catch(error => console.error('Erro ao buscar clientes:', error));
});