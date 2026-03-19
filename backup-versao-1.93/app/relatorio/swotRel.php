<?php
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
require_once $_SESSION['BASE_PATH'] . '/app/swot/swotModel.php';

$temas = [
    'INOVAÇÃO EM PROJETOS EXISTENTES',
    'NOVOS PROJETOS PARA INVESTIDORES',
    'NOVOS SERVIÇOS',
    'NOVOS PRODUTOS'
];

$temaSelecionado = $_GET['tema'] ?? 'todos';
$usuarioSelecionado = $_GET['usuario'] ?? 'todos';
$filtrar = isset($_GET['filtrar']);

$usuarios = $pdo->query("SELECT IdColaborador, Nome, Sobrenome FROM tbUser WHERE habilitado = 1 ORDER BY Nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$dados = [];
$insightsPorUsuario = [];

if ($filtrar) {
    $dados = SwotModel::listForReport($temaSelecionado, $usuarioSelecionado);
    if (!empty($dados)) {
        foreach ($dados as $item) {
            $nomeCompleto = trim(($item['Nome'] ?? '') . ' ' . ($item['Sobrenome'] ?? ''));
            if ($nomeCompleto === '') {
                $nomeCompleto = 'Nao informado';
            }
            if (!isset($insightsPorUsuario[$nomeCompleto])) {
                $insightsPorUsuario[$nomeCompleto] = 0;
            }
            $insightsPorUsuario[$nomeCompleto]++;
        }
        ksort($insightsPorUsuario, SORT_NATURAL | SORT_FLAG_CASE);
    }
}
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
                    <h1 class="h3 mb-3">Relatório SWOT</h1>

                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="get">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-5">
                                        <label for="tema" class="form-label">Tema</label>
                                        <select id="tema" name="tema" class="form-select">
                                            <option value="todos" <?= $temaSelecionado === 'todos' ? 'selected' : '' ?>>Todos os temas</option>
                                            <?php foreach ($temas as $tema): ?>
                                                <option value="<?= htmlspecialchars($tema) ?>" <?= $temaSelecionado === $tema ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($tema) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="usuario" class="form-label">Usu&aacute;rio</label>
                                        <select id="usuario" name="usuario" class="form-select">
                                            <option value="todos" <?= $usuarioSelecionado === 'todos' ? 'selected' : '' ?>>Todos os usu&aacute;rios</option>
                                            <?php foreach ($usuarios as $usuario): ?>
                                                <?php
                                                $nomeCompleto = trim(($usuario['Nome'] ?? '') . ' ' . ($usuario['Sobrenome'] ?? ''));
                                                ?>
                                                <option value="<?= intval($usuario['IdColaborador']) ?>" <?= (string) $usuarioSelecionado === (string) $usuario['IdColaborador'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($nomeCompleto) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-3 d-flex gap-2">
                                        <button type="submit" name="filtrar" value="1" class="btn btn-primary">Confirmar</button>
                                        <?php if ($filtrar && !empty($dados)): ?>
                                            <?php
                                            $pdfUrl = $_SESSION['BASE_URL'] . '/get/getSwotPDF.php?tema=' . urlencode($temaSelecionado) . '&usuario=' . urlencode($usuarioSelecionado);
                                            ?>
                                            <a class="btn btn-danger" href="<?= $pdfUrl ?>" target="_blank">Gerar PDF</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php if ($filtrar): ?>
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3">Insights por usu&aacute;rio</h5>
                                <?php if (!empty($insightsPorUsuario)): ?>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($insightsPorUsuario as $nome => $quantidade): ?>
                                            <span class="badge bg-primary"><?= htmlspecialchars($nome) ?> (<?= intval($quantidade) ?>)</span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-muted">Nenhum insight encontrado para os filtros selecionados.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-3">Resultados</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Usu&aacute;rio</th>
                                                <th>Tema</th>
                                                <th>Insight</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($dados)): ?>
                                                <?php foreach ($dados as $item): ?>
                                                    <?php
                                                    $dataCriacao = $item['DataCriacao'] ?? '';
                                                    $dataFormatada = $dataCriacao ? date('d/m/Y H:i', strtotime($dataCriacao)) : '-';
                                                    $nomeCompleto = trim(($item['Nome'] ?? '') . ' ' . ($item['Sobrenome'] ?? ''));
                                                    $textoRaw = (string) ($item['Texto'] ?? '');
                                                    $textoRaw = html_entity_decode($textoRaw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                                    $texto = trim(preg_replace('/\s+/', ' ', strip_tags($textoRaw)));
                                                    ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($dataFormatada) ?></td>
                                                        <td><?= htmlspecialchars($nomeCompleto) ?></td>
                                                        <td><?= htmlspecialchars($item['Tema'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($texto) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4">Nenhum insight encontrado para os filtros selecionados.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
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

