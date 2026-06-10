<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
} // 24 horas
bootstrap_apply_php_runtime();

// Verifica se as variáveis de sessão BASE_para_PATH e BASE_para_URL estão definidas
if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    // Salva a URL atual para redirecionar o usuário após o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endereço atual
    header("Location: " . rtrim((string) ($BASE_para_URL ?? ''), '/') . "/login/?redirect=$redirect_url");
    exit(); // Garante que o código abaixo não será executado
}

// require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <?php
    $cod = (int)($_SESSION['Cod'] ?? 0);
    $tipoSessao = (string)($_SESSION['Tipo'] ?? '');
    require_once $BASE_para_PATH . '/app/views/partials/header.php';
    require_once __DIR__ . '/funcoes.php';
    require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
    ?>

    <style>
        html {
            scroll-behavior: smooth;
        }

        #dataSelecionada {
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 8px 12px;
            cursor: pointer;
            color: #333;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        #dataSelecionada:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }

        .search-container {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
        }

        #search-input {
            flex: 1;
            padding: 8px;
            font-size: 14px;
        }

        #clear-search {
            padding: 8px 12px;
            font-size: 14px;
            background-color: #f44336;
            color: #fff;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }

        #clear-search:hover {
            background-color: #d32f2f;
        }

        .tooltip-inner {
            background-color: rgba(0, 43, 85, 0.9) !important;
            /* azul escuro */
            color: #fff;
            font-size: 13px;
            padding: 6px 10px;
            border-radius: 5px;
        }

        .tooltip.bs-tooltip-top .tooltip-arrow::before {
            border-top-color: #002b55 !important;
        }

        .tooltip.bs-tooltip-bottom .tooltip-arrow::before {
            border-bottom-color: #002b55 !important;
        }

        .tooltip.bs-tooltip-start .tooltip-arrow::before {
            border-left-color: #002b55 !important;
        }

        .tooltip.bs-tooltip-end .tooltip-arrow::before {
            border-right-color: #002b55 !important;
        }

        .card-container .card {
            position: relative;
            overflow: visible;
        }

        .card-container .card.card-birthday {
            background-image:
                linear-gradient(rgba(255, 255, 255, .82), rgba(255, 255, 255, .82)),
                url('<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/assets/img/gif/confete-niver.gif');
            background-repeat: no-repeat, repeat;
            background-position: center center, center center;
            background-size: cover, cover;
        }

        .birthday-badge {
            position: absolute;
            top: 6px;
            right: 8px;
            z-index: 3;
            font-size: 2.35rem;
            line-height: 1;
            filter: drop-shadow(0 3px 6px rgba(0, 0, 0, .24));
            animation: birthdayPulse 1.1s ease-in-out infinite;
            transform-origin: center;
            pointer-events: none;
        }

        @keyframes birthdayPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.28);
            }
        }

        .birthday-info {
            margin: .25rem 0 .5rem;
            font-size: .9rem;
            font-weight: 600;
            color: #8a1800;
            text-align: center;
        }

        .plano-aula-card {
            border: 1px solid #dfe7f3;
            border-radius: .8rem;
            padding: .8rem;
            background: #f8fbff;
            margin-bottom: .7rem;
        }

        .plano-aula-card .meta {
            font-size: .82rem;
            color: #516178;
        }

        .plano-status-badge {
            display: inline-block;
            border-radius: 999px;
            padding: .15rem .55rem;
            font-size: .75rem;
            font-weight: 700;
        }

        .plano-status-realizada { background: #d6f6dc; color: #166534; }
        .plano-status-futura { background: #fff5cc; color: #7a5a00; }
        .plano-status-atrasada { background: #ffd9d9; color: #8a1d1d; }
        .plano-status-pendente { background: #e8edf7; color: #334155; }
        .plano-status-adiada { background: #eadcff; color: #4b2f91; }

        .plano-aula-anchor {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .chamada-actions-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: .9rem auto 1rem;
            padding: 0 .75rem;
            width: 100%;
        }

        .chamada-actions-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            flex-wrap: nowrap;
            max-width: 100%;
        }

        .chamada-actions-group .dropdown {
            min-width: 0;
        }

        .chamada-action-main {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            min-height: 42px;
            max-width: calc(100vw - 88px);
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
            font-weight: 600;
            white-space: nowrap;
        }

        .chamada-action-main:hover,
        .chamada-action-main:focus {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #1e293b;
        }

        .chamada-action-photo {
            width: 46px;
            min-width: 46px;
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f766e;
        }

        .chamada-action-photo:hover,
        .chamada-action-photo:focus {
            background: #ecfdf5;
            border-color: #99f6e4;
            color: #115e59;
        }

        .chamada-action-presenca {
            color: #16a34a;
            font-weight: 600;
        }

        .chamada-action-falta {
            color: #dc2626;
            font-weight: 600;
        }

        .chamada-action-redefinir {
            color: #b91c1c;
            font-weight: 600;
        }

        .chamada-action-redefinir.is-disabled {
            color: #64748b;
            cursor: not-allowed;
            opacity: .68;
        }

        .chamada-actions-group .dropdown-menu {
            border-color: #dbe3ef;
            box-shadow: 0 14px 35px rgba(15, 23, 42, .14);
        }

        .plano-topicos-box {
            border: 1px solid #dbe5f3;
            border-radius: .65rem;
            background: #fff;
            padding: .55rem;
        }

        .plano-topico-item {
            display: flex;
            align-items: flex-start;
            gap: .55rem;
            padding: .4rem .2rem;
            border-bottom: 1px dashed #e7edf8;
        }

        .plano-topico-item:last-child {
            border-bottom: 0;
            padding-bottom: .1rem;
        }

        .plano-topico-item .form-check-input {
            margin-top: .2rem;
            cursor: pointer;
        }

        .plano-topico-texto {
            flex: 1;
            font-size: .87rem;
            color: #1e293b;
            line-height: 1.3;
        }

        .plano-topico-texto.is-done {
            text-decoration: line-through;
            color: #64748b;
        }

        .plano-topico-meta {
            display: flex;
            gap: .35rem;
            align-items: center;
            margin-top: .15rem;
        }

        .plano-topico-cor {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            border: 1px solid rgba(15, 23, 42, .2);
            display: inline-block;
        }

        .chamada-view-toolbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: .35rem;
            margin: .75rem 0 .25rem;
        }

        .chamada-view-toggle {
            display: inline-flex;
            border: 1px solid #cbd5e1;
            border-radius: .45rem;
            overflow: hidden;
            background: #fff;
        }

        .chamada-view-toggle button {
            width: 42px;
            min-height: 40px;
            border: 0;
            background: #fff;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .chamada-view-toggle button.is-active,
        .chamada-view-toggle button[aria-pressed="true"] {
            background: #0d6efd;
            color: #fff;
        }

        .chamada-list-container {
            padding-left: .75rem;
            padding-right: .75rem;
        }

        .chamada-list-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .chamada-list-table {
            min-width: 760px;
            background: #fff;
        }

        .chamada-list-table th,
        .chamada-list-table td {
            vertical-align: middle;
        }

        .chamada-list-foto {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
        }

        .chamada-list-nome {
            min-width: 180px;
            font-weight: 600;
        }

        .chamada-list-table .button-container {
            display: flex;
            gap: .35rem;
            white-space: nowrap;
        }

        .chamada-list-table .button-container .btn {
            min-width: 38px;
        }

        @media (min-width: 768px) {
            .chamada-list-container {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }
        }
    </style>


    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo $BASE_para_URL; ?>/assets/css/turma-foto.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

</head>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>

            <main class="content">
                <?php
                // Recebe os dados enviados via POST
                $idBotao = $_GET['id'] ?? null;
                $dataSelecionada = $_GET['dataSelecionada'] ?? null;
                $NNomeCurso = $_GET['NNomeCurso'] ?? null;
                $NNomeTurma = $_GET['NNomeTurma'] ?? null;
                $Id__Curso = $_GET['NNomeCurso'] ?? null;
                $Id__Turma = $_GET['NNomeTurma'] ?? null;
                $viewChamada = isset($_GET['view']) && $_GET['view'] === 'list' ? 'list' : 'grid';
                $nomeCurso = '';
                $nomeTurma = '';
                $cursosAtivos = $pdo->query("SELECT IdCurso, NomeCurso FROM tbCurso WHERE Habilitado = 1 ORDER BY NomeCurso ASC")->fetchAll(PDO::FETCH_ASSOC);

                if ($NNomeCurso && $NNomeTurma) {
                    $sqlCursoTurma = "SELECT c.NomeCurso, t.NomeTurma
                      FROM tbCurso c
                      JOIN tbTurma t ON c.IdCurso = t.IdCurso
                      WHERE c.IdCurso = ? AND t.IdTurma = ?";
                    $stmtCursoTurma = $pdo->prepare($sqlCursoTurma);
                    $stmtCursoTurma->execute([$NNomeCurso, $NNomeTurma]);
                    $rowCursoTurma = $stmtCursoTurma->fetch(PDO::FETCH_ASSOC);
                    if ($rowCursoTurma) {
                        $nomeCurso = $rowCursoTurma['NomeCurso'];
                        $nomeTurma = $rowCursoTurma['NomeTurma'];
                    }
                }
                $resultado = turma_foto($pdo, $dataSelecionada, $dataSelecionada, $NNomeCurso, $NNomeTurma, (string)$BASE_para_URL, $viewChamada);
                $TotalCards = $resultado['totalCards'];
                $podeRedefinirChamada = chamadaUsuarioPodeRedefinir($_SESSION);
                ?>
                <div class="info-container">
                    <h2>Dados selecionados</h2>
                    <form id="dataForm" method="GET" action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/chamada/lista/">
                        <div class="mb-2">
                            <label for="NomeCurso"><strong>Curso:</strong></label>
                            <select id="NomeCurso" name="NNomeCurso" class="form-select">
                                <option value="">Selecione o curso</option>
                                <?php foreach ($cursosAtivos as $cursoItem): ?>
                                    <option value="<?= htmlspecialchars((string)$cursoItem['IdCurso'], ENT_QUOTES, 'UTF-8') ?>" <?= (string)$NNomeCurso === (string)$cursoItem['IdCurso'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string)$cursoItem['NomeCurso'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="NNomeTurma"><strong>Turma:</strong></label>
                            <select id="NNomeTurma" name="NNomeTurma" class="form-select" data-selected="<?= htmlspecialchars((string)$NNomeTurma, ENT_QUOTES, 'UTF-8') ?>">
                                <option value="">Selecione o curso primeiro</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="dataSelecionada"><strong>Data da chamada:</strong></label>
                            <input type="text" id="dataSelecionada" name="dataSelecionada" value="<?= htmlspecialchars($dataSelecionada) ?>" class="datepicker form-control" autocomplete="off">
                        </div>
                        <input type="hidden" id="viewChamadaInput" name="view" value="<?= htmlspecialchars($viewChamada, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" id="turma-nome" class="btn btn-primary">Ir</button>

                    </form>
                </div>

                <div class="chamada-actions-container" aria-label="Ações de chamada">
                    <div class="chamada-actions-group">
                        <div class="dropdown">
                            <button
                                class="btn chamada-action-main dropdown-toggle"
                                type="button"
                                id="dropdownOpcoesChamada"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <i class="bi bi-gear" aria-hidden="true"></i>
                                <span>Opções de Chamada</span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownOpcoesChamada">
                                <li>
                                    <button class="dropdown-item chamada-action-presenca" type="button" data-chamada-lote="P">
                                        <i class="bi bi-check2-all me-2" aria-hidden="true"></i>Presença Geral
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item chamada-action-falta" type="button" data-chamada-lote="F">
                                        <i class="bi bi-x-lg me-2" aria-hidden="true"></i>Falta Geral
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <?php if ($podeRedefinirChamada): ?>
                                        <button class="dropdown-item chamada-action-redefinir" type="button" data-chamada-lote="reset">
                                            <i class="bi bi-trash3 me-2" aria-hidden="true"></i>Redefinir Chamada
                                        </button>
                                    <?php else: ?>
                                        <span data-bs-toggle="tooltip" title="Permissão de Admin necessária">
                                            <button
                                                class="dropdown-item chamada-action-redefinir is-disabled"
                                                type="button"
                                                disabled>
                                                <i class="bi bi-lock me-2" aria-hidden="true"></i>Redefinir Chamada
                                            </button>
                                        </span>
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </div>

                        <button
                            class="btn chamada-action-photo"
                            type="button"
                            id="btn-baixar-fotos"
                            data-bs-toggle="tooltip"
                            title="Baixar fotos"
                            aria-label="Baixar fotos">
                            <i class="bi bi-camera" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="container">
                    <div class="row">
                        <label for="search-input" class="form-label">Procurar</label>
                        <div class="input-group">
                            <input type="text" id="search-input" class="form-control-lg" placeholder="Digite aqui..." oninput="handleSearchInput()" />

                            <div class="input-group-append">
                                <button id="clear-search" onclick="clearSearch()" class="btn btn-light ms-1">X</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="container">
                    <br>
                    <div class="row">
                        <!-- Botões de Filtro -->
                        <div id="filter-buttons" style="display: flex; gap: 10px; margin-bottom: 20px;">
                            <button id="btn-presenca" class="btn btn-light ms-1" style="flex: 1; background-color: rgb(0, 128, 0); color: white; padding: 10px; border: none; cursor: pointer;">Presença</button>
                            <button id="btn-falta" class="btn btn-light ms-1" style="flex: 1; background-color: rgb(255, 0, 0); color: white; padding: 10px; border: none; cursor: pointer;">Falta</button>
                            <button id="btn-falta-justificada" class="btn btn-light ms-1" style="flex: 1; background-color: rgb(204, 153, 0); color: white; padding: 10px; border: none; cursor: pointer;">Falta Just.</button>
                            <button id="btn-nenhum" class="btn btn-light ms-1" style="flex: 1; background-color: lightgray; color: black; padding: 10px; border: none; cursor: pointer;">
                                <i class="bi bi-lightbulb"></i> Nenhum
                            </button>
                            <a id="btn-plano-aula" class="btn btn-light ms-1 plano-aula-anchor" href="#plano-aula-do-dia" style="background-color: #e9f2ff; color: #0d3b66; padding: 10px 14px; border: none; cursor: pointer;" title="Ir para o plano da aula do dia" aria-label="Ir para o plano da aula do dia">
                                <i class="bi bi-journal-text"></i>
                            </a>
                        </div>
                    </div>
                    <div class="chamada-view-toolbar">
                        <div class="chamada-view-toggle" role="group" aria-label="Alternar visualização da chamada">
                            <button type="button"
                                    class="<?= $viewChamada === 'grid' ? 'is-active' : '' ?>"
                                    data-view="grid"
                                    aria-label="Visualização em bloco"
                                    aria-pressed="<?= $viewChamada === 'grid' ? 'true' : 'false' ?>"
                                    title="Visualização em bloco">
                                <i class="bi bi-grid-fill" aria-hidden="true"></i>
                            </button>
                            <button type="button"
                                    class="<?= $viewChamada === 'list' ? 'is-active' : '' ?>"
                                    data-view="list"
                                    aria-label="Visualização em lista"
                                    aria-pressed="<?= $viewChamada === 'list' ? 'true' : 'false' ?>"
                                    title="Visualização em lista">
                                <i class="bi bi-list-ul" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <p style="text-align: center;">Total de alunos na chamada: <?php echo $TotalCards ?></p>
                </div>
            </main>
            <div class="<?= $viewChamada === 'list' ? 'chamada-list-container' : 'card-container' ?>">
                <?php
                // Chama a função turma_foto passando a conexão PDO ($pdo) e a data selecionada.
                echo $resultado['html'];
                ?>
                <div class="modal fade" id="obsModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <img id="fotoaluno" src="" class="rounded-circle img-cover" width="40px" height="40px">
                                <h1 class="modal-title" id="exampleModalLabel">Observações</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="observacaoForm">
                                    <div class="mb-3">
                                        <label for="nomealuno" class="form-label">Nome aluno</label>
                                        <input type="text" class="form-control" id="nomealuno" name="nomealuno" required autocomplete="off" readonly>
                                        <input type="hidden" name="idaluno" id="idaluno">
                                        <input type="hidden" name="idmatricula" id="idmatricula">
                                        <input type="hidden" name="idchamada" id="idchamada">
                                    </div>
                                    <div class="mb-3">
                                        <label for="observacoes" class="form-label">Observações</label>
                                        <textarea rows="7" class="form-control" aria-label="observacoes" id="observacoes" name="observacoes" autocomplete="off"></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="button" class="btn btn-success" id="SalvaObs">Salvar alterações</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container mb-4" id="plano-aula-do-dia">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>Plano da aula do dia</strong>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRecarregarPlanoAula">Atualizar</button>
                    </div>
                    <div class="card-body">
                        <div id="planoAulaStatus" class="small text-muted mb-2">Selecione curso, turma e data para visualizar o plano.</div>
                        <div id="planoAulaContainer"></div>
                    </div>
                </div>
            </div>
            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>

    <!-- Modal para confirmar exclusão de presença -->
    <div class="modal fade" id="modalExcluirPresenca" tabindex="-1" aria-labelledby="modalExcluirPresencaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modalExcluirPresencaLabel">Atenção!</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    Deseja retirar o registro desse aluno neste dia?<br>
                    <strong>Todos os botões ficarão desativados.</strong>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="confirmarExclusao">Retirar registro</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalConfirmacaoLote" tabindex="-1" aria-labelledby="modalConfirmacaoLoteLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modalConfirmacaoLoteLabel">Confirmar ação</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body" id="modalConfirmacaoLoteMensagem"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="confirmarAcaoLote">Continuar</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal de mensagem de retorno -->
    <div class="modal fade" id="modalMensagem" tabindex="-1" aria-labelledby="modalMensagemLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modalMensagemLabel">Aviso</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body" id="conteudoMensagem">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>


    <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>
</body>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let dadosParaExcluir = null;

        getStudentCards().forEach(card => {
            card.addEventListener('dblclick', function(e) {
                e.preventDefault();

                // Verifica se ? um administrador
                <?php if ($tipoSessao === 'Administrador') : ?>

                    if (card.querySelector('.btn.btnp.selecionado')) {
                        // Extrai os dados do ID
                        const partes = card.id.split('_'); // card_IdMatricula_IdTurma_IdCurso_IdAluno
                        if (partes.length === 5) {
                            const [_, IdMatricula, IdTurma, IdCurso, IdAluno] = partes;

                            const dataSelecionada = document.getElementById('dataSelecionada')?.value || '';

                            // Armazena os dados
                            dadosParaExcluir = {
                                IdMatricula,
                                IdTurma,
                                IdCurso,
                                dataSelecionada
                            };

                            // Mostra o modal
                            const modal = new bootstrap.Modal(document.getElementById('modalExcluirPresenca'));
                            modal.show();
                        }
                    }

                <?php endif; ?>
            });
        });

        document.getElementById('confirmarExclusao').addEventListener('click', function() {
            if (!dadosParaExcluir) return;

            $.ajax({
                url: '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/chamada/salvar/',
                type: 'POST',
                data: {
                    action: 'deleteAula',
                    ...dadosParaExcluir
                },
                success: function(resposta) {
                    try {
                        const res = JSON.parse(resposta);

                        const modalExcluir = bootstrap.Modal.getInstance(document.getElementById('modalExcluirPresenca'));
                        if (modalExcluir) modalExcluir.hide();

                        // Mostra o modal de mensagem
                        const conteudo = document.getElementById('conteudoMensagem');
                        conteudo.innerText = res.message;

                        const modalMensagem = new bootstrap.Modal(document.getElementById('modalMensagem'));
                        modalMensagem.show();

                        if (res.success) {
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        }
                    } catch (err) {
                        alert("Erro ao interpretar a resposta do servidor.");
                    }
                },
                error: function() {
                    alert("Erro ao tentar remover o registro.");
                }
            });
        });

    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        $.datepicker.setDefaults($.datepicker.regional["pt-BR"] = {
            closeText: "Fechar",
            prevText: "Anterior",
            nextText: "Proximo",
            currentText: "Hoje",
            monthNames: ["Janeiro", "Fevereiro", "Marco", "Abril", "Maio", "Junho",
                "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"
            ],
            monthNamesShort: ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun",
                "Jul", "Ago", "Set", "Out", "Nov", "Dez"
            ],
            dayNames: ["Domingo", "Segunda-feira", "Terca-feira", "Quarta-feira",
                "Quinta-feira", "Sexta-feira", "Sabado"
            ],
            dayNamesShort: ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sab"],
            dayNamesMin: ["D", "S", "T", "Q", "Q", "S", "S"],
            weekHeader: "Sm",
            dateFormat: "dd/mm/yy",
            firstDay: 0,
            isRTL: false,
            showMonthAfterYear: false,
            yearSuffix: ""
        });

        // Configurar o datepicker
        $(".datepicker").datepicker({
            dateFormat: "dd/mm/yy",
            changeMonth: true,
            changeYear: true,
            yearRange: "-100:+10"
        });

        const cursoSelect = document.getElementById('NomeCurso');
        if (cursoSelect) {
            new TomSelect(cursoSelect, {
                create: false,
                maxOptions: 500,
                placeholder: 'Digite para buscar curso'
            });
        }
        const turmaSelect = document.getElementById('NNomeTurma');
        const renderTurmas = (turmas) => {
            if (!turmaSelect) return;
            const turmaSelecionadaAtual = (turmaSelect.dataset.selected || '').trim();
            const options = ['<option value="">Selecione a turma</option>'];
            turmas.forEach((turma) => {
                const id = String(turma.IdTurma ?? turma.id ?? '').trim();
                const nome = String(turma.NomeTurma ?? turma.nome ?? '').trim();
                if (id && nome) {
                    const selected = turmaSelecionadaAtual !== '' && turmaSelecionadaAtual === id ? ' selected' : '';
                    options.push(`<option value="${id}"${selected}>${nome}</option>`);
                }
            });
            turmaSelect.innerHTML = options.join('');
        };

        const carregarTurmas = (idCurso) => {
            if (!turmaSelect) return;
            if (!idCurso) {
                turmaSelect.disabled = true;
                turmaSelect.innerHTML = '<option value="">Selecione o curso primeiro</option>';
                return;
            }

            turmaSelect.disabled = false;
            turmaSelect.innerHTML = '<option value="">Carregando turmas...</option>';

            $.ajax({
                url: `<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/api/v1/turmas/por-curso`,
                type: 'GET',
                dataType: 'json',
                xhrFields: { withCredentials: true },
                data: { IdCurso: idCurso, somenteAtivos: 1 },
                success: function(response) {
                    if (!response || response.success !== true || !Array.isArray(response.data)) {
                        turmaSelect.innerHTML = '<option value="">Nenhuma turma encontrada</option>';
                        return;
                    }
                    renderTurmas(response.data);
                },
                error: function() {
                    $.ajax({
                        url: '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/get/getTurmasNome.php',
                        type: 'GET',
                        data: { IdCurso: idCurso },
                        success: function(data) {
                            turmaSelect.disabled = false;
                            turmaSelect.innerHTML = data;
                            const turmaSelecionadaAtual = (turmaSelect.dataset.selected || '').trim();
                            if (turmaSelecionadaAtual !== '') {
                                turmaSelect.value = turmaSelecionadaAtual;
                            }
                        },
                        error: function() {
                            turmaSelect.innerHTML = '<option value="">Erro ao buscar turmas</option>';
                        }
                    });
                }
            });
        };

        if (cursoSelect) {
            cursoSelect.addEventListener('change', function() {
                if (turmaSelect) {
                    turmaSelect.dataset.selected = '';
                }
                carregarTurmas(this.value);
            });

            if (cursoSelect.value) {
                carregarTurmas(cursoSelect.value);
            } else if (turmaSelect) {
                turmaSelect.disabled = true;
                turmaSelect.innerHTML = '<option value="">Selecione o curso primeiro</option>';
            }
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.chamada-view-toggle [data-view]').forEach(button => {
            button.addEventListener('click', function() {
                const view = this.dataset.view === 'list' ? 'list' : 'grid';
                localStorage.setItem('chamadaView', view);

                const url = new URL(window.location.href);
                url.searchParams.set('view', view);
                const currentHash = window.location.hash;
                window.location.href = `${url.pathname}${url.search}${currentHash}`;
            });
        });

        const viewInput = document.getElementById('viewChamadaInput');
        const currentUrl = new URL(window.location.href);
        if (viewInput && !currentUrl.searchParams.has('view')) {
            const savedView = localStorage.getItem('chamadaView');
            if (savedView === 'grid' || savedView === 'list') {
                viewInput.value = savedView;
                if (savedView !== '<?= $viewChamada ?>') {
                    currentUrl.searchParams.set('view', savedView);
                    window.location.replace(`${currentUrl.pathname}${currentUrl.search}${window.location.hash}`);
                }
            }
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = {
            presenca: document.getElementById('btn-presenca'),
            falta: document.getElementById('btn-falta'),
            faltaJustificada: document.getElementById('btn-falta-justificada')
        };

        const toggleButton = (button, color) => {
            if (button.style.backgroundColor === 'gray') {
                button.style.backgroundColor = color;
            } else {
                button.style.backgroundColor = 'gray';
            }

            // Reaplica o filtro para garantir que a visibilidade dos cards seja atualizada
            filterCardsButton();
        };


        const filterCardsButton = () => {
            const cards = getStudentCards();

            // Itera sobre todos os cards
            cards.forEach(card => {
                const selectedButton = card.querySelector('.selecionado');
                if (selectedButton) {
                    const style = selectedButton.style.backgroundColor;

                    card.style.display = (
                        (style === 'rgb(0, 128, 0)' && buttons.presenca.style.backgroundColor !== 'gray') ||
                        (style === 'rgb(255, 0, 0)' && buttons.falta.style.backgroundColor !== 'gray') ||
                        (style === 'rgb(204, 153, 0)' && buttons.faltaJustificada.style.backgroundColor !== 'gray')
                    ) ? '' : 'none'; // Caso contr?rio, esconde o card
                }
            });
        };


        Object.entries(buttons).forEach(([key, button]) => {
            const color = button.style.backgroundColor;
            button.addEventListener('click', () => {
                toggleButton(button, color);
                filterCardsButton();
            });
        });

        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>

<script>
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-center",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "3000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
</script>

<script>
    $(document).ready(function() {
        let dataSelecionada = "<?= $dataSelecionada ?>";

        $.ajax({
            url: '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/chamada/salvar/',
            type: 'POST',
            data: {
                action: 'loadData',
                dataSelecionada: dataSelecionada
            },
            success: function(response) {
                try {
                    let data = JSON.parse(response); // Tenta analisar a resposta como JSON

                    // Verifica se há um erro na resposta
                    if (data.erro) {
                        console.warn("Erro retornado pelo servidor:", data.erro);
                        alert(data.erro); // Mostra a mensagem de erro retornada
                        return; // Interrompe o processamento
                    }

                    // Processa os itens retornados pelo servidor
                    data.forEach(item => {
                        let idMatricula = item.IdMatricula;
                        if (item.presenca == "1") {
                            $(`#P-${idMatricula}`).addClass("selecionado").css('background-color', '#008000');
                        }
                        if (item.falta == "1") {
                            $(`#F-${idMatricula}`).addClass("selecionado").css('background-color', '#FF0000');
                        }
                        if (item.faltajust == "1") {
                            $(`#FJ-${idMatricula}`).addClass("selecionado").css('background-color', '#CC9900');
                        }
                        if (item.Obs !== null) {
                            $(`#Obs-${idMatricula}`).addClass("selecionado").css('background-color', 'darkblue');
                        } else {
                            $(`#Obs-${idMatricula}`).addClass("selecionado").css('background-color', '#a3a3a3');
                        }
                    });
                } catch (e) {
                    console.error('Erro ao processar os dados do servidor como JSON:', e);
                    alert('Erro inesperado ao processar a resposta do servidor.');
                }
                updateButtonCount();
            },
            error: function(xhr, status, error) {
                console.error("Erro na requisição AJAX:", status, error); // Mostra detalhes do erro
                alert('Ocorreu um erro ao carregar os dados.');
            }
        });

        $(document).on('click', '.btnp', function() {
            const buttonId = $(this).attr('id');

            // Extrai apenas as letras antes do traço (-)
            const action = buttonId.split('-')[0];

            const card = $(this).closest('.chamada-student-item');

            const id = card.attr('id'); // Exemplo: card_123_45_67

            // Divide o ID para obter os valores separados
            const data = id.split('_'); // [ "card", "123", "45", "67" ]
            const id__Matricula = data[1];
            const idTurma = data[2];
            const idCurso = data[3];
            const idAluno = data[4];
            const idChamada = data[5];
            let IdCod = "<?= $cod ?>";

            const NNomeCurso = "<?= addslashes($_GET['NNomeCurso'] ?? '') ?>";
            const NNomeTurma = "<?= addslashes($_GET['NNomeTurma'] ?? '') ?>";

            // insertDATA - Envia os dados ao banco
            $.ajax({
                url: '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/chamada/salvar/',
                type: 'POST',
                data: {
                    action: 'insertData',
                    id__Matricula: id__Matricula,
                    idAluno: idAluno,
                    idTurma: idTurma,
                    idCurso: idCurso,
                    idColaborador: IdCod,
                    dataSelecionada: dataSelecionada,
                    selectedAction: action
                },
                success: function(response) {
                    console.log('Resposta do servidor:', response);
                    toastr.success("Salvo!");

                    $.ajax({
                        url: '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/chamada/salvar/',
                        type: 'POST',
                        data: {
                            action: 'getFaltas',
                            id__Matricula: id__Matricula
                        },
                        success: function(faltasResponse) {
                            try {
                                let faltasData = JSON.parse(faltasResponse);
                                if (faltasData.totalFaltas !== undefined) {
                                    let link = `<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/chamada/faltas?idAluno=${idAluno}&dataSelecionada=${encodeURIComponent(dataSelecionada)}&NNomeCurso=${encodeURIComponent(NNomeCurso)}&NNomeTurma=${encodeURIComponent(NNomeTurma)}`;
                                    let h4 = card.find('h4');
                                    if (h4.length) {
                                        let nomeAluno = h4.clone().children().remove().end().text().trim(); // Remove link anterior
                                        h4.html(`${nomeAluno} <a href="${link}" class="text-decoration-none js-faltas-link">(${faltasData.totalFaltas} faltas)</a>`);
                                    } else {
                                        card.find('.js-faltas-link')
                                            .attr('href', link)
                                            .text(`${faltasData.totalFaltas} faltas`);
                                    }
                                }
                            } catch (e) {
                                console.error('Erro ao processar resposta:', e);
                                toastr.error("Erro ao atualizar faltas.", "Erro");
                            }
                        }
                    });
                },
                error: function() {
                    console.error('Erro na comunicação com o servidor.');
                    toastr.error("Erro na comunicação com o servidor.", "Erro");
                }
            });
        });

        const btnNenhum = document.getElementById('btn-nenhum');
        btnNenhum.addEventListener('click', function() {
            toggleNenhumButton(true);
        });

        document.querySelectorAll('[data-chamada-lote]').forEach(button => {
            button.addEventListener('click', function() {
                if (this.disabled) {
                    return;
                }
                executarAcaoLote(this.dataset.chamadaLote || '');
            });
        });
    });
</script>

<script>
    function getStudentCards() {
        return document.querySelectorAll('.chamada-student-item[id^="card_"]');
    }

    function getVisibleStudentCards() {
        return Array.from(getStudentCards()).filter(card => {
            const style = window.getComputedStyle(card);
            return style.display !== 'none' && style.visibility !== 'hidden';
        });
    }

    function getVisibleMatriculaIds() {
        return getVisibleStudentCards()
            .map(card => Number((card.id || '').split('_')[1] || 0))
            .filter(id => id > 0);
    }

    function confirmarAcaoLote(titulo, mensagemInicial, mensagemFinal, classeBotao) {
        return new Promise(resolve => {
            const modalEl = document.getElementById('modalConfirmacaoLote');
            const tituloEl = document.getElementById('modalConfirmacaoLoteLabel');
            const mensagemEl = document.getElementById('modalConfirmacaoLoteMensagem');
            const confirmarBtn = document.getElementById('confirmarAcaoLote');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            let etapa = 1;

            tituloEl.textContent = titulo;
            mensagemEl.textContent = mensagemInicial;
            confirmarBtn.textContent = 'Continuar';
            confirmarBtn.className = `btn ${classeBotao || 'btn-success'}`;

            const limpar = () => {
                confirmarBtn.removeEventListener('click', onConfirmar);
                modalEl.removeEventListener('hidden.bs.modal', onFechar);
            };

            const onFechar = () => {
                limpar();
                resolve(false);
            };

            const onConfirmar = () => {
                if (etapa === 1) {
                    etapa = 2;
                    mensagemEl.textContent = mensagemFinal;
                    confirmarBtn.textContent = 'Confirmar';
                    return;
                }

                limpar();
                modal.hide();
                resolve(true);
            };

            confirmarBtn.addEventListener('click', onConfirmar);
            modalEl.addEventListener('hidden.bs.modal', onFechar);
            modal.show();
        });
    }

    function aplicarEstadoLoteNoFront(acao) {
        const cards = getVisibleStudentCards();
        cards.forEach(card => {
            const botoesChamada = card.querySelectorAll('.btn[id^="P-"], .btn[id^="F-"], .btn[id^="FJ-"]');
            botoesChamada.forEach(botao => {
                botao.style.backgroundColor = '#a3a3a3';
                botao.classList.remove('selecionado');
            });

            if (acao === 'P') {
                const botaoPresenca = card.querySelector('.btn[id^="P-"]');
                if (botaoPresenca) {
                    botaoPresenca.style.backgroundColor = '#008000';
                    botaoPresenca.classList.add('selecionado');
                }
            }

            if (acao === 'F') {
                const botaoFalta = card.querySelector('.btn[id^="F-"]');
                if (botaoFalta) {
                    botaoFalta.style.backgroundColor = '#FF0000';
                    botaoFalta.classList.add('selecionado');
                }
            }
        });
        updateButtonCount();
    }

    async function executarAcaoLote(acao) {
        const idsMatricula = getVisibleMatriculaIds();
        if (!idsMatricula.length) {
            toastr.warning('Nenhum aluno visível foi encontrado para aplicar a ação.');
            return;
        }

        const total = idsMatricula.length;
        const config = {
            P: {
                action: 'bulkChamada',
                selectedAction: 'P',
                titulo: 'Confirmar presença geral',
                inicial: `Esta ação marcará presença para ${total} aluno(s) da listagem atual. Deseja continuar?`,
                final: 'Confirme novamente para salvar a presença geral.',
                botao: 'btn-success',
                sucesso: 'Presença geral salva com sucesso.'
            },
            F: {
                action: 'bulkChamada',
                selectedAction: 'F',
                titulo: 'Confirmar falta geral',
                inicial: `Esta ação marcará falta para ${total} aluno(s) da listagem atual. Deseja continuar?`,
                final: 'Confirme novamente para salvar a falta geral.',
                botao: 'btn-danger',
                sucesso: 'Falta geral salva com sucesso.'
            },
            reset: {
                action: 'resetChamada',
                selectedAction: '',
                titulo: 'Redefinir chamada',
                inicial: 'ATENÇÃO: Esta ação apagará todas as marcações salvas do dia de hoje. Confirmar redefinição?',
                final: 'Confirme novamente para redefinir a chamada da listagem atual.',
                botao: 'btn-danger',
                sucesso: 'Chamada redefinida com sucesso.'
            }
        }[acao];

        if (!config) {
            return;
        }

        const confirmado = await confirmarAcaoLote(config.titulo, config.inicial, config.final, config.botao);
        if (!confirmado) {
            return;
        }

        $.ajax({
            url: '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/chamada/salvar/',
            type: 'POST',
            data: {
                action: config.action,
                selectedAction: config.selectedAction,
                idsMatricula: idsMatricula,
                idCurso: '<?php echo addslashes((string)$NNomeCurso); ?>',
                idTurma: '<?php echo addslashes((string)$NNomeTurma); ?>',
                idColaborador: '<?php echo (int)$cod; ?>',
                dataSelecionada: document.getElementById('dataSelecionada')?.value || ''
            },
            success: function(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (!data.success) {
                        toastr.error(data.message || 'Não foi possível concluir a ação em lote.', 'Erro');
                        return;
                    }

                    aplicarEstadoLoteNoFront(acao);
                    toastr.success(data.message || config.sucesso);
                    setTimeout(() => {
                        window.location.reload();
                    }, 900);
                } catch (e) {
                    toastr.error('Erro inesperado ao processar a resposta do servidor.', 'Erro');
                }
            },
            error: function() {
                toastr.error('Erro na comunicação com o servidor.', 'Erro');
            }
        });
    }

    function alterarCor(botao, cor) {
        const card = botao.closest('.chamada-student-item');
        if (!card) return;
        const botoes = card.querySelectorAll('.btn');

        if (botao.id.includes('Obs')) {
        } else {
            botoes.forEach(b => {
                if (!b.id.includes('Obs')) {
                    b.style.backgroundColor = '#a3a3a3';
                    b.classList.remove('selecionado');
                }
            });
            const novaCor = getCorCSS(cor);
            if (botao.style.backgroundColor === novaCor) {
                botao.style.backgroundColor = '#a3a3a3';
                botao.classList.remove('selecionado');
            } else {
                botao.style.backgroundColor = novaCor;
                botao.classList.add('selecionado');
            }
        }
        updateButtonCount();
    }

    function getCorCSS(nomeCor) {
        const cores = {
            'verde': '#008000',
            'vermelho': '#FF0000',
            'amarelo': '#CC9900',
            'azulClaro': 'lightblue'
        };
        return cores[nomeCor] || '#d3d3d3';
    }

    function updateButtonCount() {
        let countPresenca = 0;
        let countFalta = 0;
        let countFaltaJustificada = 0;
        let totalCards = <?php echo $TotalCards; ?>;
        let countNenhum = 0;

        const cards = getStudentCards();

        cards.forEach(card => {
            // Verifica se há botão de "Presença", "Falta" ou "Falta Justificada"
            const presencaButton = card.querySelector('.btn[id^="P-"]');
            const faltaButton = card.querySelector('.btn[id^="F-"]');
            const faltaJustificadaButton = card.querySelector('.btn[id^="FJ-"]');

            // Conta os cards com "Presença" selecionada (cor verde)
            if (presencaButton && presencaButton.style.backgroundColor === 'rgb(0, 128, 0)') {
                countPresenca++;
            }

            // Conta os cards com "Falta" selecionada (Cor vermelha)
            if (faltaButton && faltaButton.style.backgroundColor === 'rgb(255, 0, 0)') {
                countFalta++;
            }

            // Conta os cards com "Falta Justificada" selecionada (Cor amarelo)
            if (faltaJustificadaButton && faltaJustificadaButton.style.backgroundColor === 'rgb(204, 153, 0)') {
                countFaltaJustificada++;
            }

            if (![presencaButton, faltaButton, faltaJustificadaButton].some(button => button && button.style.backgroundColor !== 'rgb(163, 163, 163)')) {
                countNenhum++;
            }
        });
        // calcula valor de btn.nenhum
        countNenhum = totalCards - countFalta - countFaltaJustificada - countPresenca;
        document.getElementById('btn-presenca').textContent = `Presença: ${countPresenca}`;
        document.getElementById('btn-falta').textContent = `Falta: ${countFalta}`;
        document.getElementById('btn-falta-justificada').textContent = `Falta Just.: ${countFaltaJustificada}`;
        //document.getElementById('btn-nenhum').textContent = `Nenhum: ${countNenhum}`;
        document.getElementById('btn-nenhum').innerHTML = `<i class="${document.getElementById('btn-nenhum').querySelector('i').classList.value}"></i> Nenhum: ${countNenhum}`;
    }


    function toggleNenhumButton(isClickEvent = false) {
        const btnNenhum = document.getElementById('btn-nenhum');
        const isActive = btnNenhum.style.backgroundColor === 'lightgray';

        if (!isClickEvent && isActive) {
            return;
        }

        if (isClickEvent) {
            if (isActive) {
                // Botão desativado
                btnNenhum.style.backgroundColor = '#a3a3a3';
                btnNenhum.style.color = '#fff';
                btnNenhum.querySelector('i').classList.replace('bi-lightbulb', 'bi-lightbulb-off'); // Alterar ícone
                filterCardsGray('none'); // Esconde os cards
            } else {
                // Botão ativado
                btnNenhum.style.backgroundColor = 'lightgray';
                btnNenhum.style.color = 'black';
                btnNenhum.querySelector('i').classList.replace('bi-lightbulb-off', 'bi-lightbulb'); // Alterar ícone
                filterCardsGray('block'); // Mostra todos os cards
            }
        } else {
            if (!isActive) {
                btnNenhum.style.backgroundColor = 'lightgray';
                btnNenhum.style.color = 'black';
                btnNenhum.querySelector('i').classList.replace('bi-lightbulb-off', 'bi-lightbulb'); // Alterar ícone para "lâmpada acesa"
                filterCardsGray('block'); // Mostrar todos os cards
            }
        }
    }
</script>

<script>
    $(function() {
        $('#obsModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var idMatricula = button.data('idmatricula');
            var fotoaluno = button.data('fotoaluno');
            var nomealuno = button.data('nomealuno');
            var idchamada = button.data('idchamada');

            var nomecurso = '<?php echo $Id__Curso; ?>';
            var nometurma = '<?php echo $Id__Turma; ?>';
            var dataSelecionada = '<?php echo $dataSelecionada; ?>';

            var modal = $(this);
            $.ajax({
                url: '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/chamada/salvar/',
                type: 'POST',
                data: {
                    action: 'loadObs',
                    idCurso: nomecurso,
                    idTurma: nometurma,
                    idMatricula: idMatricula,
                    dataSelecionada: dataSelecionada
                },
                success: function(response) {
                    try {
                        let data = JSON.parse(response);
                        // Preenche o modal com os dados retornados
                        modal.find('#idmatricula').val(idMatricula);
                        modal.find('#fotoaluno').attr('src', fotoaluno);
                        modal.find('#nomealuno').val(nomealuno);
                        modal.find('#observacoes').val(data.Obs || ''); // Preenche com o valor de Obs
                        modal.find('#idchamada').val(idchamada);
                        setTimeout(() => {
                            modal.find('#observacoes').focus();
                        }, 500);
                    } catch (e) {
                        alert('Erro na requisição');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro na requisição AJAX:', status, error);
                    alert('Erro ao carregar os dados do banco.');
                }
            });

        });
    });
</script>

<script>
    document.getElementById("SalvaObs").addEventListener("click", function() {
        var idCurso = '<?php echo addslashes((string) $NNomeCurso); ?>';
        var idTurma = '<?php echo addslashes((string) $NNomeTurma); ?>';
        var dataSelecionada = '<?php echo $dataSelecionada; ?>';
        var idMatricula = document.getElementById('idmatricula').value;
        var observacoes = document.getElementById('observacoes').value;

        if (observacoes === '') {
            observacoes = null;
        }

        $.ajax({
            url: '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/chamada/salvar/',
            type: 'POST',
            data: {
                action: 'salvaObs',
                idCurso: idCurso,
                idTurma: idTurma,
                idMatricula: idMatricula,
                dataSelecionada: dataSelecionada,
                observacoes: observacoes
            },
            success: function(response) {
                try {
                    let data = JSON.parse(response);
                    if (data.success) {
                        toastr.success("Salvo!");
                        setTimeout(() => {
                            location.reload();
                        }, 300);
                    } else {
                        alert(data.message || 'Erro ao salvar observação.');
                    }
                } catch (e) {
                    console.error('Erro ao processar a resposta do servidor:', e);
                    toastr.error("Primeiro indique P, F ou FJ.", "Erro");
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição AJAX:', status, error);
                alert('Erro ao salvar observação.');
            }
        });
    });
</script>

<script>
    function SalvaObs() {


    };
</script>

<script>
    function filterCards() {
        const normalizeText = (text) => {
            return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        };

        const input = document.getElementById('search-input');
        const filter = normalizeText(input.value);
        const cards2 = getStudentCards();

        cards2.forEach(card => {
            if (getComputedStyle(card).display !== 'none') {
                const searchableText = card.dataset.search || card.textContent || '';
                if (normalizeText(searchableText).includes(filter)) {
                    card.style.display = ''; // Exibe o card se o texto corresponder
                } else {
                    card.style.display = 'none';
                }
            }
        });
    }

    function handleSearchInput() {
        const input = document.getElementById('search-input');
        if (input.value.trim() === '') {
            clearSearch();
        } else {
            filterCards();
        }
    }


    function filterCardsGray(display) {
        const cards3 = getStudentCards();
        const displayValue = display === 'block' ? '' : display;

        const cardsToFilter = Array.from(cards3).filter(card => {
            const buttons_card = card.querySelectorAll('.btnp');
            const allButtonsInactive = Array.from(buttons_card).every(button => !button.classList.contains('selecionado'));

            return allButtonsInactive;
        });

        //console.log("Cards selecionados para filtro:", cardsToFilter);

        // Aplica display none ou block apenas nos cards que precisam ser filtrados
        cardsToFilter.forEach(card => {
            card.style.display = displayValue;
        });
    }

    function clearSearch() {
        const input = document.getElementById('search-input');
        input.value = ''; // Limpa o valor do input

        // Reativa todos os cards (fazendo display='block' novamente)
        const cards4 = getStudentCards();
        cards4.forEach(card => {
            card.style.display = '';
        });

        const buttons = document.querySelectorAll('#filter-buttons .btn');
        buttons.forEach(button => {
            // Restaura o estilo de fundo para as cores iniciais
            if (button.id === 'btn-presenca') {
                button.style.backgroundColor = 'rgb(0, 128, 0)'; // Cor original de presen?a
            } else if (button.id === 'btn-falta') {
                button.style.backgroundColor = 'rgb(255, 0, 0)'; // Cor original de falta
            } else if (button.id === 'btn-falta-justificada') {
                button.style.backgroundColor = 'rgb(204, 153, 0)'; // Cor original de falta justificada
            } else if (button.id === 'btn-nenhum') {
                button.style.backgroundColor = 'lightgray'; // Cor original de "Nenhum"
                const btnNenhum = document.getElementById('btn-nenhum');
                btnNenhum.style.color = 'black';
                btnNenhum.querySelector('i').classList.replace('bi-lightbulb-off', 'bi-lightbulb');
            }
        });
    }

    function filterCardsButton2() {
        const cards5 = getStudentCards();
        cards5.forEach(card => {
            const selectedButton = cards5.querySelector('.selecionado');
            if (selectedButton) {
                const style = selectedButton.style.backgroundColor;
                card.style.display = (
                    (style === 'rgb(0, 128, 0)' && buttons.presenca.style.backgroundColor !== 'gray') ||
                    (style === 'rgb(255, 0, 0)' && buttons.falta.style.backgroundColor !== 'gray') ||
                    (style === 'rgb(204, 153, 0)' && buttons.faltaJustificada.style.backgroundColor !== 'gray')
                ) ? '' : 'none';
            }
        });
    }
</script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.chamada-student-item[id^="card_"] button').forEach(button => {
            button.addEventListener('click', () => {
                const tooltips = bootstrap.Tooltip.getInstance(button);
                if (tooltips) {
                    tooltips.hide();
                }
            });
        });

        // Ou: remove todos os tooltips ativos
        document.addEventListener('click', function() {
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(el => {
                const instance = bootstrap.Tooltip.getInstance(el);
                if (instance) instance.hide();
            });
        });
    });
</script>

<script>
    (function() {
        const baseUrl = '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>';
        const idTurmaAtual = Number('<?php echo addslashes((string) ($NNomeTurma ?? '')); ?>' || 0);
        const dataSelecionadaBr = '<?php echo addslashes((string) ($dataSelecionada ?? '')); ?>';

        function apiBase() {
            if (/^https?:\/\//i.test(baseUrl)) return `${baseUrl}/api/v1`;
            return `${window.location.origin}${baseUrl}/api/v1`;
        }

        function esc(value) {
            return String(value ?? '').replace(/[&<>"']/g, (m) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[m]));
        }

        function brToIso(dateBr) {
            const parts = String(dateBr || '').split('/');
            if (parts.length !== 3) return '';
            const [d, m, y] = parts;
            if (!d || !m || !y) return '';
            return `${y.padStart(4, '0')}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;
        }

        function statusClass(status) {
            const s = String(status || '').toLowerCase();
            if (s === 'realizada') return 'plano-status-realizada';
            if (s === 'futura') return 'plano-status-futura';
            if (s === 'atrasada') return 'plano-status-atrasada';
            if (s === 'adiada') return 'plano-status-adiada';
            return 'plano-status-pendente';
        }

        async function apiFetch(path, options = {}) {
            const resp = await fetch(`${apiBase()}${path}`, { credentials: 'same-origin', ...options });
            const json = await resp.json().catch(() => ({}));
            if (!resp.ok || json.success === false) {
                throw new Error((json.errors && json.errors[0]) || json.message || 'Erro');
            }
            return json;
        }

        async function loadAnexosCronograma(idCronogramaAula) {
            const data = await apiFetch(`/cronograma-aulas/${idCronogramaAula}/anexos`);
            return data.data || [];
        }

        async function loadAnexosPlano(idPlanoCursoAula) {
            const data = await apiFetch(`/planos-curso/aulas/${idPlanoCursoAula}/anexos`);
            return data.data || [];
        }

        async function loadTopicosPlano(idPlanoCursoAula) {
            const data = await apiFetch(`/planos-curso/aulas/${idPlanoCursoAula}/todos`);
            return data.data || [];
        }

        async function updateStatusTopico(idPlanoCursoAulaTodo, concluido) {
            return apiFetch(`/planos-curso/aulas/todos/${idPlanoCursoAulaTodo}/status`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ Concluido: !!concluido })
            });
        }

        function normalizarCorHex(valor) {
            const cor = String(valor || '').trim().toUpperCase();
            return /^#[0-9A-F]{6}$/.test(cor) ? cor : '#FDE68A';
        }

        function renderTopicosAulaHtml(topicos) {
            if (!Array.isArray(topicos) || topicos.length === 0) {
                return '<div class="small text-muted">Sem t&oacute;picos cadastrados para esta aula.</div>';
            }

            return topicos.map((topico, idx) => {
                const idTodo = Number(topico.IdPlanoCursoAulaTodo || 0);
                const concluido = Number(topico.Concluido || 0) === 1;
                const texto = String(topico.TextoTopico || '').trim();
                const cor = normalizarCorHex(topico.CorHex || '#FDE68A');
                const nomeConcluido = `${String(topico.NomeConcluidoPor || '').trim()} ${String(topico.SobrenomeConcluidoPor || '').trim()}`.trim();
                const statusTxt = concluido ? 'Conclu\u00EDdo' : 'Pendente';
                const concluidoPorTxt = concluido && nomeConcluido ? ` - ${esc(nomeConcluido)}` : '';

                return `
                    <div class="plano-topico-item" data-id-todo="${idTodo}">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            data-action="topico-toggle"
                            data-id-todo="${idTodo}"
                            ${concluido ? 'checked' : ''}
                            aria-label="Marcar item como conclu&iacute;do">
                        <div class="plano-topico-texto ${concluido ? 'is-done' : ''}">
                            <div><strong>${idx + 1}.</strong> ${esc(texto || '-')}</div>
                            <div class="plano-topico-meta">
                                <span class="plano-topico-cor" style="background:${esc(cor)};"></span>
                                <span class="small text-muted">${statusTxt}${concluidoPorTxt}</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        async function carregarTopicosCard(card) {
            if (!card) return;
            const idAula = Number(card.dataset.idAula || 0);
            const box = card.querySelector('[data-box="topicos"]');
            if (!idAula || !box) return;

            try {
                const topicos = await loadTopicosPlano(idAula);
                box.innerHTML = renderTopicosAulaHtml(topicos);
            } catch (_e) {
                box.innerHTML = '<div class="small text-danger">Erro ao carregar t&oacute;picos.</div>';
            }
        }

        async function loadComentarios(idCronogramaAula) {
            const data = await apiFetch(`/cronograma-aulas/${idCronogramaAula}/comentarios`);
            return data.data || [];
        }

        async function addComentario(idCronogramaAula, comentario) {
            return apiFetch(`/cronograma-aulas/${idCronogramaAula}/comentarios`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ Comentario: comentario })
            });
        }

        async function updateStatus(idCronogramaAula, payload) {
            return apiFetch(`/cronograma-aulas/${idCronogramaAula}/status`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
        }

        async function remarcaAula(idCronogramaAula, dataAula, horaInicio) {
            return apiFetch(`/cronograma-aulas/${idCronogramaAula}/agendar`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ DataAula: dataAula, HoraInicio: horaInicio, StatusExecucao: 'futura' })
            });
        }

        async function carregarPlanoAulaDia() {
            const statusEl = document.getElementById('planoAulaStatus');
            const containerEl = document.getElementById('planoAulaContainer');
            if (!statusEl || !containerEl) return;

            if (!idTurmaAtual || !dataSelecionadaBr) {
                statusEl.textContent = 'Selecione curso, turma e data para visualizar o plano.';
                containerEl.innerHTML = '';
                return;
            }

            const dataIso = brToIso(dataSelecionadaBr);
            if (!dataIso) {
                statusEl.textContent = 'Data inválida para carregar plano da aula.';
                containerEl.innerHTML = '';
                return;
            }

            try {
                statusEl.textContent = 'Carregando plano da aula...';
                const response = await apiFetch(`/turmas/${idTurmaAtual}/plano-curso/cronograma`);
                const itens = (response.data || []).filter((i) => String(i.DataAula || '') === dataIso);

                if (!itens.length) {
                    statusEl.textContent = 'Nenhuma aula planejada para esta data.';
                    containerEl.innerHTML = '';
                    return;
                }

                statusEl.textContent = `${itens.length} aula(s) planejada(s) para ${dataSelecionadaBr}.`;
                containerEl.innerHTML = itens.map((item) => `
                    <div class="plano-aula-card" data-id-cronograma="${Number(item.IdCronogramaAula)}" data-id-aula="${Number(item.IdPlanoCursoAula)}">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                            <div><strong>${esc(item.NomeAula)}</strong></div>
                            <span class="plano-status-badge ${statusClass(item.StatusExecucao)}">${esc(item.StatusExecucao || 'pendente')}</span>
                        </div>
                        <div class="meta mb-2">
                            Ordem ${Number(item.OrdemAula || 0)} | Duração ${Number(item.DuracaoMinutos || 0)} min | Categoria ${esc(item.Categoria || '-')}
                            | Horário ${esc(String(item.HoraInicio || '').slice(0,5) || '--:--')} - ${esc(String(item.HoraFim || '').slice(0,5) || '--:--')}
                        </div>
                        <div class="small mb-1"><strong>Recursos:</strong> ${esc(item.Recursos || '-')}</div>
                        <div class="small mb-1"><strong>Materiais:</strong> ${esc(item.Materiais || '-')}</div>
                        <div class="small mb-2"><strong>Observações pedagógicas:</strong> ${esc(item.ObservacoesPlano || '-')}</div>
                        <div class="mb-2">
                            <div class="small mb-1"><strong>T&oacute;picos da aula</strong></div>
                            <div class="plano-topicos-box" data-box="topicos">
                                <div class="small text-muted">Carregando t&oacute;picos...</div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-1 mb-2">
                            <button class="btn btn-sm btn-outline-success" data-action="realizada">Marcar realizada</button>
                            <button class="btn btn-sm btn-outline-warning" data-action="adiada">Marcar adiada</button>
                            <button class="btn btn-sm btn-outline-primary" data-action="remarcar">Remarcar</button>
                            <button class="btn btn-sm btn-outline-secondary" data-action="anexos">Ver anexos</button>
                        </div>

                        <div class="mb-2">
                            <label class="form-label form-label-sm"><strong>Feedback da aula</strong></label>
                            <textarea class="form-control form-control-sm" rows="2" data-field="feedback">${esc(item.FeedbackProfessor || '')}</textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label form-label-sm"><strong>Observações de execução</strong></label>
                            <textarea class="form-control form-control-sm" rows="2" data-field="obs">${esc(item.Observacoes || '')}</textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label form-label-sm"><strong>Justificativa de adiamento</strong></label>
                            <textarea class="form-control form-control-sm" rows="2" data-field="justificativa">${esc(item.JustificativaAdiamento || '')}</textarea>
                        </div>
                        <div class="d-flex gap-1 mb-2">
                            <button class="btn btn-sm btn-outline-dark" data-action="salvar-feedback">Salvar feedback/obs</button>
                        </div>

                        <div class="p-2 bg-light rounded mb-2" data-box="anexos" style="display:none;"></div>
                        <div class="p-2 bg-light rounded mb-2" data-box="comentarios"></div>
                        <div class="d-flex gap-1">
                            <input type="text" class="form-control form-control-sm" placeholder="Adicionar comentário" data-field="comentario">
                            <button class="btn btn-sm btn-outline-dark" data-action="comentar">Comentar</button>
                        </div>
                    </div>
                `).join('');

                await loadTopicosAll();
                bindPlanoAulaEvents();
                await loadComentariosAll();
            } catch (err) {
                statusEl.textContent = `Erro ao carregar plano da aula: ${err.message}`;
                containerEl.innerHTML = '';
            }
        }

        async function loadTopicosAll() {
            const cards = document.querySelectorAll('.plano-aula-card');
            for (const card of cards) {
                await carregarTopicosCard(card);
            }
        }

        async function loadComentariosAll() {
            const cards = document.querySelectorAll('.plano-aula-card');
            for (const card of cards) {
                const idCronograma = Number(card.dataset.idCronograma || 0);
                const box = card.querySelector('[data-box="comentarios"]');
                if (!idCronograma || !box) continue;
                try {
                    const comentarios = await loadComentarios(idCronograma);
                    if (!comentarios.length) {
                        box.innerHTML = '<div class="small text-muted">Sem comentários.</div>';
                        continue;
                    }
                    box.innerHTML = comentarios.map((c) => `
                        <div class="small mb-1">
                            <strong>${esc((c.Nome || '') + ' ' + (c.Sobrenome || ''))}</strong>:
                            ${esc(c.Comentario || '')}
                            <span class="text-muted">(${esc(c.DataCriacao || '')})</span>
                        </div>
                    `).join('');
                } catch (_e) {
                    box.innerHTML = '<div class="small text-danger">Erro ao carregar comentários.</div>';
                }
            }
        }

        function bindPlanoAulaEvents() {
            document.querySelectorAll('.plano-aula-card [data-action]').forEach((btn) => {
                if (btn.dataset.actionBound === '1') {
                    return;
                }
                btn.dataset.actionBound = '1';
                btn.addEventListener('click', async (e) => {
                    const action = btn.dataset.action;
                    const card = e.target.closest('.plano-aula-card');
                    if (!card) return;
                    if (action === 'topico-toggle') {
                        const idTodo = Number(btn.dataset.idTodo || 0);
                        if (!idTodo) return;
                        const novoStatus = Boolean(btn.checked);
                        try {
                            await updateStatusTopico(idTodo, novoStatus);
                            await carregarTopicosCard(card);
                            bindPlanoAulaEvents();
                            if (window.toastr) {
                                toastr.success('Status do t\u00F3pico atualizado.');
                            }
                        } catch (err) {
                            btn.checked = !novoStatus;
                            alert(`Erro: ${err.message}`);
                        }
                        return;
                    }
                    const idCronograma = Number(card.dataset.idCronograma || 0);
                    const idAula = Number(card.dataset.idAula || 0);
                    if (!idCronograma) return;

                    const feedback = card.querySelector('[data-field="feedback"]')?.value || '';
                    const obs = card.querySelector('[data-field="obs"]')?.value || '';
                    const justificativa = card.querySelector('[data-field="justificativa"]')?.value || '';

                    try {
                        const statusAtual = String(card.querySelector('.plano-status-badge')?.textContent || 'futura').trim().toLowerCase();
                        if (action === 'realizada') {
                            await updateStatus(idCronograma, {
                                StatusExecucao: 'realizada',
                                FeedbackProfessor: feedback,
                                Observacoes: obs
                            });
                            await carregarPlanoAulaDia();
                            return;
                        }
                        if (action === 'adiada') {
                            const just = justificativa || prompt('Informe a justificativa do adiamento:', '');
                            if (!just) return;
                            await updateStatus(idCronograma, {
                                StatusExecucao: 'adiada',
                                JustificativaAdiamento: just,
                                FeedbackProfessor: feedback,
                                Observacoes: obs
                            });
                            await carregarPlanoAulaDia();
                            return;
                        }
                        if (action === 'remarcar') {
                            const novaData = prompt('Nova data (AAAA-MM-DD):', brToIso(dataSelecionadaBr));
                            if (!novaData) return;
                            const novaHora = prompt('Novo horário (HH:MM):', '08:00');
                            if (!novaHora) return;
                            await remarcaAula(idCronograma, novaData, novaHora);
                            await carregarPlanoAulaDia();
                            return;
                        }
                        if (action === 'anexos') {
                            const box = card.querySelector('[data-box="anexos"]');
                            if (!box) return;
                            box.style.display = box.style.display === 'none' ? 'block' : 'none';
                            if (box.dataset.loaded === '1') return;
                            const [anexosCron, anexosPlano] = await Promise.all([
                                loadAnexosCronograma(idCronograma),
                                loadAnexosPlano(idAula)
                            ]);
                            const lista = [...anexosCron, ...anexosPlano];
                            if (!lista.length) {
                                box.innerHTML = '<div class="small text-muted">Sem anexos.</div>';
                            } else {
                                box.innerHTML = lista.map((a) => `
                                    <div class="small mb-1">
                                        <a href="${baseUrl}/${esc(a.CaminhoRelativo || '')}" target="_blank" rel="noopener noreferrer">
                                            ${esc(a.NomeArquivo || a.NomeOriginal || a.NomeFisico || 'Arquivo')}
                                        </a>
                                        ${a.Descricao ? `<div class="small text-muted">${esc(a.Descricao)}</div>` : ''}
                                        <span class="text-muted">(${esc(a.MimeType || '')})</span>
                                    </div>
                                `).join('');
                            }
                            box.dataset.loaded = '1';
                            return;
                        }
                        if (action === 'salvar-feedback') {
                            await updateStatus(idCronograma, {
                                StatusExecucao: statusAtual || 'futura',
                                FeedbackProfessor: feedback,
                                Observacoes: obs,
                                JustificativaAdiamento: justificativa || null
                            });
                            await carregarPlanoAulaDia();
                            return;
                        }
                        if (action === 'comentar') {
                            const campo = card.querySelector('[data-field="comentario"]');
                            const texto = (campo?.value || '').trim();
                            if (!texto) return;
                            await addComentario(idCronograma, texto);
                            campo.value = '';
                            await loadComentariosAll();
                        }
                    } catch (err) {
                        alert(`Erro: ${err.message}`);
                    }
                });
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('btnRecarregarPlanoAula');
            if (btn) btn.addEventListener('click', carregarPlanoAulaDia);
            carregarPlanoAulaDia();
        });
    })();
</script>

</html>










