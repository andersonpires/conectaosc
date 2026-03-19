<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header('Location: ' . rtrim((string) ($BASE_para_URL ?? ''), '/') . '/login/?redirect=' . $redirect_url);
    exit();
}

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

$cursoSelecionado = isset($_GET['NNomeCurso']) ? (int) $_GET['NNomeCurso'] : 0;
$turmaSelecionada = isset($_GET['NNomeTurma']) ? (int) $_GET['NNomeTurma'] : 0;
$dataSelecionada = isset($_GET['dataSelecionada']) ? trim((string) $_GET['dataSelecionada']) : '';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

    <style>
        #dataSelecionada {
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 8px 12px;
            width: 100%;
            cursor: pointer;
            color: #333;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        #dataSelecionada:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
    </style>

    <script>
        $(document).ready(function() {
            $.datepicker.setDefaults($.datepicker.regional['pt-BR'] = {
                closeText: 'Fechar',
                prevText: 'Anterior',
                nextText: 'Próximo',
                currentText: 'Hoje',
                monthNames: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
                ],
                monthNamesShort: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun',
                    'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'
                ],
                dayNames: ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira',
                    'Quinta-feira', 'Sexta-feira', 'Sábado'
                ],
                dayNamesShort: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
                dayNamesMin: ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'],
                weekHeader: 'Sm',
                dateFormat: 'dd/mm/yy',
                firstDay: 0,
                isRTL: false,
                showMonthAfterYear: false,
                yearSuffix: ''
            });

            const hoje = new Date();
            const dia = String(hoje.getDate()).padStart(2, '0');
            const mes = String(hoje.getMonth() + 1).padStart(2, '0');
            const ano = hoje.getFullYear();
            const dataHoje = `${dia}/${mes}/${ano}`;

            const $dataSelecionada = $('#dataSelecionada');
            const valorAtualData = ($dataSelecionada.val() || '').trim();
            $dataSelecionada.datepicker({
                dateFormat: 'dd/mm/yy',
                changeMonth: true,
                changeYear: true,
                yearRange: '-100:+10'
            });
            if (valorAtualData === '') {
                $dataSelecionada.val(dataHoje);
            }

            const $cursoSelect = $('#NomeCurso');
            const $turmaSelect = $('#NNomeTurma');
            const turmaSelecionada = String($turmaSelect.data('selected') || '');
            const cursoSelecionado = String($cursoSelect.val() || '');
            const cursoElement = document.getElementById('NomeCurso');
            if (cursoElement) {
                new TomSelect(cursoElement, {
                    create: false,
                    maxOptions: 500,
                    placeholder: 'Digite para buscar curso'
                });
            }

            function renderTurmas(turmas) {
                const options = ['<option value="">Selecione a turma</option>'];
                turmas.forEach(function(turma) {
                    const id = String(turma.IdTurma ?? turma.id ?? '').trim();
                    const nome = String(turma.NomeTurma ?? turma.nome ?? '').trim();
                    if (id !== '' && nome !== '') {
                        const selected = turmaSelecionada !== '' && turmaSelecionada === id ? ' selected' : '';
                        options.push(`<option value="${id}"${selected}>${nome}</option>`);
                    }
                });
                $turmaSelect.html(options.join(''));
            }

            function carregarTurmas(idCurso) {
                if (!idCurso) {
                    $turmaSelect.prop('disabled', true).html('<option value="">Selecione o curso primeiro</option>');
                    return;
                }

                $turmaSelect.prop('disabled', false).html('<option value="">Carregando turmas...</option>');
                $.ajax({
                    url: `<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/api/v1/turmas/por-curso`,
                    type: 'GET',
                    dataType: 'json',
                    xhrFields: {
                        withCredentials: true
                    },
                    data: {
                        IdCurso: idCurso,
                        somenteAtivos: 1
                    },
                    success: function(response) {
                        if (!response || response.success !== true || !Array.isArray(response.data)) {
                            $turmaSelect.html('<option value="">Nenhuma turma encontrada</option>');
                            return;
                        }
                        renderTurmas(response.data);
                    },
                    error: function() {
                        $.ajax({
                            url: '<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/get/getTurmasNome.php',
                            type: 'GET',
                            data: {
                                IdCurso: idCurso
                            },
                            success: function(data) {
                                $turmaSelect.prop('disabled', false).html(data);
                                if (turmaSelecionada !== '') {
                                    $turmaSelect.val(turmaSelecionada);
                                }
                            },
                            error: function() {
                                $turmaSelect.html('<option value="">Erro ao buscar turmas</option>');
                            }
                        });
                    }
                });
            }

            $turmaSelect.prop('disabled', true).html('<option value="">Selecione o curso primeiro</option>');
            $cursoSelect.on('change', function() {
                carregarTurmas($(this).val());
            });

            if (cursoSelecionado !== '') {
                carregarTurmas(cursoSelecionado);
            }
        });
    </script>
</head>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>

            <main class="content">
                <form action="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/chamada/lista/" enctype="multipart/form-data" method="GET">
                    <div class="col-12 col-md-3">
                        <label for="NomeCurso" class="form-label">Cursos</label>
                        <select class="form-select" aria-label="Default select example" id="NomeCurso" name="NNomeCurso">
                            <option value="">Selecione o curso</option>
                            <?php
                            $sql = 'SELECT IdCurso, NomeCurso FROM tbCurso WHERE Habilitado = 1 ORDER BY NomeCurso ASC';
                            $stmt = $pdo->query($sql);
                            while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $idCurso = (int) $dados['IdCurso'];
                                $nomeCurso = (string) $dados['NomeCurso'];
                                $selected = $cursoSelecionado === $idCurso ? ' selected' : '';
                                echo '<option value="' . $idCurso . '"' . $selected . '>' . htmlspecialchars($nomeCurso, ENT_QUOTES, 'UTF-8') . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <br>
                    <div class="col-12 col-md-3">
                        <label for="NNomeTurma" class="form-label">Turma</label>
                        <select class="form-select" aria-label="Default select example" id="NNomeTurma" name="NNomeTurma" data-selected="<?php echo htmlspecialchars((string) $turmaSelecionada, ENT_QUOTES, 'UTF-8'); ?>">
                            <option value="">Selecione o curso primeiro</option>
                        </select>
                    </div>
                    <br>
                    <label for="dataSelecionada" class="form-label">Data</label>
                    <div class="row">
                        <div class="col-10 col-md-3">
                            <input type="text" class="form-control" id="dataSelecionada" name="dataSelecionada" value="<?php echo htmlspecialchars($dataSelecionada, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Selecione a data" autocomplete="off">
                        </div>
                        <div class="col-2">
                            <button type="submit" id="turma-nome" class="btn btn-primary">Ir</button>
                        </div>
                    </div>
                    <br>
                </form>
            </main>

            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>
</body>
<script>
    function validarFormulario(event) {
        event.preventDefault();

        const nomeCurso = document.getElementById('NomeCurso').value;
        const nomeTurma = document.getElementById('NNomeTurma').value;
        const dataSelecionada = document.getElementById('dataSelecionada').value;

        if (nomeCurso === '' || nomeTurma === '' || !dataSelecionada) {
            alert('Por favor, selecione uma data, curso e turma antes de continuar.');
        } else {
            document.querySelector('form').submit();
        }
    }

    document.querySelector('button[type="submit"]').addEventListener('click', validarFormulario);
</script>

</html>
