<?php
// Inicialização da sessão e configuração de timezone
session_set_cookie_params(['httponly' => true]);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

// Verifica se as variáveis de sessão BASE_PATH e BASE_URL estão definidas
if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    // Salva a URL atual para redirecionar o usuário após o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endereço atual
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?erro=Ocorreu%20um%20erro!%20Talvez%20você%20tenha%20perdido%20sua%20última%20ação.%20Verifique."); // Redireciona para o login com o endereço de volta via GET
    exit(); // Garante que o código abaixo não será executado
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

// Atribuindo valores do $_POST (se existirem)
$IdUsuario     = isset($_POST['idaluno']) ? $_POST['idaluno'] : null;
$IdMatricula   = isset($_POST['idmatricula']) ? $_POST['idmatricula'] : null;
$IdCurso       = isset($_POST['curso']) ? $_POST['curso'] : null;
$IdTurma       = isset($_POST['idturma']) ? $_POST['idturma'] : null;
$datamatricula = isset($_POST['datamatricula']) ? $_POST['datamatricula'] : null;
$ativo = isset($_GET['ativo']) ? $_GET['ativo'] : 1;

$sql = "UPDATE tbMatricula AS t1
        SET t1.IdCurso = ?, t1.IdTurma = ?, t1.vData = ?
        WHERE t1.IdMatricula = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$IdCurso, $IdTurma, $datamatricula, $IdMatricula]);

if ($stmt->rowCount() > 0) {
    $resultado = "Registro alterado com sucesso";
    $pdo = null;
    header("Location: listTurmasMat.php?msg=" . urlencode($resultado) . "&turma=" . $IdTurma . "&ativo=" . $ativo);
    exit();
} else {
    $resultado = "Erro: " . implode(" ", $stmt->errorInfo());
    $pdo = null;
    header("Location: listTurmasMat.php?erro=" . urlencode($resultado) . "&turma=" . $IdTurma . "&ativo=" . $ativo);
    exit();
}
?>
