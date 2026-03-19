<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$appJsVersion = @filemtime($BASE_para_PATH . '/app/assets/js/app.js') ?: time();
// aniversariantes.php
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: " . rtrim((string)$BASE_para_URL, '/') . "/login/?redirect=$redirect_url");
    exit();
}

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/overlayNotifica.js"></script>
    <link rel="stylesheet" href="<?php echo $BASE_para_URL; ?>/assets/css/overlayNotifica.css">
    <style>
        .titulo-pagina {
            font-size: 24px;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 20px;
        }

        .card-aniversariante {
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 10px;
            margin: 10px;
            min-height: 235px;
            height: auto;
            background-color: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .card-aniversariante.ativo {
            background-color: #fff8dc;
        }

        .foto-aniversariante {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 10px;
        }

    </style>
</head>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>
        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
            <main class="content">
                <div class="container mt-4">
                    <div class="titulo-pagina">Aniversariantes</div>
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <label for="mesSelecionado" class="form-label">Escolha o mês:</label>
                            <select id="mesSelecionado" class="form-select">
                                <option value="">Selecione...</option>
                                <?php
                                $meses = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
                                foreach ($meses as $i => $mes) {
                                    $value = str_pad($i + 1, 2, "0", STR_PAD_LEFT);
                                    echo "<option value='$value'>$mes</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="filtroCurso" class="form-label">Filtrar por Curso:</label>
                            <select id="filtroCurso" class="form-select">
                                <option value="">Todos</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="filtroTurma" class="form-label">Filtrar por Turma:</label>
                            <select id="filtroTurma" class="form-select">
                                <option value="">Todos</option>
                            </select>
                        </div>

                        <div class="col-md-2 d-flex align-items-end mb-3">
                            <button id="btnBuscar" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </div>
                    <div id="carregando" class="text-center my-4" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <div class="mt-2 text-primary fw-bold">Carregando dados...</div>
                    </div>
                    <div class="row" id="resultadoAniversariantes"></div>
                </div>
            </main>
            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>
    <div id="overlayLoading" style="display: none;">
        <div class="overlay-bg"></div>
        <div class="overlay-content" id="overlayContent">
            <div class="spinner-border text-primary" role="status" id="overlaySpinner">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-2" id="overlayText">Salvando dados...</p>
            <button id="overlayBtnOk" class="btn btn-primary mt-3" style="display: none;">OK</button>
        </div>
    </div>

    <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>
    <script>
        let requisicaoAtual = null;
        let debounceTimer = null;

        function buscarAniversariantes(mes) {
            if (requisicaoAtual) requisicaoAtual.abort();
            if (debounceTimer) clearTimeout(debounceTimer);

            mostrarOverlay({
                mensagem: 'Carregando aniversariantes...',
                carregando: true
            });

            debounceTimer = setTimeout(() => {
                requisicaoAtual = $.ajax({
                    url: '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/beneficiarios/aniversariantes/dados/',
                    method: 'POST',
                    data: {
                        action: 'buscarAniversariantes',
                        mes: mes
                    },
                    dataType: 'json',
                    success: function(res) {
                        esconderOverlay();
                        $('#resultadoAniversariantes').empty();

                        if (res.status === 'ok') {
                            if (res.dados.length > 0) {
                                const hoje = new Date();
                                const ontem = new Date();
                                ontem.setDate(hoje.getDate() - 1);
                                const amanha = new Date();
                                amanha.setDate(hoje.getDate() + 1);

                                const cursosSet = new Set();
                                const turmasSet = new Set();

                                res.dados.forEach(item => {
                                    cursosSet.add(item.Curso || 'Sem curso');
                                    turmasSet.add(item.Turma || 'Sem turma');
                                });

                                $('#filtroCurso').html('<option value="">Todos</option>');
                                $('#filtroTurma').html('<option value="">Todos</option>');
                                [...cursosSet].sort().forEach(curso => {
                                    $('#filtroCurso').append(`<option value="${curso}">${curso}</option>`);
                                });
                                [...turmasSet].sort().forEach(turma => {
                                    $('#filtroTurma').append(`<option value="${turma}">${turma}</option>`);
                                });

                                res.dados.forEach(item => {
                                    const nomeExibicao = item.Apelido ? `(${item.Apelido}) ${item.Nome}` : item.Nome;
                                    const [dia, mesNasc] = item.Nascimento.split('/').map(str => parseInt(str));
                                    const dataAniv = new Date(hoje.getFullYear(), mesNasc - 1, dia);
                                    const destaque = [ontem, hoje, amanha].some(data =>
                                        data.getDate() === dataAniv.getDate() && data.getMonth() === dataAniv.getMonth()
                                    );
                                    const destaqueClasse = destaque ? 'ativo' : '';

                                    $('#resultadoAniversariantes').append(`
                    <div class="col-md-3 card-wrapper" data-curso="${item.Curso}" data-turma="${item.Turma}">
                        <div class="card-aniversariante ${destaqueClasse}">
                            <img src="${item.Foto ? '<?php echo rtrim(bootstrap_assets_img_url(), '/'); ?>/fotos/' + item.Foto : '<?php echo bootstrap_foto_url(''); ?>'}" class="foto-aniversariante" alt="Foto">
                            <div><strong>${nomeExibicao}</strong></div>
                            <div>${item.Nascimento}</div>
                            <div class="text-muted">Fará ${item.Idade} anos</div>
                            <div><small>Curso: ${item.Curso || '-'}</small></div>
                            <div><small>Turma: ${item.Turma || '-'}</small></div>
                        </div>
                    </div>
                `);
                                });

                                $('#filtroCurso, #filtroTurma').off('change').on('change', function() {
                                    const cursoSel = $('#filtroCurso').val();
                                    const turmaSel = $('#filtroTurma').val();

                                    $('.card-wrapper').each(function() {
                                        const curso = $(this).data('curso');
                                        const turma = $(this).data('turma');

                                        const cursoOk = !cursoSel || cursoSel === curso;
                                        const turmaOk = !turmaSel || turmaSel === turma;

                                        $(this).toggle(cursoOk && turmaOk);
                                    });
                                });

                            } else {
                                $('#resultadoAniversariantes').html('<div class="text-muted">Nenhum aniversariante encontrado.</div>');
                            }
                        } else {
                            $('#resultadoAniversariantes').html('<div class="text-danger">Erro ao buscar aniversariantes.</div>');
                        }
                    },
                    error: function(xhr, status) {
                        esconderOverlay();
                        if (status !== "abort") {
                            $('#resultadoAniversariantes').html('<div class="text-danger">Erro de conexão ou servidor demorando.</div>');
                        }
                    }
                });
            }, 300);
        }

        $(document).ready(function() {
            const mesAtual = (new Date().getMonth() + 1).toString().padStart(2, '0');
            $('#mesSelecionado').val(mesAtual);
            buscarAniversariantes(mesAtual);

            $('#btnBuscar').click(function() {
                const mes = $('#mesSelecionado').val();
                if (mes) buscarAniversariantes(mes);
            });
        });
    </script>
</body>

</html>





