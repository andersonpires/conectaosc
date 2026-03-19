<?php
// Verifica se as variáveis de sessão BASE_PATH e BASE_URL estão definidas
if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    // Salva a URL atual para redirecionar o usuário após o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endereço atual
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?erro=Ocorreu%20um%20erro!%20Talvez%20você%20tenha%20perdido%20sua%20última%20ação.%20Verifique."); // Redireciona para o login com o endereço de volta via GET
    exit(); // Garante que o código abaixo não será executado
}
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cursoId = isset($_POST['NomeCurso']) ? intval($_POST['NomeCurso']) : 0;
    $turmaId = isset($_POST['NNomeTurma']) ? intval($_POST['NNomeTurma']) : 0;
    $dataSelecionada = isset($_POST['dataSelecionada']) ? $_POST['dataSelecionada'] : '';

    if ($cursoId > 0 && $turmaId > 0 && !empty($dataSelecionada)) {
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

            echo "</table>";
        } else {
            echo "<p>Nenhuma presença encontrada para os critérios selecionados.</p>";
        }
    } else {
        echo "<p>Por favor, selecione curso, turma e data válidos.</p>";
    }
} else {
    echo "<p>Requisição inválida.</p>";
}
?>
