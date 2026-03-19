<?php
// Inicialização da sessão e configuração de timezone
session_set_cookie_params(['httponly' => true]);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica se as variáveis de sessão BASE_PATH e BASE_URL estão definidas
if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    // Salva a URL atual para redirecionar o usuário após o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endereço atual
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url"); // Redireciona para o login com o endereço de volta via GET
    exit(); // Garante que o código abaixo não será executado
}

require_once $_SESSION['BASE_PATH'] . '/checa-token.php';
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php'; // Retorna o objeto PDO
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $_SESSION['BASE_PATH'] . '/template/header.php'; ?>
    <style>
        .img-cover {
            object-fit: cover;
            object-position: center;
        }

        .modal.show {
            display: block !important;
            opacity: 1 !important;
            z-index: 1055 !important;
        }

        .modal-backdrop {
            z-index: 1050 !important;
        }

        #editarModal,
        #excluirModal {
            text-align: initial;
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

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            /* Melhora a rolagem no iOS */
        }

        .table-layout-auto {
            table-layout: auto;
            width: 100%;
            white-space: nowrap;
            /* Impede quebra de linha nas células */
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

        /* Melhorando estilos do DataTables */
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

        .filtro-situacao-curso {
            background-color: #f1f3f5;
            color: #000000;
            border: 1px solid #d0d7de;
        }

        .filtro-situacao-curso.active.filtro-ativo {
            background-color: #ffc107;
            color: #ffffff;
            border-color: #ffc107;
        }

        .filtro-situacao-curso.active.filtro-concluido {
            background-color: #198754;
            color: #ffffff;
            border-color: #198754;
        }

        .filtro-situacao-curso.active.filtro-todos {
            background-color: #0d6efd;
            color: #ffffff;
            border-color: #0d6efd;
        }
    </style>
</head>
<!-- Carrega jQuery via CDN (ou outra versão, se necessário) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script src="//code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/jquery.dataTables.min.js"></script>
<script>
    // Adiciona suporte à busca ignorando acentos
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
            columnDefs: [{
                targets: '_all',
                type: 'locale-agnostic',
            }],
            order: [
                [0, 'asc']
            ],
            responsive: true, // Permite responsividade melhor no DataTables
            autoWidth: false, // Impede largura fixa que dificulta telas pequenas
            language: {
                url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json"
            }
        });

        var statusColumnIndex = 4;

        function normalizeFiltroValue(value) {
            return value
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/Çõ/g, 'c').toLowerCase();
        }

        $(document).on('click', '.filtro-situacao-curso', function() {
            var value = $(this).data('value');
            $('.filtro-situacao-curso').removeClass('active');
            $(this).addClass('active');
            if (value === 'Todos') {
                table.column(statusColumnIndex).search('').draw();
                return;
            }
            table.column(statusColumnIndex).search(normalizeFiltroValue(value), false, false).draw();
        });

        // Adapta a busca para normalizar caracteres acentuados
        $('#minhaTabela_filter input').on('input', function() {
            let userInput = $(this).val();
            const normalizedInput = userInput
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/ç/g, 'c').toLowerCase();
            $(this).val(normalizedInput);
            table.search(normalizedInput).draw();
        });
    });
</script>

<body>
    <div class="wrapper">
        <?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>

        <div class="main">
            <?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php'; ?>

            <main class="content">
                <div id="loader" class="loader">
                    <img src="https://www.blogson.com.br/wp-content/uploads/2017/10/loading-gif-transparent-10.gif" width="150" alt="Carregando...">
                </div>
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3">Cursos</h1>
                    <?php
                    // Exibe mensagem vinda da URL
                    $msg = "";
                    $url = $_SERVER['REQUEST_URI'];
                    $urlDecoded = urldecode($url);
                    if (strpos($urlDecoded, '=') !== false) {
                        $params = explode('&', $urlDecoded);
                        $tipomsg = explode('?', $params[0])[1]; // valor após '?'
                        $tipomsg = explode('=', $tipomsg)[0]; // valor antes do '='
                        $msg = explode('=', $params[0])[1]; // valor após '='
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
                        <div class="card">
                            <div class="card-body">
                                <form action="cadCurso.php" id="formulario" enctype="multipart/form-data" method="post">
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label for="NomeCurso" class="form-label">Nome do curso</label>
                                            <input type="text" class="form-control" id="NomeCurso" name="NomeCurso" autocomplete="off">
                                        </div>
                                        <div class="col-6">
                                            <label for="CargaHoraria" class="form-label">Carga horária</label>
                                            <input type="text" class="form-control" id="CargaHoraria" name="CargaHoraria" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label for="Duracao" class="form-label">Duração</label>
                                            <input type="text" class="form-control" id="Duracao" name="Duracao" autocomplete="off">
                                        </div>
                                        <div class="col-6">
                                            <label for="Tipo" class="form-label">Tipo</label>
                                            <select class="form-select" aria-label="Selecione" name="Tipo">
                                                <option value="Horas">Horas</option>
                                                <option value="Dias">Dias</option>
                                                <option value="Semanas">Semanas</option>
                                                <option value="Meses" selected>Meses</option>
                                                <option value="Outro">Outro</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-4">
                                            <label for="Habilitado" class="form-label">Situação</label>
                                            <select class="form-select" aria-label="Selecione" id="Habilitado" name="Habilitado">
                                                <option value="Ativo" selected>Ativo</option>
                                                <option value="Concluído">Concluído</option>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <label for="idade-min" class="form-label">Idade mínima</label>
                                            <input type="text" class="form-control" id="idade-min" name="idade-min" autocomplete="off">
                                        </div>
                                        <div class="col-4">
                                            <label for="idade-max" class="form-label">Idade máxima</label>
                                            <input type="text" class="form-control" id="idade-max" name="idade-max" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-4">
                                            <label for="Termo" class="form-label">Termo de parceria</label>
                                            <input type="text" class="form-control" id="Termo" name="Termo" autocomplete="off">
                                        </div>
                                        <div class="col-4">
                                            <?php
                                            // Consulta dos projetos via PDO
                                            $sql = "SELECT * FROM tbProjeto ORDER BY NomeProjeto ASC";
                                            $result = $pdo->query($sql);
                                            ?>
                                            <label for="Projeto" class="form-label">Projeto</label>
                                            <select class="form-select" id="Projeto" name="Projeto">
                                                <option value="">Selecione um projeto</option>
                                                <?php
                                                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                                                    echo "<option value='" . $row['IdProjeto'] . "'>" . $row['NomeProjeto'] . "</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <label for="Programa" class="form-label">Programa</label>
                                            <select class="form-select" id="Programa" aria-label="Selecione" name="Programa">
                                                <option value="">Selecione um programa</option>
                                                <option value="Midiacom">Midiacom</option>
                                                <option value="Bem-estar">Bem-estar</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="infAdic" class="form-label">Informações adicionais</label>
                                        <textarea rows="7" class="form-control" aria-label="Informacoes" id="infAdic" name="Informacoes" autocomplete="off"></textarea>
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
                            <h5>Lista de Cursos Cadastrados</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label">Filtro de situação</label>
                                    <div class="btn-group" role="group" aria-label="Filtro de situação">
                                        <button type="button" class="btn filtro-situacao-curso filtro-ativo" data-value="Ativo">
                                            Ativo
                                        </button>
                                        <button type="button" class="btn filtro-situacao-curso filtro-concluido" data-value="Concluído">
                                            Concluído
                                        </button>
                                        <button type="button" class="btn filtro-situacao-curso filtro-todos active" data-value="Todos">
                                            Todos
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table id="minhaTabela" class="table table-striped table-hover datatable-custom table-layout-auto">
                                    <thead>
                                        <tr>
                                            <th scope="col">Nome</th>
                                            <th scope="col" class="text-center">Termo</th>
                                            <th scope="col" class="text-center">Projeto</th>
                                            <th scope="col" class="text-center">Programa</th>
                                            <th scope="col" class="text-center">Situação</th>
                                            <th scope="col" class="text-center">Duração</th>
                                            <th scope="col" class="text-center">Tipo</th>
                                            <th scope="col" class="text-center">Carga horária</th>
                                            <th scope="col">Informações</th>
                                            <th scope="col" class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT 
                                                c.*, 
                                                IFNULL(p.nomeProjeto, '-') AS nomeProjeto,
                                                IFNULL(c.Programa, '-') AS Programa
                                            FROM 
                                                tbCurso c
                                            LEFT JOIN 
                                                tbProjeto p
                                            ON 
                                                c.IdProjeto = p.IdProjeto";
                                        $busca = $pdo->query($sql);
                                        while ($dadosCurso = $busca->fetch(PDO::FETCH_ASSOC)) {
                                            $IdCurso = $dadosCurso['IdCurso'];
                                            $NomeCurso = $dadosCurso['NomeCurso'];
                                            $Termo = $dadosCurso['Termo'];
                                            $ProjetoId = $dadosCurso['IdProjeto'];
                                            $ProjetoNome = $dadosCurso['nomeProjeto'];
                                            $Programa = $dadosCurso['Programa'];
                                            if ($dadosCurso['Habilitado']) {
                                                $Habilitado = "Ativo";
                                            } else {
                                                $Habilitado = "Concluído";
                                            }
                                            $IdadeMin = $dadosCurso['IdadeMin'];
                                            $IdadeMax = $dadosCurso['IdadeMax'];
                                            $DURA = $dadosCurso['Duracao'];
                                            $Duracao = str_replace(".", ",", $DURA);
                                            $Tipo = $dadosCurso['Tipo'];
                                            $CH = $dadosCurso['CargaHoraria'];
                                            $CargaHoraria = str_replace(".", ",", $CH);
                                            $Informacoes = $dadosCurso['Informacoes'];
                                        ?>
                                            <tr>
                                                <td><?php echo $NomeCurso ?></td>
                                                <td><?php echo $Termo ?></td>
                                                <td class="text-center"><?php echo $ProjetoNome ?></td>
                                                <td class="text-center"><?php echo $Programa ?></td>
                                                <td class="text-center">
                                                    <?php if ($Habilitado === "Concluído") { ?>
                                                        <span class="badge bg-success">Concluído</span>
                                                    <?php } else { ?>
                                                        <span class="badge bg-warning text-dark">Ativo</span>
                                                    <?php } ?>
                                                </td>
                                                <td class="text-center"><?php echo $Duracao ?></td>
                                                <td class="text-center"><?php echo $Tipo ?></td>
                                                <td class="text-center"><?php echo $CargaHoraria ?></td>
                                                <td><?php echo $Informacoes ?></td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-2">
                                                        <button type="button" class="btn btn-warning btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editarModal"
                                                            data-id='<?php echo $IdCurso ?>'
                                                            data-nomecurso="<?php echo $NomeCurso ?>"
                                                            data-termo="<?php echo $Termo ?>"
                                                            data-projeto="<?php echo $ProjetoNome ?>"
                                                            data-projetoid="<?php echo $ProjetoId ?>"
                                                            data-programa="<?php echo $Programa ?>"
                                                            data-habilitado="<?php echo $Habilitado ?>"
                                                            data-idademin="<?php echo $IdadeMin ?>"
                                                            data-idademax="<?php echo $IdadeMax ?>"
                                                            data-cargah='<?php echo $CargaHoraria ?>'
                                                            data-tipo="<?php echo $Tipo ?>"
                                                            data-duracao="<?php echo $Duracao ?>"
                                                            data-informacoes="<?php echo $Informacoes ?>">
                                                            <i class="fa-solid fa-pen-to-square" data-feather="edit-3"></i>
                                                        </button>

                                                        <button type="button" class="btn btn-danger btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#excluirModal"
                                                            data-id='<?php echo $IdCurso ?>'
                                                            data-nomecurso="<?php echo $NomeCurso ?>"
                                                            data-termo="<?php echo $Termo ?>"
                                                            data-projeto="<?php echo $ProjetoNome ?>"
                                                            data-programa="<?php echo $Programa ?>"
                                                            data-habilitado="<?php echo $Habilitado ?>"
                                                            data-idademin="<?php echo $IdadeMin ?>"
                                                            data-idademax="<?php echo $IdadeMax ?>"
                                                            data-cargah='<?php echo $CargaHoraria ?>'
                                                            data-Tipo="<?php echo $Tipo ?>"
                                                            data-duracao="<?php echo $Duracao ?>"
                                                            data-informacoes="<?php echo $Informacoes ?>">
                                                            <i class="fa-solid fa-trash" data-feather="trash-2"></i>
                                                        </button>
                                                    </div>
                                                </td>

                                            </tr>
                                        <?php }
                                        ?>
                                        <!-- Modal de edição -->
                                        <div class="modal fade" id="editarModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h1 class="modal-title" id="exampleModalLabel">Editar cursos</h1>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form action="alteraCurso.php" method="post" enctype="multipart/form-data">
                                                            <div class="row mb-3">
                                                                <div class="col-8">
                                                                    <label for="nomecurso" class="form-label">Nome</label>
                                                                    <input type="text" class="form-control" id="nomecurso" name="nomecurso" required autocomplete="off">
                                                                    <input type="hidden" name="id" id="id">
                                                                </div>
                                                                <div class="col-4">
                                                                    <label for="Termo" class="form-label">Termo</label>
                                                                    <input type="text" class="form-control" id="Termo" name="Termo" autocomplete="off">
                                                                </div>
                                                            </div>

                                                            <div class="row mb-3">
                                                                <div class="col-3">
                                                                    <label for="duracao" class="form-label">Duração</label>
                                                                    <input type="text" class="form-control" id="duracao" name="duracao" autocomplete="off">
                                                                </div>
                                                                <div class="col-5">
                                                                    <label for="tipo" class="form-label">Tipo</label>
                                                                    <select class="form-select" aria-label="Selecione" id="tipo" name="tipo">
                                                                        <option value="Horas">Horas</option>
                                                                        <option value="Dias">Dias</option>
                                                                        <option value="Semanas">Semanas</option>
                                                                        <option value="Meses">Meses</option>
                                                                        <option value="Outro">Outro</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-4">
                                                                    <label for="cargah" class="form-label">Carga horária</label>
                                                                    <input type="text" class="form-control" id="cargah" name="cargah" autocomplete="off">
                                                                </div>
                                                            </div>

                                                            <div class="row mb-3">
                                                                <div class="col-6">
                                                                    <label for="habilitado" class="form-label">Situação</label>
                                                                    <select class="form-select" aria-label="Selecione" id="habilitado" name="habilitado">
                                                                        <option value="Ativo">Ativo</option>
                                                                        <option value="Concluído">Concluído</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-3">
                                                                    <label for="idade-min-editar" class="form-label">Idade minima</label>
                                                                    <input type="text" class="form-control" id="idade-min-editar" name="idade-min" autocomplete="off">
                                                                </div>
                                                                <div class="col-3">
                                                                    <label for="idade-max-editar" class="form-label">Idade maxima</label>
                                                                    <input type="text" class="form-control" id="idade-max-editar" name="idade-max" autocomplete="off">
                                                                </div>
                                                            </div>

                                                            <div class="row mb-3">
                                                                <?php
                                                                // Consulta dos projetos via PDO
                                                                $sqlProjetos = "SELECT IdProjeto, nomeProjeto FROM tbProjeto";
                                                                $buscaProjetos = $pdo->query($sqlProjetos);
                                                                $listaProjetos = $buscaProjetos->fetchAll(PDO::FETCH_ASSOC);
                                                                ?>
                                                                <div class="col-6">
                                                                    <label for="Projeto" class="form-label">Projeto</label>
                                                                    <select class="form-select" id="Projeto" name="Projeto">
                                                                        <option value="">-</option>
                                                                        <?php foreach ($listaProjetos as $projeto) { ?>
                                                                            <option value="<?php echo $projeto['IdProjeto']; ?>">
                                                                                <?php echo $projeto['nomeProjeto']; ?>
                                                                            </option>
                                                                        <?php } ?>
                                                                    </select>
                                                                </div>

                                                                <div class="col-6">
                                                                    <label for="Programa" class="form-label">Programa</label>
                                                                    <input type="text" class="form-control" id="Programa" name="Programa" autocomplete="off">
                                                                </div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="informacoes" class="form-label">Informações adicionais</label>
                                                                <textarea rows="7" class="form-control" aria-label="Informacoes" id="informacoes" name="informacoes" autocomplete="off"></textarea>
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
                                        <!-- Modal de exclusão -->
                                        <?php
                                        $sqlProjetos = "SELECT IdProjeto, nomeProjeto FROM tbProjeto";
                                        $buscaProjetos = $pdo->query($sqlProjetos);
                                        $listaProjetos = $buscaProjetos->fetchAll(PDO::FETCH_ASSOC);
                                        ?>
                                        <div class="modal fade" id="excluirModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h1 class="modal-title fs-5" id="exampleModalLabel">Excluir cursos</h1>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form action="excluirCurso.php" method="post" enctype="multipart/form-data">
                                                            <div class="row mb-3">
                                                                <div class="col-8">
                                                                    <label for="nomecurso" class="form-label">Nome</label>
                                                                    <input type="text" class="form-control" id="nomecurso" name="nomecurso" readonly autocomplete="off">
                                                                    <input type="hidden" name="id" id="id">
                                                                </div>
                                                                <div class="col-4">
                                                                    <label for="Termo" class="form-label">Termo</label>
                                                                    <input type="text" class="form-control" id="Termo" name="Termo" readonly autocomplete="off">
                                                                </div>
                                                            </div>

                                                            <div class="row mb-3">
                                                                <div class="col-3">
                                                                    <label for="duracao" class="form-label">Duração</label>
                                                                    <input type="text" class="form-control" id="duracao" name="duracao" readonly autocomplete="off">
                                                                </div>
                                                                <div class="col-5">
                                                                    <label for="tipo" class="form-label">Tipo</label>
                                                                    <select class="form-select" aria-label="Selecione" id="tipo" name="tipo" disabled>
                                                                        <option value="Horas">Horas</option>
                                                                        <option value="Dias">Dias</option>
                                                                        <option value="Semanas">Semanas</option>
                                                                        <option value="Meses">Meses</option>
                                                                        <option value="Outro">Outro</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-4">
                                                                    <label for="cargah" class="form-label">Carga horária</label>
                                                                    <input type="text" class="form-control" id="cargah" name="cargah" readonly autocomplete="off">
                                                                </div>
                                                            </div>

                                                            <div class="row mb-3">
                                                                <div class="col-6">
                                                                    <label for="Projeto" class="form-label">Projeto</label>
                                                                    <input type="text" class="form-control" id="Projeto" name="Projeto" readonly autocomplete="off">
                                                                </div>
                                                                <div class="col-6">
                                                                    <label for="Programa" class="form-label">Programa</label>
                                                                    <input type="text" class="form-control" id="Programa" name="Programa" readonly autocomplete="off">
                                                                </div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="informacoes" class="form-label">Informações adicionais</label>
                                                                <textarea rows="7" class="form-control" aria-label="Informacoes" id="informacoes" name="informacoes" readonly autocomplete="off"></textarea>
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
                <?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php'; ?>
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
            var button = event.relatedTarget;

            var id = button.getAttribute('data-id');
            var nomeCurso = button.getAttribute('data-nomecurso');
            var termo = button.getAttribute('data-termo');
            var projetoId = button.getAttribute('data-projetoid');
            var programa = button.getAttribute('data-programa');
            programa = (programa === "-") ? null : programa;
            var habilitado = button.getAttribute('data-habilitado');
            var idadeMin = button.getAttribute('data-idademin');
            var idadeMax = button.getAttribute('data-idademax');
            var cargaHoraria = button.getAttribute('data-cargah');
            var tipo = button.getAttribute('data-tipo');
            var duracao = button.getAttribute('data-duracao');
            var informacoes = button.getAttribute('data-informacoes');

            var modal = this;

            modal.querySelector('#id').value = id;
            modal.querySelector('#nomecurso').value = nomeCurso;
            modal.querySelector('#Termo').value = termo;
            modal.querySelector('#Projeto').value = projetoId; // Atualiza o valor selecionado no select
            modal.querySelector('#Programa').value = programa;
            modal.querySelector('#habilitado').value = habilitado;
            modal.querySelector('#idade-min-editar').value = idadeMin || '';
            modal.querySelector('#idade-max-editar').value = idadeMax || '';
            modal.querySelector('#cargah').value = cargaHoraria;
            modal.querySelector('#tipo').value = tipo;
            modal.querySelector('#duracao').value = duracao;
            modal.querySelector('#informacoes').value = informacoes;
        });

        modal.css('z-index', '1055');
        $('.modal-backdrop').css('z-index', '1050');

    });
</script>

<script>
    $(function() {
        $('#excluirModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            var id = button.data('id') // data-id
            var nomecurso = button.data('nomecurso')
            var termo = button.data('termo')
            var projeto = button.data('projeto')
            var programa = button.data('programa')
            var tipo = button.data('tipo')
            var cargah = button.data('cargah')
            var duracao = button.data('duracao')
            var informacoes = button.data('informacoes')

            var modal = $(this)
            //aplica valor ao id
            modal.find('.modal-title').text('Excluir dados')
            modal.find('#nomecurso').val(nomecurso)
            modal.find('#Termo').val(termo)
            modal.find('#Projeto').val(projeto)
            modal.find('#Programa').val(programa)
            modal.find('#id').val(id)
            modal.find('#tipo').val(tipo)
            modal.find('#cargah').val(cargah)
            modal.find('#duracao').val(duracao)
            modal.find('#informacoes').val(informacoes)


        });
    });
</script>

<!-- Desativa submit para evitar cadastros duplicados -->
<script>
    document.getElementById('submitButton').addEventListener('click', function() {
        const submitButton = document.getElementById('submitButton');
        const form = document.getElementById('formulario');

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


