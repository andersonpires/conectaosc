<?php
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
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

// Atribuindo valores caso existam no $_POST
$IdCurso     = isset($_POST['id']) ? $_POST['id'] : null;
$NomeCurso   = isset($_POST['nomecurso']) ? $_POST['nomecurso'] : null;
$Termo       = isset($_POST['Termo']) ? $_POST['Termo'] : null;
$Projeto     = (isset($_POST['Projeto']) && $_POST['Projeto'] !== '') ? $_POST['Projeto'] : null;
$Programa    = (isset($_POST['Programa']) && $_POST['Programa'] !== '') ? $_POST['Programa'] : null;
$DURA        = isset($_POST['duracao']) ? $_POST['duracao'] : null;
$Duracao     = str_replace(",", ".", $DURA);
$Tipo        = isset($_POST['tipo']) ? $_POST['tipo'] : null;
$CH          = isset($_POST['cargah']) ? $_POST['cargah'] : null;
$CargaHoraria= str_replace(",", ".", $CH);
$Informacoes = isset($_POST['informacoes']) ? $_POST['informacoes'] : null;
$Habil       = isset($_POST['habilitado']) ? $_POST['habilitado'] : null;
$Habilitado  = ($Habil === 'Ativo') ? 1 : 0;
$IdadeMinRaw = isset($_POST['idade-min']) ? trim($_POST['idade-min']) : null;
$IdadeMaxRaw = isset($_POST['idade-max']) ? trim($_POST['idade-max']) : null;
$IdadeMin = ($IdadeMinRaw === '' || $IdadeMinRaw === null) ? null : (int)$IdadeMinRaw;
$IdadeMax = ($IdadeMaxRaw === '' || $IdadeMaxRaw === null) ? null : (int)$IdadeMaxRaw;

// Preparar a query utilizando PDO
$sql = "UPDATE tbCurso 
        SET NomeCurso = ?, Duracao = ?, Tipo = ?, CargaHoraria = ?, Termo = ?, IdProjeto = ?, Programa = ?, Informacoes = ?, Habilitado = ?, IdadeMin = ?, IdadeMax = ? 
        WHERE IdCurso = ?";

$stmt = $pdo->prepare($sql);
$result = $stmt->execute([
    $NomeCurso,
    $Duracao,
    $Tipo,
    $CargaHoraria,
    $Termo,
    $Projeto,
    $Programa,
    $Informacoes,
    $Habilitado,
    $IdadeMin,
    $IdadeMax,
    $IdCurso
]);

if ($stmt->rowCount() > 0) {
    $resultado = "Registro%20alterado%20com%20sucesso";
    $pdo = null;
    header("Location: formCurso.php?msg='$resultado'");
    exit();
} else {
    $resultado = "Registro%20sem%20alteração.";
    $pdo = null;
    header("Location: formCurso.php?erro='$resultado'");
    exit();
}
?>
