<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$paginaAtual = $pagina ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <link rel="stylesheet" href="<?php echo $BASE_para_URL; ?>/assets/css/overlayNotifica.css">
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/overlayNotifica.js"></script>
</head>
<script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js?v=<?php echo @filemtime($BASE_para_PATH . '/app/assets/js/app.js') ?: time(); ?>"></script>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>
        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
            <main class="content">
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3">Gerenciar Páginas do Sistema</h1>

                    <?php
                    if (isset($_GET['msg'])) {
                        echo "<div class='alert alert-success'>" . urldecode($_GET['msg']) . "</div>";
                    } elseif (isset($_GET['erro'])) {
                        echo "<div class='alert alert-danger'>" . urldecode($_GET['erro']) . "</div>";
                    }
                    ?>

                    <div class="card mb-4">
                        <div class="card-body">
                            <form action="<?php echo htmlspecialchars($PAGINAS_ROUTE); ?>/" method="post" onsubmit="mostrarOverlay({mensagem: 'Salvando dados...', carregando: true})">
                                <input type="hidden" name="action" value="save">
                                <input type="hidden" name="IdPagina" value="<?php echo htmlspecialchars($paginaAtual['IdPagina'] ?? ''); ?>">
                                <div class="mb-3">
                                    <label for="NomePagina" class="form-label">Nome da Página</label>
                                    <input type="text" class="form-control" id="NomePagina" name="NomePagina" value="<?php echo htmlspecialchars($paginaAtual['NomePagina'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="DescricaoPagina" class="form-label">Descrição da Página</label>
                                    <textarea class="form-control" id="DescricaoPagina" name="DescricaoPagina" rows="2"><?php echo htmlspecialchars($paginaAtual['DescricaoPagina'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="TipoApp" class="form-label">Grupo de funcionalidade (se não definir, irá para o grupo Outros)</label>
                                    <textarea class="form-control" id="TipoApp" name="TipoApp" rows="2"><?php echo htmlspecialchars($paginaAtual['TipoApp'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="ArquivoPHP" class="form-label">Arquivo PHP</label>
                                    <input type="text" class="form-control" id="ArquivoPHP" name="ArquivoPHP" value="<?php echo htmlspecialchars($paginaAtual['ArquivoPHP'] ?? ''); ?>" required>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">Salvar</button>
                                    <button type="button" class="btn btn-secondary" onclick="cancelarPagina()">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5>Páginas Cadastradas</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Nome</th>
                                            <th>Descrição</th>
                                            <th>Grupo</th>
                                            <th>Arquivo PHP</th>
                                            <th style="width: 120px;">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paginas as $p): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($p['IdPagina']); ?></td>
                                                <td><?php echo htmlspecialchars($p['NomePagina']); ?></td>
                                                <td><?php echo htmlspecialchars($p['DescricaoPagina']); ?></td>
                                                <td><?php echo htmlspecialchars($p['TipoApp']); ?></td>
                                                <td><?php echo htmlspecialchars($p['ArquivoPHP']); ?></td>
                                                <td>
                                                    <button class="btn btn-warning btn-sm" onclick="alterarPagina(<?php echo (int) $p['IdPagina']; ?>)" title="Editar">
                                                        <i class="fa fa-pen" data-feather="edit-3"></i>
                                                    </button>
                                                    <form method="post" action="<?php echo htmlspecialchars($PAGINAS_ROUTE); ?>/" onsubmit="return confirm('Tem certeza que deseja excluir esta página?')" style="display:inline-block">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="IdPagina" value="<?php echo (int) $p['IdPagina']; ?>">
                                                        <button class="btn btn-danger btn-sm" title="Excluir">
                                                            <i class="fa fa-trash" data-feather="trash-2"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>

                                </table>
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
    <script src="https://unpkg.com/feather-icons"></script>
    <script>
        feather.replace();

        function alterarPagina(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo htmlspecialchars($PAGINAS_ROUTE); ?>/';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'IdPagina';
            input.value = id;

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }

        function cancelarPagina() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo htmlspecialchars($PAGINAS_ROUTE); ?>/';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'action';
            input.value = 'cancel';

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    </script>

</body>

</html>







