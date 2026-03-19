<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
require_once __DIR__ . '/../../../api/conectabd/conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $alertaFaltoso = intval($_POST['alertaFaltoso']);
    $corFaltoso = preg_replace('/[^0-9A-Fa-f#]/', '', $_POST['corFaltoso']);
    $alertaExcluido = intval($_POST['alertaExcluido']);
    $corExcluido = preg_replace('/[^0-9A-Fa-f#]/', '', $_POST['corExcluido']);

    try {
        $sql = "UPDATE tbAlerta SET alertaFaltoso = ?, corFaltoso = ?, alertaExcluido = ?, corExcluido = ? LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$alertaFaltoso, $corFaltoso, $alertaExcluido, $corExcluido]);

        echo json_encode(["status" => "success", "message" => "Configuração de alerta atualizada com sucesso!"]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Erro ao atualizar configuração: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Método inválido!"]);
}
