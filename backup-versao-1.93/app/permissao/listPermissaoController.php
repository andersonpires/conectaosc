<?php
// listPermissaoController.php
session_start();
require_once 'permissaoModel.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'erro', 'msg' => 'Requisição inválida. Apenas POST é permitido.']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'listar') {
    try {
        $permissoes = PermissaoModel::listarPermissoes();
        $dados = array_map(function ($item) {
            return [
                'id' => $item['IdPermissao'],
                'nome' => $item['NomePermissao'],
                'descricao' => $item['Descricao'],
            ];
        }, $permissoes);
        echo json_encode(['status' => 'ok', 'dados' => $dados]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'erro', 'msg' => 'Erro ao buscar permissões.']);
    }
} elseif ($action === 'carregarPermissao') {
    $id = $_POST['idPermissao'] ?? 0;
    try {
        $dados = PermissaoModel::getPermissao((int) $id);
        $paginas = PermissaoModel::getPermissaoPaginas((int) $id);
        $horarios = PermissaoModel::getPermissaoHorarios((int) $id);

        $horariosSelecionados = array_map(function ($item) {
            return $item['DiaSemana'] . '_' . $item['Hora'];
        }, $horarios);

        echo json_encode([
            'status' => 'ok',
            'nome' => $dados['NomePermissao'] ?? '',
            'descricao' => $dados['Descricao'] ?? '',
            'paginas' => $paginas,
            'horarios' => $horariosSelecionados
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'erro', 'msg' => 'Erro ao carregar permissão.']);
    }
} elseif ($action === 'salvarPermissao') {
    $id = $_POST['idPermissao'] ?? '';
    $nome = $_POST['nomePermissao'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $paginas = $_POST['paginas'] ?? [];

    if (trim($nome) === '') {
        echo json_encode(['status' => 'erro', 'msg' => 'Nome da permissão não pode ser vazio.']);
        exit;
    }

    try {
        if (empty($nome) || empty($descricao)) {
            echo json_encode(['status' => 'erro', 'msg' => 'Nome e descrição são obrigatórios.']);
            exit;
        }

        $todosHorariosDias = isset($_POST['todosHorariosDias']);
        $horariosSelecionados = $_POST['horariosSelecionados'] ?? [];

        PermissaoModel::salvarPermissao(
            $nome,
            $descricao,
            $paginas,
            $todosHorariosDias,
            $horariosSelecionados,
            $id ? (int) $id : null
        );

        echo json_encode(['status' => 'ok', 'msg' => 'Permissão salva com sucesso!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'erro', 'msg' => 'Erro ao salvar permissão.']);
    }
} elseif ($action === 'excluirPermissao') {
    $id = intval($_POST['idPermissao'] ?? 0);

    try {
        if ($id <= 0) {
            echo json_encode(['status' => 'erro', 'msg' => 'ID de permissão inválido.']);
            exit;
        }

        $ok = PermissaoModel::excluirPermissao($id);
        if ($ok) {
            echo json_encode(['status' => 'ok', 'msg' => 'Permissão excluída com sucesso!']);
        } else {
            echo json_encode(['status' => 'erro', 'msg' => 'Não é possível excluir. Existem usuários vinculados.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'erro', 'msg' => 'Erro ao excluir permissão.']);
    }
} else {
    echo json_encode(['status' => 'erro', 'msg' => 'Ação inválida.']);
}
