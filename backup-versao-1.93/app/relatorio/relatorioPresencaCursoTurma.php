<?php
// relatorioPresencaCursoTurma.php
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
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
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
            vertical-align: middle;
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

        html,
        body {
            width: 100%;
            overflow-x: auto;
            white-space: nowrap;
        }

        #relatorioHTML {
            display: block;
            width: 100%;
            max-width: 100%;
            max-height: calc(100vh - 400px);
            overflow-y: auto;
            overflow-x: auto;
            border: 1px solid #ccc;
        }

        table {
            min-width: max-content;
            white-space: nowrap;
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
                    <h1 class="h3 mb-3">Relatório de Presença por Curso e Turma</h1>
                    <div class="card">
                        <div class="card-body">
                            <form id="filtroPresenca" class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label for="dataInicio" class="form-label">Data Início</label>
                                    <input type="date" class="form-control" name="dataInicio" id="dataInicio" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="dataFim" class="form-label">Data Fim</label>
                                    <input type="date" class="form-control" name="dataFim" id="dataFim" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="curso" class="form-label">Curso</label>
                                    <select name="curso" id="curso" class="form-select">
                                        <option value="">Selecione o curso</option>
                                        <?php
                                        $stmt = $pdo->query("SELECT IdCurso, NomeCurso FROM tbCurso ORDER BY NomeCurso");
                                        foreach ($stmt->fetchAll() as $curso) {
                                            echo "<option value='{$curso['IdCurso']}'>{$curso['NomeCurso']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="turma" class="form-label">Turmas</label>
                                    <select name="turma[]" id="turma" class="form-select" multiple disabled></select>
                                </div>
                                <div class="col-md-12">
                                    <label>Dias da Semana:</label><br>
                                    <?php
                                    $dias = ['DOM' => 'Dom', 'SEG' => 'Seg', 'TER' => 'Ter', 'QUA' => 'Qua', 'QUI' => 'Qui', 'SEX' => 'Sex', 'SAB' => 'Sáb'];
                                    foreach ($dias as $key => $label) {
                                        echo "<div class='form-check form-check-inline'>
                                            <input class='form-check-input' type='checkbox' name='dias[]' value='$key' id='chk$key'>
                                            <label class='form-check-label' for='chk$key'>$label</label>
                                        </div>";
                                    }
                                    ?>
                                    <button type="button" id="toggleDias" class="btn btn-sm btn-outline-secondary">Selecionar todos</button>
                                </div>
                                <div class="col-md-12 d-flex align-items-end justify-content-end">
                                    <button type="button" id="gerarHTML" class="btn btn-primary me-2">Gerar Tabela</button>
                                    <button type="button" id="gerarExcel" class="btn btn-success">Exportar Excel</button>
                                </div>
                            </form>
                            <div id="relatorioHTML"></div>
                        </div>
                    </div>
                </div>
            </main>
            <?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php'; ?>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            let toggle = true;
            $('#toggleDias').click(function() {
                $('input[name="dias[]"]').prop('checked', toggle);
                $(this).text(toggle ? 'Desmarcar todos' : 'Selecionar todos');
                toggle = !toggle;
            });

            $('#curso').change(function() {
                const idCurso = $(this).val();
                const turmaSelect = $('#turma');

                if (!idCurso) {
                    turmaSelect.prop('disabled', true).html('');
                    return;
                }

                $.post('<?php echo $_SESSION['BASE_URL']; ?>/get/getTurmasPorCursoHTML.php', {
                    IdCurso: idCurso
                }, function(data) {
                    turmaSelect.html(data).prop('disabled', false);
                    new TomSelect('#turma', {
                        placeholder: 'Selecione as turmas',
                        plugins: ['remove_button']
                    });
                });
            });

            $('#gerarHTML').click(function() {
                const dados = $('#filtroPresenca').serialize();
                $.post('<?php echo $_SESSION['BASE_URL']; ?>/get/getRelatorioPresencaCursoTurmaHTML.php', dados, function(html) {
                    $('#relatorioHTML').html(html);
                });
            });

            $('#gerarExcel').click(function() {
                const dados = $('#filtroPresenca').serialize();
                $.post('<?php echo $_SESSION['BASE_URL']; ?>/get/getRelatorioPresencaCursoTurmaJson.php', dados, function(json) {
                    if (!Array.isArray(json) || json.length === 0) {
                        alert("Nenhum dado encontrado.");
                        return;
                    }
                    const cabecalhos = Object.keys(json[0]);
                    const conteudo = json.map(obj => cabecalhos.map(col => obj[col]));
                    const aoa = [cabecalhos, ...conteudo];
                    const wb = XLSX.utils.book_new();
                    const ws = XLSX.utils.aoa_to_sheet(aoa);
                    XLSX.utils.book_append_sheet(wb, ws, "Presenças");
                    XLSX.writeFile(wb, "RelatorioPresencaCursoTurma.xlsx");
                }, 'json');
            });
        });
    </script>
</body>

</html>