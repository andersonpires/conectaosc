<?php
declare(strict_types=1);

$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$basePath = (string) ($runtime['base_para_path'] ?? '');

if ($basePath === '') {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Falha ao resolver o caminho base do projeto.';
    exit;
}

require_once $basePath . '/api/conectabd/conexao.php';

$sqlPath = __DIR__ . '/20260304_create_tb_plano_curso.sql';
if (!is_file($sqlPath)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Arquivo SQL não encontrado: ' . $sqlPath;
    exit;
}

$sql = file_get_contents($sqlPath);
if ($sql === false || trim($sql) === '') {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Falha ao ler o arquivo SQL.';
    exit;
}

try {
    $pdo->exec($sql);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'OK: tabelas tbPlanoCurso, tbPlanoCursoArquivo, tbPlanoCursoAgenda e tbPlanoCursoHistorico criadas/atualizadas.';
} catch (Throwable $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Erro ao executar SQL: ' . $e->getMessage();
}
