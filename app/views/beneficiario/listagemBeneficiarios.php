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
        .table-responsive { overflow-x: auto; overflow-y: visible; }
        .lista-beneficiarios-card { border-radius: 1rem; }
        .lista-beneficiarios-card .card-body { padding: 1.5rem; }
        #minhaTabela { table-layout: auto; width: 100%; min-width: 1680px; }
        #minhaTabela th,
        #minhaTabela td { white-space: nowrap; vertical-align: middle; }
        #minhaTabela td:nth-child(4),
        #minhaTabela td:nth-child(5),
        #minhaTabela td:nth-child(7),
        #minhaTabela td:nth-child(8),
        #minhaTabela td:nth-child(13),
        #minhaTabela td:nth-child(22) { white-space: normal; min-width: 180px; }
        .datatable-toolbar { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
        .datatable-toolbar .dataTables_filter { margin-left: auto; }
        #matricularAluno { position: fixed; right: 10px; top: 100px; z-index: 100; border-radius: 50%; width: 80px; height: 80px; border: none; font-weight: bold; text-align: center; padding: 5px; display: flex; justify-content: center; align-items: center; }
        #minhaTabela tbody tr[data-popover-content] { cursor: help; }
    </style>
    <link rel="stylesheet" href="<?php echo $BASE_para_URL; ?>/assets/css/overlayNotifica.css">
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/overlayNotifica.js"></script>
</head>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?php echo $BASE_para_URL; ?>/assets/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<body>
<div id="overlayLoading">
    <div class="overlay-bg"></div>
    <div class="overlay-content" id="overlayContent">
        <div class="spinner-border text-primary" role="status" id="overlaySpinner"><span class="visually-hidden">Carregando...</span></div>
        <p class="mt-2" id="overlayText">Aguarde...</p>
        <button id="overlayBtnOk" class="btn btn-primary mt-3" style="display:none;">OK</button>
    </div>
</div>

<div class="wrapper">
    <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>
    <div class="main">
        <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>

        <main class="content">
            <?php if ($matricula === 1): ?>
                <button type="button" class="btn btn-success mb-3" id="matricularAluno">Matricular <br> Aluno</button>
            <?php endif; ?>

            <h4 class="h3 mb-3" id="titulo">Lista de Beneficiários</h4>

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
                <div class="card shadow-lg lista-beneficiarios-card">
                    <div class="card-body">
                        <div class="datatable-toolbar">
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?php echo $BASE_para_URL; ?>/beneficiarios/cadastro" class="btn btn-primary" id="novoAluno">Cadastrar novo aluno</a>
                                <button id="btnExportExcel" class="btn btn-success"><i class="fa-solid fa-file-excel"></i> Exportar Excel</button>
                                <button id="btnIpaiAnamnese" class="btn btn-secondary"><i class="fa-solid fa-file-pdf"></i> IPAI/Anamnese</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="minhaTabela" class="table table-striped table-hover datatable-custom">
                                <thead>
                                <tr>
                                    <th>*</th><th>Id</th><th>Foto</th><th>Nome</th><th>Interesses</th><th>Paciente</th>
                                    <th>Turmas Ativas</th><th>Histórico de turmas</th><th>Nascimento</th><th>Idade</th><th>CPF</th>
                                    <th>Identidade</th><th>Endereço</th><th>Bairro</th><th>Cidade</th><th>Telefone</th>
                                    <th>WhatsApp</th><th>Responsável</th><th>Contato</th><th>PCD em casa</th><th>Deficiência(s)</th><th>Obs</th><th>Ações</th>
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
                                     WHERE m.IdUsuario = a.IdUsuario AND (c.Habilitado = 0 OR cu.Habilitado = 0)) AS HistoricoTurmas,
                                    (SELECT GROUP_CONCAT(p.NomeProjeto SEPARATOR '/ ') FROM tbInteresse i JOIN tbProjeto p ON p.IdProjeto = i.IdProjeto WHERE i.IdUsuario = a.IdUsuario AND i.Valor = 1) AS Interesses,
                                    u1.Nome AS NomeUserEnt,
                                    u2.Nome AS NomeUserAlt
                                    FROM tbAluno a
                                    LEFT JOIN tbUser u1 ON u1.IdColaborador = a.IdColaboradorEnt
                                    LEFT JOIN tbUser u2 ON u2.IdColaborador = a.IdColaboradorAlt
                                    WHERE a.Habilitado = 1
                                    ORDER BY a.Nome ASC";
                                $busca = $pdo->query($sql);
                                while ($dados = $busca->fetch(PDO::FETCH_ASSOC)) {
                                    $id = (int)$dados['IdUsuario'];
                                    $nome = (string)($dados['Nome'] ?: '');
                                    $apelido = trim((string)($dados['Apelido'] ?? ''));
                                    if ($apelido !== '') {
                                        $nome = '(' . $apelido . ') ' . $nome;
                                    }
                                    $nascimento = (string)($dados['Nascimento'] ?? '');
                                    $cpf = (string)($dados['CPF'] ?? '');
                                    $idade = '-';
                                    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $nascimento, $m)) {
                                        $dn = DateTime::createFromFormat('Y-m-d', $m[3] . '-' . $m[2] . '-' . $m[1]);
                                        if ($dn) {
                                            $idade = (string)$dn->diff(new DateTime('today'))->y . ' anos';
                                        }
                                    }
                                    $pcdTexto = ((string)($dados['PCDEmCasa'] ?? '') === '1') ? 'Sim' : 'Não';
                                    $paciente = ((int)($dados['Versatilis'] ?? 0) === 1)
                                        ? '<img src="' . $ASSETS_IMG_URL . '/logos/logo_sys.png" alt="Versatilis" width="140" height="30">'
                                        : 'Não';
                                    $obs = (string)($dados['Obs'] ?? '');
                                    $obsCurto = mb_strlen($obs) > 50 ? mb_substr($obs, 0, 50) . '...' : $obs;
                                    $def = (string)($dados['DeficienciasCasa'] ?? '');
                                    $defCurto = mb_strlen($def) > 40 ? mb_substr($def, 0, 40) . '...' : $def;
                                    $cpfNumerico = preg_replace('/\D/', '', $cpf ?? '');
                                    $nomeCriador = trim((string)($dados['NomeUserEnt'] ?? ''));
                                    $nomeAlteracao = trim((string)($dados['NomeUserAlt'] ?? ''));
                                    $dataCriacaoBr = '-';
                                    $dataAlteracaoBr = '-';

                                    $timeEntrada = trim((string)($dados['TimeEntrada'] ?? ''));
                                    if ($timeEntrada !== '') {
                                        $dtEntrada = date_create($timeEntrada);
                                        if ($dtEntrada instanceof DateTime) {
                                            $dataCriacaoBr = $dtEntrada->format('d/m/Y');
                                        }
                                    }

                                    $timeAlterado = trim((string)($dados['TimeAlterado'] ?? ''));
                                    if ($timeAlterado !== '') {
                                        $dtAlterado = date_create($timeAlterado);
                                        if ($dtAlterado instanceof DateTime) {
                                            $dataAlteracaoBr = $dtAlterado->format('d/m/Y');
                                        }
                                    }

                                    $textoCriador = $nomeCriador !== '' ? $nomeCriador : 'Não informado';
                                    $textoAlteracao = $nomeAlteracao !== '' ? $nomeAlteracao : 'Não informado';
                                    $popoverContent = "Criado por: {$textoCriador} em {$dataCriacaoBr}<br>Último ajuste: {$textoAlteracao} em {$dataAlteracaoBr}";
                                    $popoverContentAttr = htmlspecialchars($popoverContent, ENT_QUOTES, 'UTF-8');

                                    echo '<tr data-popover-content="' . $popoverContentAttr . '" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" data-bs-title="Histórico do registro">';
                                    echo '<td><input type="checkbox" class="form-radio-input doacao" name="checkbox[]" value="' . $id . '"></td>';
                                    echo '<td>' . $id . '</td>';
                                    echo '<td><span class="hover-container"><img src="' . htmlspecialchars($FOTO_PADRAO_URL) . '" data-foto-id="' . $id . '" class="rounded-circle img-cover hover-img beneficiario-foto js-foto-pendente" width="40" height="40" loading="lazy" decoding="async" alt="Foto de beneficiário"></span></td>';
                                    echo '<td><a href="' . $BASE_para_URL . '/beneficiarios/cadastro?cpf=' . urlencode((string)$cpfNumerico) . '&id=' . $id . '" style="text-decoration:none;color:inherit;">' . htmlspecialchars($nome) . '</a></td>';
                                    echo '<td>' . htmlspecialchars((string)($dados['Interesses'] ?? '')) . '</td>';
                                    echo '<td class="text-center">' . $paciente . '</td>';
                                    echo '<td>' . htmlspecialchars((string)($dados['TurmasAtivas'] ?? '')) . '</td>';
                                    echo '<td>' . htmlspecialchars((string)($dados['HistoricoTurmas'] ?? '')) . '</td>';
                                    echo '<td>' . htmlspecialchars($nascimento) . '</td>';
                                    echo '<td>' . htmlspecialchars($idade) . '</td>';
                                    echo '<td>' . htmlspecialchars($cpf) . '</td>';
                                    echo '<td>' . htmlspecialchars((string)($dados['Identidade'] ?? '')) . '</td>';
                                    echo '<td>' . htmlspecialchars((string)($dados['Endereco'] ?? '')) . '</td>';
                                    echo '<td>' . htmlspecialchars((string)($dados['Bairro'] ?? '')) . '</td>';
                                    echo '<td>' . htmlspecialchars((string)($dados['Cidade'] ?? '')) . '</td>';
                                    echo '<td>' . htmlspecialchars((string)($dados['Telefone'] ?? '')) . '</td>';
                                    echo '<td>' . htmlspecialchars((string)($dados['WhatsApp'] ?? '')) . '</td>';
                                    echo '<td>' . htmlspecialchars((string)($dados['NomeResp1'] ?? '')) . '</td>';
                                    echo '<td>' . htmlspecialchars((string)($dados['WhatsAppResp1'] ?? '')) . '</td>';
                                    echo '<td class="text-center">' . $pcdTexto . '</td>';
                                    echo '<td title="' . htmlspecialchars($def) . '">' . htmlspecialchars($defCurto) . '</td>';
                                    echo '<td title="' . htmlspecialchars($obs) . '">' . htmlspecialchars($obsCurto) . '</td>';
                                    echo '<td><div class="d-flex justify-content-center gap-2">';
                                    echo '<form method="GET" action="' . $BASE_para_URL . '/beneficiarios/cadastro" style="display:inline;"><input type="hidden" name="cpf" value="' . htmlspecialchars((string)$cpfNumerico) . '"><input type="hidden" name="id" value="' . $id . '"><button type="submit" class="btn btn-warning"><i class="fa-solid fa-pen-to-square" data-feather="edit-3"></i></button></form>';
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
            scrollX: true,
            autoWidth: false,
            drawCallback: function() {
                if (window.feather) feather.replace();
                inicializarPopoversRegistros();
                agendarCarregamentoFotos();
            }
        });
        $('.dataTables_filter input[type="search"]').attr('placeholder', 'Buscar beneficiário');
        if (window.feather) feather.replace();
        inicializarPopoversRegistros();
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

    function inicializarPopoversRegistros() {
        if (!window.bootstrap || !bootstrap.Popover) return;

        document.querySelectorAll('#minhaTabela tbody tr[data-popover-content]').forEach(function(linha) {
            const popoverExistente = bootstrap.Popover.getInstance(linha);
            if (popoverExistente) {
                popoverExistente.dispose();
            }

            new bootstrap.Popover(linha, {
                container: 'body',
                trigger: 'hover focus',
                placement: 'top',
                html: true,
                content: linha.getAttribute('data-popover-content') || ''
            });
        });
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

    function extrairTexto(html) {
        const div = document.createElement('div');
        div.innerHTML = html;
        return div.textContent.trim();
    }

    function extrairPaciente(html) {
        if (html.includes('logo_sys.png')) return 'Sim';
        return extrairTexto(html) || 'Não';
    }

    document.getElementById('btnExportExcel').addEventListener('click', function() {
        if (!table) return;
        const dadosFiltrados = table.rows({ search: 'applied' }).data();
        if (dadosFiltrados.length === 0) {
            alert('Nenhum dado encontrado com o filtro atual.');
            return;
        }

        const headers = ['ID','Nome','Interesses','Paciente','Turmas Ativas','Histórico de turmas','Nascimento','CPF','Endereço','Bairro','Cidade','Telefone','WhatsApp','Responsável','Contato','Obs'];
        const dadosFormatados = [];
        dadosFiltrados.each(function(row) {
            dadosFormatados.push([row[1], extrairTexto(row[3]), row[4], extrairPaciente(row[5]), row[6], row[7], row[8], row[10], row[12], row[13], row[14], row[15], row[16], row[17], row[18], row[21]]);
        });

        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet([headers, ...dadosFormatados]);
        XLSX.utils.book_append_sheet(wb, ws, 'Beneficiários');
        XLSX.writeFile(wb, 'listagemBeneficiarios_filtrado.xlsx');
    });

    document.getElementById('btnIpaiAnamnese').addEventListener('click', function() {
        const selecionados = Object.keys(checkboxesSelecionados);
        if (!selecionados.length) {
            alert('Selecione pelo menos um beneficiário para gerar o IPAI/Anamnese.');
            return;
        }

        const form = $('<form>', {
            action: '<?php echo $BASE_para_URL; ?>/relatorios/ipai-anamnese-pdf/',
            method: 'post',
            target: '_blank'
        });

        $.each(selecionados, function(_, valor) {
            $(form).append($('<input>', { type: 'hidden', name: 'checkbox[]', value: valor }));
        });

        $(form).appendTo('body').submit().remove();
    });
</script>
</body>
</html>


