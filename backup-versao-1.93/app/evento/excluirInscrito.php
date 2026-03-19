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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['IdInscrito'])) {
    $idInscrito = (int) $_POST['IdInscrito']; // Converte para inteiro por segurança

    try {
        // Prepara a consulta SQL para exclusão
        $sql = "DELETE FROM InscritosEvento WHERE IdInscrito = :IdInscrito";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':IdInscrito', $idInscrito, PDO::PARAM_INT);

        // Executa a consulta
        $stmt->execute();

        // Redireciona após a exclusão
        header("Location: listagemInscritos.php?msg=Inscrição%20removida%20com%20sucesso");
        exit;
    } catch (PDOException $e) {
        // Redireciona para a listagem com mensagem de erro
        header("Location: listagemInscritos.php?erro=" . urlencode("Erro ao excluir: " . $e->getMessage()));
        exit;
    }
} else {
    // Redireciona caso a requisição não seja válida
    header("Location: listagemInscritos.php?erro=Requisição%20inválida");
    exit;
}
