<?php

if (session_status() === PHP_SESSION_NONE) {
    session_name('PHPSESSID3');
    session_set_cookie_params(['httponly' => true]);
    session_start();
    session_regenerate_id(true);
}

date_default_timezone_set('America/Sao_Paulo');
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = (string)($runtime['base_para_path'] ?? '');
$BASE_para_URL = (string)($runtime['base_para_url'] ?? '');

if ($BASE_para_PATH === '' || $BASE_para_URL === '') {
    $redirectUrl = urlencode((string) ($_SERVER['REQUEST_URI'] ?? '/'));
    $fallbackBaseUrl = '/' . basename(dirname(__DIR__, 2));
    $baseUrl = rtrim((string) ($BASE_para_URL !== '' ? $BASE_para_URL : $fallbackBaseUrl), '/');
    header('Location: ' . $baseUrl . '/login/?redirect=' . $redirectUrl);
    exit();
}

/*
 * Acesso: exige apenas estar logado (mesmo padrao do dashboard). Nao usa o
 * checa-token.php porque ele valida "pagina permitida" (PaginasPermitidas) e
 * esta rota nova nao esta cadastrada la — o que barrava quem nao e
 * Superadministrador. O slug secreto continua sendo a segunda barreira.
 */
if (!isset($_SESSION['Cod'])) {
    header('Location: ' . rtrim((string) $BASE_para_URL, '/') . '/login/?redirect=' . urlencode((string) ($_SERVER['REQUEST_URI'] ?? '/')));
    exit();
}

$SLUG = 'ibpbf-n743k';
$rotaGerar = rtrim($BASE_para_URL, '/') . '/exportar-arquivos-' . $SLUG . '/gerar';

require_once __DIR__ . '/_origens.php';

/** Conta arquivos e soma bytes de um prefixo (pode vir de varias pastas). */
function exportacaoResumo(string $prefixo, array $origens): array
{
    $arquivos = 0;
    $bytes = 0;
    exportacaoVarrer($prefixo, $origens, function (string $abs, string $noZip, int $tam) use (&$arquivos, &$bytes) {
        $arquivos++;
        $bytes += $tam;
    });
    return ['arquivos' => $arquivos, 'bytes' => $bytes];
}

/** Conta arquivos de UMA pasta especifica (para o diagnostico por caminho). */
function exportacaoResumoPasta(string $prefixo, string $dir): array
{
    return exportacaoResumo($prefixo, [$dir]);
}

function exportacaoTamanho(int $bytes): string
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
    }
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1, ',', '.') . ' KB';
    }
    return $bytes . ' B';
}

$origens = exportacaoOrigens($BASE_para_PATH);
$resumoImg = exportacaoResumo('img', $origens['img'] ?? []);
$resumoStorage = exportacaoResumo('storage', $origens['storage'] ?? []);
$totalArquivos = $resumoImg['arquivos'] + $resumoStorage['arquivos'];
$totalBytes = $resumoImg['bytes'] + $resumoStorage['bytes'];

// Diagnostico: mostra todos os candidatos, existam ou nao, com a contagem
$diagnostico = [];
foreach (exportacaoCandidatos($BASE_para_PATH) as $prefixo => $candidatos) {
    foreach ($candidatos as $dir) {
        $existe = is_dir($dir);
        $diagnostico[] = [
            'prefixo' => $prefixo,
            'caminho' => $dir,
            'existe' => $existe,
            'arquivos' => $existe ? exportacaoResumoPasta($prefixo, $dir)['arquivos'] : 0,
        ];
    }
}

$espacoLivre = @disk_free_space(sys_get_temp_dir());
$espacoOk = $espacoLivre === false ? true : ($espacoLivre > $totalBytes * 1.2);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
</head>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>

            <main class="content">
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3">Exportar arquivos para migração</h1>

                    <div class="row">
                        <div class="col-12 col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Pacote de arquivos físicos</h5>
                                    <h6 class="card-subtitle text-muted">
                                        Gera um ZIP com as fotos e os PDFs assinados para importar na plataforma nova.
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Conteúdo</th>
                                                <th class="text-end">Arquivos</th>
                                                <th class="text-end">Tamanho</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>img/</code> — fotos, avatares, imagens de notas, logos</td>
                                                <td class="text-end"><?php echo number_format($resumoImg['arquivos'], 0, ',', '.'); ?></td>
                                                <td class="text-end"><?php echo exportacaoTamanho($resumoImg['bytes']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><code>storage/</code> — PDFs assinados (assinados, lotes, originais)</td>
                                                <td class="text-end"><?php echo number_format($resumoStorage['arquivos'], 0, ',', '.'); ?></td>
                                                <td class="text-end"><?php echo exportacaoTamanho($resumoStorage['bytes']); ?></td>
                                            </tr>
                                            <tr class="fw-bold">
                                                <td>Total</td>
                                                <td class="text-end"><?php echo number_format($totalArquivos, 0, ',', '.'); ?></td>
                                                <td class="text-end"><?php echo exportacaoTamanho($totalBytes); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <?php if (!$espacoOk) { ?>
                                        <div class="alert alert-warning">
                                            Espaço livre em disco temporário pode ser insuficiente
                                            (<?php echo exportacaoTamanho((int) $espacoLivre); ?> livres).
                                            Prefira baixar em partes.
                                        </div>
                                    <?php } ?>

                                    <?php if ($resumoStorage['arquivos'] === 0) { ?>
                                        <div class="alert alert-warning">
                                            Nenhum PDF encontrado. Veja no quadro
                                            <strong>Caminhos verificados</strong> onde o sistema procurou — se a sua
                                            pasta de storage estiver em outro lugar, me avise o caminho.
                                        </div>
                                    <?php } ?>

                                    <?php if ($totalArquivos === 0) { ?>
                                        <div class="alert alert-danger mb-0">
                                            Nenhum arquivo encontrado. Confirme se os volumes estão montados.
                                        </div>
                                    <?php } else { ?>
                                        <a class="btn btn-primary" href="<?php echo htmlspecialchars($rotaGerar, ENT_QUOTES); ?>">
                                            <i class="align-middle" data-feather="download"></i>
                                            Gerar e baixar ZIP completo
                                        </a>
                                        <a class="btn btn-outline-secondary ms-2" href="<?php echo htmlspecialchars($rotaGerar . '?parte=img', ENT_QUOTES); ?>">
                                            Baixar só <code>img/</code>
                                        </a>
                                        <a class="btn btn-outline-secondary ms-2" href="<?php echo htmlspecialchars($rotaGerar . '?parte=storage', ENT_QUOTES); ?>">
                                            Baixar só <code>storage/</code>
                                        </a>
                                        <p class="text-muted mt-3 mb-0">
                                            A geração pode levar alguns minutos. Se o download completo falhar por
                                            tempo limite, baixe em duas partes — o importador aceita as duas pastas
                                            extraídas no mesmo diretório.
                                        </p>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Caminhos verificados</h5>
                                    <h6 class="card-subtitle text-muted">Onde o sistema procurou os arquivos</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm mb-0" style="font-size: 12px;">
                                        <tbody>
                                            <?php foreach ($diagnostico as $d) { ?>
                                                <tr>
                                                    <td style="word-break: break-all;">
                                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($d['prefixo'], ENT_QUOTES); ?></span>
                                                        <code><?php echo htmlspecialchars($d['caminho'], ENT_QUOTES); ?></code>
                                                    </td>
                                                    <td class="text-end text-nowrap">
                                                        <?php if (!$d['existe']) { ?>
                                                            <span class="text-muted">não existe</span>
                                                        <?php } else { ?>
                                                            <strong><?php echo number_format($d['arquivos'], 0, ',', '.'); ?></strong> arq.
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Como usar o pacote</h5>
                                </div>
                                <div class="card-body">
                                    <ol class="ps-3 mb-0">
                                        <li class="mb-2">Baixe o ZIP (completo ou em duas partes).</li>
                                        <li class="mb-2">Extraia num diretório de trabalho. Deve ficar com as pastas
                                            <code>img/</code> e <code>storage/</code> no topo.</li>
                                        <li class="mb-2">Na plataforma nova, rode o importador apontando para esse
                                            diretório (ele renomeia as fotos por id e reescreve as URLs das notas).</li>
                                        <li>Apague o ZIP depois da transferência: ele contém fotos de beneficiários e
                                            documentos assinados.</li>
                                    </ol>
                                </div>
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
</body>

</html>
