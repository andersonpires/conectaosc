<?php
// Inicialização da sessão e configuração de timezone
session_set_cookie_params(['httponly' => true]);
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
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php
    require_once $_SESSION['BASE_PATH'] . '/template/header.php';
    ?>
    <style>
        .img-cover {
            object-fit: cover;
            object-position: center;
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

        /* Garantir que a tabela role horizontalmente em telas pequenas */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            /* Melhora a rolagem no iOS */
        }

        /* Garantir que as colunas se ajustem melhor */
        .table-layout-auto {
            table-layout: auto;
            width: 100%;
            white-space: nowrap;
            /* Impede quebra de linha nas células */
        }

        /* Definir rolagem em telas menores */
        @media (max-width: 767px) {
            .table-responsive {
                display: block;
                width: 100%;
                overflow-x: auto;
            }

            .table-layout-auto th,
            .table-layout-auto td {
                white-space: nowrap;
                /* Evita que o texto das colunas quebre */
                text-align: left;
            }
        }

        .filtro-situacao-turma {
            background-color: #f1f3f5;
            color: #000000;
            border: 1px solid #d0d7de;
        }

        .filtro-situacao-turma.active.filtro-ativo {
            background-color: #ffc107;
            color: #ffffff;
            border-color: #ffc107;
        }

        .filtro-situacao-turma.active.filtro-concluido {
            background-color: #198754;
            color: #ffffff;
            border-color: #198754;
        }

        .filtro-situacao-turma.active.filtro-todos {
            background-color: #0d6efd;
            color: #ffffff;
            border-color: #0d6efd;
        }
    </style>
</head>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script src="//code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/jquery.dataTables.min.js"></script>
<script>
$.fn.dataTable.ext.type.search['locale-agnostic'] = function(data) {
    if (typeof data !== 'string') {
        return data;
    }
    return data
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/ç/g, 'c')
        .toLowerCase();
};

$(document).ready(function() {
    var table = $('#minhaTabela').DataTable({
        "pageLength": 50,
        "scrollX": true,  // Adiciona barra de rolagem lateral se necessário
        "autoWidth": false, // Impede que a largura da tabela fique fixa e cause cortes
        "responsive": true, // Permite melhor responsividade no DataTables
        "columnDefs": [{
            "targets": "_all",
            "type": "locale-agnostic"
        }],
        "order": [
            [0, 'asc']
        ],
        "language": {
            url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json"
        }
    });

    var statusColumnIndex = 1;
    function normalizeFiltroValue(value) {
        return value
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/ç/g, 'c').toLowerCase();
    }

    $(document).on('click', '.filtro-situacao-turma', function() {
        var value = $(this).data('value');
        $('.filtro-situacao-turma').removeClass('active');
        $(this).addClass('active');
        if (value === 'Todos') {
            table.column(statusColumnIndex).search('').draw();
            return;
        }
        table.column(statusColumnIndex).search(normalizeFiltroValue(value), false, false).draw();
    });
});

</script>

<body>
    <div class="wrapper">
        <?php
        require_once $_SESSION['BASE_PATH'] . '/template/menu.php';
        ?>

        <div class="main">
            <?php
            require_once $_SESSION['BASE_PATH'] . '/template/topo.php';
            ?>
            <main class="content">
                <div id="loader" class="loader">
                    <img src="https://www.blogson.com.br/wp-content/uploads/2017/10/loading-gif-transparent-10.gif" width="150" alt="Carregando...">
                </div>
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3">Turmas</h1>
                    <?php

                    // Lista com nome cursos
                    $listaCursos = [];
                    require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
                    $sql = "SELECT IdCurso, NomeCurso FROM tbCurso";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute();
                    $listaCursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Obter a URL atual
                    $msg = "";
                    $url = $_SERVER['REQUEST_URI'];
                    $urlDecoded = urldecode($url);
                    if (strpos($urlDecoded, '=') !== false) {
                        $params = explode('&', $urlDecoded);
                        $msg = explode('=', $params[0])[1]; // Pega o valor após o '='

                        // Extrair o valor do parâmetro "msg" (assumindo que ele seja o primeiro)
                        $msg = explode('=', $params[0])[1]; // Pega o valor após o '='
                    }

                    // Exibir a mensagem como variável
                    if ($msg != "") {
                    ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo $msg ?>
                        </div>
                    <?php
                    }
                    ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="cadTurma.php" id="formulario" enctype='multipart/form-data' method='post'>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label for="NomeTurma_0" class="form-label">Nome da turma</label>
                                            <div id="nomesTurmaContainer">
                                                <div class="input-group mb-2 nome-turma-item">
                                                    <input type="text" class="form-control" id="NomeTurma_0" name='NomeTurma[]' autocomplete="off">
                                                    <button type="button" class="btn btn-outline-danger remover-nome-turma" disabled>Remover</button>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="adicionarNomeTurma">Adicionar nome de turma</button>
                                        </div>
                                        <div class="col-6">
                                            <label for="exampleFormControlInput1" class="form-label">Cursos</label>
                                            <select class="form-select" aria-label="Selecione" name='IdCurso'>
                                                <?php
                                                foreach ($listaCursos as $curso) {
                                                    echo '<option value="' . $curso['IdCurso'] . '">' . $curso['NomeCurso'] . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-4">
                                            <label for="exampleFormControlInput1" class="form-label">Município</label>
                                            <input type="text" class="form-control" id="Municipio" name='Municipio' autocomplete="off">
                                        </div>
                                        <div class="col-4">
                                            <label for="exampleFormControlInput1" class="form-label">Local</label>
                                            <input type="text" class="form-control" id="Local" name='Local' autocomplete="off">
                                        </div>
                                        <div class="col-4">
                                            <label for="max-matriculas" class="form-label">Máximo de pessoas na turma</label>
                                            <input type="text" class="form-control" id="max-matriculas" name="max-matriculas" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class="form-label">Observações</label>
                                        <textarea rows="7" class="form-control" aria-label="Obs" id="exampleFormControlInput1" name='Obs' autocomplete="off"></textarea>
                                    </div>
                                    <div style="text-align: right;">
                                        <button type="button" id="submitButton" class="btn btn-primary">Cadastrar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <main class="content">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Lista de Turmas Cadastradas</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label">Filtro de situação</label>
                                    <div class="btn-group" role="group" aria-label="Filtro de situação">
                                        <button type="button" class="btn filtro-situacao-turma filtro-ativo" data-value="Ativo">
                                            Ativo
                                        </button>
                                        <button type="button" class="btn filtro-situacao-turma filtro-concluido" data-value="Concluído">
                                            Concluído
                                        </button>
                                        <button type="button" class="btn filtro-situacao-turma filtro-todos active" data-value="Todos">
                                            Todos
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table id="minhaTabela" class="table table-striped table-hover datatable-custom table-layout-auto">
                                    <thead>
                                        <tr>
                                            <th scope="col">Nome da Turma</th>
                                            <th scope="col">Situação</th>
                                            <th scope="col">Curso</th>
                                            <th scope="col">Município</th>
                                            <th scope="col">Local</th>
                                            <th scope="col">Observação</th>
                                            <th scope="col">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT * FROM tbTurma";
                                        $stmt = $pdo->prepare($sql);
                                        $stmt->execute();
                                        $busca2 = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($busca2 as $dadosCurso2) {
                                            $IdTurma = $dadosCurso2['IdTurma'];
                                            $NomeTurma = $dadosCurso2['NomeTurma'];
                                            if ($dadosCurso2['Habilitado']) {
                                                $Habilitado = "Ativo";
                                            } else {
                                                $Habilitado = "Concluído";
                                            }
                                            $IdCurso = $dadosCurso2['IdCurso'];
                                            $Municipio = $dadosCurso2['Municipio'];
                                            $Local = $dadosCurso2['Local'];
                                            $MaxMatriculas = $dadosCurso2['MaxMatriculas'];
                                            $Obs = $dadosCurso2['Obs'];
                                        ?>
                                            <tr>
                                                <td>
                                                    <?php echo $NomeTurma ?>
                                                </td>
                                                <td>
                                                    <?php if ($Habilitado === "Concluído") { ?>
                                                        <span class="badge bg-success">Concluído</span>
                                                    <?php } else { ?>
                                                        <span class="badge bg-warning text-dark">Ativo</span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $nomeCurso = "";
                                                    foreach ($listaCursos as $curso) {
                                                        if ($curso['IdCurso'] == $IdCurso) {
                                                            echo $curso['NomeCurso'];
                                                            $nomeCurso = $curso['NomeCurso'];
                                                        }
                                                    };
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php echo $Municipio ?>
                                                </td>
                                                <td>
                                                    <?php echo $Local ?>
                                                </td>
                                                <td>
                                                    <?php echo $Obs ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editarModal" data-id='<?php echo $IdTurma ?>' data-nometurma="<?php echo $NomeTurma ?>" data-curso='<?php echo $IdCurso ?>' data-nomecurso="<?php echo $nomeCurso ?>" data-municipio="<?php echo $Municipio ?>" data-local="<?php echo $Local ?>" data-maxmatriculas="<?php echo $MaxMatriculas ?>" data-habilitado="<?php echo $Habilitado ?>" data-obs="<?php echo $Obs ?>">
                                                        <i class="fa-solid fa-pen-to-square" data-feather="edit-3"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#excluirModal" data-id='<?php echo $IdTurma ?>' data-nometurma="<?php echo $NomeTurma ?>" data-curso='<?php echo $IdCurso ?>' data-nomecurso="<?php echo $nomeCurso ?>" data-municipio="<?php echo $Municipio ?>" data-local="<?php echo $Local ?>" data-habilitado="<?php echo $Habilitado ?>" data-obs="<?php echo $Obs ?>">
                                                        <i class="fa-solid fa-trash" data-feather="trash-2"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <div class="modal fade" id="editarModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h1 class="modal-title" id="exampleModalLabel">Editar cursos</h1>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form action="alteraTurma.php" method="post" enctype="multipart/form-data">
                                                            <div class="mb-3">
                                                                <label for="exampleFormControlInput1" class="form-label">Nome turma</label>
                                                                <input type="text" class="form-control" id="nometurma" name='nometurma' required autocomplete="off">
                                                                <input type="hidden" name="id" id="id">
                                                            </div>

                                                            <div class="row mb-3">
                                                                <div class="col-7">
                                                                    <label for="exampleFormControlInput1" class="form-label">Curso</label>
                                                                    <select class="form-select" aria-label="Selecione" name='curso' id='curso'>
                                                                        <?php
                                                                        foreach ($listaCursos as $curso) {
                                                                            echo '<option value="' . $curso['IdCurso'] . '"' . $IdCurso . ' >' . $curso['NomeCurso'] . '</option>';
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                                <div class="col-5">
                                                                    <label for="exampleFormControlInput1" class="form-label">Municipio</label>
                                                                    <input type="text" class="form-control" id="municipio" name='municipio' autocomplete="off">
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <div class="col-7">
                                                                    <label for="exampleFormControlInput1" class="form-label">Local</label>
                                                                    <input type="text" class="form-control" id="local" name='local' autocomplete="off">
                                                                </div>
                                                                <div class="col-5">
                                                                    <label for="exampleFormControlInput1" class="form-label">Situação</label>
                                                                    <select class="form-select" aria-label="Selecione" id="habilitado" name='habilitado'>
                                                                        <option value="Ativo">Ativo</option>
                                                                        <option value="Concluído">Concluído</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <div class="col-5">
                                                                    <label for="max-matriculas-editar" class="form-label">Maximo de pessoas na turma</label>
                                                                    <input type="text" class="form-control" id="max-matriculas-editar" name="max-matriculas" autocomplete="off">
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="exampleFormControlInput1" class="form-label">Obs</label>
                                                                <textarea rows="7" class="form-control" aria-label="obs" id="obs" name='obs' autocomplete="off"></textarea>
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
                                        <div class="modal fade" id="excluirModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h1 class="modal-title" id="exampleModalLabel">Excluir cursos</h1>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form action="excluirTurma.php" method="post" enctype="multipart/form-data">
                                                            <div class="mb-3">
                                                                <label for="nometurma" class="form-label">Nome turma</label>
                                                                <input type="text" class="form-control" id="nometurma" name='nometurma' readonly autocomplete="off">
                                                                <input type="hidden" name="id" id="id">
                                                            </div>

                                                            <div class="row mb-3">
                                                                <div class="col-6">
                                                                    <label for="curso" class="form-label">Curso</label>
                                                                    <select class="form-select" aria-label="Selecione" name='curso' id='curso' disabled>
                                                                        <?php
                                                                        foreach ($listaCursos as $curso) {
                                                                            echo '<option value="' . $curso['IdCurso'] . '">' . $curso['NomeCurso'] . '</option>';
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                                <div class="col-5">
                                                                    <label for="municipio" class="form-label">Municipio</label>
                                                                    <input type="text" class="form-control" id="municipio" name='municipio' readonly autocomplete="off">
                                                                </div>
                                                                <div class="col-4">
                                                                    <label for="local" class="form-label">Local</label>
                                                                    <input type="text" class="form-control" id="local" name='local' readonly autocomplete="off">
                                                                </div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="obs" class="form-label">Obs</label>
                                                                <textarea rows="7" class="form-control" aria-label="obs" id="obs" name='obs' readonly autocomplete="off"></textarea>
                                                            </div>

                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" class="btn btn-danger">Excluir registro</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

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

    <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>
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
<!-- include da importação da mascara dos inputs -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.12/jquery.mask.min.js"></script>
<script type="text/javascript">
    $("#Telefone, #WhatsApp").mask("(00) 0.0000-0000"); //000 000 0000 eua
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
    $('#Valor').mask('000.000.000.000.000,00', {
        reverse: true
    });
    $('#valor').mask('000.000.000.000.000,00', {
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
    $("#cpfcnpj").keydown(function() {
        try {
            $("#cpfcnpj").unmask();
        } catch (e) {}

        var tamanho = $("#cpfcnpj").val().length;

        if (tamanho < 11) {
            $("#cpfcnpj").mask("999.999.999-99");
        } else {
            $("#cpfcnpj").mask("99.999.999/9999-99");
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
    $(function() {
        $('#editarModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            var id = button.data('id') // data-id
            var nometurma = button.data('nometurma')
            var curso = button.data('curso')
            var municipio = button.data('municipio')
            var local = button.data('local')
            var maxMatriculas = button.data('maxmatriculas')
            var habilitado = button.data('habilitado')
            var obs = button.data('obs')

            var modal = $(this)
            //aplica valor ao id
            modal.find('.modal-title').text('Editar dados')
            modal.find('#nometurma').val(nometurma)
            modal.find('#id').val(id)
            modal.find('#curso').val(curso)
            modal.find('#municipio').val(municipio)
            modal.find('#local').val(local)
            modal.find('#max-matriculas-editar').val(maxMatriculas || '')
            modal.find('#habilitado').val(habilitado)
            modal.find('#obs').val(obs)

        });
    });
</script>

<script>
    $(function() {
        $('#excluirModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            var id = button.data('id') // data-id
            var nometurma = button.data('nometurma')
            var curso = button.data('curso')
            var municipio = button.data('municipio')
            var local = button.data('local')
            var habilitado = button.data('habilitado')
            var obs = button.data('obs')

            var modal = $(this)
            // aplica valor ao id
            modal.find('.modal-title').text('Excluir dados')
            modal.find('#nometurma').val(nometurma)
            modal.find('#id').val(id)
            modal.find('#curso').val(curso)
            modal.find('#municipio').val(municipio)
            modal.find('#local').val(local)
            modal.find('#habilitado').val(habilitado)
            modal.find('#obs').val(obs)
        });
    });
</script>


<!-- Desativa submit para evitar cadastros duplicados -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('nomesTurmaContainer');
        const addButton = document.getElementById('adicionarNomeTurma');

        function atualizarEstadoRemover() {
            const linhas = container.querySelectorAll('.nome-turma-item');
            linhas.forEach((linha, index) => {
                const btn = linha.querySelector('.remover-nome-turma');
                btn.disabled = linhas.length === 1 || index === 0;
            });
        }

        function vincularEventosLinha(linha) {
            const btnRemover = linha.querySelector('.remover-nome-turma');
            btnRemover.addEventListener('click', function() {
                const linhas = container.querySelectorAll('.nome-turma-item');
                if (linhas.length <= 1) {
                    return;
                }
                linha.remove();
                atualizarEstadoRemover();
            });
        }

        addButton.addEventListener('click', function() {
            const linhaBase = container.querySelector('.nome-turma-item');
            const novaLinha = linhaBase.cloneNode(true);
            const input = novaLinha.querySelector('input[name="NomeTurma[]"]');
            input.value = '';
            input.removeAttribute('id');
            vincularEventosLinha(novaLinha);
            container.appendChild(novaLinha);
            atualizarEstadoRemover();
        });

        const linhaInicial = container.querySelector('.nome-turma-item');
        vincularEventosLinha(linhaInicial);
        atualizarEstadoRemover();
    });
</script>
<script>
    document.getElementById('submitButton').addEventListener('click', function() {
        const submitButton = document.getElementById('submitButton');
        const form = document.getElementById('formulario');
        const inputsNomeTurma = document.querySelectorAll('input[name="NomeTurma[]"]');
        let temNomeValido = false;

        inputsNomeTurma.forEach(function(input) {
            if (input.value.trim() !== '') {
                temNomeValido = true;
            }
        });

        if (!temNomeValido) {
            alert('Informe ao menos um nome de turma.');
            return;
        }

        // Adiciona o spinner ao botão
        submitButton.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Aguarde...
    `;

        // Desabilita o botão
        submitButton.disabled = true;

        // Submete o formulário
        form.submit();
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Ocultar o loader e mostrar a tabela
        const loader = document.getElementById("loader");
        const tabela = document.getElementById("minhaTabela");

        if (loader) loader.style.display = "none";
        if (tabela) tabela.style.display = "table";

        setTimeout(() => {
            const alertElement = document.querySelector(".alert");
            if (alertElement) alertElement.style.display = "none";
        }, 2000);
    });
</script>

