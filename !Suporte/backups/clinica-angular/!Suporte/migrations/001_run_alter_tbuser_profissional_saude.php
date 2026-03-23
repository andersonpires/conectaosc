<?php
/**
 * Executor da migration 001 - Adicionar profissional_saude em tbUser
 * App Clínica - ConectaOSC
 * Execute via navegador: /conectaosc/clinica/!Suporte/migrations/001_run_alter_tbuser_profissional_saude.php
 */
session_start();
date_default_timezone_set('America/Sao_Paulo');

// Requer sessão válida (deve estar logado no ConectaOSC)
if (!isset($_SESSION['BASE_para_PATH']) || !isset($_SESSION['Cod'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<h2>Migração 001 - tbUser.profissional_saude</h2>';
    echo '<p><strong>Erro:</strong> Faça login no ConectaOSC antes de executar esta migração.</p>';
    echo '<p><a href="' . (dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])))) . '/login.php">Ir para login</a></p>';
    exit;
}

require_once $_SESSION['BASE_para_PATH'] . '/conectabd/conexao.php';

$sqlFile = __DIR__ . '/001_alter_tbuser_profissional_saude.sql';
if (!file_exists($sqlFile)) {
    die('Arquivo SQL não encontrado: ' . $sqlFile);
}

$sql = file_get_contents($sqlFile);

try {
    // Executar statements individuais (MySQL pode não aceitar múltiplos em uma chamada)
    $pdo->exec("ALTER TABLE tbUser ADD COLUMN profissional_saude TINYINT(1) NOT NULL DEFAULT 0");
    echo "Coluna profissional_saude adicionada com sucesso.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Coluna profissional_saude já existe. OK.<br>";
    } else {
        die('Erro: ' . htmlspecialchars($e->getMessage()));
    }
}

try {
    $pdo->exec("ALTER TABLE tbUser ADD INDEX idx_profissional_saude (profissional_saude)");
    echo "Índice idx_profissional_saude criado com sucesso.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key') !== false) {
        echo "Índice idx_profissional_saude já existe. OK.<br>";
    } else {
        die('Erro índice: ' . htmlspecialchars($e->getMessage()));
    }
}

echo '<p><strong>Migração 001 concluída com sucesso.</strong></p>';
echo '<p>Próximo passo: execute a migration 002 para criar as tabelas da clínica.</p>';
