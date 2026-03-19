<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: " . rtrim((string) ($BASE_para_URL ?? ''), '/') . "/login/?redirect=$redirect_url");
    exit();
}

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <style>
        .loader img { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
        .table { display: none; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .table-layout-auto { table-layout: auto; width: 100%; white-space: nowrap; }
        .filtro-situacao-curso { background-color: #f1f3f5; color: #000; border: 1px solid #d0d7de; }
        .filtro-situacao-curso.active.filtro-ativo { background-color: #ffc107; color: #fff; border-color: #ffc107; }
        .filtro-situacao-curso.active.filtro-concluido { background-color: #198754; color: #fff; border-color: #198754; }
        .filtro-situacao-curso.active.filtro-todos { background-color: #0d6efd; color: #fff; border-color: #0d6efd; }
    </style>
</head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script src="//code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="<?php echo $BASE_para_URL; ?>/assets/js/jquery.dataTables.min.js"></script>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>
        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>

            <main class="content">
                <div id="loader" class="loader">
                    <img src="https://www.blogson.com.br/wp-content/uploads/2017/10/loading-gif-transparent-10.gif" width="150" alt="Carregando...">
                </div>
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3">Cursos</h1>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/cursos/cadastrar/" id="formulario" method="post">
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label for="NomeCurso" class="form-label">Nome do curso</label>
                                            <input type="text" class="form-control" id="NomeCurso" name="NomeCurso" autocomplete="off">
                                        </div>
                                        <div class="col-6">
                                            <label for="CargaHoraria" class="form-label">Carga horaria</label>
                                            <input type="text" class="form-control" id="CargaHoraria" name="CargaHoraria" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label for="Duracao" class="form-label">Duração</label>
                                            <input type="text" class="form-control" id="Duracao" name="Duracao" autocomplete="off">
                                        </div>
                                        <div class="col-6">
                                            <label for="Tipo" class="form-label">Tipo</label>
                                            <select class="form-select" name="Tipo">
                                                <option value="Horas">Horas</option>
                                                <option value="Dias">Dias</option>
                                                <option value="Semanas">Semanas</option>
                                                <option value="Meses" selected>Meses</option>
                                                <option value="Outro">Outro</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-4">
                                            <label for="Habilitado" class="form-label">Situação</label>
                                            <select class="form-select" id="Habilitado" name="Habilitado">
                                                <option value="Ativo" selected>Ativo</option>
                                                <option value="Concluido">Concluído</option>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <label for="idade-min" class="form-label">Idade minima</label>
                                            <input type="text" class="form-control" id="idade-min" name="idade-min" autocomplete="off">
                                        </div>
                                        <div class="col-4">
                                            <label for="idade-max" class="form-label">Idade maxima</label>
                                            <input type="text" class="form-control" id="idade-max" name="idade-max" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-4">
                                            <label for="Termo" class="form-label">Termo de parceria</label>
                                            <input type="text" class="form-control" id="Termo" name="Termo" autocomplete="off">
                                        </div>
                                        <div class="col-4">
                                            <label for="ProjetoCadastro" class="form-label">Projeto</label>
                                            <select class="form-select js-projeto-select" id="ProjetoCadastro" name="Projeto">
                                                <option value="">Selecione um projeto</option>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <label for="Programa" class="form-label">Programa</label>
                                            <select class="form-select" id="Programa" name="Programa">
                                                <option value="">Selecione um programa</option>
                                                <option value="Midiacom">Midiacom</option>
                                                <option value="Bem-estar">Bem-estar</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="infAdic" class="form-label">Informações adicionais</label>
                                        <textarea rows="7" class="form-control" id="infAdic" name="Informacoes" autocomplete="off"></textarea>
                                    </div>
                                    <div style="text-align: right;">
                                        <button type="button" id="submitButton" class="btn btn-primary">Cadastrar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <main class="content">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Lista de Cursos Cadastrados</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label">Filtro de situação</label>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn filtro-situacao-curso filtro-ativo" data-value="Ativo">Ativo</button>
                                        <button type="button" class="btn filtro-situacao-curso filtro-concluido" data-value="Concluido">Concluído</button>
                                        <button type="button" class="btn filtro-situacao-curso filtro-todos active" data-value="Todos">Todos</button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table id="minhaTabela" class="table table-striped table-hover datatable-custom table-layout-auto">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th class="text-center">Termo</th>
                                            <th class="text-center">Projeto</th>
                                            <th class="text-center">Programa</th>
                                            <th class="text-center">Situação</th>
                                            <th class="text-center">Qtd turmas</th>
                                            <th class="text-center">Duração</th>
                                            <th class="text-center">Tipo</th>
                                            <th class="text-center">Carga horária</th>
                                            <th class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cursosTbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <div class="modal fade" id="editarModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5">Editar cursos</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/cursos/alterar/" method="post">
                                <div class="row mb-3">
                                    <div class="col-8">
                                        <label for="edit_nomecurso" class="form-label">Nome</label>
                                        <input type="text" class="form-control" id="edit_nomecurso" name="nomecurso" required autocomplete="off">
                                        <input type="hidden" name="id" id="edit_id">
                                    </div>
                                    <div class="col-4">
                                        <label for="edit_Termo" class="form-label">Termo</label>
                                        <input type="text" class="form-control" id="edit_Termo" name="Termo" autocomplete="off">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-3">
                                        <label for="edit_duracao" class="form-label">Duração</label>
                                        <input type="text" class="form-control" id="edit_duracao" name="duracao" autocomplete="off">
                                    </div>
                                    <div class="col-5">
                                        <label for="edit_tipo" class="form-label">Tipo</label>
                                        <select class="form-select" id="edit_tipo" name="tipo">
                                            <option value="Horas">Horas</option>
                                            <option value="Dias">Dias</option>
                                            <option value="Semanas">Semanas</option>
                                            <option value="Meses">Meses</option>
                                            <option value="Outro">Outro</option>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <label for="edit_cargah" class="form-label">Carga horaria</label>
                                        <input type="text" class="form-control" id="edit_cargah" name="cargah" autocomplete="off">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label for="edit_habilitado" class="form-label">Situação</label>
                                        <select class="form-select" id="edit_habilitado" name="habilitado">
                                            <option value="Ativo">Ativo</option>
                                            <option value="Concluido">Concluído</option>
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <label for="edit_idade_min" class="form-label">Idade minima</label>
                                        <input type="text" class="form-control" id="edit_idade_min" name="idade-min" autocomplete="off">
                                    </div>
                                    <div class="col-3">
                                        <label for="edit_idade_max" class="form-label">Idade maxima</label>
                                        <input type="text" class="form-control" id="edit_idade_max" name="idade-max" autocomplete="off">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label for="edit_Projeto" class="form-label">Projeto</label>
                                        <select class="form-select js-projeto-select" id="edit_Projeto" name="Projeto">
                                            <option value="">-</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label for="edit_Programa" class="form-label">Programa</label>
                                        <input type="text" class="form-control" id="edit_Programa" name="Programa" autocomplete="off">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_informacoes" class="form-label">Informações adicionais</label>
                                    <textarea rows="7" class="form-control" id="edit_informacoes" name="informacoes" autocomplete="off"></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-success">Salvar alteracoes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="excluirModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5">Excluir cursos</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/cursos/excluir/" method="post">
                                <div class="row mb-3">
                                    <div class="col-8">
                                        <label for="del_nomecurso" class="form-label">Nome</label>
                                        <input type="text" class="form-control" id="del_nomecurso" name="nomecurso" readonly autocomplete="off">
                                        <input type="hidden" name="id" id="del_id">
                                    </div>
                                    <div class="col-4">
                                        <label for="del_Termo" class="form-label">Termo</label>
                                        <input type="text" class="form-control" id="del_Termo" name="Termo" readonly autocomplete="off">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-3">
                                        <label for="del_duracao" class="form-label">Duração</label>
                                        <input type="text" class="form-control" id="del_duracao" name="duracao" readonly autocomplete="off">
                                    </div>
                                    <div class="col-5">
                                        <label for="del_tipo" class="form-label">Tipo</label>
                                        <input type="text" class="form-control" id="del_tipo" name="tipo" readonly autocomplete="off">
                                    </div>
                                    <div class="col-4">
                                        <label for="del_cargah" class="form-label">Carga horaria</label>
                                        <input type="text" class="form-control" id="del_cargah" name="cargah" readonly autocomplete="off">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label for="del_Projeto" class="form-label">Projeto</label>
                                        <input type="text" class="form-control" id="del_Projeto" name="Projeto" readonly autocomplete="off">
                                    </div>
                                    <div class="col-6">
                                        <label for="del_Programa" class="form-label">Programa</label>
                                        <input type="text" class="form-control" id="del_Programa" name="Programa" readonly autocomplete="off">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="del_informacoes" class="form-label">Informações adicionais</label>
                                    <textarea rows="7" class="form-control" id="del_informacoes" name="informacoes" readonly autocomplete="off"></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-danger">Excluir registro</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>
</body>
</html>

<script>
    function resolveApiBase() {
        const basePath = "<?php echo rtrim((string)$BASE_para_URL, '/'); ?>";
        if (/^https?:\/\//i.test(basePath)) {
            return `${basePath}/api/v1`;
        }
        return `${window.location.origin}${basePath}/api/v1`;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalizeText(value) {
        return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }

    function getQueryState(defaultPageLength = 10) {
        const params = new URLSearchParams(window.location.search);
        const situacao = params.get('situacao');
        const porPaginaRaw = params.get('porPagina');
        const porPagina = Number.parseInt(String(porPaginaRaw ?? ''), 10);

        return {
            situacao: ['Ativo', 'Concluido', 'Todos'].includes(String(situacao)) ? String(situacao) : 'Todos',
            porPagina: Number.isInteger(porPagina) && (porPagina > 0 || porPagina === -1) ? porPagina : defaultPageLength,
        };
    }

    function updateQueryState(nextState) {
        const url = new URL(window.location.href);
        const params = url.searchParams;

        if (nextState?.situacao) params.set('situacao', String(nextState.situacao));
        if (Number.isInteger(nextState?.porPagina)) params.set('porPagina', String(nextState.porPagina));

        const nextUrl = `${url.pathname}${params.toString() ? `?${params.toString()}` : ''}${url.hash}`;
        window.history.replaceState(null, '', nextUrl);
    }

    function buildReturnQueryValue() {
        const params = new URLSearchParams(window.location.search);
        const query = new URLSearchParams();
        const situacao = String(params.get('situacao') || '');
        const porPagina = String(params.get('porPagina') || '');

        if (['Ativo', 'Concluido', 'Todos'].includes(situacao)) query.set('situacao', situacao);
        if (/^-?\d+$/.test(porPagina)) {
            const valor = Number.parseInt(porPagina, 10);
            if (valor > 0 || valor === -1) query.set('porPagina', String(valor));
        }

        return query.toString();
    }

    function syncReturnQueryInputs() {
        const returnQuery = buildReturnQueryValue();
        document.querySelectorAll('form[action*="/cursos/"]').forEach((form) => {
            let input = form.querySelector('input[name="_return_query"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_return_query';
                form.appendChild(input);
            }
            input.value = returnQuery;
        });
    }

    function getActiveSituacao(selector) {
        const value = String($(`${selector}.active`).data('value') || 'Todos');
        return ['Ativo', 'Concluido', 'Todos'].includes(value) ? value : 'Todos';
    }

    function applySituacaoFilter(table, selector, statusColumnIndex, situacao) {
        const value = ['Ativo', 'Concluido', 'Todos'].includes(String(situacao)) ? String(situacao) : 'Todos';
        $(selector).removeClass('active');
        $(`${selector}[data-value="${value}"]`).addClass('active');
        if (value === 'Todos') {
            table.column(statusColumnIndex).search('').draw();
            return value;
        }
        table.column(statusColumnIndex).search(normalizeText(value), false, false).draw();
        return value;
    }

    async function loadProjetos() {
        const response = await fetch(`${resolveApiBase()}/projetos`, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Falha ao carregar projetos');

        document.querySelectorAll('.js-projeto-select').forEach((select) => {
            const keep = select.value;
            const first = select.id === 'ProjetoCadastro' ? 'Selecione um projeto' : '-';
            select.innerHTML = `<option value="">${first}</option>`;
            (result.data || []).forEach((item) => {
                const option = document.createElement('option');
                option.value = item.IdProjeto;
                option.textContent = item.nomeProjeto;
                select.appendChild(option);
            });
            if (keep) select.value = String(keep);
        });
    }

    function renderCursoRow(item) {
        const ativo = Number(item.Habilitado) === 1;
        const situacao = ativo ? 'Ativo' : 'Concluido';
        const badge = ativo
            ? '<span class="badge bg-warning text-dark">Ativo</span>'
            : '<span class="badge bg-success">Concluído</span>';
        const duracao = String(item.Duracao ?? '').replace('.', ',');
        const carga = String(item.CargaHoraria ?? '').replace('.', ',');

        return `<tr>
            <td>${escapeHtml(item.NomeCurso || '')}</td>
            <td>${escapeHtml(item.Termo || '')}</td>
            <td class="text-center">${escapeHtml(item.nomeProjeto || '-')}</td>
            <td class="text-center">${escapeHtml(item.Programa || '-')}</td>
            <td class="text-center">${badge}</td>
            <td class="text-center">${escapeHtml(item.QtdTurmas ?? 0)}</td>
            <td class="text-center">${escapeHtml(duracao)}</td>
            <td class="text-center">${escapeHtml(item.Tipo || '')}</td>
            <td class="text-center">${escapeHtml(carga)}</td>
            <td class="text-center">
                <div class="d-flex justify-content-center gap-2">
                    <a
                        class="btn btn-info btn-sm"
                        href="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/cursos/planejamento?id=${encodeURIComponent(item.IdCurso || '')}"
                        title="Abrir planejamento do curso"
                        aria-label="Abrir planejamento do curso">
                        <i class="fa-solid fa-calendar-days"></i>
                    </a>
                    <button type="button" class="btn btn-warning btn-sm"
                        data-bs-toggle="modal" data-bs-target="#editarModal"
                        data-id="${escapeHtml(item.IdCurso)}"
                        data-nomecurso="${escapeHtml(item.NomeCurso || '')}"
                        data-termo="${escapeHtml(item.Termo || '')}"
                        data-projeto="${escapeHtml(item.nomeProjeto || '')}"
                        data-projetoid="${escapeHtml(item.IdProjeto || '')}"
                        data-programa="${escapeHtml(item.Programa || '-')}"
                        data-habilitado="${escapeHtml(situacao)}"
                        data-idademin="${escapeHtml(item.IdadeMin || '')}"
                        data-idademax="${escapeHtml(item.IdadeMax || '')}"
                        data-cargah="${escapeHtml(carga)}"
                        data-tipo="${escapeHtml(item.Tipo || '')}"
                        data-duracao="${escapeHtml(duracao)}"
                        data-informacoes="${escapeHtml(item.Informacoes || '')}">
                        <i class="fa-solid fa-pen-to-square" data-feather="edit-3"></i>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm"
                        data-bs-toggle="modal" data-bs-target="#excluirModal"
                        data-id="${escapeHtml(item.IdCurso)}"
                        data-nomecurso="${escapeHtml(item.NomeCurso || '')}"
                        data-termo="${escapeHtml(item.Termo || '')}"
                        data-projeto="${escapeHtml(item.nomeProjeto || '')}"
                        data-programa="${escapeHtml(item.Programa || '-')}"
                        data-habilitado="${escapeHtml(situacao)}"
                        data-idademin="${escapeHtml(item.IdadeMin || '')}"
                        data-idademax="${escapeHtml(item.IdadeMax || '')}"
                        data-cargah="${escapeHtml(carga)}"
                        data-tipo="${escapeHtml(item.Tipo || '')}"
                        data-duracao="${escapeHtml(duracao)}"
                        data-informacoes="${escapeHtml(item.Informacoes || '')}">
                        <i class="fa-solid fa-trash" data-feather="trash-2"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }

    function initDataTable() {
        if ($.fn.DataTable.isDataTable('#minhaTabela')) {
            $('#minhaTabela').DataTable().destroy();
        }
        const queryState = getQueryState(10);
        $.fn.dataTable.ext.type.search['locale-agnostic'] = function(data) {
            return normalizeText(data);
        };
        const table = $('#minhaTabela').DataTable({
            pageLength: queryState.porPagina,
            columnDefs: [{ targets: '_all', type: 'locale-agnostic' }],
            order: [[0, 'asc']],
            responsive: true,
            autoWidth: false,
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json" }
        });

        const filterInput = $('#minhaTabela_filter input');
        filterInput.off('.DT');
        filterInput.on('input.DT keyup.DT paste.DT cut.DT', function() {
            table.search(normalizeText(this.value)).draw();
        });

        const statusColumnIndex = 4;
        const activeSituacao = applySituacaoFilter(table, '.filtro-situacao-curso', statusColumnIndex, queryState.situacao);
        updateQueryState({ situacao: activeSituacao, porPagina: table.page.len() });
        syncReturnQueryInputs();

        table.on('length.dt', function(_e, _settings, len) {
            updateQueryState({ situacao: getActiveSituacao('.filtro-situacao-curso'), porPagina: len });
            syncReturnQueryInputs();
        });

        $(document).off('click.cursoFiltro').on('click.cursoFiltro', '.filtro-situacao-curso', function() {
            const value = $(this).data('value');
            const active = applySituacaoFilter(table, '.filtro-situacao-curso', statusColumnIndex, value);
            updateQueryState({ situacao: active, porPagina: table.page.len() });
            syncReturnQueryInputs();
        });
    }

    async function loadCursos() {
        const response = await fetch(`${resolveApiBase()}/cursos`, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Falha ao carregar cursos');
        document.getElementById('cursosTbody').innerHTML = (result.data || []).map(renderCursoRow).join('');
        initDataTable();
        if (window.feather) feather.replace();
    }

    $(function() {
        $('#editarModal').on('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            this.querySelector('#edit_id').value = button.getAttribute('data-id') || '';
            this.querySelector('#edit_nomecurso').value = button.getAttribute('data-nomecurso') || '';
            this.querySelector('#edit_Termo').value = button.getAttribute('data-termo') || '';
            this.querySelector('#edit_Projeto').value = button.getAttribute('data-projetoid') || '';
            this.querySelector('#edit_Programa').value = button.getAttribute('data-programa') || '';
            this.querySelector('#edit_habilitado').value = button.getAttribute('data-habilitado') || 'Ativo';
            this.querySelector('#edit_idade_min').value = button.getAttribute('data-idademin') || '';
            this.querySelector('#edit_idade_max').value = button.getAttribute('data-idademax') || '';
            this.querySelector('#edit_cargah').value = button.getAttribute('data-cargah') || '';
            this.querySelector('#edit_tipo').value = button.getAttribute('data-tipo') || '';
            this.querySelector('#edit_duracao').value = button.getAttribute('data-duracao') || '';
            this.querySelector('#edit_informacoes').value = button.getAttribute('data-informacoes') || '';
        });

        $('#excluirModal').on('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            this.querySelector('#del_id').value = button.getAttribute('data-id') || '';
            this.querySelector('#del_nomecurso').value = button.getAttribute('data-nomecurso') || '';
            this.querySelector('#del_Termo').value = button.getAttribute('data-termo') || '';
            this.querySelector('#del_Projeto').value = button.getAttribute('data-projeto') || '';
            this.querySelector('#del_Programa').value = button.getAttribute('data-programa') || '';
            this.querySelector('#del_tipo').value = button.getAttribute('data-tipo') || '';
            this.querySelector('#del_cargah').value = button.getAttribute('data-cargah') || '';
            this.querySelector('#del_duracao').value = button.getAttribute('data-duracao') || '';
            this.querySelector('#del_informacoes').value = button.getAttribute('data-informacoes') || '';
        });
    });

    document.getElementById('submitButton').addEventListener('click', function() {
        const submitButton = document.getElementById('submitButton');
        const form = document.getElementById('formulario');
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Aguarde...';
        submitButton.disabled = true;
        form.submit();
    });

    document.addEventListener('DOMContentLoaded', async function() {
        try {
            await loadProjetos();
            await loadCursos();
            syncReturnQueryInputs();
        } catch (e) {
            console.error(e);
        } finally {
            const loader = document.getElementById('loader');
            const tabela = document.getElementById('minhaTabela');
            if (loader) loader.style.display = 'none';
            if (tabela) tabela.style.display = 'table';
        }
    });
</script>





