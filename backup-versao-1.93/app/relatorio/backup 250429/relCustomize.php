<?php
session_set_cookie_params(['httponly' => true]);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

require_once $_SESSION['BASE_PATH'] . '/checa-token.php';
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $_SESSION['BASE_PATH'] . '/template/header.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            border: 1px solid #000 !important;
        }

        th,
        td {
            border: 1px solid #000 !important;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: rgb(83, 37, 126);
            color: white;
        }

        tr:nth-child(even) {
            background-color: rgb(205, 241, 213);
        }

        tr:nth-child(odd) {
            background-color: #fff;
        }

        .form-check-label {
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>

        <div class="main">
            <?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php'; ?>
            <main class="content">
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3">Relatório Personalizado de Alunos</h1>
                    <div class="card">
                        <div class="card-body">
                            <form id="customForm" class="mb-4">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label>Curso:</label>
                                        <select id="curso" name="curso" class="form-select">
                                            <option value="">Selecione</option>
                                            <?php
                                            $stmt = $pdo->query("SELECT IdCurso, NomeCurso FROM tbCurso ORDER BY NomeCurso");
                                            foreach ($stmt->fetchAll() as $curso) {
                                                echo "<option value='{$curso['IdCurso']}'>{$curso['NomeCurso']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label>Turma:</label>
                                        <select id="turma" name="turma" class="form-select">
                                            <option value="">Selecione um curso primeiro</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <div class="form-check">
                                            <input type="hidden" name="matriculasAtivas" value="0">
                                            <input type="checkbox" class="form-check-input" id="matriculasAtivas" name="matriculasAtivas">
                                            <label class="form-check-label" for="matriculasAtivas">Matrículas ativas</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label>Selecionar campos:</label><br>
                                    <div class="form-check form-check-inline">
                                        <input type="checkbox" name="colunas[]" value="Nome" class="form-check-input" id="colunaNome" checked>
                                        <label class="form-check-label" for="colunaNome">Nome</label>
                                    </div>
                                    <?php
                                    $opcoes = [
                                        'Nascimento' => 'Nascimento',
                                        'CPF' => 'CPF',
                                        'Endereco' => 'Endereço',
                                        'Bairro' => 'Bairro',
                                        'Cidade' => 'Cidade',
                                        'UF' => 'UF',
                                        'Telefone' => 'Telefone',
                                        'WhatsApp' => 'WhatsApp',
                                        'NomeCurso' => 'Curso',
                                        'NomeTurma' => 'Turma'
                                    ];
                                    foreach ($opcoes as $valor => $rotulo) {
                                        echo "<div class='form-check form-check-inline'>
                                    <input type='checkbox' name='colunas[]' value='$valor' class='form-check-input' id='coluna$valor'>
                                    <label class='form-check-label' for='coluna$valor'>$rotulo</label>
                                </div>";
                                    }
                                    ?>
                                    <div class="form-check form-check-inline">
                                        <button type="button" id="toggleCheckboxes" class="btn btn-sm btn-outline-secondary">Selecionar todos</button>
                                    </div>
                                </div>

                                <button type="button" id="gerarHtml" class="btn btn-primary">Gerar planilha na tela</button>
                                <button type="button" id="exportarExcel" class="btn btn-success">Exportar para Excel</button>
                            </form>
                        </div>
                    </div>
                    <div id="status"></div>
                    <div id="relatorioHTML"></div>
                </div>
            </main>
            <?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php'; ?>
        </div>
    </div>

    <script>
        $('#curso').change(function() {
            const idCurso = $(this).val();
            const turmaSelect = $('#turma');
            turmaSelect.html('<option value="">Carregando...</option>');
            if (!idCurso) {
                turmaSelect.html('<option value="">Selecione um curso</option>');
                return;
            }
            $.post('<?php echo $_SESSION['BASE_URL']; ?>/get/getTurmas.php', {
                IdCurso: idCurso
            }, function(data) {
                turmaSelect.html(data);
            }).fail(function() {
                turmaSelect.html('<option value="">Erro ao carregar</option>');
            });
        });

        $('#toggleCheckboxes').click(function() {
            const all = $('input[name="colunas[]"]');
            const allChecked = all.length === all.filter(':checked').length;
            all.prop('checked', !allChecked);
            $(this).text(allChecked ? 'Selecionar todos' : 'Desmarcar todos');
        });

        function gerarExcel(dados, nomeArquivo) {
            if (!Array.isArray(dados) || dados.length === 0) {
                alert("Nenhum dado recebido para gerar o Excel.");
                return;
            }
            const cabecalhos = Object.keys(dados[0]);
            const conteudo = dados.map(obj => cabecalhos.map(coluna => obj[coluna]));
            const aoa = [cabecalhos, ...conteudo];
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(aoa);
            XLSX.utils.book_append_sheet(wb, ws, "Relatório Personalizado");
            XLSX.writeFile(wb, nomeArquivo);
        }

        $('#exportarExcel').click(function() {
            const formData = $('#customForm').serialize();
            $.post('<?php echo $_SESSION['BASE_URL']; ?>/get/getCustomAlunoJson.php', formData, function(response) {
                gerarExcel(response, 'RelatorioCustomizado.xlsx');
            }, 'json').fail(function() {
                alert('Erro ao gerar relatório.');
            });
        });

        $('#gerarHtml').click(function() {
            const formData = $('#customForm').serialize();
            $.post('<?php echo $_SESSION['BASE_URL']; ?>/get/getCustomAlunoHTML.php', formData, function(html) {
                $('#relatorioHTML').html(html);
            }).fail(function() {
                alert('Erro ao gerar tabela.');
            });
        });
    </script>
</body>

</html>