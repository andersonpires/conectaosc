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
        .icon-label { display: inline-flex; align-items: center; gap: .45rem; }
        .icon-label i { color: #1e4cad; }
        .form-control:focus, .form-select:focus { border-color: #9db7e8; box-shadow: 0 0 0 .2rem rgba(13, 78, 194, .12); }
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
                        <i class="fa-solid ${planoSelecionado && Number(planoSelecionado.IdPlanoCurso) === Number(p.IdPlanoCurso) ? 'fa-circle-check text-primary' : 'fa-clock-rotate-left text-muted'}"></i>
                    </div>
                </div>
            `).join('');
            el.querySelectorAll('.planejamento-item').forEach((item) => {
                item.addEventListener('click', () => selecionarPlano(Number(item.dataset.id)));
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
                    </div>
                </div>
            `).join('');

            el.querySelectorAll('.aula-item button').forEach((btn) => {
                btn.addEventListener('click', async (e) => {
                    const item = e.target.closest('.aula-item');
                    const id = Number(item.dataset.id);
                    const action = btn.dataset.action;
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
            renderAulas();
        }

        function atualizarSelectsTurmaVinculo() {
            const selectVinculo = document.getElementById('selectTurmaVinculo');
            const selectDesvinculo = document.getElementById('selectTurmaDesvinculo');
            const idPlanoAtual = Number(planoSelecionado?.IdPlanoCurso || 0);

            const turmasDisponiveis = turmasCursoComPlano.filter((t) => !Number(t.IdPlanoCurso || 0));
            const turmasVinculadasAoPlano = turmasCursoComPlano.filter((t) => Number(t.IdPlanoCurso || 0) === idPlanoAtual);

            selectVinculo.innerHTML = turmasDisponiveis.map((t) => {
                const ativa = Number(t.Habilitado || 0) === 1;
                const sufixo = ativa ? '' : ' (Concluída/Inativa)';
                return `<option value="${Number(t.IdTurma)}">${esc(t.NomeTurma)}${sufixo}</option>`;
            }).join('');

            selectDesvinculo.innerHTML = turmasVinculadasAoPlano.map((t) => {
                const ativa = Number(t.Habilitado || 0) === 1;
                const sufixo = ativa ? '' : ' (Concluída/Inativa)';
                return `<option value="${Number(t.IdTurma)}">${esc(t.NomeTurma)}${sufixo}</option>`;
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
                placeholder: idPlanoAtual > 0 ? 'Selecione turma(s) para desvincular deste plano' : 'Selecione um plano',
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
            try {
                const results = await Promise.allSettled(idsTurma.map((idTurma) => apiFetch(`/turmas/${idTurma}/plano-curso/vincular`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ IdPlanoCurso: Number(planoSelecionado.IdPlanoCurso) })
                })));
                const ok = results.filter((r) => r.status === 'fulfilled').length;
                const falhas = results.length - ok;
                await carregarPlanos();
                await carregarTurmasCurso();
                if (turmaVinculoTomSelect) turmaVinculoTomSelect.clear(true);
                if (falhas === 0) {
                    toastOk(`Plano vinculado com sucesso em ${ok} turma(s).`);
                    showStatus(`Plano vinculado com sucesso em ${ok} turma(s).`, 'success');
                } else {
                    toastWarn(`Vínculo concluído parcialmente: ${ok} sucesso(s), ${falhas} falha(s).`);
                    showStatus(`Vínculo parcial: ${ok} sucesso(s), ${falhas} falha(s).`, 'warning');
                }
            } catch (err) {
                toastErr(err.message);
                showStatus(err.message, 'danger');
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

            try {
                const results = await Promise.allSettled(idsTurma.map((idTurma) => apiFetch(`/turmas/${idTurma}/plano-curso/desvincular`, {
                    method: 'POST'
                })));
                const sucesso = results.filter((r) => r.status === 'fulfilled').length;
                const falhas = results.length - sucesso;
                await carregarTurmasCurso();
                await carregarPlanos();
                if (turmaDesvinculoTomSelect) turmaDesvinculoTomSelect.clear(true);
                if (falhas === 0) {
                    toastOk(`Desvínculo concluído em ${sucesso} turma(s).`);
                    showStatus(`Desvínculo concluído em ${sucesso} turma(s).`, 'success');
                } else {
                    toastWarn(`Desvínculo parcial: ${sucesso} sucesso(s), ${falhas} falha(s).`);
                    showStatus(`Desvínculo parcial: ${sucesso} sucesso(s), ${falhas} falha(s).`, 'warning');
                }
            } catch (err) {
                toastErr(err.message);
                showStatus(err.message, 'danger');
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

