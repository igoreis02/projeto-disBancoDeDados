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
 // Use um valor padrão para quantidade se não for fornecida (ex: 0)
 $quantidade = $_POST['quantidade'] ?? 0; // Se 'quantidade' não for postado, define como 0
 
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
 // Se a imagem não for enviada (porque o campo está oculto), $imagem permanece vazio.
 // Você pode verificar se a imagem já existe no caso de edição, mas para adicionar, vazio é o padrão.
 
 // Usando prepared statements para segurança
 $sql = "INSERT INTO produtos (nome, preco, quantidade, imagem) VALUES (?, ?, ?, ?)";
 $stmt = $conn->prepare($sql);
 $stmt->bind_param("sdis", $nome, $preco, $quantidade, $imagem); // s: string, d: double, i: integer, s: string
 
 if ($stmt->execute()) {
     echo json_encode(array("success" => true, "message" => "Produto adicionado com sucesso."));
 } else {
     echo json_encode(array("success" => false, "message" => "Erro ao adicionar produto: " . $stmt->error));
 }
 
 $stmt->close();
 $conn->close();
 ?>