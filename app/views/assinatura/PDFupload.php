<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$appJsVersion = @filemtime($BASE_para_PATH . '/app/assets/js/app.js') ?: time();
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header('Location: ' . rtrim((string) $BASE_para_URL, '/') . '/login/?redirect=' . $redirect_url);
    exit;
}

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

$pathBase = $BASE_para_PATH . '/app/storage/assinatura/';
$pathOriginais = $pathBase . 'originais/';

$msg = '';
$documentosEmitidos = [];
$opcoesPorPagina = [15, 50, 100];
$porPagina = isset($_GET['por_pagina']) ? (int) $_GET['por_pagina'] : 15;
if (!in_array($porPagina, $opcoesPorPagina, true)) {
    $porPagina = 15;
}
$paginaAtual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
if ($paginaAtual < 1) {
    $paginaAtual = 1;
}
$totalRegistros = 0;
$totalPaginas = 1;
$offset = 0;

try {
    $totalRegistros = (int) $pdo->query('SELECT COUNT(*) FROM tbpdf_assinado')->fetchColumn();
    $totalPaginas = max(1, (int) ceil($totalRegistros / $porPagina));
    if ($paginaAtual > $totalPaginas) {
        $paginaAtual = $totalPaginas;
    }
    $offset = ($paginaAtual - 1) * $porPagina;

    $sqlDocs = $pdo->prepare(
        'SELECT NomeDocumento, TimestampAssinatura, NomeArquivo, AssinaturaBase64, urlValidacao
           FROM tbpdf_assinado
       ORDER BY TimestampAssinatura DESC
          LIMIT :limite OFFSET :offset'
    );
    $sqlDocs->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $sqlDocs->bindValue(':offset', $offset, PDO::PARAM_INT);
    $sqlDocs->execute();
    $documentosEmitidos = $sqlDocs->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $documentosEmitidos = [];
    $totalRegistros = 0;
    $totalPaginas = 1;
    $paginaAtual = 1;
}

if (isset($_POST['enviar'])) {
    if (!isset($_FILES['pdf']) || !is_array($_FILES['pdf'])) {
        $msg = "<div class='alert alert-danger'>Arquivo não enviado.</div>";
    } else {
        $file = $_FILES['pdf'];

        $nomeDocumento = trim((string) ($_POST['nomeDocumento'] ?? ''));
        if ($nomeDocumento === '') {
            $msg = "<div class='alert alert-danger'>Informe o nome do documento.</div>";
        }

        if ($msg === '' && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === 0) {
            if (!is_dir($pathOriginais)) {
                @mkdir($pathOriginais, 0775, true);
            }

            $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                $msg = "<div class='alert alert-danger'>Envie apenas PDF.</div>";
            } else {
                $nomeTemp = 'pdf_' . time() . '_' . ($_SESSION['Cod'] ?? 'anon') . '.pdf';
                $pathPDF = $pathOriginais . $nomeTemp;

                if (move_uploaded_file((string) $file['tmp_name'], $pathPDF)) {
                    echo "
                        <form id='goPreview' method='POST' action='" . rtrim((string) $BASE_para_URL, '/') . "/assinatura/pdf/preview/'>
                            <input type='hidden' name='file' value='{$nomeTemp}'>
                            <input type='hidden' name='nomeDocumento' value='" . htmlspecialchars($nomeDocumento, ENT_QUOTES, 'UTF-8') . "'>
                        </form>

                        <script>
                            document.getElementById('goPreview').submit();
                        </script>
                    ";
                    exit;
                }
                $msg = "<div class='alert alert-danger'>Falha ao mover o arquivo enviado.</div>";
            }
        } elseif ($msg === '') {
            $msg = "<div class='alert alert-danger'>Falha no upload do arquivo.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <style>
        .historico-documentos {
            margin-top: 1.25rem;
        }

        .historico-documentos .card {
            border: 1px solid #e6e9ef;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
        }

        .historico-documentos .table {
            margin-bottom: 0;
            font-size: 0.92rem;
        }

        .historico-documentos .table thead th {
            white-space: nowrap;
            font-weight: 700;
            color: #2a3547;
        }

        .historico-documentos .link-acesso {
            white-space: nowrap;
        }

        .historico-documentos .historico-topo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 0.85rem;
        }

        .historico-documentos .historico-acoes {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .historico-documentos .historico-busca {
            min-width: 240px;
        }

        .historico-documentos .historico-contador {
            font-size: 0.88rem;
            color: #51607a;
            margin: 0;
        }

        .historico-documentos .paginacao {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            margin-top: 0.9rem;
            flex-wrap: wrap;
        }

        .historico-documentos .paginacao-info {
            font-size: 0.88rem;
            color: #51607a;
            margin: 0;
        }

        .historico-documentos .paginacao-links {
            display: inline-flex;
            gap: 0.35rem;
            flex-wrap: wrap;
        }

        @media (max-width: 767.98px) {
            .historico-documentos .table thead {
                display: none;
            }

            .historico-documentos .table,
            .historico-documentos .table tbody,
            .historico-documentos .table tr,
            .historico-documentos .table td {
                display: block;
                width: 100%;
            }

            .historico-documentos .table tr {
                border: 1px solid #e6e9ef;
                border-radius: 10px;
                margin-bottom: 0.75rem;
                padding: 0.45rem 0.25rem;
                background: #fff;
            }

            .historico-documentos .table td {
                border: 0;
                padding: 0.35rem 0.6rem;
                text-align: left;
                word-break: break-word;
            }

            .historico-documentos .table td::before {
                content: attr(data-label);
                display: block;
                font-size: 0.74rem;
                text-transform: uppercase;
                letter-spacing: 0.02em;
                color: #6c7a93;
                margin-bottom: 0.12rem;
                font-weight: 600;
            }

            .historico-documentos .historico-topo,
            .historico-documentos .paginacao {
                flex-direction: column;
                align-items: stretch;
            }

            .historico-documentos .historico-acoes,
            .historico-documentos .historico-busca {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
            <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>

            <main class="content">
                <div class="container-fluid p-0">
                    <div class="container mt-4">
                        <h2>Enviar documento PDF</h2>
                        <p>Selecione o PDF para iniciar o processo de assinatura digital.</p>

                        <?= $msg ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group row mb-3">
                                <label>Arquivo PDF:</label>
                                <input type="file" name="pdf" class="form-control" accept="application/pdf" required>
                            </div>

                            <div class="form-group row mb-3">
                                <label>Nome do documento:</label>
                                <input type="text" name="nomeDocumento" class="form-control" placeholder="Ex: Ofício XYZ" required>
                            </div>

                            <button type="submit" name="enviar" class="btn btn-success">
                                Próximo
                            </button>
                        </form>

                        <section class="historico-documentos">
                            <div class="card mt-4">
                                <div class="card-body">
                                    <?php
                                    $urlBaseLista = rtrim((string) $BASE_para_URL, '/') . '/assinatura/pdf';
                                    $montarUrlLista = function (int $pagina, int $porPaginaParam) use ($urlBaseLista): string {
                                        return $urlBaseLista . '?pagina=' . $pagina . '&por_pagina=' . $porPaginaParam;
                                    };
                                    $inicioRegistro = $totalRegistros > 0 ? ($offset + 1) : 0;
                                    $fimRegistro = $totalRegistros > 0 ? min($offset + $porPagina, $totalRegistros) : 0;
                                    ?>

                                    <div class="historico-topo">
                                        <h5 class="card-title mb-0">Documentos emitidos</h5>

                                        <div class="historico-acoes">
                                            <input
                                                type="search"
                                                id="filtro_documentos_emitidos"
                                                class="form-control form-control-sm historico-busca"
                                                placeholder="Pesquisar documento, arquivo ou código"
                                                autocomplete="off">

                                            <form method="GET" action="<?= htmlspecialchars($urlBaseLista, ENT_QUOTES, 'UTF-8') ?>" class="d-flex align-items-center gap-2">
                                                <label for="por_pagina" class="mb-0">Exibindo</label>
                                                <select id="por_pagina" name="por_pagina" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    <?php foreach ($opcoesPorPagina as $opcao) { ?>
                                                        <option value="<?= $opcao ?>" <?= $porPagina === $opcao ? 'selected' : '' ?>><?= $opcao ?> registros</option>
                                                    <?php } ?>
                                                </select>
                                                <input type="hidden" name="pagina" value="1">
                                            </form>
                                        </div>
                                    </div>

                                    <p class="historico-contador">
                                        Exibindo <?= htmlspecialchars((string) $inicioRegistro, ENT_QUOTES, 'UTF-8') ?> a <?= htmlspecialchars((string) $fimRegistro, ENT_QUOTES, 'UTF-8') ?> de <?= htmlspecialchars((string) $totalRegistros, ENT_QUOTES, 'UTF-8') ?> registro(s).
                                    </p>

                                    <?php if (empty($documentosEmitidos)) { ?>
                                        <div class="alert alert-light border mb-0">Nenhum documento emitido até o momento.</div>
                                    <?php } else { ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover align-middle" id="tabela_documentos_emitidos">
                                                <thead>
                                                    <tr>
                                                        <th>Nome do documento</th>
                                                        <th>Data e hora da emissão</th>
                                                        <th>Nome do arquivo</th>
                                                        <th>Código de validação</th>
                                                        <th>Link para acesso</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($documentosEmitidos as $doc) {
                                                        $timestamp = (int) ($doc['TimestampAssinatura'] ?? 0);
                                                        $dataHora = $timestamp > 0 ? date('d/m/Y H:i:s', $timestamp) : '-';

                                                        $nomeDoc = trim((string) ($doc['NomeDocumento'] ?? ''));
                                                        if ($nomeDoc === '') {
                                                            $nomeDoc = '(Sem nome)';
                                                        }

                                                        $nomeArquivo = (string) ($doc['NomeArquivo'] ?? '');
                                                        $codigo = (string) ($doc['AssinaturaBase64'] ?? '');
                                                        $urlAcesso = rtrim((string) $BASE_para_URL, '/') . '/app/storage/assinatura/assinados/' . rawurlencode($nomeArquivo);
                                                        $pathArquivo = $BASE_para_PATH . '/app/storage/assinatura/assinados/' . $nomeArquivo;
                                                        $arquivoExiste = $nomeArquivo !== '' && file_exists($pathArquivo);
                                                        $urlValidacao = (string) ($doc['urlValidacao'] ?? '');
                                                    ?>
                                                        <tr>
                                                            <td data-label="Nome do documento"><?= htmlspecialchars($nomeDoc, ENT_QUOTES, 'UTF-8') ?></td>
                                                            <td data-label="Data e hora da emissão"><?= htmlspecialchars($dataHora, ENT_QUOTES, 'UTF-8') ?></td>
                                                            <td data-label="Nome do arquivo"><?= htmlspecialchars($nomeArquivo !== '' ? $nomeArquivo : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                            <td data-label="Código de validação"><?= htmlspecialchars($codigo !== '' ? $codigo : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                            <td data-label="Link para acesso" class="link-acesso">
                                                                <?php if ($arquivoExiste) { ?>
                                                                    <a href="<?= htmlspecialchars($urlAcesso, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Abrir PDF</a>
                                                                <?php } elseif ($urlValidacao !== '') { ?>
                                                                    <a href="<?= htmlspecialchars($urlValidacao, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Validar documento</a>
                                                                <?php } else { ?>
                                                                    <span>-</span>
                                                                <?php } ?>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="paginacao">
                                            <p class="paginacao-info mb-0">Página <?= htmlspecialchars((string) $paginaAtual, ENT_QUOTES, 'UTF-8') ?> de <?= htmlspecialchars((string) $totalPaginas, ENT_QUOTES, 'UTF-8') ?></p>
                                            <div class="paginacao-links">
                                                <?php if ($paginaAtual > 1) { ?>
                                                    <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($montarUrlLista($paginaAtual - 1, $porPagina), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
                                                <?php } else { ?>
                                                    <span class="btn btn-sm btn-outline-secondary disabled">Anterior</span>
                                                <?php } ?>

                                                <?php
                                                $inicioJanela = max(1, $paginaAtual - 2);
                                                $fimJanela = min($totalPaginas, $paginaAtual + 2);
                                                for ($p = $inicioJanela; $p <= $fimJanela; $p++) {
                                                    $classeBotao = ($p === $paginaAtual) ? 'btn-primary' : 'btn-outline-primary';
                                                ?>
                                                    <a class="btn btn-sm <?= $classeBotao ?>" href="<?= htmlspecialchars($montarUrlLista($p, $porPagina), ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a>
                                                <?php } ?>

                                                <?php if ($paginaAtual < $totalPaginas) { ?>
                                                    <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($montarUrlLista($paginaAtual + 1, $porPagina), ENT_QUOTES, 'UTF-8') ?>">Próxima</a>
                                                <?php } else { ?>
                                                    <span class="btn btn-sm btn-outline-secondary disabled">Próxima</span>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </main>

            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>
</body>

</html>
<script>
    (function() {
        const inputFiltro = document.getElementById('filtro_documentos_emitidos');
        const tabela = document.getElementById('tabela_documentos_emitidos');

        if (!inputFiltro || !tabela) {
            return;
        }

        const linhas = Array.from(tabela.querySelectorAll('tbody tr'));

        function normalizarTexto(valor) {
            return String(valor ?? '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[çÇ]/g, 'c')
                .toLowerCase();
        }

        linhas.forEach((linha) => {
            linha.dataset.searchText = normalizarTexto(linha.innerText);
        });

        inputFiltro.addEventListener('input', () => {
            const termo = normalizarTexto(inputFiltro.value.trim());

            linhas.forEach((linha) => {
                const corresponde = termo === '' || (linha.dataset.searchText || '').includes(termo);
                linha.style.display = corresponde ? '' : 'none';
            });
        });
    })();
</script>
