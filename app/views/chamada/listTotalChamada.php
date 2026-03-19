<?php $runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}

function traduzirDia($data)
{
    $dias = [
        'Monday' => 'Segunda',
        'Tuesday' => 'Terça',
        'Wednesday' => 'Quarta',
        'Thursday' => 'Quinta',
        'Friday' => 'Sexta',
        'Saturday' => 'Sábado',
        'Sunday' => 'Domingo'
    ];
    $diaIngles = date('l', strtotime($data));
    return $dias[$diaIngles] ?? $diaIngles;
}

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: " . rtrim((string) ($BASE_para_URL ?? ''), '/') . "/login/?redirect=$redirect_url");
    exit();
}

// require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
require_once $BASE_para_PATH . '/api/legacy/funcoes.php';
require_once __DIR__ . '/funcoes.php';

$idAluno = $_GET['idAluno'] ?? 0;
$nomeCurso = $_GET['NNomeCurso'] ?? '';
$nomeTurma = $_GET['NNomeTurma'] ?? '';
$paginaChamada = $_GET['chamada'] ?? '';
$dataSelecionada = $_GET['dataSelecionada'] ?? date('Y-m-d');

if ($nomeCurso && $nomeTurma) {
    $sqlCursoTurma = "SELECT c.NomeCurso, t.NomeTurma
      FROM tbCurso c
      JOIN tbTurma t ON c.IdCurso = t.IdCurso
      WHERE c.IdCurso = ? AND t.IdTurma = ?";
    $stmtCursoTurma = $pdo->prepare($sqlCursoTurma);
    $stmtCursoTurma->execute([$nomeCurso, $nomeTurma]);
    $rowCursoTurma = $stmtCursoTurma->fetch(PDO::FETCH_ASSOC);
    if ($rowCursoTurma) {
        $nomeExtCurso = $rowCursoTurma['NomeCurso'];
        $nomeExtTurma = $rowCursoTurma['NomeTurma'];
    }
}

$sqlAluno = $pdo->prepare("SELECT * FROM tbAluno WHERE IdUsuario = ?");
$sqlAluno->execute([$idAluno]);
$aluno = $sqlAluno->fetch(PDO::FETCH_ASSOC);

$sqlMatricula = $pdo->prepare("SELECT IdMatricula, Habilitado FROM tbMatricula WHERE IdUsuario = ? AND IdCurso = ?");
$sqlMatricula->execute([$idAluno, $nomeCurso]);
$dadosMatricula = $sqlMatricula->fetch(PDO::FETCH_ASSOC);
$idMatricula = $dadosMatricula['IdMatricula'] ?? null;
$statusMatricula = $dadosMatricula['Habilitado'] ?? 0;

$sqlDatas = $pdo->prepare("SELECT DISTINCT Data FROM tbChamada WHERE IdAluno = ? AND IdCurso = ? ORDER BY Data");
$sqlDatas->execute([$idAluno, $nomeCurso]);
$datas = $sqlDatas->fetchAll(PDO::FETCH_COLUMN);

// Verifica se o usuário selecionou as datas no formulário
$dataInicio = $_GET['dataInicio'] ?? ($datas[0] ?? date('Y-m-01'));
$dataFim = $_GET['dataFim'] ?? (end($datas) ?: date('Y-m-t'));


function diasEntreDatas($start, $end)
{
    $period = new DatePeriod(
        new DateTime($start),
        new DateInterval('P1D'),
        (new DateTime($end))->modify('+1 day')
    );
    return $period;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo $BASE_para_URL; ?>/assets/css/overlayNotifica.css">


    <script>
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-center", // pode ser top-right, top-left, bottom-center etc ? "preventDuplicates": true,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "4000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };
    </script>

</head>
<style>
    .button-container button {
        background-color: #a7a7a7;
        border: 1px solid #ccc;
        border-radius: 5px;
        color: white;
        font-size: 0.7rem;
        padding: 4px 7px;
        cursor: pointer;
    }

    .button-container button.selecionado {
        color: white;
    }

    .button-container button:hover {
        background-color: #a9a9a9;
    }

    .button-container button.obs-darkblue {
        background-color: darkblue;
        color: white;
    }

    #filter-buttons button.ativo {
        border: 2px solid #000;
        box-shadow: 0 0 6px rgba(0, 0, 0, 0.3);
    }

    button.obs-botao:disabled {
        opacity: 1;
        color: white;
        background-color: #a3a3a3;
        /* ou 'darkblue' se tiver conteúdo */
        pointer-events: none;
        /* Garante que não clica */
        border: 1px solid #ccc;
    }


    @media (min-width: 376px) and (max-width: 499px) {
        .button-container button {
            font-size: 0.8rem;
            padding: 5px 9px;
        }
    }

    @media (min-width: 499px) {
        .button-container button {
            font-size: 0.9rem;
            padding: 5px 10px;
        }
    }
</style>


<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>

            <main class="content">
                <form id="retornoForm" method="GET" action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/chamada/lista/">
                    <input type="hidden" name="dataSelecionada" value="<?= htmlspecialchars($dataSelecionada) ?>">
                    <input type="hidden" name="NNomeCurso" value="<?= htmlspecialchars($nomeCurso) ?>">
                    <input type="hidden" name="NNomeTurma" value="<?= htmlspecialchars($nomeTurma) ?>">
                    <?php
                    if ($paginaChamada == 1){
                        echo '<button type="submit" class="btn btn-secondary mb-3">Retornar</button>';
                    }
                    ?>
                </form>

                <div class="container-fluid mt-4">
                    <?php require_once __DIR__ . '/chamadaTotalFiltro.php'; ?>
                </div>
            </main>

            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
            <div class="modal fade" id="modalExcluirPresenca" tabindex="-1" aria-labelledby="modalExcluirPresencaLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="modalExcluirPresencaLabel">Atenção!</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            Deseja realmente excluir a presença/falta registrada neste dia?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-success" id="confirmarExclusaoLinha">Confirmar</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/overlayNotifica.js"></script>
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/filtroChamadaTotal.js"></script>

    <script>
        const DADOS_GLOBAIS = {
            idAluno: <?php echo json_encode($idAluno); ?>,
            idCurso: <?php echo json_encode($nomeCurso); ?>,
            idTurma: <?php echo json_encode($nomeTurma); ?>,
            idColaborador: <?php echo json_encode((int)($_SESSION['Cod'] ?? 0)); ?>,
            idMatricula: <?php echo json_encode($idMatricula); ?>,
            saveUrl: <?php echo json_encode(rtrim((string)$BASE_para_URL, '/') . '/chamada/salvar/'); ?>
        };
    </script>

    <script>
        $(document).ready(function() {
            $.ajax({
                url: '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/chamada/salvar/',
                method: 'POST',
                data: {
                    action: 'loadHistoricoData',
                    idAluno: '<?php echo $idAluno; ?>',
                    idCurso: '<?php echo $nomeCurso; ?>'
                },
                success: function(response) {
                    try {
                        let dados = JSON.parse(response);
                        if (Array.isArray(dados)) {
                            dados.forEach(item => {
                                const idMatricula = item.IdMatricula;
                                const dataFormatada = item.Data.replace(/-/g, '');

                                if (item.presenca == "1") {
                                    $(`#P-${idMatricula}-${dataFormatada}`).addClass("selecionado").css("background-color", "#008000");
                                }
                                if (item.falta == "1") {
                                    $(`#F-${idMatricula}-${dataFormatada}`).addClass("selecionado").css("background-color", "#FF0000");
                                }
                                if (item.faltajust == "1") {
                                    $(`#FJ-${idMatricula}-${dataFormatada}`).addClass("selecionado").css("background-color", "#CC9900");
                                }
                                if (item.Obs && item.Obs !== "") {
                                    $(`#Obs-${idMatricula}-${dataFormatada}`).css("background-color", "darkblue").addClass("selecionado");
                                }
                            });
                            updateTabelaChamada(); // importante para recalcular contadores e filtros após carregar
                        }
                    } catch (e) {
                        console.error("Erro ao processar loadHistoricoData:", e);
                    }
                },
                error: function() {
                    console.error("Erro na requisição loadHistoricoData");
                }
            });
        });
    </script>
    <script>
        let datasSelecionadas = [];

        document.addEventListener("DOMContentLoaded", () => {
            const botaoExcluirSelecionados = document.getElementById('botaoExcluirSelecionados');

            botaoExcluirSelecionados.addEventListener('click', () => {
                datasSelecionadas = [];

                document.querySelectorAll('.selecionar-aula:checked').forEach(checkbox => {
                    datasSelecionadas.push(checkbox.getAttribute('data-data'));
                });

                if (datasSelecionadas.length === 0) {
                    toastr.warning("Selecione pelo menos uma aula para excluir.");
                    return;
                }

                // Monta o texto novo do modal corretamente
                let textoModal = "";
                if (datasSelecionadas.length === 1) {
                    textoModal = `Deseja realmente excluir a aula do dia: ${datasSelecionadas[0]}?`;
                } else {
                    const diasFormatados = datasSelecionadas.map(data => {
                        const [dia, mes, ano] = data.split('/');
                        return `${dia}/${mes}`;
                    });
                    textoModal = `Deseja realmente excluir as aulas dos dias: ${diasFormatados.join(', ')}?`;
                }

                document.querySelector('#modalExcluirPresenca .modal-body').innerText = textoModal;

                // Mostra o modal sem alterar scroll
                const modal = new bootstrap.Modal(document.getElementById('modalExcluirPresenca'), {
                    backdrop: 'static',
                    keyboard: true,
                    focus: false
                });
                modal.show();
            });

            document.getElementById('confirmarExclusaoLinha').addEventListener('click', () => {
                if (datasSelecionadas.length === 0) return;

                $.ajax({
                    url: '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/chamada/salvar/',
                    type: 'POST',
                    data: {
                        action: 'deleteAulasMultiplo',
                        IdMatricula: DADOS_GLOBAIS.idMatricula,
                        datasSelecionadas: datasSelecionadas
                    },
                    success: function(resposta) {
                        try {
                            const res = JSON.parse(resposta);

                            // Fecha o modal de confirmação, mesmo em erro
                            const modal = bootstrap.Modal.getInstance(document.getElementById('modalExcluirPresenca'));
                            modal.hide();

                            setTimeout(() => {
                                if (res.success) {
                                    toastr.success(res.message || "Aulas excluídas com sucesso!");
                                    setTimeout(() => location.reload(), 500);
                                } else {
                                    toastr.warning(res.message || "Algumas datas não possuem registro para exclusão.");
                                    setTimeout(() => location.reload(), 3000);
                                }
                            }, 300);
                        } catch (err) {
                            toastr.error("Erro ao interpretar resposta do servidor.");
                        }
                    },
                    error: function() {
                        toastr.error("Erro ao tentar excluir aulas.");
                    }
                });
            });
            document.getElementById('btnMatriculaToggle').addEventListener('click', function() {
                const idAluno = this.getAttribute('data-idaluno');
                const botao = this;

                $.ajax({
                    url: '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/chamada/salvar/',
                    method: 'POST',
                    data: {
                        action: 'matricular-desmatricular',
                        idAluno: idAluno
                    },
                    success: function(resposta) {
                        try {
                            const res = JSON.parse(resposta);

                            if (res.success) {
                                toastr.success(res.message || "Status atualizado!");

                                // Atualiza visual do botão
                                if (res.novoStatus == 1) {
                                    botao.classList.remove('btn-secondary');
                                    botao.classList.add('btn-primary');
                                    botao.textContent = "Matriculado";
                                } else {
                                    botao.classList.remove('btn-primary');
                                    botao.classList.add('btn-secondary');
                                    botao.textContent = "Desmatriculado";
                                }
                            } else {
                                toastr.warning(res.message || "Falha ao atualizar status.");
                            }
                        } catch (e) {
                            toastr.error("Erro ao interpretar resposta.");
                        }
                    },
                    error: function() {
                        toastr.error("Erro na requisição.");
                    }
                });
            });

        });
    </script>
    <script>
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, (m) => map[m]);
        }

        $(document).ready(function() {
            const cells = $('#tabelaPresencas .feriado-cell');
            if (!cells.length) return;

            const datas = cells.map(function() {
                return $(this).data('date');
            }).get();

            $.ajax({
                url: '<?php echo $BASE_para_URL; ?>/feriados',
                method: 'POST',
                dataType: 'json',
                data: {
                    acao: 'verificar',
                    datas: datas
                },
                success: function(response) {
                    const dados = response && response.data ? response.data : {};

                    cells.each(function() {
                        const data = $(this).data('date');
                        const info = dados && data ? dados[data] : null;

                        if (info && info.feriado) {
                            $(this).html('<span class="badge bg-danger">' + escapeHtml(info.nome || 'Feriado') + '</span>');
                        } else {
                            $(this).text('Não');
                        }
                    });
                },
                error: function() {
                    cells.text('Não');
                }
            });
        });
    </script>
</body>

</html>












