<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header('Location: ' . rtrim((string) $BASE_para_URL, '/') . '/login/?redirect=' . $redirect_url);
    exit();
}

require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cursoId = isset($_POST['NomeCurso']) ? (int) $_POST['NomeCurso'] : 0;
    $turmaId = isset($_POST['NNomeTurma']) ? (int) $_POST['NNomeTurma'] : 0;
    $dataSelecionada = isset($_POST['dataSelecionada']) ? (string) $_POST['dataSelecionada'] : '';

    if ($cursoId > 0 && $turmaId > 0 && $dataSelecionada !== '') {
        $query = "SELECT alunos.nome AS aluno, presencas.status AS status 
                  FROM presencas
                  INNER JOIN alunos ON presencas.aluno_id = alunos.id
                  WHERE presencas.curso_id = ? AND presencas.turma_id = ? AND presencas.data = ?";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$cursoId, $turmaId, $dataSelecionada]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) > 0) {
            echo "<table border='1'>
                    <tr>
                        <th>Aluno</th>
                        <th>Status</th>
                    </tr>";

            foreach ($result as $row) {
                echo "<tr>
                        <td>{$row['aluno']}</td>
                        <td>{$row['status']}</td>
                    </tr>";
            }

            echo '</table>';
        } else {
            echo '<p>Nenhuma presenca encontrada para os criterios selecionados.</p>';
        }
    } else {
        echo '<p>Por favor, selecione curso, turma e data validos.</p>';
    }
} else {
    echo '<p>Requisicao invalida.</p>';
}



