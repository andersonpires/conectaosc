<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode((string) ($_SERVER['REQUEST_URI'] ?? ''));
    header('Location: ' . rtrim((string) ($BASE_para_URL ?? ''), '/') . '/login/?redirect=' . $redirect_url);
    exit();
}

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

$idCurso = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$curso = null;
$paginaValida = false;
if ($idCurso > 0) {
    $stmtCurso = $pdo->prepare(
        'SELECT c.*, IFNULL(p.nomeProjeto, "-") AS nomeProjeto
         FROM tbCurso c
         LEFT JOIN tbProjeto p ON p.IdProjeto = c.IdProjeto
         WHERE c.IdCurso = ?
         LIMIT 1'
    );
    $stmtCurso->execute([$idCurso]);
    $curso = $stmtCurso->fetch(PDO::FETCH_ASSOC) ?: null;
    $paginaValida = $curso !== null;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <style>
        .pc-page { padding: 1rem 0 1.25rem; background: #f5f7fb; color: #1f2937; }
        .pc-hero { background: linear-gradient(130deg, #0b4aac 0%, #08347f 100%); border-radius: 1rem; padding: 1rem 1.2rem; box-shadow: 0 12px 24px rgba(8, 52, 127, .24); margin-bottom: 1rem; }
        .pc-hero h1 { color: #f8fafc; margin: 0; font-weight: 800; }
        .pc-hero .sub { color: #dbeafe; margin-top: .35rem; }
        .pc-hero .btn { border-radius: .7rem; }
        .planejamento-grid { display: grid; gap: 1rem; grid-template-columns: 1fr; }
        .planejamento-card { border: 1px solid #dde3ee; border-radius: .8rem; background: #fff; box-shadow: 0 2px 8px rgba(15, 23, 42, .04); }
        .planejamento-card-header { padding: .95rem 1rem; border-bottom: 1px solid #ecf0f6; background: #fbfcfe; }
        .planejamento-card-header .h6, .planejamento-card-header .h5 { margin: 0; color: #1e293b; font-weight: 800; }
        .planejamento-card-body { padding: 1rem; }
        .planejamento-lista { max-height: 420px; overflow-y: auto; }
        .planejamento-item { border: 1px solid #e3e8f1; border-radius: .7rem; padding: .75rem .8rem; cursor: pointer; background: #fff; transition: .15s ease; }
        .planejamento-item + .planejamento-item { margin-top: .55rem; }
        .planejamento-item:hover { border-color: #cddaf0; background: #f8fbff; }
        .planejamento-item.active { border: 2px solid #9ab5ec; background: #f4f8ff; }
        .pc-section-subtitle { font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; font-weight: 800; margin-bottom: .55rem; }
        .pc-soft-box { background: #f6f8fc; border: 1px solid #e1e6ef; border-radius: .75rem; padding: .75rem; }
        .aula-item { border: 1px solid #e4ebf6; border-radius: .8rem; padding: 0; background: #fff; overflow: hidden; }
        .aula-item + .aula-item { margin-top: .65rem; }
        .aula-item-head { padding: .8rem .9rem; border-bottom: 1px solid #edf1f7; background: #fafcff; }
        .aula-item-body { padding: .85rem; }
        .aula-ordem { display: inline-flex; width: 30px; height: 30px; align-items: center; justify-content: center; font-weight: 700; background: #1e4cad; color: #fff; border-radius: 50%; font-size: .82rem; }
        .aula-meta { font-size: .7rem; background: #e8effe; color: #1e4cad; border-radius: .4rem; padding: .1rem .35rem; font-weight: 700; }
        .status-msg { min-height: 22px; }
        .btn-primary { background: #0b4aac; border-color: #0b4aac; }
        .btn-primary:hover { background: #08347f; border-color: #08347f; }
        .btn-outline-primary { color: #0b4aac; border-color: #0b4aac; }
        .btn-outline-primary:hover { background: #0b4aac; border-color: #0b4aac; color: #fff; }
        .btn-success { background: #22c55e; border-color: #22c55e; }
        .btn-success:hover { background: #1ea552; border-color: #1ea552; }
        .btn-soft-danger { color: #dc3545; background: #fff5f5; border: 1px solid #ffd4d8; }
        .btn-soft-danger:hover { color: #b42332; background: #ffecee; border-color: #ffbcc3; }
        .btn-pdf-plano { width: 34px; height: 34px; border-radius: .65rem; display: inline-flex; align-items: center; justify-content: center; background: #dc2626; border: 1px solid #dc2626; color: #fff; box-shadow: 0 4px 12px rgba(220, 38, 38, .28); transition: .15s ease; }
        .btn-pdf-plano:hover { background: #b91c1c; border-color: #b91c1c; color: #fff; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(185, 28, 28, .32); }
        .btn-pdf-plano i { font-size: .98rem; }
        .icon-label { display: inline-flex; align-items: center; gap: .45rem; }
        .icon-label i { color: #1e4cad; }
        .form-control:focus, .form-select:focus { border-color: #9db7e8; box-shadow: 0 0 0 .2rem rgba(13, 78, 194, .12); }
        .todo-wrap { margin-top: .65rem; }
        .todo-header { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .45rem; }
        .todo-title { font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; font-weight: 800; margin: 0; }
        .todo-add { display: grid; grid-template-columns: 1fr 44px; gap: .45rem; margin-bottom: .55rem; }
        .todo-add .form-control { border-radius: .6rem; min-height: 40px; }
        .todo-add .btn { border-radius: .6rem; font-weight: 800; }
        .todo-list { display: grid; gap: .45rem; }
        .todo-empty { font-size: .82rem; color: #6b7280; background: #fff; border: 1px dashed #d6deec; border-radius: .65rem; padding: .65rem .7rem; }
        .todo-item { position: relative; z-index: 1; border: 1px solid rgba(148, 163, 184, .35); border-radius: .75rem; padding: .55rem; box-shadow: 0 10px 22px rgba(15, 23, 42, .14); cursor: grab; transition: box-shadow .15s ease, transform .15s ease; animation: todoPopIn .22s ease; }
        .todo-item.is-color-open { z-index: 60; }
        .todo-item:hover { box-shadow: 0 12px 26px rgba(15, 23, 42, .18); transform: translateY(-1px); }
        .todo-item:active { cursor: grabbing; }
        .todo-item-main { display: flex; align-items: center; gap: .45rem; }
        .todo-toggle { width: 18px; height: 18px; flex: 0 0 auto; }
        .todo-text { border: 1px solid rgba(148, 163, 184, .35); background: rgba(255, 255, 255, .75); border-radius: .55rem; min-height: 36px; font-size: .92rem; }
        .todo-actions { margin-top: .45rem; display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .todo-actions-left { display: flex; align-items: center; gap: .35rem; }
        .todo-actions .btn { border-radius: .55rem; }
        .todo-color-wrap { position: relative; z-index: 2; }
        .todo-color-toggle { width: 30px; height: 30px; border: 1px solid rgba(15, 23, 42, .22); border-radius: 50%; background: #fff; padding: 0; display: inline-flex; align-items: center; justify-content: center; transition: transform .18s ease, box-shadow .18s ease; }
        .todo-color-wrap.is-open .todo-color-toggle { transform: rotate(18deg) scale(1.05); box-shadow: 0 4px 10px rgba(15, 23, 42, .2); }
        .todo-color-dot { width: 16px; height: 16px; border-radius: 50%; border: 1px solid rgba(15, 23, 42, .25); display: inline-block; }
        .todo-color-menu { position: absolute; z-index: 80; top: calc(100% + 6px); left: 0; display: grid; grid-template-columns: repeat(4, 1fr); gap: .3rem; background: #fff; border: 1px solid #dce3f0; border-radius: .65rem; padding: .4rem; box-shadow: 0 8px 18px rgba(2, 6, 23, .15); opacity: 0; transform: translateY(-6px) scale(.94); transform-origin: top left; pointer-events: none; transition: opacity .2s ease, transform .2s ease; }
        .todo-color-wrap.is-open .todo-color-menu { opacity: 1; transform: translateY(0) scale(1); pointer-events: auto; }
        .todo-color-option { width: 22px; height: 22px; border-radius: 50%; border: 1px solid rgba(15, 23, 42, .24); padding: 0; }
        .todo-item.is-done .todo-text { text-decoration: line-through; color: #475569; opacity: .88; }
        .todo-avatar { position: absolute; top: -8px; right: -8px; width: 28px; height: 28px; border-radius: 50%; overflow: hidden; border: 2px solid #fff; box-shadow: 0 2px 8px rgba(2, 6, 23, .2); background: #fff; }
        .todo-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .todo-ghost { opacity: .6; }
        .todo-saving { opacity: .8; pointer-events: none; }
        .todo-item input, .todo-item button { cursor: auto; }
        .todo-inline-tooltip { position: absolute; top: -10px; right: 10px; z-index: 120; padding: .2rem .45rem; border-radius: .45rem; background: rgba(15, 23, 42, .92); color: #fff; font-size: .68rem; font-weight: 700; box-shadow: 0 6px 16px rgba(2, 6, 23, .28); opacity: 0; transform: translateY(-4px); pointer-events: none; transition: opacity .18s ease, transform .18s ease; }
        .todo-inline-tooltip.is-visible { opacity: 1; transform: translateY(0); }
        @keyframes todoPopIn {
            0% { opacity: 0; transform: translateY(8px) scale(.98); }
            100% { opacity: 1; transform: none; }
        }
        @media (min-width: 992px) {
            .planejamento-grid { grid-template-columns: 320px 1fr; }
            .pc-hero { padding: 1.1rem 1.25rem; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>
        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
            <main class="content">
                <div class="container-fluid p-0 pc-page">
                    <div class="pc-hero d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
                        <div>
                            <h1 class="h3">Plano de Curso</h1>
                            <div class="sub small">
                                <?php if ($paginaValida): ?>
                                    Curso: <strong><?php echo htmlspecialchars((string) ($curso['NomeCurso'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php else: ?>
                                    Curso não encontrado.
                                <?php endif; ?>
                            </div>
                        </div>
                        <a class="btn btn-light" href="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/plano-cursos">Voltar Planos</a>
                    </div>

                    <?php if (!$paginaValida): ?>
                        <div class="alert alert-danger">Abra esta página a partir da lista de cursos.</div>
                    <?php else: ?>
                        <div id="statusMsg" class="status-msg text-muted small mb-2"></div>
                        <div class="planejamento-grid">
                            <section class="planejamento-card">
                                <div class="planejamento-card-header">
                                    <h2 class="h5 mb-0 icon-label"><i class="fa-solid fa-table-list"></i> Planos do Curso</h2>
                                </div>
                                <div class="planejamento-card-body">
                                    <form id="formNovoPlano" class="mb-3">
                                        <div class="mb-2">
                                            <label class="form-label small fw-bold text-uppercase text-secondary">Nome</label>
                                            <input type="text" class="form-control" id="novoNomePlano" placeholder="Ex: Plano Verão 2026" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small fw-bold text-uppercase text-secondary">Versão</label>
                                            <input type="text" class="form-control" id="novoVersaoPlano" placeholder="1.0">
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fa-solid fa-plus me-1"></i>Criar plano</button>
                                    </form>
                                    <div id="listaPlanos" class="planejamento-lista"></div>
                                </div>
                            </section>

                            <section class="planejamento-card">
                                <div class="planejamento-card-header d-flex align-items-center justify-content-between gap-2">
                                    <h2 class="h5 mb-0 icon-label"><i class="fa-solid fa-file-lines"></i> Detalhes e Aulas</h2>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-light border" id="btnDuplicarPlano"><i class="fa-regular fa-copy me-1"></i>Duplicar</button>
                                        <button type="button" class="btn btn-sm btn-soft-danger" id="btnExcluirPlano"><i class="fa-regular fa-trash-can me-1"></i>Excluir</button>
                                    </div>
                                </div>
                                <div class="planejamento-card-body">
                                    <div id="semPlanoSelecionado" class="alert alert-info mb-3">Selecione ou crie um plano.</div>

                                    <div id="conteudoPlano" style="display:none;">
                                        <form id="formEditarPlano" class="mb-3">
                                            <input type="hidden" id="planoIdEdit">
                                            <div class="row g-2">
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label small fw-bold text-uppercase text-secondary">Nome do plano</label>
                                                    <input type="text" class="form-control" id="planoNomeEdit" required>
                                                </div>
                                                <div class="col-12 col-md-3">
                                                    <label class="form-label small fw-bold text-uppercase text-secondary">Versão</label>
                                                    <input type="text" class="form-control" id="planoVersaoEdit">
                                                </div>
                                                <div class="col-12 col-md-3 d-grid">
                                                    <label class="form-label">&nbsp;</label>
                                                    <button type="submit" class="btn btn-success fw-bold"><i class="fa-regular fa-floppy-disk me-1"></i>Salvar plano</button>
                                                </div>
                                            </div>
                                        </form>

                                        <div class="pc-soft-box mb-3">
                                            <div class="pc-section-subtitle">Vincular plano à turma</div>
                                            <div class="row g-2">
                                            <div class="col-12 col-md-7">
                                                <select id="selectTurmaVinculo" class="form-select" multiple></select>
                                            </div>
                                            <div class="col-12 col-md-5 d-grid">
                                                <button type="button" class="btn btn-outline-secondary" id="btnVincularPlanoTurma">Vincular</button>
                                            </div>
                                        </div>
                                        </div>

                                        <div class="pc-soft-box mb-3">
                                            <div class="pc-section-subtitle">Desvincular plano da turma</div>
                                            <div class="row g-2">
                                            <div class="col-12 col-md-7">
                                                <select id="selectTurmaDesvinculo" class="form-select" multiple></select>
                                            </div>
                                            <div class="col-12 col-md-5 d-grid">
                                                <button type="button" class="btn btn-outline-danger" id="btnDesvincularPlanoTurma">Desvincular</button>
                                            </div>
                                        </div>
                                        </div>

                                        <hr>

                                        <h3 class="pc-section-subtitle mb-2">Cadastrar nova aula</h3>
                                        <form id="formNovaAula" class="mb-3">
                                            <div class="row g-2">
                                                <div class="col-12 col-md-6">
                                                    <input type="text" class="form-control" id="aulaNome" placeholder="Nome da aula" required>
                                                </div>
                                                <div class="col-6 col-md-2">
                                                    <input type="number" min="1" class="form-control" id="aulaDuracao" placeholder="Min" required>
                                                </div>
                                                <div class="col-6 col-md-2">
                                                    <input type="text" class="form-control" id="aulaCategoria" placeholder="Categoria">
                                                </div>
                                                <div class="col-12 col-md-2 d-grid">
                                                    <button type="submit" class="btn btn-primary">Adicionar</button>
                                                </div>
                                            </div>
                                        </form>

                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="btnSalvarOrdem" disabled>Salvar nova ordem</button>
                                        </div>

                                        <h3 class="pc-section-subtitle mb-2">Aulas do plano</h3>
                                        <div id="listaAulas"></div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>

    <div class="modal fade" id="uxActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uxActionTitle">Confirmação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p id="uxActionMessage" class="mb-3"></p>
                    <div id="uxActionInputWrap" style="display:none;">
                        <label for="uxActionInput" class="form-label">Valor</label>
                        <input type="text" id="uxActionInput" class="form-control">
                    </div>
                </div>
                <div class="modal-footer" id="uxActionFooter">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="uxActionConfirm">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>
    <script>
        const ID_CURSO = <?php echo (int) $idCurso; ?>;
        let planos = [];
        let planoSelecionado = null;
        let aulas = [];
        let aulasOrdemEditada = [];
        let ordemAlterada = false;
        let salvandoOrdem = false;
        let turmaVinculoTomSelect = null;
        let turmaDesvinculoTomSelect = null;
        let turmasCursoComPlano = [];
        let todosPorAula = new Map();
        let sortablesTodoPorAula = new Map();
        const salvandoOrdemTodoAula = {};
        const estadoAutoSaveTopico = {};
        const timersTooltipTopico = new WeakMap();
        let eventoGlobalPaletaCorVinculado = false;
        const CORES_TODO = ['#FDE68A', '#FECACA', '#BFDBFE', '#BBF7D0', '#E9D5FF', '#FED7AA', '#FBCFE8'];
        const BASE_FOTOS_COLABORADOR = <?php echo json_encode(rtrim(bootstrap_assets_img_url(), '/') . '/fotos', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
        const FOTO_PADRAO_COLABORADOR = <?php echo json_encode(bootstrap_foto_url(''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
        const FOTO_USUARIO_LOGADO = <?php echo json_encode((string) ($_SESSION['Foto'] ?? ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

        function resolveApiBase() {
            const basePath = "<?php echo rtrim((string)$BASE_para_URL, '/'); ?>";
            if (/^https?:\/\//i.test(basePath)) return `${basePath}/api/v1`;
            return `${window.location.origin}${basePath}/api/v1`;
        }

        function showStatus(msg, type = 'muted') {
            const el = document.getElementById('statusMsg');
            el.className = `status-msg small text-${type}`;
            el.textContent = msg || '';
        }
        if (window.toastr) {
            toastr.options = {
                closeButton: true,
                debug: false,
                newestOnTop: true,
                progressBar: true,
                positionClass: 'toast-top-center',
                preventDuplicates: false,
                onclick: null,
                showDuration: '300',
                hideDuration: '1000',
                timeOut: '3000',
                extendedTimeOut: '1000',
                showEasing: 'swing',
                hideEasing: 'linear',
                showMethod: 'fadeIn',
                hideMethod: 'fadeOut'
            };
        }
        const toastOk = (msg) => { if (window.toastr) toastr.success(msg || 'Alteração salva.'); };
        const toastErr = (msg) => { if (window.toastr) toastr.error(msg || 'Falha na operação.'); };
        const toastWarn = (msg) => { if (window.toastr) toastr.warning(msg || 'Atenção.'); };
        const toastInfo = (msg) => { if (window.toastr) toastr.info(msg || 'Informação.'); };

        function esc(text) {
            return String(text ?? '').replace(/[&<>"']/g, (m) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[m]));
        }

        function escAttr(text) {
            return esc(text).replace(/`/g, '&#096;');
        }

        function normalizarCorHex(valor) {
            const cor = String(valor || '').trim().toUpperCase();
            return /^#[0-9A-F]{6}$/.test(cor) ? cor : '#FDE68A';
        }

        function fotoColaboradorUrl(foto) {
            const nomeArquivo = String(foto || '').trim();
            if (!nomeArquivo) return FOTO_PADRAO_COLABORADOR;
            return `${BASE_FOTOS_COLABORADOR}/${encodeURIComponent(nomeArquivo)}`;
        }

        function destruirSortablesTodos() {
            sortablesTodoPorAula.forEach((sortable) => {
                try {
                    sortable.destroy();
                } catch (err) {
                    console.warn('Falha ao destruir sortable de topicos.', err);
                }
            });
            sortablesTodoPorAula.clear();
        }

        function definirEstadoPaletaCor(wrap, aberto) {
            if (!wrap) return;
            wrap.classList.toggle('is-open', Boolean(aberto));
            const todoItem = wrap.closest('.todo-item');
            if (todoItem) {
                todoItem.classList.toggle('is-color-open', Boolean(aberto));
            }
        }

        function fecharPaletasCorTopicos() {
            document.querySelectorAll('.todo-color-wrap.is-open').forEach((wrap) => {
                definirEstadoPaletaCor(wrap, false);
            });
        }

        function garantirEventosGlobaisPaletaCor() {
            if (eventoGlobalPaletaCorVinculado) return;
            eventoGlobalPaletaCorVinculado = true;

            document.addEventListener('click', (evt) => {
                const dentro = evt.target && evt.target.closest ? evt.target.closest('.todo-color-wrap') : null;
                if (!dentro) {
                    fecharPaletasCorTopicos();
                }
            });

            document.addEventListener('keydown', (evt) => {
                if (evt.key === 'Escape') {
                    fecharPaletasCorTopicos();
                }
            });
        }

        function mostrarTooltipTopico(elemento, mensagem) {
            if (!elemento) return;
            const texto = String(mensagem || '').trim();
            if (!texto) return;

            if (!['relative', 'absolute', 'fixed', 'sticky'].includes(window.getComputedStyle(elemento).position)) {
                elemento.style.position = 'relative';
            }

            const anterior = elemento.querySelector('.todo-inline-tooltip');
            if (anterior) {
                anterior.remove();
            }

            const timerAnterior = timersTooltipTopico.get(elemento);
            if (timerAnterior) {
                clearTimeout(timerAnterior);
            }

            const tooltip = document.createElement('span');
            tooltip.className = 'todo-inline-tooltip';
            tooltip.textContent = texto;
            elemento.appendChild(tooltip);

            window.requestAnimationFrame(() => {
                tooltip.classList.add('is-visible');
            });

            const timer = setTimeout(() => {
                tooltip.classList.remove('is-visible');
                setTimeout(() => {
                    if (tooltip.parentNode) {
                        tooltip.parentNode.removeChild(tooltip);
                    }
                }, 200);
                timersTooltipTopico.delete(elemento);
            }, 1100);

            timersTooltipTopico.set(elemento, timer);
        }

        function uxModalElements() {
            return {
                root: document.getElementById('uxActionModal'),
                title: document.getElementById('uxActionTitle'),
                message: document.getElementById('uxActionMessage'),
                inputWrap: document.getElementById('uxActionInputWrap'),
                input: document.getElementById('uxActionInput'),
                footer: document.getElementById('uxActionFooter'),
                confirm: document.getElementById('uxActionConfirm')
            };
        }

        function openUxConfirm({ title = 'Confirmação', message = 'Deseja continuar?', confirmText = 'Confirmar', confirmClass = 'btn-primary' } = {}) {
            return new Promise((resolve) => {
                const el = uxModalElements();
                el.title.textContent = title;
                el.message.textContent = message;
                el.inputWrap.style.display = 'none';
                el.confirm.className = `btn ${confirmClass}`;
                el.confirm.textContent = confirmText;
                el.footer.innerHTML = `
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn ${confirmClass}" id="uxActionConfirm">${confirmText}</button>
                `;
                const modal = bootstrap.Modal.getOrCreateInstance(el.root);
                const confirmBtn = el.footer.querySelector('#uxActionConfirm');
                let settled = false;
                confirmBtn.onclick = () => { settled = true; modal.hide(); resolve(true); };
                el.root.addEventListener('hidden.bs.modal', () => { if (!settled) resolve(false); }, { once: true });
                modal.show();
            });
        }

        function openUxPrompt({ title = 'Informar valor', message = '', label = 'Valor', initialValue = '', confirmText = 'Confirmar' } = {}) {
            return new Promise((resolve) => {
                const el = uxModalElements();
                el.title.textContent = title;
                el.message.textContent = message;
                el.inputWrap.style.display = '';
                el.input.previousElementSibling.textContent = label;
                el.input.value = initialValue || '';
                el.footer.innerHTML = `
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="uxActionConfirm">${confirmText}</button>
                `;
                const modal = bootstrap.Modal.getOrCreateInstance(el.root);
                const confirmBtn = el.footer.querySelector('#uxActionConfirm');
                let settled = false;
                confirmBtn.onclick = () => {
                    const value = (el.input.value || '').trim();
                    if (!value) {
                        toastWarn('Informe um valor válido.');
                        return;
                    }
                    settled = true;
                    modal.hide();
                    resolve(value);
                };
                el.root.addEventListener('shown.bs.modal', () => el.input.focus(), { once: true });
                el.root.addEventListener('hidden.bs.modal', () => { if (!settled) resolve(null); }, { once: true });
                modal.show();
            });
        }

        function openUxChoiceOrderMode() {
            return new Promise((resolve) => {
                const el = uxModalElements();
                el.title.textContent = 'Aplicar nova ordem';
                el.message.textContent = 'Escolha como refletir a nova ordem nas turmas vinculadas:';
                el.inputWrap.style.display = 'none';
                el.footer.innerHTML = `
                    <button type="button" class="btn btn-outline-primary" id="uxModeB">Modo B: Manter turmas</button>
                    <button type="button" class="btn btn-primary" id="uxModeA">Modo A: Reordenar turmas</button>
                `;
                const modal = bootstrap.Modal.getOrCreateInstance(el.root);
                const modeA = el.footer.querySelector('#uxModeA');
                const modeB = el.footer.querySelector('#uxModeB');
                let settled = false;
                modeA.onclick = () => { settled = true; modal.hide(); resolve('A'); };
                modeB.onclick = () => { settled = true; modal.hide(); resolve('B'); };
                el.root.addEventListener('hidden.bs.modal', () => { if (!settled) resolve(null); }, { once: true });
                modal.show();
            });
        }

        async function apiFetch(path, options = {}) {
            const response = await fetch(`${resolveApiBase()}${path}`, {
                credentials: 'same-origin',
                ...options
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok || result.success === false) {
                throw new Error((result.errors && result.errors[0]) || result.message || 'Erro na API');
            }
            return result;
        }

        function renderPlanos() {
            const el = document.getElementById('listaPlanos');
            if (!planos.length) {
                el.innerHTML = '<div class="text-muted small">Nenhum plano cadastrado.</div>';
                return;
            }
            el.innerHTML = planos.map((p) => `
                <div class="planejamento-item ${planoSelecionado && Number(planoSelecionado.IdPlanoCurso) === Number(p.IdPlanoCurso) ? 'active' : ''}" data-id="${Number(p.IdPlanoCurso)}">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <div class="fw-bold">${esc(p.NomePlano)}</div>
                            <div class="small text-muted">Versão: <strong>${esc(p.Versao || '-')}</strong></div>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <a
                                class="btn btn-sm btn-pdf-plano"
                                data-action="pdf-plano"
                                href="${resolveApiBase()}/planos-curso/${Number(p.IdPlanoCurso)}/pdf"
                                target="_blank"
                                rel="noopener noreferrer"
                                title="Gerar PDF do planejamento"
                                aria-label="Gerar PDF do planejamento">
                                <i class="fa-solid fa-file-pdf"></i>
                            </a>
                            <i class="fa-solid ${planoSelecionado && Number(planoSelecionado.IdPlanoCurso) === Number(p.IdPlanoCurso) ? 'fa-circle-check text-primary' : 'fa-clock-rotate-left text-muted'}"></i>
                        </div>
                    </div>
                </div>
            `).join('');
            el.querySelectorAll('.planejamento-item').forEach((item) => {
                item.addEventListener('click', () => selecionarPlano(Number(item.dataset.id)));
            });
            el.querySelectorAll('.planejamento-item [data-action="pdf-plano"]').forEach((btnPdf) => {
                btnPdf.addEventListener('click', (evt) => {
                    evt.stopPropagation();
                });
            });
        }

        function renderAulas() {
            const el = document.getElementById('listaAulas');
            if (!aulasOrdemEditada.length) {
                el.innerHTML = '<div class="text-muted small">Sem aulas neste plano.</div>';
                return;
            }
            el.innerHTML = aulasOrdemEditada.map((a, idx) => `
                <div class="aula-item" data-id="${Number(a.IdPlanoCursoAula)}">
                    <div class="aula-item-head d-flex align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="aula-ordem">${idx + 1}</span>
                            <div>
                                <div class="fw-bold">${esc(a.NomeAula)}</div>
                                <span class="aula-meta">${Number(a.DuracaoMinutos)} min | ${esc(a.Categoria || '-')}</span>
                            </div>
                        </div>
                        <div class="d-flex gap-1">
                            <button class="btn btn-outline-secondary btn-sm" data-action="up" ${idx === 0 ? 'disabled' : ''}><i class="fa-solid fa-chevron-up"></i></button>
                            <button class="btn btn-outline-secondary btn-sm" data-action="down" ${idx === aulasOrdemEditada.length - 1 ? 'disabled' : ''}><i class="fa-solid fa-chevron-down"></i></button>
                            <button class="btn btn-outline-danger btn-sm" data-action="delete"><i class="fa-regular fa-trash-can"></i></button>
                        </div>
                    </div>
                    <div class="aula-item-body">
                    <div class="row g-2 mb-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-secondary mb-1">Título</label>
                            <input class="form-control form-control-sm" data-field="NomeAula" value="${esc(a.NomeAula)}">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-bold text-uppercase text-secondary mb-1">Duração (min)</label>
                            <input type="number" min="1" class="form-control form-control-sm" data-field="DuracaoMinutos" value="${Number(a.DuracaoMinutos)}">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-bold text-uppercase text-secondary mb-1">Categoria</label>
                            <input class="form-control form-control-sm" data-field="Categoria" value="${esc(a.Categoria || '')}">
                        </div>
                        <div class="col-12 col-md-2 d-grid">
                            <label class="form-label mb-1">&nbsp;</label>
                            <button class="btn btn-success btn-sm fw-bold" data-action="save"><i class="fa-solid fa-check me-1"></i>Salvar</button>
                        </div>
                    </div>
                    <div class="pc-soft-box">
                        <div class="pc-section-subtitle mb-2"><i class="fa-solid fa-paperclip me-1"></i> Anexos da aula</div>
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-bold text-uppercase text-secondary mb-1">Arquivo</label>
                                <input type="file" class="form-control form-control-sm" data-field="anexo">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-bold text-uppercase text-secondary mb-1">Nome do arquivo</label>
                                <input type="text" class="form-control form-control-sm" data-field="nomeArquivo" placeholder="Nome visível do anexo">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-bold text-uppercase text-secondary mb-1">Descrição</label>
                                <input type="text" class="form-control form-control-sm" data-field="descricao" placeholder="Descrição (opcional)">
                            </div>
                            <div class="col-6 col-md-2 d-grid">
                                <button class="btn btn-outline-primary btn-sm" data-action="upload"><i class="fa-solid fa-upload me-1"></i>Enviar</button>
                            </div>
                            <div class="col-6 col-md-2 d-grid">
                                <button class="btn btn-outline-secondary btn-sm" data-action="list-anexos"><i class="fa-regular fa-eye me-1"></i>Ver</button>
                            </div>
                        </div>
                    </div>
                    <div class="small mt-2" data-box="anexos" style="display:none;"></div>
                    <div class="pc-soft-box todo-wrap">
                        <div class="todo-header">
                            <h4 class="todo-title mb-0"><i class="fa-solid fa-list-check me-1"></i>T&oacute;picos da aula</h4>
                        </div>
                        <div class="todo-add">
                            <input type="text" class="form-control form-control-sm" data-field="todoNovoTexto" placeholder="Novo t&oacute;pico">
                            <button class="btn btn-primary btn-sm" type="button" data-action="todo-add" aria-label="Adicionar t&oacute;pico">+</button>
                        </div>
                        <div class="todo-list" data-box="todo-list">
                            <div class="todo-empty">Carregando t&oacute;picos...</div>
                        </div>
                    </div>
                    </div>
                </div>
            `).join('');

            el.querySelectorAll('.aula-item button').forEach((btn) => {
                btn.addEventListener('click', async (e) => {
                    const item = e.target.closest('.aula-item');
                    const id = Number(item.dataset.id);
                    const action = btn.dataset.action;
                    if (action === 'todo-add') {
                        await adicionarTopicoAula(item, id);
                        return;
                    }
                    if (action === 'up' || action === 'down') {
                        moverAula(id, action);
                        return;
                    }
                    if (action === 'delete') {
                        await excluirAula(id);
                        return;
                    }
                    if (action === 'save') {
                        await salvarAula(item, id);
                        return;
                    }
                    if (action === 'upload') {
                        await uploadAnexosAula(item, id);
                        return;
                    }
                    if (action === 'list-anexos') {
                        await listarAnexosAula(item, id, true);
                    }
                });
            });
            el.querySelectorAll('.aula-item [data-field="anexo"]').forEach((inputFile) => {
                inputFile.addEventListener('change', () => {
                    const item = inputFile.closest('.aula-item');
                    if (!item) return;
                    const nomeInput = item.querySelector('[data-field="nomeArquivo"]');
                    const file = inputFile.files && inputFile.files[0] ? inputFile.files[0] : null;
                    if (nomeInput && file) {
                        nomeInput.value = file.name || '';
                    }
                });
            });

            el.querySelectorAll('.aula-item').forEach((aulaItem) => {
                const idAula = Number(aulaItem.dataset.id || 0);
                if (idAula <= 0) return;
                renderTopicosAula(idAula);
            });
        }

        async function listarAnexosAula(item, idAula, toggle = false) {
            const box = item.querySelector('[data-box="anexos"]');
            if (!box) return;
            if (toggle) {
                box.style.display = box.style.display === 'none' ? 'block' : 'none';
                if (box.style.display === 'none') return;
            } else {
                box.style.display = 'block';
            }
            const data = await apiFetch(`/planos-curso/aulas/${idAula}/anexos`);
            const lista = data.data || [];
            if (!lista.length) {
                box.innerHTML = '<span class="text-muted">Sem anexos.</span>';
                return;
            }
            box.innerHTML = lista.map((a) => `
                <div>
                    <a href="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/${esc(a.CaminhoRelativo || '')}" target="_blank" rel="noopener noreferrer">
                        ${esc(a.NomeArquivo || a.NomeOriginal || a.NomeFisico || 'Arquivo')}
                    </a>
                    <span class="text-muted">(${esc(a.MimeType || '')})</span>
                    ${a.Descricao ? `<div class="text-muted">${esc(a.Descricao)}</div>` : ''}
                </div>
            `).join('');
        }

        async function uploadAnexosAula(item, idAula) {
            const input = item.querySelector('[data-field="anexo"]');
            const nomeArquivoInput = item.querySelector('[data-field="nomeArquivo"]');
            const descricaoInput = item.querySelector('[data-field="descricao"]');
            if (!input || !input.files || !input.files.length) {
                showStatus('Selecione um arquivo para upload.', 'warning');
                toastWarn('Selecione um arquivo para upload.');
                return;
            }
            const form = new FormData();
            form.append('arquivo', input.files[0]);
            form.append('NomeArquivo', (nomeArquivoInput?.value || '').trim());
            form.append('Descricao', (descricaoInput?.value || '').trim());
            await apiFetch(`/planos-curso/aulas/${idAula}/anexos`, {
                method: 'POST',
                body: form
            });
            input.value = '';
            if (nomeArquivoInput) nomeArquivoInput.value = '';
            if (descricaoInput) descricaoInput.value = '';
            await listarAnexosAula(item, idAula, false);
            toastOk('Anexo(s) enviado(s).');
            showStatus('Anexo(s) enviados com sucesso.', 'success');
        }

        async function carregarTopicosAula(idAula, { silencioso = true } = {}) {
            try {
                const result = await apiFetch(`/planos-curso/aulas/${idAula}/todos`);
                todosPorAula.set(idAula, result.data || []);
                renderTopicosAula(idAula);
            } catch (err) {
                if (!silencioso) {
                    toastErr(err.message);
                    showStatus(err.message, 'danger');
                }
                const aulaItem = document.querySelector(`.aula-item[data-id="${idAula}"]`);
                const box = aulaItem?.querySelector('[data-box="todo-list"]');
                if (box) {
                    box.innerHTML = '<div class="todo-empty">Falha ao carregar t&oacute;picos.</div>';
                }
            }
        }

        async function carregarTopicosDasAulas() {
            const idsAula = aulasOrdemEditada.map((a) => Number(a.IdPlanoCursoAula)).filter((id) => id > 0);
            if (!idsAula.length) return;
            await Promise.all(idsAula.map((idAula) => carregarTopicosAula(idAula, { silencioso: true })));
        }

        function renderTopicosAula(idAula) {
            const aulaItem = document.querySelector(`.aula-item[data-id="${idAula}"]`);
            if (!aulaItem) return;

            const box = aulaItem.querySelector('[data-box="todo-list"]');
            if (!box) return;

            if (!todosPorAula.has(idAula)) {
                box.innerHTML = '<div class="todo-empty">Carregando t&oacute;picos...</div>';
                return;
            }

            const topicos = todosPorAula.get(idAula) || [];
            if (!topicos.length) {
                box.innerHTML = '<div class="todo-empty">Sem t&oacute;picos cadastrados.</div>';
                inicializarSortableTopicosAula(aulaItem, idAula);
                vincularEventosTopicosAula(aulaItem, idAula);
                return;
            }

            box.innerHTML = topicos.map((topico) => {
                const idTodo = Number(topico.IdPlanoCursoAulaTodo || 0);
                const concluido = Number(topico.Concluido || 0) === 1;
                const corHex = normalizarCorHex(topico.CorHex || '#FDE68A');
                const nomeConcluido = `${String(topico.NomeConcluidoPor || '').trim()} ${String(topico.SobrenomeConcluidoPor || '').trim()}`.trim() || 'Usu\u00E1rio';
                const fotoConcluido = String(topico.FotoConcluidoPor || '').trim();
                const avatarUrl = fotoColaboradorUrl(fotoConcluido || FOTO_USUARIO_LOGADO);
                const chipsCores = CORES_TODO.map((cor) => `
                    <button
                        type="button"
                        class="todo-color-option"
                        data-action="todo-cor-chip"
                        data-color="${escAttr(cor)}"
                        title="Aplicar cor ${escAttr(cor)}"
                        style="background:${escAttr(cor)};">
                    </button>
                `).join('');

                return `
                    <div class="todo-item ${concluido ? 'is-done' : ''}" data-todo-id="${idTodo}" data-saved-text="${escAttr(topico.TextoTopico || '')}" data-saved-color="${escAttr(corHex)}" data-current-color="${escAttr(corHex)}" style="background:${escAttr(corHex)};">
                        ${concluido ? `
                            <div class="todo-avatar" title="Conclu\u00EDdo por ${escAttr(nomeConcluido)}">
                                <img src="${escAttr(avatarUrl)}" alt="${escAttr(nomeConcluido)}">
                            </div>
                        ` : ''}
                        <div class="todo-item-main">
                            <input type="checkbox" class="todo-toggle" data-action="todo-toggle" ${concluido ? 'checked' : ''} aria-label="Marcar t&oacute;pico como conclu&iacute;do">
                            <input type="text" class="form-control form-control-sm todo-text" data-field="todoTexto" maxlength="500" value="${escAttr(topico.TextoTopico || '')}">
                        </div>
                        <div class="todo-actions">
                            <div class="todo-actions-left">
                                <div class="todo-color-wrap">
                                    <button type="button" class="todo-color-toggle" data-action="todo-color-toggle" aria-label="Escolher cor do t&oacute;pico" title="Escolher cor do t&oacute;pico">
                                        <span class="todo-color-dot" data-box="todo-color-dot" style="background:${escAttr(corHex)};"></span>
                                    </button>
                                    <div class="todo-color-menu" data-box="todo-color-menu">
                                        ${chipsCores}
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-outline-danger btn-sm" data-action="todo-delete"><i class="fa-regular fa-trash-can"></i></button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            vincularEventosTopicosAula(aulaItem, idAula);
            inicializarSortableTopicosAula(aulaItem, idAula);
        }

        function vincularEventosTopicosAula(aulaItem, idAula) {
            garantirEventosGlobaisPaletaCor();

            const novoTopicoInput = aulaItem.querySelector('[data-field="todoNovoTexto"]');
            const btnAdicionarTopico = aulaItem.querySelector('[data-action="todo-add"]');
            if (novoTopicoInput && !novoTopicoInput.dataset.boundEnter) {
                novoTopicoInput.dataset.boundEnter = '1';
                novoTopicoInput.addEventListener('keydown', (evt) => {
                    if (evt.key === 'Enter') {
                        evt.preventDefault();
                        if (btnAdicionarTopico) {
                            btnAdicionarTopico.click();
                        } else {
                            void adicionarTopicoAula(aulaItem, idAula);
                        }
                    }
                });
            }

            aulaItem.querySelectorAll('.todo-item [data-action="todo-toggle"]').forEach((checkbox) => {
                checkbox.addEventListener('change', async () => {
                    const todoItem = checkbox.closest('.todo-item');
                    const idTodo = Number(todoItem?.dataset.todoId || 0);
                    if (idTodo > 0) {
                        await atualizarStatusTopicoAula(aulaItem, idAula, idTodo, checkbox.checked);
                    }
                });
            });

            aulaItem.querySelectorAll('.todo-item [data-action="todo-color-toggle"]').forEach((btnCor) => {
                btnCor.addEventListener('click', (evt) => {
                    evt.stopPropagation();
                    const wrap = btnCor.closest('.todo-color-wrap');
                    if (!wrap) return;
                    const abrir = !wrap.classList.contains('is-open');
                    fecharPaletasCorTopicos();
                    if (abrir) {
                        definirEstadoPaletaCor(wrap, true);
                    }
                });
            });

            aulaItem.querySelectorAll('.todo-item [data-action="todo-cor-chip"]').forEach((chip) => {
                chip.addEventListener('click', async () => {
                    const todoItem = chip.closest('.todo-item');
                    const idTodo = Number(todoItem?.dataset.todoId || 0);
                    if (idTodo <= 0) return;
                    const corHex = normalizarCorHex(chip.dataset.color || '#FDE68A');
                    const dot = todoItem.querySelector('[data-box="todo-color-dot"]');
                    if (dot) {
                        dot.style.background = corHex;
                    }
                    todoItem.style.background = corHex;
                    todoItem.dataset.currentColor = corHex;
                    const menu = chip.closest('[data-box="todo-color-menu"]');
                    if (menu) {
                        const wrap = menu.closest('.todo-color-wrap');
                        if (wrap) {
                            definirEstadoPaletaCor(wrap, false);
                        }
                    }
                    await agendarAutoSaveTopico(aulaItem, idAula, idTodo, todoItem, { imediato: true, tooltip: 'Cor salva automaticamente' });
                });
            });

            aulaItem.querySelectorAll('.todo-item [data-action="todo-delete"]').forEach((btnExcluir) => {
                btnExcluir.addEventListener('click', async () => {
                    const todoItem = btnExcluir.closest('.todo-item');
                    const idTodo = Number(todoItem?.dataset.todoId || 0);
                    if (idTodo > 0) {
                        await excluirTopicoAula(aulaItem, idAula, idTodo);
                    }
                });
            });

            aulaItem.querySelectorAll('.todo-item [data-field="todoTexto"]').forEach((inputTexto) => {
                inputTexto.addEventListener('input', async () => {
                    const todoItem = inputTexto.closest('.todo-item');
                    const idTodo = Number(todoItem?.dataset.todoId || 0);
                    if (idTodo > 0) {
                        await agendarAutoSaveTopico(aulaItem, idAula, idTodo, todoItem, { tooltip: 'T\u00F3pico salvo automaticamente' });
                    }
                });
                inputTexto.addEventListener('blur', async () => {
                    const todoItem = inputTexto.closest('.todo-item');
                    const idTodo = Number(todoItem?.dataset.todoId || 0);
                    if (idTodo > 0) {
                        await agendarAutoSaveTopico(aulaItem, idAula, idTodo, todoItem, { imediato: true, tooltip: 'T\u00F3pico salvo automaticamente' });
                    }
                });
                inputTexto.addEventListener('keydown', async (evt) => {
                    if (evt.key === 'Enter') {
                        evt.preventDefault();
                        const todoItem = inputTexto.closest('.todo-item');
                        const idTodo = Number(todoItem?.dataset.todoId || 0);
                        if (idTodo > 0) {
                            await agendarAutoSaveTopico(aulaItem, idAula, idTodo, todoItem, { imediato: true, tooltip: 'T\u00F3pico salvo automaticamente' });
                        }
                    }
                });
            });
        }

        function inicializarSortableTopicosAula(aulaItem, idAula) {
            const box = aulaItem.querySelector('[data-box="todo-list"]');
            if (!box) return;

            const sortableAtual = sortablesTodoPorAula.get(idAula);
            if (sortableAtual) {
                try {
                    sortableAtual.destroy();
                } catch (err) {
                    console.warn('Falha ao destruir sortable antigo de topicos.', err);
                }
                sortablesTodoPorAula.delete(idAula);
            }

            if (!window.Sortable) return;
            if (box.querySelectorAll('.todo-item').length < 2) return;

            const sortable = Sortable.create(box, {
                animation: 150,
                draggable: '.todo-item',
                handle: '.todo-item',
                filter: 'input,button,a,label,[data-box="todo-color-menu"]',
                preventOnFilter: false,
                ghostClass: 'todo-ghost',
                onEnd: async () => {
                    await persistirOrdemTopicosAula(aulaItem, idAula);
                }
            });
            sortablesTodoPorAula.set(idAula, sortable);
        }

        async function adicionarTopicoAula(aulaItem, idAula) {
            const input = aulaItem.querySelector('[data-field="todoNovoTexto"]');
            const textoTopico = String(input?.value || '').trim();
            if (!textoTopico) {
                toastWarn('Informe o texto do t\u00F3pico.');
                return;
            }

            await apiFetch(`/planos-curso/aulas/${idAula}/todos`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    TextoTopico: textoTopico,
                    CorHex: '#FDE68A'
                })
            });

            if (input) input.value = '';
            await carregarTopicosAula(idAula);
            toastOk('T\u00F3pico adicionado.');
            showStatus('T\u00F3pico adicionado com sucesso.', 'success');
        }

        async function agendarAutoSaveTopico(aulaItem, idAula, idTodo, todoItem, { imediato = false, tooltip = 'T\u00F3pico salvo automaticamente' } = {}) {
            if (!estadoAutoSaveTopico[idTodo]) {
                estadoAutoSaveTopico[idTodo] = { timer: null, salvando: false, pendente: false, tooltip };
            }
            const estado = estadoAutoSaveTopico[idTodo];
            estado.tooltip = tooltip;

            if (estado.timer) {
                clearTimeout(estado.timer);
                estado.timer = null;
            }

            if (imediato) {
                await executarAutoSaveTopico(aulaItem, idAula, idTodo, todoItem);
                return;
            }

            estado.timer = setTimeout(() => {
                void executarAutoSaveTopico(aulaItem, idAula, idTodo, todoItem);
            }, 500);
        }

        async function executarAutoSaveTopico(aulaItem, idAula, idTodo, todoItem) {
            const estado = estadoAutoSaveTopico[idTodo] || { timer: null, salvando: false, pendente: false, tooltip: 'T\u00F3pico salvo automaticamente' };
            estadoAutoSaveTopico[idTodo] = estado;
            if (estado.salvando) {
                estado.pendente = true;
                return;
            }

            estado.salvando = true;
            try {
                const salvo = await salvarTopicoAula(aulaItem, idAula, idTodo, todoItem, { silencioso: true, tooltip: estado.tooltip });
                if (!salvo) {
                    return;
                }
            } finally {
                estado.salvando = false;
                if (estado.pendente) {
                    estado.pendente = false;
                    await executarAutoSaveTopico(aulaItem, idAula, idTodo, todoItem);
                }
            }
        }

        async function salvarTopicoAula(aulaItem, idAula, idTodo, todoItem, { silencioso = false, tooltip = '' } = {}) {
            const inputTexto = todoItem.querySelector('[data-field="todoTexto"]');
            const dotCor = todoItem.querySelector('[data-box="todo-color-dot"]');
            const textoTopico = String(inputTexto?.value || '').trim();
            const corHex = normalizarCorHex(todoItem.dataset.currentColor || '#FDE68A');
            const textoSalvo = String(todoItem.dataset.savedText || '');
            const corSalva = normalizarCorHex(todoItem.dataset.savedColor || '#FDE68A');

            if (!textoTopico) {
                toastWarn('Informe o texto do t\u00F3pico.');
                return false;
            }
            if (textoTopico === textoSalvo && corHex === corSalva) {
                return false;
            }

            await apiFetch(`/planos-curso/aulas/todos/${idTodo}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    TextoTopico: textoTopico,
                    CorHex: corHex
                })
            });

            todoItem.dataset.savedText = textoTopico;
            todoItem.dataset.savedColor = corHex;
            todoItem.dataset.currentColor = corHex;
            todoItem.style.background = corHex;
            if (dotCor) {
                dotCor.style.background = corHex;
            }

            const topicos = todosPorAula.get(idAula) || [];
            const idx = topicos.findIndex((row) => Number(row.IdPlanoCursoAulaTodo) === idTodo);
            if (idx >= 0) {
                topicos[idx].TextoTopico = textoTopico;
                topicos[idx].CorHex = corHex;
                todosPorAula.set(idAula, topicos);
            }

            if (!silencioso) {
                toastOk('T\u00F3pico atualizado.');
                showStatus('T\u00F3pico atualizado com sucesso.', 'success');
            } else if (tooltip) {
                mostrarTooltipTopico(todoItem, tooltip);
            }
            return true;
        }

        async function atualizarStatusTopicoAula(aulaItem, idAula, idTodo, concluido) {
            await apiFetch(`/planos-curso/aulas/todos/${idTodo}/status`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ Concluido: !!concluido })
            });

            await carregarTopicosAula(idAula);
            const alvo = aulaItem.querySelector(`.todo-item[data-todo-id="${idTodo}"]`);
            mostrarTooltipTopico(alvo, concluido ? 'Status salvo: conclu\u00EDdo' : 'Status salvo: pendente');
        }

        async function excluirTopicoAula(aulaItem, idAula, idTodo) {
            const ok = await openUxConfirm({
                title: 'Excluir t\u00F3pico',
                message: 'Essa a\u00E7\u00E3o remove o t\u00F3pico da aula. Deseja continuar?',
                confirmText: 'Excluir',
                confirmClass: 'btn-danger'
            });
            if (!ok) return;

            await apiFetch(`/planos-curso/aulas/todos/${idTodo}`, {
                method: 'DELETE'
            });

            await carregarTopicosAula(idAula);
            toastOk('T\u00F3pico exclu\u00EDdo.');
            showStatus('T\u00F3pico exclu\u00EDdo com sucesso.', 'success');
        }

        async function persistirOrdemTopicosAula(aulaItem, idAula) {
            const box = aulaItem.querySelector('[data-box="todo-list"]');
            if (!box) return;

            const ids = Array.from(box.querySelectorAll('.todo-item'))
                .map((el) => Number(el.dataset.todoId || 0))
                .filter((id) => id > 0);
            if (ids.length < 2) return;
            if (salvandoOrdemTodoAula[idAula]) return;

            salvandoOrdemTodoAula[idAula] = true;
            box.classList.add('todo-saving');
            try {
                await apiFetch(`/planos-curso/aulas/${idAula}/todos/reordenar`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ Itens: ids })
                });
                await carregarTopicosAula(idAula);
            } catch (err) {
                toastErr(err.message);
                showStatus(err.message, 'danger');
                await carregarTopicosAula(idAula);
            } finally {
                salvandoOrdemTodoAula[idAula] = false;
                box.classList.remove('todo-saving');
            }
        }

        function moverAula(idAula, direcao) {
            const idx = aulasOrdemEditada.findIndex((a) => Number(a.IdPlanoCursoAula) === idAula);
            if (idx < 0) return;
            const novoIdx = direcao === 'up' ? idx - 1 : idx + 1;
            if (novoIdx < 0 || novoIdx >= aulasOrdemEditada.length) return;
            const tmp = aulasOrdemEditada[idx];
            aulasOrdemEditada[idx] = aulasOrdemEditada[novoIdx];
            aulasOrdemEditada[novoIdx] = tmp;
            ordemAlterada = true;
            document.getElementById('btnSalvarOrdem').disabled = false;
            renderAulas();
            void persistirOrdemAulas({ silencioso: true });
        }

        async function salvarAula(item, idAula) {
            const nome = item.querySelector('[data-field="NomeAula"]').value.trim();
            const duracao = Number(item.querySelector('[data-field="DuracaoMinutos"]').value);
            const categoria = item.querySelector('[data-field="Categoria"]').value.trim();
            await apiFetch(`/planos-curso/aulas/${idAula}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ NomeAula: nome, DuracaoMinutos: duracao, Categoria: categoria })
            });
            toastOk('Alteração salva.');
            showStatus('Aula atualizada com sucesso.', 'success');
            await carregarAulas(planoSelecionado.IdPlanoCurso);
        }

        async function excluirAula(idAula) {
            const ok = await openUxConfirm({
                title: 'Excluir aula',
                message: 'Essa ação remove a aula do plano. Deseja continuar?',
                confirmText: 'Excluir',
                confirmClass: 'btn-danger'
            });
            if (!ok) return;
            await apiFetch(`/planos-curso/aulas/${idAula}`, { method: 'DELETE' });
            toastOk('Aula excluída.');
            showStatus('Aula excluída.', 'success');
            await carregarAulas(planoSelecionado.IdPlanoCurso);
            await carregarPlanos();
        }

        async function carregarPlanos() {
            const result = await apiFetch(`/planos-curso?IdCurso=${ID_CURSO}`);
            planos = result.data || [];
            renderPlanos();
            if (!planoSelecionado && planos.length > 0) {
                await selecionarPlano(Number(planos[0].IdPlanoCurso));
            }
        }

        async function selecionarPlano(idPlanoCurso) {
            const result = await apiFetch(`/planos-curso/${idPlanoCurso}`);
            planoSelecionado = result.data;
            document.getElementById('semPlanoSelecionado').style.display = 'none';
            document.getElementById('conteudoPlano').style.display = '';
            document.getElementById('planoIdEdit').value = Number(planoSelecionado.IdPlanoCurso);
            document.getElementById('planoNomeEdit').value = planoSelecionado.NomePlano || '';
            document.getElementById('planoVersaoEdit').value = planoSelecionado.Versao || '';
            renderPlanos();
            await carregarTurmasCurso();
            await carregarAulas(idPlanoCurso);
        }

        async function carregarAulas(idPlanoCurso) {
            const result = await apiFetch(`/planos-curso/${idPlanoCurso}/aulas`);
            aulas = result.data || [];
            aulasOrdemEditada = [...aulas];
            ordemAlterada = false;
            document.getElementById('btnSalvarOrdem').disabled = true;
            destruirSortablesTodos();
            todosPorAula = new Map();
            renderAulas();
            await carregarTopicosDasAulas();
        }

        function atualizarSelectsTurmaVinculo() {
            const selectVinculo = document.getElementById('selectTurmaVinculo');
            const selectDesvinculo = document.getElementById('selectTurmaDesvinculo');
            const idPlanoAtual = Number(planoSelecionado?.IdPlanoCurso || 0);

            const turmasDisponiveis = turmasCursoComPlano.filter((t) => !Number(t.IdPlanoCurso || 0));
            const turmasVinculadas = turmasCursoComPlano.filter((t) => Number(t.IdPlanoCurso || 0) > 0);

            selectVinculo.innerHTML = turmasDisponiveis.map((t) => {
                const ativa = Number(t.Habilitado || 0) === 1;
                const sufixo = ativa ? '' : ' (Concluída/Inativa)';
                return `<option value="${Number(t.IdTurma)}">${esc(t.NomeTurma)}${sufixo}</option>`;
            }).join('');

            selectDesvinculo.innerHTML = turmasVinculadas.map((t) => {
                const ativa = Number(t.Habilitado || 0) === 1;
                const sufixo = ativa ? '' : ' (Concluída/Inativa)';
                const planoInfo = t.NomePlano ? ` - ${esc(t.NomePlano)}${t.Versao ? ` (v${esc(t.Versao)})` : ''}` : '';
                return `<option value="${Number(t.IdTurma)}">${esc(t.NomeTurma)}${planoInfo}${sufixo}</option>`;
            }).join('');

            if (turmaVinculoTomSelect) turmaVinculoTomSelect.destroy();
            if (turmaDesvinculoTomSelect) turmaDesvinculoTomSelect.destroy();

            turmaVinculoTomSelect = new TomSelect('#selectTurmaVinculo', {
                placeholder: 'Selecione uma ou mais turmas',
                plugins: ['remove_button'],
                create: false,
                maxOptions: 500
            });
            turmaDesvinculoTomSelect = new TomSelect('#selectTurmaDesvinculo', {
                placeholder: idPlanoAtual > 0 ? 'Selecione turma(s) com plano para desvincular' : 'Selecione turma(s) com plano',
                plugins: ['remove_button'],
                create: false,
                maxOptions: 500
            });

            if (!turmasDisponiveis.length) {
                toastInfo('Não há turmas disponíveis para novo vínculo.');
            }
        }

        async function carregarTurmasCurso() {
            const result = await apiFetch(`/cursos/${ID_CURSO}/turmas-com-plano`);
            turmasCursoComPlano = result.data || [];
            atualizarSelectsTurmaVinculo();
        }

        async function recarregarSelectsTurma() {
            try {
                await carregarTurmasCurso();
                if (turmaVinculoTomSelect) turmaVinculoTomSelect.clear(true);
                if (turmaDesvinculoTomSelect) turmaDesvinculoTomSelect.clear(true);
            } catch (err) {
                console.warn('Falha ao recarregar selects de turma.', err);
            }
        }

        async function persistirOrdemAulas({ silencioso = false } = {}) {
            if (!planoSelecionado || !ordemAlterada || salvandoOrdem) return false;
            const modo = 'A';

            salvandoOrdem = true;
            try {
                await apiFetch(`/planos-curso/${Number(planoSelecionado.IdPlanoCurso)}/aulas/reordenar`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ Modo: modo, Aulas: aulasOrdemEditada.map((a) => Number(a.IdPlanoCursoAula)) })
                });
                ordemAlterada = false;
                document.getElementById('btnSalvarOrdem').disabled = true;
                await carregarAulas(Number(planoSelecionado.IdPlanoCurso));
                await carregarPlanos();
                if (!silencioso) {
                    toastOk(`Ordem salva (modo ${modo}).`);
                    showStatus(`Ordem salva (modo ${modo}).`, 'success');
                }
                return true;
            } catch (err) {
                toastErr(err.message);
                showStatus(err.message, 'danger');
                return false;
            } finally {
                salvandoOrdem = false;
            }
        }

        async function carregarImpactoExclusaoPlano(idPlanoCurso) {
            const result = await apiFetch(`/planos-curso/${Number(idPlanoCurso)}/impacto-exclusao`);
            return result.data || {};
        }

        document.getElementById('formNovoPlano')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const nome = document.getElementById('novoNomePlano').value.trim();
                const versao = document.getElementById('novoVersaoPlano').value.trim();
                const result = await apiFetch('/planos-curso', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ IdCurso: ID_CURSO, NomePlano: nome, Versao: versao })
                });
                document.getElementById('novoNomePlano').value = '';
                document.getElementById('novoVersaoPlano').value = '';
                await carregarPlanos();
                await selecionarPlano(Number(result.data.id));
                toastOk('Plano criado.');
                showStatus('Plano criado com sucesso.', 'success');
            } catch (err) {
                toastErr(err.message);
                showStatus(err.message, 'danger');
            }
        });

        document.getElementById('formEditarPlano')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!planoSelecionado) return;
            try {
                await apiFetch(`/planos-curso/${Number(planoSelecionado.IdPlanoCurso)}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        NomePlano: document.getElementById('planoNomeEdit').value.trim(),
                        Versao: document.getElementById('planoVersaoEdit').value.trim()
                    })
                });
                toastOk('Plano atualizado.');
                showStatus('Plano atualizado.', 'success');
                await carregarPlanos();
                await selecionarPlano(Number(planoSelecionado.IdPlanoCurso));
            } catch (err) {
                toastErr(err.message);
                showStatus(err.message, 'danger');
            }
        });

        document.getElementById('btnDuplicarPlano')?.addEventListener('click', async () => {
            if (!planoSelecionado) return;
            const nome = await openUxPrompt({
                title: 'Duplicar plano',
                message: 'Informe o nome do novo plano duplicado:',
                label: 'Nome do novo plano',
                initialValue: `${planoSelecionado.NomePlano} (Cópia)`,
                confirmText: 'Duplicar'
            });
            if (!nome) return;
            try {
                const result = await apiFetch(`/planos-curso/${Number(planoSelecionado.IdPlanoCurso)}/duplicar`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ NomePlano: nome, Versao: planoSelecionado.Versao || '' })
                });
                await carregarPlanos();
                await selecionarPlano(Number(result.data.id));
                toastOk('Plano duplicado.');
                showStatus('Plano duplicado.', 'success');
            } catch (err) {
                toastErr(err.message);
                showStatus(err.message, 'danger');
            }
        });

        document.getElementById('btnExcluirPlano')?.addEventListener('click', async () => {
            if (!planoSelecionado) return;
            try {
                const idPlanoCurso = Number(planoSelecionado.IdPlanoCurso);
                const impacto = await carregarImpactoExclusaoPlano(idPlanoCurso);
                const qtdTurmas = Number(impacto.QtdTurmas || 0);
                const qtdVinculos = Number(impacto.QtdVinculos || 0);
                const qtdCronogramas = Number(impacto.QtdCronogramas || 0);
                const qtdCronogramasAgendados = Number(impacto.QtdCronogramasAgendados || 0);
                const temImpacto = qtdVinculos > 0 || qtdCronogramas > 0;

                const mensagem = temImpacto
                    ? `Este plano possui ${qtdTurmas} turma(s) vinculada(s), ${qtdVinculos} vínculo(s) e ${qtdCronogramas} registro(s) de cronograma (${qtdCronogramasAgendados} agendado(s)). Excluir apagará esses dados em cascata.`
                    : 'Essa ação exclui o plano de curso permanentemente. Deseja continuar?';

                const ok = await openUxConfirm({
                    title: temImpacto ? 'Plano com turmas e cronogramas vinculados' : 'Excluir plano',
                    message: mensagem,
                    confirmText: temImpacto ? 'Excluir mesmo assim' : 'Excluir',
                    confirmClass: 'btn-danger'
                });
                if (!ok) return;

                await apiFetch(`/planos-curso/${idPlanoCurso}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ForcarExclusao: temImpacto })
                });
                planoSelecionado = null;
                document.getElementById('conteudoPlano').style.display = 'none';
                document.getElementById('semPlanoSelecionado').style.display = '';
                await carregarPlanos();
                atualizarSelectsTurmaVinculo();
                toastOk('Plano excluído.');
                showStatus('Plano excluído.', 'success');
            } catch (err) {
                toastErr(err.message);
                showStatus(err.message, 'danger');
            }
        });

        document.getElementById('formNovaAula')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!planoSelecionado) return;
            try {
                await apiFetch(`/planos-curso/${Number(planoSelecionado.IdPlanoCurso)}/aulas`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        NomeAula: document.getElementById('aulaNome').value.trim(),
                        DuracaoMinutos: Number(document.getElementById('aulaDuracao').value),
                        Categoria: document.getElementById('aulaCategoria').value.trim()
                    })
                });
                document.getElementById('aulaNome').value = '';
                document.getElementById('aulaDuracao').value = '';
                document.getElementById('aulaCategoria').value = '';
                await carregarAulas(Number(planoSelecionado.IdPlanoCurso));
                await carregarPlanos();
                toastOk('Aula criada.');
                showStatus('Aula adicionada com sucesso.', 'success');
            } catch (err) {
                toastErr(err.message);
                showStatus(err.message, 'danger');
            }
        });

        document.getElementById('btnSalvarOrdem')?.addEventListener('click', async () => {
            await persistirOrdemAulas({ silencioso: false });
        });

        document.getElementById('btnVincularPlanoTurma')?.addEventListener('click', async () => {
            if (!planoSelecionado) return;
            const selecionadas = turmaVinculoTomSelect
                ? turmaVinculoTomSelect.getValue()
                : Array.from(document.getElementById('selectTurmaVinculo').selectedOptions).map((o) => o.value);
            const idsTurma = (Array.isArray(selecionadas) ? selecionadas : String(selecionadas || '').split(','))
                .map((v) => Number(v))
                .filter((v) => v > 0);
            if (!idsTurma.length) {
                showStatus('Selecione ao menos uma turma válida.', 'warning');
                toastWarn('Selecione ao menos uma turma válida.');
                return;
            }
            let deveRecarregar = false;
            try {
                const results = await Promise.allSettled(idsTurma.map((idTurma) => apiFetch(`/turmas/${idTurma}/plano-curso/vincular`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ IdPlanoCurso: Number(planoSelecionado.IdPlanoCurso) })
                })));
                const ok = results.filter((r) => r.status === 'fulfilled').length;
                const falhas = results.length - ok;
                await carregarPlanos();
                if (falhas === 0) {
                    toastOk(`Plano vinculado com sucesso em ${ok} turma(s).`);
                    showStatus(`Plano vinculado com sucesso em ${ok} turma(s).`, 'success');
                } else {
                    toastWarn(`Vínculo concluído parcialmente: ${ok} sucesso(s), ${falhas} falha(s).`);
                    showStatus(`Vínculo parcial: ${ok} sucesso(s), ${falhas} falha(s).`, 'warning');
                }
                deveRecarregar = true;
            } catch (err) {
                toastErr(err.message);
                showStatus(err.message, 'danger');
            } finally {
                await recarregarSelectsTurma();
                if (deveRecarregar) {
                    window.location.reload();
                }
            }
        });

        document.getElementById('btnDesvincularPlanoTurma')?.addEventListener('click', async () => {
            if (!planoSelecionado) {
                toastWarn('Selecione um plano antes de desvincular turmas.');
                return;
            }
            const selecionadas = turmaDesvinculoTomSelect
                ? turmaDesvinculoTomSelect.getValue()
                : Array.from(document.getElementById('selectTurmaDesvinculo').selectedOptions).map((o) => o.value);
            const idsTurma = (Array.isArray(selecionadas) ? selecionadas : String(selecionadas || '').split(','))
                .map((v) => Number(v))
                .filter((v) => v > 0);
            if (!idsTurma.length) {
                toastWarn('Selecione ao menos uma turma para desvincular.');
                return;
            }

            const ok = await openUxConfirm({
                title: 'Desvincular turmas do plano',
                message: `Confirma desvincular ${idsTurma.length} turma(s) deste plano?`,
                confirmText: 'Desvincular',
                confirmClass: 'btn-danger'
            });
            if (!ok) return;

            let deveRecarregar = false;
            try {
                const results = await Promise.allSettled(idsTurma.map((idTurma) => apiFetch(`/turmas/${idTurma}/plano-curso/desvincular`, {
                    method: 'POST'
                })));
                const sucesso = results.filter((r) => r.status === 'fulfilled').length;
                const falhas = results.length - sucesso;
                await carregarPlanos();
                if (falhas === 0) {
                    toastOk(`Desvínculo concluído em ${sucesso} turma(s).`);
                    showStatus(`Desvínculo concluído em ${sucesso} turma(s).`, 'success');
                } else {
                    toastWarn(`Desvínculo parcial: ${sucesso} sucesso(s), ${falhas} falha(s).`);
                    showStatus(`Desvínculo parcial: ${sucesso} sucesso(s), ${falhas} falha(s).`, 'warning');
                }
                deveRecarregar = true;
            } catch (err) {
                toastErr(err.message);
                showStatus(err.message, 'danger');
            } finally {
                await recarregarSelectsTurma();
                if (deveRecarregar) {
                    window.location.reload();
                }
            }
        });

        document.addEventListener('DOMContentLoaded', async () => {
            try {
                await Promise.all([carregarPlanos(), carregarTurmasCurso()]);
                showStatus('Módulo carregado.', 'muted');
            } catch (err) {
                toastErr(err.message);
                showStatus(err.message, 'danger');
            }
        });
    </script>
</body>
</html>

