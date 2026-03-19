<?php
session_set_cookie_params(['httponly' => true]);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

// Verifica se as variáveis de sessão BASE_PATH e BASE_URL estão definidas
if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?erro=Ocorreu%20um%20erro!%20Talvez%20voce%20tenha%20perdido%20sua%20ultima%20acao.%20Verifique.");
    exit();
}
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

$nomesTurmaPost = isset($_POST['NomeTurma']) ? $_POST['NomeTurma'] : [];
$idCurso = isset($_POST['IdCurso']) ? (int)$_POST['IdCurso'] : 0;
$municipio = isset($_POST['Municipio']) ? $_POST['Municipio'] : null;
$local = isset($_POST['Local']) ? $_POST['Local'] : null;
$obs = isset($_POST['Obs']) ? $_POST['Obs'] : null;
$maxMatriculasRaw = isset($_POST['max-matriculas']) ? trim($_POST['max-matriculas']) : null;
$maxMatriculas = ($maxMatriculasRaw === '' || $maxMatriculasRaw === null) ? null : (int)$maxMatriculasRaw;

if (!is_array($nomesTurmaPost)) {
    $nomesTurmaPost = [$nomesTurmaPost];
}

$nomesTurma = [];
foreach ($nomesTurmaPost as $nome) {
    $nomeLimpo = trim((string)$nome);
    if ($nomeLimpo !== '') {
        $nomesTurma[] = $nomeLimpo;
    }
}

if ($idCurso <= 0) {
    $resultado = "Selecione%20um%20curso%20valido.";
    header("Location: formTurma.php?erro=$resultado");
    exit();
}

if (empty($nomesTurma)) {
    $resultado = "Informe%20ao%20menos%20um%20nome%20de%20turma.";
    header("Location: formTurma.php?erro=$resultado");
    exit();
}

$sql = "INSERT INTO tbTurma (NomeTurma, IdCurso, Municipio, Local, Obs, MaxMatriculas) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);

$cadastrosRealizados = 0;

foreach ($nomesTurma as $nomeTurma) {
    $stmt->bindParam(1, $nomeTurma, PDO::PARAM_STR);
    $stmt->bindParam(2, $idCurso, PDO::PARAM_INT);
    $stmt->bindParam(3, $municipio, PDO::PARAM_STR);
    $stmt->bindParam(4, $local, PDO::PARAM_STR);
    $stmt->bindParam(5, $obs, PDO::PARAM_STR);
    $stmt->bindValue(6, $maxMatriculas, $maxMatriculas === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->execute();
    $cadastrosRealizados += $stmt->rowCount();
}

if ($cadastrosRealizados > 0) {
    $resultado = $cadastrosRealizados . "%20turma(s)%20cadastrada(s)%20com%20sucesso";
    header("Location: formTurma.php?msg=$resultado");
} else {
    $resultado = "Erro%20ao%20cadastrar%20turmas.";
    header("Location: formTurma.php?erro=$resultado");
}
exit();
?>