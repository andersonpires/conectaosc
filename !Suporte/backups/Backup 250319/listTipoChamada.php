<?php
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400); // 24 horas
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

// Verifica se as variáveis de sessão BASE_para_PATH e BASE_para_URL estão definidas
if (!isset($_SESSION['BASE_para_PATH']) || !isset($_SESSION['BASE_para_URL'])) {
    // Salva a URL atual para redirecionar o usuário após o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endereço atual
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url"); // Redireciona para o login com o endereço de volta via GET
    exit(); // Garante que o código abaixo não será executado
}

require_once $_SESSION['BASE_para_PATH'] . '/checa-token.php';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <?php require_once $_SESSION['BASE_para_PATH'] . '/template/header.php'; ?>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <style>
        #dataSelecionada {
            background-color: #fff;
            /* Cor de fundo */
            border: 1px solid #ccc;
            /* Borda */
            border-radius: 4px;
            /* Arredondamento */
            padding: 8px 12px;
            /* Espaçamento interno */
            width: 100%;
            /* Largura igual ao select */
            cursor: pointer;
            /* Cursor de seleção */
            color: #333;
            /* Cor do texto */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            /* Sombra */
        }

        #dataSelecionada:focus {
            border-color: #007bff;
            /* Borda azul ao focar */
            outline: none;
            /* Remove o contorno padrão */
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
            /* Sombra azul ao focar */
        }
    </style>

    <script>
        $(document).ready(function() {
            // Configurações de idioma para o Datepicker
            $.datepicker.setDefaults($.datepicker.regional["pt-BR"] = {
                closeText: "Fechar",
                prevText: "Anterior",
                nextText: "Próximo",
                currentText: "Hoje",
                monthNames: ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho",
                    "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"
                ],
                monthNamesShort: ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun",
                    "Jul", "Ago", "Set", "Out", "Nov", "Dez"
                ],
                dayNames: ["Domingo", "Segunda-feira", "Terça-feira", "Quarta-feira",
                    "Quinta-feira", "Sexta-feira", "Sábado"
                ],
                dayNamesShort: ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"],
                dayNamesMin: ["D", "S", "T", "Q", "Q", "S", "S"],
                weekHeader: "Sm",
                dateFormat: "dd/mm/yy",
                firstDay: 0,
                isRTL: false,
                showMonthAfterYear: false,
                yearSuffix: ""
            });

            // Configurar o datepicker
            const hoje = new Date();
            const dia = String(hoje.getDate()).padStart(2, '0');
            const mes = String(hoje.getMonth() + 1).padStart(2, '0');
            const ano = hoje.getFullYear();
            const dataHoje = `${dia}/${mes}/${ano}`;

            $('#dataSelecionada').datepicker({
                dateFormat: 'dd/mm/yy',
                changeMonth: true,
                changeYear: true,
                yearRange: "-100:+10"
            }).val(dataHoje);

            // Desativar select de turmas inicialmente
            $('#NNomeTurma').prop('disabled', true);

            // Configurar evento de mudança no select de cursos
            $('#NomeCurso').change(function() {
                const IdCurso = $(this).val();

                if (IdCurso) {
                    $('#NNomeTurma').prop('disabled', false);
                    $.ajax({
                        url: '<?php echo $_SESSION['BASE_para_URL']; ?>/get/getTurmasNome.php',
                        type: 'GET',
                        data: { IdCurso },
                        success: function(data) {
                            $('#NNomeTurma').html(data);
                        },
                        error: function() {
                            alert('Erro ao buscar turmas.');
                        }
                    });
                } else {
                    $('#NNomeTurma').prop('disabled', true).html('<option value="">Selecione o curso primeiro</option>');
                }
            });

            // Adiciona classes dinâmicas a botões (se necessário)
            $('.button').hover(function() {
                $(this).css('box-shadow', '0 4px 6px rgba(0, 0, 0, 0.2)');
            }, function() {
                $(this).css('box-shadow', 'none');
            });
        });
    </script>
</head>

<body>
    <div class="wrapper">
        <?php require_once $_SESSION['BASE_para_PATH'] . '/template/menu.php'; ?>

        <div class="main">
            <?php require_once $_SESSION['BASE_para_PATH'] . '/template/topo.php'; ?>

            <main class="content">
                <form action="listChamada.php" enctype="multipart/form-data" method="GET">
                    <div class="col-12 col-md-3">
                        <label for="NomeCurso" class="form-label">Cursos</label>
                        <select class="form-select" aria-label="Default select example" id="NomeCurso" name="NNomeCurso">
                            <option selected>Selecione o curso</option>
                            <?php
                            require_once $_SESSION['BASE_para_PATH'] . '/conectabd/conexao.php';
                            $sql = "SELECT * FROM tbCurso";
                            $stmt = $pdo->query($sql);
                            while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $IdCurso = $dados['IdCurso'];
                                $NomeCurso = $dados['NomeCurso'];
                                echo '<option value="'.$IdCurso.'">'.$NomeCurso.'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <br>
                    <div class="col-12 col-md-3">
                        <label for="NNomeTurma" class="form-label">Turma</label>
                        <select class="form-select" aria-label="Default select example" id="NNomeTurma" name="NNomeTurma">
                            <option selected>Selecione a turma</option>
                            <?php
                            $sql2 = "SELECT * FROM tbTurma";
                            $stmt2 = $pdo->query($sql2);
                            while ($dados2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                                $IdTurma = $dados2['IdTurma'];
                                $NomeTurma = $dados2['NomeTurma'];
                                echo '<option value="'.$IdTurma.'">'.$NomeTurma.'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <br>
                    <label for="dataSelecionada" class="form-label">Data</label>
                    <div class="row">
                        <div class="col-10 col-md-3">
                            <input type="text" class="form-control" id="dataSelecionada" name="dataSelecionada" placeholder="Selecione a data">
                        </div>
                        <div class="col-2">
                            <button type="submit" id="turma-nome" class="btn btn-primary">Ir</button>
                        </div>
                    </div>
                    <br>
                </form>
            </main>

            <footer class="footer">
                <?php require_once $_SESSION['BASE_para_PATH'] . '/template/footer.php'; ?>
            </footer>
        </div>
    </div>
    <script src="<?php echo $_SESSION['BASE_para_URL']; ?>/assets/js/app.js"></script>
</body>
<script>
    // Função para validar o formulário
    function validarFormulario(event) {
        // Prevenir o envio do formulário
        event.preventDefault();

        // Capturar os elementos
        const nomeCurso = document.getElementById('NomeCurso').value;
        const nomeTurma = document.getElementById('NNomeTurma').value;
        const dataSelecionada = document.getElementById('dataSelecionada').value;

        // Verificar se os campos estão preenchidos corretamente
        if (nomeCurso === 'Selecione o curso' || nomeTurma === '' || !dataSelecionada) {
            alert('Por favor, selecione uma data, curso e turma antes de continuar.');
        } else {
            // Caso todos os campos sejam válidos, enviar o formulário
            document.querySelector('form').submit();
        }
    }

    // Adicionar o evento de clique ao botão submit
    document.querySelector('button[type="submit"]').addEventListener('click', validarFormulario);
</script>

</html>