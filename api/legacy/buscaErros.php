<?php
// buscaErros.php - v2 com modo diagnóstico
declare(strict_types=1);
mb_internal_encoding('UTF-8');
set_time_limit(0);

$BASE_DIR = realpath(__DIR__);

// ---------- Helpers ----------
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function isPathInside(string $base, string $path): bool {
    $base = rtrim(str_replace(['\\','/'], DIRECTORY_SEPARATOR, realpath($base)), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $path = rtrim(str_replace(['\\','/'], DIRECTORY_SEPARATOR, realpath($path) ?: $path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    return strpos($path, $base) === 0;
}

function toRelative(string $base, string $abs): string {
    $base = rtrim(str_replace(['\\','/'], DIRECTORY_SEPARATOR, realpath($base)), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $abs  = str_replace(['\\','/'], DIRECTORY_SEPARATOR, realpath($abs));
    if ($abs === false) { return ''; }
    return ltrim(str_replace($base, '', $abs), DIRECTORY_SEPARATOR);
}

function scanForHtaccess(string $base): array {
    $found = [];
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $file) {
        /** @var SplFileInfo $file */
        if ($file->isFile() && strtolower($file->getFilename()) === '.htaccess') {
            $found[] = toRelative($base, $file->getRealPath());
        }
    }
    sort($found, SORT_NATURAL | SORT_FLAG_CASE);
    return $found;
}

/**
 * Captura o próximo erro PHP gerado dentro do callable ($fn).
 * Retorna [resultado, erro|null]
 */
function withErrorCapture(callable $fn) {
    $captured = null;
    set_error_handler(function ($severity, $message, $file, $line) use (&$captured) {
        // Não converte em exceção; apenas captura para retornarmos
        $captured = [
            'severity' => $severity,
            'message'  => $message,
            'file'     => $file,
            'line'     => $line,
        ];
        // retornar false permite que o PHP mantenha o comportamento padrão (mas não queremos ecoar nada)
        return true; // "consome" o erro para não poluir a saída
    });
    try {
        $res = $fn();
    } finally {
        restore_error_handler();
    }
    return [$res, $captured];
}

function fmtPerms($perms): string {
    if (!is_int($perms)) return '';
    return substr(sprintf('%o', $perms), -4);
}

function ownerInfo($path): array {
    $owner = @fileowner($path);
    $group = @filegroup($path);
    $out = ['uid' => $owner, 'gid' => $group, 'user' => null, 'group' => null];
    if (function_exists('posix_getpwuid') && is_int($owner)) {
        $pw = @posix_getpwuid($owner);
        if ($pw && isset($pw['name'])) $out['user'] = $pw['name'];
    }
    if (function_exists('posix_getgrgid') && is_int($group)) {
        $gr = @posix_getgrgid($group);
        if ($gr && isset($gr['name'])) $out['group'] = $gr['name'];
    }
    return $out;
}

function deleteHtaccess(string $base, string $relativePath): array {
    $relativePath = ltrim(str_replace(['\\','/'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
    $abs = $base . DIRECTORY_SEPARATOR . $relativePath;

    // Sanity checks
    if (basename($abs) !== '.htaccess') {
        return ['ok' => false, 'message' => 'Ignorado: nome de arquivo não é .htaccess', 'path' => $relativePath];
    }
    $real = realpath($abs);
    if ($real === false || !isPathInside($base, $abs)) {
        return ['ok' => false, 'message' => 'Caminho inválido ou fora do diretório base', 'path' => $relativePath];
    }
    if (!is_file($real)) {
        return ['ok' => false, 'message' => 'Arquivo não encontrado (pode já ter sido removido)', 'path' => $relativePath];
    }

    clearstatcache(true, $real);
    $isWritable = @is_writable($real);
    $perms      = @fileperms($real);
    $owner      = ownerInfo($real);

    // Tenta ajustar permissão para facilitar remoção
    @chmod($real, 0644);
    clearstatcache(true, $real);

    // Tentativa 1: unlink direto (com captura de erro)
    [$ok, $err1] = withErrorCapture(fn() => unlink($real));
    if ($ok) {
      return [
        'ok' => true,
        'message' => 'Excluído',
        'path' => $relativePath,
        'debug' => [
          'method' => 'unlink',
          'pre' => ['writable' => $isWritable, 'perms' => fmtPerms($perms), 'owner' => $owner],
        ],
      ];
    }

    // Tentativa 2: rename + unlink (alguns hosts bloqueiam unlink direto)
    $tmp = $real . '.del.' . uniqid('', true);
    [$renOk, $err2] = withErrorCapture(fn() => rename($real, $tmp));
    if ($renOk) {
        [$delOk, $err3] = withErrorCapture(fn() => unlink($tmp));
        if ($delOk) {
            return [
                'ok' => true,
                'message' => 'Excluído (via rename)',
                'path' => $relativePath,
                'debug' => [
                    'method' => 'rename+unlink',
                    'pre' => ['writable' => $isWritable, 'perms' => fmtPerms($perms), 'owner' => $owner],
                ],
            ];
        } else {
            // rollback não é necessário; arquivo já foi renomeado
            return [
                'ok' => false,
                'message' => 'Falha ao excluir arquivo renomeado',
                'path' => $relativePath,
                'debug' => [
                    'method' => 'rename+unlink',
                    'unlink_error' => $err3,
                    'pre' => ['writable' => $isWritable, 'perms' => fmtPerms($perms), 'owner' => $owner],
                ],
            ];
        }
    }

    // Falhou geral - retorna diagnóstico completo
    return [
        'ok' => false,
        'message' => 'Falha ao excluir (permissões/host)',
        'path' => $relativePath,
        'debug' => [
            'method' => 'unlink|rename failed',
            'unlink_error' => $err1,
            'rename_error' => $err2,
            'pre' => [
                'writable' => $isWritable,
                'perms' => fmtPerms($perms),
                'owner' => $owner,
            ],
            'env' => [
                'php_version' => PHP_VERSION,
                'sapi' => PHP_SAPI,
                'cwd' => getcwd(),
                'base_dir' => $base,
                'uid' => function_exists('posix_geteuid') ? @posix_geteuid() : null,
                'user' => function_exists('get_current_user') ? @get_current_user() : null,
                'open_basedir' => ini_get('open_basedir') ?: null,
                'disable_functions' => ini_get('disable_functions') ?: null,
            ],
        ],
    ];
}

// ---------- API ----------
$action = $_GET['action'] ?? $_POST['action'] ?? null;
if ($action === 'scan') {
    $files = scanForHtaccess($BASE_DIR);
    jsonResponse([
        'ok' => true,
        'total' => count($files),
        'files' => $files,
        'base' => $BASE_DIR
    ]);
}
if ($action === 'delete_one') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw ?: '[]', true) ?: $_POST;
    $rel = $payload['path'] ?? '';
    $result = deleteHtaccess($BASE_DIR, (string)$rel);
    jsonResponse($result, 200);
}

// ---------- UI (HTML) ----------
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Remoção de .htaccess - Busca & Exclusão (diagnóstico)</title>
  <link rel="stylesheet" href="assets/css/buscaErros.css">
</head>
<body>
  <div class="wrap">
    <header class="header">
      <h1>ðŸ§¹ Remoção de <code>.htaccess</code> <small style="font-size:14px;color:#a6b0c2;">(modo diagnóstico)</small></h1>
      <p class="warn">
        Você afirmou estar ciente dos riscos. Esta ferramenta faz uma varredura completa a partir da pasta atual
        (<span id="baseDir">...</span>) e <strong>exclui</strong> todos os arquivos <code>.htaccess</code> encontrados.
        Agora com <strong>detalhamento de erros</strong> caso a exclusão falhe.
      </p>
    </header>

    <section class="controls">
      <button id="btnScan" class="btn primary">1) Buscar arquivos</button>
      <button id="btnDeleteAll" class="btn danger" disabled>2) Iniciar exclusão</button>
      <button id="btnStop" class="btn ghost" disabled>Parar</button>
    </section>

    <section class="progress-area">
      <div class="progress-meta">
        <span id="progressText">0%</span>
        <span id="counters">Encontrados: 0 * Excluídos: 0 * Falhas: 0</span>
      </div>
      <div class="progressbar">
        <div id="progressBar" class="progressbar-fill" style="width:0%"></div>
      </div>
    </section>

    <section class="grid">
      <div class="panel">
        <h2>ðŸ”Ž Resultados da busca</h2>
        <ul id="fileList" class="file-list"></ul>
      </div>

      <div class="panel">
        <h2>ðŸ“œ Log de atividades</h2>
        <div id="log" class="log"></div>
      </div>
    </section>

    <section class="charts">
      <h2>ðŸ“Š Relatório</h2>
      <div class="charts-grid">
        <canvas id="barChart" width="420" height="260"></canvas>
        <canvas id="donutChart" width="420" height="260"></canvas>
      </div>
      <div id="finalSummary" class="summary"></div>
    </section>

    <footer class="footer">
      <small>Feito para modo escuro * Cores vibrantes * Sem dependências externas</small>
    </footer>
  </div>

  <script>
    window.BUSCA_ERROS_CONFIG = {
      endpoints: {
        scan: "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?action=scan",
        delete_one: "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
      }
    };
  </script>
  <script src="assets/js/buscaErros.js" defer></script>
</body>
</html>

