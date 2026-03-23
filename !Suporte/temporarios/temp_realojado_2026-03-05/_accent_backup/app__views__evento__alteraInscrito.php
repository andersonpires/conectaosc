<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_PATH = $runtime['base_path'];
$BASE_URL = $runtime['base_url'];

if (!isset($BASE_PATH) || !isset($BASE_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: " . rtrim((string)$BASE_URL, '/') . "/login/?erro=Ocorreu%20um%20erro!%20Talvez%20voce%20tenha%20perdido%20sua%20ultima%20acao.%20Verifique.");
    exit();
}

require_once $BASE_PATH . '/api/repositories/EventoRepository.php';
require_once $BASE_PATH . '/api/services/EventoService.php';

use BackEnd\Repositories\EventoRepository;
use BackEnd\Services\EventoService;

$eventoService = new EventoService(new EventoRepository());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $IdInscrito = (int)($_POST['IdInscrito'] ?? 0);

    if ($IdInscrito > 0) {
        try {
            $eventoService->update($IdInscrito, $_POST);
            header("Location: " . rtrim((string)$BASE_URL, "/") . "/eventos/inscritos/editar/?IdInscrito=$IdInscrito&msg=Dados atualizados com sucesso");
            exit;
        } catch (Throwable $e) {
            header("Location: " . rtrim((string)$BASE_URL, "/") . "/eventos/inscritos/editar/?IdInscrito=$IdInscrito&erro=" . urlencode("Erro ao atualizar: " . $e->getMessage()));
            exit;
        }
    }

    header("Location: " . rtrim((string)$BASE_URL, "/") . "/eventos/inscritos/?erro=" . urlencode("ID do inscrito n?f?????T?f??s?,?o fornecido"));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['IdInscrito'])) {
    $IdInscrito = (int)$_GET['IdInscrito'];

    try {
        $inscrito = $eventoService->findInscrito($IdInscrito);
        if (!$inscrito) {
            header("Location: " . rtrim((string)$BASE_URL, "/") . "/eventos/inscritos/?erro=" . urlencode("Inscrito n?f?????T?f??s?,?o encontrado"));
            exit;
        }
    } catch (Throwable $e) {
        header("Location: " . rtrim((string)$BASE_URL, "/") . "/eventos/inscritos/?erro=" . urlencode("Erro ao buscar inscrito: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: " . rtrim((string)$BASE_URL, "/") . "/eventos/inscritos/?erro=" . urlencode("ID do inscrito n?f?????T?f??s?,?o fornecido"));
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
<?php require_once $BASE_PATH . '/app/template/header.php'; ?>
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
            /* Espacamento entre checkboxes */
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
            /* Espaco entre o checkbox e o texto */
        }

        .checkbox-item input {
            transform: scale(1.2);
            /* Aumenta o tamanho do checkbox */
        }

        .banner-container {
            width: 100%;
            /* A div mae ocupa toda a largura */
            overflow: hidden;
            /* Garante que o conteudo cortado nao "escape" */
            height: 144px;
            /* Limita a altura da div mae a 144px */
        }

        .responsive-banner {
            width: 100%;
            /* Ocupa toda a largura da div mae */
            height: 100%;
            /* Adapta-se a altura da div mae */
            object-fit: cover;
            /* Faz a imagem preencher e cortar */
            object-position: left center;
            /* Prioriza o lado esquerdo da imagem */
        }

        @media (max-width: 540px) {
            .banner-container {
                height: 144px;
                /* Mantem a altura fixa de 144px mesmo em telas menores */
            }

            .responsive-banner {
                object-position: left center;
                /* Mantem o corte do lado direito, priorizando o lado esquerdo */
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
    <?php require_once $BASE_PATH . '/app/template/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_PATH . '/app/template/topo.php'; ?>

            <div class="banner-container">
                <img class="responsive-banner" src="<?php echo $BASE_URL; ?>/assets/img/banners/inscricao3osetor.jpg" alt="banner evento">
            </div>
            <script src="<?php echo $BASE_URL; ?>/assets/js/app.js"></script>

            <div class="container mt-5">
                <h2 class="mb-4">Alterar Dados da Inscricao:</h2>

                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
                <?php elseif (isset($_GET['erro'])): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['erro']); ?></div>
                <?php endif; ?>
                <form action="<?php echo rtrim((string)$BASE_URL, "/"); ?>/eventos/inscritos/editar/" method="POST">
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
                                <!-- Organizacao Social -->
                                <div class="mb-3 col-6">
                                    <label for="organizacaoSocial" class="form-label">Organizacao Social que Representa</label>
                                    <input type="text" class="form-control" id="organizacaoSocial" name="organizacaoSocial" maxlength="100" value="<?php echo htmlspecialchars($inscrito['OrganizacaoSocial']); ?>">
                                    </div>
                            </div>

                            <div class="mb-3">
                                <label for="cargoFuncao" class="form-label">Cargo ou Funcao na Organizacao</label>
                                <input type="text" class="form-control" id="cargoFuncao" name="cargoFuncao" maxlength="50" value="<?php echo htmlspecialchars($inscrito['CargoOuFuncao']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="enderecoOrganizacao" class="form-label">Endereco da Organizacao</label>
                                <input type="text" class="form-control" id="enderecoOrganizacao" name="enderecoOrganizacao" maxlength="150" value="<?php echo htmlspecialchars($inscrito['EnderecoOrganizacao']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="motivacaoEvento" class="form-label">Motivacao para Participar do Evento*</label>
                                <textarea class="form-control" id="motivacaoEvento" name="motivacaoEvento" rows="4" maxlength="500"><?php echo htmlspecialchars($inscrito['MotivacaoEvento']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="necessidadesEspeciais" class="form-label">Necessidades Especiais</label>
                                <textarea class="form-control" id="necessidadesEspeciais" name="necessidadesEspeciais" rows="3" maxlength="300"><?php echo htmlspecialchars($inscrito['NecessidadesEspeciais']); ?></textarea>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="confirmacaoParticipacao" name="confirmacaoParticipacao" <?php echo $inscrito['ConfirmacaoParticipacao'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="confirmacaoParticipacao">
                                    Confirmo minha participacao no evento*
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary">Salvar Alteracoes</button>
                        </div>
                    </div>
                </form>
            </div>

            <footer class="footer">
            <?php require_once $BASE_PATH . '/app/template/footer.php'; ?>
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
<!-- Importacao do Jquery Mask -->
<!-- include da importacao da mascara dos inputs -->
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
            // mudo a posicao do seletor
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
            // mudo a posicao do seletor
            elem.selectionStart = elem.selectionEnd = 10000;
        }, 0);
        // reaplico o valor para mudar o foco
        var currentValue = $(this).val();
        $(this).val('');
        $(this).val(currentValue);
    });
</script>








