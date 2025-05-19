document.addEventListener('DOMContentLoaded', function () {
    const addProdutoBtn = document.getElementById('addProdutoBtn');
    const modal = document.getElementById('produtoModal');
    const closeBtn = document.querySelector('.close');
    const form = document.getElementById('produtoForm');
    const tableBody = document.querySelector('#productTable tbody');

    const entradaEstoqueBtn = document.getElementById('entradaEstoqueBtn');
    const entradaEstoqueModal = document.getElementById('entradaEstoqueModal');
    const closeEstoqueBtn = document.querySelector('.close-estoque');
    const entradaEstoqueForm = document.getElementById('entradaEstoqueForm');
    const produtoEstoqueSelect = document.getElementById('produtoEstoque');

   
    // Função para abrir o modal de entrada de estoque
    entradaEstoqueBtn.addEventListener('click', () => {
        entradaEstoqueModal.style.display = 'block';
        preencherSelectProdutos(); // Preenche o select com os produtos
    });

    // Função para fechar o modal de entrada de estoque
    closeEstoqueBtn.addEventListener('click', () => {
        entradaEstoqueModal.style.display = 'none';
    });

    window.addEventListener('click', (event) => {
        if (event.target === entradaEstoqueModal) {
            entradaEstoqueModal.style.display = 'none';
        }
    });

    // Função para preencher o select de produtos no modal de entrada de estoque
    function preencherSelectProdutos() {
        fetch('listar-produtos.php')
            .then(response => response.json())
            .then(produtos => {
                produtoEstoqueSelect.innerHTML = ''; // Limpa as opções existentes
                produtos.forEach(produto => {
                    const option = document.createElement('option');
                    option.value = produto.id_produtos;
                    option.textContent = produto.nome;
                    produtoEstoqueSelect.appendChild(option);
                    console.log(produto);
                });
            });
    }

    // Função para adicionar a entrada de estoque
    entradaEstoqueForm.addEventListener('submit', (event) => {
        event.preventDefault();

         const produtoId = produtoEstoqueSelect.value;
        const quantidadeAdicionar = parseInt(document.getElementById('quantidadeEstoque').value, 10);

        if (!produtoId || quantidadeAdicionar <= 0) {
            alert('Por favor, selecione um produto e insira uma quantidade válida.');
            return;
        }

        fetch('atualizar-estoque.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_produtos=${produtoId}&quantidade=${quantidadeAdicionar}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta do servidor: ' + response.status);
                }
                return response.text(); // Obtenha a resposta como texto
            })
            .then(data => {
                try {
                    const jsonData = JSON.parse(data); // Tente analisar como JSON
                    if (jsonData.success) {
                        alert(jsonData.message);
                        entradaEstoqueModal.style.display = 'none';
                        listarProdutos();
                    } else {
                        alert(jsonData.message);
                    }
                } catch (error) {
                    console.error("Erro ao analisar JSON:", error);
                }
            })
            .catch(error => console.error('Erro ao atualizar estoque:', error));
    });

    // Função para abrir o modal
    addProdutoBtn.addEventListener('click', () => {
        modal.style.display = 'block';
        document.getElementById('id').value = ''; // Limpa o ID
        document.getElementById('nome').value = '';
        document.getElementById('preco').value = '';
        document.getElementById('quantidade').value = '';
        document.getElementById('imagem').value = '';
        document.querySelector('#produtoModal h2').textContent = 'Adicionar Produto';
    });

    // Função para fechar o modal
    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Função para buscar e exibir os produtos
    function listarProdutos() {
        fetch('listar-produtos.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta do servidor: ' + response.status);
                }
                return response.json();
            })
            .then(produtos => {
                tableBody.innerHTML = '';

                if (produtos.length > 0) {
                    produtos.forEach(produto => {
                        let row = tableBody.insertRow();
                        row.insertCell().textContent = produto.nome;
                        row.insertCell().textContent = produto.preco;
                        row.insertCell().textContent = produto.quantidade;
                        let imgCell = row.insertCell();
                        if (produto.imagem) {
                            let img = document.createElement('img');
                            img.src = produto.imagem;
                            img.style.maxWidth = '50px'; // Ajuste o estilo conforme necessário
                            imgCell.appendChild(img);
                        } else {
                            imgCell.textContent = 'Sem Imagem';
                        }

                        let tdAcoes = row.insertCell();

                        let btnEditar = document.createElement('button');
                        btnEditar.textContent = 'Editar';
                        btnEditar.classList.add('edit-btn');
                        btnEditar.addEventListener('click', () => editarProduto(produto));
                        tdAcoes.appendChild(btnEditar);

                        let btnExcluir = document.createElement('button');
                        btnExcluir.textContent = 'Excluir';
                        btnExcluir.classList.add('delete-btn');
                        btnExcluir.addEventListener('click', () => excluirProduto(produto.id));
                        tdAcoes.appendChild(btnExcluir);
                    });
                } else {
                    let row = tableBody.insertRow();
                    let cell = row.insertCell();
                    cell.colSpan = 5;
                    cell.textContent = "Nenhum produto encontrado";
                }
            })
            .catch(error => console.error('Erro ao buscar produtos:', error));
    }

    // Função para editar um produto
    function editarProduto(produto) {
        modal.style.display = 'block';
        document.getElementById('id').value = produto.id_produto;
        document.getElementById('nome').value = produto.nome;
        document.getElementById('preco').value = produto.preco;
        document.getElementById('quantidade').value = produto.quantidade;
        document.querySelector('#produtoModal h2').textContent = 'Editar Produto';
    }

    // Função para excluir um produto
    function excluirProduto(id) {
        if (confirm("Tem certeza que deseja excluir este produto?")) {
            fetch('excluir-produto.php', {
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
                        listarProdutos(); // Atualiza a tabela após a exclusão
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Erro ao excluir produto:', error));
        }
    }

    // Função para salvar o produto (adicionar ou editar)
    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(form);

        fetch('adicionar-produto.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    modal.style.display = 'none';
                    listarProdutos(); // Atualiza a tabela após adicionar/editar
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Erro ao salvar produto:', error));
    });

    // Carregar produtos ao carregar a página
    listarProdutos();
});