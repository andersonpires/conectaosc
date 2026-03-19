<?php
if (false) {
// listPermissao.php
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url");
    exit();
}

require_once $_SESSION['BASE_PATH'] . '/checa-token.php';
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $_SESSION['BASE_PATH'] . '/template/header.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/overlayNotifica.js"></script>
    <link rel="stylesheet" href="<?php echo $_SESSION['BASE_URL']; ?>/assets/css/overlayNotifica.css">
    <style>
        .titulo-pagina {
            font-size: 24px;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 20px;
        }

        .table thead {
            background-color: #f8f9fa;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .btn {
            min-width: 40px;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>
        <div class="main">
            <?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php'; ?>
            <main class="content">
                <div class="container mt-4">
                    <div class="titulo-pagina">Permissões Cadastradas</div>
                    <div class="mb-3 text-end">
                        <a href="permissaoController.php?view=form" class="btn btn-primary" data-bs-toggle="tooltip" title="Nova Permissão">
                            <i class="fa-solid fa-plus"></i> Nova Permissão
                        </a>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <div id="tabelaContainer" style="display: none;">
                                    <table class="table table-bordered table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 50px;">#</th>
                                                <th>Nome</th>
                                                <th>Descrição</th>
                                                <th style="width: 120px;">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tabelaPermissoes"></tbody>
                                    </table>
                                </div>
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
        let idParaExcluir = null;
        $(document).ready(function() {
            mostrarOverlay({
                mensagem: 'Carregando permissões...',
                carregando: true
            });

            debounceTimer = setTimeout(() => {
                CarregarTable();
            }, 300);


            function carregarPermissoes() {
                mostrarOverlay({
                    mensagem: 'Carregando permissões...',
                    carregando: true
                });
                $.post('listPermissaoController.php', {
                    action: 'listar'
                }, function(res) {
                    esconderOverlay();
                    if (res.status === 'ok') {
                        let html = '';
                        res.dados.forEach((item, index) => {
                            html += `<tr>
                                <td>${index + 1}</td>
                                <td>${item.nome}</td>
                                <td>${item.descricao}</td>
                                <td>
                                    <a href="permissaoController.php?view=form&idPermissao=${item.id}" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Editar">
                                        <i class="fa-solid fa-pen-to-square" data-feather="edit-3"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger ms-1" onclick="abrirModalExclusao(${item.id})" data-bs-toggle="tooltip" title="Excluir">
                                        <i class="fa-solid fa-trash" data-feather="trash-2"></i>
                                    </button>
                                </td>
                            </tr>`;
                        });
                        $('#tabelaPermissoes').html(html);
                        feather.replace();
                    } else {
                        mostrarOverlay({
                            mensagem: res.msg || 'Erro ao carregar permissões.',
                            carregando: false
                        });
                    }
                }, 'json');
            }

            $('#confirmarExclusaoPermissao').on('click', function() {
                if (!idParaExcluir) return;
                const id = idParaExcluir;
                idParaExcluir = null;
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalExcluirPermissao'));
                modal.hide();

                mostrarOverlay({
                    mensagem: 'Excluindo permissão...',
                    carregando: true
                });
                $.post('listPermissaoController.php', {
                    action: 'excluirPermissao',
                    idPermissao: id
                }, function(res) {
                    if (res.status === 'ok') {
                        mostrarOverlay({
                            mensagem: res.msg,
                            carregando: false,
                            onClose: () => location.reload()
                        });
                    } else {
                        mostrarOverlay({
                            mensagem: res.msg || 'Erro ao excluir permissão.',
                            carregando: false
                        });
                    }
                }, 'json');
            });
        });

        function abrirModalExclusao(id) {
            idParaExcluir = id;
            const modal = new bootstrap.Modal(document.getElementById('modalExcluirPermissao'));
            modal.show();
        }
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

    <div class="modal fade" id="modalExcluirPermissao" tabindex="-1" aria-labelledby="modalExcluirPermissaoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modalExcluirPermissaoLabel">Atenção!</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    Deseja realmente excluir esta permissão?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="confirmarExclusaoPermissao">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/feather-icons"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => feather.replace());

        let requisicaoAtual = null;

        function CarregarTable() {
            // Se já tem uma requisição em andamento, aborta
            if (requisicaoAtual) {
                requisicaoAtual.abort();
            }

            mostrarOverlay({
                mensagem: 'Carregando permissões...',
                carregando: true
            });

            requisicaoAtual = $.ajax({
                url: 'listPermissaoController.php',
                method: 'POST',
                data: {
                    action: 'listar'
                },
                dataType: 'json',
                success: function(res) {
                    esconderOverlay();
                    if (res.status === 'ok') {
                        let html = '';
                        res.dados.forEach((item, index) => {
                            html += `<tr>
                        <td>${index + 1}</td>
                        <td>${item.nome}</td>
                        <td>${item.descricao}</td>
                        <td>
                            <a href="permissaoController.php?view=form&idPermissao=${item.id}" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Editar">
                                <i class="fa-solid fa-pen-to-square" data-feather="edit-3"></i>
                            </a>
                            <button class="btn btn-sm btn-danger ms-1" onclick="abrirModalExclusao(${item.id})" data-bs-toggle="tooltip" title="Excluir">
                                <i class="fa-solid fa-trash" data-feather="trash-2"></i>
                            </button>
                        </td>
                    </tr>`;
                        });
                        $('#tabelaPermissoes').html(html);
                        $('#tabelaContainer').fadeIn();
                        feather.replace();
                    } else {
                        mostrarOverlay({
                            mensagem: res.msg || 'Erro ao carregar permissões.',
                            carregando: false,
                            onClose: () => esconderOverlay()
                        });
                    }
                },
                error: function(xhr, status, error) {
                    if (status !== "abort") {
                        mostrarOverlay({
                            mensagem: 'Erro de conexão ou servidor demorando.',
                            carregando: false,
                            onClose: () => esconderOverlay()
                        });
                    }
                }
            });
        }
    </script>
</body>

</html>
