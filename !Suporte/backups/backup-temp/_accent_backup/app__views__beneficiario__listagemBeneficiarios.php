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
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php require_once $BASE_PATH . '/app/template/header.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .img-cover { object-fit: cover; object-position: center; }
        .hover-container { display: inline-block; position: relative; }
        .hover-img { transition: transform 0.2s ease; }
        .hover-container:hover .hover-img { transform: scale(3.5); z-index: 10; position: absolute; left: 0; top: 0; box-shadow: 0 8px 18px rgba(0,0,0,.25); }
        .table { display: none; }
        .loader { display: none; }
        .table-responsive { overflow-x: auto; overflow-y: visible; }
        #minhaTabela { min-width: 1600px; }
        #matricularAluno { position: fixed; right: 10px; top: 100px; z-index: 100; border-radius: 50%; width: 80px; height: 80px; border: none; font-weight: bold; text-align: center; padding: 5px; display: flex; justify-content: center; align-items: center; }
    </style>
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/assets/css/overlayNotifica.css">
    <script src="<?php echo $BASE_URL; ?>/assets/js/overlayNotifica.js"></script>
</head>
<script src="//code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="<?php echo $BASE_URL; ?>/assets/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
                            <button id="btnExportExcel" class="btn btn-success mb-3"><i class="fa-solid fa-file-excel"></i> Exportar Excel</button>
                            <button type="button" class="btn btn-success mb-3" id="matricularAluno">Matricular <br> Aluno</button>

                            <div class="table-responsive">
                                <table id="minhaTabela" class="table table-striped table-hover datatable-custom">
                                    <thead>
                                        <tr>
                                            <th>*</th>
                                            <th>Id</th>
                                            <th>Foto</th>
                                            <th>Nome</th>
                                            <th>Interesses</th>
                                            <th>Paciente</th>
                                            <th>Turmas Ativas</th>
                                            <th>Hist?f?????T?f??s?,?rico de turmas</th>
                                            <th>Nascimento</th>
                                            <th>Idade</th>
                                            <th>CPF</th>
                                            <th>Identidade</th>
                                            <th>Endere?f?????T?f??s?,?o</th>
                                            <th>Bairro</th>
                                            <th>Cidade</th>
                                            <th>Telefone</th>
                                            <th>WhatsApp</th>
                                            <th>Respons?f?????T?f??s?,?vel</th>
                                            <th>Contato</th>
                                            <th>PCD em casa</th>
                                            <th>Defici?f?????T?f??s?,?ncia(s)</th>
                                            <th>Obs</th>
                                            <th>A?f?????T?f??s?,??f?????T?f??s?,?es</th>
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
    let table;
    const checkboxesSelecionados = {};

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

    function calcularIdade(nascimento) {
        if (!nascimento) return '-';
        const partes = String(nascimento).split('/');
        if (partes.length !== 3) return '-';
        const data = new Date(`${partes[2]}-${partes[1]}-${partes[0]}`);
        if (Number.isNaN(data.getTime())) return '-';
        const hoje = new Date();
        let idade = hoje.getFullYear() - data.getFullYear();
        const m = hoje.getMonth() - data.getMonth();
        if (m < 0 || (m === 0 && hoje.getDate() < data.getDate())) idade--;
        return `${idade} anos`;
    }

    function abrirFormularioEdicao(idUsuario, cpf) {
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = '<?php echo $BASE_URL; ?>/beneficiarios/cadastro';
        form.target = '_self';
        form.innerHTML = `<input type="hidden" name="id" value="${escapeHtml(idUsuario)}"><input type="hidden" name="cpf" value="${escapeHtml(cpf)}">`;
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
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
                    <button type="submit" class="btn btn-warning"><i class="fa-solid fa-pen-to-square" data-feather="edit-3"></i></button>
                </form>
                <form method="POST" action="<?php echo $BASE_URL; ?>/beneficiarios/cadastro/" style="display:inline;">
                    <input type="hidden" name="delete" value="${escapeHtml(item.IdUsuario)}">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir este benefici?f?????T?f??s?,?rio?');"><i class="bi bi-trash-fill" data-feather="trash-2"></i></button>
                </form>
            </div>`;
    }

    function renderRow(item) {
        const pcdTexto = (String(item.PCDEmCasa) === '1') ? 'Sim' : 'N?f?????T?f??s?,?o';
        const def = String(item.DeficienciasCasa || '');
        const defCurto = def.length > 40 ? def.slice(0, 40) + '...' : def;
        const obs = String(item.Obs || '');
        const obsCurto = obs.length > 50 ? obs.slice(0, 50) + '...' : obs;
        const nome = item.Nome || '';
        const paciente = Number(item.Versatilis || 0) === 1
            ? '<img src="<?php echo $BASE_URL; ?>/assets/img/logos/logo_sys.png" alt="Versatilis" width="140" height="30">'
            : 'N?f?????T?f??s?,?o';

        return `<tr>
            <td><input type="checkbox" class="form-radio-input doacao" name="checkbox[]" value="${escapeHtml(item.IdUsuario)}"></td>
            <td>${escapeHtml(item.IdUsuario)}</td>
            <td><span class="hover-container"><img src="<?php echo $BASE_URL; ?>/assets/img/fotos/${escapeHtml(item.Foto || 'padrao.jfif')}" class="rounded-circle img-cover hover-img" width="40" height="40"></span></td>
            <td><a href="#" onclick="abrirFormularioEdicao('${escapeHtml(item.IdUsuario)}','${escapeHtml(String(item.CPF || '').replace(/\\D/g,''))}'); return false;" style="text-decoration:none; color:inherit;">${escapeHtml(nome)}</a></td>
            <td title="${escapeHtml(item.Interesses || '')}">${escapeHtml(item.Interesses || '')}</td>
            <td class="text-center">${paciente}</td>
            <td>${escapeHtml(item.TurmasAtivas || '')}</td>
            <td>${escapeHtml(item.HistoricoTurmas || '')}</td>
            <td>${escapeHtml(item.Nascimento || '')}</td>
            <td>${escapeHtml(calcularIdade(item.Nascimento || ''))}</td>
            <td>${escapeHtml(item.CPF || '')}</td>
            <td>${escapeHtml(item.Identidade || '')}</td>
            <td>${escapeHtml(item.Endereco || '')}</td>
            <td>${escapeHtml(item.Bairro || '')}</td>
            <td>${escapeHtml(item.Cidade || '')}</td>
            <td>${escapeHtml(item.Telefone || '')}</td>
            <td>${escapeHtml(item.WhatsApp || '')}</td>
            <td>${escapeHtml(item.NomeResp1 || '')}</td>
            <td>${escapeHtml(item.WhatsAppResp1 || '')}</td>
            <td class="text-center">${pcdTexto}</td>
            <td title="${escapeHtml(def)}">${escapeHtml(defCurto || '-')}</td>
            <td title="${escapeHtml(obs)}">${escapeHtml(obsCurto)}</td>
            <td>${renderAcoes(item)}</td>
        </tr>`;
    }

    async function carregarDados() {
        const response = await fetch(`${resolveApiBase()}/beneficiarios/detalhado-ativos`, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Erro ao carregar benefici?f?????T?f??s?,?rios');

        const tbody = document.getElementById('beneficiariosTbody');
        tbody.innerHTML = (result.data || []).map(renderRow).join('');

        if ($.fn.DataTable.isDataTable('#minhaTabela')) $('#minhaTabela').DataTable().destroy();
        table = $('#minhaTabela').DataTable({ order: [[3, 'asc']] });
        if (window.feather) feather.replace();
        return result.data ? result.data.length : 0;
    }

    document.addEventListener('DOMContentLoaded', async function() {
        mostrarOverlay({ mensagem: 'Carregando benefici?f?????T?f??s?,?rios...', carregando: true });
        let total = 0;
        try {
            total = await carregarDados();
        } catch (error) {
            console.error(error);
        } finally {
            const tabela = document.getElementById('minhaTabela');
            if (tabela) tabela.style.display = 'table';
            esconderOverlay();
        }

        const tituloElement = document.getElementById('titulo');
        if (tituloElement) tituloElement.textContent = `Lista de Benefici?f?????T?f??s?,?rios: ${total}`;

        setTimeout(() => {
            const alertElement = document.querySelector('.alert');
            if (alertElement) alertElement.style.display = 'none';
        }, 2000);
    });

    $('#minhaTabela').on('change', 'input[name="checkbox[]"]', function() {
        const id = $(this).val();
        if (this.checked) checkboxesSelecionados[id] = true;
        else delete checkboxesSelecionados[id];
    });

    $('#matricularAluno').click(function() {
        const selecionados = Object.keys(checkboxesSelecionados);
        if (!selecionados.length) {
            alert('Selecione pelo menos um aluno para matricular.');
            return;
        }
        const form = $('<form>', { action: "<?php echo $BASE_URL; ?>/matriculas/realizar/", method: 'post' });
        $.each(selecionados, function(index, valor) {
            $(form).append($('<input>', { type: 'hidden', name: 'checkbox[]', value: valor }));
        });
        $(form).appendTo('body').submit();
    });

    function extrairTexto(html) {
        const div = document.createElement('div');
        div.innerHTML = html;
        return div.textContent.trim();
    }

    function extrairPaciente(html) {
        if (html.includes('logo_sys.png')) return 'Sim';
        return extrairTexto(html) || 'N?f?????T?f??s?,?o';
    }

    document.getElementById('btnExportExcel').addEventListener('click', function() {
        if (!table) return;
        const dadosFiltrados = table.rows({ search: 'applied' }).data();
        if (dadosFiltrados.length === 0) {
            alert('Nenhum dado encontrado com o filtro atual.');
            return;
        }

        const headers = [
            "ID", "Nome", "Interesses", "Paciente", "Turmas Ativas", "Hist?f?????T?f??s?,?rico de turmas", "Nascimento", "CPF",
            "Endere?f?????T?f??s?,?o", "Bairro", "Cidade", "Telefone", "WhatsApp", "Respons?f?????T?f??s?,?vel", "Contato", "Obs"
        ];

        const dadosFormatados = [];
        dadosFiltrados.each(function(row) {
            dadosFormatados.push([
                row[1], row[3], row[4], extrairPaciente(row[5]), row[6], row[7], row[8], row[10],
                row[12], row[13], row[14], row[15], row[16], row[17], row[18], row[21]
            ]);
        });

        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet([headers, ...dadosFormatados]);
        XLSX.utils.book_append_sheet(wb, ws, "Beneficiarios");
        XLSX.writeFile(wb, "listagemBeneficiarios_filtrado.xlsx");
    });
</script>




