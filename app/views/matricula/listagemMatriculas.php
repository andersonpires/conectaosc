<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
// Inicializacao da sessao e configuracao de timezone

// Verifica se as variaveis de sessao BASE_para_PATH e BASE_para_URL estao definidas
if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    // Salva a URL atual para redirecionar o usuario apos o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endereço atual
    header("Location: " . rtrim((string) ($BASE_para_URL ?? ''), '/') . "/login/?redirect=$redirect_url");
    exit(); // Garante que o codigo abaixo nao sera executado
}

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <style>
        .avatar-group {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }

        .avatar-group .avatar {
            margin: 0 -0.3rem;
            border: 2px solid #fff;
        }

        .avatar-group .avatar-xs {
            width: 30px;
            height: 30px;
            font-size: 20px;
        }

        .avatar-group .rounded-circle {
            border-radius: 50%;
        }

        .avatar {
            z-index: 0;
            transform: scale(1);
            transition: transform 0.2s ease-in-out, z-index 0.2s ease-in-out;
            position: relative;
        }

        .avatar:hover {
            z-index: 10;
            transform: scale(5);
        }

        .img-cover {
            object-fit: cover;
            object-position: center;
        }

        .loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            display: block;
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
<!-- Carrega jQuery via CDN -->
<script src="//code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="<?php echo $BASE_para_URL; ?>/assets/js/jquery.dataTables.min.js"></script>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
            <script src="https://npmcdn.com/flatpickr/dist/flatpickr.min.js"></script>
            <script src="https://npmcdn.com/flatpickr/dist/l10n/pt.js"></script>
            <script>
                $.fn.dataTable.ext.type.search['locale-agnostic'] = function(data) {
                    if (typeof data !== 'string') {
                        return data;
                    }
                    return data
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .toLowerCase();
                };

                $(document).ready(function() {
                    function normalizeText(value) {
                        return String(value || '')
                            .normalize('NFD')
                            .replace(/[\u0300-\u036f]/g, '')
                            .toLowerCase();
                    }

                    function normalizeSituacao(value) {
                        var v = normalizeText(value);
                        if (v === 'ativo' || v === 'em progresso' || v === 'emprogresso') {
                            return 'Ativo';
                        }
                        if (v === 'concluido') {
                            return 'Concluido';
                        }
                        return 'Todos';
                    }

                    function getQueryState(defaultPageLength) {
                        var params = new URLSearchParams(window.location.search);
                        var situacao = normalizeSituacao(params.get('situacao') || 'Todos');
                        var porPaginaRaw = params.get('porPagina');
                        var porPagina = Number.parseInt(String(porPaginaRaw || ''), 10);
                        if (!Number.isInteger(porPagina) || (porPagina <= 0 && porPagina !== -1)) {
                            porPagina = defaultPageLength;
                        }
                        return { situacao: situacao, porPagina: porPagina };
                    }

                    function updateQueryState(nextState) {
                        var url = new URL(window.location.href);
                        if (nextState && nextState.situacao) {
                            url.searchParams.set('situacao', String(nextState.situacao));
                        }
                        if (nextState && Number.isInteger(nextState.porPagina)) {
                            url.searchParams.set('porPagina', String(nextState.porPagina));
                        }
                        var nextUrl = url.pathname + (url.searchParams.toString() ? '?' + url.searchParams.toString() : '') + url.hash;
                        window.history.replaceState(null, '', nextUrl);
                    }

                    function setSituacaoButtonActive(canonicalValue) {
                        $('.filtro-situacao-turma').removeClass('active');
                        $('.filtro-situacao-turma').each(function() {
                            var current = normalizeSituacao($(this).data('value'));
                            if (current === canonicalValue) {
                                $(this).addClass('active');
                            }
                        });
                    }

                    function applySituacaoFilter(table, canonicalValue, statusColumnIndex) {
                        var value = normalizeSituacao(canonicalValue);
                        setSituacaoButtonActive(value);
                        if (value === 'Todos') {
                            table.column(statusColumnIndex).search('').draw();
                            return value;
                        }
                        var searchTerm = value === 'Ativo' ? 'Em progresso' : 'Concluído';
                        table.column(statusColumnIndex).search(normalizeText(searchTerm), false, false).draw();
                        return value;
                    }

                    var queryState = getQueryState(50);
                    var table = $('#minhaTabela').DataTable({
                        "order": [],
                        "pageLength": queryState.porPagina,
                        "columnDefs": [{
                            "targets": "_all",
                            "type": "locale-agnostic"
                        }],
                        language: {
                            url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json"
                        }
                    });

                    var statusColumnIndex = 5;
                    var activeSituacao = applySituacaoFilter(table, queryState.situacao, statusColumnIndex);
                    updateQueryState({ situacao: activeSituacao, porPagina: table.page.len() });

                    var filterInput = $('#minhaTabela_filter input');
                    filterInput.off('.DT');
                    filterInput.on('input.DT keyup.DT paste.DT cut.DT', function() {
                        table.search(normalizeText(this.value)).draw();
                    });

                    table.on('length.dt', function(_e, _settings, len) {
                        updateQueryState({
                            situacao: normalizeSituacao($('.filtro-situacao-turma.active').data('value')),
                            porPagina: len
                        });
                    });

                    $(document).on('click', '.filtro-situacao-turma', function() {
                        var value = normalizeSituacao($(this).data('value'));
                        var active = applySituacaoFilter(table, value, statusColumnIndex);
                        updateQueryState({ situacao: active, porPagina: table.page.len() });
                    });
                });
            </script>
            <main class="content">
                <div class="container-fluid p-0">
                    <?php
                    // Tratamento da mensagem vinda da URL
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
                    <div id="loader" class="loader">
                        <img src="https://www.blogson.com.br/wp-content/uploads/2017/10/loading-gif-transparent-10.gif" width="150" alt="Carregando...">
                    </div>
                    <!-- Conteudo da pagina: Listagem de alunos e cursos -->
                    <div class="col-12">
                        <h4 class="h3 mb-3" id="titulo">Total de matrículas</h4>
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                    <a href="<?php echo $BASE_para_URL; ?>/beneficiarios/lista?matricula=1" class="btn btn-primary" id="novoAluno">Matricular novo aluno</a>
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="form-label mb-0">Filtro de situação</label>
                                        <div class="btn-group" role="group" aria-label="Filtro de situação">
                                            <button type="button" class="btn filtro-situacao-turma filtro-ativo" data-value="Ativo">
                                                Ativo
                                            </button>
                                            <button type="button" class="btn filtro-situacao-turma filtro-concluido" data-value="Concluído">Concluído</button>
                                            <button type="button" class="btn filtro-situacao-turma filtro-todos active" data-value="Todos">
                                                Todos
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table id="minhaTabela" class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th scope="col">No</th>
                                                <th scope="col">Curso</th>
                                                <th scope="col">Turma</th>
                                                <th scope="col">Local</th>
                                                <th scope="col">Qtd alunos</th>
                                                <th scope="col" style="width: 7%;">Situação</th>
                                                <th scope="col">Contrato</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Configurar group_concat_max_len para garantir concatenacao completa
                                            require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

                                            $sql = "SELECT 
                                                        c.IdCurso,
                                                        c.NomeCurso, 
                                                        t.NomeTurma, 
                                                        t.Local, 
                                                        t.Habilitado, 
                                                        t.IdTurma, 
                                                        COUNT(a.IdUsuario) AS QuantidadeAlunos
                                                    FROM tbTurma t
                                                    JOIN tbCurso c ON t.IdCurso = c.IdCurso
                                                    LEFT JOIN tbMatricula m ON t.IdTurma = m.IdTurma AND m.Habilitado = 1
                                                    LEFT JOIN tbAluno a ON m.IdUsuario = a.IdUsuario 
                                                    GROUP BY c.NomeCurso, t.NomeTurma, t.Local, t.Habilitado, t.IdTurma;";

                                            $busca = $pdo->query($sql);
                                            $rowCount = 0;
                                            $indice = 1;
                                            ?>
                                            <?php while ($dados = $busca->fetch(PDO::FETCH_ASSOC)) {
                                                $nomeCurso   = $dados['NomeCurso'];
                                                $IdTurma     = $dados['IdTurma'];
                                                $nomeTurma   = $dados['NomeTurma'];
                                                $Local       = $dados['Local'];
                                                $Habilitado  = $dados['Habilitado'];
                                                $QtdAlunos = $dados['QuantidadeAlunos'];
                                                $rowCount = $rowCount + $QtdAlunos;
                                            ?>
                                                <tr>
                                                    <td><?php echo $indice;
                                                        $indice++ ?></td>
                                                    <td><?php echo $nomeCurso ?></td>
                                                    <td><?php echo "<a href='" . $BASE_para_URL . "/matriculas/turma?turma=$IdTurma'>$nomeTurma</a>" ?></td>
                                                    <td><?php echo $Local ?></td>
                                                    <td><?php echo $QtdAlunos ?></td>
                                                    <td style="width: 7%;"><?php echo ($Habilitado ? "<span class='badge bg-warning'>Em progresso</span>" : "<span class='badge bg-success'>Concluído</span>") ?></td>
                                                    <td>
                                                        <a class="btn btn-info btn-sm" href="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/matriculas/contratos/curso/?curso=<?php echo $dados['IdCurso']; ?>" title="Gerar contratos do curso" target="_blank">
                                                            <i class="fa-solid fa-file-signature"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>
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
        });
    }
</script>

<!-- Importacao do Jquery Mask -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.12/jquery.mask.min.js"></script>
<script type="text/javascript">
    $("#telefone, #celular").mask("(00) 00000-0000");
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
        const loader = document.getElementById("loader");
        const tabela = document.getElementById("minhaTabela");
        if (loader) loader.style.display = "none";
        if (tabela) tabela.style.display = "table";
        const tituloElement = document.getElementById("titulo");
        if (tituloElement) {
            tituloElement.textContent = "Total de matrículas: <?php echo $rowCount ?>";
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
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var nomeCurso = button.getAttribute('data-nomecurso');
            var termo = button.getAttribute('data-termo');
            var projetoId = button.getAttribute('data-projetoid');
            var programa = button.getAttribute('data-programa');
            programa = (programa === "-") ? null : programa;
            var cargaHoraria = button.getAttribute('data-cargah');
            var tipo = button.getAttribute('data-tipo');
            var duracao = button.getAttribute('data-duracao');
            var informacoes = button.getAttribute('data-informacoes');

            var modal = this;

            modal.querySelector('#id').value = id;
            modal.querySelector('#nomecurso').value = nomeCurso;
            modal.querySelector('#Termo').value = termo;
            modal.querySelector('#Projeto').value = projetoId;
            modal.querySelector('#Programa').value = programa;
            modal.querySelector('#cargah').value = cargaHoraria;
            modal.querySelector('#tipo').value = tipo;
            modal.querySelector('#duracao').value = duracao;
            modal.querySelector('#informacoes').value = informacoes;
        });
        $('.modal-backdrop').css('z-index', '1050');
    });
</script>

<script>
    $(function() {
        $('#excluirModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var nomecurso = button.data('nomecurso');
            var termo = button.data('termo');
            var projeto = button.data('projeto');
            var programa = button.data('programa');
            var tipo = button.data('tipo');
            var cargah = button.data('cargah');
            var duracao = button.data('duracao');
            var informacoes = button.data('informacoes');

            var modal = $(this);
            modal.find('.modal-title').text('Excluir dados');
            modal.find('#nomecurso').val(nomecurso);
            modal.find('#Termo').val(termo);
            modal.find('#Projeto').val(projeto);
            modal.find('#Programa').val(programa);
            modal.find('#id').val(id);
            modal.find('#tipo').val(tipo);
            modal.find('#cargah').val(cargah);
            modal.find('#duracao').val(duracao);
            modal.find('#informacoes').val(informacoes);
        });
    });
</script>








