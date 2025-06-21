<?php
 $servername = "localhost";
 $username = "root";
 $password = "";
 $dbname = "cadastro";
 
 $conn = new mysqli($servername, $username, $password, $dbname);
 
 if ($conn->connect_error) {
     die("Erro na conexão com o banco de dados: " . $conn->connect_error);
 }
 
 $nome = $_POST['nome'];
 $preco = $_POST['preco'];
 $quantidade = $_POST['quantidade'];
 
 // Processamento do upload da imagem
 $imagem = "";
 if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
     $diretorio = "imagens/"; // Pasta onde as imagens serão salvas
     $nomeArquivo = uniqid() . "_" . $_FILES['imagem']['name']; // Nome único para o arquivo
     $caminhoArquivo = $diretorio . $nomeArquivo;
 
     if (move_uploaded_file($_FILES['imagem']['tmp_name'], $caminhoArquivo)) {
         $imagem = $caminhoArquivo; // Salva o caminho no banco de dados
     } else {
         echo json_encode(array("success" => false, "message" => "Erro ao fazer upload da imagem."));
         exit;
     }
 }
 
 $sql = "INSERT INTO produtos (nome, preco, quantidade, imagem) VALUES ('$nome', $preco, $quantidade, '$imagem')";
 
 if ($conn->query($sql) === TRUE) {
     echo json_encode(array("success" => true, "message" => "Produto adicionado com sucesso."));
 } else {
     echo json_encode(array("success" => false, "message" => "Erro ao adicionar produto: " . $conn->error));
 }
 
 $conn->close();
 ?>