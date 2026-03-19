<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$PAGINAS_ROUTE = rtrim((string) $BASE_para_URL, '/') . '/permissoes/paginas';

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: " . rtrim((string) $BASE_para_URL, '/') . "/login/?redirect=$redirect_url");
    exit();
}

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/app/models/permissao/paginaModel.php';

$action = $_POST['action'] ?? '';

if ($action === 'save') {
    $nome = trim((string) ($_POST['NomePagina'] ?? ''));
    $descricao = trim((string) ($_POST['DescricaoPagina'] ?? ''));
    $tipoApp = trim((string) ($_POST['TipoApp'] ?? ''));
    $arquivo = trim((string) ($_POST['ArquivoPHP'] ?? ''));
    $id = $_POST['IdPagina'] ?? null;

    try {
        if ($id !== null && $id !== '') {
            PaginaModel::atualizarPagina((int) $id, $nome, $descricao, $tipoApp, $arquivo);
            $msg = urlencode('Registro atualizado com sucesso!');
        } else {
            PaginaModel::criarPagina($nome, $descricao, $tipoApp, $arquivo);
            $msg = urlencode('Registro salvo com sucesso!');
        }

        header("Location: {$PAGINAS_ROUTE}/?msg=$msg");
        exit();
    } catch (Throwable $e) {
        $erro = urlencode('Erro ao salvar a página: ' . $e->getMessage());
        header("Location: {$PAGINAS_ROUTE}/?erro=$erro");
        exit();
    }
}

if ($action === 'delete') {
    $id = $_POST['IdPagina'] ?? ($_POST['id'] ?? null);
    if ($id === null || $id === '') {
        header("Location: {$PAGINAS_ROUTE}/?erro=" . urlencode('ID inválido'));
        exit();
    }

    try {
        $ok = PaginaModel::excluirPagina((int) $id);
        if ($ok) {
            header("Location: {$PAGINAS_ROUTE}/?msg=" . urlencode('Página excluída com sucesso!'));
        } else {
            header("Location: {$PAGINAS_ROUTE}/?erro=" . urlencode('Não foi possível excluir a página.'));
        }
        exit();
    } catch (Throwable $e) {
        header("Location: {$PAGINAS_ROUTE}/?erro=" . urlencode('Erro ao excluir: ' . $e->getMessage()));
        exit();
    }
}

if ($action === 'cancel') {
    header("Location: {$PAGINAS_ROUTE}/");
    exit();
}

$idPagina = $_POST['IdPagina'] ?? null;
$pagina = null;
if ($idPagina !== null && $idPagina !== '') {
    $pagina = PaginaModel::buscarPagina((int) $idPagina);
}

$paginas = PaginaModel::listarPaginas();

require __DIR__ . '/formPaginasView.php';
