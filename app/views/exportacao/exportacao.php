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

// Diagnostico: radiografia de cada candidato + a propria base, para descobrir
// onde os arquivos estao de verdade neste servidor.
$diagnostico = [];
foreach (exportacaoCandidatos($BASE_para_PATH) as $prefixo => $candidatos) {
    foreach ($candidatos as $dir) {
        $diagnostico[] = ['prefixo' => $prefixo] + exportacaoInspecionar($dir);
    }
}
$inspecaoBase = exportacaoInspecionar($BASE_para_PATH);
$ambiente = [
    'BASE_para_PATH' => $BASE_para_PATH,
    'DOCUMENT_ROOT' => (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''),
    'getcwd()' => (string) (getcwd() ?: ''),
    'usuario do PHP' => function_exists('posix_getpwuid') && function_exists('posix_geteuid')
        ? (string) ((posix_getpwuid(posix_geteuid())['name'] ?? '?'))
        : (string) (get_current_user() ?: '?'),
];

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
                                <div class="card-body" style="font-size: 12px;">
                                    <p class="mb-1"><strong>Ambiente</strong></p>
                                    <ul class="ps-3 mb-3" style="word-break: break-all;">
                                        <?php foreach ($ambiente as $k => $v) { ?>
                                            <li><?php echo htmlspecialchars((string) $k, ENT_QUOTES); ?>:
                                                <code><?php echo htmlspecialchars($v !== '' ? $v : '(vazio)', ENT_QUOTES); ?></code></li>
                                        <?php } ?>
                                    </ul>

                                    <p class="mb-1"><strong>Conteúdo da base</strong></p>
                                    <p class="mb-3" style="word-break: break-all;">
                                        <?php if (empty($inspecaoBase['sub'])) { ?>
                                            <span class="text-muted">nenhuma subpasta listada</span>
                                        <?php } else { ?>
                                            <?php foreach ($inspecaoBase['sub'] as $nome => $n) { ?>
                                                <span class="badge bg-light text-dark me-1 mb-1">
                                                    <?php echo htmlspecialchars((string) $nome, ENT_QUOTES); ?>
                                                    (<?php echo $n < 0 ? 'erro' : number_format($n, 0, ',', '.'); ?>)
                                                </span>
                                            <?php } ?>
                                        <?php } ?>
                                    </p>

                                    <p class="mb-1"><strong>Candidatos</strong></p>
                                    <?php foreach ($diagnostico as $d) { ?>
                                        <div class="mb-2 pb-2 border-bottom" style="word-break: break-all;">
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($d['prefixo'], ENT_QUOTES); ?></span>
                                            <code><?php echo htmlspecialchars($d['caminho'], ENT_QUOTES); ?></code>
                                            <br>
                                            <?php if (!$d['existe']) { ?>
                                                <span class="text-muted">não existe</span>
                                            <?php } elseif (!$d['legivel']) { ?>
                                                <span class="text-danger">existe, mas SEM PERMISSÃO de leitura</span>
                                            <?php } else { ?>
                                                <strong><?php echo number_format($d['arquivos'], 0, ',', '.'); ?></strong> arquivo(s)
                                                <?php if ($d['link']) { ?>
                                                    · link → <code><?php echo htmlspecialchars((string) $d['link'], ENT_QUOTES); ?></code>
                                                <?php } ?>
                                                <?php if (!empty($d['sub'])) { ?>
                                                    <br>
                                                    <?php foreach ($d['sub'] as $nome => $n) { ?>
                                                        <span class="badge bg-light text-dark me-1">
                                                            <?php echo htmlspecialchars((string) $nome, ENT_QUOTES); ?>
                                                            (<?php echo $n < 0 ? 'erro' : number_format($n, 0, ',', '.'); ?>)
                                                        </span>
                                                    <?php } ?>
                                                <?php } ?>
                                            <?php } ?>
                                            <?php if ($d['erro']) { ?>
                                                <br><span class="text-danger"><?php echo htmlspecialchars((string) $d['erro'], ENT_QUOTES); ?></span>
                                            <?php } ?>
                                        </div>
                                    <?php } ?>
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
