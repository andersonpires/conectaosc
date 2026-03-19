<?php
/**
 * Executor da migration 002 - Criar tabelas do App Clínica
 * Execute via navegador: /conectaosc/clinica/!Suporte/migrations/002_run_create_tables_clinica.php
 */
session_start();
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_para_PATH']) || !isset($_SESSION['Cod'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<h2>Migração 002 - Tabelas Clínica</h2>';
    echo '<p><strong>Erro:</strong> Faça login no ConectaOSC antes de executar.</p>';
    exit;
}

require_once $_SESSION['BASE_para_PATH'] . '/conectabd/conexao.php';

$statements = [
    "CREATE TABLE IF NOT EXISTS tb_especialidade (
        id INT(11) NOT NULL AUTO_INCREMENT,
        nome VARCHAR(100) NOT NULL,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_nome (nome),
        KEY idx_ativo (ativo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS tb_tipo_consulta (
        id INT(11) NOT NULL AUTO_INCREMENT,
        nome VARCHAR(100) NOT NULL,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_nome (nome),
        KEY idx_ativo (ativo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS tb_consulta (
        id INT(11) NOT NULL AUTO_INCREMENT,
        aluno_id INT(11) NOT NULL,
        profissional_id INT(11) NULL DEFAULT NULL,
        profissional_nome_livre VARCHAR(120) NULL DEFAULT NULL,
        especialidade_id INT(11) NOT NULL,
        tipo_consulta_id INT(11) NOT NULL,
        data_consulta DATE NOT NULL,
        hora_inicio_prevista TIME NOT NULL,
        duracao_minutos_prevista INT(11) NOT NULL,
        hora_fim_prevista TIME NOT NULL,
        hora_fim_real TIME NULL DEFAULT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'agendada',
        observacao TEXT NULL,
        criado_por INT(11) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_aluno (aluno_id),
        KEY idx_profissional (profissional_id),
        KEY idx_data_status (data_consulta, status),
        CONSTRAINT fk_consulta_aluno FOREIGN KEY (aluno_id) REFERENCES tbAluno (IdUsuario) ON DELETE RESTRICT,
        CONSTRAINT fk_consulta_profissional FOREIGN KEY (profissional_id) REFERENCES tbUser (IdColaborador) ON DELETE SET NULL,
        CONSTRAINT fk_consulta_especialidade FOREIGN KEY (especialidade_id) REFERENCES tb_especialidade (id) ON DELETE RESTRICT,
        CONSTRAINT fk_consulta_tipo FOREIGN KEY (tipo_consulta_id) REFERENCES tb_tipo_consulta (id) ON DELETE RESTRICT,
        CONSTRAINT fk_consulta_criado FOREIGN KEY (criado_por) REFERENCES tbUser (IdColaborador) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS tb_agenda_clinica (
        id INT(11) NOT NULL AUTO_INCREMENT,
        consulta_id INT(11) NOT NULL,
        data_agenda DATE NOT NULL,
        inicio DATETIME NOT NULL,
        fim_previsto DATETIME NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'agendada',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_consulta (consulta_id),
        KEY idx_data (data_agenda),
        CONSTRAINT fk_agenda_consulta FOREIGN KEY (consulta_id) REFERENCES tb_consulta (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS tb_prontuario (
        id INT(11) NOT NULL AUTO_INCREMENT,
        consulta_id INT(11) NOT NULL,
        aluno_id INT(11) NOT NULL,
        profissional_id INT(11) NOT NULL,
        conteudo_ia LONGTEXT NULL,
        conteudo_editado LONGTEXT NULL,
        versao INT(11) NOT NULL DEFAULT 1,
        status VARCHAR(20) NOT NULL DEFAULT 'rascunho',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_consulta (consulta_id),
        KEY idx_aluno (aluno_id),
        KEY idx_profissional (profissional_id),
        CONSTRAINT fk_prontuario_consulta FOREIGN KEY (consulta_id) REFERENCES tb_consulta (id) ON DELETE RESTRICT,
        CONSTRAINT fk_prontuario_aluno FOREIGN KEY (aluno_id) REFERENCES tbAluno (IdUsuario) ON DELETE RESTRICT,
        CONSTRAINT fk_prontuario_profissional FOREIGN KEY (profissional_id) REFERENCES tbUser (IdColaborador) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

foreach ($statements as $i => $sql) {
    try {
        $pdo->exec($sql);
        echo "Tabela " . ($i + 1) . " criada/verificada. OK.<br>";
    } catch (PDOException $e) {
        echo "Erro no statement " . ($i + 1) . ": " . htmlspecialchars($e->getMessage()) . "<br>";
    }
}

echo '<p><strong>Migração 002 concluída.</strong></p>';
echo '<p>Execute 003_run_seeds_clinica.php para inserir especialidades e tipos de consulta.</p>';
