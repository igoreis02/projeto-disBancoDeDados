<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8");
?>