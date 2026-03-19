<?php
// Inicializa?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o da sess?f?????T?f?????,????f???,?s?f??s?,?o e configura?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o de timezone
require_once __DIR__ . '/../../../bootstrap/runtime.php';
$runtime = bootstrap_runtime();
$BASE_PATH = $runtime['base_path'];
$BASE_URL = $runtime['base_url'];
$appJsVersion = @filemtime($BASE_PATH . '/app/assets/js/app.js') ?: time();

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
} // 24 horas
session_regenerate_id(true);

require_once $BASE_PATH . '/api/legacy/checa-token.php';
require_once $BASE_PATH . '/api/repositories/RelatorioRepository.php';
require_once $BASE_PATH . '/api/services/RelatorioService.php';

$relatorioService = new \BackEnd\Services\RelatorioService(new \BackEnd\Repositories\RelatorioRepository());
$cursos = array_values(array_filter(
    $relatorioService->cursosOptions(1),
    static fn(array $row): bool => is_numeric((string)($row['value'] ?? ''))
));
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_PATH . '/app/template/header.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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

        thead {
            text-align: center;
            color: white;
        }

        th,
        td {
            border: 1px solid #000 !important;
            padding: 10px;
            text-align: center !important;
            vertical-align: middle;
        }

        th {
            background-color: rgb(83, 37, 126) !important;

        }

        tr:nth-child(even) {
            background-color: rgb(205, 241, 213);
        }

        tr:nth-child(odd) {
            background-color: #fff;
        }

        .header2 {
            margin-bottom: 20px;
            text-align: center;
        }

        .header2 div {
            margin: 5px 0;
        }

        html,
        body {
            width: 100%;
            overflow-x: auto;
            /* Permite rolagem horizontal */
            white-space: nowrap;
            /* Impede que elementos se quebrem desnecessariamente */
        }

        #relatorio2 {
            display: block;
            width: 100%;
            max-width: 100%;
            max-height: calc(100vh - 400px);
            overflow-y: auto;
            overflow-x: auto;
            border: 1px solid #ccc;
        }


        table {
            width: 100%;
            min-width: max-content;
            /* Impede quebra for?f?????T?f?????,????f???,?s?f??s?,?ada */
            white-space: nowrap;
            /* Mant?f?????T?f?????,????f???,?s?f??s?,?m as colunas lado a lado */
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php require_once $BASE_PATH . '/app/template/menu.php'; ?>

        <div class="main">
            <?php
            require_once $BASE_PATH . '/app/template/topo.php';

            // Consulta os cursos e turmas dispon?f?????T?f?????,????f???,?s?f??s?,?veis
            ?>

            <script src="<?php echo $BASE_URL; ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>
            <main class="content">
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3">Gerar Relat?f?????T?f?????,????f???,?s?f??s?,?rio de Frequ?f?????T?f?????,????f???,?s?f??s?,?ncia</h1>
                    <div class="col-12 d-flex flex-wrap gap-3">

                        <!-- <div class="card">
                            <div class="card-body">
                                <form id="filterForm">
                                    <div class="mb-3">
                                        <label for="curso" class="form-label">Curso:</label>
                                        <select id="curso" name="curso" class="form-select">
                                            <option value="">Selecione o Curso</option>
                                            <?php foreach ($cursos as $curso): ?>
                                                <option value="<?= htmlspecialchars((string)$curso['value'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$curso['label'], ENT_QUOTES, 'UTF-8') ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="turma" class="form-label">Turma:</label>
                                        <select id="turma" name="turma" class="form-select">
                                            <option value="">Selecione um curso primeiro</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="mesAno" class="form-label">M?f?????T?f?????,????f???,?s?f??s?,?s/Ano:</label>
                                        <input type="month" id="mesAno" name="mesAno" class="form-control">
                                    </div>

                                    <div class="mb-3">
                                        <input class="form-check-input" type="checkbox" id="checkHabilitado" name="habilitado">
                                        <label class="form-check-label" for="checkHabilitado">
                                            Apenas alunos ativos e matriculados
                                        </label>
                                    </div>

                                    <button type="button" id="gerarRelatorio" class="btn btn-primary">Gerar Relat?f?????T?f?????,????f???,?s?f??s?,?rio</button>
                                    <button type="button" id="salvarPDF" style="display: none; " class="btn btn-danger">Salvar PDF</button>
                                    <button id="salvarExcel" style="display: none; " class="btn btn-success">Salvar Excel</button>

                                </form>
                            </div>
                        </div> -->
                        <div class="card">
                            <div class="card-body">
                                <form id="filterForm2">
                                    <div class="mb-3">
                                        <div class="row">
                                            <div class="col-4">
                                                <label for="curso2" class="form-label">Curso:</label>
                                                <select id="curso2" name="curso2" class="form-select">
                                                    <option value="">Selecione o Curso</option>
                                                    <?php foreach ($cursos as $curso): ?>
                                                        <option value="<?= htmlspecialchars((string)$curso['value'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$curso['label'], ENT_QUOTES, 'UTF-8') ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-4">
                                                <label for="turma2" class="form-label">Turma:</label>
                                                <select id="turma2" name="turma2" class="form-select">
                                                    <option value="">Selecione um curso primeiro</option>
                                                </select>
                                            </div>
                                            <div class="col-2">
                                                <label for="dataInicio" class="form-label">Data de In?f?????T?f?????,????f???,?s?f??s?,?cio:</label>
                                                <input type="date" id="dataInicio" name="dataInicio" class="form-control">
                                            </div>

                                            <div class="col-2">
                                                <label for="dataFim" class="form-label">Data de Fim:</label>
                                                <input type="date" id="dataFim" name="dataFim" class="form-control">
                                            </div>
                                        </div>

                                    </div>

                                    <div class="mb-3 d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="checkHabilitado2" name="habilitado2">
                                            <label class="form-check-label" for="checkHabilitado2">
                                                Apenas alunos ativos e matriculados
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="filtroAtivos" name="filtroAtivos" checked>
                                            <label class="form-check-label" for="filtroAtivos">
                                                Cursos e turmas ativas
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="turmaInterval" name="turmaInterval">
                                            <label class="form-check-label" for="turmaInterval">
                                                Inserir turma
                                            </label>
                                        </div>

                                        <!-- Grupo de bot?f?????T?f?????,????f???,?s?f??s?,?es alinhado ?f?????T?f?????,????f???,?s?f??s?,? direita -->
                                        <div class="ms-auto d-flex gap-2">
                                            <button type="button" id="gerarRelatorioInterval" class="btn btn-primary">Gerar Relat?f?????T?f?????,????f???,?s?f??s?,?rio</button>
                                            <button id="salvarPDF2" style="display: none;" class="btn btn-danger">Salvar PDF</button>
                                            <button id="salvarExcel2" style="display: none;" class="btn btn-success">Salvar Excel</button>
                                        </div>
                                    </div>




                                </form>
                            </div>
                        </div>
                    </div>
                </div>





                <div id="relatorio">
                    <!-- Dados da primeira pesquisa (por m?f?????T?f?????,????f???,?s?f??s?,?s), aparecer?f?????T?f?????,????f???,?s?f??s?,?o aqui -->
                </div>
                <div id="relatorio2">
                    <!-- Dados da segunda pesquisa (por per?f?????T?f?????,????f???,?s?f??s?,?odo), aparecer?f?????T?f?????,????f???,?s?f??s?,?o aqui -->
                </div>

            </main>
            <footer class="footer">
                <?php require_once $BASE_PATH . '/app/template/footer.php'; ?>
            </footer>

            <script>
                // Pesquisa por m?f?????T?f?????,????f???,?s?f??s?,?s - na tela
                $('#gerarRelatorio').click(function(e) {
                    e.preventDefault(); // Evita recarregar a p?f?????T?f?????,????f???,?s?f??s?,?gina

                    const curso = $('#curso').val();
                    const turma = $('#turma').val();
                    const mesAno = $('#mesAno').val();
                    const habilitado = $('#checkHabilitado').is(':checked') ? 1 : 0; // Verifica se o checkbox est?f?????T?f?????,????f???,?s?f??s?,? marcado
                    let getFrequencia1 = "<?php echo $BASE_URL ?>/get/getFrequencia.php";
                    $.post(getFrequencia1, {
                        curso,
                        turma,
                        mesAno,
                        habilitado
                    }, function(data) {
                        $('#relatorio').empty(); // Limpa a tabela antiga
                        $('#relatorio2').empty(); // Limpa a tabela antiga

                        // Cria a tabela dinamicamente
                        let table = '<table class="table"><thead><tr><th>Ordem</th><th>Benefici?f?????T?f?????,????f???,?s?f??s?,?rio</th>';
                        const dias = [...new Set(data.map(d => d.Dia))].sort((a, b) => a - b);
                        dias.forEach(dia => table += `<th>${dia}</th>`);
                        table += '</tr></thead><tbody>';

                        const alunos = [...new Set(data.map(row => row.Aluno))];
                        alunos.forEach((aluno, index) => {
                            table += `<tr><td>${index + 1}</td><td>${aluno}</td>`; // Adiciona a coluna "Ordem" e desloca "Benefici?f?????T?f?????,????f???,?s?f??s?,?rio"
                            dias.forEach(dia => {
                                const diaAtual = data.find(row => row.Aluno === aluno && row.Dia == dia);
                                let status = 'NA';
                                if (diaAtual) {
                                    if (diaAtual.presenca) status = 'P';
                                    else if (diaAtual.faltajust) status = 'FJ';
                                    else if (diaAtual.falta) status = 'F';
                                }
                                table += `<td>${status}</td>`;
                            });
                            table += '</tr>';
                        });

                        table += '</tbody></table>'; // Fechamento correto da tabela
                        $('#relatorio2').html(table); // Insere a nova tabela
                        // Adiciona a observa?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o abaixo da tabela
                        const observacao = `
                <p style="margin-top: 10px; font-style: italic;">
                    <strong>Obs.:</strong> P = Presen?f?????T?f?????,????f???,?s?f??s?,?a; F = Falta; FJ = Falta Justificada; e NA = N?f?????T?f?????,????f???,?s?f??s?,?o se Aplica.
                </p>`;
                        $('#relatorio').append(observacao);

                        $('#salvarPDF').show();
                        $('#salvarExcel').show();
                        $('#salvarPDF2').css('display', 'none');
                        $('#salvarExcel2').css('display', 'none');
                    }, 'json').fail(function() {
                        alert('Erro ao carregar os dados.');
                    });
                });

                // Pesquisa por m?f?????T?f?????,????f???,?s?f??s?,?s - em PDF
                $('#salvarPDF').click(function(e) {
                    e.preventDefault(); // Impede a recarga da p?f?????T?f?????,????f???,?s?f??s?,?gina

                    const curso = $('#curso').val();
                    const turma = $('#turma').val();
                    const mesAno = $('#mesAno').val();
                    const habilitado = $('#checkHabilitado').is(':checked') ? 1 : 0; // Verifica se o checkbox est?f?????T?f?????,????f???,?s?f??s?,? marcado
                    let getPDF = "<?php echo $BASE_URL ?>/get/";
                    // Abre o PDF em uma nova aba diretamente
                    const url = getPDF + `getPDF.php?curso=${curso}&turma=${turma}&mesAno=${mesAno}&habilitado=${habilitado}`;
                    window.open(url, '_blank');
                });
            </script>
            <script>
                // Pesquisa por periodo - na tela
                $('#gerarRelatorioInterval').click(function(e) {
                    e.preventDefault();

                    const curso2 = $('#curso2').val();
                    const turma2 = $('#turma2').val();
                    const dataInicio = $('#dataInicio').val();
                    const dataFim = $('#dataFim').val();
                    const habilitado2 = $('#checkHabilitado2').is(':checked') ? 1 : 0;
                    const turmaInterval = $('#turmaInterval').is(':checked');

                    let getFrequencia = "<?php echo $BASE_URL ?>/get/getFrequenciaInterval.php";

                    $.post(getFrequencia, {
                        curso2,
                        turma2,
                        dataInicio,
                        dataFim,
                        habilitado2,
                        turmaInterval
                    }, function(data) {
                        $('#relatorio').empty();
                        $('#relatorio2').empty();

                        // Criar conjunto de dias ?f?????T?f?????,????f???,?s?f??s?,?nicos (formatados como 'dd/mm/yy') e ordenados
                        const diasUnicos = [...new Set(data.map(d => {
                            const dia = String(d.Dia).padStart(2, '0');
                            const mes = String(d.Mes).padStart(2, '0');
                            const ano = String(d.Ano).slice(-2);
                            return `${dia}/${mes}/${ano}`;
                        }))].sort((a, b) => {
                            const [da, ma, ya] = a.split('/').map(Number);
                            const [db, mb, yb] = b.split('/').map(Number);
                            return (ya - yb) || (ma - mb) || (da - db);
                        });

                        // Mapear alunos por IdAluno
                        const alunosMap = new Map();

                        data.forEach(row => {
                            const id = row.IdAluno || row.IdUsuario; // suporte aos dois nomes
                            if (!alunosMap.has(id)) {
                                alunosMap.set(id, {
                                    nome: row.Aluno,
                                    turma: row.NomeTurma,
                                    frequencias: {},
                                    totalP: 0,
                                    totalF: 0,
                                    totalFJ: 0
                                });
                            }

                            const dataFormatada = `${String(row.Dia).padStart(2, '0')}/${String(row.Mes).padStart(2, '0')}/${String(row.Ano).slice(-2)}`;
                            let status = 'NA';
                            if (row.presenca == 1) {
                                status = 'P';
                                alunosMap.get(id).totalP++;
                            } else if (row.faltajust == 1) {
                                status = 'FJ';
                                alunosMap.get(id).totalFJ++;
                            } else if (row.falta == 1) {
                                status = 'F';
                                alunosMap.get(id).totalF++;
                            }
                            alunosMap.get(id).frequencias[dataFormatada] = status;

                        });

                        // Montar tabela
                        let table = '<table class="table"><thead><tr><th>Ordem</th><th>Benefici?f?????T?f?????,????f???,?s?f??s?,?rio</th>';
                        if (turmaInterval) table += '<th>Turma</th>';
                        table += '<th>Qtd P</th><th>Qtd F</th><th>Qtd FJ</th><th>Qtd Aulas</th>';
                        diasUnicos.forEach(dia => table += `<th>${dia}</th>`);
                        table += '</tr></thead><tbody>';


                        let index = 1;
                        alunosMap.forEach((aluno, idAluno) => {
                            table += `<tr><td>${index++}</td><td>${aluno.nome}</td>`;
                            if (turmaInterval) table += `<td>${aluno.turma}</td>`;
                            const totalAulas = aluno.totalP + aluno.totalF + aluno.totalFJ;
                            table += `<td>${aluno.totalP}</td><td>${aluno.totalF}</td><td>${aluno.totalFJ}</td><td>${totalAulas}</td>`;

                            diasUnicos.forEach(dia => {
                                const status = aluno.frequencias[dia] || 'NA';
                                table += `<td>${status}</td>`;
                            });

                            table += `</tr>`;
                        });

                        table += '</tbody></table>';
                        $('#relatorio2').html(table);

                        // Observa?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o
                        const obs = `
            <p style="margin-top: 10px; font-style: italic;">
                <strong>Obs.:</strong> P = Presen?f?????T?f?????,????f???,?s?f??s?,?a; F = Falta; FJ = Falta Justificada; e NA = N?f?????T?f?????,????f???,?s?f??s?,?o se Aplica.
            </p>`;
                        $('#relatorio2').append(obs);

                        $('#salvarPDF2').show();
                        $('#salvarExcel2').show();
                        $('#salvarPDF').hide();
                        $('#salvarExcel').hide();
                    }, 'json').fail(function() {
                        alert('Erro ao carregar os dados.');
                    });
                });



                // Pesquisa por periodo - PDF
                $('#salvarPDF2').click(function(e) {
                    e.preventDefault(); // Impede a recarga da p?f?????T?f?????,????f???,?s?f??s?,?gina

                    const curso2 = $('#curso2').val();
                    const turma2 = $('#turma2').val();
                    const dataInicio = $('#dataInicio').val(); // Data de in?f?????T?f?????,????f???,?s?f??s?,?cio selecionada
                    const dataFim = $('#dataFim').val(); // Data de fim selecionada
                    const habilitado2 = $('#checkHabilitado2').is(':checked') ? 1 : 0; // Verifica se o checkbox est?f?????T?f?????,????f???,?s?f??s?,? marcado
                    const turmaInterval = $('#turmaInterval').is(':checked') ? 1 : 0;
                    let getPDFInterval = "<?php echo $BASE_URL ?>/get/";

                    // Abre o PDF em uma nova aba diretamente
                    const url = getPDFInterval + `getPDFInterval.php?curso2=${curso2}&turma2=${turma2}&dataInicio=${dataInicio}&dataFim=${dataFim}&habilitado=${habilitado2}&turmaInterval=${turmaInterval}`;
                    window.open(url, '_blank');
                });
            </script>
</body>
<script>
    async function carregarTurmas(idCurso) {
        const turmaSelect = document.getElementById('turma');
        turmaSelect.innerHTML = '<option value="">Carregando turmas...</option>';

        if (!idCurso) {
            turmaSelect.innerHTML = '<option value="">Selecione um curso primeiro</option>';
            return;
        }

        try {
            const formData = new FormData();
            formData.append('IdCurso', idCurso);
            const somenteAtivos = document.getElementById('filtroAtivos')?.checked ? 1 : 0;
            formData.append('somenteAtivos', somenteAtivos);
            let getTurmas = "<?php echo $BASE_URL ?>/get/getTurmas.php";
            const response = await fetch(getTurmas, {
                method: 'POST',
                body: formData,
            });

            const turmasHtml = await response.text();
            turmaSelect.innerHTML = turmasHtml;
        } catch (error) {
            turmaSelect.innerHTML = '<option value="">Erro ao carregar turmas</option>';
            console.error('Erro ao carregar turmas:', error);
        }
    }
</script>
<script>
    $('#curso').change(function() {
        const idCurso = $(this).val();
        const turmaSelect = $('#turma');
        turmaSelect.html('<option value="">Carregando turmas...</option>');

        if (!idCurso) {
            turmaSelect.html('<option value="">Selecione um curso primeiro</option>');
            return;
        }
        let getTurmas = "<?php echo $BASE_URL ?>/get/getTurmas.php";
        $.post(getTurmas, {
            IdCurso: idCurso,
            somenteAtivos: $('#filtroAtivos').is(':checked') ? 1 : 0
        }, function(data) {
            turmaSelect.html(data);
        }).fail(function() {
            turmaSelect.html('<option value="">Erro ao carregar turmas</option>');
        });
    });
</script>
<script>
    async function carregarTurmas2(idCurso) {
        const turmaSelect = document.getElementById('turma2');
        turmaSelect.innerHTML = '<option value="">Carregando turmas...</option>';

        if (!idCurso) {
            turmaSelect.innerHTML = '<option value="">Selecione um curso primeiro</option>';
            return;
        }

        try {
            const formData = new FormData();
            formData.append('IdCurso', idCurso);
            const somenteAtivos = document.getElementById('filtroAtivos')?.checked ? 1 : 0;
            formData.append('somenteAtivos', somenteAtivos);
            let getTurmas = "<?php echo $BASE_URL ?>/get/getTurmas.php";
            const response = await fetch(getTurmas, {
                method: 'POST',
                body: formData,
            });

            const turmasHtml = await response.text();
            turmaSelect.innerHTML = turmasHtml;
        } catch (error) {
            turmaSelect.innerHTML = '<option value="">Erro ao carregar turmas</option>';
            console.error('Erro ao carregar turmas:', error);
        }
    }
</script>
<script>
    $('#curso2').change(function() {
        const idCurso = $(this).val();
        const turmaSelect = $('#turma2');
        turmaSelect.html('<option value="">Carregando turmas...</option>');

        if (!idCurso) {
            turmaSelect.html('<option value="">Selecione um curso primeiro</option>');
            return;
        }
        let getTurmas = "<?php echo $BASE_URL ?>/get/getTurmas.php";
        $.post(getTurmas, {
            IdCurso: idCurso,
            somenteAtivos: $('#filtroAtivos').is(':checked') ? 1 : 0
        }, function(data) {
            turmaSelect.html(data);
        }).fail(function() {
            turmaSelect.html('<option value="">Erro ao carregar turmas</option>');
        });
    });
</script>

<script>
    function recarregarCursos() {
        const somenteAtivos = $('#filtroAtivos').is(':checked') ? 1 : 0;
        const getCursos = "<?php echo $BASE_URL ?>/get/getCursos.php";

        const cursoSelect = $('#curso');
        if (cursoSelect.length) {
            cursoSelect.html('<option value="">Carregando cursos...</option>');
            $.post(getCursos, { somenteAtivos }, function(data) {
                cursoSelect.html(data);
                $('#turma').html('<option value="">Selecione um curso primeiro</option>');
            }).fail(function() {
                cursoSelect.html('<option value="">Erro ao carregar cursos</option>');
            });
        }

        const cursoSelect2 = $('#curso2');
        if (cursoSelect2.length) {
            cursoSelect2.html('<option value="">Carregando cursos...</option>');
            $.post(getCursos, { somenteAtivos }, function(data) {
                cursoSelect2.html(data);
                $('#turma2').html('<option value="">Selecione um curso primeiro</option>');
            }).fail(function() {
                cursoSelect2.html('<option value="">Erro ao carregar cursos</option>');
            });
        }
    }

    $('#filtroAtivos').on('change', function() {
        recarregarCursos();
    });
</script>

<script>
    // Fun?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o para exportar os dados para Excel
    function gerarExcel(dados, nomeArquivo) {
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(dados);

        // Ajustando largura das colunas
        ws['!cols'] = [{
                wch: 5
            }, // Coluna N?f?????T?f????s??.??f???,?s?f??s?,?
            {
                wch: 25
            }, // Nome do Aluno
            ...dados[0].slice(2).map(() => ({
                wch: 10
            })) // Largura dos dias
        ];

        XLSX.utils.book_append_sheet(wb, ws, "Relat?f?????T?f?????,????f???,?s?f??s?,?rio");
        XLSX.writeFile(wb, nomeArquivo);
    }

    // Evento para gerar Excel (Relat?f?????T?f?????,????f???,?s?f??s?,?rio Geral)
    $('#salvarExcel').click(function(e) {
        e.preventDefault();

        const curso = $('#curso').val();
        const turma = $('#turma').val();
        const mesAno = $('#mesAno').val();
        const habilitado = $('#checkHabilitado').is(':checked') ? 1 : 0;

        let getExcelURL = "<?php echo $BASE_URL ?>/get/getEXCEL.php";

        $.ajax({
            url: getExcelURL,
            type: 'GET',
            data: {
                curso: curso,
                turma: turma,
                mesAno: mesAno,
                habilitado: habilitado
            },
            dataType: 'json',
            success: function(response) {
                gerarExcel(response, 'RelatorioGeral.xlsx');
            },
            error: function(xhr, status, error) {
                console.error("Erro ao gerar Excel: ", error);
            }
        });
    });

    // Evento para gerar Excel (Relat?f?????T?f?????,????f???,?s?f??s?,?rio por Intervalo)
    $('#salvarExcel2').click(function(e) {
        e.preventDefault();

        const curso2 = $('#curso2').val();
        const turma2 = $('#turma2').val();
        const dataInicio = $('#dataInicio').val();
        const dataFim = $('#dataFim').val();
        const habilitado2 = $('#checkHabilitado2').is(':checked') ? 1 : 0;
        const turmaIntervalExcel = $('#turmaInterval').is(':checked') ? 1 : 0; // Verifica se o checkbox est?f?????T?f?????,????f???,?s?f??s?,? marcado

        let getExcelIntervalURL = "<?php echo $BASE_URL ?>/get/getEXCELInterval.php?format=json";

        $.ajax({
            url: getExcelIntervalURL,
            type: 'GET',
            data: {
                curso2: curso2,
                turma2: turma2,
                dataInicio: dataInicio,
                dataFim: dataFim,
                habilitado2: habilitado2,
                turmaInterval: turmaIntervalExcel
            },
            dataType: 'json', // Certifique-se que o PHP est?f?????T?f?????,????f???,?s?f??s?,? retornando JSON
            success: function(response) {
                gerarExcel(response, 'RelatorioIntervalar.xlsx');
            },
            error: function(xhr, status, error) {
                console.error("Erro ao gerar Excel: ", error);
            }
        });
    });
</script>

</html>







