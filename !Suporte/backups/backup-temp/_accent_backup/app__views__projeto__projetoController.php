<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_PATH = $runtime['base_path'];
$BASE_URL = $runtime['base_url'];
$PROJETOS_ROUTE = rtrim((string)$BASE_URL, '/') . '/projetos';
if (session_status() !== PHP_SESSION_ACTIVE) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}
}

if (!isset($BASE_PATH) || !isset($BASE_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: " . rtrim((string)$BASE_URL, '/') . "/login/?redirect=$redirect_url");
    exit();
}

require_once __DIR__ . '/projetoModel.php';

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
    $caminho = $BASE_PATH . '/app/assets/img/logos/' . $logo;
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

    $destinoDir = $BASE_PATH . '/app/assets/img/logos/';
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
            header("Location: {$PROJETOS_ROUTE}/?msg=" . urlencode("Projeto exclu?f?do com sucesso!"));
            exit;
        }

        header("Location: {$PROJETOS_ROUTE}/?erro=" . urlencode("Erro ao excluir projeto."));
        exit;
    }

    if ($acao === 'salvar') {
        $id = intval($_POST['IdProjeto'] ?? 0);
        $logoAtual = $_POST['LogoAtual'] ?? '';
        $upload = $_FILES['LogoProjeto'] ?? null;

        $resultadoLogo = processarUploadLogo($upload, $logoAtual);
        if (is_array($resultadoLogo) && isset($resultadoLogo['erro'])) {
            header("Location: {$PROJETOS_ROUTE}/?erro=" . urlencode($resultadoLogo['erro']));
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
                header("Location: {$PROJETOS_ROUTE}/?id=$id&msg=" . urlencode("Projeto atualizado com sucesso!"));
                exit;
            }
            header("Location: {$PROJETOS_ROUTE}/?id=$id&erro=" . urlencode("Erro ao atualizar projeto."));
            exit;
        }

        if (ProjetoModel::create($dados)) {
            header("Location: {$PROJETOS_ROUTE}/?msg=" . urlencode("Projeto cadastrado com sucesso!"));
            exit;
        }

        header("Location: {$PROJETOS_ROUTE}/?erro=" . urlencode("Erro ao cadastrar projeto."));
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

require __DIR__ . '/formProjeto.php';
exit;





