<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cadastro";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT telefone, nome, dt_nascimento, endereco, quadra, lote, setor, complemento, cidade, sexo, termoSorteio FROM clientes";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<!DOCTYPE html>";
    echo "<html lang='pt-br'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<title>Lista de Clientes</title>";
    echo "<style>";
    echo "  body { font-family: sans-serif; }";
    echo "  table { border-collapse: collapse; width: 80%; margin: 20px auto; }";
    echo "  th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }";
    echo "  th { background-color: #f2f2f2; }";
    echo "  tr:nth-child(even) { background-color: #f2f2f2; }"; // Optional: Zebra-striping
    echo "</style>";
    echo "</head>";
    echo "<body>";
    echo "<h2>Lista de Clientes</h2>";
    echo "<table>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>Telefone</th>";
    echo "<th>Nome</th>";
    echo "<th>Data de Nascimento</th>";
    echo "<th>Endereço</th>";
    echo "<th>Quadra</th>";
    echo "<th>Lote</th>";
    echo "<th>Setor</th>";
    echo "<th>Complemento</th>";
    echo "<th>Cidade</th>";
    echo "<th>Sexo</th>";
    echo "<th>Termo Sorteio</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    // Output data of each row
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row["telefone"]. "</td>";
        echo "<td>" . $row["nome"]. "</td>";
        echo "<td>" . $row["dt_nascimento"]. "</td>";
        echo "<td>" . $row["endereco"]. "</td>";
        echo "<td>" . $row["quadra"]. "</td>";
        echo "<td>" . $row["lote"]. "</td>";
        echo "<td>" . $row["setor"]. "</td>";
        echo "<td>" . $row["complemento"]. "</td>";
        echo "<td>" . $row["cidade"]. "</td>";
        echo "<td>" . $row["sexo"]. "</td>";
        echo "<td>" . $row["termoSorteio"]. "</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    echo "</body>";
    echo "</html>";

} else {
    echo "Nenhum cliente encontrado.";
}
$conn->close();
?>