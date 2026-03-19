<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$appJsVersion = @filemtime($BASE_para_PATH . '/app/assets/js/app.js') ?: time();

// Verifica se as variáveis de sessão BASE_para_PATH e BASE_para_URL estão definidas
if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    // Salva a URL atual para redirecionar o usuário após o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endereço atual
    header("Location: " . rtrim((string)$BASE_para_URL, '/') . "/login/?erro=" . urlencode("Ocorreu um erro! Talvez você tenha perdido sua última ação. Verifique."));
    exit(); // Garante que o código abaixo não será executado
}
require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
?>
<?php if (isset($_GET['erro']) && $_GET['erro'] === 'Esse email já está cadastrado no sistema, não precisa se cadastrar novamente'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.querySelector('#email'); // Altere para o ID do campo de e-mail
            emailField.setAttribute('data-bs-toggle', 'tooltip');
            emailField.setAttribute('data-bs-placement', 'right');
            emailField.setAttribute('title', 'Erro: Esse email já está cadastrado no sistema, não precisa se cadastrar novamente');
            const tooltip = new bootstrap.Tooltip(emailField);
            tooltip.show();

            setTimeout(() => {
                tooltip.dispose();
            }, 15000);
        });
    </script>
<?php endif; ?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
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
    <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
            <div class="banner-container">
                <img class="responsive-banner" src="<?php echo $BASE_para_URL; ?>/assets/img/banners/inscricao3osetor.jpg" alt="banner evento">
            </div>
            <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>

            <main class="content">
                <div class="container-fluid p-0">

                    <?php // Exibe mensagem vinda da URL
                    $gp = "graficoProfessor.php?v=" . time();

                    // Obter a URL atual
                    $msg = "";
                    $url = $_SERVER['REQUEST_URI'];
                    $urlDecoded = urldecode($url);
                    if (strpos($urlDecoded, '=') !== false) {
                        $params = explode('&', $urlDecoded);
                        $tipomsg = explode('?', $params[0])[1]; // Pega o valor após o '?'
                        $tipomsg = explode('=', $tipomsg)[0]; // Pega o valor antes do '='

                        $msg = explode('=', $params[0])[1]; // Pega o valor após o '='
                        // Extrair o valor do par?metro "msg" (assumindo que ele seja o primeiro)
                        $msg = explode('=', $params[0])[1]; // Pega o valor após o '='
                    }

                    // Exibir a mensagem como vari?vel
                    if ($msg != "") {
                        if ($tipomsg == "msg") {
                    ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $msg ?>
                            </div>
                        <?php
                        } elseif ($tipomsg == "erro") {
                        ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $msg ?>
                            </div>
                    <?php
                        }
                    }
                    ?>

                    <div class="container mt-5">
                        <h2 class="mb-4">Inscreva-se para o workshop:</h2>
                        <form id="formulario" action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/eventos/inscricao/" method="POST">

                            <div class="card shadow-lg">
                                <div class="card-body">
                                    <!-- Nome Completo -->
                                    <span style="font-style: italic;">Os itens com asterisco (*) são de preenchimento obrigatório.</span>
                                    <p></p>
                                    <div class="mb-3">
                                        <label for="nomeCompleto" class="form-label">Nome Completo*</label>
                                        <input type="text" class="form-control" id="nomeCompleto" name="nomeCompleto" maxlength="150" required>
                                    </div>

                                    <!-- Email -->
                                    <div class="mb-3">
                                        <label for="email" class="form-label">E-mail*</label>
                                        <input type="email" class="form-control" id="email" name="email" maxlength="100" required>
                                        <div id="emailError" class="invalid-feedback"></div> <!-- Mensagem de erro -->

                                    </div>
                                    <div class="row mb-3">
                                        <!-- Telefone -->
                                        <div class="mb-3 col-6">
                                            <label for="telefone" class="form-label">Telefone (WhatsApp)*</label>
                                            <input type="tel" class="form-control" id="telefone" name="telefone" maxlength="16" required>
                                            <div id="telefoneError" class="invalid-feedback"></div> <!-- Mensagem de erro -->
                                        </div>

                                        <!-- Telefone2 -->
                                        <div class="mb-3 col-6">
                                            <label for="tel2" class="form-label">Telefone</label>
                                            <input type="tel" class="form-control" id="tel2" name="tel2" maxlength="16">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <!-- Data de Nascimento -->
                                        <div class="mb-3 col-6">
                                            <label for="dataNascimento" class="form-label">Data de Nascimento*</label>
                                            <input type="date" class="form-control" id="dataNascimento" name="dataNascimento" required>
                                        </div>
                                        <!-- Organizacao Social -->
                                        <div class="mb-3 col-6">
                                            <label for="organizacaoSocial" class="form-label">Organização Social que Representa</label>
                                            <input type="text" class="form-control" id="organizacaoSocial" name="organizacaoSocial" maxlength="100">
                                        </div>
                                    </div>
                                    <!-- Cargo ou Funcao -->
                                    <div class="mb-3">
                                        <label for="cargoFuncao" class="form-label">Cargo ou Função na Organização</label>
                                        <input type="text" class="form-control" id="cargoFuncao" name="cargoFuncao" maxlength="50">
                                    </div>

                                    <!-- Endereco da Organizacao -->
                                    <div class="mb-3">
                                        <label for="enderecoOrganizacao" class="form-label">Endereço da Organização (Bairro/Cidade/Estado)</label>
                                        <input type="text" class="form-control" id="enderecoOrganizacao" name="enderecoOrganizacao" maxlength="150">
                                    </div>

                                    <!-- Motivacao para Participar -->
                                    <div class="mb-3">
                                        <label for="motivacaoEvento" class="form-label">Motivação para Participar do Evento*</label>
                                        <textarea class="form-control" id="motivacaoEvento" name="motivacaoEvento" rows="4" maxlength="500" required></textarea>
                                    </div>

                                    <!-- Necessidades Especiais -->
                                    <div class="mb-3">
                                        <label for="necessidadesEspeciais" class="form-label">Necessidades Especiais</label>
                                        <textarea class="form-control" id="necessidadesEspeciais" name="necessidadesEspeciais" rows="3" maxlength="300"></textarea>
                                    </div>

                                    <!-- Confirmação de participação -->
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="confirmacaoParticipacao" name="confirmacaoParticipacao" required>
                                        <label class="form-check-label" for="confirmacaoParticipacao">
                                            Confirmo minha participação no evento*
                                        </label>
                                    </div>
                                    <button type="submit" id="submitButton" class="btn btn-primary disabled" disabled>Cadastrar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>

            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById("formulario");
            const submitButton = document.getElementById("submitButton");
            const emailField = document.getElementById("email");
            const telefoneField = document.getElementById("telefone");
            const emailError = document.getElementById("emailError");
            const telefoneError = document.getElementById("telefoneError");

            // Verifica se o e-mail já existe no banco de dados
            async function checkEmailExists(email) {
                try {
                    const response = await fetch("<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/eventos/verifica-email/?email=" + encodeURIComponent(email));
                    const data = await response.json();
                    return data.exists;
                } catch (error) {
                    console.error("Erro ao verificar e-mail:", error);
                    return false;
                }
            }

            // Função para validar os campos obrigatórios e ativar/desativar botão de envio
            async function validateFields() {
                let allValid = true;

                // Verifica se todos os campos obrigatórios foram preenchidos
                document.querySelectorAll("[required]").forEach(field => {
                    if (!field.value.trim()) {
                        allValid = false;
                        field.classList.add("is-invalid");
                    } else {
                        field.classList.remove("is-invalid");
                    }
                });

                // Verifica formato do e-mail
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailField.value.trim())) {
                    allValid = false;
                    emailField.classList.add("is-invalid");
                    emailError.innerText = "Digite um e-mail válido.";
                } else {
                    emailField.classList.remove("is-invalid");
                    emailError.innerText = "";
                }

                // Verifica se o e-mail já está cadastrado
                if (emailField.value.trim()) {
                    const emailExists = await checkEmailExists(emailField.value.trim());
                    if (emailExists) {
                        allValid = false;
                        emailField.classList.add("is-invalid");
                        emailError.innerText = "Esse e-mail já está cadastrado no sistema.";
                    }
                }

                // Verifica se o telefone contém apenas números e tem no mínimo 10 dígitos
                const telefoneRegex = /^[0-9]{10,}$/;
                const telefoneSomenteNumeros = telefoneField.value.replace(/\D/g, ""); // Remove tudo que não ? número
                if (!telefoneRegex.test(telefoneSomenteNumeros)) {
                    allValid = false;
                    telefoneField.classList.add("is-invalid");
                    telefoneError.innerText = "O telefone deve ter pelo menos 10 números.";
                } else {
                    telefoneField.classList.remove("is-invalid");
                    telefoneError.innerText = "";
                }

                // Se tudo estiver correto, habilita o botão, senão desabilita
                submitButton.disabled = !allValid;
                submitButton.classList.toggle("disabled", !allValid);
            }

            // Executa validação ao preencher os campos
            document.querySelectorAll("[required]").forEach(field => {
                field.addEventListener("input", validateFields);
            });

            // Validação final no envio do formulário
            form.addEventListener("submit", async function(event) {
                event.preventDefault(); // Impede envio inicial
                await validateFields(); // Confirma que os campos estão corretos

                if (submitButton.disabled) {
                    alert("Por favor, corrija os erros antes de enviar.");
                    return;
                }

                // Se tudo estiver correto, exibe spinner e envia
                submitButton.disabled = true;
                submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Enviando...`;
                form.submit();
            });

        });
    </script>





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
<!-- Importação do jQuery Mask -->
<!-- Include da importação da máscara dos inputs -->
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


<!-- Desativa submit para evitar cadastros duplicados -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script src="//code.jquery.com/jquery-3.2.1.min.js"></script>








