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

// Verifica se o método da requisição é POST para atualizar os dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura os dados do formulário para atualização
    $IdInscrito = $_POST['IdInscrito'] ?? '';
    $nomeCompleto = $_POST['nomeCompleto'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $telefone2 = $_POST['tel2'] ?? '';
    $dataNascimento = $_POST['dataNascimento'] ?? '';
    $organizacaoSocial = $_POST['organizacaoSocial'] ?? '';
    $cargoFuncao = $_POST['cargoFuncao'] ?? '';
    $enderecoOrganizacao = $_POST['enderecoOrganizacao'] ?? '';
    $motivacaoEvento = $_POST['motivacaoEvento'] ?? '';
    $necessidadesEspeciais = $_POST['necessidadesEspeciais'] ?? '';
    $confirmacaoParticipacao = isset($_POST['confirmacaoParticipacao']) ? 1 : 0;

    if (!empty($IdInscrito)) {
        try {
            // Prepara a query SQL para atualização
            $sql = "UPDATE InscritosEvento SET
                        NomeCompleto = :nomeCompleto,
                        Email = :email,
                        Telefone = :telefone,
                        Telefone2 = :telefone2,
                        DataNascimento = :dataNascimento,
                        OrganizacaoSocial = :organizacaoSocial,
                        CargoOuFuncao = :cargoFuncao,
                        EnderecoOrganizacao = :enderecoOrganizacao,
                        MotivacaoEvento = :motivacaoEvento,
                        NecessidadesEspeciais = :necessidadesEspeciais,
                        ConfirmacaoParticipacao = :confirmacaoParticipacao
                    WHERE IdInscrito = :IdInscrito";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nomeCompleto' => $nomeCompleto,
                ':email' => $email,
                ':telefone' => $telefone,
                ':telefone2' => $telefone2,
                ':dataNascimento' => $dataNascimento,
                ':organizacaoSocial' => $organizacaoSocial,
                ':cargoFuncao' => $cargoFuncao,
                ':enderecoOrganizacao' => $enderecoOrganizacao,
                ':motivacaoEvento' => $motivacaoEvento,
                ':necessidadesEspeciais' => $necessidadesEspeciais,
                ':confirmacaoParticipacao' => $confirmacaoParticipacao,
                ':IdInscrito' => $IdInscrito
            ]);

            header("Location: alteraInscrito.php?IdInscrito=$IdInscrito&msg=Dados atualizados com sucesso");
            exit;
        } catch (PDOException $e) {
            header("Location: alteraInscrito.php?IdInscrito=$IdInscrito&erro=Erro ao atualizar: " . urlencode($e->getMessage()));
            exit;
        }
    } else {
        header("Location: listaInscritos.php?erro=ID do inscrito não fornecido");
        exit;
    }
}

// Se a requisição for GET, busca os dados do inscrito para preencher o formulário
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['IdInscrito'])) {
    $IdInscrito = $_GET['IdInscrito'];

    try {
        // Prepara a query para buscar os dados do inscrito
        $sql = "SELECT * FROM InscritosEvento WHERE IdInscrito = :IdInscrito";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':IdInscrito', $IdInscrito, PDO::PARAM_INT);
        $stmt->execute();
        $inscrito = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$inscrito) {
            header("Location: listaInscritos.php?erro=Inscrito não encontrado");
            exit;
        }
    } catch (PDOException $e) {
        header("Location: listaInscritos.php?erro=Erro ao buscar inscrito: " . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: listaInscritos.php?erro=ID do inscrito não fornecido");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
<?php require_once $_SESSION['BASE_PATH'] . '/template/header.php'; ?>
    <style>
        .tooltip .tooltip-inner {
            background-color: #007bff;
            color: #ffffff;
        }

        .img-cover {
            object-fit: cover;
            object-position: center;
        }

        .checkbox-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            /* Espaçamento entre checkboxes */
            justify-content: center;
            /* Centraliza horizontalmente */
            align-items: center;
            /* Centraliza verticalmente */
            margin: 20px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 10px;
            /* Espaço entre o checkbox e o texto */
        }

        .checkbox-item input {
            transform: scale(1.2);
            /* Aumenta o tamanho do checkbox */
        }

        .banner-container {
            width: 100%;
            /* A div mãe ocupa toda a largura */
            overflow: hidden;
            /* Garante que o conteúdo cortado não "escape" */
            height: 144px;
            /* Limita a altura da div mãe a 144px */
        }

        .responsive-banner {
            width: 100%;
            /* Ocupa toda a largura da div mãe */
            height: 100%;
            /* Adapta-se à altura da div mãe */
            object-fit: cover;
            /* Faz a imagem preencher e cortar */
            object-position: left center;
            /* Prioriza o lado esquerdo da imagem */
        }

        @media (max-width: 540px) {
            .banner-container {
                height: 144px;
                /* Mantém a altura fixa de 144px mesmo em telas menores */
            }

            .responsive-banner {
                object-position: left center;
                /* Mantém o corte do lado direito, priorizando o lado esquerdo */
            }
        }
    </style>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

</head>

<script src="//code.jquery.com/jquery-3.2.1.min.js"></script>

<body>
    <div class="wrapper">
    <?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>

        <div class="main">
            <?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php'; ?>

            <div class="banner-container">
                <img class="responsive-banner" src="<?php echo $_SESSION['BASE_URL']; ?>/assets/img/banners/inscricao3osetor.jpg" alt="banner evento">
            </div>
            <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>

            <div class="container mt-5">
                <h2 class="mb-4">Alterar Dados da Inscrição:</h2>

                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
                <?php elseif (isset($_GET['erro'])): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['erro']); ?></div>
                <?php endif; ?>
                <form action="alteraInscrito.php" method="POST">
                    <input type="hidden" name="IdInscrito" value="<?php echo htmlspecialchars($inscrito['IdInscrito']); ?>">

                    <div class="card shadow-lg">
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="nomeCompleto" class="form-label">Nome Completo*</label>
                                <input type="text" class="form-control" id="nomeCompleto" name="nomeCompleto" maxlength="150" value="<?php echo htmlspecialchars($inscrito['NomeCompleto']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail*</label>
                                <input type="email" class="form-control" id="email" name="email" maxlength="100" value="<?php echo htmlspecialchars($inscrito['Email']); ?>" required>
                            </div>

                            <div class="row mb-3">
                                <!-- Telefone -->
                                <div class="mb-3 col-6">
                                    <label for="telefone" class="form-label">Telefone (WhatsApp)*</label>
                                    <input type="tel" class="form-control" id="telefone" name="telefone" maxlength="16" value="<?php echo htmlspecialchars($inscrito['Telefone']); ?>" required>
                                </div>

                                <!-- Telefone2 -->
                                <div class="mb-3 col-6">
                                    <label for="tel2" class="form-label">Telefone 2</label>
                                    <input type="tel" class="form-control" id="tel2" name="tel2" maxlength="16" value="<?php echo htmlspecialchars($inscrito['Telefone2']); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <!-- Data de Nascimento -->
                                <div class="mb-3 col-6">
                                    <label for="dataNascimento" class="form-label">Data de Nascimento*</label>
                                    <input type="date" class="form-control" id="dataNascimento" name="dataNascimento" value="<?php echo htmlspecialchars($inscrito['DataNascimento']); ?>" required>

                                </div>
                                <!-- Organização Social -->
                                <div class="mb-3 col-6">
                                    <label for="organizacaoSocial" class="form-label">Organização Social que Representa</label>
                                    <input type="text" class="form-control" id="organizacaoSocial" name="organizacaoSocial" maxlength="100" value="<?php echo htmlspecialchars($inscrito['OrganizacaoSocial']); ?>">
                                    </div>
                            </div>

                            <div class="mb-3">
                                <label for="cargoFuncao" class="form-label">Cargo ou Função na Organização</label>
                                <input type="text" class="form-control" id="cargoFuncao" name="cargoFuncao" maxlength="50" value="<?php echo htmlspecialchars($inscrito['CargoOuFuncao']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="enderecoOrganizacao" class="form-label">Endereço da Organização</label>
                                <input type="text" class="form-control" id="enderecoOrganizacao" name="enderecoOrganizacao" maxlength="150" value="<?php echo htmlspecialchars($inscrito['EnderecoOrganizacao']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="motivacaoEvento" class="form-label">Motivação para Participar do Evento*</label>
                                <textarea class="form-control" id="motivacaoEvento" name="motivacaoEvento" rows="4" maxlength="500"><?php echo htmlspecialchars($inscrito['MotivacaoEvento']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="necessidadesEspeciais" class="form-label">Necessidades Especiais</label>
                                <textarea class="form-control" id="necessidadesEspeciais" name="necessidadesEspeciais" rows="3" maxlength="300"><?php echo htmlspecialchars($inscrito['NecessidadesEspeciais']); ?></textarea>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="confirmacaoParticipacao" name="confirmacaoParticipacao" <?php echo $inscrito['ConfirmacaoParticipacao'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="confirmacaoParticipacao">
                                    Confirmo minha participação no evento*
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        </div>
                    </div>
                </form>
            </div>

            <footer class="footer">
            <?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php'; ?>
            </footer>
        </div>
    </div>
</body>

</html>
<script>
    function viaCEP() {

        var numCep = $("#cep").val();

        var url = "https://viacep.com.br/ws/" + numCep + "/json";

        $.ajax({
            url: url,
            type: "get",
            dataType: "json",

            success: function(dados) {
                console.log(dados);
                $("#uf").val(dados.uf);
                $("#cidade").val(dados.localidade);
                $("#logradouro").val(dados.logradouro);
                $("#bairro").val(dados.bairro);
            }
        })


    }
</script>
<!-- Importação do Jquery Mask -->
<!-- include da importação da mascara dos inputs -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.12/jquery.mask.min.js"></script>
<script type="text/javascript">
    $("#telefone, #tel2, #WhatsApp, #WhatsAppResp1").mask("(00) 0.0000-0000"); //000 000 0000 eua
    $("#Nascimento").mask('00/00/0000');
    $('.time').mask('00:00:00');
    $('.date_time').mask('00/00/0000 00:00:00');
    $('.cep').mask('00000-000');
    $('.phone').mask('0000-0000');
    $('.phone_with_ddd').mask('(00) 0000-0000');
    $('.phone_us').mask('(000) 000-0000');
    $('.mixed').mask('AAA 000-S0S');
    $('#CPF').mask('000.000.000-00', {
        reverse: true
    });
    $('.cnpj').mask('00.000.000/0000-00', {
        reverse: true
    });
    $('.money').mask('000.000.000.000.000,00', {
        reverse: true
    });
    $('.money2').mask("#.##0,00", {
        reverse: true
    });
    $('.ip_address').mask('0ZZ.0ZZ.0ZZ.0ZZ', {
        translation: {
            'Z': {
                pattern: /[0-9]/,
                optional: true
            }
        }
    });
    $('.ip_address').mask('099.099.099.099');
    $('.percent').mask('##0,00%', {
        reverse: true
    });
    $('.clear-if-not-match').mask("00/00/0000", {
        clearIfNotMatch: true
    });
    $('.placeholder').mask("00/00/0000", {
        placeholder: "__/__/____"
    });
    $('.fallback').mask("00r00r0000", {
        translation: {
            'r': {
                pattern: /[\/]/,
                fallback: '/'
            },
            placeholder: "__/__/____"
        }
    });
    $('.selectonfocus').mask("00/00/0000", {
        selectOnFocus: true
    });
</script>


<script type="text/javascript">
    $("#CPF").keydown(function() {
        try {
            $("#CPF").unmask();
        } catch (e) {}

        var tamanho = $("#CPF").val().length;

        if (tamanho < 11) {
            $("#CPF").mask("999.999.999-99");
        }

        // ajustando foco
        var elem = this;
        setTimeout(function() {
            // mudo a posição do seletor
            elem.selectionStart = elem.selectionEnd = 10000;
        }, 0);
        // reaplico o valor para mudar o foco
        var currentValue = $(this).val();
        $(this).val('');
        $(this).val(currentValue);
    });
</script>
<script type="text/javascript">
    $("#CpfResp1").keydown(function() {
        try {
            $("#CpfResp1").unmask();
        } catch (e) {}

        var tamanho = $("#CpfResp1").val().length;

        if (tamanho < 11) {
            $("#CpfResp1").mask("999.999.999-99");
        } else {
            $("#CpfResp1").mask("99.999.999/9999-99");
        }

        // ajustando foco
        var elem = this;
        setTimeout(function() {
            // mudo a posição do seletor
            elem.selectionStart = elem.selectionEnd = 10000;
        }, 0);
        // reaplico o valor para mudar o foco
        var currentValue = $(this).val();
        $(this).val('');
        $(this).val(currentValue);
    });
</script>