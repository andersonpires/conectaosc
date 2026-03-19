<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$ASSETS_IMG_URL = rtrim((string)($runtime['assets_img_url'] ?? (rtrim((string)$BASE_para_URL, '/') . '/assets/img')), '/');
$FOTO_PADRAO_URL = $ASSETS_IMG_URL . '/fotos/padrao.jpg';
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI'] ?? '/');
    header('Location: ' . rtrim((string)($BASE_para_URL ?? ''), '/') . '/login/?redirect=' . $redirect_url);
    exit();
}

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
$matricula = (isset($_GET['matricula']) && (int)$_GET['matricula'] === 1) ? 1 : 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <style>
        .img-cover { object-fit: cover; object-position: center; }
        .hover-container { display: inline-block; position: relative; }
        .hover-img { transition: transform .2s ease; }
        .hover-container:hover .hover-img { transform: scale(3.5); z-index: 10; position: absolute; left: 0; top: 0; box-shadow: 0 8px 18px rgba(0,0,0,.25); }
        #matricularAluno { position: fixed; right: 10px; top: 100px; z-index: 100; border-radius: 50%; width: 80px; height: 80px; border: none; font-weight: bold; text-align: center; padding: 5px; display: flex; justify-content: center; align-items: center; }
        .table-responsive {
            overflow-x: auto;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            touch-action: pan-x pan-y;
            max-width: 100%;
        }
        .table-responsive .datatable-custom {
            min-width: 1100px;
        }
        .datatable-toolbar { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
        .datatable-toolbar .dataTables_filter { margin-left: auto; }
        @media (max-width: 767.98px) {
            .card-body {
                padding: 1rem .75rem;
            }
            .table-responsive {
                border: 1px solid rgba(0, 0, 0, .08);
                border-radius: .5rem;
            }
        }
    </style>
    <link rel="stylesheet" href="<?php echo $BASE_para_URL; ?>/assets/css/overlayNotifica.css">
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/overlayNotifica.js"></script>
</head>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?php echo $BASE_para_URL; ?>/assets/js/jquery.dataTables.min.js"></script>
<body>
<div class="wrapper">
    <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>
    <div class="main">
        <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>

        <main class="content">
            <?php if ($matricula === 1): ?>
                <button type="button" class="btn btn-success mb-3" id="matricularAluno">Matricular <br> Aluno</button>
            <?php endif; ?>

            <h4 class="h3 mb-3">Lista de Alunos</h4>

            <?php
            $msg = '';
            $tipomsg = '';
            if (!empty($_GET['msg'])) {
                $msg = (string)$_GET['msg'];
                $tipomsg = 'msg';
            } elseif (!empty($_GET['erro'])) {
                $msg = (string)$_GET['erro'];
                $tipomsg = 'erro';
            }
            if ($msg !== '') {
                if ($tipomsg === 'msg') {
                    echo '<div class="alert alert-success" role="alert">' . htmlspecialchars($msg) . '</div>';
                } else {
                    echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($msg) . '</div>';
                }
            }
            ?>

            <div class="col-12">
                <div class="card shadow-lg">
                    <div class="card-body">
                        <div class="datatable-toolbar">
                            <a href="<?php echo $BASE_para_URL; ?>/beneficiarios/cadastro" class="btn btn-primary" id="novoAluno">Cadastrar novo aluno</a>
                        </div>
                        <div class="table-responsive mb-3">
                            <table id="minhaTabela" class="table table-striped table-hover datatable-custom table-layout-auto">
                                <thead>
                                <tr>
                                    <th class="text-center">*</th>
                                    <th class="text-center">Id</th>
                                    <th class="text-center">Foto</th>
                                    <th>Nome</th>
                                    <th>Turmas Ativas</th>
                                    <th>Histórico de turmas</th>
                                    <th class="text-center">Nascimento</th>
                                    <th class="text-center">Telefone</th>
                                    <th class="text-center">WhatsApp</th>
                                    <th>Obs</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $sql = "SELECT a.*,
                                    (SELECT GROUP_CONCAT(DISTINCT CASE WHEN m.Habilitado = 1 THEN c.NomeTurma ELSE CONCAT(c.NomeTurma, ' (Desmatriculado)') END ORDER BY c.NomeTurma SEPARATOR '; ')
                                     FROM tbMatricula m
                                     JOIN tbTurma c ON m.IdTurma = c.IdTurma
                                     JOIN tbCurso cu ON cu.IdCurso = m.IdCurso
                                     WHERE m.IdUsuario = a.IdUsuario AND c.Habilitado = 1 AND cu.Habilitado = 1) AS TurmasAtivas,
                                    (SELECT GROUP_CONCAT(DISTINCT c.NomeTurma ORDER BY c.NomeTurma SEPARATOR '; ')
                                     FROM tbMatricula m
                                     JOIN tbTurma c ON m.IdTurma = c.IdTurma
                                     JOIN tbCurso cu ON cu.IdCurso = m.IdCurso
                                     WHERE m.IdUsuario = a.IdUsuario AND (c.Habilitado = 0 OR cu.Habilitado = 0)) AS HistoricoTurmas
                                    FROM tbAluno a
                                    WHERE a.Habilitado = 1
                                    ORDER BY a.Nome ASC";
                                $busca = $pdo->query($sql);
                                while ($dados = $busca->fetch(PDO::FETCH_ASSOC)) {
                                    $id = (int)$dados['IdUsuario'];
                                    $nome = (string)($dados['Nome'] ?? '');
                                    $apelido = trim((string)($dados['Apelido'] ?? ''));
                                    if ($apelido !== '') $nome = '(' . $apelido . ') ' . $nome;
                                    $cpf = preg_replace('/\D/', '', (string)($dados['CPF'] ?? ''));
                                    echo '<tr>';
                                    echo '<td class="text-center"><input type="checkbox" class="form-radio-input doacao" name="checkbox[]" value="' . $id . '"></td>';
                                    echo '<td class="text-center">' . $id . '</td>';
                                    echo '<td class="text-center"><span class="hover-container"><img src="' . htmlspecialchars($FOTO_PADRAO_URL) . '" data-foto-id="' . $id . '" class="rounded-circle img-cover hover-img beneficiario-foto js-foto-pendente" width="40" height="40" loading="lazy" decoding="async" alt="Foto de beneficiário"></span></td>';
                                    echo '<td>' . htmlspecialchars($nome) . '</td>';
                                    echo '<td>' . htmlspecialchars((string)($dados['TurmasAtivas'] ?? '')) . '</td>';
                                    echo '<td>' . htmlspecialchars((string)($dados['HistoricoTurmas'] ?? '')) . '</td>';
                                    echo '<td class="text-center">' . htmlspecialchars((string)($dados['Nascimento'] ?? '')) . '</td>';
                                    echo '<td class="text-center">' . htmlspecialchars((string)($dados['Telefone'] ?? '')) . '</td>';
                                    echo '<td class="text-center">' . htmlspecialchars((string)($dados['WhatsApp'] ?? '')) . '</td>';
                                    echo '<td>' . htmlspecialchars((string)($dados['Obs'] ?? '')) . '</td>';
                                    echo '<td><div class="d-flex justify-content-center gap-2">';
                                    echo '<form method="GET" action="' . $BASE_para_URL . '/beneficiarios/cadastro" style="display:inline;"><input type="hidden" name="cpf" value="' . htmlspecialchars((string)$cpf) . '"><input type="hidden" name="id" value="' . $id . '"><button type="submit" class="btn btn-warning"><i class="fa-solid fa-pen-to-square" data-feather="edit-3"></i></button></form>';
                                    echo '<form method="GET" action="' . $BASE_para_URL . '/relatorios/ficha-cadastro-pdf/" target="_blank" style="display:inline;"><input type="hidden" name="id" value="' . $id . '"><button type="submit" class="btn btn-success" title="Gerar PDF"><i data-feather="file-text"></i></button></form>';
                                    echo '<form method="POST" action="' . $BASE_para_URL . '/beneficiarios/cadastro/" style="display:inline;"><input type="hidden" name="delete" value="' . $id . '"><button type="submit" class="btn btn-danger" onclick="return confirm(\'Tem certeza que deseja excluir este beneficiário?\');"><i class="bi bi-trash-fill" data-feather="trash-2"></i></button></form>';
                                    echo '</div></td>';
                                    echo '</tr>';
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="footer"><?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?></footer>
    </div>
</div>
<script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>
<script>
    let table;
    const checkboxesSelecionados = {};
    const fotosCarregadas = new Set();
    const fotosEmCarregamento = new Set();
    const fotosEndpoint = '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/beneficiarios/fotos';
    const fotoPadraoUrl = '<?php echo htmlspecialchars($FOTO_PADRAO_URL, ENT_QUOTES, 'UTF-8'); ?>';
    let timerCarregarFotos = null;
    function normalizarBusca(valor) {
        return (valor || '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/ç/g, 'c')
            .replace(/Ç/g, 'C')
            .toLowerCase();
    }

    $(document).ready(function() {
        if ($.fn.dataTable && $.fn.dataTable.ext && $.fn.dataTable.ext.type && $.fn.dataTable.ext.type.search) {
            $.fn.dataTable.ext.type.search.string = function(data) {
                return normalizarBusca($('<div>').html(data).text());
            };
            $.fn.dataTable.ext.type.search.html = function(data) {
                return normalizarBusca($('<div>').html(data).text());
            };
        }

        table = $('#minhaTabela').DataTable({
            order: [[3, 'asc']],
            drawCallback: function() {
                if (window.feather) feather.replace();
                agendarCarregamentoFotos();
            }
        });
        $('.dataTables_filter input[type="search"]').attr('placeholder', 'Buscar beneficiário');
        if (window.feather) feather.replace();
        agendarCarregamentoFotos();

        $('#minhaTabela').on('change', 'input[name="checkbox[]"]', function() {
            const id = $(this).val();
            if (this.checked) checkboxesSelecionados[id] = true;
            else delete checkboxesSelecionados[id];
        });

        $('#matricularAluno').on('click', function() {
            const selecionados = Object.keys(checkboxesSelecionados);
            if (!selecionados.length) {
                alert('Selecione pelo menos um aluno para matricular.');
                return;
            }
            const form = $('<form>', { action: '<?php echo $BASE_para_URL; ?>/matriculas/realizar/', method: 'post' });
            $.each(selecionados, function(_, valor) {
                $(form).append($('<input>', { type: 'hidden', name: 'checkbox[]', value: valor }));
            });
            $(form).appendTo('body').submit();
        });
    });

    function agendarCarregamentoFotos() {
        if (timerCarregarFotos) clearTimeout(timerCarregarFotos);
        timerCarregarFotos = setTimeout(carregarFotosDaPaginaAtual, 80);
    }

    async function carregarFotosDaPaginaAtual() {
        if (!table) return;

        const ids = [];
        const linhas = table.rows({ page: 'current', search: 'applied' }).nodes().toArray();

        linhas.forEach(function(linha) {
            const img = linha.querySelector('img.beneficiario-foto[data-foto-id]');
            if (!img) return;
            const id = (img.getAttribute('data-foto-id') || '').trim();
            if (!id || fotosCarregadas.has(id) || fotosEmCarregamento.has(id)) return;
            fotosEmCarregamento.add(id);
            ids.push(id);
        });

        if (!ids.length) return;

        try {
            const response = await fetch(fotosEndpoint + '?ids=' + encodeURIComponent(ids.join(',')), {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) throw new Error('Falha ao carregar fotos');

            const payload = await response.json();
            const fotos = (payload && typeof payload === 'object' && payload.fotos && typeof payload.fotos === 'object')
                ? payload.fotos
                : {};

            ids.forEach(function(id) {
                const novaSrc = (typeof fotos[id] === 'string' && fotos[id].trim() !== '') ? fotos[id] : fotoPadraoUrl;
                document.querySelectorAll('img.beneficiario-foto[data-foto-id="' + id + '"]').forEach(function(img) {
                    if (img.getAttribute('src') !== novaSrc) {
                        img.setAttribute('src', novaSrc);
                    }
                    img.classList.remove('js-foto-pendente');
                });
                fotosCarregadas.add(id);
                fotosEmCarregamento.delete(id);
            });
        } catch (error) {
            ids.forEach(function(id) {
                fotosEmCarregamento.delete(id);
            });
        }
    }
</script>
</body>
</html>


