<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_PATH = $runtime['base_path'];
$BASE_URL = $runtime['base_url'];
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}

if (!isset($BASE_PATH) || !isset($BASE_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: " . rtrim((string) ($BASE_URL ?? ''), '/') . "/login/?redirect=$redirect_url");
    exit();
}

require_once $BASE_PATH . '/api/legacy/checa-token.php';
$matricula = (isset($_GET['matricula']) && (int)$_GET['matricula'] === 1) ? 1 : null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php require_once $BASE_PATH . '/app/template/header.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <style>
        .img-cover { object-fit: cover; object-position: center; }
        .table { display: none; }
        .hover-container { display: inline-block; position: relative; }
        .hover-img { transition: transform 0.2s ease; }
        .hover-container:hover .hover-img { transform: scale(3.5); z-index: 10; position: absolute; left: 0; top: 0; box-shadow: 0 8px 18px rgba(0, 0, 0, 0.25); }
        .table-responsive { overflow: visible; }
        #minhaTabela { table-layout: fixed; width: 100%; }
        #matricularAluno { position: fixed; right: 10px; top: 100px; z-index: 100; border-radius: 50%; width: 80px; height: 80px; border: none; font-weight: bold; text-align: center; padding: 5px; display: flex; justify-content: center; align-items: center; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { color: #ffffff !important; background-color: rgb(7, 9, 12) !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background-color: #1a73e8 !important; border: 1px solid #1a73e8 !important; }
        table.dataTable thead { background-color: #222E3C; color: #ffffff; }
        table.dataTable tbody tr:nth-child(odd) { background-color: #f9f9f9; }
        table.dataTable tbody tr:nth-child(even) { background-color: #ffffff; }
        .dataTables_filter { margin-bottom: 1rem; }
        .dataTables_filter label { display: flex; align-items: center; }
        .dataTables_filter input { margin-left: 0.5rem; border: 2px solid #007BFF; }
        #overlayLoading { display: none; justify-content: center; align-items: center; }
    </style>
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/assets/css/overlayNotifica.css">
    <script src="<?php echo $BASE_URL; ?>/assets/js/overlayNotifica.js"></script>
</head>
<script src="//code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="<?php echo $BASE_URL; ?>/assets/js/jquery.dataTables.min.js"></script>
<body>
    <div id="overlayLoading">
        <div class="overlay-bg"></div>
        <div class="overlay-content" id="overlayContent">
            <div class="spinner-border text-primary" role="status" id="overlaySpinner">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-2" id="overlayText">Aguarde...</p>
            <button id="overlayBtnOk" class="btn btn-primary mt-3" style="display: none;">OK</button>
        </div>
    </div>
    <div class="wrapper">
        <?php require_once $BASE_PATH . '/app/template/menu.php'; ?>
        <div class="main">
            <?php require_once $BASE_PATH . '/app/template/topo.php'; ?>

            <main class="content">
                <?php if ($matricula === 1) { ?>
                    <button type="button" class="btn btn-success mb-3" id="matricularAluno">Matricular <br> Aluno</button>
                <?php } ?>

                <h4 class="h3 mb-3" id="titulo">Lista de Alunos</h4>
                <?php
                $msg = "";
                $url = $_SERVER['REQUEST_URI'];
                $urlDecoded = urldecode($url);
                if (strpos($urlDecoded, '=') !== false) {
                    $params = explode('&', $urlDecoded);
                    $tipomsg = explode('?', $params[0])[1] ?? '';
                    $tipomsg = explode('=', $tipomsg)[0] ?? '';
                    $msg = explode('=', $params[0])[1] ?? '';
                }

                if ($msg !== "") {
                    if ($tipomsg === "msg") {
                        echo '<div class="alert alert-success" role="alert">' . htmlspecialchars($msg) . '</div>';
                    } elseif ($tipomsg === "erro") {
                        echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($msg) . '</div>';
                    }
                }
                ?>
                <div class="col-12">
                    <div class="card shadow-lg">
                        <div class="card-body">
                            <a href="<?php echo $BASE_URL; ?>/beneficiarios/cadastro" class="btn btn-primary mb-3" id="novoAluno">Cadastrar novo aluno</a>
                            <div class="table-responsive mb-3">
                                <table id="minhaTabela" class="table table-striped table-hover datatable-custom table-layout-auto">
                                    <thead>
                                        <tr>
                                            <th class="text-center">*</th>
                                            <th class="text-center">Id</th>
                                            <th class="text-center">Foto</th>
                                            <th>Nome</th>
                                            <th>Turmas Ativas</th>
                                            <th>Hist?f?????T?f??s?,?rico de turmas</th>
                                            <th class="text-center">Nascimento</th>
                                            <th class="text-center">Telefone</th>
                                            <th class="text-center">WhatsApp</th>
                                            <th>Obs</th>
                                            <th class="text-center">A?f?????T?f??s?,??f?????T?f??s?,?es</th>
                                        </tr>
                                    </thead>
                                    <tbody id="beneficiariosTbody"></tbody>
                                </table>
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

    function normalizeSearch(data) {
        if (typeof data !== 'string') return data;
        return data.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/?f?????T?f??s?,?/g, 'c').toLowerCase();
    }

    function renderAcoes(item) {
        if ("<?php echo $_SESSION['Tipo'] ?? ''; ?>" === "Anulado") {
            return '<button type="button" class="btn btn-primary btn-disabled" disabled>Desabilitado</button>';
        }

        const cpfNumerico = String(item.CPF || '').replace(/\D/g, '');
        return `
            <div class="d-flex justify-content-center gap-2">
                <form method="GET" action="<?php echo $BASE_URL; ?>/beneficiarios/cadastro" style="display:inline;">
                    <input type="hidden" name="cpf" value="${escapeHtml(cpfNumerico)}">
                    <input type="hidden" name="id" value="${escapeHtml(item.IdUsuario)}">
                    <button type="submit" class="btn btn-warning">
                        <i class="fa-solid fa-pen-to-square" data-feather="edit-3"></i>
                    </button>
                </form>
                <form method="GET" action="<?php echo $BASE_URL; ?>/relatorios/ficha-cadastro-pdf/" target="_blank" style="display:inline;">
                    <input type="hidden" name="id" value="${escapeHtml(item.IdUsuario)}">
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-file-pdf"></i>
                    </button>
                </form>
                <form method="POST" action="<?php echo $BASE_URL; ?>/beneficiarios/cadastro/" style="display:inline;">
                    <input type="hidden" name="delete" value="${escapeHtml(item.IdUsuario)}">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir este benefici?f?????T?f??s?,?rio?');">
                        <i class="bi bi-trash-fill" data-feather="trash-2"></i>
                    </button>
                </form>
            </div>`;
    }

    function renderBeneficiarioRow(item) {
        let nome = item.Nome || '';
        if (item.Apelido) {
            nome = `(${item.Apelido}) ${nome}`;
        }
        return `<tr>
            <td class="text-center"><input type="checkbox" class="form-radio-input doacao" name="checkbox[]" value="${escapeHtml(item.IdUsuario)}"></td>
            <td class="text-center">${escapeHtml(item.IdUsuario)}</td>
            <td class="text-center">
                <span class="hover-container">
                    <img src="<?php echo $BASE_URL ?>/assets/img/fotos/${escapeHtml(item.Foto || 'padrao.jfif')}" class="rounded-circle img-cover hover-img" width="40" height="40" alt="">
                </span>
            </td>
            <td>${escapeHtml(nome)}</td>
            <td>${escapeHtml(item.TurmasAtivas || '')}</td>
            <td>${escapeHtml(item.HistoricoTurmas || '')}</td>
            <td class="text-center">${escapeHtml(item.Nascimento || '')}</td>
            <td class="text-center">${escapeHtml(item.Telefone || '')}</td>
            <td class="text-center">${escapeHtml(item.WhatsApp || '')}</td>
            <td>${escapeHtml(item.Obs || '')}</td>
            <td>${renderAcoes(item)}</td>
        </tr>`;
    }

    async function carregarBeneficiarios() {
        const response = await fetch(`${resolveApiBase()}/beneficiarios/resumo-ativos`, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Erro ao carregar benefici?f?????T?f??s?,?rios');
        }

        const tbody = document.getElementById('beneficiariosTbody');
        tbody.innerHTML = (result.data || []).map(renderBeneficiarioRow).join('');

        $.fn.dataTable.ext.type.search['locale-agnostic'] = function(data) {
            return normalizeSearch(data);
        };

        const table = $('#minhaTabela').DataTable({
            columnDefs: [{ targets: '_all', type: 'locale-agnostic' }],
            order: [[3, 'asc']]
        });

        $('#minhaTabela_filter input').on('input', function() {
            const normalizedInput = normalizeSearch($(this).val());
            $(this).val(normalizedInput);
            table.search(normalizedInput).draw();
        });

        if (window.feather) feather.replace();
        return result.data ? result.data.length : 0;
    }

    $(document).ready(async function() {
        const checkboxesSelecionados = {};

        mostrarOverlay({
            mensagem: 'Carregando benefici?f?????T?f??s?,?rios...',
            carregando: true
        });

        let total = 0;
        try {
            total = await carregarBeneficiarios();
        } catch (e) {
            console.error(e);
        } finally {
            const tabela = document.getElementById('minhaTabela');
            if (tabela) tabela.style.display = 'table';
            esconderOverlay();
        }

        const tituloElement = document.getElementById("titulo");
        if (tituloElement) {
            tituloElement.textContent = "Lista de Benefici?f?????T?f??s?,?rios: " + total;
        }

        setTimeout(() => {
            const alertElement = document.querySelector(".alert");
            if (alertElement) alertElement.style.display = "none";
        }, 2000);

        $('#minhaTabela').on('change', 'input[name="checkbox[]"]', function() {
            const id = $(this).val();
            if (this.checked) {
                checkboxesSelecionados[id] = true;
            } else {
                delete checkboxesSelecionados[id];
            }
        });

        $('#matricularAluno').click(function() {
            const selecionados = Object.keys(checkboxesSelecionados);
            if (selecionados.length > 0) {
                mostrarOverlay({ mensagem: 'Redirecionando para matr?f?????T?f??s?,?cula...', carregando: true });
                const form = $('<form>', {
                    action: "<?php echo $BASE_URL . '/matriculas/realizar/' ?>",
                    method: 'post'
                });

                $.each(selecionados, function(index, valor) {
                    $(form).append($('<input>', { type: 'hidden', name: 'checkbox[]', value: valor }));
                });

                $('body').append(form);
                form.submit();
            } else {
                alert('Selecione pelo menos um aluno para matricular.');
            }
        });
    });
</script>




