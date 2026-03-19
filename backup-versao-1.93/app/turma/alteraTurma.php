<?php
session_set_cookie_params(['httponly' => true]);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: application/json');
// Verifica se as variáveis de sessão BASE_PATH e BASE_URL estão definidas

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    // Salva a URL atual para redirecionar o usuário após o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endereço atual
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?erro=Ocorreu%20um%20erro!%20Talvez%20você%20tenha%20perdido%20sua%20última%20ação.%20Verifique."); // Redireciona para o login com o endereço de volta via GET
    exit(); // Garante que o código abaixo não será executado
}
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

// Habilitar relatórios de erros para depuração
error_reporting(E_ALL);
// ini_set('display_errors', 1);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Atribuindo valores caso existam no $_POST
$IdTurma = isset($_POST['id']) ? $_POST['id'] : null;
$NomeTurma = isset($_POST['nometurma']) ? $_POST['nometurma'] : null;
$IdCurso = isset($_POST['curso']) ? $_POST['curso'] : null;
$Municipio = isset($_POST['municipio']) ? $_POST['municipio'] : null;
$Local = isset($_POST['local']) ? $_POST['local'] : null;
$Habil = isset($_POST['habilitado']) ? $_POST['habilitado'] : null;
If ($Habil == 'Ativo'){
    $Habilitado = true;
} else {
    $Habilitado = false;
}
$MaxMatriculasRaw = isset($_POST['max-matriculas']) ? trim($_POST['max-matriculas']) : null;
$MaxMatriculas = ($MaxMatriculasRaw === '' || $MaxMatriculasRaw === null) ? null : (int)$MaxMatriculasRaw;
$Obs = isset($_POST['obs']) ? $_POST['obs'] : null;

// Preparar e executar a query
$stmt = $pdo->prepare("UPDATE `tbTurma` SET NomeTurma = ?, IdCurso = ?, Municipio = ?, Local = ?, Habilitado = ?, Obs = ?, MaxMatriculas = ? WHERE IdTurma = ?");

$stmt->bindParam(1, $NomeTurma, PDO::PARAM_STR);
$stmt->bindParam(2, $IdCurso, PDO::PARAM_INT);
$stmt->bindParam(3, $Municipio, PDO::PARAM_STR);
$stmt->bindParam(4, $Local, PDO::PARAM_STR);
$stmt->bindParam(5, $Habilitado, PDO::PARAM_INT);
$stmt->bindParam(6, $Obs, PDO::PARAM_STR);
$stmt->bindValue(7, $MaxMatriculas, $MaxMatriculas === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
$stmt->bindParam(8, $IdTurma, PDO::PARAM_INT);
$stmt->execute();

$rowCount = $stmt->rowCount(); 
// Verificando se a query foi executada com sucesso
if ($stmt->rowCount() > 0) {
    $resultado = "Registro%20alterado%20com%20sucesso";
    header("Location: formTurma.php?msg=$resultado");
} else {
    $resultado = "Erro%20ao%20alterar%20registro.";
    header("Location: formTurma.php?msg=$resultado");
}

