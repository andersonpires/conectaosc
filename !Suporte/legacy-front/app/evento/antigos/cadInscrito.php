<!-- SERVI?O DE INSCRI??O EVENTO 3O SETOR - CADASTRA DADOS DO FORM NO BANCO -->
<?php
// Inclui o arquivo de conexão com o banco de dados
include 'conexao_grava.php';

// Verifica se o método da requisição ? POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura os dados do formul?rio
    $nomeCompleto = $_POST['nomeCompleto'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $dataNascimento = $_POST['dataNascimento'] ?? '';
    $organizacaoSocial = $_POST['organizacaoSocial'] ?? '';
    $cargoFuncao = $_POST['cargoFuncao'] ?? '';
    $enderecoOrganizacao = $_POST['enderecoOrganizacao'] ?? '';
    $motivacaoEvento = $_POST['motivacaoEvento'] ?? '';
    $necessidadesEspeciais = $_POST['necessidadesEspeciais'] ?? '';
    $confirmacaoParticipacao = isset($_POST['confirmacaoParticipacao']) ? 1 : 0;

    $check_email_query = "SELECT COUNT(*) as total FROM InscritosEvento WHERE email = ?";
    $stmt_check = mysqli_prepare($conexao_grava, $check_email_query);
    mysqli_stmt_bind_param($stmt_check, "s", $email);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_bind_result($stmt_check, $total);
    mysqli_stmt_fetch($stmt_check);
    mysqli_stmt_close($stmt_check);

    if ($total > 0) {
        header("Location: formInscrito.php?erro=Esse%20email%20já%20está%20cadastrado%20no%20sistema,%20não%20precisa%20se%20cadastrar%20novamente");
        exit;
    }

    // Prepara a query SQL para inser??o
    $sql = "INSERT INTO InscritosEvento (
                NomeCompleto,
                Email,
                Telefone,
                DataNascimento,
                OrganizacaoSocial,
                CargoOuFuncao,
                EnderecoOrganizacao,
                MotivacaoEvento,
                NecessidadesEspeciais,
                ConfirmacaoParticipacao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Prepara a instru??o
    $stmt = mysqli_prepare($conexao_grava, $sql);

    if ($stmt) {
        // Faz o bind dos par?metros
        mysqli_stmt_bind_param(
            $stmt,
            'sssssssssi',
            $nomeCompleto,
            $email,
            $telefone,
            $dataNascimento,
            $organizacaoSocial,
            $cargoFuncao,
            $enderecoOrganizacao,
            $motivacaoEvento,
            $necessidadesEspeciais,
            $confirmacaoParticipacao
        );

        // Executa a instru??o
        if (mysqli_stmt_execute($stmt)) {
            // Redireciona para a página de sucesso
            header("Location: inscricaosucess.php");
            exit;
        } else {
            // Captura o erro e redireciona para o formul?rio com a mensagem de erro
            $resultado = mysqli_error($conexao_grava);
            header("Location: formInscrito.php?erro=Erro" . urlencode($resultado));
            exit;
        }
    } else {
        // Captura o erro de preparação e redireciona para o formul?rio
        $resultado = mysqli_error($conexao_grava);
        header("Location: formInscrito.php?erro=Erro" . urlencode($resultado));
        exit;
    }
} else {
    // Redireciona para o formul?rio caso o método não seja POST
    header("Location: formInscrito.php?erro=Erro");
    exit;
}
?>

