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

// require_once $_SESSION['BASE_para_PATH'] . '/checa-token.php';


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

    try {
        // Prepara a query
        $stmt = $pdo->prepare("
            SELECT c.IdAluno, a.IdUsuario, a.Nome, c.Data
            FROM tbChamada AS c
            INNER JOIN tbAluno AS a ON a.IdUsuario = c.IdAluno
            ORDER BY a.Nome, c.Data
        ");
        $stmt->execute();

        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Erro ao buscar dados: " . $e->getMessage());
    }
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
    <title>Lista de Chamada</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #eee;
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
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
</head>

<body>
    <div class="wrapper">
        <?php require_once $_SESSION['BASE_para_PATH'] . '/template/menu.php'; ?>

        <div class="main">
            <?php require_once $_SESSION['BASE_para_PATH'] . '/template/topo.php'; ?>

            <main class="content">
                <button class="btn btn-success mb-3" onclick="exportarExcel()">Exportar para Excel</button>

                <h2>Lista de Chamada</h2>
                <!--  -->

                <?php
                // Agrupa datas por aluno no formato original yyyy-mm-dd
                $chamadasPorAluno = [];

                foreach ($dados as $linha) {
                    $nome = $linha['Nome'];
                    $dataRaw = $linha['Data']; // yyyy-mm-dd
                    $chamadasPorAluno[$nome][] = $dataRaw;
                }

                // Ordena datas e encontra o maior número de chamadas
                $maxColunas = 0;
                foreach ($chamadasPorAluno as &$datas) {
                    sort($datas); // Ordena por yyyy-mm-dd
                    if (count($datas) > $maxColunas) {
                        $maxColunas = count($datas);
                    }
                }
                unset($datas);
                ?>

                <table border="1" cellpadding="8" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <?php for ($i = 1; $i <= $maxColunas; $i++): ?>
                                <th><?= $i ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $linhaNumero = 1;
                        foreach ($chamadasPorAluno as $nome => $datas):
                        ?>
                            <!-- Linha com nomes -->
                            <tr>
                                <td rowspan="2"><?= $linhaNumero ?></td>
                                <?php foreach ($datas as $dataRaw): ?>
                                    <td><?= $nome ?></td>
                                <?php endforeach; ?>
                                <?php for ($i = count($datas); $i < $maxColunas; $i++): ?>
                                    <td></td>
                                <?php endfor; ?>
                            </tr>
                            <!-- Linha com datas -->
                            <tr>
                                <?php foreach ($datas as $dataRaw): ?>
                                    <td><?= date('d/m/Y', strtotime($dataRaw)) ?></td>
                                <?php endforeach; ?>
                                <?php for ($i = count($datas); $i < $maxColunas; $i++): ?>
                                    <td></td>
                                <?php endfor; ?>
                            </tr>
                        <?php
                            $linhaNumero++;
                        endforeach;
                        ?>
                    </tbody>
                </table>

                <!--  -->
                <footer class="footer">
                    <?php require_once $_SESSION['BASE_para_PATH'] . '/template/footer.php'; ?>
                </footer>
        </div>
    </div>

    <!-- Modal para confirmar exclusão de presença -->
    <div class="modal fade" id="modalExcluirPresenca" tabindex="-1" aria-labelledby="modalExcluirPresencaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modalExcluirPresencaLabel">Atenção!</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    Deseja retirar o registro desse aluno neste dia?<br>
                    <strong>Todos os botões ficarão desativados.</strong>
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
                    <!-- Conteúdo dinâmico vem aqui -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>


    <script src="<?php echo $_SESSION['BASE_para_URL']; ?>/assets/js/app.js"></script>
    <script>
        function exportarExcel() {
            var tabela = document.querySelector("table");
            var wb = XLSX.utils.table_to_book(tabela, {
                sheet: "Chamada"
            });
            XLSX.writeFile(wb, "lista_chamada.xlsx");
        }
    </script>

</body>

</html>