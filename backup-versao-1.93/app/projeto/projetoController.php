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

require_once 'projetoModel.php';

function gerarNomeLogoJpg()
{
    $milis = (int) ((microtime(true) - floor(microtime(true))) * 1000);
    return sprintf('%s%03d.jpg', date('YmdHis'), $milis);
}

function apagarLogoSeExistir($logo)
{
    $logo = trim((string) $logo);
    if ($logo === '') {
        return;
    }

    $logo = basename($logo);
    $caminho = $_SESSION['BASE_PATH'] . '/assets/img/logos/' . $logo;
    if (is_file($caminho)) {
        @unlink($caminho);
    }
}

function processarUploadLogo($arquivo, $logoAtual = '')
{
    if (!$arquivo || $arquivo['error'] !== UPLOAD_ERR_OK || empty($arquivo['tmp_name'])) {
        return $logoAtual;
    }

    if ($arquivo['size'] > 500 * 1024) {
        return ['erro' => 'A logo deve ter no maximo 500KB.'];
    }

    $nomeOriginal = $arquivo['name'] ?? '';
    $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
    if ($ext !== 'jpg' && $ext !== 'jpeg') {
        return ['erro' => 'A logo deve estar em formato JPG.'];
    }

    $info = @getimagesize($arquivo['tmp_name']);
    if (!$info || ($info[2] ?? null) !== IMAGETYPE_JPEG) {
        return ['erro' => 'Arquivo JPG invalido.'];
    }

    $destinoDir = $_SESSION['BASE_PATH'] . '/assets/img/logos/';
    if (!is_dir($destinoDir)) {
        @mkdir($destinoDir, 0777, true);
    }

    $novoNome = gerarNomeLogoJpg();
    $destino = $destinoDir . $novoNome;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        return ['erro' => 'Falha ao salvar a logo enviada.'];
    }

    if ($logoAtual && $logoAtual !== $novoNome) {
        apagarLogoSeExistir($logoAtual);
    }

    return $novoNome;
}

$acao = $_POST['acao'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($acao === 'excluir') {
        $id = intval($_POST['IdProjeto'] ?? 0);
        $projeto = $id ? ProjetoModel::getById($id) : null;

        if ($id && $projeto && ProjetoModel::delete($id)) {
            if (!empty($projeto['LogoProjeto'])) {
                apagarLogoSeExistir($projeto['LogoProjeto']);
            }
            header("Location: projetoController.php?msg=" . urlencode("Projeto excluido com sucesso!"));
            exit;
        }

        header("Location: projetoController.php?erro=" . urlencode("Erro ao excluir projeto."));
        exit;
    }

    if ($acao === 'salvar') {
        $id = intval($_POST['IdProjeto'] ?? 0);
        $logoAtual = $_POST['LogoAtual'] ?? '';
        $upload = $_FILES['LogoProjeto'] ?? null;

        $resultadoLogo = processarUploadLogo($upload, $logoAtual);
        if (is_array($resultadoLogo) && isset($resultadoLogo['erro'])) {
            header("Location: projetoController.php?erro=" . urlencode($resultadoLogo['erro']));
            exit;
        }

        $dados = [
            'IdProjeto' => $id,
            'NomeProjeto' => $_POST['NomeProjeto'] ?? '',
            'LogoProjeto' => $resultadoLogo,
            'TermosContrato' => $_POST['TermosContrato'] ?? ''
        ];

        if ($id > 0) {
            if (ProjetoModel::update($dados)) {
                header("Location: projetoController.php?id=$id&msg=" . urlencode("Projeto atualizado com sucesso!"));
                exit;
            }
            header("Location: projetoController.php?id=$id&erro=" . urlencode("Erro ao atualizar projeto."));
            exit;
        }

        if (ProjetoModel::create($dados)) {
            header("Location: projetoController.php?msg=" . urlencode("Projeto cadastrado com sucesso!"));
            exit;
        }

        header("Location: projetoController.php?erro=" . urlencode("Erro ao cadastrar projeto."));
        exit;
    }
}

if (isset($_GET['msg'])) $_POST['msg'] = urldecode($_GET['msg']);
if (isset($_GET['erro'])) $_POST['erro'] = urldecode($_GET['erro']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$projeto = $id ? ProjetoModel::getById($id) : null;

if ($projeto) {
    foreach ($projeto as $key => $value) {
        $_POST[$key] = $value;
    }
}

$listaProjetos = ProjetoModel::listAll();

require 'formProjeto.php';
exit;
