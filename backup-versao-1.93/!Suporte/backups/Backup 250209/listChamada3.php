<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verifica se as variáveis de sessão BASE_PATH e BASE_URL estão definidas
if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    // Salva a URL atual para redirecionar o usuário após o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endereço atual
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url"); // Redireciona para o login com o endereço de volta via GET
    exit(); // Garante que o código abaixo não será executado
}

require_once $_SESSION['BASE_PATH'] . '/checa-token.php';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <?php
    $cod = $_SESSION['Cod'];
    require_once $_SESSION['BASE_PATH'] . '/template/header.php';
    require_once 'funcoes.php';
    require_once 'conexao_grava.php';
    require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
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
    <link rel="stylesheet" type="text/css" href="<?php echo $_SESSION['BASE_URL']; ?>/assets/css/turma-foto.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
</head>

<body>
    <div class="wrapper">
    <?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>

        <div class="main">
            <?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php'; ?>

            <main class="content">

                <?php
                // Recebe os dados enviados via POST
                $idBotao = $_POST['id'] ?? null;
                $dataSelecionada = $_POST['dataSelecionada'] ?? date('d/m/Y');

                $NNomeCurso = $_POST['NNomeCurso'] ?? null;
                $NNomeTurma = $_POST['NNomeTurma'] ?? null;
                $Id__Curso = $_POST['NNomeCurso'] ?? null;
                $Id__Turma = $_POST['NNomeTurma'] ?? null;
                

                if ($NNomeCurso && $NNomeTurma) {
                    $sqlCursoTurma = "SELECT c.NomeCurso, t.NomeTurma
                      FROM tbCurso c
                      JOIN tbTurma t ON c.IdCurso = t.IdCurso
                      WHERE c.IdCurso = ? AND t.IdTurma = ?
                      ";

                    $stmtCursoTurma = $conexao_grava->prepare($sqlCursoTurma);
                    $stmtCursoTurma->bind_param('ii', $NNomeCurso, $NNomeTurma);
                    $stmtCursoTurma->execute();
                    $resultCursoTurma = $stmtCursoTurma->get_result();

                    if ($resultCursoTurma->num_rows > 0) {
                        $rowCursoTurma = $resultCursoTurma->fetch_assoc();
                        $nomeCurso = $rowCursoTurma['NomeCurso'];
                        $nomeTurma = $rowCursoTurma['NomeTurma'];
                    }

                    $stmtCursoTurma->close(); // Fecha o statement para liberar recursos
                }
                ?>
                <div class="info-container">
                    <h2>Dados selecionados</h2>
                    <p><strong>Data da chamada:</strong> <?= htmlspecialchars($dataSelecionada) ?></p>
                    <p><strong>Curso:</strong> <?= htmlspecialchars($nomeCurso) ?></p>
                    <p><strong>Turma:</strong> <?= htmlspecialchars($nomeTurma) ?></p>
                </div>

                <div class="container">
                    <div class="row ">
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
                        </div>

                    </div>
                </div>
            </main>
            <div class="card-container">
                <?php turma_foto($pdo, $dataSelecionada); ?>

                <div class="modal fade" id="obsModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <image id="fotoaluno" src="" class="rounded-circle img-cover" width='40px' height='40px'>
                                    <h1 class="modal-title" id="exampleModalLabel">Observações</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="observacaoForm" action="" method="" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="nomealuno" class="form-label">Nome aluno</label>
                                        <input type="text" class="form-control" id="nomealuno" name='nomealuno' required autocomplete="off" readonly>
                                        <input type="hidden" name="idaluno" id="idaluno">
                                        <input type="hidden" name="idmatricula" id="idmatricula">
                                        <input type="hidden" name="idchamada" id="idchamada">
                                    </div>

                                    <div class="mb-3">
                                        <label for="observacoes" class="form-label">Observações</label>
                                        <textarea rows="7" class="form-control" aria-label="observacoes" id="observacoes" name='observacoes' autocomplete="off"></textarea>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-success">Salvar alterações</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="footer">
                <?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php'; ?>
            </footer>
        </div>
    </div>
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
            };

            const filterCards = () => {
                const cards = document.querySelectorAll('.card');
                cards.forEach(card => {
                    const selectedButton = card.querySelector('.selecionado');
                    if (selectedButton) {
                        const style = selectedButton.style.backgroundColor;

                        card.style.display = (
                            (style === 'rgb(0, 128, 0)' && buttons.presenca.style.backgroundColor !== 'gray') ||
                            (style === 'rgb(255, 0, 0)' && buttons.falta.style.backgroundColor !== 'gray') ||
                            (style === 'rgb(204, 153, 0)' && buttons.faltaJustificada.style.backgroundColor !== 'gray')
                        ) ? '' : 'none';
                    }
                });
            };

            Object.entries(buttons).forEach(([key, button]) => {
                const color = button.style.backgroundColor;
                button.addEventListener('click', () => {
                    toggleButton(button, color);
                    filterCards();
                });
            });
        });
    </script>
</body>
<script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>

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
    // Script para alterar as cores dos botões dentro do mesmo card
    function alterarCor(botao, cor) {
        const card = botao.closest('.card'); // Obtém o card do botão clicado
        const botoes = card.querySelectorAll('.btn'); // Seleciona todos os botões do card

        if (botao.id.require_onces('Obs')) {

        } else {
            // Reseta todos os botões no card para a cor inicial
            botoes.forEach(b => {
                if (!b.id.require_onces('Obs')) { // Exclui o botão Obs do reset
                    b.style.backgroundColor = '#a3a3a3';
                    b.classList.remove('selecionado');
                }
            });

            // Altera a cor do botão clicado, ou retorna à cor inicial se já estiver selecionado
            const novaCor = getCorCSS(cor);
            if (botao.style.backgroundColor === novaCor) {
                botao.style.backgroundColor = '#a3a3a3';
                botao.classList.remove('selecionado');
            } else {
                botao.style.backgroundColor = novaCor;
                botao.classList.add('selecionado');
            }
        }
    }



    // Função para mapear nomes de cores para valores CSS
    function getCorCSS(nomeCor) {
        const cores = {
            'verde': 'green',
            'vermelho': 'red',
            'amarelo': '#CC9900',
            'azulClaro': 'lightblue'
        };
        return cores[nomeCor] || '#d3d3d3'; // Retorna a cor inicial padrão caso a cor não exista
    }
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
                            $(`#P-${idMatricula}`).addClass("selecionado").css('background-color', 'green');
                        }
                        if (item.falta == "1") {
                            $(`#F-${idMatricula}`).addClass("selecionado").css('background-color', 'red');
                        }
                        if (item.faltajust == "1") {
                            $(`#FJ-${idMatricula}`).addClass("selecionado").css('background-color', '#CC9900');
                        }
                        if (item.Obs !== null) {
                            $(`#Obs-${idMatricula}`).addClass("selecionado").css('background-color', 'darkblue');
                        }
                    });
                } catch (e) {
                    console.error('Erro ao processar os dados do servidor como JSON:', e);
                    alert('Erro inesperado ao processar a resposta do servidor.');
                }
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
    });
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

            // Requisição AJAX para buscar o valor de Obs
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
    $('#observacaoForm').on('submit', function(event) {
        event.preventDefault();

        var idCurso = '<?php echo $nomeCurso; ?>';
        var idTurma = '<?php echo $nomeTurma; ?>';
        var dataSelecionada = '<?php echo $dataSelecionada; ?>';
        var idMatricula = $('#idmatricula').val();
        var observacoes = $('#observacoes').val().trim();;

        // Define como null se o campo estiver vazio
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
                        $('#obsModal').modal('hide');
                        setTimeout(() => {
                            location.reload(); // Recarrega a página
                        }, 1500); // Atraso de 2 segundos
                    } else {
                        alert(data.message || 'Erro ao salvar observação.');
                    }
                } catch (e) {
                    console.error('Erro ao processar a resposta do servidor:', e);
                    toastr.error("Erro ao processar a resposta do servidor", "Erro");
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição AJAX:', status, error);
                alert('Erro ao salvar observação.');
            }
        });
    });
</script>

<!-- Função para filtrar os cards -->
<script>
    // Função para filtrar os cards
    function filterCards() {
        // Função para normalizar texto (remover acentos e substituir "ç" por "c")
        const normalizeText = (text) => {
            return text
                .toLowerCase() // Converte para caixa baixa
                .normalize('NFD') // Decompõe caracteres acentuados
                .replace(/[\u0300-\u036f]/g, '') // Remove diacríticos
                .replace(/ç/g, 'c'); // Substitui "ç" por "c"
        };

        // Obtem o valor do input de busca e normaliza
        const input = document.getElementById('search-input');
        const filter = normalizeText(input.value);

        // Seleciona todos os cards para aplicar o filtro
        const cards = document.querySelectorAll('.card');

        cards.forEach(card => {
            const h4 = card.querySelector('h4'); // Encontra o elemento <h4> no card
            if (h4 && normalizeText(h4.textContent).require_onces(filter)) {
                card.style.display = ''; // Mostra o card
            } else {
                card.style.display = 'none'; // Esconde o card
            }
        });
    }

    // Função para limpar a busca
    function clearSearch() {
        const input = document.getElementById('search-input');
        input.value = ''; // Limpa o campo de entrada
        filterCards(); // Reseta a exibição dos cards
    }
</script>


</html>