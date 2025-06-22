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

    // Referências aos grupos de campos de imagem e quantidade
    const quantidadeGroup = document.getElementById('quantidadeGroup'); //
    const imagemGroup = document.getElementById('imagemGroup');     //

    // Referências diretas aos inputs de imagem e quantidade para required
    const inputImagem = document.getElementById('imagem');
    const inputQuantidade = document.getElementById('quantidade');

   
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

        // MUDANÇA AQUI: Aponta para o novo arquivo PHP de atualização de estoque
        fetch('atualizar_estoque_produto.php', {
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

    // Função para abrir o modal de adição de produto
    addProdutoBtn.addEventListener('click', () => {
        modal.style.display = 'block';
        document.getElementById('id').value = ''; // Limpa o ID para indicar nova adição
        form.reset(); // Limpa o formulário
        document.querySelector('#produtoModal h2').textContent = 'Adicionar Produto';

        // Oculta o campo de quantidade e mostra o campo de imagem para adição
        quantidadeGroup.style.display = 'none';
        inputQuantidade.required = false; // Remove o required quando oculto

        imagemGroup.style.display = 'block'; // Mostra o campo de imagem
        inputImagem.required = false; // Imagem não é obrigatória na adição (conforme HTML)
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
                        // Passa o id_produto para a função editarProduto
                        btnEditar.addEventListener('click', () => editarProduto(produto.id_produtos));
                        tdAcoes.appendChild(btnEditar);

                        let btnExcluir = document.createElement('button');
                        btnExcluir.textContent = 'Excluir';
                        btnExcluir.classList.add('delete-btn');
                        btnExcluir.addEventListener('click', () => excluirProduto(produto.id_produtos)); // Corrigido para id_produtos
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
    function editarProduto(id_produto) {
        // Primeiro, busca os dados do produto específico para preencher o formulário
        fetch(`buscar-produto.php?id=${id_produto}`) // CRIE ESTE ARQUIVO
            .then(response => response.json())
            .then(produto => {
                if (produto.success) {
                    modal.style.display = 'block';
                    document.getElementById('id').value = produto.data.id_produtos; // Preenche o ID
                    document.getElementById('nome').value = produto.data.nome;
                    document.getElementById('preco').value = produto.data.preco;
                    
                    // Oculta os campos de quantidade e imagem para edição e remove o atributo 'required'
                    quantidadeGroup.style.display = 'none';
                    inputQuantidade.required = false; // REMOVE O ATRIBUTO 'REQUIRED' PARA EDIÇÃO

                    imagemGroup.style.display = 'none';
                    inputImagem.required = false; // Garante que não é obrigatório ao editar

                    document.querySelector('#produtoModal h2').textContent = 'Editar Produto';
                } else {
                    alert(produto.message);
                }
            })
            .catch(error => console.error('Erro ao buscar produto para edição:', error));
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

        const id = document.getElementById('id').value; // Pega o ID (se estiver editando)
        const nome = document.getElementById('nome').value;
        const preco = document.getElementById('preco').value;
        // A quantidade e imagem só serão pegas se os campos estiverem visíveis
        const quantidade = inputQuantidade.value;
        const imagemFile = inputImagem.files[0];

        const formData = new FormData();
        formData.append('id', id);
        formData.append('nome', nome);
        formData.append('preco', preco);
        
        // Só adiciona quantidade e imagem se o grupo estiver visível
        if (quantidadeGroup.style.display !== 'none') {
            formData.append('quantidade', quantidade);
        }
        if (imagemGroup.style.display !== 'none' && imagemFile) {
            formData.append('imagem', imagemFile);
        }

        // Determina qual script PHP usar com base no ID
        const url = id ? 'editar-produto.php' : 'adicionar-produto.php';

        fetch(url, {
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