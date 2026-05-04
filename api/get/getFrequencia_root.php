<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
if (!isset($BASE_para_URL) || !isset($BASE_para_PATH)) {
    header('Location: ' . rtrim((string) $BASE_para_URL, '/') . '/login/');
    exit();
}
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['curso'], $_POST['turma'], $_POST['mesAno'])) {
    $curso = $_POST['curso'];
    $turma = $_POST['turma'];
    $mesAno = $_POST['mesAno'];
    $habilitado = isset($_POST['habilitado']) ? 1 : 0; // 1 se o checkbox foi marcado, 0 caso contrario

    list($ano, $mes) = explode('-', $mesAno);

    $sql = "SELECT a.Nome AS Aluno, c.Dia, c.Mes, c.Ano, c.presenca, c.falta, c.faltajust,
               cu.NomeCurso, t.NomeTurma, a.Habilitado
        FROM tbChamada c
        INNER JOIN tbAluno a ON c.IdAluno = a.IdUsuario
        INNER JOIN tbCurso cu ON c.IdCurso = cu.IdCurso
        INNER JOIN tbTurma t ON c.IdTurma = t.IdTurma";

    // Adiciona a condicao de alunos habilitados se necessario
    if ($habilitado == 1) {
        $sql .= " INNER JOIN tbMatricula m ON c.IdMatricula = m.IdMatricula";
    }

    $sql .= " WHERE c.IdCurso = ? AND c.Mes = ? AND c.Ano = ?";

    // Adiciona a condicao de IdTurma apenas se nao for "todas"
    if ($turma !== "todas") {
        $sql .= " AND c.IdTurma = ?";
    }

    // Adiciona a condicao de alunos habilitados se necessario
    if ($habilitado == 1) {
        $sql .= " AND a.Habilitado = 1";
    }

    $sql .= " AND (c.presenca = 1 OR c.falta = 1 OR c.faltajust = 1) ORDER BY a.Nome, c.Dia";

    // Prepara e executa a query de acordo com os parametros
    $stmt = $pdo->prepare($sql);

    // Executa a query de forma condicional
    if ($turma !== "todas") {
        $stmt->execute([$curso, $mes, $ano, $turma]);
    } else {
        $stmt->execute([$curso, $mes, $ano]);
    }
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($data);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Parametros invalidos']);
}





