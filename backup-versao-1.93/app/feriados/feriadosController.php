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

require_once 'feriadosModel.php';
require_once 'BrasilApiService.php';

function normalizarDataFeriado($valor)
{
    $valor = trim((string) $valor);
    if ($valor === '') {
        return null;
    }

    if (preg_match('/^\d{2}\/\d{2}$/', $valor)) {
        return $valor;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
        $data = DateTime::createFromFormat('Y-m-d', $valor);
        return $data ? $data->format('d/m') : null;
    }

    return null;
}

$acao = $_POST['acao'] ?? $_GET['acao'] ?? null;
$idColaborador = $_SESSION['Cod'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'verificar') {
    header('Content-Type: application/json; charset=utf-8');

    $datas = $_POST['datas'] ?? [];
    if (is_string($datas)) {
        $decoded = json_decode($datas, true);
        if (is_array($decoded)) {
            $datas = $decoded;
        }
    }
    if (!is_array($datas)) {
        $datas = [];
    }

    $datas = array_values(array_unique(array_filter($datas, function ($data) {
        return is_string($data) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data);
    })));

    $diasMes = [];
    $anos = [];
    foreach ($datas as $data) {
        $dataObj = DateTime::createFromFormat('Y-m-d', $data);
        if ($dataObj) {
            $diasMes[] = $dataObj->format('d/m');
            $anos[$dataObj->format('Y')] = true;
        }
    }

    $feriadosLocais = FeriadosModel::getByDiasMes(array_values(array_unique($diasMes)));
    $mapLocal = [];
    foreach ($feriadosLocais as $feriado) {
        $diaMes = $feriado['DataFeriado'] ?? '';
        if ($diaMes !== '') {
            $mapLocal[$diaMes] = $feriado['Nome'] ?? 'Feriado';
        }
    }

    $mapApi = [];
    foreach (array_keys($anos) as $ano) {
        try {
            $feriadosApi = BrasilApiService::buscarFeriadosNacionais((int) $ano);
        } catch (Exception $e) {
            $feriadosApi = [];
        }

        foreach ($feriadosApi as $feriado) {
            $dataApi = $feriado['data'] ?? null;
            if ($dataApi && in_array($dataApi, $datas, true)) {
                $mapApi[$dataApi] = $feriado['nome'] ?? 'Feriado';
            }
        }
    }

    $resultado = [];
    foreach ($datas as $data) {
        $dataObj = DateTime::createFromFormat('Y-m-d', $data);
        $diaMes = $dataObj ? $dataObj->format('d/m') : '';

        if ($diaMes !== '' && isset($mapLocal[$diaMes])) {
            $resultado[$data] = [
                'feriado' => true,
                'nome' => $mapLocal[$diaMes],
                'fonte' => 'local'
            ];
        } elseif (isset($mapApi[$data])) {
            $resultado[$data] = [
                'feriado' => true,
                'nome' => $mapApi[$data],
                'fonte' => 'api'
            ];
        } else {
            $resultado[$data] = [
                'feriado' => false,
                'nome' => null,
                'fonte' => null
            ];
        }
    }

    echo json_encode(['success' => true, 'data' => $resultado], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($acao === 'excluir') {
        $id = intval($_POST['IdFeriado'] ?? 0);
        $registro = $id ? FeriadosModel::getById($id) : null;

        if ($id && $registro && intval($registro['IdColaborador'] ?? 0) === intval($idColaborador) && FeriadosModel::delete($id)) {
            header("Location: feriadosController.php?msg=" . urlencode("Feriado excluido com sucesso!"));
            exit;
        }

        header("Location: feriadosController.php?erro=" . urlencode("Erro ao excluir feriado."));
        exit;
    }

    if ($acao === 'salvar') {
        $id = intval($_POST['IdFeriado'] ?? 0);
        $nome = trim((string) ($_POST['Nome'] ?? ''));
        $dataFeriado = normalizarDataFeriado($_POST['DataFeriado'] ?? '');

        if ($nome === '') {
            header("Location: feriadosController.php?erro=" . urlencode("Informe o nome do feriado."));
            exit;
        }

        if (!$dataFeriado) {
            header("Location: feriadosController.php?erro=" . urlencode("Informe a data no formato dd/mm."));
            exit;
        }

        if ($idColaborador <= 0) {
            header("Location: feriadosController.php?erro=" . urlencode("Usuario invalido."));
            exit;
        }

        if (FeriadosModel::existsByNome($nome, $id)) {
            header("Location: feriadosController.php?erro=" . urlencode("Ja existe um feriado com este nome."));
            exit;
        }

        $dados = [
            'IdFeriado' => $id,
            'Nome' => $nome,
            'DataFeriado' => $dataFeriado,
            'IdColaborador' => $idColaborador
        ];

        if ($id > 0) {
            $registro = FeriadosModel::getById($id);
            if (!$registro || intval($registro['IdColaborador'] ?? 0) !== intval($idColaborador)) {
                header("Location: feriadosController.php?erro=" . urlencode("Feriado nao encontrado para edicao."));
                exit;
            }

            if (FeriadosModel::update($dados)) {
                header("Location: feriadosController.php?msg=" . urlencode("Feriado atualizado com sucesso!"));
                exit;
            }

            header("Location: feriadosController.php?erro=" . urlencode("Erro ao atualizar feriado."));
            exit;
        }

        if (FeriadosModel::create($dados)) {
            header("Location: feriadosController.php?msg=" . urlencode("Feriado cadastrado com sucesso!"));
            exit;
        }

        header("Location: feriadosController.php?erro=" . urlencode("Erro ao cadastrar feriado."));
        exit;
    }
}

if (isset($_GET['msg'])) $_POST['msg'] = urldecode($_GET['msg']);
if (isset($_GET['erro'])) $_POST['erro'] = urldecode($_GET['erro']);

$idEdicao = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($idEdicao > 0) {
    $registro = FeriadosModel::getById($idEdicao);
    if ($registro && intval($registro['IdColaborador'] ?? 0) === intval($idColaborador)) {
        foreach ($registro as $key => $value) {
            $_POST[$key] = $value;
        }
    }
}

$listaFeriados = FeriadosModel::listAll();

require 'formFeriados.php';
exit;
