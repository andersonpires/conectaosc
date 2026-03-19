<?php
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

function traduzirDia($data)
{
    $dias = [
        'Monday' => 'Segunda',
        'Tuesday' => 'Terça',
        'Wednesday' => 'Quarta',
        'Thursday' => 'Quinta',
        'Friday' => 'Sexta',
        'Saturday' => 'Sabado',
        'Sunday' => 'Domingo'
    ];
    $diaIngles = date('l', strtotime($data));
    return $dias[$diaIngles] ?? $diaIngles;
}

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url");
    exit();
}

// require_once $_SESSION['BASE_PATH'] . '/checa-token.php';
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
require_once 'funcoes.php';

$idAluno = $_GET['idAluno'] ?? 0;
$nomeCurso = $_GET['NNomeCurso'] ?? '';
$nomeTurma = $_GET['NNomeTurma'] ?? '';
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

$sqlMatricula = $pdo->prepare("SELECT IdMatricula FROM tbMatricula WHERE IdUsuario = ? AND IdCurso = ?");
$sqlMatricula->execute([$idAluno, $nomeCurso]);
$idMatricula = $sqlMatricula->fetchColumn();

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
    <?php require_once $_SESSION['BASE_PATH'] . '/template/header.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="<?php echo $_SESSION['BASE_URL']; ?>/assets/css/overlayNotifica.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
        <?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>

        <div class="main">
            <?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php'; ?>

            <main class="content">
                <form id="retornoForm" method="GET" action="listChamada.php">
                    <input type="hidden" name="dataSelecionada" value="<?= htmlspecialchars($dataSelecionada) ?>">
                    <input type="hidden" name="NNomeCurso" value="<?= htmlspecialchars($nomeCurso) ?>">
                    <input type="hidden" name="NNomeTurma" value="<?= htmlspecialchars($nomeTurma) ?>">
                    <button type="submit" class="btn btn-secondary mb-3">Retornar</button>
                </form>

                <div class="container-fluid mt-4">
                    <?php require_once 'chamadaTotalFiltro.php'; ?>
                </div>
            </main>

            <footer class="footer">
                <?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php'; ?>
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
    <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>
    <script>
        const DADOS_GLOBAIS = {
            idAluno: <?php echo json_encode($idAluno); ?>,
            idCurso: <?php echo json_encode($nomeCurso); ?>,
            idTurma: <?php echo json_encode($nomeTurma); ?>,
            idColaborador: <?php echo json_encode($_SESSION['Cod']); ?>
        };
    </script>

    <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/filtroChamadaTotal.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.js"></script>

    <script>
        $(document).ready(function() {
            $.ajax({
                url: 'savebanco.php',
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
        let dadosParaExcluirLinha = null;

        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll('.excluir-registro').forEach(btn => {
                btn.addEventListener('click', () => {
                    dadosParaExcluirLinha = {
                        IdMatricula: btn.getAttribute('data-idmatricula'),
                        IdTurma: btn.getAttribute('data-idturma'),
                        IdCurso: btn.getAttribute('data-idcurso'),
                        dataSelecionada: btn.dataset.data.split('-').reverse().join('/')
                    };

                    const modal = new bootstrap.Modal(document.getElementById('modalExcluirPresenca'));
                    modal.show();
                });
            });

            document.getElementById('confirmarExclusaoLinha').addEventListener('click', () => {
                if (!dadosParaExcluirLinha) return;

                $.ajax({
                    url: 'savebanco.php',
                    type: 'POST',
                    data: {
                        action: 'deleteAula',
                        ...dadosParaExcluirLinha
                    },
                    success: function(resposta) {
                        try {
                            const res = JSON.parse(resposta);
                            if (res.success) {
                                toastr.success("Registro excluído com sucesso!");
                                setTimeout(() => location.reload(), 500);
                            } else {
                                toastr.error(res.message || "Erro ao excluir o registro.");
                            }
                        } catch (err) {
                            toastr.error("Erro ao interpretar resposta do servidor.");
                        }
                    },
                    error: function() {
                        toastr.error("Erro ao tentar excluir o registro.");
                    }
                });
            });
        });
    </script>

</body>

</html>