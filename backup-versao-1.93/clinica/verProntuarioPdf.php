<?php
/**
 * Validação de autenticidade do prontuário - consulta pública via código/QR.
 * Similar a app/assinatura/verPDF.php
 */
date_default_timezone_set('America/Sao_Paulo');
$basePath = dirname(__DIR__);
require_once $basePath . '/conectabd/conexao.php';

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$urlBase = ($isHttps ? 'https' : 'http') . '://' . $host . '/conectaosc';
$urlValidacao = rtrim($urlBase, '/') . '/clinica/verProntuarioPdf.php';

$css = '
body{background:#eef2f7;font-family:Arial,sans-serif;padding:30px;text-align:center;}
.card{background:white;padding:30px 25px;max-width:500px;margin:0 auto;border-radius:16px;box-shadow:0 8px 25px rgba(0,0,0,.12);animation:fadeIn .5s ease;text-align:left;}
.icon-ok{font-size:60px;color:#28a745;text-align:center;margin-bottom:10px;}
.icon-cert{font-size:60px;color:#007bff;margin-bottom:10px;}
.icon-error{font-size:60px;color:#dc3545;margin-bottom:10px;}
h2{margin:0;font-weight:700;color:#333;text-align:center;}
p{color:#444;font-size:15px;line-height:1.5;}
input{width:100%;padding:12px;margin-top:15px;border-radius:8px;border:1px solid #ccc;font-size:16px;box-sizing:border-box;}
button,.btn{display:block;width:100%;padding:12px;border-radius:8px;border:none;background:#007bff;color:white;font-size:17px;cursor:pointer;margin-top:15px;text-decoration:none;text-align:center;box-sizing:border-box;}
button:hover,.btn:hover{background:#0069d9;}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
';

if (!isset($_GET['code']) || empty(trim($_GET['code']))) {
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Validação de Prontuário - ITEVA</title><meta name="viewport" content="width=device-width,initial-scale=1"><style><?= $css ?></style></head>
<body>
<div class="card">
<div class="icon-cert">🔒</div>
<h2>Validação de Prontuário</h2>
<p>Digite o código de validação ou escaneie o QR Code do documento para verificar a autenticidade.</p>
<form method="GET" action="">
<input type="text" name="code" placeholder="Insira o código de validação" required>
<button type="submit">Validar</button>
</form>
</div>
</body>
</html>
<?php
    exit;
}

$code = trim($_GET['code']);
$decoded = @base64_decode($code, true);
if ($decoded === false || strpos($decoded, '|') === false) {
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Código Inválido</title><meta name="viewport" content="width=device-width,initial-scale=1"><style><?= $css ?></style></head>
<body>
<div class="card">
<div class="icon-error">❌</div>
<h2>Código Inválido</h2>
<p>O código informado não é válido.</p>
<a href="verProntuarioPdf.php" class="btn">Tentar Novamente</a>
</div>
</body>
</html>
<?php
    exit;
}

$partes = explode('|', $decoded, 3);
$prontuarioId = (int) ($partes[0] ?? 0);
$profissionalId = (int) ($partes[1] ?? 0);
$timestamp = (int) ($partes[2] ?? 0);

if ($prontuarioId <= 0) {
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Código Inválido</title><meta name="viewport" content="width=device-width,initial-scale=1"><style><?= $css ?></style></head>
<body>
<div class="card">
<div class="icon-error">❌</div>
<h2>Código Inválido</h2>
<p>O código informado não é válido.</p>
<a href="verProntuarioPdf.php" class="btn">Tentar Novamente</a>
</div>
</body>
</html>
<?php
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.id, p.created_at, p.profissional_id,
           CONCAT(u.Nome, ' ', u.Sobrenome) AS profissional_nome,
           al.Nome AS paciente_nome,
           c.data_consulta, c.hora_inicio_prevista
    FROM tb_prontuario p
    JOIN tbUser u ON u.IdColaborador = p.profissional_id
    JOIN tbAluno al ON al.IdUsuario = p.aluno_id
    LEFT JOIN tb_consulta c ON c.id = p.consulta_id
    WHERE p.id = ?
");
$stmt->execute([$prontuarioId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || (int)$row['profissional_id'] !== $profissionalId) {
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Documento Não Encontrado</title><meta name="viewport" content="width=device-width,initial-scale=1"><style><?= $css ?></style></head>
<body>
<div class="card">
<div class="icon-error">❌</div>
<h2>Documento Não Encontrado</h2>
<p>Nenhum prontuário foi localizado com o código informado.</p>
<a href="verProntuarioPdf.php" class="btn">Tentar Novamente</a>
</div>
</body>
</html>
<?php
    exit;
}

$dataDoc = date('d/m/Y H:i', $timestamp ?: strtotime($row['created_at']));
if (!empty($row['data_consulta'])) {
    $dt = DateTime::createFromFormat('Y-m-d', $row['data_consulta']);
    if ($dt) {
        $dataDoc = $dt->format('d/m/Y');
        if (!empty($row['hora_inicio_prevista']) && preg_match('/^\d{2}:\d{2}/', trim($row['hora_inicio_prevista']))) {
            $dataDoc .= ' ' . substr(trim($row['hora_inicio_prevista']), 0, 5);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Prontuário Validado - ITEVA</title><meta name="viewport" content="width=device-width,initial-scale=1"><style><?= $css ?></style></head>
<body>
<div class="card">
<div class="icon-ok">📄✒️</div>
<h2>Prontuário Validado</h2>
<p><b>Documento:</b> Prontuário Clínico #<?= (int)$row['id'] ?></p>
<p><b>Paciente:</b> <?= htmlspecialchars($row['paciente_nome']) ?></p>
<p><b>Profissional responsável:</b> <?= htmlspecialchars($row['profissional_nome']) ?></p>
<p><b>Data do atendimento:</b> <?= htmlspecialchars($dataDoc) ?></p>
<p><b>Código de validação:</b><br><span style="font-family:monospace;font-size:14px;"><?= htmlspecialchars($code) ?></span></p>
<a href="verProntuarioPdf.php" class="btn">Validar Outro Documento</a>
</div>
</body>
</html>
