<?php
session_start(); // Inicia a sessão (ou resume, se já estiver iniciada)
session_unset(); // Remove todas as variáveis de sessão registradas
session_destroy(); // Destrói a sessão atual

// Redireciona o usuário para a página de login
header('Location: login.html');
exit(); // Termina a execução do script
?>