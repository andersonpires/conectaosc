<?php
$servername = "108.167.188.85";
$username = "kriade18_cadface";
$password = "F@ce1991!";
$dbname = "kriade18_cadface";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

$SexoBio = isset($_GET['SexoBio']) ? $_GET['SexoBio'] : '';
$bairro = isset($_GET['bairro']) ? $_GET['bairro'] : '';

$sql = "SELECT * FROM t_usuario WHERE 1=1";
if ($SexoBio != '') {
    $sql .= " AND SexoBio = '" . $SexoBio . "'";
}
if ($bairro != '') {
    $sql .= " AND Bairro = '" . $bairro . "'";
}

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Tipo</th><th>Foto</th><th>Nome</th><th>Sexo Biológico</th><th>Gênero</th><th>Nome Social</th><th>Nascimento</th><th>CPF/CNPJ</th><th>Identidade</th><th>Nome da Mãe</th><th>NIS</th><th>SUS</th><th>Título Eleitoral</th><th>Zona Eleitoral</th><th>Seção Eleitoral</th><th>Endereço</th><th>Bairro</th><th>Cidade</th><th>UF</th><th>GeoReferência</th><th>Telefone</th><th>WhatsApp</th><th>Email</th><th>Escolaridade</th><th>Profissão</th><th>Estado Civil</th><th>Nome Líder Comunitário</th><th>Nome Gerente</th><th>Observações</th><th>Ações</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row["IdUsuario"] . "</td>";
        echo "<td>" . $row["IdTipo"] . "</td>";
        echo "<td>"; // Foto
        if ($row["Foto"]) {
            echo '<img src="data:image/jpeg;base64,'.base64_encode($row["Foto"]).'" height="100" width="100"/>';
        }
        echo "</td>";
        echo "<td>" . $row["Nome"] . "</td>";
        echo "<td>" . $row["SexoBio"] . "</td>";
        echo "<td>" . $row["Genero"] . "</td>";
        echo "<td>" . $row["NomeSocial"] . "</td>";
        echo "<td>" . $row["Nascimento"] . "</td>";
        echo "<td>" . $row["CPF_CNPJ"] . "</td>";
        echo "<td>" . $row["Identidade"] . "</td>";
        echo "<td>" . $row["NomeMae"] . "</td>";
        echo "<td>" . $row["NIS"] . "</td>";
        echo "<td>" . $row["SUS"] . "</td>";
        echo "<td>" . $row["Titulo"] . "</td>";
        echo "<td>" . $row["Zona"] . "</td>";
        echo "<td>" . $row["Secao"] . "</td>";
        echo "<td>" . $row["Endereco"] . "</td>";
        echo "<td>" . $row["Bairro"] . "</td>";
        echo "<td>" . $row["Cidade"] . "</td>";
        echo "<td>" . $row["UF"] . "</td>";
        echo "<td>" . $row["GeoReferencia"] . "</td>";
        echo "<td>" . $row["Telefone"] . "</td>";
        echo "<td>" . $row["WhatsApp"] . "</td>";
        echo "<td>" . $row["Email"] . "</td>";
        echo "<td>" . $row["Escolaridade"] . "</td>";
        echo "<td>" . $row["Profissao"] . "</td>";
        echo "<td>" . $row["EstadoCivil"] . "</td>";
        echo "<td>" . $row["NomeLiderCom"] . "</td>";
        echo "<td>" . $row["NomeGerente"] . "</td>";
        echo "<td>" . $row["Obs"] . "</td>";

        // Botões de ação
        echo "<td>";
        echo "<a href='alterar.php?id=" . $row["IdUsuario"] . "'>Alterar</a> | ";
        echo "<button onclick='excluirRegistro(" . $row["IdUsuario"] . ")'>Excluir</button>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Nenhum registro encontrado.";
}

$conn->close();
?>
