<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_PATH = $runtime['base_path'];
$BASE_URL = $runtime['base_url'];
$appJsVersion = @filemtime($BASE_PATH . '/app/assets/js/app.js') ?: time();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php
    require_once $BASE_PATH . '/app/template/header.php';
    $gp = "graficoProfessor.php?v=" . time();
    ?>
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
            /* Espa?amento entre checkboxes */
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
            /* Espa?o entre o checkbox e o texto */
        }

        .checkbox-item input {
            transform: scale(1.2);
            /* Aumenta o tamanho do checkbox */
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
        <?php include __DIR__ . '/menuprofessor.php'; ?>

        <div class="main">
            <?php
            include __DIR__ . '/topoinscrito.php';
            ?>
            <script src="<?php echo rtrim((string)$BASE_URL, '/'); ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>

            <main class="content">
                <div class="container-fluid p-0">

                    <?php // Exibe mensagem vinda da URL
                    // Obter a URL atual
                    $msg = "";
                    $url = $_SERVER['REQUEST_URI'];
                    $urlDecoded = urldecode($url);
                    if (strpos($urlDecoded, '=') !== false) {
                        $params = explode('&', $urlDecoded);
                        $tipomsg = explode('?', $params[0])[1]; // Pega o valor ap?f?????T?f?????,????f???,?s?f??s?,?s o '?'
                        $tipomsg = explode('=', $tipomsg)[0]; // Pega o valor antes do '='

                        $msg = explode('=', $params[0])[1]; // Pega o valor ap?f?????T?f?????,????f???,?s?f??s?,?s o '='
                        // Extrair o valor do par?metro "msg" (assumindo que ele seja o primeiro)
                        $msg = explode('=', $params[0])[1]; // Pega o valor ap?f?????T?f?????,????f???,?s?f??s?,?s o '='
                    }

                    // Exibir a mensagem como vari?f?????T?f?????,????f???,?s?f??s?,?vel
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
                        <h1 class="mb-4">Pesquisa Professores Digitais 2025.1 Beberibe</h1>
                        <div class="card">
                            <div class="card-body">
                                <h3 class="mb-4">Abaixo est?f?????T?f?????,????f???,?s?f??s?,?o as principais dificuldades pesquisadas junto ? professores. Analise cada quest?f?????T?f?????,????f???,?s?f??s?,?o e indique qual seu n?vel de dificuldade em cada uma.</h3>
                                <form id="formulario" action="<?php echo rtrim((string)$BASE_URL, '/'); ?>/quizz/avaliacao-professor/" method="POST">

                                    <!-- Gest?f?????T?f?????,????f???,?s?f??s?,?o de Tempo -->
                                    <h4>1. Gest?f?????T?f?????,????f???,?s?f??s?,?o de Tempo</h4>
                                    <div class="mb-4">
                                        <label class="form-label">a. Dividir o tempo entre preparar aulas, corrigir atividades, planejar avalia?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?es e desenvolver materiais.</label>
                                        <div>
                                            <input type="radio" name="gestao_tempo_a" value="1" required> N?f?????T?f?????,????f???,?s?f??s?,?o se aplica ? mim<br>
                                            <input type="radio" name="gestao_tempo_a" value="2"> N?f?????T?f?????,????f???,?s?f??s?,?o tenho dificuldade<br>
                                            <input type="radio" name="gestao_tempo_a" value="3"> Pouca dificuldade<br>
                                            <input type="radio" name="gestao_tempo_a" value="4"> M?dia dificuldade<br>
                                            <input type="radio" name="gestao_tempo_a" value="5"> Grande dificuldade<br>
                                            <input type="radio" name="gestao_tempo_a" value="6"> N?f?????T?f?????,????f???,?s?f??s?,?o consigo
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">b. Lidar com demandas de ?f?????T?f?????,????f???,?s?f??s?,?ltima hora ou com prazos apertados.</label>
                                        <div>
                                            <input type="radio" name="gestao_tempo_b" value="1" required> N?f?????T?f?????,????f???,?s?f??s?,?o se aplica ? mim<br>
                                            <input type="radio" name="gestao_tempo_b" value="2"> N?f?????T?f?????,????f???,?s?f??s?,?o tenho dificuldade<br>
                                            <input type="radio" name="gestao_tempo_b" value="3"> Pouca dificuldade<br>
                                            <input type="radio" name="gestao_tempo_b" value="4"> M?dia dificuldade<br>
                                            <input type="radio" name="gestao_tempo_b" value="5"> Grande dificuldade<br>
                                            <input type="radio" name="gestao_tempo_b" value="6"> N?f?????T?f?????,????f???,?s?f??s?,?o consigo
                                        </div>
                                    </div>

                                    <!-- Individualiza?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o do Ensino -->
                                    <h4>2. Individualiza?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o do Ensino</h4>
                                    <div class="mb-4">
                                        <label class="form-label">a. Lidar com alunos com diferentes dificuldades (como autismo, TDAH, discalculia) e aplicar metodologias personalizadas.</label>
                                        <div>
                                            <input type="radio" name="individualizacao_ensino_a" value="1" required> N?f?????T?f?????,????f???,?s?f??s?,?o se aplica ? mim<br>
                                            <input type="radio" name="individualizacao_ensino_a" value="2"> N?f?????T?f?????,????f???,?s?f??s?,?o tenho dificuldade<br>
                                            <input type="radio" name="individualizacao_ensino_a" value="3"> Pouca dificuldade<br>
                                            <input type="radio" name="individualizacao_ensino_a" value="4"> M?dia dificuldade<br>
                                            <input type="radio" name="individualizacao_ensino_a" value="5"> Grande dificuldade<br>
                                            <input type="radio" name="individualizacao_ensino_a" value="6"> N?f?????T?f?????,????f???,?s?f??s?,?o consigo
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">b. N?f?????T?f?????,????f???,?s?f??s?,?o ter acesso ? treinamentos especializados ou capacita?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?es espec?ficas, de acordo com a necessidade.</label>
                                        <div>
                                            <input type="radio" name="individualizacao_ensino_b" value="1" required> N?f?????T?f?????,????f???,?s?f??s?,?o se aplica ? mim<br>
                                            <input type="radio" name="individualizacao_ensino_b" value="2"> N?f?????T?f?????,????f???,?s?f??s?,?o tenho dificuldade<br>
                                            <input type="radio" name="individualizacao_ensino_b" value="3"> Pouca dificuldade<br>
                                            <input type="radio" name="individualizacao_ensino_b" value="4"> M?dia dificuldade<br>
                                            <input type="radio" name="individualizacao_ensino_b" value="5"> Grande dificuldade<br>
                                            <input type="radio" name="individualizacao_ensino_b" value="6"> N?f?????T?f?????,????f???,?s?f??s?,?o consigo
                                        </div>
                                    </div>

                                    <!-- Cria?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o de Material Did?tico -->
                                    <h4>3. Cria?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o de Material Did?tico</h4>
                                    <div class="mb-4">
                                        <label class="form-label">a. Criar slides, atividades, mapas mentais, charadas, exemplos contextualizados e outros materiais pedag?gicos.</label>
                                        <div>
                                            <input type="radio" name="material_didatico_a" value="1" required> N?f?????T?f?????,????f???,?s?f??s?,?o se aplica ? mim<br>
                                            <input type="radio" name="material_didatico_a" value="2"> N?f?????T?f?????,????f???,?s?f??s?,?o tenho dificuldade<br>
                                            <input type="radio" name="material_didatico_a" value="3"> Pouca dificuldade<br>
                                            <input type="radio" name="material_didatico_a" value="4"> M?dia dificuldade<br>
                                            <input type="radio" name="material_didatico_a" value="5"> Grande dificuldade<br>
                                            <input type="radio" name="material_didatico_a" value="6"> N?f?????T?f?????,????f???,?s?f??s?,?o consigo
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">b. Falta de recursos ou materiais prontos, ou mesmo adapt?-los ? necessidade.</label>
                                        <div>
                                            <input type="radio" name="material_didatico_b" value="1" required> N?f?????T?f?????,????f???,?s?f??s?,?o se aplica ? mim<br>
                                            <input type="radio" name="material_didatico_b" value="2"> N?f?????T?f?????,????f???,?s?f??s?,?o tenho dificuldade<br>
                                            <input type="radio" name="material_didatico_b" value="3"> Pouca dificuldade<br>
                                            <input type="radio" name="material_didatico_b" value="4"> M?dia dificuldade<br>
                                            <input type="radio" name="material_didatico_b" value="5"> Grande dificuldade<br>
                                            <input type="radio" name="material_didatico_b" value="6"> N?f?????T?f?????,????f???,?s?f??s?,?o consigo
                                        </div>
                                    </div>

                                    <!-- Inclus?f?????T?f?????,????f???,?s?f??s?,?o e Acessibilidade -->
                                    <h4>4. Inclus?f?????T?f?????,????f???,?s?f??s?,?o e Acessibilidade</h4>
                                    <div class="mb-4">
                                        <label class="form-label">a. Adaptar aulas e materiais para alunos com defici?ncia (visual, auditiva, motora, intelectual).</label>
                                        <div>
                                            <input type="radio" name="inclusao_acessibilidade_a" value="1" required> N?f?????T?f?????,????f???,?s?f??s?,?o se aplica ? mim<br>
                                            <input type="radio" name="inclusao_acessibilidade_a" value="2"> N?f?????T?f?????,????f???,?s?f??s?,?o tenho dificuldade<br>
                                            <input type="radio" name="inclusao_acessibilidade_a" value="3"> Pouca dificuldade<br>
                                            <input type="radio" name="inclusao_acessibilidade_a" value="4"> M?dia dificuldade<br>
                                            <input type="radio" name="inclusao_acessibilidade_a" value="5"> Grande dificuldade<br>
                                            <input type="radio" name="inclusao_acessibilidade_a" value="6"> N?f?????T?f?????,????f???,?s?f??s?,?o consigo
                                        </div>
                                    </div>

                                    <!-- Engajamento e Criatividade -->
                                    <h4>5. Engajamento e Criatividade</h4>
                                    <div class="mb-4">
                                        <label class="form-label">a. Desenvolver aulas criativas e cativantes, especialmente em disciplinas consideradas dif?ceis ou menos atraentes.</label>
                                        <div>
                                            <input type="radio" name="engajamento_criatividade_a" value="1" required> N?f?????T?f?????,????f???,?s?f??s?,?o se aplica ? mim<br>
                                            <input type="radio" name="engajamento_criatividade_a" value="2"> N?f?????T?f?????,????f???,?s?f??s?,?o tenho dificuldade<br>
                                            <input type="radio" name="engajamento_criatividade_a" value="3"> Pouca dificuldade<br>
                                            <input type="radio" name="engajamento_criatividade_a" value="4"> M?dia dificuldade<br>
                                            <input type="radio" name="engajamento_criatividade_a" value="5"> Grande dificuldade<br>
                                            <input type="radio" name="engajamento_criatividade_a" value="6"> N?f?????T?f?????,????f???,?s?f??s?,?o consigo
                                        </div>
                                    </div>

                                    <!-- Corre??o e Avalia?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o -->
                                    <h4>6. Corre??o e Avalia?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o</h4>
                                    <div class="mb-4">
                                        <label class="form-label">a. Corrigir provas e atividades, planejar recupera?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o e dar feedback personalizado.</label>
                                        <div>
                                            <input type="radio" name="correcao_avaliacao_a" value="1" required> N?f?????T?f?????,????f???,?s?f??s?,?o se aplica ? mim<br>
                                            <input type="radio" name="correcao_avaliacao_a" value="2"> N?f?????T?f?????,????f???,?s?f??s?,?o tenho dificuldade<br>
                                            <input type="radio" name="correcao_avaliacao_a" value="3"> Pouca dificuldade<br>
                                            <input type="radio" name="correcao_avaliacao_a" value="4"> M?dia dificuldade<br>
                                            <input type="radio" name="correcao_avaliacao_a" value="5"> Grande dificuldade<br>
                                            <input type="radio" name="correcao_avaliacao_a" value="6"> N?f?????T?f?????,????f???,?s?f??s?,?o consigo
                                        </div>
                                    </div>

                                    <!-- Infraestrutura e Recursos -->
                                    <h4>7. Infraestrutura e Recursos</h4>
                                    <div class="mb-4">
                                        <label class="form-label">a. Sugest?f?????T?f?????,????f???,?s?f??s?,?es de solu??es criativas para superar limita?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?es de recursos tecnol?gicos ou f?sicos.</label>
                                        <div>
                                            <input type="radio" name="infraestrutura_recursos_a" value="1" required> N?f?????T?f?????,????f???,?s?f??s?,?o se aplica ? mim<br>
                                            <input type="radio" name="infraestrutura_recursos_a" value="2"> N?f?????T?f?????,????f???,?s?f??s?,?o tenho dificuldade<br>
                                            <input type="radio" name="infraestrutura_recursos_a" value="3"> Pouca dificuldade<br>
                                            <input type="radio" name="infraestrutura_recursos_a" value="4"> M?dia dificuldade<br>
                                            <input type="radio" name="infraestrutura_recursos_a" value="5"> Grande dificuldade<br>
                                            <input type="radio" name="infraestrutura_recursos_a" value="6"> N?f?????T?f?????,????f???,?s?f??s?,?o consigo
                                        </div>
                                    </div>

                                    <!-- Press?f?????T?f?????,????f???,?s?f??s?,?o e Expectativas -->
                                    <h4>8. Press?f?????T?f?????,????f???,?s?f??s?,?o e Expectativas</h4>
                                    <div class="mb-4">
                                        <label class="form-label">a. Organizar tarefas para aliviar a carga de trabalho e equilibrar demandas administrativas e pedag?gicas.</label>
                                        <div>
                                            <input type="radio" name="pressao_expectativas_a" value="1" required> N?f?????T?f?????,????f???,?s?f??s?,?o se aplica ? mim<br>
                                            <input type="radio" name="pressao_expectativas_a" value="2"> N?f?????T?f?????,????f???,?s?f??s?,?o tenho dificuldade<br>
                                            <input type="radio" name="pressao_expectativas_a" value="3"> Pouca dificuldade<br>
                                            <input type="radio" name="pressao_expectativas_a" value="4"> M?dia dificuldade<br>
                                            <input type="radio" name="pressao_expectativas_a" value="5"> Grande dificuldade<br>
                                            <input type="radio" name="pressao_expectativas_a" value="6"> N?f?????T?f?????,????f???,?s?f??s?,?o consigo
                                        </div>
                                    </div>

                                    <!-- Equil?brio pessoal e profissional -->
                                    <h4>9. Equil?brio pessoal e profissional</h4>
                                    <div class="mb-4">
                                        <label class="form-label">a. Cumprir e ficar contente com o resultado das demandas da vida pessoal e profissional, sem comprometer a sa?de f?sica ou emocional, conseguindo separar uma de outra.</label>
                                        <div>
                                            <input type="radio" name="equilibrio_pessoal_profissional_a" value="1" required> N?f?????T?f?????,????f???,?s?f??s?,?o se aplica ? mim<br>
                                            <input type="radio" name="equilibrio_pessoal_profissional_a" value="2"> N?f?????T?f?????,????f???,?s?f??s?,?o tenho dificuldade<br>
                                            <input type="radio" name="equilibrio_pessoal_profissional_a" value="3"> Pouca dificuldade<br>
                                            <input type="radio" name="equilibrio_pessoal_profissional_a" value="4"> M?dia dificuldade<br>
                                            <input type="radio" name="equilibrio_pessoal_profissional_a" value="5"> Grande dificuldade<br>
                                            <input type="radio" name="equilibrio_pessoal_profissional_a" value="6"> N?f?????T?f?????,????f???,?s?f??s?,?o consigo
                                        </div>
                                    </div>
                                    <!-- Bot?o de Envio -->
                                    <div class="mt-4">
                                        <button type="submit" id="submitButton" class="btn btn-primary disabled" disabled>Enviar Avalia?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o</button>

                                    </div>
                                </form>
                            </div>
                        </div>
            </main>

            <footer class="footer">
                <?php require_once $BASE_PATH . '/app/template/footer.php'; ?>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('formulario'); // Certifique-se de que o ID est?f?????T?f?????,????f???,?s?f??s?,? correto
            const submitButton = document.getElementById('submitButton');

            // Fun??o para validar os campos required
            function validateForm() {
                // Verifica se todos os campos obrigat?rios est?f?????T?f?????,????f???,?s?f??s?,?o preenchidos e v?lidos
                const allValid = [...form.elements].every(input => {
                    if (input.required) {
                        if (input.type === 'checkbox') {
                            return input.checked; // Para checkboxes
                        }
                        return input.value.trim() !== ''; // Para inputs e textareas
                    }
                    return true;
                });

                // Alterna o estado do bot?o
                if (allValid) {
                    submitButton.disabled = false;
                    submitButton.classList.remove('disabled', 'btn-secondary'); // Remove estilo desabilitado
                    submitButton.classList.add('btn-primary'); // Estilo habilitado
                } else {
                    submitButton.disabled = true;
                    submitButton.classList.add('disabled', 'btn-secondary'); // Adiciona estilo desabilitado
                    submitButton.classList.remove('btn-primary'); // Remove estilo habilitado
                }
            }

            // Adiciona evento de valida?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o em todos os campos do formul?rio
            form.addEventListener('input', validateForm);

            // Desabilita o bot?o no carregamento inicial
            validateForm();
        });
    </script>
</body>

</html>

<!-- Importa?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o do Jquery Mask -->
<!-- include da importa?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o da mascara dos inputs -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.12/jquery.mask.min.js"></script>
<script type="text/javascript">
    $("#telefone, #TelefoneResp1, #WhatsApp, #WhatsAppResp1").mask("(00) 0.0000-0000"); //000 000 0000 eua
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

<!-- Desativa submit para evitar cadastros duplicados -->
<script>
    document.getElementById('submitButton').addEventListener('click', function() {
        const submitButton = document.getElementById('submitButton');
        const form = document.getElementById('formulario');

        // Adiciona o spinner ao bot?o
        submitButton.innerHTML = `
<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
Aguarde...
`;

        // Desabilita o bot?o
        submitButton.disabled = true;

        // Submete o formul?rio
        form.submit();
    });
</script>




