<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$appJsVersion = @filemtime($BASE_para_PATH . '/app/assets/js/app.js') ?: time();
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
} // 24 horas

// Verifica se as variaveis de sessao BASE_para_PATH e BASE_para_URL estao definidas
if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    // Salva a URL atual para redirecionar o usuario apos o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endereco atual
    header("Location: " . rtrim((string) ($BASE_para_URL ?? ''), '/') . "/login/?redirect=$redirect_url");
    exit(); // Garante que o codigo abaixo nao sera executado
}

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/repositories/ConfigRepository.php';
require_once $BASE_para_PATH . '/api/services/ConfigService.php';
require_once $BASE_para_PATH . '/api/repositories/RelatorioRepository.php';
require_once $BASE_para_PATH . '/api/services/RelatorioService.php';

$configService = new \BackEnd\Services\ConfigService(new \BackEnd\Repositories\ConfigRepository());
$relatorioService = new \BackEnd\Services\RelatorioService(new \BackEnd\Repositories\RelatorioRepository());

$config = $configService->getConfig();
$ativaZap = !empty($config['APIzap']);
$desabilita = empty($config['APIzap']) ? 'disabled' : '';
$cursosDisponiveis = $relatorioService->cursosOptions(0);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.1/spectrum.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.1/spectrum.min.css">
    <script src="https://cdn.tiny.cloud/1/maa7p6ervwrty9h84gphxrsrfking1awabjrp7tak98iuwjh/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <link rel="stylesheet" href="<?php echo $BASE_para_URL; ?>/assets/css/overlayNotifica.css">

    <style>
        .card-hover {
            transition: transform 0.2s ease;
            cursor: pointer;
        }

        .card-hover:hover {
            transform: scale(1.02);
        }

        .custom-context-menu {
            min-width: 260px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.35);
            overflow: hidden;
            font-size: 0.75rem;
            padding: 8px 0;
        }

        .custom-context-menu .list-group-item {
            border: none;
            background: none;
            padding: 10px 15px;
            transition: background 0.2s, color 0.2s;
            color: #333;
            font-weight: 500;
        }

        .custom-context-menu .list-group-item:hover {
            background-color: #f1f3f5;
            color: #000;
        }

        .custom-context-menu .list-group-item i {
            width: 20px;
            height: 20px;
        }
    </style>





    <script>
        tinymce.init({
            selector: '#textoNota',
            height: 250,
            menubar: false,
            plugins: 'image link lists code autoresize',
            toolbar: 'undo redo | styles | bold italic underline | bullist numlist | link image | code',
            language: 'pt_BR',
            branding: false,
            images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/crm/salvar/');

                xhr.onload = () => {
                    if (xhr.status < 200 || xhr.status >= 300) {
                        reject('HTTP Error: ' + xhr.status);
                        return;
                    }

                    let json;
                    try {
                        json = JSON.parse(xhr.responseText);
                    } catch (e) {
                        reject('Erro ao processar JSON: ' + xhr.responseText);
                        return;
                    }

                    if (!json || typeof json.location !== 'string') {
                        reject('Resposta inválida: ' + xhr.responseText);
                        return;
                    }

                    resolve(json.location);
                };

                xhr.onerror = () => {
                    reject('Erro de conexão. Código: ' + xhr.status);
                };

                const formData = new FormData();
                formData.append('action', 'uploadImagemNota');
                formData.append('file', blobInfo.blob(), blobInfo.filename());

                xhr.send(formData);
            })
        });
    </script>



    <script>
        function mostrarDiv(area) {
            // Lista das divs que queremos alternar visibilidade
            const divs = ['divTarefas', 'divBusca', 'divTimeline', 'divNotificacoes'];

            // Esconde todas
            divs.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.setProperty('display', 'none', 'important');
            });

            // Mostra a div especifica
            const id = 'div' + area.charAt(0).toUpperCase() + area.slice(1);
            const target = document.getElementById(id);
            if (target) {
                target.style.setProperty('display', 'block', 'important');
                if (area === 'tarefas') {
                    carregarTarefas();
                }
                if (area === 'notificacoes') carregarPreferencias();
            } else {
                console.warn('Div nao encontrada:', id);
            }
        }

        function carregarTurmas() {
            const idCurso = document.getElementById('curso').value;
            const turmaSelect = document.getElementById('turma');
            turmaSelect.innerHTML = '';

            if (idCurso === '') {
                turmaSelect.innerHTML = '<option value="">Selecione um curso primeiro...</option>';
                turmaSelect.disabled = true;
                return;
            }

            if (idCurso == 0) {
                turmaSelect.disabled = false;
                turmaSelect.innerHTML = '<option value="">TODAS as turmas</option>';

                fetch('<?php echo $BASE_para_URL; ?>/get/getTurmasNome.php?IdCurso=all')
                    .then(res => res.text())
                    .then(html => {
                        turmaSelect.innerHTML += html;
                    });

                return;
            }


            // Curso especifico
            // Para curso especifico
            fetch('<?php echo $BASE_para_URL; ?>/get/getTurmasNome.php?IdCurso=' + idCurso)
                .then(res => res.text())
                .then(html => {
                    turmaSelect.disabled = false;
                    turmaSelect.innerHTML = '<option value="">TODAS as turmas</option>' + html;
                });
        }
    </script>
</head>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>

            <main class="content">
                <div class="container-fluid mt-4">

                    <!-- Botoes de Acao -->
                    <div class="mb-3 d-flex gap-2">
                        <button class="btn btn-primary btn-sm" onclick="mostrarDiv('tarefas')">
                            <i class="fa-solid fa-list"></i> Ver Tarefas
                        </button>
                        <button class="btn btn-success btn-sm" onclick="mostrarDiv('busca')">
                            <i class="fa-solid fa-search"></i> Fazer Busca
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="mostrarDiv('timeline')">
                            <i class="fa-solid fa-clock-rotate-left"></i> Ver Hist&oacute;rico
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="mostrarDiv('notificacoes')">
                            <i class="fa-solid fa-bell"></i> Notificações
                        </button>
                    </div>

                    <!-- Area de Tarefas -->
                    <div id="divTarefas" class="mb-4">
                        <div class="card shadow-lg">
                            <div class="card-header bg-primary text-white">
                                Tarefas Registradas
                            </div>
                            <div class="card-body table-responsive">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <label for="filtroNotificado" class="form-label me-2">Mostrar:</label>
                                        <select id="filtroNotificado" class="form-select form-select-sm d-inline-block w-auto">
                                            <option value="0" selected>Não notificadas</option>
                                            <option value="1">Notificadas</option>
                                            <option value="todas">Todas</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="filtroTipo" class="form-label me-2">Tipo:</label>
                                        <select id="filtroTipo" class="form-select form-select-sm d-inline-block w-auto">
                                            <option value="todas" selected>Todas</option>
                                            <option value="importantes">Importantes</option>
                                            <option value="futuras">Futuras</option>
                                            <option value="feitas">Feitas</option>
                                        </select>
                                    </div>

                                    <div>
                                        <input type="checkbox" id="filtroMinhasTarefas" class="form-check-input me-1">
                                        <label for="filtroMinhasTarefas" class="form-check-label">Filtrar as minhas</label>
                                    </div>
                                </div>

                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Aluno</th>
                                            <th>Descrição</th>
                                            <th>Responsável</th>
                                            <th>Data/Hora</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabelaTarefasBody">
                                        <!-- Linhas de tarefas serao inseridas aqui via PHP ou JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Area de Busca -->
                    <div id="divBusca" style="display: none;">
                        <div class="card shadow-lg">
                            <div class="card-header bg-success text-white">
                                Buscar Alunos
                            </div>
                            <div class="card-body">
                                <!-- Formulario de busca e exibicao dos cards aqui -->
                                <form id="formBusca" class="mb-4">
                                    <div class="row mb-4">
                                        <div class="col-md-3">
                                            <label class="form-label" for="curso">Curso</label>
                                            <select name="curso" id="curso" class="form-select" onchange="carregarTurmas()">
                                                <option value="">Selecione aqui...</option>
                                                <option value="0">TODOS os cursos</option>
                                                <?php
                                                foreach ($cursosDisponiveis as $curso) {
                                                    if (!is_numeric((string)($curso['value'] ?? ''))) {
                                                        continue;
                                                    }
                                                    $idCurso = htmlspecialchars((string)$curso['value'], ENT_QUOTES, 'UTF-8');
                                                    $nomeCurso = htmlspecialchars((string)$curso['label'], ENT_QUOTES, 'UTF-8');
                                                    echo "<option value=\"{$idCurso}\">{$nomeCurso}</option>";
                                                }
                                                ?>
                                            </select>

                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="turma">Turma</label>
                                            <select name="turma" id="turma" class="form-select" disabled>
                                                <option value="">Selecione um curso primeiro...</option>
                                            </select>

                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label" for="faltas">Faltou</label>
                                            <input type="number" min="0" name="faltas" id="faltas" class="form-control" placeholder="Ex: 3">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label" for="dias">Nos últimos (dias)</label>
                                            <input type="number" min="0" name="dias" id="dias" class="form-control" placeholder="Ex: 7">
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="button" class="btn btn-success w-100" onclick="buscarAlunos()">
                                                <i class='fa-solid fa-magnifying-glass'></i> Fazer busca
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="somenteMatriculados" name="somenteMatriculados" checked>
                                            <label class="form-check-label" for="somenteMatriculados">
                                                Somente Matriculados
                                            </label>
                                        </div>
                                    </div>
                                    <div id="resultadoBuscaCount" class="text-muted small mt-4 mb-3"></div>
                                </form>


                                <!-- Aqui serao carregados os cards -->
                                <div class="row" id="cardsAlunos">
                                    <!-- Cards serao inseridos via JS -->
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Área de Timeline (Notas e Histórico de Interação) -->
                    <div id="divTimeline" style="display: none;">
                        <div class="card shadow-lg">
                            <div class="card-header bg-secondary text-white">
                                Hist&oacute;rico de Intera&ccedil;&otilde;es
                            </div>
                            <div class="card-body" id="conteudoTimeline">
                                <div id="fichaAluno" class="mb-4"></div>
                                <div class="d-flex gap-2 mb-3">
                                    <button class="btn btn-success btn-sm" onclick="mostrarFormNota()">
                                        <i class="fa-solid fa-pen"></i> Adicionar Nota
                                    </button>
                                    <button class="btn btn-primary btn-sm" onclick="mostrarFormTarefa()">
                                        <i class="fa-solid fa-calendar-plus"></i> Adicionar Tarefa
                                    </button>
                                </div>

                                <?php require_once __DIR__ . '/formNota.php'; ?>
                                <div id="historicoNotas"></div>
                            </div>

                        </div>
                    </div>
                    <!-- Area de Notificacoes -->
                    <div id="divNotificacoes" style="display: none;">
                        <div class="card shadow-lg">
                            <div class="card-header bg-warning text-dark">
                                Notificações
                            </div>
                            <div class="card-body">
                                <form id="formNotificacoes">
                                    <div class="mb-3">
                                        <h6>Nota</h6>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="nota_email">
                                            <label class="form-check-label" for="nota_email">
                                                E-mail: <span id="emailUsuario"></span> <a href="<?php echo $BASE_para_URL; ?>/perfil?m=mail">(mudar)</a>
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="nota_whatsapp" <?= $desabilita ?>>
                                            <label class="form-check-label" for="nota_whatsapp">
                                                WhatsApp: <span id="zapUsuario"></span> <a href="<?php echo $BASE_para_URL; ?>/perfil?m=ws" <?php echo ($ativaZap == false) ? 'disabled' : ''; ?>>(mudar)</a>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <h6>Tarefa</h6>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="tarefa_email">
                                            <label class="form-check-label" for="tarefa_email">
                                                E-mail: <span id="emailUsuario2"></span> <a href="<?php echo $BASE_para_URL; ?>/perfil?m=mail">(mudar)</a>
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="tarefa_whatsapp" <?= $desabilita ?>>
                                            <label class="form-check-label" for="tarefa_whatsapp">
                                                WhatsApp: <span id="zapUsuario2"></span> <a href="<?php echo $BASE_para_URL; ?>/perfil?m=ws" <?php echo ($ativaZap == false) ? 'disabled' : ''; ?>>(mudar)</a>
                                            </label>
                                        </div>
                                    </div>

                                    <div id="msgPreferencias" class="mt-2 small text-muted"></div>
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
    <div id="contextMenu" class="custom-context-menu" style="display: none; position: absolute; z-index: 9999;">
        <ul class="list-group list-group-flush mb-0">
            <li class="list-group-item d-flex align-items-center" id="menuVerMatricula" style="cursor:pointer;">
                <i data-feather="book-open" class="me-2"></i> Ver matrícula
            </li>
            <li class="list-group-item d-flex align-items-center" id="menuListarFaltas" style="cursor:pointer;">
                <i data-feather="calendar" class="me-2"></i> Listar Faltas
            </li>
            <li class="list-group-item d-flex align-items-center" id="menuDadosAluno" style="cursor:pointer;">
                <i data-feather="user" class="me-2"></i> Dados do Aluno
            </li>
        </ul>
    </div>

    <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/overlayNotifica.js"></script>

    <script>
        function mostrarHistorico(idUsuario) {
            document.getElementById('historicoNotas').innerHTML = '';
            mostrarDiv('timeline');

            fetch('<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/crm/salvar/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'dadosAluno',
                        idUsuario: idUsuario
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'ok') {
                        const a = data.aluno;
                        const fichaDiv = document.getElementById('fichaAluno');
                        document.getElementById('notaIdUsuario').value = idUsuario;
                        document.getElementById('tarefaIdUsuario').value = idUsuario;

                        fichaDiv.innerHTML = `
                                                <div class="d-flex align-items-center mb-3">
                                                    <img src="${a.Foto ? '<?php echo rtrim(bootstrap_assets_img_url(), '/'); ?>/fotos/' + a.Foto : '<?php echo bootstrap_foto_url(''); ?>'}" class="rounded-circle me-3" width="80" height="80">
                                                    <div>
                                                        <h5 class="mb-0">
                                                            <a href="<?php echo $BASE_para_URL; ?>/chamada/faltas/?idAluno=${idUsuario}&NNomeCurso=${a.IdCurso}&NNomeTurma=${a.IdTurma}" target="_blank" class="text-decoration-none">
                                                                ${a.Apelido ? `(${a.Apelido}) ` : ''}${a.Nome}
                                                            </a>
                                                        </h5>
                                                        <small><strong>Curso: </strong>${a.NomeCurso} - <strong>Turma: </strong>${a.NomeTurma}</small><br>
                                                        <small><strong>Contatos: </strong>
                                                            ${a.Telefone ? `${a.Telefone} (só telefone)` : ''}
                                                            ${a.Telefone && a.WhatsApp ? ' | ' : ''}
                                                            ${a.WhatsApp ? `${a.WhatsApp} (telefone e WhatsApp)` : ''}
                                                        </small><br>

                                                        <small><strong>Endereço: </strong>${a.Endereco}, ${a.Bairro}, ${a.Cidade}-${a.UF}</small><br>
                                                        <small><strong>Faltas:</strong> ${a.TotalFaltas}</small>
                                                    </div>
                                                </div>
                                            `;
                    }
                });
            fetch('<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/crm/salvar/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'listarNotasAluno',
                        idUsuario
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'ok') {
                        const container = document.getElementById('historicoNotas');
                        let html = '<h6 class="mt-4">Histórico de Notas</h6>';

                        if (data.notas.length > 0) {
                            data.notas.forEach(nota => {
                                html += `
                                        <div class="card mb-2">
                                            <div class="card-body">
                                                <small class="text-muted">${nota.DataHoraCriacao} - ${nota.NomeColaborador}</small>
                                                <p class="mb-0">${nota.TextoNota}</p>
                                            </div>
                                        </div>
                                    `;
                            });
                        } else {
                            html += '<div class="alert alert-secondary">Nenhuma nota registrada ainda.</div>';
                        }

                        container.innerHTML = html;

                    }
                });

        }

        function atualizarHistoricoNotas(idUsuario) {
            document.getElementById('historicoNotas').innerHTML = '';
            fetch('<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/crm/salvar/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'listarNotasAluno',
                        idUsuario
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'ok') {
                        document.getElementById('historicoNotas').innerHTML = '';

                        const container = document.createElement('div');
                        container.innerHTML = `<h6 class="mt-4">Histórico de Notas</h6>`;

                        if (data.notas.length > 0) {
                            data.notas.forEach(nota => {
                                const card = document.createElement('div');
                                card.className = 'card mb-2';
                                card.innerHTML = `
                        <div class="card-body">
                            <small class="text-muted">${nota.DataHoraCriacao} - ${nota.NomeColaborador}</small>
                            <p class="mb-0">${nota.TextoNota}</p>
                        </div>
                    `;
                                container.appendChild(card);
                            });
                        } else {
                            container.innerHTML += '<div class="alert alert-secondary">Nenhuma nota registrada ainda.</div>';
                        }

                        document.getElementById('historicoNotas').appendChild(container);
                    }
                });
        }
        document.addEventListener('DOMContentLoaded', function() {
            const hoje = new Date().toISOString().split('T')[0];
            document.getElementById('dataExecucao').setAttribute('min', hoje);
        });

        function mostrarFormNota() {
            document.getElementById('formNovaNota').style.display = 'block';
            document.getElementById('formNovaTarefa').style.display = 'none';
        }

        function mostrarFormTarefa() {
            document.getElementById('formNovaNota').style.display = 'none';
            document.getElementById('formNovaTarefa').style.display = 'block';
        }

        document.getElementById('novaTarefaForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);

            const dataEscolhida = new Date(formData.get('dataExecucao'));
            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);

            if (dataEscolhida <= hoje) {
                mostrarOverlay({
                    mensagem: 'Escolha uma data futura para a tarefa.',
                    carregando: false,
                    onClose: () => esconderOverlay()
                });
                return;
            }

            mostrarOverlay({
                mensagem: 'Salvando dados...',
                carregando: true
            });

            fetch('<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/crm/salvar/', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'ok') {
                        form.reset();
                        carregarTarefas();
                        mostrarOverlay({
                            mensagem: 'Tarefa salva com sucesso!',
                            carregando: false,
                            onClose: () => esconderOverlay()
                        });
                    } else {
                        mostrarOverlay({
                            mensagem: 'Erro ao salvar a tarefa.',
                            carregando: false,
                            onClose: () => esconderOverlay()
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao enviar tarefa:', error);
                    mostrarOverlay({
                        mensagem: 'Erro ao enviar tarefa. Verifique o console.',
                        carregando: false,
                        onClose: () => esconderOverlay()
                    });
                });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            carregarTarefas();

            // Verifica se veio idUsuario na URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('idUsuario')) {
                mostrarDiv('timeline');
                const dadosSalvos = sessionStorage.getItem('dadosBuscaCRM');
                if (dadosSalvos) {
                    const dados = JSON.parse(dadosSalvos);
                    for (const key in dados) {
                        const el = document.querySelector(`[name="${key}"]`);
                        if (el) el.value = dados[key];
                    }
                }
                const idUsuario = urlParams.get('idUsuario');
                fetch('<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/crm/salvar/', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'dadosAluno',
                            idUsuario
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        console.log('Resposta do servidor:', data);
                        if (data.status === 'ok') {
                            const a = data.aluno;
                            const fichaDiv = document.getElementById('fichaAluno');
                            fichaDiv.innerHTML = `
                                                    <div class="d-flex align-items-center mb-3">
                                                        <img src="${a.Foto ? '<?php echo rtrim(bootstrap_assets_img_url(), '/'); ?>/fotos/' + a.Foto : '<?php echo bootstrap_foto_url(''); ?>'}" class="rounded-circle me-3" width="80" height="80">
                                                        <div>
                                                            <h5 class="mb-0">${a.Apelido ? `(${a.Apelido}) ` : ''}${a.Nome}</h5>
                                                            <small>${a.NomeCurso} - ${a.NomeTurma}</small><br>
                                                            <small>${a.Telefone} | ${a.WhatsApp}</small><br>
                                                            <small>${a.Endereco}, ${a.Bairro}, ${a.Cidade}-${a.UF}</small><br>
                                                            <small><strong>Faltas:</strong> ${a.TotalFaltas}</small>
                                                        </div>
                                                    </div>`;
                        } else {
                            console.error('Erro ao obter dados do aluno:', data.message);
                        }
                    }).catch(error => {
                        console.error('Erro na requisicao:', error);
                    });

            } else {
                mostrarDiv('tarefas'); // padrao
            }
            // Preenche o email e WhatsApp do colaborador logado
            fetch('<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/crm/salvar/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'dadosColaborador'
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'ok') {
                        // document.getElementById('email').value = data.colaborador.Email || '';
                        // document.getElementById('whatsapp').value = data.colaborador.WhatsApp || '';
                    }
                });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const divTimeline = document.getElementById('divTimeline');
                    if (divTimeline && divTimeline.style.display !== 'none') {
                        mostrarDiv('busca');
                    }
                }
            });

        });


        let tarefasGlobal = []; // todas as tarefas carregadas

        function carregarTarefas() {
            fetch('<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/crm/salvar/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'listarTarefas'
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'ok') {
                        tarefasGlobal = data.tarefas;
                        renderizarTabelaTarefas();
                    }
                });
        }

        function renderizarTabelaTarefas() {
            const tbody = document.getElementById('tabelaTarefasBody');
            tbody.innerHTML = '';

            const filtroNotificado = document.getElementById('filtroNotificado').value;
            const filtrarMinhas = document.getElementById('filtroMinhasTarefas').checked;
            const filtroTipo = document.getElementById('filtroTipo').value;
            const meuCodigo = "<?php echo (int)($_SESSION['Cod'] ?? 0); ?>";

            let tarefasFiltradas = tarefasGlobal;

            if (filtroNotificado !== 'todas') {
                tarefasFiltradas = tarefasFiltradas.filter(t => String(t.Notificado) === filtroNotificado);
            }

            if (filtrarMinhas) {
                tarefasFiltradas = tarefasFiltradas.filter(t => String(t.IdColaborador) === meuCodigo);
            }

            if (filtroTipo !== 'todas') {
                tarefasFiltradas = tarefasFiltradas.filter(t => {
                    if (filtroTipo === 'importantes') return t.cor === 'amarelo' && t.Status !== 'feito';
                    if (filtroTipo === 'futuras') return t.cor === 'branco';
                    if (filtroTipo === 'feitas') return t.Status === 'feito';
                    return true;
                });
            }

            tarefasFiltradas.forEach(tarefa => {
                const tr = document.createElement('tr');

                let badge = '';
                if (tarefa.Status === 'feito') {
                    badge = '<span class="badge bg-success">Feita</span>';
                } else if (tarefa.cor === 'vermelho') {
                    badge = '<span class="badge bg-danger">Atrasada</span>';
                } else if (tarefa.cor === 'amarelo') {
                    badge = '<span class="badge bg-warning text-dark">Importante</span>';
                } else {
                    badge = '<span class="badge bg-primary">Futura</span>';
                }

                let botoes = '';
                if (tarefa.Status !== 'feito') {
                    botoes = `
                <button class="btn btn-success btn-sm rounded me-1 mb-3" onclick="marcarFeito(${tarefa.IdTarefa})">
                    <i class="fa-solid fa-check"></i> Feito
                </button>
                <button class="btn btn-primary btn-sm me-1 rounded mb-3" onclick="mostrarHistorico(${tarefa.IdUsuario})">
                    <i class="fa-solid fa-pen-to-square"></i> Fazer
                </button>
            `;
                }

                botoes += `
            <button class="btn btn-danger btn-sm rounded mb-3" onclick="excluirTarefa(${tarefa.IdTarefa})">
                <i class="fa-solid fa-trash"></i> Excluir
            </button>
        `;

                tr.innerHTML = `
            <td>${tarefa.NomeAluno}</td>
            <td>${tarefa.DescricaoTarefa}</td>
            <td>${tarefa.NomeColaborador}</td>
            <td>${formatarDataHora(tarefa.DataHoraExecucao)}</td>
            <td>${badge}</td>
            <td>${botoes}</td>
        `;
                tbody.appendChild(tr);
            });
        }


        document.getElementById('filtroNotificado').addEventListener('change', renderizarTabelaTarefas);
        document.getElementById('filtroMinhasTarefas').addEventListener('change', renderizarTabelaTarefas);
        document.getElementById('filtroTipo').addEventListener('change', renderizarTabelaTarefas);


        function formatarDataHora(dataHoraStr) {
            const data = new Date(dataHoraStr);
            return data.toLocaleString('pt-BR', {
                dateStyle: 'short',
                timeStyle: 'short'
            });
        }

        function marcarFeito(id) {
            fetch('<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/crm/salvar/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'marcarFeito',
                        id
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'ok') {
                        carregarTarefas(); // atualiza a tabela
                    }
                });
        }

        function excluirTarefa(id) {
            if (confirm('Tem certeza que deseja excluir esta tarefa?')) {
                fetch('<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/crm/salvar/', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'excluirTarefa',
                            id
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'ok') {
                            carregarTarefas(); // atualiza a tabela
                        }
                    });
            }
        }

        function buscarAlunos() {
            const form = document.getElementById('formBusca');
            const formData = new FormData(form);
            formData.append('action', 'buscarAlunosFaltosos');
            formData.append('somenteMatriculados', document.getElementById('somenteMatriculados').checked ? 1 : 0);

            fetch('<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/crm/salvar/', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('cardsAlunos');
                    container.innerHTML = '';
                    if (data.status === 'ok' && data.alunos.length > 0) {
                        data.alunos.forEach(aluno => {
                            let infoExtras = '';
                            if (aluno.TotalTarefas > 0) {
                                infoExtras += `<span class="badge bg-primary rounded-circle me-1" style="font-size: 0.7rem; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center;">${aluno.TotalTarefas}</span>`;
                            }
                            if (aluno.TotalNotas > 0) {
                                infoExtras += `<span class="badge bg-success rounded-circle" style="font-size: 0.7rem; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center;">${aluno.TotalNotas}</span>`;
                            }
                            if (aluno.Habilitado == 0) {
                                infoExtras += `<span class="badge bg-secondary ms-1" style="font-size: 0.7rem;">Desmatriculado</span>`;
                            }

                            const card = document.createElement('div');
                            card.className = 'col-md-4';

                            // Badge caso esteja desmatriculado
                            let badgeStatus = '';
                            if (aluno.Habilitado == 0) {
                                badgeStatus = `<span class="badge bg-secondary mb-1">Desmatriculado</span><br>`;
                            }

                            // Construindo o card
                            card.innerHTML = `
                                                <div class="d-flex shadow-lg rounded mb-3 p-2 bg-light align-items-start justify-content-between card-hover">
                                                    <img src="<?php echo rtrim(bootstrap_assets_img_url(), '/'); ?>/fotos/${aluno.Foto || 'padrao.jpg'}" class="rounded-circle me-3" width="50" height="50" alt="Foto">
                                                    <div class="flex-grow-1">
                                                        ${badgeStatus}
                                                        <strong>${aluno.Apelido ? `(${aluno.Apelido}) ` : ''}${aluno.Nome} (${aluno.TotalFaltas} faltas)</strong><br>
                                                        <small>${aluno.NomeCurso}</small><br>
                                                        <small>${aluno.NomeTurma}</small>
                                                    </div>
                                                    <div class="ms-2 text-end">
                                                        ${aluno.TotalTarefas > 0 ? `<span class="badge bg-primary rounded-circle mb-1" style="font-size: 0.7rem; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center;">${aluno.TotalTarefas}</span>` : ''}
                                                        ${aluno.TotalNotas > 0 ? `<span class="badge bg-success rounded-circle" style="font-size: 0.7rem; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center;">${aluno.TotalNotas}</span>` : ''}
                                                    </div>
                                                </div>
                                            `;

                            card.onclick = () => {
                                mostrarHistorico(aluno.IdUsuario);
                            };
                            card.oncontextmenu = (e) => {
                                e.preventDefault();
                                abrirMenuContexto(e, aluno);
                            };

                            // Guardando informações
                            card.dataset.idUsuario = aluno.IdUsuario;
                            card.dataset.idTurma = aluno.IdTurma;
                            card.dataset.idCurso = aluno.IdCurso;

                            container.appendChild(card);

                        });

                        document.getElementById('resultadoBuscaCount').textContent = `Foram encontrados ${data.alunos.length} registro(s)`;
                    } else {
                        container.innerHTML = '<div class="alert alert-warning">Nenhum aluno encontrado com os criterios informados.</div>';
                        document.getElementById('resultadoBuscaCount').textContent = 'Nenhum aluno encontrado com os criterios informados.';
                    }
                });
        }

        function carregarPreferencias() {
            fetch('<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/crm/salvar/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'getPreferenciasNotificacao'
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'ok') {
                        const p = data.preferencias;
                        document.getElementById('nota_email').checked = p.Nota_Email == 1;
                        document.getElementById('nota_whatsapp').checked = p.Nota_WhatsApp == 1;
                        document.getElementById('tarefa_email').checked = p.Tarefa_Email == 1;
                        document.getElementById('tarefa_whatsapp').checked = p.Tarefa_WhatsApp == 1;

                        document.getElementById('emailUsuario').textContent = p.Email;
                        document.getElementById('emailUsuario2').textContent = p.Email;
                        document.getElementById('zapUsuario').textContent = p.WhatsApp;
                        document.getElementById('zapUsuario2').textContent = p.WhatsApp;

                        // Aplica listeners aos checkboxes para salvar automaticamente ao mudar
                        ['nota_email', 'nota_whatsapp', 'tarefa_email', 'tarefa_whatsapp'].forEach(id => {
                            const checkbox = document.getElementById(id);
                            checkbox.addEventListener('change', () => {
                                salvarPreferencias(true);
                            });
                        });
                    }
                });
        }


        function salvarPreferencias(mostrarMensagem = false) {
            const dados = new URLSearchParams({
                action: 'salvarPreferenciasNotificacao',
                nota_email: document.getElementById('nota_email').checked ? 1 : 0,
                nota_whatsapp: document.getElementById('nota_whatsapp').checked ? 1 : 0,
                tarefa_email: document.getElementById('tarefa_email').checked ? 1 : 0,
                tarefa_whatsapp: document.getElementById('tarefa_whatsapp').checked ? 1 : 0
            });

            const msgEl = document.getElementById('msgPreferencias');

            if (mostrarMensagem) {
                msgEl.innerHTML = `<div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Salvando preferencias...`;
            }

            fetch('<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/crm/salvar/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: dados
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'ok' && mostrarMensagem) {
                        let contador = 5;
                        const intervalo = setInterval(() => {
                            msgEl.textContent = `Preferencias salvas com sucesso! ${contador}`;
                            contador--;
                            if (contador < 0) {
                                clearInterval(intervalo);
                                msgEl.textContent = '';
                            }
                        }, 1000);
                    } else if (mostrarMensagem) {
                        msgEl.textContent = 'Erro ao salvar.';
                    }
                });
        }
    </script>
    <script>
        document.getElementById('novaNotaForm').addEventListener('submit', function(e) {
            e.preventDefault();
            tinymce.triggerSave();

            const form = e.target;
            const formData = new FormData(form);

            mostrarOverlay({
                mensagem: 'Salvando dados...',
                carregando: true
            });

            fetch('<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/crm/salvar/', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'ok') {
                        form.reset();
                        tinymce.get('textoNota').setContent('');
                        const idUsuario = formData.get('idUsuario');
                        atualizarHistoricoNotas(idUsuario);

                        mostrarOverlay({
                            mensagem: 'Nota salva com sucesso!<br>' + (data.mensagem || ''),
                            carregando: false,
                            onClose: () => esconderOverlay()
                        });
                    } else {
                        mostrarOverlay({
                            mensagem: 'Erro ao salvar a nota: ' + data.mensagem,
                            carregando: false,
                            onClose: () => esconderOverlay()
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao enviar nota:', error);
                    mostrarOverlay({
                        mensagem: 'Erro ao enviar nota. Verifique o console.',
                        carregando: false,
                        onClose: () => esconderOverlay()
                    });
                });
        });

        //CONTROLAR MENU SUSPENSO
        let alunoSelecionadoMenu = null;

        function abrirMenuContexto(e, aluno) {
            alunoSelecionadoMenu = aluno;

            const menu = document.getElementById('contextMenu');
            menu.style.top = `${e.pageY}px`;
            menu.style.left = `${e.pageX}px`;
            menu.style.display = 'block';
        }

        // Esconder o menu se clicar fora
        document.addEventListener('click', function() {
            document.getElementById('contextMenu').style.display = 'none';
        });

        // Clique nas opcoes do menu
        document.getElementById('menuVerMatricula').addEventListener('click', function() {
            const url = '<?php echo $BASE_para_URL; ?>/matriculas/turma/?turma=' + alunoSelecionadoMenu.IdTurma;
            window.open(url, '_blank');
        });

        document.getElementById('menuListarFaltas').addEventListener('click', function() {
            const url = '<?php echo $BASE_para_URL; ?>/chamada/faltas/?idAluno=' + alunoSelecionadoMenu.IdUsuario + '&NNomeCurso=' + alunoSelecionadoMenu.IdCurso + '&NNomeTurma=' + alunoSelecionadoMenu.IdTurma;
            window.open(url, '_blank');
        });

        document.getElementById('menuDadosAluno').addEventListener('click', function() {
            abrirPostNovaAba('<?php echo $BASE_para_URL; ?>/beneficiarios/cadastro', {
                IdUsuario: alunoSelecionadoMenu.IdUsuario
            });
        });

        // Funcao para abrir via POST
        function abrirPostNovaAba(url, params) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            form.target = '_blank';
            form.style.display = 'none';

            for (const key in params) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = params[key];
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    </script>

    <div id="overlayLoading" style="display: none;">
        <div class="overlay-bg"></div>
        <div class="overlay-content" id="overlayContent">
            <div class="spinner-border text-primary" role="status" id="overlaySpinner">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-2" id="overlayText">Salvando dados...</p>
            <button id="overlayBtnOk" class="btn btn-primary mt-3" style="display: none;">OK</button>
        </div>
    </div>
    <style>
        #contextMenu {
            width: 200px;
        }
    </style>



</body>


</html>












