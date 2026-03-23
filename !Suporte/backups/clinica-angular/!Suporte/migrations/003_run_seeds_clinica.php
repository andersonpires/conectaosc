<?php
/**
 * Seeds iniciais - Especialidades e Tipos de Consulta
 * Execute via navegador: /conectaosc/clinica/!Suporte/migrations/003_run_seeds_clinica.php
 */
session_start();
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_para_PATH']) || !isset($_SESSION['Cod'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<p><strong>Erro:</strong> Faça login no ConectaOSC antes de executar.</p>';
    exit;
}

require_once $_SESSION['BASE_para_PATH'] . '/conectabd/conexao.php';

$especialidades = ['Fisioterapia', 'Psicologia', 'Nutrição', 'Fonaudiologia'];
$tiposConsulta = ['Consulta simples', 'Consulta on-line', 'Anamnese', 'Retorno', 'IPAI'];

foreach ($especialidades as $nome) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO tb_especialidade (nome) VALUES (?)");
    $stmt->execute([$nome]);
    if ($stmt->rowCount() > 0) {
        echo "Especialidade '$nome' inserida.<br>";
    }
}

foreach ($tiposConsulta as $nome) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO tb_tipo_consulta (nome) VALUES (?)");
    $stmt->execute([$nome]);
    if ($stmt->rowCount() > 0) {
        echo "Tipo de consulta '$nome' inserido.<br>";
    }
}

echo '<p><strong>Seeds concluídos.</strong></p>';
