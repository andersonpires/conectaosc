<?php
require_once $_SESSION['BASE_PATH'] . '/checa-token.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $_SESSION['BASE_PATH'] . '/template/header.php'; ?>
</head>

<body>
    <div class="wrapper">
        <?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>
        <div class="main">
            <?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php'; ?>
            <main class="content">
                <div class="container-fluid p-0">
                    <?php
                    $msg = $_POST['msg'] ?? '';
                    $erro = $_POST['erro'] ?? '';
                    if ($msg) {
                        echo '<div class="alert alert-success" role="alert">' . htmlspecialchars($msg) . '</div>';
                    } elseif ($erro) {
                        echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($erro) . '</div>';
                    }
                    ?>

                    <h1 class="h3 mb-3">Feriados</h1>

                    <div class="card mb-4">
                        <div class="card-body">
                            <form action="feriadosController.php" method="post">
                                <input type="hidden" name="IdFeriado" id="IdFeriado" value="<?= htmlspecialchars($_POST['IdFeriado'] ?? '') ?>">

                                <div class="mb-3">
                                    <label for="NomeFeriado" class="form-label">Nome do feriado</label>
                                    <input type="text" class="form-control" id="NomeFeriado" name="Nome" value="<?= htmlspecialchars($_POST['Nome'] ?? '') ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="DataFeriado" class="form-label">Data (dd/mm)</label>
                                    <input type="text" class="form-control" id="DataFeriado" name="DataFeriado" placeholder="dd/mm" value="<?= htmlspecialchars($_POST['DataFeriado'] ?? '') ?>" required>
                                </div>

                                <button type="submit" name="acao" value="salvar" class="btn btn-primary">Salvar</button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-3">Feriados cadastrados</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Nome</th>
                                            <th>Cadastrado por</th>
                                            <th>Acoes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($listaFeriados)): ?>
                                            <?php foreach ($listaFeriados as $item): ?>
                                                <?php
                                                $idItem = intval($item['IdFeriado'] ?? 0);
                                                $nomeItem = $item['Nome'] ?? '';
                                                $dataItem = $item['DataFeriado'] ?? '';
                                                $autor = trim(($item['NomeUsuario'] ?? '') . ' ' . ($item['SobrenomeUsuario'] ?? ''));
                                                $isOwner = intval($item['IdColaborador'] ?? 0) === intval($_SESSION['Cod'] ?? 0);
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($dataItem) ?></td>
                                                    <td><?= htmlspecialchars($nomeItem) ?></td>
                                                    <td><?= htmlspecialchars($autor ?: '-') ?></td>
                                                    <td>
                                                        <?php if ($isOwner): ?>
                                                            <button type="button"
                                                                    class="btn btn-sm btn-warning btn-editar-feriado"
                                                                    data-id="<?= $idItem ?>"
                                                                    data-nome="<?= htmlspecialchars($nomeItem) ?>"
                                                                    data-data="<?= htmlspecialchars($dataItem) ?>">
                                                                Editar
                                                            </button>
                                                            <form action="feriadosController.php" method="post" style="display:inline-block;" onsubmit="return confirm('Excluir este feriado?');">
                                                                <input type="hidden" name="IdFeriado" value="<?= $idItem ?>">
                                                                <button type="submit" name="acao" value="excluir" class="btn btn-sm btn-danger">Excluir</button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4">Nenhum feriado cadastrado.</td>
                                            </tr>
                                        <?php endif; ?>
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

    <script>
        document.querySelectorAll('.btn-editar-feriado').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.getElementById('IdFeriado').value = btn.getAttribute('data-id');
                document.getElementById('NomeFeriado').value = btn.getAttribute('data-nome');
                document.getElementById('DataFeriado').value = btn.getAttribute('data-data');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    </script>

    <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>
</body>

</html>
