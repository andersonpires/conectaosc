<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Relatório de Dados</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }

        h1 {
            color: #333;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }

        th, td {
            text-align: left;
            padding: 8px;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }

        td {
            background-color: #f2f2f2;
        }

        tr:nth-child(even) {
            background-color: #ddd;
        }

        tr:hover {
            background-color: #c1e1c1;
        }

        button {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 5px 10px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }

        button:hover {
            background-color: #d73833;
        }

        a {
            color: #4CAF50;
            text-decoration: none;
            font-size: 16px;
        }

        a:hover {
            text-decoration: underline;
        }

        select {
            padding: 8px;
            margin-right: 15px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
    </style>
    <script>
        function fetchFilteredData() {
            var SexoBio = document.getElementById('filtro-SexoBio').value;
            var bairro = document.getElementById('filtro-bairro').value;

            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById('tabela-dados').innerHTML = this.responseText;
                }
            };
            xhttp.open("GET", "fetch_data.php?SexoBio=" + SexoBio + "&bairro=" + bairro, true);
            xhttp.send();
        }

        function excluirRegistro(id) {
    if(confirm("Tem certeza que deseja excluir este registro?")) {
        window.location.href = 'excluir.php?id=' + id;
    }
}

    </script>
    <!-- Estilos adicionais aqui -->
</head>
<body>
    <h1>Relatório de Dados do Banco</h1>

    <?php
    $servername = "108.167.188.85";
    $username = "kriade18_cadface";
    $password = "F@ce1991!";
    $dbname = "kriade18_cadface";
    $port = 3306;

    // Criar conexão
    $conn = new mysqli($servername, $username, $password, $dbname, $port);

    // Verificar conexão
    if ($conn->connect_error) {
        die("Conexão falhou: " . $conn->connect_error);
    }

    // Buscar o total de registros
    $result = $conn->query("SELECT COUNT(*) AS total FROM t_usuario");
    $totalRow = $result->fetch_assoc();
    echo "<p>Total de Registros: " . $totalRow['total'] . "</p>";

    // Filtros
    echo "<select id='filtro-SexoBio' onchange='fetchFilteredData()'>";
    echo "<option value=''>Selecione o Sexo</option>";
    $SexoBioResult = $conn->query("SELECT DISTINCT SexoBio FROM t_usuario WHERE SexoBio IS NOT NULL");
    while ($row = $SexoBioResult->fetch_assoc()) {
        echo "<option value='" . $row['SexoBio'] . "'>" . $row['SexoBio'] . "</option>";
    }
    echo "</select>";

    echo "<select id='filtro-bairro' onchange='fetchFilteredData()'>";
    echo "<option value=''>Selecione um Bairro</option>";
    $bairroResult = $conn->query("SELECT DISTINCT Bairro FROM t_usuario WHERE Bairro IS NOT NULL");
    while ($row = $bairroResult->fetch_assoc()) {
        echo "<option value='" . $row['Bairro'] . "'>" . $row['Bairro'] . "</option>";
    }
    echo "</select>";

    // Tabela de dados inicial
    echo "<div id='tabela-dados'>";
    $sql = "SELECT * FROM t_usuario";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Tipo</th><th>Foto</th><th>Nome</th><th>Sexo Biológico</th><th>Gênero</th><th>Nome Social</th><th>Nascimento</th><th>CPF/CNPJ</th><th>Identidade</th><th>Nome da Mãe</th><th>NIS</th><th>SUS</th><th>Título Eleitoral</th><th>Zona Eleitoral</th><th>Seção Eleitoral</th><th>Endereço</th><th>Bairro</th><th>Cidade</th><th>UF</th><th>GeoReferência</th><th>Telefone</th><th>WhatsApp</th><th>Email</th><th>Escolaridade</th><th>Profissão</th><th>Estado Civil</th><th>Nome Líder Comunitário</th><th>Nome Gerente</th><th>Observações</th><th>Ações</th></tr>";
        while($row = $result->fetch_assoc()) {
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
    echo "</div>";

    $conn->close();
    ?>
</body>
</html>
       
