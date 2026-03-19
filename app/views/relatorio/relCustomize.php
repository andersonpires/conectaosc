<?php
require_once __DIR__ . '/../../../bootstrap/runtime.php';
$runtime = bootstrap_runtime();
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$appJsVersion = @filemtime($BASE_para_PATH . '/app/assets/js/app.js') ?: time();

session_regenerate_id(true);
require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/repositories/RelatorioRepository.php';
require_once $BASE_para_PATH . '/api/services/RelatorioService.php';

$relatorioService = new \BackEnd\Services\RelatorioService(new \BackEnd\Repositories\RelatorioRepository());
$projetosDisponiveis = $relatorioService->projetos();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>
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

        #relatorioHTML {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 767.98px) {
            .content .card-body {
                padding: 1rem .75rem;
            }

            #customForm .row > [class*="col-"] {
                width: 100%;
                margin-bottom: .75rem;
            }

            #customForm .d-flex.gap-4 {
                display: block !important;
            }

            #customForm .d-flex.gap-4 .form-check {
                margin-bottom: .5rem;
            }

            #customForm .btn {
                width: 100%;
                margin-bottom: .5rem;
            }

            #customForm .dropdown-menu {
                width: 100%;
                max-width: 100%;
            }

            #customForm .form-check-inline {
                display: block;
                margin-right: 0;
                margin-bottom: .35rem;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
            <main class="content">
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3">Relatorio Personalizado de Alunos</h1>
                    <div class="card">
                        <div class="card-body">
                            <form id="customForm" class="mb-4">
                                                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label>Curso:</label>
                                        <select id="curso" name="curso" class="form-select">
                                            <option value="">Carregando cursos...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label>Turma:</label>
                                        <select id="turma" name="turma" class="form-select">
                                            <option value="">Selecione um curso primeiro</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <div class="d-flex gap-4">
                                            <div class="form-check">
                                                <input type="hidden" name="matriculasAtivas" value="0">
                                                <input type="checkbox" class="form-check-input" id="matriculasAtivas" name="matriculasAtivas">
                                                <label class="form-check-label" for="matriculasAtivas">Matriculas ativas</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="hidden" name="filtroAtivos" value="0">
                                                <input type="checkbox" class="form-check-input" id="filtroAtivos" name="filtroAtivos" checked>
                                                <label class="form-check-label" for="filtroAtivos">Cursos e turmas ativas</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label>Interesses:</label>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownInteresses" data-bs-toggle="dropdown" aria-expanded="false">
                                            Selecionar projetos
                                        </button>
                                        <div class="dropdown-menu p-3" aria-labelledby="dropdownInteresses" style="max-height: 320px; overflow-y: auto;">
                                            <?php
                                            foreach ($projetosDisponiveis as $projeto) {
                                                $idProjeto = htmlspecialchars((string)($projeto['IdProjeto'] ?? ''), ENT_QUOTES, 'UTF-8');
                                                $nomeProjeto = htmlspecialchars((string)($projeto['NomeProjeto'] ?? ''), ENT_QUOTES, 'UTF-8');
                                                echo "<div class='form-check'>
                                                    <input class='form-check-input' type='checkbox' name='interesses[]' value='{$idProjeto}' id='interesse{$idProjeto}'>
                                                    <label class='form-check-label' for='interesse{$idProjeto}'>{$nomeProjeto}</label>
                                                </div>";
                                            }
                                            ?>
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
                                        'Interesses' => 'Interesses',
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
                                        $checked = ($valor === 'Interesses') ? 'checked' : '';
                                        echo "<div class='form-check form-check-inline'>
                                    <input type='checkbox' name='colunas[]' value='$valor' class='form-check-input' id='coluna$valor' $checked>
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
                                <button type="button" id="salvarPdf" class="btn btn-danger">Salvar PDF</button>
                            </form>
                        </div>
                    </div>
                    <div id="status"></div>
                    <div id="relatorioHTML"></div>
                </div>
            </main>
            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>

<script>
    function initCursoTomSelect() {
        const element = document.getElementById('curso');
        if (!element || typeof TomSelect === 'undefined') {
            return;
        }

        if (element.tomselect) {
            element.tomselect.destroy();
        }

        new TomSelect(element, {
            create: false,
            allowEmptyOption: true,
            searchField: ['text'],
            placeholder: 'Selecione o curso',
        });
    }

    function somenteAtivos() {
        return $('#filtroAtivos').is(':checked') ? 1 : 0;
    }

    function resetTurmas(mensagem, disabled = true) {
        $('#turma')
            .prop('disabled', disabled)
            .html(`<option value="">${mensagem}</option>`);
    }

    function carregarTurmasPorCurso(idCurso) {
        const turmaSelect = $('#turma');

        if (!idCurso) {
            resetTurmas('Selecione um curso primeiro');
            return;
        }

        if (idCurso === 'todas') {
            turmaSelect.prop('disabled', false).html('<option value="todas">TODAS as turmas</option>');
            return;
        }

        if (idCurso === 'geral') {
            resetTurmas('Turma desabilitada', true);
            return;
        }

        turmaSelect.prop('disabled', false).html('<option value="">Carregando turmas...</option>');
        $.post('<?php echo $BASE_para_URL; ?>/get/getTurmas.php', {
            IdCurso: idCurso,
            somenteAtivos: somenteAtivos(),
            todasTurmas: 1
        }, function(data) {
            turmaSelect.prop('disabled', false).html(data);
        }).fail(function() {
            resetTurmas('Erro ao carregar turmas', false);
        });
    }

    function recarregarCursos() {
        const cursoSelect = $('#curso');
        const cursoAtual = cursoSelect.val();

        cursoSelect.html('<option value="">Carregando cursos...</option>');
        resetTurmas('Selecione um curso primeiro');

        $.post('<?php echo $BASE_para_URL; ?>/get/getCursosTodos.php', {
            somenteAtivos: somenteAtivos()
        }, function(data) {
            cursoSelect.html(data);
            initCursoTomSelect();

            if (cursoAtual && cursoSelect.find(`option[value="${cursoAtual}"]`).length) {
                cursoSelect.val(cursoAtual);
                if (cursoSelect[0] && cursoSelect[0].tomselect) {
                    cursoSelect[0].tomselect.setValue(cursoAtual, true);
                }
                carregarTurmasPorCurso(cursoAtual);
            }
        }).fail(function() {
            cursoSelect.html('<option value="">Erro ao carregar cursos</option>');
            resetTurmas('Erro ao carregar turmas');
        });
    }

    $('#curso').on('change', function() {
        carregarTurmasPorCurso($(this).val());
    });

    $('#filtroAtivos').on('change', function() {
        recarregarCursos();
    });

    $('#toggleCheckboxes').click(function() {
        const all = $('input[name="colunas[]"]');
        const allChecked = all.length === all.filter(':checked').length;
        all.prop('checked', !allChecked);
        $(this).text(allChecked ? 'Selecionar todos' : 'Desmarcar todos');
    });

    function gerarExcel(dados, nomeArquivo) {
        if (!Array.isArray(dados) || dados.length === 0) {
            alert('Nenhum dado recebido para gerar o Excel.');
            return;
        }
        const cabecalhos = Object.keys(dados[0]);
        const conteudo = dados.map(obj => cabecalhos.map(coluna => obj[coluna]));
        const aoa = [cabecalhos, ...conteudo];
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(aoa);
        XLSX.utils.book_append_sheet(wb, ws, 'Relatorio Personalizado');
        XLSX.writeFile(wb, nomeArquivo);
    }

    $('#exportarExcel').click(function() {
        const formData = $('#customForm').serialize();
        $.post('<?php echo $BASE_para_URL; ?>/get/getCustomAlunoJson.php', formData, function(response) {
            gerarExcel(response, 'RelatorioCustomizado.xlsx');
        }, 'json').fail(function() {
            alert('Erro ao gerar relatorio.');
        });
    });

    $('#gerarHtml').click(function() {
        const formData = $('#customForm').serialize();
        $.post('<?php echo $BASE_para_URL; ?>/get/getCustomAlunoHTML.php', formData, function(html) {
            $('#relatorioHTML').html(html);
        }).fail(function() {
            alert('Erro ao gerar tabela.');
        });
    });

    $('#salvarPdf').click(function() {
        const form = document.getElementById('customForm');
        const params = new URLSearchParams(new FormData(form));
        const url = '<?php echo $BASE_para_URL; ?>/get/getCustomAlunoPDF.php?' + params.toString();
        window.open(url, '_blank');
    });

    $(document).ready(function() {
        recarregarCursos();
    });
</script>
</body>

</html>






