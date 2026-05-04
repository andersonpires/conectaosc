<?php
require_once __DIR__ . '/bootstrap/runtime.php';

$runtime = bootstrap_runtime();
$basePath = rtrim((string)($runtime['base_para_path'] ?? __DIR__), '/\\');

require_once $basePath . '/api/conectabd/conexao.php';

$fotosDir = $basePath . '/app/assets/img/fotos';

$stmt = $pdo->query("SELECT IdUsuario, Nome, Foto FROM tbAluno WHERE Foto IS NOT NULL AND TRIM(Foto) <> '' AND TRIM(Foto) <> 'padrao.jfif' ORDER BY Nome ASC");
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$faltantes = [];
foreach ($registros as $row) {
    $foto = trim((string)($row['Foto'] ?? ''));
    if ($foto === '') {
        continue;
    }

    $nomeArquivo = basename(str_replace('\\', '/', $foto));
    $caminhoFoto = $fotosDir . DIRECTORY_SEPARATOR . $nomeArquivo;
    if (!is_file($caminhoFoto)) {
        $faltantes[] = [
            'IdUsuario' => (int)($row['IdUsuario'] ?? 0),
            'Nome' => (string)($row['Nome'] ?? ''),
            'Foto' => $foto,
        ];
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fotos Ausentes - tbAluno</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #111827; }
        h1 { margin: 0 0 8px 0; }
        .meta { margin-bottom: 16px; color: #4b5563; }
        table { border-collapse: collapse; width: 100%; max-width: 1000px; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; }
        th { background: #f3f4f6; }
        tr:nth-child(even) { background: #fafafa; }
        .ok { color: #065f46; font-weight: 700; }
    </style>
</head>
<body>
    <h1>Fotos ausentes em tbAluno</h1>
    <p class="meta">Diretório verificado: <code><?php echo htmlspecialchars($fotosDir, ENT_QUOTES, 'UTF-8'); ?></code></p>

    <?php if (empty($faltantes)): ?>
        <p class="ok">Nenhum registro com foto ausente foi encontrado.</p>
    <?php else: ?>
        <p class="meta">Total encontrado: <strong><?php echo count($faltantes); ?></strong></p>
        <table>
            <thead>
                <tr>
                    <th>IdUsuario</th>
                    <th>Nome</th>
                    <th>Foto (banco)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($faltantes as $item): ?>
                    <tr>
                        <td><?php echo (int)$item['IdUsuario']; ?></td>
                        <td><?php echo htmlspecialchars($item['Nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($item['Foto'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
