const botaoPesquisaTelefone = document.querySelector("#pesquisaTelefone");
const aparecerSenha = document.getElementById('hidden');
const fomulario = document.querySelector("#formulario")

const clientes = [];


botaoPesquisaTelefone.addEventListener('click', (event) => {
    event.preventDefault();
    console.log(clientes);
    const form = document.querySelector("#formulario");

    const telefoneCliente = form.telefone.value

    console.log(telefoneCliente);

    if (telefoneCliente == 62993094343){
        aparecerSenha.style.display = 'block';
    }
})
/*
    if (clientes.length  == 0) {
        window.location = "cadastro.html";
        fomulario.addEventListener('submit', (event) => {
            const nome = form.nome.value;
            const dt_nascimento = form.dt_nascimento.value;
            const endereco = form.endereco.value;
            const quadra = form.quadra.value;
            const lote = form.lote.value;
            const setor = form.setor.value;
            const complemento = form.complemento.value;
            const cidade = form.cidade.value;
            const sexo = form.sexo.value;

            const dados = {
                telefone: telefoneCliente

            }
        })

    } else if (clientes.length > 0) {
       

    }

    console.log(clientes)
})

*/