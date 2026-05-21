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

$mensagemSucesso = isset($_GET['msg']) ? trim((string) $_GET['msg']) : '';
$mensagemErro = isset($_GET['erro']) ? trim((string) $_GET['erro']) : '';
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
        .filtro-situacao-turma { background-color: #f1f3f5; color: #000; border: 1px solid #d0d7de; }
        .filtro-situacao-turma.active.filtro-ativo { background-color: #ffc107; color: #fff; border-color: #ffc107; }
        .filtro-situacao-turma.active.filtro-concluido { background-color: #198754; color: #fff; border-color: #198754; }
        .filtro-situacao-turma.active.filtro-todos { background-color: #0d6efd; color: #fff; border-color: #0d6efd; }
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
                    <h1 class="h3 mb-3">Turmas</h1>
                    <?php if ($mensagemSucesso !== ''): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars(urldecode($mensagemSucesso), ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($mensagemErro !== ''): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars(urldecode($mensagemErro), ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                    <?php endif; ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/turmas/cadastrar/" id="formulario" method="post">
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label for="NomeTurma_0" class="form-label">Nome da turma</label>
                                            <div id="nomesTurmaContainer">
                                                <div class="input-group mb-2 nome-turma-item">
                                                    <input type="text" class="form-control" id="NomeTurma_0" name="NomeTurma[]" autocomplete="off">
                                                    <button type="button" class="btn btn-outline-danger remover-nome-turma" disabled>Remover</button>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="adicionarNomeTurma">Adicionar nome de turma</button>
                                        </div>
                                        <div class="col-6">
                                            <label for="IdCursoCadastro" class="form-label">Cursos</label>
                                            <select class="form-select js-curso-select" name="IdCurso" id="IdCursoCadastro"></select>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-4">
                                            <label for="Municipio" class="form-label">Município</label>
                                            <input type="text" class="form-control" id="Municipio" name="Municipio" autocomplete="off">
                                        </div>
                                        <div class="col-4">
                                            <label for="Local" class="form-label">Local</label>
                                            <input type="text" class="form-control" id="Local" name="Local" autocomplete="off">
                                        </div>
                                        <div class="col-4">
                                            <label for="max-matriculas" class="form-label">Máximo de pessoas na turma</label>
                                            <input type="text" class="form-control" id="max-matriculas" name="max-matriculas" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="ObsCadastro" class="form-label">Observações</label>
                                        <textarea rows="7" class="form-control" id="ObsCadastro" name="Obs" autocomplete="off"></textarea>
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
                            <h5>Lista de Turmas Cadastradas</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label">Filtro de situação</label>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn filtro-situacao-turma filtro-ativo" data-value="Ativo">Ativo</button>
                                        <button type="button" class="btn filtro-situacao-turma filtro-concluido" data-value="Concluido">Concluído</button>
                                        <button type="button" class="btn filtro-situacao-turma filtro-todos active" data-value="Todos">Todos</button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table id="minhaTabela" class="table table-striped table-hover datatable-custom table-layout-auto">
                                    <thead>
                                        <tr>
                                            <th>Nome da Turma</th>
                                            <th>Situação</th>
                                            <th>Curso</th>
                                            <th>Município</th>
                                            <th>Local</th>
                                            <th>Qtd alunos</th>
                                            <th>Observação</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody id="turmasTbody"></tbody>
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
                            <h1 class="modal-title fs-5">Editar turma</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/turmas/alterar/" method="post">
                                <div class="mb-3">
                                    <label for="edit_nometurma" class="form-label">Nome turma</label>
                                    <input type="text" class="form-control" id="edit_nometurma" name="nometurma" required autocomplete="off">
                                    <input type="hidden" name="id" id="edit_id">
                                </div>
                                <div class="row mb-3">
                                    <div class="col-7">
                                        <label for="edit_curso" class="form-label">Curso</label>
                                        <select class="form-select js-curso-select" name="curso" id="edit_curso"></select>
                                    </div>
                                    <div class="col-5">
                                        <label for="edit_municipio" class="form-label">Município</label>
                                        <input type="text" class="form-control" id="edit_municipio" name="municipio" autocomplete="off">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-7">
                                        <label for="edit_local" class="form-label">Local</label>
                                        <input type="text" class="form-control" id="edit_local" name="local" autocomplete="off">
                                    </div>
                                    <div class="col-5">
                                        <label for="edit_habilitado" class="form-label">Situação</label>
                                        <select class="form-select" id="edit_habilitado" name="habilitado">
                                            <option value="Ativo">Ativo</option>
                                            <option value="Concluido">Concluído</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-5">
                                        <label for="edit_max_matriculas" class="form-label">Máximo de pessoas na turma</label>
                                        <input type="text" class="form-control" id="edit_max_matriculas" name="max-matriculas" autocomplete="off">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_obs" class="form-label">Obs</label>
                                    <textarea rows="7" class="form-control" id="edit_obs" name="obs" autocomplete="off"></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-success">Salvar alterações</button>
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
                            <h1 class="modal-title fs-5">Excluir turma</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/turmas/excluir/" method="post">
                                <div class="mb-3">
                                    <label for="del_nometurma" class="form-label">Nome turma</label>
                                    <input type="text" class="form-control" id="del_nometurma" name="nometurma" readonly autocomplete="off">
                                    <input type="hidden" name="id" id="del_id">
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label for="del_curso" class="form-label">Curso</label>
                                        <input type="text" class="form-control" id="del_curso" readonly autocomplete="off">
                                    </div>
                                    <div class="col-6">
                                        <label for="del_municipio" class="form-label">Município</label>
                                        <input type="text" class="form-control" id="del_municipio" readonly autocomplete="off">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="del_local" class="form-label">Local</label>
                                    <input type="text" class="form-control" id="del_local" readonly autocomplete="off">
                                </div>
                                <div class="mb-3">
                                    <label for="del_obs" class="form-label">Obs</label>
                                    <textarea rows="7" class="form-control" id="del_obs" readonly autocomplete="off"></textarea>
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

    function getQueryState(defaultPageLength = 50) {
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
        document.querySelectorAll('form[action*="/turmas/"]').forEach((form) => {
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

    async function loadCursosSelect() {
        const response = await fetch(`${resolveApiBase()}/cursos`, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Falha ao carregar cursos');

        document.querySelectorAll('.js-curso-select').forEach((select) => {
            const keep = select.value;
            select.innerHTML = '';
            (result.data || []).forEach((curso) => {
                const option = document.createElement('option');
                option.value = curso.IdCurso;
                option.textContent = curso.NomeCurso;
                select.appendChild(option);
            });
            if (keep) select.value = String(keep);
        });
    }

    function renderTurmaRow(item) {
        const ativo = Number(item.Habilitado) === 1;
        const situacao = ativo ? 'Ativo' : 'Concluido';
        const badge = ativo
            ? '<span class="badge bg-warning text-dark">Ativo</span>'
            : '<span class="badge bg-success">Concluído</span>';

        return `<tr>
            <td><a href="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/matriculas/turma?turma=${encodeURIComponent(item.IdTurma || '')}">${escapeHtml(item.NomeTurma || '')}</a></td>
            <td>${badge}</td>
            <td>${escapeHtml(item.NomeCurso || '')}</td>
            <td>${escapeHtml(item.Municipio || '')}</td>
            <td>${escapeHtml(item.Local || '')}</td>
            <td>${escapeHtml(item.QtdAlunos ?? 0)}</td>
            <td>${escapeHtml(item.Obs || '')}</td>
            <td>
                <a href="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/turmas/cronograma?idTurma=${encodeURIComponent(item.IdTurma || '')}"
                   class="btn btn-info btn-sm"
                   title="Abrir cronograma da turma"
                   aria-label="Abrir cronograma da turma">
                   <i class="fa-solid fa-calendar-days"></i>
                </a>
                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editarModal"
                    data-id="${escapeHtml(item.IdTurma)}"
                    data-nometurma="${escapeHtml(item.NomeTurma || '')}"
                    data-curso="${escapeHtml(item.IdCurso || '')}"
                    data-nomecurso="${escapeHtml(item.NomeCurso || '')}"
                    data-municipio="${escapeHtml(item.Municipio || '')}"
                    data-local="${escapeHtml(item.Local || '')}"
                    data-maxmatriculas="${escapeHtml(item.MaxMatriculas || '')}"
                    data-habilitado="${escapeHtml(situacao)}"
                    data-obs="${escapeHtml(item.Obs || '')}">
                    <i class="fa-solid fa-pen-to-square" data-feather="edit-3"></i>
                </button>
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#excluirModal"
                    data-id="${escapeHtml(item.IdTurma)}"
                    data-nometurma="${escapeHtml(item.NomeTurma || '')}"
                    data-curso="${escapeHtml(item.IdCurso || '')}"
                    data-nomecurso="${escapeHtml(item.NomeCurso || '')}"
                    data-municipio="${escapeHtml(item.Municipio || '')}"
                    data-local="${escapeHtml(item.Local || '')}"
                    data-habilitado="${escapeHtml(situacao)}"
                    data-obs="${escapeHtml(item.Obs || '')}">
                    <i class="fa-solid fa-trash" data-feather="trash-2"></i>
                </button>
            </td>
        </tr>`;
    }

    function initDataTable() {
        if ($.fn.DataTable.isDataTable('#minhaTabela')) $('#minhaTabela').DataTable().destroy();
        const queryState = getQueryState(50);
        $.fn.dataTable.ext.type.search['locale-agnostic'] = function(data) { return normalizeText(data); };
        const table = $('#minhaTabela').DataTable({
            pageLength: queryState.porPagina,
            scrollX: true,
            autoWidth: false,
            responsive: true,
            columnDefs: [{ targets: '_all', type: 'locale-agnostic' }],
            order: [[0, 'asc']],
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json" }
        });

        const filterInput = $('#minhaTabela_filter input');
        filterInput.off('.DT');
        filterInput.on('input.DT keyup.DT paste.DT cut.DT', function() {
            table.search(normalizeText(this.value)).draw();
        });

        const statusColumnIndex = 1;

        const activeSituacao = applySituacaoFilter(table, '.filtro-situacao-turma', statusColumnIndex, queryState.situacao);
        updateQueryState({ situacao: activeSituacao, porPagina: table.page.len() });
        syncReturnQueryInputs();

        table.on('length.dt', function(_e, _settings, len) {
            updateQueryState({ situacao: getActiveSituacao('.filtro-situacao-turma'), porPagina: len });
            syncReturnQueryInputs();
        });

        $(document).off('click.turmaFiltro').on('click.turmaFiltro', '.filtro-situacao-turma', function() {
            const value = $(this).data('value');
            const active = applySituacaoFilter(table, '.filtro-situacao-turma', statusColumnIndex, value);
            updateQueryState({ situacao: active, porPagina: table.page.len() });
            syncReturnQueryInputs();
        });
    }

    async function loadTurmas() {
        const response = await fetch(`${resolveApiBase()}/turmas`, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Falha ao carregar turmas');
        document.getElementById('turmasTbody').innerHTML = (result.data || []).map(renderTurmaRow).join('');
        initDataTable();
        if (window.feather) feather.replace();
    }

    $(function() {
        $('#editarModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            $('#edit_id').val(button.data('id'));
            $('#edit_nometurma').val(button.data('nometurma'));
            $('#edit_curso').val(button.data('curso'));
            $('#edit_municipio').val(button.data('municipio'));
            $('#edit_local').val(button.data('local'));
            $('#edit_max_matriculas').val(button.data('maxmatriculas') || '');
            $('#edit_habilitado').val(button.data('habilitado'));
            $('#edit_obs').val(button.data('obs'));
        });

        $('#excluirModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            $('#del_id').val(button.data('id'));
            $('#del_nometurma').val(button.data('nometurma'));
            $('#del_curso').val(button.data('nomecurso'));
            $('#del_municipio').val(button.data('municipio'));
            $('#del_local').val(button.data('local'));
            $('#del_obs').val(button.data('obs'));
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('nomesTurmaContainer');
        const addButton = document.getElementById('adicionarNomeTurma');

        function atualizarEstadoRemover() {
            const linhas = container.querySelectorAll('.nome-turma-item');
            linhas.forEach((linha, index) => {
                const btn = linha.querySelector('.remover-nome-turma');
                btn.disabled = linhas.length === 1 || index === 0;
            });
        }

        function vincularEventosLinha(linha) {
            const btnRemover = linha.querySelector('.remover-nome-turma');
            btnRemover.addEventListener('click', function() {
                const linhas = container.querySelectorAll('.nome-turma-item');
                if (linhas.length <= 1) return;
                linha.remove();
                atualizarEstadoRemover();
            });
        }

        addButton.addEventListener('click', function() {
            const linhaBase = container.querySelector('.nome-turma-item');
            const novaLinha = linhaBase.cloneNode(true);
            const input = novaLinha.querySelector('input[name="NomeTurma[]"]');
            input.value = '';
            input.removeAttribute('id');
            vincularEventosLinha(novaLinha);
            container.appendChild(novaLinha);
            atualizarEstadoRemover();
        });

        vincularEventosLinha(container.querySelector('.nome-turma-item'));
        atualizarEstadoRemover();
    });

    document.getElementById('submitButton').addEventListener('click', function() {
        const inputsNomeTurma = document.querySelectorAll('input[name="NomeTurma[]"]');
        let temNomeValido = false;
        inputsNomeTurma.forEach((input) => { if (input.value.trim() !== '') temNomeValido = true; });
        if (!temNomeValido) {
            alert('Informe ao menos um nome de turma.');
            return;
        }
        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Aguarde...';
        this.disabled = true;
        document.getElementById('formulario').submit();
    });

    document.addEventListener('DOMContentLoaded', async function() {
        try {
            await loadCursosSelect();
            await loadTurmas();
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





