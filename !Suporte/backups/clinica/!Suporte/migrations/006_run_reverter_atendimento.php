<?php
/**
 * Executor da migration 006 - Reverter consultas em_atendimento para agendada
 * App Clínica - ConectaOSC
 * Execute via navegador: /conectaosc/clinica/!Suporte/migrations/006_run_reverter_atendimento.php
 */
header('Content-Type: text/html; charset=utf-8');
session_start();
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_para_PATH']) || !isset($_SESSION['Cod'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<h2>Migração 006 - Reverter atendimento</h2>';
    echo '<p><strong>Erro:</strong> Faça login no ConectaOSC antes de executar.</p>';
    exit;
}

require_once $_SESSION['BASE_para_PATH'] . '/conectabd/conexao.php';

$dataAlvo = '2026-02-13'; // Data a ser corrigida

try {
    $stmt = $pdo->prepare("UPDATE tb_consulta SET status = 'agendada' WHERE data_consulta = ? AND status = 'em_atendimento'");
    $stmt->execute([$dataAlvo]);
    $rowsConsulta = $stmt->rowCount();

    $stmt = $pdo->prepare("
        UPDATE tb_agenda_clinica a
        JOIN tb_consulta c ON c.id = a.consulta_id
        SET a.status = 'agendada'
        WHERE c.data_consulta = ? AND a.status = 'em_atendimento'
    ");
    $stmt->execute([$dataAlvo]);
    $rowsAgenda = $stmt->rowCount();

    echo "<p><strong>Concluído.</strong> $rowsConsulta consulta(s) e $rowsAgenda agenda(s) revertidas para 'agendada' em $dataAlvo.</p>";
} catch (PDOException $e) {
    echo '<p><strong>Erro:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
}
