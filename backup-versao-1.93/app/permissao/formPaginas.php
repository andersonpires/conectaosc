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
require_once 'paginaModel.php';

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
        header("Location: formPaginas.php?msg=$msg");
        exit();
    } catch (Throwable $e) {
        $erro = urlencode('Erro ao salvar a página: ' . $e->getMessage());
        header("Location: formPaginas.php?erro=$erro");
        exit();
    }
}

if ($action === 'delete') {
    $id = $_POST['IdPagina'] ?? ($_POST['id'] ?? null);
    if ($id === null || $id === '') {
        header("Location: formPaginas.php?erro=" . urlencode('ID inválido'));
        exit();
    }

    try {
        $ok = PaginaModel::excluirPagina((int) $id);
        if ($ok) {
            header("Location: formPaginas.php?msg=" . urlencode('Página excluída com sucesso!'));
        } else {
            header("Location: formPaginas.php?erro=" . urlencode('Não foi possível excluir a página.'));
        }
        exit();
    } catch (Throwable $e) {
        header("Location: formPaginas.php?erro=" . urlencode('Erro ao excluir: ' . $e->getMessage()));
        exit();
    }
}

if ($action === 'cancel') {
    header("Location: formPaginas.php");
    exit();
}

$idPagina = $_POST['IdPagina'] ?? null;
$pagina = null;
if ($idPagina !== null && $idPagina !== '') {
    $pagina = PaginaModel::buscarPagina((int) $idPagina);
}

$paginas = PaginaModel::listarPaginas();

require 'formPaginasView.php';
