<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: " . rtrim((string) ($BASE_para_URL ?? ''), '/') . "/login/?redirect=$redirect_url");
    exit();
}

if (!isset($_GET['turma'])) {
    die();
}

$IdTurma = (int)$_GET['turma'];
$Habilitado = isset($_GET['ativo']) ? (int)$_GET['ativo'] : 1;
$emailUsuarioLogado = trim((string)($_SESSION['Email'] ?? ''));
$IdCursoTurma = 0;

require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
if (isset($pdo) && $pdo instanceof PDO) {
    $stmtCursoTurma = $pdo->prepare('SELECT IdCurso FROM tbTurma WHERE IdTurma = ? LIMIT 1');
    $stmtCursoTurma->execute([$IdTurma]);
    $IdCursoTurma = (int)($stmtCursoTurma->fetchColumn() ?: 0);
}

if (isset($_GET['IdMatricula'])) {
    require_once $BASE_para_PATH . '/api/repositories/MatriculaRepository.php';
    require_once $BASE_para_PATH . '/api/services/MatriculaService.php';
    $service = new \BackEnd\Services\MatriculaService(new \BackEnd\Repositories\MatriculaRepository());
    $service->ativar((int)$_GET['IdMatricula'], (int)($_SESSION['Cod'] ?? 0));
    header("Location: " . rtrim((string)$BASE_para_URL, "/") . "/matriculas/turma/?turma={$IdTurma}&ativo={$Habilitado}");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/overlayNotifica.js"></script>
    <link rel="stylesheet" href="<?php echo $BASE_para_URL; ?>/assets/css/overlayNotifica.css">
    <style>
        .img-cover { object-fit: cover; object-position: center; }
        #minhaTabela { display: none; }
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
                <h4 class="h3 mb-3" id="titulo">Lista de Alunos da turma</h4>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <a href="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/matriculas/turma/?turma=<?php echo $IdTurma ?>&ativo=<?php echo ($Habilitado == 0) ? 1 : 0; ?>" class="btn btn-primary mb-3">
                                <?php echo ($Habilitado == 0) ? 'Mostrar somente matrículas ativas' : 'Mostrar matrículas ativas e inativas'; ?>
                            </a>
                            <?php if ((string)($_SESSION['Tipo'] ?? '') !== "Visitante") { ?>
                                <button type="button" class="btn btn-info mb-3" id="btnGerarContratosCurso"
                                    data-curso="<?php echo (int)$IdCursoTurma; ?>"
                                    data-turma="<?php echo $IdTurma; ?>"
                                    title="Gerar contratos da turma">
                                    <i class="fa-solid fa-file-signature"></i> Gerar contratos (ativos)
                                </button>
                            <?php } ?>
                            <div class="table-responsive">
                                <table id="minhaTabela" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Foto</th>
                                            <th>Aluno</th>
                                            <th class="text-center">Situação</th>
                                            <th>Nascimento</th>
                                            <th>WhatsApp</th>
                                            <th>Telefone</th>
                                            <th>Data Matrícula</th>
                                            <th>Ações</th>
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
                                <h1 class="modal-title fs-5">Editar matrícula</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/matriculas/alterar/?turma=<?php echo $IdTurma; ?>&ativo=<?php echo $Habilitado; ?>" method="post">
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
                                        <label class="form-label">Município</label>
                                            <input type="text" class="form-control" id="edit_municipio" name="municipio" readonly>
                                        </div>
                                        <div class="col-5">
                                        <label class="form-label">Data Matrícula</label>
                                            <input type="text" class="form-control" id="edit_datamatricula" name="datamatricula">
                                        </div>
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

                <div class="modal fade" id="modalSolicitarContratosTurma" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Solicitar geração de contratos da turma</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <div class="modal-body">
                                <p>Todos os contratos gerados serão enviados por e-mail após o processamento, por favor confirme seu e-mail:</p>
                                <div class="mb-2">
                                    <label for="email_contratos_turma" class="form-label">E-mail para recebimento</label>
                                    <input type="email" id="email_contratos_turma" class="form-control" value="<?php echo htmlspecialchars($emailUsuarioLogado, ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                                <div id="feedback_solicitacao_turma" class="small mt-2"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" id="btnConfirmarSolicitacaoTurma" class="btn btn-primary">Confirmar solicitação</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modalHistoricoContratosAluno" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Contratos já emitidos</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-3" id="historicoContratosDescricao">Consultando contratos emitidos...</p>
                                <div id="historicoContratosFeedback" class="small mb-3 text-muted"></div>
                                <div id="historicoContratosLista" class="table-responsive"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" id="btnCriarNovoContrato">Desejo criar um novo</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modalGerarContratosCurso" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Gerar contratos do curso</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-3">Selecione o formato de download:</p>
                                <div class="mb-4">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="contratos-curso-modo" id="contratos-modo-individual" value="individual" checked>
                                        <label class="form-check-label" for="contratos-modo-individual">
                                            Um PDF por aluno (download automático um a um)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="contratos-curso-modo" id="contratos-modo-zip" value="zip">
                                        <label class="form-check-label" for="contratos-modo-zip">
                                            ZIP com todos os contratos (download ao final)
                                        </label>
                                    </div>
                                </div>
                                <p class="mb-3">Deseja assinar digitalmente os contratos?</p>
                                <div class="mb-2">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="contratos-curso-assinar" id="contratos-assinar-sim" value="1" checked>
                                        <label class="form-check-label" for="contratos-assinar-sim">
                                            Sim, assinar digitalmente
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="contratos-curso-assinar" id="contratos-assinar-nao" value="0">
                                        <label class="form-check-label" for="contratos-assinar-nao">
                                            Não, gerar sem assinatura digital
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" id="btnConfirmarGerarContratosCurso">
                                    <i class="fa-solid fa-file-signature"></i> Gerar contratos
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="excluirModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <img id="del_fotoaluno" src="" class="rounded-circle img-cover" width="40" height="40" alt="">
                                <h1 class="modal-title fs-5">Excluir matrícula</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/matriculas/excluir/?turma=<?php echo $IdTurma; ?>" method="post">
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
                                            <label class="form-label">Município</label>
                                            <input type="text" class="form-control" id="del_municipio" name="municipio" readonly>
                                        </div>
                                        <div class="col-5">
                                            <label class="form-label">Data Matrícula</label>
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
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>
    <div id="overlayLoading" style="display: none;">
        <div class="overlay-bg"></div>
        <div class="overlay-content" id="overlayContent">
            <div class="spinner-border text-primary" role="status" id="overlaySpinner">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-2" id="overlayText">Carregando matrículas...</p>
            <button id="overlayBtnOk" class="btn btn-primary mt-3" style="display: none;">OK</button>
        </div>
    </div>
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>
</body>
</html>

<script>
    const ID_TURMA = <?php echo (int)$IdTurma; ?>;
    const ID_CURSO_TURMA = <?php echo (int)$IdCursoTurma; ?>;
    const SOMENTE_ATIVAS = <?php echo (int)$Habilitado; ?>;
    const TIPO_USUARIO = "<?php echo addslashes($_SESSION['Tipo'] ?? ''); ?>";
    const SOLICITAR_TURMA_URL = "<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/matriculas/contratos/turma/solicitar";
    const CONTRATO_INDIVIDUAL_BASE_URL         = "<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/matriculas/contrato/";
    const CONTRATO_INDIVIDUAL_PREVIEW_BASE_URL = "<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/matriculas/contrato/preview";
    const BENEFICIARIO_CADASTRO_BASE_URL = "<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/beneficiarios/cadastro";

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

    function fotoUrl(foto) {
        return foto
            ? `<?php echo rtrim(bootstrap_assets_img_url(), '/'); ?>/fotos/${foto}`
            : `<?php echo bootstrap_foto_url(''); ?>`;
    }

    function beneficiarioCadastroUrl(item) {
        const id = item?.IdUsuario ?? '';
        const cpfRaw = String(item?.CPF ?? '');
        const cpf = cpfRaw.replace(/\D/g, '');
        const params = new URLSearchParams();

        if (cpf) params.set('cpf', cpf);
        if (id !== '') params.set('id', String(id));

        const query = params.toString();
        return query ? `${BENEFICIARIO_CADASTRO_BASE_URL}?${query}` : BENEFICIARIO_CADASTRO_BASE_URL;
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
                <button type="button" class="btn btn-info btn-sm btn-historico-contrato"
                    data-turma="${escapeHtml(item.IdTurma)}"
                    data-aluno="${escapeHtml(item.IdUsuario)}"
                    data-nome="${escapeHtml(item.NomeAluno)}"
                    title="Gerar contrato">
                    <i class="fa-solid fa-file-signature"></i>
                </button>
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
            return `<a href="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/matriculas/turma/?turma=${escapeHtml(item.IdTurma)}&ativo=${escapeHtml(SOMENTE_ATIVAS)}&IdMatricula=${escapeHtml(item.IdMatricula)}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen-to-square" data-feather="edit-3"></i> ativar</a>`;
        }
        return '';
    }

    async function buscarTurmasPorCurso(idCurso) {
        const resp = await fetch(`${resolveApiBase()}/turmas/por-curso?IdCurso=${encodeURIComponent(idCurso)}&somenteAtivos=0`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        });
        const data = await resp.json();
        if (!resp.ok || !data.success) {
            throw new Error(data.message || 'Erro ao carregar turmas do curso.');
        }
        return data.data || [];
    }

    function definirEstadoTurmas(mensagem, disabled = true) {
        const editTurma = document.getElementById('edit_idturma');
        editTurma.innerHTML = '';
        const option = document.createElement('option');
        option.value = '';
        option.textContent = mensagem;
        editTurma.appendChild(option);
        editTurma.disabled = disabled;
    }

    function preencherTurmasDoCurso(turmas, turmaSelecionada = '') {
        const editTurma = document.getElementById('edit_idturma');
        editTurma.innerHTML = '';

        if (!Array.isArray(turmas) || turmas.length === 0) {
            definirEstadoTurmas('Nenhuma turma cadastrada neste curso', true);
            return;
        }

        turmas.forEach((turma) => {
            const option = document.createElement('option');
            option.value = turma.IdTurma;
            option.textContent = turma.NomeTurma || turma.IdTurma;
            editTurma.appendChild(option);
        });

        editTurma.disabled = false;
        if (turmaSelecionada !== '') {
            editTurma.value = String(turmaSelecionada);
        }
    }

    let carregarTurmasSeq = 0;
    async function carregarTurmasDoCurso(idCurso, turmaSelecionada = '') {
        if (!idCurso) {
            definirEstadoTurmas('Selecione um curso primeiro', true);
            return [];
        }

        const seq = ++carregarTurmasSeq;
        definirEstadoTurmas('Carregando turmas...', true);
        const turmas = await buscarTurmasPorCurso(idCurso);
        if (seq !== carregarTurmasSeq) {
            return turmas;
        }
        preencherTurmasDoCurso(turmas, turmaSelecionada);
        return turmas;
    }

    async function carregarSelects() {
        const cursosResp = await fetch(`${resolveApiBase()}/cursos`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const cursosData = await cursosResp.json();
        if (!cursosResp.ok || !cursosData.success) throw new Error('Erro ao carregar cursos');

        const editCurso = document.getElementById('edit_curso');
        editCurso.innerHTML = '';
        (cursosData.data || []).forEach((curso) => {
            const option = document.createElement('option');
            option.value = curso.IdCurso;
            option.textContent = curso.NomeCurso;
            editCurso.appendChild(option);
        });

        definirEstadoTurmas('Selecione um curso primeiro', true);

        if (ID_CURSO_TURMA > 0) {
            const turmasCursoAtual = await carregarTurmasDoCurso(ID_CURSO_TURMA, ID_TURMA);
            const turmaAtual = turmasCursoAtual.find((t) => Number(t.IdTurma) === Number(ID_TURMA));
            if (turmaAtual) {
                document.getElementById('titulo').innerHTML += ` <span style="color: blue;">${escapeHtml(turmaAtual.NomeTurma || '')}</span>`;
            }
        }
    }

    async function carregarMatriculas() {
        const resp = await fetch(`${resolveApiBase()}/matriculas/por-turma?turma=${encodeURIComponent(ID_TURMA)}&ativo=${encodeURIComponent(SOMENTE_ATIVAS)}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        });
        const data = await resp.json();
        if (!resp.ok || !data.success) throw new Error(data.message || 'Erro ao carregar matrículas');

        const tbody = document.getElementById('matriculasTbody');
        tbody.innerHTML = (data.data || []).map((item, idx) => {
            const ativo = Number(item.Habilitado) === 1;
            const status = ativo
                ? '<span class="badge bg-success text-center d-flex justify-content-center">Ativo</span>'
                : '<span class="badge bg-secondary text-center d-flex justify-content-center">Inativo</span>';
            return `<tr>
                <td>${idx + 1}</td>
                <td><img src="${escapeHtml(fotoUrl(item.Foto))}" class="rounded-circle img-cover" width="40" height="40" alt=""></td>
                <td><a href="${escapeHtml(beneficiarioCadastroUrl(item))}">${escapeHtml(item.NomeAluno)}</a> (Mat. ${escapeHtml(item.IdMatricula)})</td>
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
        $('#edit_curso').on('change', function() {
            carregarTurmasDoCurso(this.value).catch((err) => {
                definirEstadoTurmas(err.message || 'Erro ao carregar turmas.', true);
            });
        });

        $('#editarModal').on('show.bs.modal', async function(event) {
            const button = $(event.relatedTarget);
            const cursoAtual = button.data('curso');
            const turmaAtual = button.data('idturma');

            $('#edit_fotoaluno').attr('src', button.data('fotoaluno'));
            $('#edit_nomealuno').val(button.data('nomealuno'));
            $('#edit_idaluno').val(button.data('idaluno'));
            $('#edit_idmatricula').val(button.data('idmatricula'));
            $('#edit_curso').val(cursoAtual);
            $('#edit_municipio').val(button.data('municipio'));
            $('#edit_datamatricula').val(button.data('datamatricula'));

            try {
                await carregarTurmasDoCurso(cursoAtual, turmaAtual);
            } catch (err) {
                definirEstadoTurmas(err.message || 'Erro ao carregar turmas.', true);
            }
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

    function renderHistoricoContratos(items) {
        if (!Array.isArray(items) || items.length === 0) {
            return '<div class="alert alert-light border mb-0">Nenhum contrato anterior foi encontrado para este aluno.</div>';
        }

        const rows = items.map((item, index) => `
            <tr>
                <td>${index + 1}</td>
                <td>${escapeHtml(item.dataAssinatura || '-')}</td>
                <td>${escapeHtml(item.codigo || '-')}</td>
                <td>
                    ${item.downloadUrl ? `<a href="${escapeHtml(item.downloadUrl)}" target="_blank" rel="noopener noreferrer">Baixar PDF</a>` : '-'}
                </td>
            </tr>
        `).join('');

        return `
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Data da assinatura</th>
                        <th>Código</th>
                        <th>Download</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        `;
    }

    const CONTRATOS_CURSO_BASE_URL   = "<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/matriculas/contratos/curso";
    const CONTRATOS_PREVIEW_BASE_URL = "<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/matriculas/contratos/preview";

    (function iniciarModalContratosCurso() {
        const btnGerar = document.getElementById('btnGerarContratosCurso');
        const modalEl  = document.getElementById('modalGerarContratosCurso');
        const btnConf  = document.getElementById('btnConfirmarGerarContratosCurso');
        if (!btnGerar || !modalEl || !btnConf) return;

        btnGerar.addEventListener('click', function () {
            new bootstrap.Modal(modalEl).show();
        });

        btnConf.addEventListener('click', function () {
            const modo    = (modalEl.querySelector('input[name="contratos-curso-modo"]:checked') || {}).value || 'individual';
            const assinar = (modalEl.querySelector('input[name="contratos-curso-assinar"]:checked') || {}).value || '1';
            const curso   = btnGerar.getAttribute('data-curso') || '0';
            const turma   = btnGerar.getAttribute('data-turma') || '0';

            const params = '?curso='   + encodeURIComponent(curso) +
                           '&turma='   + encodeURIComponent(turma) +
                           '&modo='    + encodeURIComponent(modo) +
                           '&assinar=' + encodeURIComponent(assinar);

            // Com assinatura digital: preview interativo para posicionar o bloco
            const url = assinar === '1'
                ? CONTRATOS_PREVIEW_BASE_URL + params
                : CONTRATOS_CURSO_BASE_URL   + params;

            window.open(url, '_blank', 'noopener');

            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        });
    })();

    document.addEventListener('DOMContentLoaded', async function() {
        let carregamentoComErro = false;
        if (typeof mostrarOverlay === 'function') {
            mostrarOverlay({
                mensagem: 'Carregando matrículas...',
                carregando: true
            });
        }

        try {
            await carregarSelects();
            await carregarMatriculas();
        } catch (error) {
            console.error(error);
            carregamentoComErro = true;
            if (typeof mostrarOverlay === 'function') {
                mostrarOverlay({
                    mensagem: error.message || 'Erro ao carregar dados da turma.',
                    carregando: false
                });
            }
        } finally {
            const tabela = document.getElementById('minhaTabela');
            if (tabela) tabela.style.display = 'table';
            if (!carregamentoComErro && typeof esconderOverlay === 'function') {
                esconderOverlay();
            }
        }

        const btnSolicitar = document.getElementById('btnConfirmarSolicitacaoTurma');
        const inputEmail = document.getElementById('email_contratos_turma');
        const feedback = document.getElementById('feedback_solicitacao_turma');
        const modalEl = document.getElementById('modalSolicitarContratosTurma');
        const modalHistoricoEl = document.getElementById('modalHistoricoContratosAluno');
        const historicoDescricao = document.getElementById('historicoContratosDescricao');
        const historicoFeedback = document.getElementById('historicoContratosFeedback');
        const historicoLista = document.getElementById('historicoContratosLista');
        const btnCriarNovoContrato = document.getElementById('btnCriarNovoContrato');
        let novoContratoUrl = '';

        if (btnSolicitar && inputEmail && feedback && modalEl) {
            btnSolicitar.addEventListener('click', async () => {
                const email = (inputEmail.value || '').trim();
                if (!email) {
                    feedback.className = 'small mt-2 text-danger';
                    feedback.textContent = 'Informe um e-mail válido.';
                    return;
                }

                btnSolicitar.disabled = true;
                feedback.className = 'small mt-2 text-muted';
                feedback.textContent = 'Enviando solicitação...';

                try {
                    const body = new URLSearchParams();
                    body.set('turma', String(ID_TURMA));
                    body.set('email_destino', email);

                    const resp = await fetch(SOLICITAR_TURMA_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: body.toString()
                    });

                    const data = await resp.json();
                    if (!resp.ok || !data.ok) {
                        throw new Error(data.erro || `Falha HTTP ${resp.status}`);
                    }

                    feedback.className = 'small mt-2 text-success';
                    feedback.textContent = data.mensagem || 'Solicitação registrada com sucesso.';

                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) {
                            modal.hide();
                        }
                    }, 800);
                } catch (error) {
                    feedback.className = 'small mt-2 text-danger';
                    feedback.textContent = error.message || 'Erro ao registrar solicitação.';
                } finally {
                    btnSolicitar.disabled = false;
                }
            });
        }

        if (modalHistoricoEl && historicoDescricao && historicoFeedback && historicoLista && btnCriarNovoContrato) {
            document.addEventListener('click', async (event) => {
                const button = event.target.closest('.btn-historico-contrato');
                if (!button) {
                    return;
                }

                event.preventDefault();
                const idTurma = button.getAttribute('data-turma') || '';
                const idAluno = button.getAttribute('data-aluno') || '';
                const nomeAluno = button.getAttribute('data-nome') || 'aluno';
                const historicoBaseUrl = `${CONTRATO_INDIVIDUAL_BASE_URL}?turma=${encodeURIComponent(idTurma)}&aluno=${encodeURIComponent(idAluno)}`;
                novoContratoUrl = `${CONTRATO_INDIVIDUAL_PREVIEW_BASE_URL}?turma=${encodeURIComponent(idTurma)}&aluno=${encodeURIComponent(idAluno)}`;

                historicoDescricao.textContent = `Contratos já emitidos para ${nomeAluno}.`;
                historicoFeedback.className = 'small mb-3 text-muted';
                historicoFeedback.textContent = 'Consultando contratos emitidos...';
                historicoLista.innerHTML = '<div class="d-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span><span>Carregando histórico...</span></div>';

                const modal = new bootstrap.Modal(modalHistoricoEl);
                modal.show();

                try {
                    const response = await fetch(`${historicoBaseUrl}&ajax=historico`, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                        cache: 'no-store'
                    });
                    const data = await response.json();

                    if (!response.ok || !data.ok) {
                        throw new Error(data.erro || 'Não foi possível consultar os contratos emitidos.');
                    }

                    historicoFeedback.className = 'small mb-3 text-muted';
                    historicoFeedback.textContent = data.items.length > 0
                        ? 'Revise os contratos existentes antes de gerar um novo.'
                        : 'Nenhum contrato anterior encontrado.';
                    historicoLista.innerHTML = renderHistoricoContratos(data.items || []);
                    novoContratoUrl = data.novoContratoUrl || novoContratoUrl;
                } catch (error) {
                    historicoFeedback.className = 'small mb-3 text-danger';
                    historicoFeedback.textContent = error.message || 'Erro ao consultar contratos emitidos.';
                    historicoLista.innerHTML = '';
                }
            });

            btnCriarNovoContrato.addEventListener('click', () => {
                if (novoContratoUrl) {
                    window.open(novoContratoUrl, '_blank', 'noopener');
                }
            });
        }
    });
</script>










