<?php
// Comentário ajustado para UTF-8.
require_once __DIR__ . '/../../../bootstrap/runtime.php';
$runtime = bootstrap_runtime();
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$appJsVersion = @filemtime($BASE_para_PATH . '/app/assets/js/app.js') ?: time();

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
} // 24 horas
session_regenerate_id(true);

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/repositories/RelatorioRepository.php';
require_once $BASE_para_PATH . '/api/services/RelatorioService.php';

$relatorioService = new \BackEnd\Services\RelatorioService(new \BackEnd\Repositories\RelatorioRepository());
$cursos = array_values(array_filter(
    $relatorioService->cursosOptions(1),
    static fn(array $row): bool => is_numeric((string)($row['value'] ?? ''))
));
$beneficiarioCadastroUrl = $BASE_para_URL . '/beneficiarios/cadastro';
$assetsFotosBaseUrl = rtrim(bootstrap_assets_img_url(), '/') . '/fotos';
$avatarPadraoUrl = bootstrap_foto_url('');
$cargoColaboradorPadrao = '';
$tipoSessao = trim((string)($_SESSION['Tipo'] ?? ''));
$podeEscolherAssinante = in_array($tipoSessao, ['Administrador', 'Superadministrador'], true);
$assinantesDisponiveis = [];
if (isset($pdo) && $pdo instanceof PDO) {
    $stmtCargoColaborador = $pdo->prepare('SELECT COALESCE(Cargo, "") AS Cargo FROM tbUser WHERE IdColaborador = ? LIMIT 1');
    $stmtCargoColaborador->execute([(int)($_SESSION['Cod'] ?? 0)]);
    $cargoColaboradorPadrao = trim((string)($stmtCargoColaborador->fetchColumn() ?: ''));
    if ($podeEscolherAssinante) {
        $stmtAssinantes = $pdo->query("
            SELECT IdColaborador, Nome, Sobrenome, COALESCE(Cargo, '') AS Cargo
              FROM tbUser
             WHERE COALESCE(Habilitado, 1) = 1
             ORDER BY Nome, Sobrenome
        ");
        $assinantesDisponiveis = array_map(static function (array $row): array {
            $id = (int)($row['IdColaborador'] ?? 0);
            $nome = trim((string)(($row['Nome'] ?? '') . ' ' . ($row['Sobrenome'] ?? '')));
            $cargo = trim((string)($row['Cargo'] ?? ''));

            return [
                'id' => $id,
                'nome' => $nome,
                'cargo' => $cargo,
                'label' => $cargo !== '' ? ($nome . ' - ' . $cargo) : $nome,
            ];
        }, $stmtAssinantes->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
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
            /* Comentário interno */
            white-space: nowrap;
            /* Comentário interno */
        }
        .relatorio-foto {
            width: 42px;
            height: 42px;
            object-fit: cover;
            border-radius: 50%;
        }

        .relatorio-beneficiario-link {
            color: inherit;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .relatorio-beneficiario-link:hover {
            text-decoration: underline;
        }

        .relatorio-data-link {
            color: inherit;
            text-decoration: underline;
            font-weight: 700;
        }

        .relatorio-data-link:hover {
            color: #f3e8ff;
        }

        .fj-popover-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: help;
            font-weight: 700;
            color: #0f172a;
        }

        .fj-popover-content {
            position: absolute;
            left: 50%;
            bottom: calc(100% + 10px);
            transform: translateX(-50%) translateY(6px);
            min-width: 240px;
            max-width: 340px;
            padding: .6rem .75rem;
            border-radius: 10px;
            background: #111827;
            color: #f9fafb;
            font-size: .82rem;
            line-height: 1.35;
            box-shadow: 0 12px 28px rgba(0, 0, 0, .28);
            opacity: 0;
            visibility: hidden;
            transition: opacity .18s ease, transform .18s ease, visibility .18s ease;
            z-index: 12;
            text-align: left;
            white-space: normal;
            pointer-events: none;
        }

        .fj-popover-content::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 100%;
            transform: translateX(-50%);
            border-width: 6px;
            border-style: solid;
            border-color: #111827 transparent transparent transparent;
        }

        .fj-popover-wrap:hover .fj-popover-content,
        .fj-popover-wrap:focus-within .fj-popover-content {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }

        #relatorio2 .table {
            border-collapse: separate;
            border-spacing: 0;
        }

        #relatorio2 .table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            background-color: rgb(83, 37, 126) !important;
            color: #fff;
        }

        .beneficiario-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1100;
            padding: 1rem;
        }

        .beneficiario-modal-overlay.show {
            display: flex;
        }

        .beneficiario-modal {
            width: min(520px, 100%);
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, .25);
        }

        .beneficiario-modal h3 {
            margin: 0 0 .35rem 0;
            font-size: 1.6rem;
            color: #1f2937;
        }

        .beneficiario-modal .status-chip {
            display: inline-block;
            margin-bottom: 1rem;
            padding: .25rem .7rem;
            border-radius: 999px;
            background: #d1fae5;
            color: #065f46;
            font-size: .85rem;
            font-weight: 600;
        }

        .beneficiario-modal .modal-info {
            color: #4b5563;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .beneficiario-modal .acoes {
            display: grid;
            gap: .75rem;
            margin-bottom: 1rem;
        }

        .beneficiario-modal .acao-btn {
            width: 100%;
            border: 0;
            border-radius: 12px;
            color: #fff;
            text-decoration: none;
            text-align: center;
            font-weight: 700;
            padding: .85rem 1rem;
            display: block;
        }

        .beneficiario-modal .acao-cadastro {
            background: #1f2f46;
        }

        .beneficiario-modal .acao-frequencia {
            background: #f59e0b;
        }

        .beneficiario-modal .acao-turma {
            background: #0f9d70;
        }

        .beneficiario-modal .fechar-btn {
            width: 100%;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            color: #374151;
            border-radius: 12px;
            padding: .75rem 1rem;
            font-weight: 600;
        }

        body.modal-acoes-open {
            overflow: hidden;
        }

        .pdf-assinatura-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1150;
            padding: 1rem;
        }

        .pdf-assinatura-overlay.show {
            display: flex;
        }

        .pdf-assinatura-modal {
            width: min(520px, 100%);
            background: #fff;
            border-radius: 16px;
            padding: 1.2rem 1.2rem 1.3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, .25);
        }

        .pdf-assinatura-acoes {
            display: grid;
            gap: .55rem;
            margin-top: .8rem;
        }

        .pdf-assinatura-cargo-wrap {
            display: none;
            margin-top: .55rem;
        }

        .pdf-assinatura-cargo-wrap.show {
            display: block;
        }

        .relatorio-frequencia-card {
            width: 100%;
        }

        .filtros-grid > [class*="col-"] {
            margin-bottom: .75rem;
        }

        #relatorio,
        #relatorio2 {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 767.98px) {
            html,
            body {
                white-space: normal;
                overflow-x: hidden;
            }

            .content .card-body {
                padding: 1rem .75rem;
            }

            .mb-3.d-flex.gap-3 {
                display: block !important;
            }

            .mb-3.d-flex.gap-3 .form-check {
                margin-bottom: .5rem;
            }

            .mb-3.d-flex.gap-3 .ms-auto.d-flex.gap-2 {
                margin-left: 0 !important;
                display: flex !important;
                flex-direction: column;
            }

            .mb-3.d-flex.gap-3 .ms-auto.d-flex.gap-2 .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

        <div class="main">
            <?php
            require_once $BASE_para_PATH . '/app/views/partials/topo.php';

            // Comentário ajustado para UTF-8.
            ?>

            <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>
            <main class="content">
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3">Gerar Relatório de Frequência</h1>
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
                                        <label for="mesAno" class="form-label">Mês/Ano:</label>
                                        <input type="month" id="mesAno" name="mesAno" class="form-control">
                                    </div>

                                    <div class="mb-3">
                                        <input class="form-check-input" type="checkbox" id="checkHabilitado" name="habilitado">
                                        <label class="form-check-label" for="checkHabilitado">
                                            Apenas alunos ativos e matriculados
                                        </label>
                                    </div>

                                    <button type="button" id="gerarRelatorio" class="btn btn-primary">Gerar Relatório</button>
                                    <button type="button" id="salvarPDF" style="display: none; " class="btn btn-danger">Salvar PDF</button>
                                    <button id="salvarExcel" style="display: none; " class="btn btn-success">Salvar Excel</button>

                                </form>
                            </div>
                        </div> -->
                        <div class="card relatorio-frequencia-card">
                            <div class="card-body">
                                <form id="filterForm2">
                                    <div class="mb-3">
                                        <div class="row filtros-grid">
                                            <div class="col-12 col-md-6 col-lg-3">
                                                <label for="curso2" class="form-label">Curso:</label>
                                                <select id="curso2" name="curso2" class="form-select">
                                                    <option value="">Selecione o Curso</option>
                                                    <?php foreach ($cursos as $curso): ?>
                                                        <option value="<?= htmlspecialchars((string)$curso['value'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$curso['label'], ENT_QUOTES, 'UTF-8') ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-12 col-md-6 col-lg-3">
                                                <label for="turma2" class="form-label">Turma:</label>
                                                <select id="turma2" name="turma2" class="form-select">
                                                    <option value="">Selecione um curso primeiro</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-6 col-lg-3">
                                                <label for="dataInicio" class="form-label">Data de Início:</label>
                                                <input type="date" id="dataInicio" name="dataInicio" class="form-control">
                                            </div>

                                            <div class="col-12 col-md-6 col-lg-3">
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

                                        <!-- Comentário interno -->
                                        <div class="ms-auto d-flex gap-2">
                                            <button type="button" id="gerarRelatorioInterval" class="btn btn-primary">Gerar Relatório</button>
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
                    <!-- Comentário interno -->
                </div>
                <div id="relatorio2">
                    <!-- Comentário interno -->
                </div>

                <div id="beneficiarioAcoesOverlay" class="beneficiario-modal-overlay" aria-hidden="true">
                    <div class="beneficiario-modal" role="dialog" aria-modal="true" aria-labelledby="beneficiarioModalNome">
                        <h3 id="beneficiarioModalNome">Beneficiário</h3>
                        <span class="status-chip">Selecionada</span>
                        <div class="modal-info">
                            <div id="beneficiarioModalLinha1"></div>
                            <div id="beneficiarioModalLinha2"></div>
                            <div id="beneficiarioModalLinha3"></div>
                        </div>
                        <div class="acoes">
                            <a id="beneficiarioBtnCadastro" class="acao-btn acao-cadastro" href="#" target="_blank" rel="noopener noreferrer">Ver/Alterar dados do beneficiário</a>
                            <a id="beneficiarioBtnFrequencia" class="acao-btn acao-frequencia" href="#" target="_blank" rel="noopener noreferrer">Acompanhar registro diário de frequência</a>
                            <a id="beneficiarioBtnTurma" class="acao-btn acao-turma" href="#" target="_blank" rel="noopener noreferrer">Informações sobre a turma</a>
                        </div>
                        <button type="button" class="fechar-btn" data-modal-close-beneficiario>Fechar</button>
                    </div>
                </div>

                <div id="pdfAssinaturaOverlay" class="pdf-assinatura-overlay" aria-hidden="true">
                    <div class="pdf-assinatura-modal" role="dialog" aria-modal="true" aria-labelledby="pdfAssinaturaTitulo">
                        <h5 id="pdfAssinaturaTitulo" class="mb-2">Gerar documento em PDF</h5>
                        <p class="mb-2 text-muted">Você deseja assinar digitalmente este relatório agora?</p>

                        <div class="form-check mb-1">
                            <input class="form-check-input pdf-opcao" type="checkbox" id="pdfOptCargoNome">
                            <label class="form-check-label" for="pdfOptCargoNome">Desejo adicionar o cargo ao nome?</label>
                        </div>
                        <div id="pdfCargoWrap" class="pdf-assinatura-cargo-wrap">
                            <label for="pdfCargoInput" class="form-label mb-1">Cargo para exibir junto ao nome</label>
                            <input type="text" id="pdfCargoInput" class="form-control" maxlength="120" value="<?= htmlspecialchars($cargoColaboradorPadrao, ENT_QUOTES, 'UTF-8') ?>" placeholder="Digite o cargo (opcional)">
                            <small class="text-muted">Essa alteração é apenas para este PDF e não altera o cadastro.</small>
                        </div>

                        <?php if ($podeEscolherAssinante): ?>
                        <div class="mt-3">
                            <label for="pdfAssinanteSelect" class="form-label mb-1">Assinante do documento</label>
                            <select id="pdfAssinanteSelect" class="form-select">
                                <?php foreach ($assinantesDisponiveis as $assinante): ?>
                                <option
                                    value="<?= (int)$assinante['id'] ?>"
                                    data-cargo="<?= htmlspecialchars((string)$assinante['cargo'], ENT_QUOTES, 'UTF-8') ?>"
                                    <?= (int)$assinante['id'] === (int)($_SESSION['Cod'] ?? 0) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars((string)$assinante['label'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Pesquise e escolha o colaborador que deve assinar este documento.</small>
                        </div>
                        <?php endif; ?>

                        <div class="pdf-assinatura-acoes">
                            <button type="button" class="btn btn-primary" id="pdfAssinarSim">Sim, assinar digitalmente</button>
                            <button type="button" class="btn btn-outline-secondary" id="pdfAssinarNao">Não, gerar sem assinatura</button>
                        </div>
                    </div>
                </div>

            </main>
            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>

            <script>
                // Comentário ajustado para UTF-8.
                $('#gerarRelatorio').click(function(e) {
                    e.preventDefault();

                    const curso = $('#curso').val();
                    const turma = $('#turma').val();
                    const mesAno = $('#mesAno').val();
                    const habilitado = $('#checkHabilitado').is(':checked') ? 1 : 0;
                    let getFrequencia1 = "<?php echo $BASE_para_URL ?>/get/getFrequencia.php";
                    $.post(getFrequencia1, {
                        curso,
                        turma,
                        mesAno,
                        habilitado
                    }, function(data) {
                        $('#relatorio').empty(); // Limpa a tabela antiga
                        $('#relatorio2').empty(); // Limpa a tabela antiga

                        // Cria a tabela dinamicamente
                        let table = '<table class="table"><thead><tr><th>Ordem</th><th>Beneficiário</th>';
                        const dias = [...new Set(data.map(d => d.Dia))].sort((a, b) => a - b);
                        dias.forEach(dia => table += `<th>${dia}</th>`);
                        table += '</tr></thead><tbody>';

                        const alunos = [...new Set(data.map(row => row.Aluno))];
                        alunos.forEach((aluno, index) => {
                            table += `<tr><td>${index + 1}</td><td>${aluno}</td>`;
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
                        // Comentário ajustado para UTF-8.
                        const observacao = `
                <p style="margin-top: 10px; font-style: italic;">
                    <strong>Obs.:</strong> P = Presença; F = Falta; FJ = Falta Justificada; e NA = Não se Aplica.
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

                // Comentário ajustado para UTF-8.
                $('#salvarPDF').click(function(e) {
                    e.preventDefault();

                    const curso = $('#curso').val();
                    const turma = $('#turma').val();
                    const mesAno = $('#mesAno').val();
                    const habilitado = $('#checkHabilitado').is(':checked') ? 1 : 0;
                    let getPDF = "<?php echo $BASE_para_URL ?>/get/";
                    // Abre o PDF em uma nova aba diretamente
                    const url = getPDF + `getPDF.php?curso=${curso}&turma=${turma}&mesAno=${mesAno}&habilitado=${habilitado}`;
                    window.open(url, '_blank');
                });
            </script>
            <script>
                // Pesquisa por período - na tela
                const abrirModalBeneficiario = (dados) => {
                    $('#beneficiarioModalNome').text(dados.nome || 'Beneficiário');
                    $('#beneficiarioModalLinha1').text(`${dados.cursoNome || 'Curso não informado'} • ${dados.turmaNome || 'Turma não informada'}`);
                    $('#beneficiarioModalLinha2').text(`Data de referência: ${dados.dataSelecionada || 'Não informada'}`);
                    $('#beneficiarioModalLinha3').text(`CPF: ${dados.cpfFormatado || 'Não informado'}`);

                    $('#beneficiarioBtnCadastro').attr('href', dados.urlCadastro || '#');
                    $('#beneficiarioBtnFrequencia').attr('href', dados.urlFrequencia || '#');
                    $('#beneficiarioBtnTurma').attr('href', dados.urlTurma || '#');

                    $('#beneficiarioAcoesOverlay').addClass('show').attr('aria-hidden', 'false');
                    $('body').addClass('modal-acoes-open');
                };

                const fecharModalBeneficiario = () => {
                    $('#beneficiarioAcoesOverlay').removeClass('show').attr('aria-hidden', 'true');
                    $('body').removeClass('modal-acoes-open');
                };

                $(document).on('click', '.js-beneficiario-modal', function(e) {
                    e.preventDefault();
                    const payload = $(this).attr('data-aluno');
                    if (!payload) {
                        window.location.href = $(this).attr('href');
                        return;
                    }
                    try {
                        const dados = JSON.parse(decodeURIComponent(payload));
                        abrirModalBeneficiario(dados);
                    } catch (error) {
                        window.location.href = $(this).attr('href');
                    }
                });

                $(document).on('click', '[data-modal-close-beneficiario]', function() {
                    fecharModalBeneficiario();
                });

                $(document).on('click', '#beneficiarioAcoesOverlay', function(e) {
                    if (e.target === this) {
                        fecharModalBeneficiario();
                    }
                });

                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape' && $('#beneficiarioAcoesOverlay').hasClass('show')) {
                        fecharModalBeneficiario();
                    }
                });

                $(document).on('click', '#beneficiarioAcoesOverlay .acao-btn', function() {
                    fecharModalBeneficiario();
                });

                $('#gerarRelatorioInterval').click(function(e) {
                    e.preventDefault();

                    const curso2 = $('#curso2').val();
                    const turma2 = $('#turma2').val();
                    const dataInicio = $('#dataInicio').val();
                    const dataFim = $('#dataFim').val();
                    const habilitado2 = $('#checkHabilitado2').is(':checked') ? 1 : 0;
                    const turmaInterval = $('#turmaInterval').is(':checked');

                    let getFrequencia = "<?php echo $BASE_para_URL ?>/get/getFrequenciaInterval.php";

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

                        const beneficiarioCadastroBaseUrl = <?php echo json_encode($beneficiarioCadastroUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
                        const faltasBaseUrl = <?php echo json_encode($BASE_para_URL . '/chamada/faltas', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
                        const chamadaListaBaseUrl = <?php echo json_encode($BASE_para_URL . '/chamada/lista/', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
                        const turmaInfoBaseUrl = <?php echo json_encode($BASE_para_URL . '/matriculas/turma', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
                        const fotosBaseUrl = <?php echo json_encode($assetsFotosBaseUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
                        const avatarPadraoUrl = <?php echo json_encode($avatarPadraoUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
                        const nomeCursoSelecionado = ($('#curso2 option:selected').text() || '').trim();
                        const nomeTurmaSelecionada = ($('#turma2 option:selected').text() || '').trim();

                        const escapeHtml = (value) => String(value ?? '')
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;');

                        const normalizeCpf = (cpf) => String(cpf ?? '').replace(/\D/g, '');
                        const normalizeIdNumerico = (valor) => {
                            const id = String(valor ?? '').replace(/\D/g, '');
                            return id === '' ? '' : id;
                        };
                        const formatarCpf = (cpf) => {
                            const valor = normalizeCpf(cpf);
                            if (valor.length !== 11) {
                                return valor;
                            }
                            return valor.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                        };

                        const isoParaDataBr = (dataIso) => {
                            if (!dataIso || !/^\d{4}-\d{2}-\d{2}$/.test(dataIso)) {
                                return '';
                            }
                            const [ano, mes, dia] = dataIso.split('-');
                            return `${dia}/${mes}/${ano}`;
                        };

                        const dataBrParaIso = (dataBr) => {
                            if (!dataBr || !/^\d{2}\/\d{2}\/\d{4}$/.test(dataBr)) {
                                return '';
                            }
                            const [dia, mes, ano] = dataBr.split('/');
                            return `${ano}-${mes}-${dia}`;
                        };

                        const anoCompleto = (valorAno) => {
                            const anoTexto = String(valorAno ?? '').trim();
                            if (/^\d{4}$/.test(anoTexto)) {
                                return anoTexto;
                            }
                            if (/^\d{2}$/.test(anoTexto)) {
                                return `20${anoTexto}`;
                            }
                            return '2000';
                        };

                        const dataFiltroPadrao = isoParaDataBr(dataInicio);

                        const fotoUrl = (foto) => {
                            const nomeArquivo = String(foto ?? '').trim();
                            if (!nomeArquivo) {
                                return avatarPadraoUrl;
                            }
                            if (/^https?:\/\//i.test(nomeArquivo) || nomeArquivo.startsWith('data:')) {
                                return nomeArquivo;
                            }
                            const partes = nomeArquivo.split(/[\\/]/);
                            const nomeNormalizado = partes[partes.length - 1];
                            return `${fotosBaseUrl}/${encodeURIComponent(nomeNormalizado)}`;
                        };

                        const beneficiarioUrl = (aluno) => {
                            const params = new URLSearchParams();
                            const cpf = normalizeCpf(aluno.cpf);
                            if (cpf.length === 11) {
                                params.set('cpf', cpf);
                            }
                            if (aluno.id) {
                                params.set('id', aluno.id);
                            }
                            const query = params.toString();
                            return query ? `${beneficiarioCadastroBaseUrl}?${query}` : beneficiarioCadastroBaseUrl;
                        };

                        const frequenciaUrl = (aluno) => {
                            const params = new URLSearchParams();
                            params.set('chamada', '1');
                            if (aluno.id) {
                                params.set('idAluno', aluno.id);
                            }
                            const dataSelecionada = aluno.dataSelecionada || dataFiltroPadrao;
                            if (dataSelecionada) {
                                params.set('dataSelecionada', dataSelecionada);
                            }
                            const cursoId = normalizeIdNumerico(aluno.cursoId);
                            if (cursoId) {
                                params.set('NNomeCurso', cursoId);
                            }
                            const turmaId = normalizeIdNumerico(aluno.turmaId);
                            if (turmaId) {
                                params.set('NNomeTurma', turmaId);
                            }
                            return `${faltasBaseUrl}?${params.toString()}`;
                        };

                        const turmaUrl = (aluno) => {
                            const params = new URLSearchParams();
                            const turmaRef = normalizeIdNumerico(aluno.turmaId) || aluno.turmaNome;
                            if (turmaRef) {
                                params.set('turma', turmaRef);
                            }
                            const query = params.toString();
                            return query ? `${turmaInfoBaseUrl}?${query}` : turmaInfoBaseUrl;
                        };

                        // Comentário ajustado para UTF-8.
                        const diasMap = new Map();
                        data.forEach((d) => {
                            const dia = String(d.Dia).padStart(2, '0');
                            const mes = String(d.Mes).padStart(2, '0');
                            const anoCompletoValor = anoCompleto(d.Ano);
                            const dataCompleta = `${dia}/${mes}/${anoCompletoValor}`;
                            const dataChave = `${dia}/${mes}/${String(anoCompletoValor).slice(-2)}`;
                            diasMap.set(dataChave, dataCompleta);
                        });

                        const diasUnicos = [...diasMap.keys()].sort((a, b) => {
                            const [da, ma, ya] = a.split('/').map(Number);
                            const [db, mb, yb] = b.split('/').map(Number);
                            return (ya - yb) || (ma - mb) || (da - db);
                        });

                        const cursoIdSelecionado = normalizeIdNumerico(curso2);
                        const turmaIdSelecionada = normalizeIdNumerico(turma2);
                        const linkListaPresenca = (dataCompleta) => {
                            if (!cursoIdSelecionado || !turmaIdSelecionada || !dataCompleta) {
                                return '';
                            }
                            const params = new URLSearchParams();
                            params.set('NNomeCurso', cursoIdSelecionado);
                            params.set('NNomeTurma', turmaIdSelecionada);
                            params.set('dataSelecionada', dataCompleta);
                            return `${chamadaListaBaseUrl}?${params.toString()}`;
                        };

                        // Mapear alunos por IdAluno
                        const alunosMap = new Map();

                        data.forEach(row => {
                            const id = row.IdAluno || row.IdUsuario; // suporte aos dois nomes
                            if (!alunosMap.has(id)) {
                                alunosMap.set(id, {
                                    id: id,
                                    nome: row.Aluno,
                                    cpf: row.CPF || '',
                                    foto: row.Foto || '',
                                    turmaNome: row.NomeTurma || nomeTurmaSelecionada || '',
                                    turmaId: normalizeIdNumerico(row.IdTurma || row.idTurma || row.IdTurmaMatricula || turma2),
                                    cursoNome: row.NomeCurso || nomeCursoSelecionado || '',
                                    cursoId: normalizeIdNumerico(row.IdCurso || row.idCurso || row.IdCursoMatricula || curso2),
                                    dataSelecionada: '',
                                    detalhes: {},
                                    frequencias: {},
                                    totalP: 0,
                                    totalF: 0,
                                    totalFJ: 0
                                });
                            }

                            const dataIso = `${anoCompleto(row.Ano)}-${String(row.Mes).padStart(2, '0')}-${String(row.Dia).padStart(2, '0')}`;
                            const alunoAtual = alunosMap.get(id);
                            const dataAtualIso = dataBrParaIso(alunoAtual.dataSelecionada);
                            if (!dataAtualIso || dataIso < dataAtualIso) {
                                alunoAtual.dataSelecionada = isoParaDataBr(dataIso);
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
                            alunosMap.get(id).detalhes[dataFormatada] = {
                                idChamada: row.IdChamada || '',
                                obs: String(row.Obs ?? '').trim(),
                                status: status
                            };

                        });

                        // Montar tabela
                        let table = '<table class="table"><thead><tr><th>Ordem</th><th>Beneficiário</th>';
                        table = table.replace('<th>Ordem</th>', '<th>Ordem</th><th>Foto</th>');
                        if (turmaInterval) table += '<th>Turma</th>';
                        table += '<th>Qtd P</th><th>Qtd F</th><th>Qtd FJ</th><th>Qtd Aulas</th>';
                        diasUnicos.forEach((dia) => {
                            const dataCompleta = diasMap.get(dia) || '';
                            const urlLista = linkListaPresenca(dataCompleta);
                            if (urlLista) {
                                table += `<th><a href="${escapeHtml(urlLista)}" class="relatorio-data-link" target="_blank" rel="noopener noreferrer">${dia}</a></th>`;
                                return;
                            }
                            table += `<th>${dia}</th>`;
                        });
                        table += '</tr></thead><tbody>';


                        let index = 1;
                        alunosMap.forEach((aluno, idAluno) => {
                            const nomeSeguro = escapeHtml(aluno.nome);
                            const turmaSegura = escapeHtml(aluno.turmaNome);
                            const fotoSegura = escapeHtml(fotoUrl(aluno.foto));
                            const linkSeguro = escapeHtml(beneficiarioUrl(aluno));
                            const dadosModal = encodeURIComponent(JSON.stringify({
                                nome: aluno.nome || '',
                                cpfFormatado: formatarCpf(aluno.cpf),
                                cursoNome: aluno.cursoNome || nomeCursoSelecionado || '',
                                turmaNome: aluno.turmaNome || nomeTurmaSelecionada || '',
                                dataSelecionada: aluno.dataSelecionada || dataFiltroPadrao,
                                urlCadastro: beneficiarioUrl(aluno),
                                urlFrequencia: frequenciaUrl(aluno),
                                urlTurma: turmaUrl(aluno)
                            }));
                            const dadosModalSeguro = escapeHtml(dadosModal);

                            table += `<tr><td>${index++}</td><td><img src="${fotoSegura}" class="relatorio-foto" alt="${nomeSeguro}" onerror="this.onerror=null;this.src='${escapeHtml(avatarPadraoUrl)}';"></td><td><a href="${linkSeguro}" class="relatorio-beneficiario-link js-beneficiario-modal" data-aluno="${dadosModalSeguro}">${nomeSeguro}</a></td>`;
                            if (turmaInterval) table += `<td>${turmaSegura}</td>`;
                            const totalAulas = aluno.totalP + aluno.totalF + aluno.totalFJ;
                            table += `<td>${aluno.totalP}</td><td>${aluno.totalF}</td><td>${aluno.totalFJ}</td><td>${totalAulas}</td>`;

                            diasUnicos.forEach(dia => {
                                const status = aluno.frequencias[dia] || 'NA';
                                const detalheDia = aluno.detalhes[dia] || null;
                                if (status === 'FJ' && detalheDia && detalheDia.obs) {
                                    const obsSegura = escapeHtml(detalheDia.obs);
                                    table += `<td><span class="fj-popover-wrap" tabindex="0">FJ<span class="fj-popover-content">Obs: ${obsSegura}</span></span></td>`;
                                    return;
                                }
                                if (status === 'FJ') {
                                    table += `<td><span class="fj-popover-wrap" tabindex="0">FJ<span class="fj-popover-content">Obs: Sem observação registrada.</span></span></td>`;
                                    return;
                                }
                                table += `<td>${status}</td>`;
                            });

                            table += `</tr>`;
                        });

                        table += '</tbody></table>';
                        $('#relatorio2').html(table);

                        // Comentário ajustado para UTF-8.
                        const obs = `
            <p style="margin-top: 10px; font-style: italic;">
                <strong>Obs.:</strong> P = Presença; F = Falta; FJ = Falta Justificada; e NA = Não se Aplica.
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



                // Pesquisa por período - PDF
                const pdfAssinaturaOverlay = $('#pdfAssinaturaOverlay');
                const pdfCargoWrap = $('#pdfCargoWrap');
                const pdfOptCargoNome = $('#pdfOptCargoNome');
                const pdfCargoInput = $('#pdfCargoInput');
                const pdfFrequenciaIntervalUrl = "<?php echo $BASE_para_URL ?>/relatorios/frequencia/pdf-intervalo";

                const abrirModalPdfAssinatura = () => {
                    pdfAssinaturaOverlay.addClass('show').attr('aria-hidden', 'false');
                    $('body').addClass('modal-acoes-open');
                };

                const fecharModalPdfAssinatura = () => {
                    pdfAssinaturaOverlay.removeClass('show').attr('aria-hidden', 'true');
                    $('body').removeClass('modal-acoes-open');
                };
                const pdfAssinanteSelect = $('#pdfAssinanteSelect');
                const podeEscolherAssinante = <?= $podeEscolherAssinante ? 'true' : 'false' ?>;
                let signerSelectControl = null;

                const getSignerIdSelecionado = () => {
                    if (!podeEscolherAssinante || !pdfAssinanteSelect.length) {
                        return '';
                    }
                    if (signerSelectControl) {
                        return String(signerSelectControl.getValue() || '').trim();
                    }
                    return String(pdfAssinanteSelect.val() || '').trim();
                };

                const getCargoAssinanteSelecionado = () => {
                    if (!podeEscolherAssinante || !pdfAssinanteSelect.length) {
                        return '';
                    }

                    const signerId = getSignerIdSelecionado();
                    if (signerId === '') {
                        return '';
                    }

                    const option = pdfAssinanteSelect.find(`option[value="${signerId}"]`);
                    return String(option.data('cargo') || '').trim();
                };

                const syncCampoCargoPdf = () => {
                    const ativo = !!pdfOptCargoNome.prop('checked');
                    if (ativo && podeEscolherAssinante && pdfAssinanteSelect.length) {
                        const cargoAssinante = getCargoAssinanteSelecionado();
                        if (cargoAssinante !== '' && String(pdfCargoInput.val() || '').trim() === '') {
                            pdfCargoInput.val(cargoAssinante);
                        }
                    }
                    pdfCargoWrap.toggleClass('show', ativo);
                };

                const abrirPdfIntervalo = (assinarDigitalmente) => {
                    const curso2 = $('#curso2').val();
                    const turma2 = $('#turma2').val();
                    const dataInicio = $('#dataInicio').val();
                    const dataFim = $('#dataFim').val();
                    const habilitado2 = $('#checkHabilitado2').is(':checked') ? 1 : 0;
                    const turmaInterval = $('#turmaInterval').is(':checked') ? 1 : 0;
                    const incluirCargo = pdfOptCargoNome.is(':checked') ? 1 : 0;
                    const cargoPersonalizado = incluirCargo ? String(pdfCargoInput.val() || '').trim() : '';

                    const params = new URLSearchParams({
                        curso2: String(curso2 ?? ''),
                        turma2: String(turma2 ?? ''),
                        dataInicio: String(dataInicio ?? ''),
                        dataFim: String(dataFim ?? ''),
                        habilitado: String(habilitado2),
                        turmaInterval: String(turmaInterval),
                        assinar: assinarDigitalmente ? '1' : '0',
                        incluir_cargo: String(incluirCargo),
                    });

                    if (incluirCargo && cargoPersonalizado !== '') {
                        params.set('cargo_personalizado', cargoPersonalizado);
                    }
                    if (podeEscolherAssinante && pdfAssinanteSelect.length) {
                        const signerIdSelecionado = getSignerIdSelecionado();
                        if (signerIdSelecionado !== '') {
                            params.set('signer_id', signerIdSelecionado);
                        }
                    }

                    const url = `${pdfFrequenciaIntervalUrl}?${params.toString()}`;
                    window.open(url, '_blank', 'noopener');
                };

                $('#salvarPDF2').click(function(e) {
                    e.preventDefault();
                    abrirModalPdfAssinatura();
                });

                pdfOptCargoNome.on('change', syncCampoCargoPdf);
                if (podeEscolherAssinante && pdfAssinanteSelect.length) {
                    signerSelectControl = new TomSelect('#pdfAssinanteSelect', {
                        create: false,
                        maxOptions: 300,
                        placeholder: 'Pesquise o colaborador que vai assinar'
                    });
                    signerSelectControl.on('change', function() {
                        if (pdfOptCargoNome.is(':checked')) {
                            pdfCargoInput.val(getCargoAssinanteSelecionado());
                        }
                    });
                }
                syncCampoCargoPdf();

                $('#pdfAssinarSim').on('click', function() {
                    abrirPdfIntervalo(true);
                    fecharModalPdfAssinatura();
                });

                $('#pdfAssinarNao').on('click', function() {
                    abrirPdfIntervalo(false);
                    fecharModalPdfAssinatura();
                });

                pdfAssinaturaOverlay.on('click', function(e) {
                    if (e.target === this) {
                        fecharModalPdfAssinatura();
                    }
                });

                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape' && pdfAssinaturaOverlay.hasClass('show')) {
                        fecharModalPdfAssinatura();
                    }
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
            let getTurmas = "<?php echo $BASE_para_URL ?>/get/getTurmas.php";
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
        let getTurmas = "<?php echo $BASE_para_URL ?>/get/getTurmas.php";
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
            let getTurmas = "<?php echo $BASE_para_URL ?>/get/getTurmas.php";
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
    function initCursoTomSelect(selector, placeholder) {
        const element = document.querySelector(selector);
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
            placeholder: placeholder,
        });
    }

    $('#curso2').change(function() {
        const idCurso = $(this).val();
        const turmaSelect = $('#turma2');
        turmaSelect.html('<option value="">Carregando turmas...</option>');

        if (!idCurso) {
            turmaSelect.html('<option value="">Selecione um curso primeiro</option>');
            return;
        }
        let getTurmas = "<?php echo $BASE_para_URL ?>/get/getTurmas.php";
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
        const getCursos = "<?php echo $BASE_para_URL ?>/get/getCursos.php";

        const cursoSelect = $('#curso');
        if (cursoSelect.length) {
            cursoSelect.html('<option value="">Carregando cursos...</option>');
            $.post(getCursos, { somenteAtivos }, function(data) {
                cursoSelect.html(data);
                initCursoTomSelect('#curso', 'Selecione o curso');
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
                initCursoTomSelect('#curso2', 'Selecione o curso');
                $('#turma2').html('<option value="">Selecione um curso primeiro</option>');
            }).fail(function() {
                cursoSelect2.html('<option value="">Erro ao carregar cursos</option>');
            });
        }
    }

    $('#filtroAtivos').on('change', function() {
        recarregarCursos();
    });

    $(document).ready(function() {
        initCursoTomSelect('#curso2', 'Selecione o curso');
    });
</script>

<script>
    // Comentário ajustado para UTF-8.
    function gerarExcel(dados, nomeArquivo) {
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(dados);

        // Ajustando largura das colunas
        ws['!cols'] = [{
                wch: 5
            },
            {
                wch: 25
            }, // Nome do Aluno
            ...dados[0].slice(2).map(() => ({
                wch: 10
            })) // Largura dos dias
        ];

        XLSX.utils.book_append_sheet(wb, ws, "Relatório");
        XLSX.writeFile(wb, nomeArquivo);
    }

    // Comentário ajustado para UTF-8.
    $('#salvarExcel').click(function(e) {
        e.preventDefault();

        const curso = $('#curso').val();
        const turma = $('#turma').val();
        const mesAno = $('#mesAno').val();
        const habilitado = $('#checkHabilitado').is(':checked') ? 1 : 0;

        let getExcelURL = "<?php echo $BASE_para_URL ?>/get/getEXCEL.php";

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

    // Comentário ajustado para UTF-8.
    $('#salvarExcel2').click(function(e) {
        e.preventDefault();

        const curso2 = $('#curso2').val();
        const turma2 = $('#turma2').val();
        const dataInicio = $('#dataInicio').val();
        const dataFim = $('#dataFim').val();
        const habilitado2 = $('#checkHabilitado2').is(':checked') ? 1 : 0;
        const turmaIntervalExcel = $('#turmaInterval').is(':checked') ? 1 : 0;

        let getExcelIntervalURL = "<?php echo $BASE_para_URL ?>/get/getEXCELInterval.php?format=json";

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
            dataType: 'json',
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









