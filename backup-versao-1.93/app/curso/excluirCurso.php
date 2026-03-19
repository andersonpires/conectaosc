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

$IdCurso = isset($_POST['id']) ? $_POST['id'] : null;

$sql = "DELETE FROM tbCurso WHERE IdCurso = ?";
$stmt = $pdo->prepare($sql);

try {
    $stmt->execute([$IdCurso]);

    if ($stmt->rowCount() > 0) {
        $resultado = "Registro%20excluído%20com%20sucesso";
    } else {
        // Se a exclusão não afetou nenhuma linha, pode ser que o curso não exista ou não haja alteração
        $resultado = "Registro%20sem%20alteração.";
    }
} catch (PDOException $e) {
    // Se ocorrer um erro, provavelmente é por causa da restrição de chave estrangeira
    $resultado = "Esse%20curso%20não%20pode%20ser%20apagado,%20pois%20ele%20já%20foi%20utilizado!";
}
header("Location: formCurso.php?msg='$resultado'");
exit();
?>
