const verificarForm = document.getElementById('formulario');
  const mensagemDiv = document.getElementById('mensagem');
  const telefoneInput = document.getElementById('telefone');
  const senhaInput = document.getElementById('hidden');
  const pesquisaTelefoneButton = document.getElementById('pesquisaTelefone');
 
  telefoneInput.addEventListener('input', function() {
  if (telefoneInput.value === '62993997054') {
    senhaInput.style.display = 'block';
    pesquisaTelefoneButton.textContent = 'Verificar Senha';
  } else {
  pesquisaTelefoneButton.textContent = 'Próximo';
  }
  });
 
  verificarForm.addEventListener('submit', function(event) {
  event.preventDefault();
  const telefone = telefoneInput.value;
  const privacidade = document.getElementById('privacidade').checked;
 
  if (!privacidade) {
  mensagemDiv.textContent = 'Aceite os termos de privacidade.';
  mensagemDiv.style.color = 'red';
  return;
  }
 
  if (telefone === '62993997054' && senhaInput.style.display === 'block') {
  const senha = senhaInput.value;
    console.log(senha);
  // Aqui você faria a verificação da senha com o banco de dados
  // Esta parte precisa ser feita no servidor (PHP) por segurança
  fetch('verificar_senha.php', {
  method: 'POST',
  headers: {
  'Content-Type': 'application/x-www-form-urlencoded',
  },
  body: `telefone=<span class="math-inline">\{telefone\}&senha\=</span>{senha}`
  })
  .then(response => response.json())
  .then(data => {
  if (data.senhaCorreta) {
  window.location.href = 'lista_clientes.php'; // Redirecionar para a lista
  } else {
  mensagemDiv.textContent = 'Senha incorreta.';
  mensagemDiv.style.color = 'red';
  }
  })
  .catch(error => {
  console.error('Erro na requisição:', error);
  mensagemDiv.textContent = 'Erro ao verificar a senha.';
  mensagemDiv.style.color = 'red';
  });
  } else {
  // Restante do código para verificar o telefone e ir para cadastro.html
  fetch('verificar_telefone.php', {
  method: 'POST',
  headers: {
  'Content-Type': 'application/x-www-form-urlencoded',
  },
  body: `telefone=${telefone}`
  })
  .then(response => response.json())
  .then(data => {
  if (data.existe) {
  mensagemDiv.textContent = 'Telefone já cadastrado.';
  mensagemDiv.style.color = 'red';
  } else {
  window.location.href = `cadastro.html?telefone=${telefone}`;
  }
  })
  .catch(error => {
  console.error('Erro na requisição:', error);
  mensagemDiv.textContent = 'Erro ao verificar o telefone.';
  mensagemDiv.style.color = 'red';
  });
  }
  });