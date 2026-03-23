<?php
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400); // 24 horas
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verifica se as variÒ¡veis de sessÒ£o BASE_para_PATH e BASE_para_URL estÒ£o definidas
if (!isset($_SESSION['BASE_para_PATH']) || !isset($_SESSION['BASE_para_URL'])) {
    // Salva a URL atual para redirecionar o usuÒ¡rio apÒ³s o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endereÒ§o atual
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url"); // Redireciona para o login com o endereÒ§o de volta via GET
    exit(); // Garante que o cÒ³digo abaixo nÒ£o serÒ¡ executado
}

// require_once $_SESSION['BASE_para_PATH'] . '/api/legacy/checa-token.php';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <?php
    $cod = $_SESSION['Cod'];
    require_once $_SESSION['BASE_para_PATH'] . '/app/template/header.php';
    require_once 'funcoes.php';
    require_once $_SESSION['BASE_para_PATH'] . '/api/conectabd/conexao.php';
    ?>

    <style>
        #dataSelecionada {
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 8px 12px;
            cursor: pointer;
            color: #333;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        #dataSelecionada:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }

        .search-container {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
        }

        #search-input {
            flex: 1;
            padding: 8px;
            font-size: 14px;
        }

        #clear-search {
            padding: 8px 12px;
            font-size: 14px;
            background-color: #f44336;
            color: #fff;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }

        #clear-search:hover {
            background-color: #d32f2f;
        }

        .tooltip-inner {
            background-color: rgba(0, 43, 85, 0.9) !important;
            /* azul escuro */
            color: #fff;
            font-size: 13px;
            padding: 6px 10px;
            border-radius: 5px;
        }

        .tooltip.bs-tooltip-top .tooltip-arrow::before {
            border-top-color: #002b55 !important;
        }

        .tooltip.bs-tooltip-bottom .tooltip-arrow::before {
            border-bottom-color: #002b55 !important;
        }

        .tooltip.bs-tooltip-start .tooltip-arrow::before {
            border-left-color: #002b55 !important;
        }

        .tooltip.bs-tooltip-end .tooltip-arrow::before {
            border-right-color: #002b55 !important;
        }
    </style>


    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $_SESSION['BASE_para_URL']; ?>/assets/css/turma-foto.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

</head>

<body>
    <div class="wrapper">
        <?php require_once $_SESSION['BASE_para_PATH'] . '/app/template/menu.php'; ?>

        <div class="main">
            <?php require_once $_SESSION['BASE_para_PATH'] . '/app/template/topo.php'; ?>

            <main class="content">
                <?php
                // Recebe os dados enviados via POST
                $idBotao = $_GET['id'] ? null;
                $dataSelecionada = $_GET['dataSelecionada'] ? null;
                $NNomeCurso = $_GET['NNomeCurso'] ? null;
                $NNomeTurma = $_GET['NNomeTurma'] ? null;
                $Id__Curso = $_GET['NNomeCurso'] ? null;
                $Id__Turma = $_GET['NNomeTurma'] ? null;

                if ($NNomeCurso && $NNomeTurma) {
                    $sqlCursoTurma = "SELECT c.NomeCurso, t.NomeTurma
                      FROM tbCurso c
                      JOIN tbTurma t ON c.IdCurso = t.IdCurso
                      WHERE c.IdCurso = ? AND t.IdTurma = ?";
                    $stmtCursoTurma = $pdo->prepare($sqlCursoTurma);
                    $stmtCursoTurma->execute([$NNomeCurso, $NNomeTurma]);
                    $rowCursoTurma = $stmtCursoTurma->fetch(PDO::FETCH_ASSOC);
                    if ($rowCursoTurma) {
                        $nomeCurso = $rowCursoTurma['NomeCurso'];
                        $nomeTurma = $rowCursoTurma['NomeTurma'];
                    }
                }
                $resultado = turma_foto($pdo, $dataSelecionada, $dataSelecionada, $NNomeCurso, $NNomeTurma);
                $TotalCards = $resultado['totalCards'];
                ?>
                <div class="info-container">
                    <h2>Dados selecionados</h2>
                    <form id="dataForm" method="GET" action="listChamada.php">
                        <label for="dataSelecionada"><strong>Data da chamada:</strong></label>
                        <input type="text" id="dataSelecionada" name="dataSelecionada" value="<?= htmlspecialchars($dataSelecionada) ?>" class="datepicker" autocomplete="off">
                        <input type="hidden" name="NNomeCurso" value="<?= htmlspecialchars($_GET['NNomeCurso'] ? '') ?>">
                        <input type="hidden" name="NNomeTurma" value="<?= htmlspecialchars($_GET['NNomeTurma'] ? '') ?>">
                        <button type="submit" id="turma-nome" class="btn btn-primary">Ir</button>

                    </form>
                    <p><strong>Curso:</strong> <?= htmlspecialchars($nomeCurso ? '') ?></p>
                    <p><strong>Turma:</strong> <?= htmlspecialchars($nomeTurma ? '') ?></p>
                </div>

                <div class="container">
                    <div class="row">
                        <label for="search-input" class="form-label">Procurar</label>
                        <div class="input-group">
                            <input type="text" id="search-input" class="form-control-lg" placeholder="Digite aqui..." oninput="handleSearchInput()" />

                            <div class="input-group-append">
                                <button id="clear-search" onclick="clearSearch()" class="btn btn-light ms-1">X</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="container">
                    <br>
                    <div class="row">
                        <!-- BotÒµes de Filtro -->
                        <div id="filter-buttons" style="display: flex; gap: 10px; margin-bottom: 20px;">
                            <button id="btn-presenca" class="btn btn-light ms-1" style="flex: 1; background-color: rgb(0, 128, 0); color: white; padding: 10px; border: none; cursor: pointer;">PresenÒ§a</button>
                            <button id="btn-falta" class="btn btn-light ms-1" style="flex: 1; background-color: rgb(255, 0, 0); color: white; padding: 10px; border: none; cursor: pointer;">Falta</button>
                            <button id="btn-falta-justificada" class="btn btn-light ms-1" style="flex: 1; background-color: rgb(204, 153, 0); color: white; padding: 10px; border: none; cursor: pointer;">Falta Just.</button>
                            <button id="btn-nenhum" class="btn btn-light ms-1" style="flex: 1; background-color: lightgray; color: black; padding: 10px; border: none; cursor: pointer;">
                                <i class="bi bi-lightbulb"></i> Nenhum
                            </button>
                        </div>
                    </div>
                    <p style="text-align: center;">Total de alunos na chamada: <?php echo $TotalCards ?></p>
                </div>
            </main>
            <div class="card-container">
                <?php
                // Chama a funÒ§Ò£o turma_foto passando a conexÒ£o PDO ($pdo) e a data selecionada.
                echo $resultado['html'];
                ?>
                <div class="modal fade" id="obsModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <img id="fotoaluno" src="" class="rounded-circle img-cover" width="40px" height="40px">
                                <h1 class="modal-title" id="exampleModalLabel">ObservaÒ§Òµes</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="observacaoForm">
                                    <div class="mb-3">
                                        <label for="nomealuno" class="form-label">Nome aluno</label>
                                        <input type="text" class="form-control" id="nomealuno" name="nomealuno" required autocomplete="off" readonly>
                                        <input type="hidden" name="idaluno" id="idaluno">
                                        <input type="hidden" name="idmatricula" id="idmatricula">
                                        <input type="hidden" name="idchamada" id="idchamada">
                                    </div>
                                    <div class="mb-3">
                                        <label for="observacoes" class="form-label">ObservaÒ§Òµes</label>
                                        <textarea rows="7" class="form-control" aria-label="observacoes" id="observacoes" name="observacoes" autocomplete="off"></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="button" class="btn btn-success" id="SalvaObs">Salvar alteraÒ§Òµes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="footer">
                <?php require_once $_SESSION['BASE_para_PATH'] . '/app/template/footer.php'; ?>
            </footer>
        </div>
    </div>

    <!-- Modal para confirmar exclusÒ£o de presenÒ§a -->
    <div class="modal fade" id="modalExcluirPresenca" tabindex="-1" aria-labelledby="modalExcluirPresencaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modalExcluirPresencaLabel">AtenÒ§Ò£o!</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    Deseja retirar o registro desse aluno neste dia?<br>
                    <strong>Todos os botÒµes ficarÒ£o desativados.</strong>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="confirmarExclusao">Retirar registro</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal de mensagem de retorno -->
    <div class="modal fade" id="modalMensagem" tabindex="-1" aria-labelledby="modalMensagemLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modalMensagemLabel">Aviso</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body" id="conteudoMensagem">
                    <!-- ConteÒºdo dinÒ¢mico vem aqui -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>


    <script src="<?php echo $_SESSION['BASE_para_URL']; ?>/assets/js/app.js"></script>
</body>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let dadosParaExcluir = null;

        // Detectar clique com o botÒ£o direito nos cards
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('dblclick', function(e) {
                e.preventDefault();

                // Verifica se Ò© um administrador
                <?php if ($_SESSION['Tipo'] === 'Administrador') : ?>

                    // Verifica se o card contÒ©m um botÒ£o com classe 'btnp selecionado'
                    if (card.querySelector('.btn.btnp.selecionado')) {
                        // Extrai os dados do ID
                        const partes = card.id.split('_'); // card_IdMatricula_IdTurma_IdCurso_IdAluno
                        if (partes.length === 5) {
                            const [_, IdMatricula, IdTurma, IdCurso, IdAluno] = partes;

                            // Obter dataSelecionada de algum input oculto ou variÒ¡vel JS (ajustar conforme seu sistema)
                            const dataSelecionada = document.getElementById('dataSelecionada')?.value || '';

                            // Armazena os dados
                            dadosParaExcluir = {
                                IdMatricula,
                                IdTurma,
                                IdCurso,
                                dataSelecionada
                            };

                            // Mostra o modal
                            const modal = new bootstrap.Modal(document.getElementById('modalExcluirPresenca'));
                            modal.show();
                        }
                    }

                <?php endif; ?>
            });
        });

        // AÒ§Ò£o de confirmar exclusÒ£o
        // AÒ§Ò£o de confirmar exclusÒ£o
        document.getElementById('confirmarExclusao').addEventListener('click', function() {
            if (!dadosParaExcluir) return;

            $.ajax({
                url: 'savebanco.php',
                type: 'POST',
                data: {
                    action: 'deleteAula',
                    ...dadosParaExcluir
                },
                success: function(resposta) {
                    try {
                        const res = JSON.parse(resposta);

                        // Fecha o primeiro modal (de confirmaÒ§Ò£o)
                        const modalExcluir = bootstrap.Modal.getInstance(document.getElementById('modalExcluirPresenca'));
                        if (modalExcluir) modalExcluir.hide();

                        // Mostra o modal de mensagem
                        const conteudo = document.getElementById('conteudoMensagem');
                        conteudo.innerText = res.message;

                        const modalMensagem = new bootstrap.Modal(document.getElementById('modalMensagem'));
                        modalMensagem.show();

                        if (res.success) {
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        }
                    } catch (err) {
                        alert("Erro ao interpretar a resposta do servidor.");
                    }
                },
                error: function() {
                    alert("Erro ao tentar remover o registro.");
                }
            });
        });

    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // ConfiguraÒ§Òµes de idioma para o Datepicker
        $.datepicker.setDefaults($.datepicker.regional["pt-BR"] = {
            closeText: "Fechar",
            prevText: "Anterior",
            nextText: "PrÒ³ximo",
            currentText: "Hoje",
            monthNames: ["Janeiro", "Fevereiro", "MarÒ§o", "Abril", "Maio", "Junho",
                "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"
            ],
            monthNamesShort: ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun",
                "Jul", "Ago", "Set", "Out", "Nov", "Dez"
            ],
            dayNames: ["Domingo", "Segunda-feira", "TerÒ§a-feira", "Quarta-feira",
                "Quinta-feira", "Sexta-feira", "SÒ¡bado"
            ],
            dayNamesShort: ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "SÒ¡b"],
            dayNamesMin: ["D", "S", "T", "Q", "Q", "S", "S"],
            weekHeader: "Sm",
            dateFormat: "dd/mm/yy",
            firstDay: 0,
            isRTL: false,
            showMonthAfterYear: false,
            yearSuffix: ""
        });

        // Configurar o datepicker
        $(".datepicker").datepicker({
            dateFormat: "dd/mm/yy",
            changeMonth: true,
            changeYear: true,
            yearRange: "-100:+10"
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = {
            presenca: document.getElementById('btn-presenca'),
            falta: document.getElementById('btn-falta'),
            faltaJustificada: document.getElementById('btn-falta-justificada')
        };

        const toggleButton = (button, color) => {
            if (button.style.backgroundColor === 'gray') {
                button.style.backgroundColor = color;
            } else {
                button.style.backgroundColor = 'gray';
            }

            // Reaplica o filtro para garantir que a visibilidade dos cards seja atualizada
            filterCardsButton();
        };


        const filterCardsButton = () => {
            const cards = document.querySelectorAll('.card');

            // Itera sobre todos os cards
            cards.forEach(card => {
                const selectedButton = card.querySelector('.selecionado');
                if (selectedButton) {
                    const style = selectedButton.style.backgroundColor;

                    // Exibe o card se algum botÒ£o estiver ativo
                    card.style.display = (
                        (style === 'rgb(0, 128, 0)' && buttons.presenca.style.backgroundColor !== 'gray') ||
                        (style === 'rgb(255, 0, 0)' && buttons.falta.style.backgroundColor !== 'gray') ||
                        (style === 'rgb(204, 153, 0)' && buttons.faltaJustificada.style.backgroundColor !== 'gray')
                    ) ? '' : 'none'; // Caso contrÒ¡rio, esconde o card
                }
            });
        };


        Object.entries(buttons).forEach(([key, button]) => {
            const color = button.style.backgroundColor;
            button.addEventListener('click', () => {
                toggleButton(button, color); // AlteraÒ§Ò£o do estado do botÒ£o
                filterCardsButton(); // Reaplicar o filtro apÒ³s alteraÒ§Ò£o
            });
        });

        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>

<script>
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-center",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "3000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
</script>

<script>
    //LoadData - LÒª dados do banco
    $(document).ready(function() {
        // Carrega informaÒ§Òµes do banco na inicializaÒ§Ò£o
        let dataSelecionada = "<?= $dataSelecionada ?>";

        $.ajax({
            url: 'savebanco.php',
            type: 'POST',
            data: {
                action: 'loadData', // Especifica a aÒ§Ò£o
                dataSelecionada: dataSelecionada
            },
            success: function(response) {
                try {
                    let data = JSON.parse(response); // Tenta analisar a resposta como JSON

                    // Verifica se hÒ¡ um erro na resposta
                    if (data.erro) {
                        console.warn("Erro retornado pelo servidor:", data.erro);
                        alert(data.erro); // Mostra a mensagem de erro retornada
                        return; // Interrompe o processamento
                    }

                    // Processa os itens retornados pelo servidor
                    data.forEach(item => {
                        let idMatricula = item.IdMatricula;
                        // Adiciona a classe de botÒ£o selecionado com base nos valores
                        if (item.presenca == "1") {
                            $(`#P-${idMatricula}`).addClass("selecionado").css('background-color', '#008000');
                        }
                        if (item.falta == "1") {
                            $(`#F-${idMatricula}`).addClass("selecionado").css('background-color', '#FF0000');
                        }
                        if (item.faltajust == "1") {
                            $(`#FJ-${idMatricula}`).addClass("selecionado").css('background-color', '#CC9900');
                        }
                        if (item.Obs !== null) {
                            $(`#Obs-${idMatricula}`).addClass("selecionado").css('background-color', 'darkblue');
                        } else {
                            $(`#Obs-${idMatricula}`).addClass("selecionado").css('background-color', '#a3a3a3');
                        }
                    });
                } catch (e) {
                    console.error('Erro ao processar os dados do servidor como JSON:', e);
                    alert('Erro inesperado ao processar a resposta do servidor.');
                }
                updateButtonCount();
            },
            error: function(xhr, status, error) {
                console.error("Erro na requisiÒ§Ò£o AJAX:", status, error); // Mostra detalhes do erro
                alert('Ocorreu um erro ao carregar os dados.');
            }
        });

        // Vincula eventos aos botÒµes dinamicamente
        $(document).on('click', '.btnp', function() {
            // ObtÒ©m o ID do botÒ£o
            const buttonId = $(this).attr('id');

            // Extrai apenas as letras antes do traÒ§o (-)
            const action = buttonId.split('-')[0];

            const card = $(this).closest('.card');

            // ObtÒ©m o ID do card
            const id = card.attr('id'); // Exemplo: card_123_45_67

            // Divide o ID para obter os valores separados
            const data = id.split('_'); // [ "card", "123", "45", "67" ]
            const id__Matricula = data[1];
            const idTurma = data[2];
            const idCurso = data[3];
            const idAluno = data[4];
            const idChamada = data[5];
            let IdCod = "<?= $cod ?>";

            const NNomeCurso = "<?= addslashes($_GET['NNomeCurso'] ? '') ?>";
            const NNomeTurma = "<?= addslashes($_GET['NNomeTurma'] ? '') ?>";

            // insertDATA - Envia os dados ao banco
            $.ajax({
                url: 'savebanco.php',
                type: 'POST',
                data: {
                    action: 'insertData', // AÒ§Ò£o de inserÒ§Ò£o
                    id__Matricula: id__Matricula,
                    idAluno: idAluno,
                    idTurma: idTurma,
                    idCurso: idCurso,
                    idColaborador: IdCod,
                    dataSelecionada: dataSelecionada,
                    selectedAction: action
                },
                success: function(response) {
                    console.log('Resposta do servidor:', response);
                    toastr.success("Salvo!");

                    // getFaltas - atualiza o nÒºmero de faltas caso o usuÒ¡rio mude isso
                    $.ajax({
                        url: 'savebanco.php',
                        type: 'POST',
                        data: {
                            action: 'getFaltas',
                            id__Matricula: id__Matricula
                        },
                        success: function(faltasResponse) {
                            try {
                                let faltasData = JSON.parse(faltasResponse);
                                if (faltasData.totalFaltas !== undefined) {
                                    let h4 = card.find('h4');
                                    let nomeAluno = h4.clone().children().remove().end().text().trim(); // Remove link anterior
                                    let link = `listTotalChamada.php?idAluno=${idAluno}&dataSelecionada=${dataSelecionada}&NNomeCurso=${encodeURIComponent(NNomeCurso)}&NNomeTurma=${encodeURIComponent(NNomeTurma)}`;
                                    h4.html(`${nomeAluno} <a href="${link}" class="text-decoration-none">(${faltasData.totalFaltas} faltas)</a>`);
                                }
                            } catch (e) {
                                console.error('Erro ao processar resposta:', e);
                                toastr.error("Erro ao atualizar faltas.", "Erro");
                            }
                        }
                    });
                },
                error: function() {
                    console.error('Erro na comunicaÒ§Ò£o com o servidor.');
                    toastr.error("Erro na comunicaÒ§Ò£o com o servidor.", "Erro");
                }
            });
        });

        // Novo botÒ£o de filtro Nenhum
        const btnNenhum = document.getElementById('btn-nenhum');
        btnNenhum.addEventListener('click', function() {
            toggleNenhumButton(true); // Chama a funÒ§Ò£o com o parÒ¢metro true para realizar o toggle
        });
    });
</script>

<script>
    function alterarCor(botao, cor) {
        const card = botao.closest('.card');
        const botoes = card.querySelectorAll('.btn');

        if (botao.id.includes('Obs')) {
            // Se for o botÒ£o de observaÒ§Ò£o, nÒ£o faz alteraÒ§Ò£o
        } else {
            botoes.forEach(b => {
                if (!b.id.includes('Obs')) {
                    b.style.backgroundColor = '#a3a3a3';
                    b.classList.remove('selecionado');
                }
            });
            const novaCor = getCorCSS(cor);
            if (botao.style.backgroundColor === novaCor) {
                botao.style.backgroundColor = '#a3a3a3';
                botao.classList.remove('selecionado');
            } else {
                botao.style.backgroundColor = novaCor;
                botao.classList.add('selecionado');
            }
        }
        // Chama a funÒ§Ò£o para atualizar a contagem de cards apÒ³s alterar a cor
        updateButtonCount();
    }

    function getCorCSS(nomeCor) {
        const cores = {
            'verde': '#008000',
            'vermelho': '#FF0000',
            'amarelo': '#CC9900',
            'azulClaro': 'lightblue'
        };
        return cores[nomeCor] || '#d3d3d3';
    }

    // FunÒ§Ò£o para atualizar a contagem de cards ao lado dos botÒµes
    function updateButtonCount() {
        // Inicializa os contadores para cada tipo de botÒ£o
        let countPresenca = 0;
        let countFalta = 0;
        let countFaltaJustificada = 0;
        let totalCards = <?php echo $TotalCards; ?>;
        let countNenhum = 0;

        // ObtÒ©m todos os cards
        const cards = document.querySelectorAll('.card');

        // Percorre os cards e conta os botÒµes selecionados
        cards.forEach(card => {
            // Verifica se hÒ¡ botÒ£o de "PresenÒ§a", "Falta", ou "Falta Justificada"
            const presencaButton = card.querySelector('.btn[id^="P-"]'); // Seleciona qualquer botÒ£o com ID comeÒ§ando com P-
            const faltaButton = card.querySelector('.btn[id^="F-"]'); // Seleciona qualquer botÒ£o com ID comeÒ§ando com F-
            const faltaJustificadaButton = card.querySelector('.btn[id^="FJ-"]'); // Seleciona qualquer botÒ£o com ID comeÒ§ando com FJ-

            // Conta os cards com "PresenÒ§a" selecionada (Cor verde)
            if (presencaButton && presencaButton.style.backgroundColor === 'rgb(0, 128, 0)') {
                countPresenca++;
            }

            // Conta os cards com "Falta" selecionada (Cor vermelha)
            if (faltaButton && faltaButton.style.backgroundColor === 'rgb(255, 0, 0)') {
                countFalta++;
            }

            // Conta os cards com "Falta Justificada" selecionada (Cor amarelo)
            if (faltaJustificadaButton && faltaJustificadaButton.style.backgroundColor === 'rgb(204, 153, 0)') {
                countFaltaJustificada++;
            }

            // Conta os cards sem nenhum botÒ£o selecionado
            // Verifica se nenhum botÒ£o tem a cor ativa (usando #a3a3a3 para o estado inativo)
            if (![presencaButton, faltaButton, faltaJustificadaButton].some(button => button && button.style.backgroundColor !== 'rgb(163, 163, 163)')) {
                countNenhum++;
            }
        });
        // calcula valor de btn.nenhum
        countNenhum = totalCards - countFalta - countFaltaJustificada - countPresenca;
        // Atualiza o texto dos botÒµes com as contagens
        document.getElementById('btn-presenca').textContent = `PresenÒ§a: ${countPresenca}`;
        document.getElementById('btn-falta').textContent = `Falta: ${countFalta}`;
        document.getElementById('btn-falta-justificada').textContent = `Falta Just.: ${countFaltaJustificada}`;
        //document.getElementById('btn-nenhum').textContent = `Nenhum: ${countNenhum}`;
        document.getElementById('btn-nenhum').innerHTML = `<i class="${document.getElementById('btn-nenhum').querySelector('i').classList.value}"></i> Nenhum: ${countNenhum}`;
    }


    // FunÒ§Ò£o para alternar o estado do botÒ£o e controlar os cards
    function toggleNenhumButton(isClickEvent = false) {
        const btnNenhum = document.getElementById('btn-nenhum');
        const isActive = btnNenhum.style.backgroundColor === 'lightgray'; // Verifica se o botÒ£o estÒ¡ ativo (lightgray)

        // Se for chamada isoladamente (nÒ£o pelo click), nada acontece se isActive for true
        if (!isClickEvent && isActive) {
            return; // NÒ£o faz nada se o botÒ£o estiver ativo
        }

        if (isClickEvent) {
            // CÒ³digo para o evento de clique (faz o toggle)
            if (isActive) {
                // BotÒ£o desativado
                btnNenhum.style.backgroundColor = '#a3a3a3';
                btnNenhum.style.color = '#fff';
                btnNenhum.querySelector('i').classList.replace('bi-lightbulb', 'bi-lightbulb-off'); // Alterar Ò­cone
                filterCardsGray('none'); // Esconde os cards
            } else {
                // BotÒ£o ativado
                btnNenhum.style.backgroundColor = 'lightgray';
                btnNenhum.style.color = 'black';
                btnNenhum.querySelector('i').classList.replace('bi-lightbulb-off', 'bi-lightbulb'); // Alterar Ò­cone
                filterCardsGray('block'); // Mostra todos os cards
            }
        } else {
            // Se nÒ£o for evento de clique (isClickEvent === false), faz a lÒ³gica para nÒ£o fazer nada se o botÒ£o estiver ativo
            if (!isActive) {
                // BotÒ£o nÒ£o estava ativo (isActive === false)
                btnNenhum.style.backgroundColor = 'lightgray';
                btnNenhum.style.color = 'black';
                btnNenhum.querySelector('i').classList.replace('bi-lightbulb-off', 'bi-lightbulb'); // Alterar Ò­cone para "lampada acesa"
                filterCardsGray('block'); // Mostrar todos os cards
            }
            // Se o botÒ£o jÒ¡ estava ativo, nada acontece
        }
    }
</script>

<script>
    $(function() {
        $('#obsModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var idMatricula = button.data('idmatricula');
            var fotoaluno = button.data('fotoaluno');
            var nomealuno = button.data('nomealuno');
            var idchamada = button.data('idchamada');

            var nomecurso = '<?php echo $Id__Curso; ?>';
            var nometurma = '<?php echo $Id__Turma; ?>';
            var dataSelecionada = '<?php echo $dataSelecionada; ?>';

            var modal = $(this);
            // loadOBS - LÒª as observaÒ§Òµes do banco
            $.ajax({
                url: 'savebanco.php',
                type: 'POST',
                data: {
                    action: 'loadObs',
                    idCurso: nomecurso,
                    idTurma: nometurma,
                    idMatricula: idMatricula,
                    dataSelecionada: dataSelecionada
                },
                success: function(response) {
                    try {
                        let data = JSON.parse(response);
                        // Preenche o modal com os dados retornados
                        modal.find('#idmatricula').val(idMatricula);
                        modal.find('#fotoaluno').attr('src', fotoaluno);
                        modal.find('#nomealuno').val(nomealuno);
                        modal.find('#observacoes').val(data.Obs || ''); // Preenche com o valor de Obs
                        modal.find('#idchamada').val(idchamada);
                        setTimeout(() => {
                            modal.find('#observacoes').focus();
                        }, 500);
                    } catch (e) {
                        alert('Erro na requisiÒ§Ò£o');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro na requisiÒ§Ò£o AJAX:', status, error);
                    alert('Erro ao carregar os dados do banco.');
                }
            });

        });
    });
</script>

<script>
    document.getElementById("SalvaObs").addEventListener("click", function() {
        var idCurso = '<?php echo $nomeCurso; ?>';
        var idTurma = '<?php echo $nomeTurma; ?>';
        var dataSelecionada = '<?php echo $dataSelecionada; ?>';
        var idMatricula = document.getElementById('idmatricula').value;
        var observacoes = document.getElementById('observacoes').value;

        if (observacoes === '') {
            observacoes = null;
        }

        // salvaOBS - Salva as observaÒ§Òµes no banco
        $.ajax({
            url: 'savebanco.php',
            type: 'POST',
            data: {
                action: 'salvaObs',
                idCurso: idCurso,
                idTurma: idTurma,
                idMatricula: idMatricula,
                dataSelecionada: dataSelecionada,
                observacoes: observacoes
            },
            success: function(response) {
                try {
                    let data = JSON.parse(response);
                    if (data.success) {
                        toastr.success("Salvo!");
                        setTimeout(() => {
                            location.reload();
                        }, 300);
                    } else {
                        alert(data.message || 'Erro ao salvar observaÒ§Ò£o.');
                    }
                } catch (e) {
                    console.error('Erro ao processar a resposta do servidor:', e);
                    toastr.error("Primeiro indique P, F ou FJ.", "Erro");
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisiÒ§Ò£o AJAX:', status, error);
                alert('Erro ao salvar observaÒ§Ò£o.');
            }
        });
    });
</script>

<script>
    function SalvaObs() {


    };
</script>

<script>
    function filterCards() {
        const normalizeText = (text) => {
            return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/Ò§/g, 'c');
        };

        const input = document.getElementById('search-input');
        const filter = normalizeText(input.value);
        const cards2 = document.querySelectorAll('.card');

        cards2.forEach(card => {
            // Verifica se o card estÒ¡ visÒ­vel (display !== 'none')
            if (getComputedStyle(card).display !== 'none') {
                const h4 = card.querySelector('h4');
                if (h4 && normalizeText(h4.textContent).includes(filter)) {
                    card.style.display = ''; // Exibe o card se o texto corresponder
                } else {
                    card.style.display = 'none'; // Esconde o card se o texto nÒ£o corresponder
                }
            }
        });
    }

    function handleSearchInput() {
        const input = document.getElementById('search-input');
        if (input.value.trim() === '') {
            clearSearch();
        } else {
            filterCards();
        }
    }


    function filterCardsGray(display) {
        const cards3 = document.querySelectorAll('.card');

        // Filtra os cards que tÒªm todos os botÒµes sem a classe 'selecionado'
        const cardsToFilter = Array.from(cards3).filter(card => {
            const buttons_card = card.querySelectorAll('.btnp');
            // Verifica se todos os botÒµes do card **nÒ£o** tÒªm a classe 'selecionado'
            const allButtonsInactive = Array.from(buttons_card).every(button => !button.classList.contains('selecionado'));

            return allButtonsInactive; // Retorna true se todos os botÒµes estÒ£o inativos (sem a classe 'selecionado')
        });

        //console.log("Cards selecionados para filtro:", cardsToFilter);

        // Aplica display none ou block apenas nos cards que precisam ser filtrados
        cardsToFilter.forEach(card => {
            card.style.display = display;
        });
    }

    function clearSearch() {
        const input = document.getElementById('search-input');
        input.value = ''; // Limpa o valor do input
        // filterCards(); // Chama a funÒ§Ò£o para reexibir todos os cards

        // Reativa todos os cards (fazendo display='block' novamente)
        const cards4 = document.querySelectorAll('.card');
        cards4.forEach(card => {
            card.style.display = ''; // Garante que todos os cards estÒ£o visÒ­veis
        });

        // Reativa os botÒµes de filtro
        const buttons = document.querySelectorAll('#filter-buttons .btn');
        buttons.forEach(button => {
            // Restaura o estilo de fundo para as cores iniciais
            if (button.id === 'btn-presenca') {
                button.style.backgroundColor = 'rgb(0, 128, 0)'; // Cor original de presenÒ§a
            } else if (button.id === 'btn-falta') {
                button.style.backgroundColor = 'rgb(255, 0, 0)'; // Cor original de falta
            } else if (button.id === 'btn-falta-justificada') {
                button.style.backgroundColor = 'rgb(204, 153, 0)'; // Cor original de falta justificada
            } else if (button.id === 'btn-nenhum') {
                button.style.backgroundColor = 'lightgray'; // Cor original de "Nenhum"
                const btnNenhum = document.getElementById('btn-nenhum');
                btnNenhum.style.color = 'black';
                btnNenhum.querySelector('i').classList.replace('bi-lightbulb-off', 'bi-lightbulb');
            }
        });
    }

    function filterCardsButton2() {
        const cards5 = document.querySelectorAll('.card');
        cards5.forEach(card => {
            const selectedButton = cards5.querySelector('.selecionado');
            if (selectedButton) {
                const style = selectedButton.style.backgroundColor;
                card.style.display = (
                    (style === 'rgb(0, 128, 0)' && buttons.presenca.style.backgroundColor !== 'gray') ||
                    (style === 'rgb(255, 0, 0)' && buttons.falta.style.backgroundColor !== 'gray') ||
                    (style === 'rgb(204, 153, 0)' && buttons.faltaJustificada.style.backgroundColor !== 'gray')
                ) ? '' : 'none';
            }
        });
    }
</script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Esconde tooltip ao clicar em qualquer botÒ£o dentro de um card
        document.querySelectorAll('.card button').forEach(button => {
            button.addEventListener('click', () => {
                const tooltips = bootstrap.Tooltip.getInstance(button);
                if (tooltips) {
                    tooltips.hide();
                }
            });
        });

        // Ou: remove todos os tooltips ativos
        document.addEventListener('click', function() {
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(el => {
                const instance = bootstrap.Tooltip.getInstance(el);
                if (instance) instance.hide();
            });
        });
    });
</script>

</html>