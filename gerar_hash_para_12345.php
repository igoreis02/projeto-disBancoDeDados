<?php
// Não inclui conexão com banco aqui, apenas para gerar o hash
$senha_padrao = '12345';
$hash_senha_padrao = password_hash($senha_padrao, PASSWORD_DEFAULT);
echo "O HASH SEGURO para a senha '12345' é: <br><br>";
echo "<strong>" . htmlspecialchars($hash_senha_padrao) . "</strong>";
echo "<br><br>Copie este hash e use-o para atualizar a senha do usuário 'albert@souza.com.br' no seu banco de dados na tabela 'usuarios'.";
?>