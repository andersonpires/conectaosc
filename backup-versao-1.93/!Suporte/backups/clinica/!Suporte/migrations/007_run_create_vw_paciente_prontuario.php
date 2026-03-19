<?php
/**
 * Executor da migration 007 - Criar view vw_paciente_prontuario
 * App Clínica - ConectaOSC
 * Execute via navegador: /conectaosc/clinica/!Suporte/migrations/007_run_create_vw_paciente_prontuario.php
 */
session_start();
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['Cod'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<h2>Migração 007 - View vw_paciente_prontuario</h2>';
    echo '<p><strong>Erro:</strong> Faça login no ConectaOSC antes de executar.</p>';
    exit;
}

require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

$sql = "CREATE OR REPLACE VIEW vw_paciente_prontuario AS
SELECT DISTINCT al.IdUsuario AS aluno_id, al.Nome AS paciente_nome
FROM tbAluno al
INNER JOIN tb_prontuario p ON p.aluno_id = al.IdUsuario
WHERE al.Habilitado = 1";

try {
    $pdo->exec($sql);
    echo '<p>View vw_paciente_prontuario criada com sucesso.</p>';
} catch (PDOException $e) {
    die('<p><strong>Erro:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>');
}

echo '<p><strong>Migração 007 concluída.</strong></p>';
