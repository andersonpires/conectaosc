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
// Incluir a conexão com o banco de dados
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

// Atribuindo valores caso existam no $_POST
$NomeCurso = isset($_POST['NomeCurso']) ? $_POST['NomeCurso'] : null;
$Termo = isset($_POST['Termo']) ? $_POST['Termo'] : null;
$Projeto = isset($_POST['Projeto']) ? $_POST['Projeto'] : null;
$Programa = isset($_POST['Programa']) ? $_POST['Programa'] : null;
$DURA = isset($_POST['Duracao']) ? $_POST['Duracao'] : null;
$Duracao = str_replace(",", ".", $DURA);
$Tipo = isset($_POST['Tipo']) ? $_POST['Tipo'] : null;
$CH = isset($_POST['CargaHoraria']) ? $_POST['CargaHoraria'] : null;
$CargaHoraria = str_replace(",", ".", $CH);
$Informacoes = isset($_POST['Informacoes']) ? $_POST['Informacoes'] : null;
$Habil = isset($_POST['Habilitado']) ? $_POST['Habilitado'] : null;
$Habilitado = ($Habil === 'Ativo') ? 1 : 0;
$IdadeMinRaw = isset($_POST['idade-min']) ? trim($_POST['idade-min']) : null;
$IdadeMaxRaw = isset($_POST['idade-max']) ? trim($_POST['idade-max']) : null;
$IdadeMin = ($IdadeMinRaw === '' || $IdadeMinRaw === null) ? null : (int)$IdadeMinRaw;
$IdadeMax = ($IdadeMaxRaw === '' || $IdadeMaxRaw === null) ? null : (int)$IdadeMaxRaw;

try {
    // Preparando a query
    $sql = "INSERT INTO tbCurso (NomeCurso, Duracao, Tipo, CargaHoraria, Termo, IdProjeto, Programa, Informacoes, Habilitado, IdadeMin, IdadeMax) 
            VALUES (:NomeCurso, :Duracao, :Tipo, :CargaHoraria, :Termo, :IdProjeto, :Programa, :Informacoes, :Habilitado, :IdadeMin, :IdadeMax)";
    $stmt = $pdo->prepare($sql);

    // Bind dos parâmetros
    $stmt->bindParam(':NomeCurso', $NomeCurso, PDO::PARAM_STR);
    $stmt->bindParam(':Duracao', $Duracao, PDO::PARAM_STR);
    $stmt->bindParam(':Tipo', $Tipo, PDO::PARAM_STR);
    $stmt->bindParam(':CargaHoraria', $CargaHoraria, PDO::PARAM_STR);
    $stmt->bindParam(':Termo', $Termo, PDO::PARAM_STR);
    $stmt->bindParam(':IdProjeto', $Projeto, PDO::PARAM_INT);
    $stmt->bindParam(':Programa', $Programa, PDO::PARAM_STR);
    $stmt->bindParam(':Informacoes', $Informacoes, PDO::PARAM_STR);
    $stmt->bindParam(':Habilitado', $Habilitado, PDO::PARAM_INT);
    $stmt->bindValue(':IdadeMin', $IdadeMin, $IdadeMin === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':IdadeMax', $IdadeMax, $IdadeMax === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

    // Executando a query
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Sucesso ao cadastrar
        $resultado = "Registro%20cadastrado%20com%20sucesso";
        header("Location: formCurso.php?msg='$resultado'");
    } else {
        // Erro ao cadastrar
        $resultado = "Erro%20ao%20cadastrar%20doações.";
        header("Location: formCurso.php?erro='$resultado'");
    }
} catch (PDOException $e) {
    // Caso ocorra um erro na execução da query
    echo "Erro ao executar a consulta: " . $e->getMessage();
}
?>
