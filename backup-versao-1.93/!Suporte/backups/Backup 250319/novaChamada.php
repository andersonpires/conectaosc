<?php
require_once 'checa-token.php';
require_once 'header.php';
require_once 'funcoes.php';
require_once 'conexao.php';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Chamada</title>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <style>
        #dataSelecionada {
            background-color: #fff;
            border: 1px solid #ccc;
            padding: 8px;
            border-radius: 4px;
        }

        .form-container {
            margin: 20px;
        }

        .results-container {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h1>Selecione os Dados</h1>
        <form id="chamadaForm">
            <label for="NomeCurso">Curso:</label>
            <select id="NomeCurso" name="NomeCurso">
                <!-- Options fetched dynamically from the database -->
                <?php
                $cursos = mysqli_query($conexao, "SELECT * FROM tbCurso");
                while ($curso = mysqli_fetch_assoc($cursos)) {
                    echo "<option value=\"{$curso['IdCurso']}\">{$curso['NomeCurso']}</option>";
                }
                ?>
            </select>

            <label for="NNomeTurma">Turma:</label>
            <select id="NNomeTurma" name="NNomeTurma">
                <!-- Options fetched dynamically based on the selected course -->
            </select>

            <label for="dataSelecionada">Data:</label>
            <input type="text" id="dataSelecionada" name="dataSelecionada">

            <button type="submit">Buscar</button>
        </form>
    </div>

    <div class="results-container" id="results">
        <!-- Results will be loaded here -->
    </div>

    <script>
        $(document).ready(function() {
            // Initialize datepicker
            $("#dataSelecionada").datepicker({
                dateFormat: 'yy-mm-dd'
            });

            // Fetch turmas dynamically based on the selected course
            $("#NomeCurso").change(function() {
                const cursoId = $(this).val();
                if (cursoId) { // Verifica se cursoId tem valor
                    $.ajax({
                        url: 'getTurmas.php', // Caminho correto para o arquivo
                        type: 'POST',
                        data: {
                            IdCurso: cursoId
                        }, // Nome do parâmetro está correto
                        success: function(response) {
                            $("#NNomeTurma").html(response); // Atualiza o select de turmas
                        },
                        error: function() {
                            $("#NNomeTurma").html('<option value="">Erro ao carregar turmas</option>'); // Em caso de erro
                        }
                    });
                } else {
                    $("#NNomeTurma").html('<option value="">Selecione um curso primeiro</option>');
                }
            });


            // Handle form submission via AJAX
            $("#chamadaForm").submit(function(event) {
                event.preventDefault();

                $.ajax({
                    url: 'fetch_chamada.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        $("#results").html(response);
                    }
                });
            });
        });
    </script>
</body>

</html>