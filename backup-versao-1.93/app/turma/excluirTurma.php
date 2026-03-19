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
$IdTurma = isset($_POST['id']) ? $_POST['id'] : null;

// Preparando a statement
$sql = "DELETE FROM `tbTurma` WHERE `IdTurma` = ?";
$stmt = $pdo->prepare($sql);


// Executando a statement
$stmt->bindParam(1, $IdTurma, PDO::PARAM_INT);

try {
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $resultado = "Registro%20excluído%20com%20sucesso";
    } else {
        // Se a exclusão não afetou nenhuma linha, pode ser que o curso não exista ou não haja alteração
        $resultado = "Registro%20sem%20alteração.";
    }
} catch (PDOException $e) {
    // Se ocorrer um erro, provavelmente é por causa da restrição de chave estrangeira
    $resultado = "Essa%20turma%20não%20pode%20ser%20apagada,%20pois%20ela%20já%20foi%20utilizada!";
}
header("Location: formTurma.php?msg='$resultado'");
exit();
?>
