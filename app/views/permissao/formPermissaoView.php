<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$PERMISSOES_ROUTE = rtrim((string)$BASE_para_URL, '/') . '/permissoes';
$appJsVersion = @filemtime($BASE_para_PATH . '/app/assets/js/app.js') ?: time();
if (false) {
// formPermissao.php
if (session_status() !== PHP_SESSION_ACTIVE) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}
}

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: " . rtrim((string)$BASE_para_URL, '/') . "/login/?redirect=$redirect_url");
    exit();
}

//require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

$paginas = [];
$stmt = $pdo->query("SELECT NomePagina, DescricaoPagina, ArquivoPHP, TipoApp FROM tbPaginas ORDER BY TipoApp, NomePagina ASC");
if ($stmt) {
    $paginas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Agrupar por TipoApp
$grupos = [];
foreach ($paginas as $pagina) {
    $tipoApp = $pagina['TipoApp'] ?: 'Outros'; // Se TipoApp estiver vazio ou null, vai para 'Outros'
    $grupos[$tipoApp][] = $pagina;
}

// Ordenar os grupos por TipoApp, exceto "Outros", que deve ficar por último
ksort($grupos);
if (isset($grupos['Outros'])) {
    $outros = $grupos['Outros'];
    unset($grupos['Outros']);
    $grupos['Outros'] = $outros;
}
}
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

        .group-title {
            font-weight: bold;
            margin-top: 10px;
            color: rgb(26, 27, 29);
            /* Título em cinza escuro (#343a40) */
        }

        /* List group com cor de fundo cinza claro */
        .list-group-item {
            background-color: #f8f9fa !important;
        }

        .list-group-item-action.active {
            background-color: #f8f9fa !important;
            border-color: #ddd !important;
        }

        /* Responsividade para colocar list-group ao lado um do outro */
        .list-group-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .list-group {
            flex: 1 1 200px;
            /* Garante mínimo de 200px e expansão até o limite disponível */
        }

        /* Estilo do label para que ele atue como o clique do checkbox */
        .form-check-label {
            cursor: pointer;
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
                    <div class="titulo-pagina">
                        <?php echo isset($_GET['idPermissao']) ? 'Editar Permissão' : 'Nova Permissão'; ?>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <form id="formPermissao" method="post">
                                <input type="hidden" name="idPermissao" value="<?php echo $_GET['idPermissao'] ?? ''; ?>">

                                <div class="mb-3">
                                    <label class="form-label">Nome da Permissão</label>
                                    <input type="text" name="nomePermissao" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Descrição</label>
                                    <textarea name="descricao" class="form-control" rows="3"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Páginas com acesso</label>

                                    <!-- Botões "Marcar todos" e "Desmarcar todos" -->
                                    <div class="mb-3">
                                        <button type="button" id="marcarTodos" class="btn btn-primary">Marcar todos</button>
                                        <button type="button" id="desmarcarTodos" class="btn btn-secondary">Desmarcar todos</button>
                                    </div>

                                    <!-- List group com páginas agrupadas -->
                                    <div class="list-group-container">
                                        <?php foreach ($grupos as $tipo => $grupo): ?>
                                            <div class="list-group">
                                                <div class="list-group-item-action ">
                                                    <strong><?php echo $tipo; ?></strong>
                                                </div>
                                                <?php foreach ($grupo as $pagina): ?>
                                                    <div class="list-group-item">
                                                        <div class="form-check"
                                                            <?php echo $pagina['DescricaoPagina'] ? 'title="' . htmlspecialchars($pagina['DescricaoPagina']) . '"' : ''; ?>>
                                                            <input class="form-check-input" type="checkbox" name="paginas[]" value="<?php echo htmlspecialchars($pagina['ArquivoPHP']); ?>" id="checkbox-<?php echo htmlspecialchars($pagina['ArquivoPHP']); ?>">
                                                            <label class="form-check-label" for="checkbox-<?php echo htmlspecialchars($pagina['ArquivoPHP']); ?>">
                                                                <?php echo htmlspecialchars($pagina['NomePagina']); ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label d-block">Controle de acesso por horário</label>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="todosHorariosDias" id="todosHorariosDias" checked>
                                        <label class="form-check-label" for="todosHorariosDias">
                                            Todos os horários e dias
                                        </label>
                                    </div>

                                    <div id="gradeHorarios" class="table-responsive" style="display: none;">
                                        <table class="table table-bordered text-center align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Horário</th>
                                                    <?php
                                                    $diasSemana = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab', 'Dom'];
                                                    foreach ($diasSemana as $dia) {
                                                        echo "<th>{$dia}</th>";
                                                    }
                                                    ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                for ($h = 0; $h <= 23; $h++) {
                                                    $horaLabel = str_pad($h, 2, '0', STR_PAD_LEFT) . 'h';
                                                    echo "<tr>";
                                                    echo "<td><strong>{$horaLabel}</strong></td>";
                                                    foreach ($diasSemana as $dia) {
                                                        $value = "{$dia}_{$h}";
                                                        echo "<td><input type='checkbox' class='chk-horario' name='horariosSelecionados[]' value='{$value}'></td>";
                                                    }
                                                    echo "</tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success">Salvar</button>
                                    <a href="<?php echo $PERMISSOES_ROUTE; ?>/?view=list" class="btn btn-secondary">Retornar</a>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>
</body>

<script>
    $(document).ready(function() {
        // Funcionalidade "Marcar todos"
        $('#marcarTodos').on('click', function() {
            $('input[type="checkbox"]').prop('checked', true);
        });

        // Funcionalidade "Desmarcar todos"
        $('#desmarcarTodos').on('click', function() {
            $('input[type="checkbox"]').prop('checked', false);
        });

        const idPermissao = $('input[name="idPermissao"]').val();
        if (idPermissao) {
            mostrarOverlay({
                mensagem: 'Carregando dados da Permissão...',
                carregando: true
            });

            setTimeout(() => {
                $.post('<?php echo $PERMISSOES_ROUTE; ?>/', {
                    action: 'carregarPermissao',
                    idPermissao
                }, function(res) {
                    esconderOverlay();
                    if (res.status === 'ok') {
                        $('input[name="nomePermissao"]').val(res.nome);
                        $('textarea[name="descricao"]').val(res.descricao);
                        res.paginas.forEach(p => {
                            $(`input[value='${p}']`).prop('checked', true);
                        });

                        // NOVO BLOCO: carregar horários
                        if (res.horarios && Array.isArray(res.horarios)) {
                            const totalMarcados = res.horarios.length;
                            const totalPossiveis = 7 * 24; // 168

                            if (totalMarcados === totalPossiveis) {
                                $('#todosHorariosDias').prop('checked', true);
                                $('#gradeHorarios').hide();
                            } else {
                                $('#todosHorariosDias').prop('checked', false);
                                $('#gradeHorarios').show();
                                res.horarios.forEach(h => {
                                    $(`input[name="horariosSelecionados[]"][value="${h}"]`).prop('checked', true);
                                });
                            }
                        }

                    } else {
                        mostrarOverlay({
                            mensagem: res.msg,
                            carregando: false
                        });
                    }
                }, 'json').fail(function(xhr) {
                    mostrarOverlay({
                        mensagem: 'Erro de conexão ou servidor demorando.',
                        carregando: false
                    });
                });
            }, 300); // pequeno delay para garantir que o overlay apareça
        }

        $('#formPermissao').on('submit', function(e) {
            e.preventDefault();
            mostrarOverlay({
                mensagem: 'Salvando Permissão...',
                carregando: true
            });

            const formData = $(this).serializeArray();
            formData.push({
                name: 'action',
                value: 'salvarPermissao'
            });

            $.post('<?php echo $PERMISSOES_ROUTE; ?>/', formData, function(res) {
                if (res.status === 'ok') {
                    mostrarOverlay({
                        mensagem: res.msg,
                        carregando: false,
                        onClose: () => window.location.href = '<?php echo $PERMISSOES_ROUTE; ?>/?view=list'
                    });
                } else {
                    mostrarOverlay({
                        mensagem: res.msg || 'Erro ao salvar Permissão',
                        carregando: false
                    });
                }
            }, 'json').fail(function(xhr) {
                mostrarOverlay({
                    mensagem: 'Erro na requisição: ' + xhr.status,
                    carregando: false
                });
            });
        });
    });

    $(function() {
        const diasUteis = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex'];

        function marcarComercial() {
            $('.chk-horario').prop('checked', false);
            diasUteis.forEach(dia => {
                for (let h = 7; h <= 16; h++) {
                    $(`input[value='${dia}_${h}']`).prop('checked', true);
                }
            });
        }

        $('#todosHorariosDias').on('change', function() {
            if (this.checked) {
                $('#gradeHorarios').hide();
                $('.chk-horario').prop('checked', false);
            } else {
                $('#gradeHorarios').show();
                marcarComercial();
            }
        });

        // Se o campo estiver desmarcado (por reedição da permissão), mostramos a grade
        if (!$('#todosHorariosDias').is(':checked')) {
            $('#gradeHorarios').show();
        }
    });
</script>

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

</html>











