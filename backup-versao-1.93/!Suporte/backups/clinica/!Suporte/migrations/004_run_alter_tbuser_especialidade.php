<?php
/**
 * Executor da migration 004 - Adicionar especialidade_id em tbUser
 * App Clínica - ConectaOSC
 * Execute via navegador: /conectaosc/clinica/!Suporte/migrations/004_run_alter_tbuser_especialidade.php
 */
session_start();
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['Cod'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<h2>Migração 004 - tbUser.especialidade_id</h2>';
    echo '<p><strong>Erro:</strong> Faça login no ConectaOSC antes de executar.</p>';
    exit;
}

require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

try {
    $pdo->exec("ALTER TABLE tbUser ADD COLUMN especialidade_id INT(11) NULL DEFAULT NULL");
    echo "Coluna especialidade_id adicionada.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Coluna especialidade_id já existe. OK.<br>";
    } else {
        die('Erro: ' . htmlspecialchars($e->getMessage()));
    }
}

try {
    $pdo->exec("ALTER TABLE tbUser ADD CONSTRAINT fk_user_especialidade FOREIGN KEY (especialidade_id) REFERENCES tb_especialidade(id) ON DELETE SET NULL");
    echo "FK fk_user_especialidade criada.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "FK fk_user_especialidade já existe. OK.<br>";
    } else {
        echo "Aviso FK: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
}

try {
    $pdo->exec("ALTER TABLE tbUser ADD INDEX idx_user_especialidade (especialidade_id)");
    echo "Índice idx_user_especialidade criado.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "Índice já existe. OK.<br>";
    } else {
        echo "Aviso índice: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
}

echo '<p><strong>Migração 004 concluída.</strong></p>';
