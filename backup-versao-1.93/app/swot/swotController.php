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

require_once 'swotModel.php';

$temasDisponiveis = [
    'INOVAÇÃO EM PROJETOS EXISTENTES',
    'NOVOS PROJETOS PARA INVESTIDORES',
    'NOVOS SERVIÇOS',
    'NOVOS PRODUTOS'
];

$temaPadrao = $temasDisponiveis[0];
$acao = $_POST['acao'] ?? null;
$idColaborador = $_SESSION['Cod'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($acao === 'excluir') {
        $id = intval($_POST['IdSwot'] ?? 0);
        $registro = $id ? SwotModel::getById($id) : null;

        if ($id && $registro && intval($registro['IdColaborador'] ?? 0) === intval($idColaborador) && SwotModel::delete($id)) {
            header("Location: swotController.php?msg=" . urlencode("Insight excluido com sucesso!"));
            exit;
        }

        header("Location: swotController.php?erro=" . urlencode("Erro ao excluir insight."));
        exit;
    }

    if ($acao === 'salvar') {
        $id = intval($_POST['IdSwot'] ?? 0);
        $tema = trim((string) ($_POST['Tema'] ?? $temaPadrao));
        if (!in_array($tema, $temasDisponiveis, true)) {
            $tema = $temaPadrao;
        }
        $texto = trim((string) ($_POST['Texto'] ?? ''));

        if ($texto === '') {
            header("Location: swotController.php?tema=" . urlencode($tema) . "&erro=" . urlencode("Informe o insight antes de enviar."));
            exit;
        }

        if ($idColaborador <= 0) {
            header("Location: swotController.php?tema=" . urlencode($tema) . "&erro=" . urlencode("Usuario invalido."));
            exit;
        }

        $dados = [
            'IdSwot' => $id,
            'Tema' => $tema,
            'Texto' => $texto,
            'IdColaborador' => $idColaborador
        ];

        if ($id > 0) {
            $registro = SwotModel::getById($id);
            if (!$registro || intval($registro['IdColaborador'] ?? 0) !== intval($idColaborador)) {
                header("Location: swotController.php?tema=" . urlencode($tema) . "&erro=" . urlencode("Insight nao encontrado para edicao."));
                exit;
            }

            if (SwotModel::update($dados)) {
                header("Location: swotController.php?tema=" . urlencode($tema) . "&msg=" . urlencode("Insight atualizado com sucesso!"));
                exit;
            }

            header("Location: swotController.php?tema=" . urlencode($tema) . "&erro=" . urlencode("Erro ao atualizar insight."));
            exit;
        }

        if (SwotModel::create($dados)) {
            header("Location: swotController.php?tema=" . urlencode($tema) . "&msg=" . urlencode("Insight cadastrado com sucesso!"));
            exit;
        }

        header("Location: swotController.php?tema=" . urlencode($tema) . "&erro=" . urlencode("Erro ao cadastrar insight."));
        exit;
    }
}

if (isset($_GET['msg'])) $_POST['msg'] = urldecode($_GET['msg']);
if (isset($_GET['erro'])) $_POST['erro'] = urldecode($_GET['erro']);
if (isset($_GET['tema'])) $_POST['Tema'] = urldecode($_GET['tema']);

$idEdicao = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($idEdicao > 0) {
    $registro = SwotModel::getById($idEdicao);
    if ($registro && intval($registro['IdColaborador'] ?? 0) === intval($idColaborador)) {
        foreach ($registro as $key => $value) {
            $_POST[$key] = $value;
        }
    }
}

$listaSwot = SwotModel::listByUser($idColaborador);

if (empty($_POST['Tema'])) {
    $_POST['Tema'] = $temaPadrao;
}

require 'formSwot.php';
exit;
