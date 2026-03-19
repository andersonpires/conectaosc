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

require_once $_SESSION['BASE_PATH'] . '/checa-token.php';
$matricula = null; // Inicializa a variável com null por padrão

require_once 'beneficiarioModel.php';

if (isset($_GET['matricula']) && $_GET['matricula'] == 1) {
    $matricula = 1;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <?php require_once $_SESSION['BASE_PATH'] . '/template/header.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <style>
        .img-cover {
            object-fit: cover;
            object-position: center;
        }

        .table {
            display: none;
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
            overflow: visible;
        }

        .dataTables_wrapper,
        .dataTables_wrapper .row,
        .dataTables_scrollBody {
            overflow: visible !important;
        }

        #minhaTabela td {
            overflow: visible;
        }

        #minhaTabela {
            table-layout: fixed;
            width: 100%;
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
        #minhaTabela th:nth-child(11),
        #minhaTabela td:nth-child(11) { width: 8%; }
    </style>
    <style>
        #matricularAluno {
            position: fixed;
            right: 10px;
            top: 100px;
            z-index: 100;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            border: none;
            font-weight: bold;
            text-align: center;
            padding: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #ffffff !important;
            background-color: rgb(7, 9, 12) !important;
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
        }

        .dataTables_filter label {
            display: flex;
            align-items: center;
        }

        .dataTables_filter input {
            margin-left: 0.5rem;
            border: 2px solid #007BFF;
        }

        #overlayLoading {
            display: none;
            justify-content: center;
            align-items: center;
        }

        @media (min-width: 768px) {
            .table-layout-auto {
                table-layout: auto;
                width: 100%;
                word-wrap: break-word;
                white-space: normal;
            }

            .table-layout-auto th,
            .table-layout-auto td {
                word-break: break-word;
                white-space: normal;
                max-width: 200px;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }

        @media (max-width: 767px) {
            .table-layout-auto {
                table-layout: auto;
                width: 100%;
                white-space: nowrap;
            }

            .table-layout-auto th,
            .table-layout-auto td {
                word-wrap: break-word;
                text-align: left;
            }
        }
    </style>
    <link rel="stylesheet" href="<?php echo $_SESSION['BASE_URL']; ?>/assets/css/overlayNotifica.css">
    <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/overlayNotifica.js"></script>
</head>

<!-- Carregando jQuery de fontes externas -->
<script src="//code.jquery.com/jquery-3.2.1.min.js"></script>
<!-- Alterado para caminho local na pasta assets/js -->
<script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/jquery.dataTables.min.js"></script>

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
            <?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php'; ?>

            <main class="content">
                <?php if (isset($matricula) && $matricula == 1) { ?>
                    <button type="button" class="btn btn-success mb-3" id="matricularAluno">Matricular <br> Aluno</button>
                <?php } ?>

                <h4 class="h3 mb-3" id="titulo">Lista de Alunos</h4>
                <?php
                // Exibe mensagem vinda da URL
                $msg = "";
                $url = $_SERVER['REQUEST_URI'];
                $urlDecoded = urldecode($url);
                if (strpos($urlDecoded, '=') !== false) {
                    $params = explode('&', $urlDecoded);
                    $tipomsg = explode('?', $params[0])[1];
                    $tipomsg = explode('=', $tipomsg)[0];
                    $msg = explode('=', $params[0])[1];
                }

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
                <div class="col-12">
                    <div class="card shadow-lg">
                        <div class="card-body">
                            <a href="beneficiarioController.php" class="btn btn-primary mb-3" id="novoAluno">Cadastrar novo aluno</a>
                            <div class="table-responsive mb-3">
                                <table id="minhaTabela" class="table table-striped table-hover datatable-custom table-layout-auto">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="text-center">*</th>
                                            <th scope="col" class="text-center">Id</th>
                                            <th scope="col" class="text-center">Foto</th>
                                            <th scope="col">Nome</th>
                                            <th scope="col">Turmas Ativas</th>
                                            <th scope="col">Histórico de turmas</th>
                                            <th scope="col" class="text-center">Nascimento</th>
                                            <th scope="col" class="text-center">Telefone</th>
                                            <th scope="col" class="text-center">WhatsApp</th>
                                            <th scope="col">Obs</th>
                                            <th scope="col" class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
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
                                                      AND (c.Habilitado = 0 OR cu.Habilitado = 0)) AS HistoricoTurmas
                                                FROM tbAluno a
                                                WHERE a.Habilitado = 1
                                                ORDER BY a.Nome ASC";
                                        $busca = $pdo->query($sql);
                                        $rowCount = $busca->rowCount();
                                        while ($dados = $busca->fetch(PDO::FETCH_ASSOC)) {
                                            $IdUsuario = $dados['IdUsuario'];
                                            $Foto = $dados['Foto'];
                                            $Nome = $dados['Nome'];
                                            $TurmasAtivas = $dados['TurmasAtivas'] ?? '';
                                            $HistoricoTurmas = $dados['HistoricoTurmas'] ?? '';
                                            $Apelido = $dados['Apelido'];
                                            if (isset($Apelido) && $Apelido !== "" && $Apelido !== null) {
                                                $Nome = "(" . $Apelido . ") " . $Nome;
                                            }
                                            $Nascimento = $dados['Nascimento'];
                                            $CPF = $dados['CPF'];
                                            $Endereco = $dados['Endereco'];
                                            $Bairro = $dados['Bairro'];
                                            $Cidade = $dados['Cidade'];
                                            $UF = $dados['UF'];
                                            $WhatsApp = $dados['WhatsApp'];
                                            $NomeResp1 = $dados['NomeResp1'];
                                            $WhatsAppResp1 = $dados['WhatsAppResp1'];
                                            $Obs = $dados['Obs'];
                                            $NIS = $dados['NIS'];
                                            $UF = $dados['UF'];
                                            $SexoBio = $dados['SexoBio'];
                                            $Identidade = $dados['Identidade'];
                                            $Telefone = $dados['Telefone'];
                                            $CpfResp1 = $dados['CpfResp1'];
                                            $TelefoneResp1 = $dados['TelefoneResp1'];
                                            $NomeResp2 = $dados['NomeResp2'];
                                            $Escolaridade = $dados['Escolaridade'];
                                            $EstadoCivil = $dados['EstadoCivil'];
                                            $Profissao = $dados['Profissao'];
                                        ?>
                                            <tr>
                                                <td class="text-center"><input type="checkbox" class="form-radio-input doacao" name="checkbox[]" value="<?php echo $IdUsuario ?>" id="CheckID<?php echo $IdUsuario ?>"></td>
                                                <td class="text-center"><?php echo $IdUsuario ?></td>
                                                <td class="text-center">
                                                    <span class="hover-container">
                                                        <img src="<?php echo $_SESSION['BASE_URL'] ?>/assets/img/fotos/<?php echo $Foto ?>" class="rounded-circle img-cover hover-img" width="40px" height="40px">
                                                    </span>
                                                </td>
                                                <td><?php echo $Nome ?></td>
                                                <td><?php echo $TurmasAtivas ?></td>
                                                <td><?php echo $HistoricoTurmas ?></td>
                                                <td class="text-center"><?php echo $Nascimento ?></td>
                                                <td class="text-center"><?php echo $Telefone ?></td>
                                                <td class="text-center"><?php echo $WhatsApp ?></td>
                                                <td><?php echo $Obs ?></td>
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
                                                        echo '<form method="GET" action="' . $_SESSION['BASE_URL'] . '/get/getPDF_ficha_cadastro.php" target="_blank" style="display:inline;">
                                                                <input type="hidden" name="id" value="' . $IdUsuario . '">
                                                                <button type="submit" class="btn btn-success">
                                                                    <i class="fa-solid fa-file-pdf"></i>
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
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="footer">
                <?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php'; ?>
            </footer>
        </div>
    </div>
    <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>
    <script>
        $.fn.dataTable.ext.type.search['locale-agnostic'] = function(data) {
            if (typeof data !== 'string') return data;
            return data
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/ç/g, 'c')
                .toLowerCase();
        };

        $(document).ready(function() {
            const table = $('#minhaTabela').DataTable({
                columnDefs: [{
                    targets: '_all',
                    type: 'locale-agnostic',
                }],
                order: [
                    [3, 'asc']
                ]
            });

            $('#minhaTabela_filter input').on('input', function() {
                const normalizedInput = $(this).val()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/ç/g, 'c');
                $(this).val(normalizedInput);
                table.search(normalizedInput).draw();
            });

            const checkboxesSelecionados = {};

            $('#minhaTabela').on('change', 'input[name="checkbox[]"]', function() {
                const id = $(this).val();
                if (this.checked) {
                    checkboxesSelecionados[id] = true;
                } else {
                    delete checkboxesSelecionados[id];
                }
            });

            $('#matricularAluno').click(function() {
                const selecionados = Object.keys(checkboxesSelecionados);
                if (selecionados.length > 0) {
                    mostrarOverlay({
                        mensagem: 'Redirecionando para matrícula...',
                        carregando: true
                    });

                    const form = $('<form>', {
                        action: "<?php echo $_SESSION['BASE_URL'] . '/app/matricula/formMatricula.php' ?>",
                        method: 'post'
                    });

                    $.each(selecionados, function(index, valor) {
                        $(form).append($('<input>', {
                            type: 'hidden',
                            name: 'checkbox[]',
                            value: valor
                        }));
                    });

                    $('body').append(form);
                    form.submit();
                } else {
                    alert('Selecione pelo menos um aluno para matricular.');
                }
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

<!-- Importação do Jquery Mask -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.12/jquery.mask.min.js"></script>
<script type="text/javascript">
    $("#telefone, #celular").mask("(00) 00000-0000");
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
        var elem = this;
        setTimeout(function() {
            elem.selectionStart = elem.selectionEnd = 10000;
        }, 0);
        var currentValue = $(this).val();
        $(this).val('');
        $(this).val(currentValue);
    });
</script>

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
        }, 400); // você pode ajustar esse tempo conforme a fluidez
    });
</script>

<script>
    $(function() {
        $('#editarModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var IdUsuario = button.data('id');
            var nome = button.data('nome');
            var nascimento = button.data('nascimento');
            var cpf = button.data('cpf');
            var whatsapp = button.data('whatsapp');
            var modal = $(this);
            modal.find('#nome').val(nome);
            modal.find('#IdUsuario').val(IdUsuario);
            modal.find('#Nascimento').val(nascimento);
            modal.find('#CPF').val(cpf);
            modal.find('#whatsapp').val(whatsapp);
        });
    });
</script>

<script>
    $(function() {
        $('#excluirModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var fotoaluno = button.data('fotoaluno');
            var IdUsuario = button.data('id');
            var nome = button.data('nome');
            var nascimento = button.data('nascimento');
            var cpf = button.data('cpf');
            var whatsapp = button.data('whatsapp');
            var modal = $(this);
            modal.find('#fotoaluno').attr('src', fotoaluno);
            modal.find('#nome').val(nome);
            modal.find('#IdUsuario').val(IdUsuario);
            modal.find('#Nascimento').val(nascimento);
            modal.find('#CPF').val(cpf);
            modal.find('#whatsapp').val(whatsapp);
        });
    });
</script>

<script>
    function confirmExclusion() {
        return confirm("Este cadastro será excluído!");
    }
</script>
