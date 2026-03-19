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

// Verifica se o método da requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura os dados do formulário
    $nomeCompleto = $_POST['nomeCompleto'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $tel2 = $_POST['tel2'] ?? '';
    $dataNascimento = $_POST['dataNascimento'] ?? '';
    $organizacaoSocial = $_POST['organizacaoSocial'] ?? '';
    $cargoFuncao = $_POST['cargoFuncao'] ?? '';
    $enderecoOrganizacao = $_POST['enderecoOrganizacao'] ?? '';
    $motivacaoEvento = $_POST['motivacaoEvento'] ?? '';
    $necessidadesEspeciais = $_POST['necessidadesEspeciais'] ?? '';
    $confirmacaoParticipacao = isset($_POST['confirmacaoParticipacao']) ? 1 : 0;

    try {
        // Verifica se o e-mail já está cadastrado
        $check_email_query = "SELECT COUNT(*) FROM InscritosEvento WHERE Email = :email";
        $stmt_check = $pdo->prepare($check_email_query);
        $stmt_check->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt_check->execute();
        $total = $stmt_check->fetchColumn();

        if ($total > 0) {
            header("Location: formInscrito.php?erro=Esse%20email%20já%20está%20cadastrado%20no%20sistema,%20não%20precisa%20se%20cadastrar%20novamente");
            exit;
        }

        // Prepara a query SQL para inserção
        $sql = "INSERT INTO InscritosEvento (
                    NomeCompleto,
                    Email,
                    Telefone,
                    Telefone2,
                    DataNascimento,
                    OrganizacaoSocial,
                    CargoOuFuncao,
                    EnderecoOrganizacao,
                    MotivacaoEvento,
                    NecessidadesEspeciais,
                    ConfirmacaoParticipacao
                ) VALUES (
                    :nomeCompleto, 
                    :email, 
                    :telefone, 
                    :tel2,
                    :dataNascimento, 
                    :organizacaoSocial, 
                    :cargoFuncao, 
                    :enderecoOrganizacao, 
                    :motivacaoEvento, 
                    :necessidadesEspeciais, 
                    :confirmacaoParticipacao
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nomeCompleto' => $nomeCompleto,
            ':email' => $email,
            ':telefone' => $telefone,
            ':tel2' => $tel2,
            ':dataNascimento' => $dataNascimento,
            ':organizacaoSocial' => $organizacaoSocial,
            ':cargoFuncao' => $cargoFuncao,
            ':enderecoOrganizacao' => $enderecoOrganizacao,
            ':motivacaoEvento' => $motivacaoEvento,
            ':necessidadesEspeciais' => $necessidadesEspeciais,
            ':confirmacaoParticipacao' => $confirmacaoParticipacao
        ]);

        // Redireciona para a página de sucesso
        header("Location: inscricaosucess.php");
        exit;

    } catch (PDOException $e) {
        // Captura o erro e redireciona para o formulário com a mensagem de erro
        header("Location: formInscrito.php?erro=" . urlencode("Erro ao cadastrar: " . $e->getMessage()));
        exit;
    }
} else {
    // Redireciona para o formulário caso o método não seja POST
    header("Location: formInscrito.php?erro=Método%20inválido");
    exit;
}
