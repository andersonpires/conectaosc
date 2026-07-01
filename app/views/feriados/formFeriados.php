<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
require_once $BASE_para_PATH . '/api/legacy/checa-token.php';

$dataAtual = (string) ($_POST['DataFeriado'] ?? '');
$tipoAtual = preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dataAtual) ? 'pontual' : 'recorrente';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
</head>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>
        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
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
                            <form action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/feriados/" method="post">
                                <input type="hidden" name="IdFeriado" id="IdFeriado" value="<?= htmlspecialchars($_POST['IdFeriado'] ?? '') ?>">

                                <div class="mb-3">
                                    <label for="NomeFeriado" class="form-label">Nome do feriado</label>
                                    <input type="text" class="form-control" id="NomeFeriado" name="Nome" value="<?= htmlspecialchars($_POST['Nome'] ?? '') ?>" required>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input tipo-ocorrencia" type="radio" name="TipoOcorrencia" id="TipoOcorrenciaRecorrente" value="recorrente" <?= $tipoAtual === 'recorrente' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="TipoOcorrenciaRecorrente">Ocorre todo ano</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input tipo-ocorrencia" type="radio" name="TipoOcorrencia" id="TipoOcorrenciaPontual" value="pontual" <?= $tipoAtual === 'pontual' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="TipoOcorrenciaPontual">Ocorre apenas uma vez</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="DataFeriado" class="form-label" id="DataFeriadoLabel">Data (dd/mm)</label>
                                    <input type="text" class="form-control" id="DataFeriado" name="DataFeriado" placeholder="dd/mm" value="<?= htmlspecialchars($dataAtual) ?>" required>
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
                                            <th>Tipo</th>
                                            <th>Nome</th>
                                            <th>Cadastrado por</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($listaFeriados)): ?>
                                            <?php foreach ($listaFeriados as $item): ?>
                                                <?php
                                                $idItem = intval($item['IdFeriado'] ?? 0);
                                                $nomeItem = $item['Nome'] ?? '';
                                                $dataItem = $item['DataFeriado'] ?? '';
                                                $tipoItem = preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dataItem) ? 'Pontual' : 'Recorrente';
                                                $autor = trim(($item['NomeUsuario'] ?? '') . ' ' . ($item['SobrenomeUsuario'] ?? ''));
                                                $isOwner = intval($item['IdColaborador'] ?? 0) === intval($_SESSION['Cod'] ?? 0);
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($dataItem) ?></td>
                                                    <td><?= htmlspecialchars($tipoItem) ?></td>
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
                                                            <form action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/feriados/" method="post" style="display:inline-block;" onsubmit="return confirm('Excluir este feriado?');">
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
                                                <td colspan="5">Nenhum feriado cadastrado.</td>
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
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>

    <script>
        const dataInput = document.getElementById('DataFeriado');
        const dataLabel = document.getElementById('DataFeriadoLabel');
        const radiosOcorrencia = document.querySelectorAll('.tipo-ocorrencia');

        function atualizarFormatoData() {
            const pontual = document.getElementById('TipoOcorrenciaPontual').checked;
            dataLabel.textContent = pontual ? 'Data (dd/mm/aaaa)' : 'Data (dd/mm)';
            dataInput.placeholder = pontual ? 'dd/mm/aaaa' : 'dd/mm';
            dataInput.maxLength = pontual ? 10 : 5;
        }

        radiosOcorrencia.forEach((radio) => {
            radio.addEventListener('change', atualizarFormatoData);
        });

        document.querySelectorAll('.btn-editar-feriado').forEach((btn) => {
            btn.addEventListener('click', () => {
                const data = btn.getAttribute('data-data') || '';
                document.getElementById('IdFeriado').value = btn.getAttribute('data-id');
                document.getElementById('NomeFeriado').value = btn.getAttribute('data-nome');
                dataInput.value = data;
                document.getElementById(data.length === 10 ? 'TipoOcorrenciaPontual' : 'TipoOcorrenciaRecorrente').checked = true;
                atualizarFormatoData();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });

        atualizarFormatoData();
    </script>

    <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>
</body>

</html>
