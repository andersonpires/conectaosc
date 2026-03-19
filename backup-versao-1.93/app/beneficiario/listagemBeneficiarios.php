<?php
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400); // 24 horas
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

// Verifica se as variáveis de sessão BASE_PATH e BASE_URL estão definidas
if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    // Salva a URL atual para redirecionar o usuário após o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endereço atual
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url"); // Redireciona para o login com o endereço de volta via GET
    exit(); // Garante que o código abaixo não será executado
}
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
?>
<?php require_once $_SESSION['BASE_PATH'] . '/checa-token.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <?php
    require_once $_SESSION['BASE_PATH'] . '/template/header.php';
    ?>
    <style>
        .img-cover {
            object-fit: cover;
            object-position: center;
        }

        .hover-container {
            display: inline-block;
            position: relative;
        }

        .hover-img {
            transition: transform 0.2s ease;
        }

        .hover-container:hover .hover-img {
            transform: scale(3.5);
            z-index: 10;
            position: absolute;
            left: 0;
            top: 0;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.25);
        }

        .table-responsive {
            overflow-x: auto;
            overflow-y: visible;
        }

        #minhaTabela {
            table-layout: auto;
            width: 100%;
            min-width: 1200px;
        }

        #minhaTabela th:nth-child(1),
        #minhaTabela td:nth-child(1) { width: 3%; }
        #minhaTabela th:nth-child(2),
        #minhaTabela td:nth-child(2) { width: 4%; }
        #minhaTabela th:nth-child(3),
        #minhaTabela td:nth-child(3) { width: 6%; }
        #minhaTabela th:nth-child(4),
        #minhaTabela td:nth-child(4) { width: 22%; }
        #minhaTabela th:nth-child(5),
        #minhaTabela td:nth-child(5) { width: 18%; }
        #minhaTabela th:nth-child(6),
        #minhaTabela td:nth-child(6) { width: 9%; }
        #minhaTabela th:nth-child(7),
        #minhaTabela td:nth-child(7) { width: 8%; }
        #minhaTabela th:nth-child(8),
        #minhaTabela td:nth-child(8) { width: 8%; }
        #minhaTabela th:nth-child(9),
        #minhaTabela td:nth-child(9) { width: 14%; }
        #minhaTabela th:nth-child(10),
        #minhaTabela td:nth-child(10) { width: 8%; }

        .loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            display: none;
        }

        .loader img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .table {
            display: none;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #ffffff !important;
            background-color: rgb(7, 9, 12) !important;
            /* border: 1px solid #222E3C !important; */
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background-color: #1a73e8 !important;
            border: 1px solid #1a73e8 !important;
        }

        table.dataTable thead {
            background-color: #222E3C;
            color: #ffffff;
        }

        table.dataTable tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
        }

        table.dataTable tbody tr:nth-child(even) {
            background-color: #ffffff;
        }

        .dataTables_filter {
            margin-bottom: 1rem;
            /* Espaço abaixo do "Procurar" */
        }

        .dataTables_filter label {
            display: flex;
            /* Garante que o texto e o input fiquem alinhados */
            align-items: center;
        }

        .dataTables_filter input {
            margin-left: 0.5rem;
            /* Espaço entre o texto e o campo de entrada */
        }

        .table-layout-auto {
            table-layout: auto;
            width: 100%;
            /* Para garantir que a tabela use todo o espaço disponível */
            white-space: nowrap;
            /* Evita que o texto quebre para múltiplas linhas */
        }

        .table-layout-auto th,
        .table-layout-auto td {
            word-wrap: break-word;
            /* Se necessário, permite que o texto seja quebrado */
            text-align: left;
            /* Define o alinhamento padrão das células */
        }
    </style>
    <style>
        #matricularAluno {
            position: fixed;
            right: 10px;
            top: 100px;
            z-index: 100;
            border-radius: 50%;
            /* Borda redonda */
            width: 80px;
            /* Largura do botão */
            height: 80px;
            /* Altura do botão */
            border: none;
            /* Sem borda */
            font-weight: bold;
            /* Texto em negrito */
            text-align: center;
            /* Texto centralizado */
            padding: 5px;
            /* Espaçamento interno */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #ffffff !important;
            background-color: #222E3C !important;
            border: 1px solid #222E3C !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background-color: #1a73e8 !important;
            border: 1px solid #1a73e8 !important;
        }

        table.dataTable thead {
            background-color: #222E3C;
            color: #ffffff;
        }

        table.dataTable tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
        }

        table.dataTable tbody tr:nth-child(even) {
            background-color: #ffffff;
        }

        /* Personaliza o tooltip padrão do Bootstrap */
        .tooltip-inner {
            background-color: rgba(0, 35, 180, 0.9) !important;
            /* azul com 85% opacidade */
            color: white !important;
            font-size: 14px;
            padding: 8px 12px;
            border-radius: 8px;
            text-align: left;
        }

        .tooltip-inner {
            white-space: pre-wrap !important;
        }


        /* Opcional: seta do tooltip com mesma cor */
        .bs-tooltip-top .tooltip-arrow::before,
        .bs-tooltip-bottom .tooltip-arrow::before,
        .bs-tooltip-start .tooltip-arrow::before,
        .bs-tooltip-end .tooltip-arrow::before {
            border-top-color: rgba(0, 35, 180, 0.9) !important;
            border-bottom-color: rgba(0, 35, 180, 0.9) !important;
            border-left-color: rgba(0, 35, 180, 0.9) !important;
            border-right-color: rgba(0, 35, 180, 0.9) !important;
        }
    </style>
    <link rel="stylesheet" href="<?php echo $_SESSION['BASE_URL']; ?>/assets/css/overlayNotifica.css">
    <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/overlayNotifica.js"></script>
</head>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="//code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<body>
    <div id="overlayLoading">
        <div class="overlay-bg"></div>
        <div class="overlay-content" id="overlayContent">
            <div class="spinner-border text-primary" role="status" id="overlaySpinner">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-2" id="overlayText">Aguarde...</p>
            <button id="overlayBtnOk" class="btn btn-primary mt-3" style="display: none;">OK</button>
        </div>
    </div>

    <div class="wrapper">
        <?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>

        <div class="main">
            <?php
            require_once $_SESSION['BASE_PATH'] . '/template/topo.php';
            ?>

            <main class="content">

                <!-- <button type="button" class="btn btn-success mb-3" id="matricularAluno">Matricular <br> Aluno</button> -->

                <h4 class="h3 mb-3" id="titulo">Lista de Alunos</h4>
                <?php // Exibe mensagem vinda da URL
                // Obter a URL atual
                $msg = "";
                $url = $_SERVER['REQUEST_URI'];
                $urlDecoded = urldecode($url);
                if (strpos($urlDecoded, '=') !== false) {
                    $params = explode('&', $urlDecoded);
                    $tipomsg = explode('?', $params[0])[1]; // Pega o valor após o '?'
                    $tipomsg = explode('=', $tipomsg)[0]; // Pega o valor antes do '='

                    $msg = explode('=', $params[0])[1]; // Pega o valor após o '='
                    // Extrair o valor do parâmetro "msg" (assumindo que ele seja o primeiro)
                    $msg = explode('=', $params[0])[1]; // Pega o valor após o '='
                }

                // Exibir a mensagem como variável
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
                <div id="loader" class="loader">
                    <!-- <img src="https://www.blogson.com.br/wp-content/uploads/2017/10/loading-gif-transparent-10.gif" width="150" alt="Carregando..."> -->
                </div>
                <div class="col-12">
                    <div class="card shadow-lg">
                        <div class="card-body">
                            <a href="beneficiarioController.php" class="btn btn-primary mb-3" id="novoAluno">Cadastrar novo aluno</a>
                            <button id="btnExportExcel" class="btn btn-success mb-3">
                                <i class="fa-solid fa-file-excel"></i> Exportar Excel
                            </button>


                            <div class="table-responsive">
                                <table id="minhaTabela" class="table table-striped table-hover datatable-custom">
                                    <thead>
                                        <tr>
                                            <th scope="col">*</th>
                                            <th scope="col">Id</th>
                                            <th scope="col">Foto</th>
                                            <th scope="col">Nome</th>
                                            <th scope="col">Interesses</th>
                                            <th scope="col">Paciente</th>
                                            <th scope="col">Turmas Ativas</th>
                                            <th scope="col">Histórico de turmas</th>
                                            <th scope="col">Nascimento</th>
                                            <th scope="col">Idade</th>
                                            <th scope="col">CPF</th>
                                            <th scope="col">Identidade</th>
                                            <th scope="col">Endereço</th>
                                            <th scope="col">Bairro</th>
                                            <th scope="col">Cidade</th>
                                            <th scope="col">Telefone</th>
                                            <th scope="col">WhatsApp</th>
                                            <th scope="col">Responsável</th>
                                            <th scope="col">Contato</th>
                                            <th scope="col">PCD em casa</th>
                                            <th scope="col">Deficiência(s)</th>
                                            <th scope="col">Obs</th>
                                            <th scope="col">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // $sql = "SELECT * FROM tbAluno WHERE Habilitado=1 ORDER BY Nome ASC";
                                        $sql = "SELECT a.*,
                                                    (SELECT GROUP_CONCAT(
                                                        DISTINCT CASE
                                                            WHEN m.Habilitado = 1 THEN c.NomeTurma
                                                            ELSE CONCAT(c.NomeTurma, ' (Desmatriculado)')
                                                        END
                                                        ORDER BY c.NomeTurma SEPARATOR '; '
                                                    )
                                                    FROM tbMatricula m
                                                    JOIN tbTurma c ON m.IdTurma = c.IdTurma
                                                    JOIN tbCurso cu ON cu.IdCurso = m.IdCurso
                                                    WHERE m.IdUsuario = a.IdUsuario
                                                      AND c.Habilitado = 1
                                                      AND cu.Habilitado = 1) AS TurmasAtivas,

                                                    (SELECT GROUP_CONCAT(DISTINCT c.NomeTurma ORDER BY c.NomeTurma SEPARATOR '; ')
                                                    FROM tbMatricula m
                                                    JOIN tbTurma c ON m.IdTurma = c.IdTurma
                                                    JOIN tbCurso cu ON cu.IdCurso = m.IdCurso
                                                    WHERE m.IdUsuario = a.IdUsuario
                                                      AND (c.Habilitado = 0 OR cu.Habilitado = 0)) AS HistoricoTurmas,
                                                    
                                                    (SELECT GROUP_CONCAT(p.NomeProjeto SEPARATOR '/ ')
                                                    FROM tbInteresse i
                                                    JOIN tbProjeto p ON p.IdProjeto = i.IdProjeto
                                                    WHERE i.IdUsuario = a.IdUsuario AND i.Valor = 1) AS Interesses,
                                                    u1.Nome AS NomeUserEnt,
                                                    u2.Nome AS NomeUserAlt
                                                    FROM tbAluno a
                                                    LEFT JOIN tbUser u1 ON u1.IdColaborador = a.IdColaboradorEnt 
                                                    LEFT JOIN tbUser u2 ON u2.IdColaborador = a.IdColaboradorAlt 
                                                    WHERE a.Habilitado = 1 ORDER BY a.Nome ASC
                                                ";
                                        $busca = $pdo->query($sql);
                                        $rowCount = $busca->rowCount();

                                        while ($dados = $busca->fetch(PDO::FETCH_ASSOC)) {
                                            $IdUsuario = $dados['IdUsuario'];
                                            $Foto = $dados['Foto'];
                                            $Nome = $dados['Nome'];
                                            $TurmasAtivas = $dados['TurmasAtivas'] ?? '';
                                            $HistoricoTurmas = $dados['HistoricoTurmas'] ?? '';
                                            $Nascimento = $dados['Nascimento'];
                                            $CPF = $dados['CPF'];
                                            $Identidade = $dados['Identidade'];
                                            $Endereco = $dados['Endereco'];
                                            $Bairro = $dados['Bairro'];
                                            $Cidade = $dados['Cidade'];
                                            $UF = $dados['UF'];
                                            $Telefone = $dados['Telefone'];
                                            $WhatsApp = $dados['WhatsApp'];
                                            $NomeResp1 = $dados['NomeResp1'];
                                            $WhatsAppResp1 = $dados['WhatsAppResp1'];
                                            $Obs = $dados['Obs'];
                                            $IdColaboradorEnt = $dados['IdColaboradorEnt'];
                                            $IdColaboradorAlt = $dados['IdColaboradorAlt'];
                                            $TimeAlterado = $dados['TimeAlterado'];

                                            $NomeUserEnt = $dados['NomeUserEnt'];
                                            $NomeUserAlt = $dados['NomeUserAlt'];


                                            // Construindo o conteúdo do tooltip
                                            $tooltip = "";
                                            if (!empty($NomeUserEnt)) {
                                                $tooltip .= "Criado por: <strong>$NomeUserEnt</strong><br>";
                                            }
                                            if (!empty($NomeUserAlt) && !empty($TimeAlterado)) {
                                                $tooltip .= "Alterado por: <strong>$NomeUserAlt</strong> em <strong>$TimeAlterado</strong>";
                                            }

                                            $tooltipAttr = $tooltip ? 'data-bs-toggle="tooltip" data-bs-html="true" title="' . htmlspecialchars($tooltip, ENT_QUOTES) . '"' : '';
                                        ?>
                                            <tr <?= $tooltipAttr ?>>
                                                <td><input type="checkbox" class="form-radio-input doacao" name="checkbox[]" value="<?php echo $IdUsuario ?>" id="CheckID<?php echo $IdUsuario ?>"></td>
                                                <td><?php echo $IdUsuario ?></td>
                                                <td>
                                                    <span class="hover-container">
                                                        <image src="<?php echo $_SESSION['BASE_URL']; ?>/assets/img/fotos/<?php echo $Foto ?>" class="rounded-circle img-cover hover-img" width='40px' height='40px'>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="#" onclick="abrirFormularioEdicao('<?php echo $IdUsuario ?>', '<?php echo preg_replace('/[^0-9]/', '', $CPF) ?>')" style="text-decoration:none; color:inherit;">
                                                        <?php echo htmlspecialchars($Nome) ?>
                                                    </a>
                                                </td>

                                                <?php
                                                $Interesses = $dados['Interesses'] ?? '';
                                                $InteressesCurto = (strlen($Interesses) > 50)
                                                    ? substr($Interesses, 0, 50) . "..."
                                                    : $Interesses;
                                                ?>
                                                <td
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    data-bs-html="true"
                                                    title="<?php echo htmlspecialchars($Interesses, ENT_QUOTES); ?>">
                                                    <?php echo htmlspecialchars($InteressesCurto); ?>
                                                </td>


                                                
                                                <?php
                                                $versatilis = (int) ($dados['Versatilis'] ?? 0);
                                                ?>
                                                <td class="text-center">
                                                    <?php if ($versatilis === 1): ?>
                                                        <img src="<?php echo $_SESSION['BASE_URL']; ?>/assets/img/logos/logo_sys.png" alt="Versatilis" width="140" height="30">
                                                    <?php else: ?>
                                                        Não
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $TurmasAtivas ?></td>
                                                <td><?php echo $HistoricoTurmas ?></td>
                                                <td><?php echo $Nascimento ?></td>

                                                <!-- Cálculo Idade -->
                                                <td>
                                                    <?php
                                                    // Calcula idade
                                                    if (!empty($Nascimento)) {
                                                        // Converte dd/mm/YYYY → YYYY-mm-dd (compatível com DateTime)
                                                        $data = DateTime::createFromFormat('d/m/Y', $Nascimento);
                                                        if ($data) {
                                                            $hoje = new DateTime();
                                                            $idade = $data->diff($hoje)->y;
                                                            echo $idade . " anos";
                                                        } else {
                                                            echo "-";
                                                        }
                                                    } else {
                                                        echo "-";
                                                    }
                                                    ?>
                                                </td>

                                                <td><?php echo $CPF ?></td>
                                                <td><?php echo $Identidade ?></td>
                                                <td><?php echo $Endereco ?></td>
                                                <td><?php echo $Bairro ?></td>
                                                <td><?php echo $Cidade ?></td>
                                                <td><?php echo $Telefone ?></td>
                                                <td><?php echo $WhatsApp ?></td>
                                                <td><?php echo $NomeResp1 ?></td>
                                                <td><?php echo $WhatsAppResp1 ?></td>
                                                <?php
                                                $pcdEmCasa = $dados['PCDEmCasa'] ?? null;
                                                $deficiencias = trim($dados['DeficienciasCasa'] ?? '');

                                                $pcdTexto = ($pcdEmCasa === '1' || $pcdEmCasa === 1) ? 'Sim' : 'Não';
                                                ?>
                                                <td class="text-center">
                                                    <?php echo $pcdTexto; ?>
                                                </td>

                                                <td
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    data-bs-html="true"
                                                    title="<?php echo htmlspecialchars($deficiencias, ENT_QUOTES); ?>">
                                                    <?php
                                                    echo !empty($deficiencias)
                                                        ? (strlen($deficiencias) > 40 ? substr($deficiencias, 0, 40) . "..." : htmlspecialchars($deficiencias))
                                                        : '-';
                                                    ?>
                                                </td>

                                                <?php
                                                $obsTexto = trim($Obs);
                                                $obsCurto = (strlen($obsTexto) > 50)
                                                    ? substr($obsTexto, 0, 50) . "..."
                                                    : $obsTexto;
                                                ?>
                                                <td
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    data-bs-html="true"
                                                    title="<?php echo htmlspecialchars($obsTexto, ENT_QUOTES); ?>">
                                                    <?php echo htmlspecialchars($obsCurto); ?>
                                                </td>


                                                <td>
                                                    <?php
                                                    if ($_SESSION['Tipo'] == "Anulado") {
                                                        echo '<button type="button" class="btn btn-primary btn-disabled" disabled>Desabilitado</button>';
                                                    } else {
                                                        echo '<div class="d-flex justify-content-center gap-2">';
                                                        echo '<form method="GET" action="beneficiarioController.php" style="display:inline;">
                                                                <input type="hidden" name="cpf" value="' . preg_replace('/[^0-9]/', '', $CPF) .'">
                                                                <input type="hidden" name="id" value="' . $IdUsuario .'">
                                                                <button type="submit" class="btn btn-warning">
                                                                    <i class="fa-solid fa-pen-to-square" data-feather="edit-3"></i>
                                                                </button>
                                                            </form>';
                                                        echo '<form method="POST" action="beneficiarioController.php" style="display:inline;">
                                                                    <input type="hidden" name="delete" value="' . $IdUsuario . '">
                                                                    <button type="submit" class="btn btn-danger" onclick="return confirm("Tem certeza que deseja excluir este beneficiário?");">
                                                                        <i class="bi bi-trash-fill" data-feather="trash-2">"></i>
                                                                    </button>
                                                                </form>';
                                                        echo '</div>';
                                                    }
                                                    ?>
                                                    
                                                </td>
                                            </tr>
                                        <?php
                                        } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>


            <footer class="footer">
                <?php
                require_once $_SESSION['BASE_PATH'] . '/template/footer.php';
                ?>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>

    <!-- Cria link para envio _blank da pagina editar -->
    <script>
        function abrirFormularioEdicao(idUsuario, cpf) {
            // Cria um formulário temporário
            var form = document.createElement("form");
            form.method = "GET";
            form.action = "beneficiarioController.php";
            form.target = "_self";

            // Cria o campo hidden
            var input = document.createElement("input");
            input.type = "hidden";
            input.name = "id";
            input.value = idUsuario;
            form.appendChild(input);

            var input = document.createElement("input");
            input.type = "hidden";
            input.name = "cpf";
            input.value = cpf;
            form.appendChild(input);

            // Adiciona e envia
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    </script>


    <!-- Envia dados checkbox para cadastrar doação -->
    <script>
        // Inicializa o DataTable
        var table = $('#minhaTabela').DataTable({
            order: [
                [3, 'asc']
            ],
            columnDefs: [{
                targets: '_all', // Aplica a todas as colunas
                type: 'locale-agnostic', // Define o tipo de busca como o plug-in criado
            }],
        });

        var checkboxesSelecionados = {};

        $(document).ready(function() {
            // Monitora mudanças nos checkboxes
            $('#minhaTabela').on('change', 'input[name="checkbox[]"]', function() {
                var id = $(this).val();
                if (this.checked) {
                    checkboxesSelecionados[id] = true;
                } else {
                    delete checkboxesSelecionados[id];
                }
            });

            $('#matricularAluno').click(function() {
                matricularAluno.innerHTML = `
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Aguarde...
                `;
                var selecionados = Object.keys(checkboxesSelecionados);

                if (selecionados.length > 0) {

                    // Ocultar a tabela e mostra o loader 
                    document.getElementById("loader").style.display = "block";
                    document.getElementById("minhaTabela").style.display = "none";
                    let matricular = "<?php echo  $_SESSION['BASE_URL'] . '/app/matricula/formMatricula.php' ?>"
                    // Cria um form dinamicamente para enviar os dados
                    var form = $('<form>', {
                        'action': matricular,
                        'method': 'post'
                    });

                    // Adiciona um input para cada ID selecionado ao form
                    $.each(selecionados, function(index, valor) {
                        $(form).append($('<input>', {
                            'type': 'hidden',
                            'name': 'checkbox[]',
                            'value': valor
                        }));
                    });

                    // Adiciona o form ao body e o envia
                    $(form).appendTo('body').submit();
                } else {
                    alert('Selecione pelo menos uma doação para enviar.');
                }
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    html: true
                });
            });
        });
    </script>

</body>

</html>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        mostrarOverlay({
            mensagem: 'Carregando beneficiários...',
            carregando: true
        });

        setTimeout(() => {
            const tabela = document.getElementById("minhaTabela");
            if (tabela) tabela.style.display = "table";

            const tituloElement = document.getElementById("titulo");
            if (tituloElement) {
                tituloElement.textContent = "Lista de Beneficiários: <?php echo $rowCount ?>";
            }

            esconderOverlay();

            setTimeout(() => {
                const alertElement = document.querySelector(".alert");
                if (alertElement) alertElement.style.display = "none";
            }, 2000);
        }, 400);
    });
</script>

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
<!-- Importação do Jquery Mask -->
<!-- include da importação da mascara dos inputs -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.12/jquery.mask.min.js"></script>
<script type="text/javascript">
    $("#telefone, #celular").mask("(00) 00000-0000"); //000 000 0000 eua
    $('.date').mask('00/00/0000');
    $('.time').mask('00:00:00');
    $('.date_time').mask('00/00/0000 00:00:00');
    $('.cep').mask('00000-000');
    $('.phone').mask('0000-0000');
    $('.phone_with_ddd').mask('(00) 0000-0000');
    $('.phone_us').mask('(000) 000-0000');
    $('.mixed').mask('AAA 000-S0S');
    $('.cpf').mask('000.000.000-00', {
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
        } else {
            $("#CPF").mask("99.999.999/9999-99");
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

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const loader = document.getElementById("loader");
        const tabela = document.getElementById("minhaTabela");
        if (loader) loader.style.display = "none";
        if (tabela) tabela.style.display = "table";
        const tituloElement = document.getElementById("titulo");
        if (tituloElement) {
            tituloElement.textContent = "Lista de Beneficiários: <?php echo $rowCount ?>";
        }
        setTimeout(() => {
            const alertElement = document.querySelector(".alert");
            if (alertElement) alertElement.style.display = "none";
        }, 2000);
    });
</script>

<script>
    $(function() {
        $('#editarModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            var IdUsuario = button.data('id') // data-id
            var nome = button.data('nome')
            var nascimento = button.data('nascimento')
            var cpf = button.data('cpf')
            var whatsapp = button.data('whatsapp')

            var modal = $(this)
            //aplica valor ao id
            modal.find('#nome').val(nome)
            modal.find('#IdUsuario').val(IdUsuario)
            modal.find('#Nascimento').val(nascimento)
            modal.find('#CPF').val(cpf)
            modal.find('#whatsapp').val(whatsapp)

        });
    });
</script>

<script>
    $(function() {
        $('#excluirModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            var fotoaluno = button.data('fotoaluno')
            var IdUsuario = button.data('id') // data-id
            var nome = button.data('nome')
            var nascimento = button.data('nascimento')
            var cpf = button.data('cpf')
            var whatsapp = button.data('whatsapp')

            var modal = $(this)
            //aplica valor ao id
            modal.find('#fotoaluno').attr('src', fotoaluno);
            modal.find('#nome').val(nome)
            modal.find('#IdUsuario').val(IdUsuario)
            modal.find('#Nascimento').val(nascimento)
            modal.find('#CPF').val(cpf)
            modal.find('#whatsapp').val(whatsapp)
        });
    });
</script>


<script>
    function confirmExclusion() {
        return confirm("Este cadastro será excluído!");
    }
</script>

<!-- CODIGO EXPORTA EXCEL - DADOS COMPLETOS -->
<!-- <script>
    document.getElementById('btnExportExcel').addEventListener('click', function() {
        fetch('exportExcel.php')
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    alert('Nenhum dado encontrado.');
                    return;
                }

                const headers = [
                    "ID", "Nome", "Interesses", "Paciente", "Turmas", "Nascimento", "CPF", "Endereço", "Bairro", "Cidade", "Telefone", "WhatsApp", "Responsável", "Contato", "Obs"
                ];

                const dadosFormatados = data.map(item => [
                    item.IdUsuario,
                    item.Nome,
                    item.Interesses,
                    (item.Versatilis == 1 ? 'Sim' : 'Não'),
                    item.Turmas,
                    item.Nascimento,
                    item.CPF,
                    item.Endereco,
                    item.Bairro,
                    item.Cidade,
                    item.Telefone,
                    item.WhatsApp,
                    item.NomeResp1,
                    item.WhatsAppResp1,
                    item.Obs
                ]);

                const wb = XLSX.utils.book_new();
                const ws = XLSX.utils.aoa_to_sheet([headers, ...dadosFormatados]);
                XLSX.utils.book_append_sheet(wb, ws, "Beneficiarios");

                XLSX.writeFile(wb, "listagemBeneficiarios.xlsx");
            })
            .catch(error => {
                console.error("Erro ao exportar:", error);
                alert("Erro ao gerar o Excel. Veja o console.");
            });
    });
</script> -->

<script>
function extrairTexto(html) {
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.textContent.trim();
}

function extrairPaciente(html) {
    if (html.includes('logo_sys.png')) {
        return 'Sim';
    }
    return extrairTexto(html) || 'Não';
}
</script>

<!-- EXPORTA DADOS FILTRADOS DA TABLE -->
<script>
    document.getElementById('btnExportExcel').addEventListener('click', function() {

        // Pega somente as linhas FILTRADAS no DataTable
        const dadosFiltrados = table.rows({
            search: 'applied'
        }).data();

        if (dadosFiltrados.length === 0) {
            alert('Nenhum dado encontrado com o filtro atual.');
            return;
        }

        const headers = [
            "ID", "Nome", "Interesses", "Paciente", "Turmas Ativas", "Histórico de turmas", "Nascimento", "CPF",
            "Endereço", "Bairro", "Cidade", "Telefone", "WhatsApp",
            "Responsável", "Contato", "Obs"
        ];

        const dadosFormatados = [];

        dadosFiltrados.each(function(row) {

            /*
              Atenção aos índices:
              Eles seguem a ordem das colunas da tabela HTML
            */

            dadosFormatados.push([
                row[1], // IdUsuario
                extrairTexto(row[3]), // Nome
                row[4], // Interesses
                extrairPaciente(row[5]), // Paciente (texto já renderizado)
                row[6], // Turmas Ativas
                row[7], // Histórico de turmas
                row[8], // Nascimento
                row[10], // CPF
                row[12], // Endereço
                row[13], // Bairro
                row[14], // Cidade
                row[15], // Telefone
                row[16], // WhatsApp
                row[17], // Responsável
                row[18], // Contato
                row[21] // Obs
            ]);
        });

        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet([headers, ...dadosFormatados]);
        XLSX.utils.book_append_sheet(wb, ws, "Beneficiarios");

        XLSX.writeFile(wb, "listagemBeneficiarios_filtrado.xlsx");
    });
</script>
