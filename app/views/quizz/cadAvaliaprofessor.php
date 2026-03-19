<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_URL = $runtime['base_para_url'];

// Inclui o arquivo de conexao com o banco de dados
include __DIR__ . '/conexao_grava.php';

$base = rtrim((string)$BASE_para_URL, '/');
$formRoute = $base . '/quizz/avaliacao-professor/';
$successRoute = $base . '/quizz/avaliacao-professor/sucesso/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $perguntas = [
        'gestao_tempo_a' => 1,
        'gestao_tempo_b' => 2,
        'individualizacao_ensino_a' => 3,
        'individualizacao_ensino_b' => 4,
        'material_didatico_a' => 5,
        'material_didatico_b' => 6,
        'inclusao_acessibilidade_a' => 7,
        'engajamento_criatividade_a' => 8,
        'correcao_avaliacao_a' => 9,
        'infraestrutura_recursos_a' => 10,
        'pressao_expectativas_a' => 11,
        'equilibrio_pessoal_profissional_a' => 12,
    ];

    $sql = 'INSERT INTO tbap_respostas (IdPergunta, IdOpcao) VALUES (?, ?)';
    $stmt = mysqli_prepare($conexao_grava, $sql);

    if ($stmt) {
        $all_success = true;

        foreach ($perguntas as $key => $idPergunta) {
            if (!isset($_POST[$key])) {
                continue;
            }

            $idOpcao = (int) $_POST[$key];
            mysqli_stmt_bind_param($stmt, 'ii', $idPergunta, $idOpcao);

            if (!mysqli_stmt_execute($stmt)) {
                $all_success = false;
                $resultado = mysqli_error($conexao_grava);
                break;
            }
        }

        if ($all_success) {
            header('Location: ' . $successRoute);
            exit;
        }

        header('Location: ' . $formRoute . '?erro=' . urlencode((string)$resultado));
        exit;
    }

    $resultado = mysqli_error($conexao_grava);
    header('Location: ' . $formRoute . '?erro=' . urlencode((string)$resultado));
    exit;
}

header('Location: ' . $formRoute);
exit;
?>




