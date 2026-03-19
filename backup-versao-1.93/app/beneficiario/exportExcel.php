<?php
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url");
    exit();
}

require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

header('Content-Type: application/json');

$sql = "
    SELECT 
        a.IdUsuario,
        a.Nome,

        -- 🔥 INTERESSES (NOVO)
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

// Garante que não venha NULL no JSON
foreach ($dados as &$linha) {
    $linha['Interesses'] = $linha['Interesses'] ?? '';
    $linha['Turmas']     = $linha['Turmas'] ?? '';
}

echo json_encode($dados);
