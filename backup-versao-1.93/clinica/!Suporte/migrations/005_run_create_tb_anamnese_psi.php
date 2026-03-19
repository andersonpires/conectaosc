<?php
/**
 * Executor da migration 005 - Criar tabela tb_anamnese_psi
 * App Clínica - ConectaOSC
 * Execute via navegador: /conectaosc/clinica/!Suporte/migrations/005_run_create_tb_anamnese_psi.php
 */
session_start();
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['Cod'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<h2>Migração 005 - tb_anamnese_psi</h2>';
    echo '<p><strong>Erro:</strong> Faça login no ConectaOSC antes de executar.</p>';
    exit;
}

require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

$sql = "CREATE TABLE IF NOT EXISTS tb_anamnese_psi (
    id INT(11) NOT NULL AUTO_INCREMENT,
    consulta_id INT(11) NOT NULL,
    aluno_id INT(11) NOT NULL,
    profissional_id INT(11) NOT NULL,
    crenca_religiao VARCHAR(100) NULL DEFAULT NULL,
    crenca_conforto_preocupacao VARCHAR(20) NULL DEFAULT NULL,
    quem_ajuda_dia_dia TEXT NULL,
    cuidador_de_alguem TINYINT(1) NULL DEFAULT NULL,
    cuidador_impacto_rotina TEXT NULL,
    acompanhamento_psicologico TINYINT(1) NULL DEFAULT NULL,
    medicacoes_psicotropicos TINYINT(1) NULL DEFAULT NULL,
    internacao_psiquiatrica TINYINT(1) NULL DEFAULT NULL,
    percebido_ultimos_meses TEXT NULL,
    lidar_situacoes_dificeis TEXT NULL,
    avaliacao_saude_geral VARCHAR(30) NULL DEFAULT NULL,
    cansado_desanimado VARCHAR(30) NULL DEFAULT NULL,
    qualidade_sono VARCHAR(30) NULL DEFAULT NULL,
    acesso_servicos_saude TINYINT(1) NULL DEFAULT NULL,
    renda_suficiente_necessidades TINYINT(1) NULL DEFAULT NULL,
    ori_nome_completo VARCHAR(50) NULL DEFAULT NULL,
    ori_onde_estamos VARCHAR(50) NULL DEFAULT NULL,
    ori_dia_mes_ano VARCHAR(50) NULL DEFAULT NULL,
    ori_quem_esta_aqui VARCHAR(50) NULL DEFAULT NULL,
    mem_tres_palavras VARCHAR(50) NULL DEFAULT NULL,
    mem_cafe_manha VARCHAR(50) NULL DEFAULT NULL,
    ate_contar_20_0 VARCHAR(50) NULL DEFAULT NULL,
    ate_sim_bata_palma VARCHAR(50) NULL DEFAULT NULL,
    ate_frase_maria VARCHAR(50) NULL DEFAULT NULL,
    lin_nomeacao VARCHAR(50) NULL DEFAULT NULL,
    lin_compreensao VARCHAR(50) NULL DEFAULT NULL,
    lin_repeticao VARCHAR(50) NULL DEFAULT NULL,
    lin_fluencia VARCHAR(50) NULL DEFAULT NULL,
    fex_planejamento VARCHAR(50) NULL DEFAULT NULL,
    fex_sequencia VARCHAR(50) NULL DEFAULT NULL,
    fex_flexibilidade VARCHAR(50) NULL DEFAULT NULL,
    fex_resolucao_problemas VARCHAR(50) NULL DEFAULT NULL,
    uso_alcool_substancias TINYINT(1) NULL DEFAULT NULL,
    pensou_tentou_machucar TINYINT(1) NULL DEFAULT NULL,
    sente_sozinho_isolado VARCHAR(20) NULL DEFAULT NULL,
    o_que_ajuda_momentos_dificeis VARCHAR(200) NULL DEFAULT NULL,
    atividades_fazem_bem TEXT NULL,
    sentimento_ultimos_meses VARCHAR(100) NULL DEFAULT NULL,
    consegue_pedir_ajuda VARCHAR(30) NULL DEFAULT NULL,
    tem_objetivos_motivam VARCHAR(30) NULL DEFAULT NULL,
    quando_triste_o_que_faz TEXT NULL,
    dificuldades_memoria VARCHAR(50) NULL DEFAULT NULL,
    atividades_basicas_sozinho VARCHAR(50) NULL DEFAULT NULL,
    o_que_espera_projeto TEXT NULL,
    fortalecer_mais VARCHAR(100) NULL DEFAULT NULL,
    observacoes_gerais LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_consulta (consulta_id),
    KEY idx_aluno (aluno_id),
    KEY idx_profissional (profissional_id),
    KEY idx_created (created_at),
    CONSTRAINT fk_anamnese_consulta FOREIGN KEY (consulta_id) REFERENCES tb_consulta (id) ON DELETE RESTRICT,
    CONSTRAINT fk_anamnese_aluno FOREIGN KEY (aluno_id) REFERENCES tbAluno (IdUsuario) ON DELETE RESTRICT,
    CONSTRAINT fk_anamnese_profissional FOREIGN KEY (profissional_id) REFERENCES tbUser (IdColaborador) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $pdo->exec($sql);
    echo "Tabela tb_anamnese_psi criada/verificada. OK.<br>";
} catch (PDOException $e) {
    echo "Erro: " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo '<p><strong>Migração 005 concluída.</strong></p>';
