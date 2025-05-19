<?php
 $servername = "localhost:3307";
 $username = "root";
 $password = "";
 $dbname = "cadastro";
 
 $conn = new mysqli($servername, $username, $password, $dbname);
 
 if ($conn->connect_error) {
     die("Erro na conexão com o banco de dados: " . $conn->connect_error);
 }
 
 $id = $_POST['id'];
 
 $sql = "DELETE FROM produtos WHERE id_produto = $id";
 
 if ($conn->query($sql) === TRUE) {
     echo json_encode(array("success" => true, "message" => "Produto excluído com sucesso."));
 } else {
     echo json_encode(array("success" => false, "message" => "Erro ao excluir produto: " . $conn->error));
 }
 
 $conn->close();
 ?>