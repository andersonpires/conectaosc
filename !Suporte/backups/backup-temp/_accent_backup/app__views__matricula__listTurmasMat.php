<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_PATH = $runtime['base_path'];
$BASE_URL = $runtime['base_url'];

if (!isset($BASE_PATH) || !isset($BASE_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: " . rtrim((string) ($BASE_URL ?? ''), '/') . "/login/?redirect=$redirect_url");
    exit();
}

if (!isset($_GET['turma'])) {
    die();
}

$IdTurma = (int)$_GET['turma'];
$Habilitado = isset($_GET['ativo']) ? (int)$_GET['ativo'] : 1;

if (isset($_GET['IdMatricula'])) {
    require_once $BASE_PATH . '/api/repositories/MatriculaRepository.php';
    require_once $BASE_PATH . '/api/services/MatriculaService.php';
    $service = new \BackEnd\Services\MatriculaService(new \BackEnd\Repositories\MatriculaRepository());
    $service->ativar((int)$_GET['IdMatricula'], (int)($_SESSION['Cod'] ?? 0));
    header("Location: " . rtrim((string)$BASE_URL, "/") . "/matriculas/turma/?turma={$IdTurma}&ativo={$Habilitado}");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php require_once $BASE_PATH . '/app/template/header.php'; ?>
    <style>
        .img-cover { object-fit: cover; object-position: center; }
        .loader { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(255, 255, 255, 0.8); z-index: 9999; display: block; }
        .loader img { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
        .table { display: none; }
    </style>
</head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script src="//code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="<?php echo $BASE_URL; ?>/assets/js/jquery.dataTables.min.js"></script>
<body>
    <div class="wrapper">
        <?php require_once $BASE_PATH . '/app/template/menu.php'; ?>
        <div class="main">
            <?php require_once $BASE_PATH . '/app/template/topo.php'; ?>
            <main class="content">
                <h4 class="h3 mb-3" id="titulo">Lista de Alunos da turma</h4>
                <div id="loader" class="loader">
                    <img src="https://www.blogson.com.br/wp-content/uploads/2017/10/loading-gif-transparent-10.gif" width="150" alt="Carregando...">
                </div>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <a href="<?php echo rtrim((string)$BASE_URL, '/'); ?>/matriculas/turma/?turma=<?php echo $IdTurma ?>&ativo=<?php echo ($Habilitado == 0) ? 1 : 0; ?>" class="btn btn-primary mb-3">
                                <?php echo ($Habilitado == 0) ? 'Mostrar somente Matr?f?????T?f??s?,?culas ativas' : 'Mostrar Matr?f?????T?f??s?,?culas ativas e inativas'; ?>
                            </a>
                            <?php if ($_SESSION['Tipo'] != "Visitante") { ?>
                                <a href="<?php echo rtrim((string)$BASE_URL, '/'); ?>/matriculas/contratos/turma/?turma=<?php echo $IdTurma ?>" class="btn btn-info mb-3" target="_blank" title="Gerar contratos dos alunos ativos">
                                    <i class="fa-solid fa-file-signature"></i> Gerar contratos (ativos)
                                </a>
                            <?php } ?>
                            <div class="table-responsive">
                                <table id="minhaTabela" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>N?f???,?s?f??s?,?</th>
                                            <th>Foto</th>
                                            <th>Aluno</th>
                                            <th class="text-center">Situa?f?????T?f??s?,??f?????T?f??s?,?o</th>
                                            <th>Nascimento</th>
                                            <th>WhatsApp</th>
                                            <th>Telefone</th>
                                            <th>Data Matr?f?????T?f??s?,?cula</th>
                                            <th>A?f?????T?f??s?,??f?????T?f??s?,?es</th>
                                        </tr>
                                    </thead>
                                    <tbody id="matriculasTbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="editarModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <img id="edit_fotoaluno" src="" class="rounded-circle img-cover" width="40" height="40" alt="">
                                <h1 class="modal-title fs-5">Editar matr?f?????T?f??s?,?cula</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="<?php echo rtrim((string)$BASE_URL, '/'); ?>/matriculas/alterar/?turma=<?php echo $IdTurma; ?>&ativo=<?php echo $Habilitado; ?>" method="post">
                                    <div class="mb-3">
                                        <label class="form-label">Nome aluno</label>
                                        <input type="text" class="form-control" id="edit_nomealuno" name="nomealuno" readonly>
                                        <input type="hidden" name="idaluno" id="edit_idaluno">
                                        <input type="hidden" name="idmatricula" id="edit_idmatricula">
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-5">
                                            <label class="form-label">Curso</label>
                                            <select class="form-select" name="curso" id="edit_curso"></select>
                                        </div>
                                        <div class="col-7">
                                            <label class="form-label">Turma</label>
                                            <select class="form-select" name="idturma" id="edit_idturma"></select>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-7">
                                            <label class="form-label">Municipio</label>
                                            <input type="text" class="form-control" id="edit_municipio" name="municipio" readonly>
                                        </div>
                                        <div class="col-5">
                                            <label class="form-label">Data Matr?f?????T?f??s?,?cula</label>
                                            <input type="text" class="form-control" id="edit_datamatricula" name="datamatricula">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-success">Salvar altera?f?????T?f??s?,??f?????T?f??s?,?es</button>
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
                                <img id="del_fotoaluno" src="" class="rounded-circle img-cover" width="40" height="40" alt="">
                                <h1 class="modal-title fs-5">Excluir matr?f?????T?f??s?,?cula</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="<?php echo rtrim((string)$BASE_URL, '/'); ?>/matriculas/excluir/?turma=<?php echo $IdTurma; ?>" method="post">
                                    <div class="mb-3">
                                        <label class="form-label">Nome aluno</label>
                                        <input type="text" class="form-control" id="del_nomealuno" name="nomealuno" readonly>
                                        <input type="hidden" name="idmatricula" id="del_idmatricula">
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-7">
                                            <label class="form-label">Curso</label>
                                            <input type="text" class="form-control" id="del_nomecurso" name="nomecurso" readonly>
                                            <input type="hidden" name="curso" id="del_curso">
                                        </div>
                                        <div class="col-5">
                                            <label class="form-label">Turma</label>
                                            <input type="text" class="form-control" id="del_nometurma" name="nometurma" readonly>
                                            <input type="hidden" name="idturma" id="del_idturma">
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-7">
                                            <label class="form-label">Municipio</label>
                                            <input type="text" class="form-control" id="del_municipio" name="municipio" readonly>
                                        </div>
                                        <div class="col-5">
                                            <label class="form-label">Data Matr?f?????T?f??s?,?cula</label>
                                            <input type="text" class="form-control" id="del_datamatricula" name="datamatricula" readonly>
                                        </div>
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
            </main>

            <footer class="footer">
                <?php require_once $BASE_PATH . '/app/template/footer.php'; ?>
            </footer>
        </div>
    </div>
    <script src="<?php echo $BASE_URL; ?>/assets/js/app.js"></script>
</body>
</html>

<script>
    const ID_TURMA = <?php echo (int)$IdTurma; ?>;
    const SOMENTE_ATIVAS = <?php echo (int)$Habilitado; ?>;
    const TIPO_USUARIO = "<?php echo addslashes($_SESSION['Tipo'] ?? ''); ?>";

    function resolveApiBase() {
        const basePath = "<?php echo rtrim((string)$BASE_URL, '/'); ?>";
        if (/^https?:\/\//i.test(basePath)) {
            return `${basePath}/api/v1`;
        }
        return `${window.location.origin}${basePath}/api/v1`;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function fotoUrl(foto) {
        return `<?php echo $BASE_URL; ?>/assets/img/fotos/${foto || 'padrao.jpg'}`;
    }

    function renderAcoes(item) {
        const ativo = Number(item.Habilitado) === 1;
        if (TIPO_USUARIO === 'Visitante' && ativo) {
            return '<button type="button" class="btn btn-primary btn-disabled" disabled>Desabilitado</button>';
        }

        if (TIPO_USUARIO !== 'Visitante' && ativo) {
            return `
                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editarModal"
                    data-fotoaluno="${escapeHtml(fotoUrl(item.Foto))}"
                    data-nomealuno="${escapeHtml(item.NomeAluno)}"
                    data-idaluno="${escapeHtml(item.IdUsuario)}"
                    data-idmatricula="${escapeHtml(item.IdMatricula)}"
                    data-curso="${escapeHtml(item.IdCurso)}"
                    data-idturma="${escapeHtml(item.IdTurma)}"
                    data-municipio="${escapeHtml(item.Municipio || '')}"
                    data-datamatricula="${escapeHtml(item.vData || '')}">
                    <i class="fa-solid fa-pen-to-square" data-feather="edit-3"></i>
                </button>
                <a class="btn btn-info btn-sm" href="<?php echo rtrim((string)$BASE_URL, '/'); ?>/matriculas/contrato/?turma=${escapeHtml(item.IdTurma)}&aluno=${escapeHtml(item.IdUsuario)}" target="_blank" title="Gerar contrato">
                    <i class="fa-solid fa-file-signature"></i>
                </a>
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#excluirModal"
                    data-fotoaluno="${escapeHtml(fotoUrl(item.Foto))}"
                    data-nomealuno="${escapeHtml(item.NomeAluno)}"
                    data-idmatricula="${escapeHtml(item.IdMatricula)}"
                    data-curso="${escapeHtml(item.IdCurso)}"
                    data-nomecurso="${escapeHtml(item.NomeCurso)}"
                    data-idturma="${escapeHtml(item.IdTurma)}"
                    data-nometurma="${escapeHtml(item.NomeTurma)}"
                    data-municipio="${escapeHtml(item.Municipio || '')}"
                    data-datamatricula="${escapeHtml(item.vData || '')}">
                    <i class="fa-solid fa-trash" data-feather="trash-2"></i>
                </button>`;
        }

        if (TIPO_USUARIO !== 'Educador' && !ativo) {
            return `<a href="<?php echo rtrim((string)$BASE_URL, '/'); ?>/matriculas/turma/?turma=${escapeHtml(item.IdTurma)}&ativo=${escapeHtml(SOMENTE_ATIVAS)}&IdMatricula=${escapeHtml(item.IdMatricula)}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen-to-square" data-feather="edit-3"></i> ativar</a>`;
        }
        return '';
    }

    async function carregarSelects() {
        const [cursosResp, turmasResp] = await Promise.all([
            fetch(`${resolveApiBase()}/cursos`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' }),
            fetch(`${resolveApiBase()}/turmas`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' }),
        ]);
        const cursosData = await cursosResp.json();
        const turmasData = await turmasResp.json();
        if (!cursosResp.ok || !cursosData.success) throw new Error('Erro ao carregar cursos');
        if (!turmasResp.ok || !turmasData.success) throw new Error('Erro ao carregar turmas');

        const editCurso = document.getElementById('edit_curso');
        editCurso.innerHTML = '';
        (cursosData.data || []).forEach((curso) => {
            const option = document.createElement('option');
            option.value = curso.IdCurso;
            option.textContent = curso.NomeCurso;
            editCurso.appendChild(option);
        });

        const editTurma = document.getElementById('edit_idturma');
        editTurma.innerHTML = '';
        (turmasData.data || []).forEach((turma) => {
            const option = document.createElement('option');
            option.value = turma.IdTurma;
            option.textContent = turma.NomeTurma || turma.NomeCurso || turma.IdTurma;
            editTurma.appendChild(option);
        });

        const turmaAtual = (turmasData.data || []).find((t) => Number(t.IdTurma) === Number(ID_TURMA));
        if (turmaAtual) {
            document.getElementById('titulo').innerHTML += ` <span style="color: blue;">${escapeHtml(turmaAtual.NomeTurma || '')}</span>`;
        }
    }

    async function carregarMatriculas() {
        const resp = await fetch(`${resolveApiBase()}/matriculas/por-turma?turma=${encodeURIComponent(ID_TURMA)}&ativo=${encodeURIComponent(SOMENTE_ATIVAS)}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        });
        const data = await resp.json();
        if (!resp.ok || !data.success) throw new Error(data.message || 'Erro ao carregar Matr?f?????T?f??s?,?culas');

        const tbody = document.getElementById('matriculasTbody');
        tbody.innerHTML = (data.data || []).map((item, idx) => {
            const ativo = Number(item.Habilitado) === 1;
            const status = ativo
                ? '<span class="badge bg-success text-center d-flex justify-content-center">Ativo</span>'
                : '<span class="badge bg-secondary text-center d-flex justify-content-center">Inativo</span>';
            return `<tr>
                <td>${idx + 1}</td>
                <td><img src="${escapeHtml(fotoUrl(item.Foto))}" class="rounded-circle img-cover" width="40" height="40" alt=""></td>
                <td>${escapeHtml(item.NomeAluno)} (Mat. ${escapeHtml(item.IdMatricula)})</td>
                <td>${status}</td>
                <td>${escapeHtml(item.Nascimento || '')}</td>
                <td>${escapeHtml(item.WhatsApp || '')}</td>
                <td>${escapeHtml(item.Telefone || '')}</td>
                <td>${escapeHtml(item.vData || '')}</td>
                <td>${renderAcoes(item)}</td>
            </tr>`;
        }).join('');

        if ($.fn.DataTable.isDataTable('#minhaTabela')) {
            $('#minhaTabela').DataTable().destroy();
        }
        $('#minhaTabela').DataTable({
            order: [],
            pageLength: 50,
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json" }
        });

        if (window.feather) feather.replace();
    }

    $(function() {
        $('#editarModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            $('#edit_fotoaluno').attr('src', button.data('fotoaluno'));
            $('#edit_nomealuno').val(button.data('nomealuno'));
            $('#edit_idaluno').val(button.data('idaluno'));
            $('#edit_idmatricula').val(button.data('idmatricula'));
            $('#edit_curso').val(button.data('curso'));
            $('#edit_idturma').val(button.data('idturma'));
            $('#edit_municipio').val(button.data('municipio'));
            $('#edit_datamatricula').val(button.data('datamatricula'));
        });

        $('#excluirModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            $('#del_fotoaluno').attr('src', button.data('fotoaluno'));
            $('#del_nomealuno').val(button.data('nomealuno'));
            $('#del_idmatricula').val(button.data('idmatricula'));
            $('#del_curso').val(button.data('curso'));
            $('#del_nomecurso').val(button.data('nomecurso'));
            $('#del_idturma').val(button.data('idturma'));
            $('#del_nometurma').val(button.data('nometurma'));
            $('#del_municipio').val(button.data('municipio'));
            $('#del_datamatricula').val(button.data('datamatricula'));
        });
    });

    document.addEventListener('DOMContentLoaded', async function() {
        try {
            await carregarSelects();
            await carregarMatriculas();
        } catch (error) {
            console.error(error);
        } finally {
            const loader = document.getElementById('loader');
            const tabela = document.getElementById('minhaTabela');
            if (loader) loader.style.display = 'none';
            if (tabela) tabela.style.display = 'table';
        }
    });
</script>








