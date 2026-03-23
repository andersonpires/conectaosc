<?php
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400); // 24 horas
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <?php
    $cod = $_SESSION['Cod'];
    require_once $_SESSION['BASE_para_PATH'] . '/template/header.php';
    require_once 'funcoes.php';
    require_once $_SESSION['BASE_para_PATH'] . '/conectabd/conexao.php';
    ?>

    <style>
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
    </style>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $_SESSION['BASE_para_URL']; ?>/assets/css/turma-foto.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

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
                        data: {
                            IdCurso
                        },
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
                $resultado = turma_foto($pdo, $dataSelecionada);
                $TotalCards = $resultado['totalCards'];
                ?>
                <div class="info-container">
                    <h2>Dados selecionados</h2>
                    <p><strong>Data da chamada:</strong> <?= htmlspecialchars($dataSelecionada) ?></p>
                    <p><strong>Curso:</strong> <?= htmlspecialchars($nomeCurso ? '') ?></p>
                    <p><strong>Turma:</strong> <?= htmlspecialchars($nomeTurma ? '') ?></p>
                </div>

                <div class="container">
                    <div class="row">
                        <label for="search-input" class="form-label">Procurar</label>
                        <div class="input-group">
                            <input type="text" id="search-input" class="form-control-lg" placeholder="Digite aqui..." oninput="filterCards()" />
                            <div class="input-group-append">
                                <button id="clear-search" onclick="clearSearch()" class="btn btn-light ms-1">X</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="container">
                    <br>
                    <div class="row">
                        <!-- Botões de Filtro -->
                        <div id="filter-buttons" style="display: flex; gap: 10px; margin-bottom: 20px;">
                            <button id="btn-presenca" class="btn btn-light ms-1" style="flex: 1; background-color: rgb(0, 128, 0); color: white; padding: 10px; border: none; cursor: pointer;">Presença</button>
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
                // Chama a função turma_foto passando a conexão PDO ($pdo) e a data selecionada.
                echo $resultado['html'];
                ?>
                <div class="modal fade" id="obsModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <img id="fotoaluno" src="" class="rounded-circle img-cover" width="40px" height="40px">
                                <h1 class="modal-title" id="exampleModalLabel">Observações</h1>
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
                                        <label for="observacoes" class="form-label">Observações</label>
                                        <textarea rows="7" class="form-control" aria-label="observacoes" id="observacoes" name="observacoes" autocomplete="off"></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="button" class="btn btn-success" id="SalvaObs">Salvar alterações</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="footer">
                <?php require_once $_SESSION['BASE_para_PATH'] . '/template/footer.php'; ?>
            </footer>
        </div>
    </div>
    <script src="<?php echo $_SESSION['BASE_para_URL']; ?>/assets/js/app.js"></script>
</body>

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

                    // Exibe o card se algum botão estiver ativo
                    card.style.display = (
                        (style === 'rgb(0, 128, 0)' && buttons.presenca.style.backgroundColor !== 'gray') ||
                        (style === 'rgb(255, 0, 0)' && buttons.falta.style.backgroundColor !== 'gray') ||
                        (style === 'rgb(204, 153, 0)' && buttons.faltaJustificada.style.backgroundColor !== 'gray')
                    ) ? '' : 'none'; // Caso contrário, esconde o card
                }
            });
        };


        Object.entries(buttons).forEach(([key, button]) => {
            const color = button.style.backgroundColor;
            button.addEventListener('click', () => {
                toggleButton(button, color); // Alteração do estado do botão
                filterCardsButton(); // Reaplicar o filtro após alteração
            });
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
    //LoadData
    $(document).ready(function() {
        // Carrega informações do banco na inicialização
        let dataSelecionada = "<?= $dataSelecionada ?>";

        $.ajax({
            url: 'savebanco.php',
            type: 'POST',
            data: {
                action: 'loadData', // Especifica a ação
                dataSelecionada: dataSelecionada
            },
            success: function(response) {
                try {
                    let data = JSON.parse(response); // Tenta analisar a resposta como JSON

                    // Verifica se há um erro na resposta
                    if (data.erro) {
                        console.warn("Erro retornado pelo servidor:", data.erro);
                        alert(data.erro); // Mostra a mensagem de erro retornada
                        return; // Interrompe o processamento
                    }

                    // Processa os itens retornados pelo servidor
                    data.forEach(item => {
                        let idMatricula = item.IdMatricula;
                        // Adiciona a classe de botão selecionado com base nos valores
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
                console.error("Erro na requisição AJAX:", status, error); // Mostra detalhes do erro
                alert('Ocorreu um erro ao carregar os dados.');
            }
        });

        // Vincula eventos aos botões dinamicamente
        $(document).on('click', '.btnp', function() {
            // Obtém o ID do botão
            const buttonId = $(this).attr('id');

            // Extrai apenas as letras antes do traço (-)
            const action = buttonId.split('-')[0];

            const card = $(this).closest('.card');

            // Obtém o ID do card
            const id = card.attr('id'); // Exemplo: card_123_45_67

            // Divide o ID para obter os valores separados
            const data = id.split('_'); // [ "card", "123", "45", "67" ]
            const id__Matricula = data[1];
            const idTurma = data[2];
            const idCurso = data[3];
            const idAluno = data[4];
            const idChamada = data[5];
            let IdCod = "<?= $cod ?>";

            // Envie os dados ao servidor
            $.ajax({
                url: 'savebanco.php',
                type: 'POST',
                data: {
                    action: 'insertData', // Ação de inserção
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
                },
                error: function() {
                    console.error('Erro na comunicação com o servidor.');
                    toastr.error("Erro na comunicação com o servidor.", "Erro");
                }
            });
        });

        // Novo botão de filtro Nenhum
        const btnNenhum = document.getElementById('btn-nenhum');
        btnNenhum.addEventListener('click', function() {
            toggleNenhumButton(true); // Chama a função com o parâmetro true para realizar o toggle
        });
    });
</script>

<script>
    function alterarCor(botao, cor) {
        const card = botao.closest('.card');
        const botoes = card.querySelectorAll('.btn');

        if (botao.id.includes('Obs')) {
            // Se for o botão de observação, não faz alteração
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
        // Chama a função para atualizar a contagem de cards após alterar a cor
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

    // Função para atualizar a contagem de cards ao lado dos botões
    function updateButtonCount() {
        // Inicializa os contadores para cada tipo de botão
        let countPresenca = 0;
        let countFalta = 0;
        let countFaltaJustificada = 0;
        let totalCards = <?php echo $TotalCards; ?>;
        let countNenhum = 0;

        // Obtém todos os cards
        const cards = document.querySelectorAll('.card');

        // Percorre os cards e conta os botões selecionados
        cards.forEach(card => {
            // Verifica se há botão de "Presença", "Falta", ou "Falta Justificada"
            const presencaButton = card.querySelector('.btn[id^="P-"]'); // Seleciona qualquer botão com ID começando com P-
            const faltaButton = card.querySelector('.btn[id^="F-"]'); // Seleciona qualquer botão com ID começando com F-
            const faltaJustificadaButton = card.querySelector('.btn[id^="FJ-"]'); // Seleciona qualquer botão com ID começando com FJ-

            // Conta os cards com "Presença" selecionada (Cor verde)
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

            // Conta os cards sem nenhum botão selecionado
            // Verifica se nenhum botão tem a cor ativa (usando #a3a3a3 para o estado inativo)
            if (![presencaButton, faltaButton, faltaJustificadaButton].some(button => button && button.style.backgroundColor !== 'rgb(163, 163, 163)')) {
                countNenhum++;
            }
        });
        // calcula valor de btn.nenhum
        countNenhum = totalCards - countFalta - countFaltaJustificada - countPresenca;
        // Atualiza o texto dos botões com as contagens
        document.getElementById('btn-presenca').textContent = `Presença: ${countPresenca}`;
        document.getElementById('btn-falta').textContent = `Falta: ${countFalta}`;
        document.getElementById('btn-falta-justificada').textContent = `Falta Just.: ${countFaltaJustificada}`;
        //document.getElementById('btn-nenhum').textContent = `Nenhum: ${countNenhum}`;
        document.getElementById('btn-nenhum').innerHTML = `<i class="${document.getElementById('btn-nenhum').querySelector('i').classList.value}"></i> Nenhum: ${countNenhum}`;
    }


    // Função para alternar o estado do botão e controlar os cards
    function toggleNenhumButton(isClickEvent = false) {
        const btnNenhum = document.getElementById('btn-nenhum');
        const isActive = btnNenhum.style.backgroundColor === 'lightgray'; // Verifica se o botão está ativo (lightgray)

        // Se for chamada isoladamente (não pelo click), nada acontece se isActive for true
        if (!isClickEvent && isActive) {
            return; // Não faz nada se o botão estiver ativo
        }

        if (isClickEvent) {
            // Código para o evento de clique (faz o toggle)
            if (isActive) {
                // Botão desativado
                btnNenhum.style.backgroundColor = '#a3a3a3';
                btnNenhum.style.color = '#fff';
                btnNenhum.querySelector('i').classList.replace('bi-lightbulb', 'bi-lightbulb-off'); // Alterar ícone
                filterCardsGray('none'); // Esconde os cards
            } else {
                // Botão ativado
                btnNenhum.style.backgroundColor = 'lightgray';
                btnNenhum.style.color = 'black';
                btnNenhum.querySelector('i').classList.replace('bi-lightbulb-off', 'bi-lightbulb'); // Alterar ícone
                filterCardsGray('block'); // Mostra todos os cards
            }
        } else {
            // Se não for evento de clique (isClickEvent === false), faz a lógica para não fazer nada se o botão estiver ativo
            if (!isActive) {
                // Botão não estava ativo (isActive === false)
                btnNenhum.style.backgroundColor = 'lightgray';
                btnNenhum.style.color = 'black';
                btnNenhum.querySelector('i').classList.replace('bi-lightbulb-off', 'bi-lightbulb'); // Alterar ícone para "lampada acesa"
                filterCardsGray('block'); // Mostrar todos os cards
            }
            // Se o botão já estava ativo, nada acontece
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
                        alert('Erro na requisição');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro na requisição AJAX:', status, error);
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
                        alert(data.message || 'Erro ao salvar observação.');
                    }
                } catch (e) {
                    console.error('Erro ao processar a resposta do servidor:', e);
                    toastr.error("Primeiro indique P, F ou FJ.", "Erro");
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição AJAX:', status, error);
                alert('Erro ao salvar observação.');
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
            return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/ç/g, 'c');
        };

        const input = document.getElementById('search-input');
        const filter = normalizeText(input.value);
        const cards2 = document.querySelectorAll('.card');

        cards2.forEach(card => {
            // Verifica se o card está visível (display !== 'none')
            if (getComputedStyle(card).display !== 'none') {
                const h4 = card.querySelector('h4');
                if (h4 && normalizeText(h4.textContent).includes(filter)) {
                    card.style.display = ''; // Exibe o card se o texto corresponder
                } else {
                    card.style.display = 'none'; // Esconde o card se o texto não corresponder
                }
            }
        });
    }

    function filterCardsGray(display) {
        const cards3 = document.querySelectorAll('.card');

        // Filtra os cards que têm todos os botões sem a classe 'selecionado'
        const cardsToFilter = Array.from(cards3).filter(card => {
            const buttons_card = card.querySelectorAll('.btnp');
            // Verifica se todos os botões do card **não** têm a classe 'selecionado'
            const allButtonsInactive = Array.from(buttons_card).every(button => !button.classList.contains('selecionado'));

            return allButtonsInactive; // Retorna true se todos os botões estão inativos (sem a classe 'selecionado')
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
        // filterCards(); // Chama a função para reexibir todos os cards

        // Reativa todos os cards (fazendo display='block' novamente)
        const cards4 = document.querySelectorAll('.card');
        cards4.forEach(card => {
            card.style.display = ''; // Garante que todos os cards estão visíveis
        });

        // Reativa os botões de filtro
        const buttons = document.querySelectorAll('#filter-buttons .btn');
        buttons.forEach(button => {
            // Restaura o estilo de fundo para as cores iniciais
            if (button.id === 'btn-presenca') {
                button.style.backgroundColor = 'rgb(0, 128, 0)'; // Cor original de presença
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

</html>