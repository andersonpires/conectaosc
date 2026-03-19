<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: " . rtrim((string) ($BASE_para_URL ?? ''), '/') . "/login/?redirect=$redirect_url");
    exit();
}

require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

header('Content-Type: application/json');

$sql = "
    SELECT 
        a.IdUsuario,
        a.Nome,

        -- INTERESSES (NOVO)
        (
            SELECT GROUP_CONCAT(p.NomeProjeto SEPARATOR ' / ')
            FROM tbInteresse i
            JOIN tbProjeto p ON p.IdProjeto = i.IdProjeto
            WHERE i.IdUsuario = a.IdUsuario
              AND i.Valor = 1
        ) AS Interesses,

        -- TURMAS
        (
            SELECT GROUP_CONCAT(c.NomeTurma SEPARATOR ' / ')
            FROM tbMatricula m 
            JOIN tbTurma c ON m.IdTurma = c.IdTurma 
            WHERE m.IdUsuario = a.IdUsuario
        ) AS Turmas,

        a.Nascimento,
        a.CPF,
        a.Endereco,
        a.Bairro,
        a.Cidade,
        a.Telefone,
        a.WhatsApp,
        a.NomeResp1,
        a.WhatsAppResp1,
        a.Obs

    FROM tbAluno a
    WHERE a.Habilitado = 1
    ORDER BY a.Nome ASC
";

$stmt = $pdo->query($sql);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Comentário ajustado para UTF-8.
foreach ($dados as &$linha) {
    $linha['Interesses'] = $linha['Interesses'] ?? '';
    $linha['Turmas']     = $linha['Turmas'] ?? '';
}

echo json_encode($dados);




