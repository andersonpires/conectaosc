<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_PATH = $runtime['base_path'];
$BASE_URL = $runtime['base_url'];
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

require_once $BASE_PATH . '/api/legacy/checa-token.php';
require_once __DIR__ . '/permissaoModel.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'listar') {
            $permissoes = PermissaoModel::listarPermissoes();
            $dados = array_map(static function (array $item): array {
                return [
                    'id' => $item['IdPermissao'],
                    'nome' => $item['NomePermissao'],
                    'descricao' => $item['Descricao'],
                ];
            }, $permissoes);

            echo json_encode(['status' => 'ok', 'dados' => $dados], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'carregarPermissao') {
            $id = (int) ($_POST['idPermissao'] ?? 0);
            $dados = PermissaoModel::getPermissao($id);
            $paginas = PermissaoModel::getPermissaoPaginas($id);
            $horarios = PermissaoModel::getPermissaoHorarios($id);
            $horariosSelecionados = array_map(
                static fn(array $item): string => $item['DiaSemana'] . '_' . $item['Hora'],
                $horarios
            );

            echo json_encode([
                'status' => 'ok',
                'nome' => $dados['NomePermissao'] ?? '',
                'descricao' => $dados['Descricao'] ?? '',
                'paginas' => $paginas,
                'horarios' => $horariosSelecionados,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'salvarPermissao') {
            $id = $_POST['idPermissao'] ?? '';
            $nome = trim((string) ($_POST['nomePermissao'] ?? ''));
            $descricao = trim((string) ($_POST['descricao'] ?? ''));
            $paginas = $_POST['paginas'] ?? [];

            if ($nome === '' || $descricao === '') {
                echo json_encode(['status' => 'erro', 'msg' => 'Nome e descri?f?????T?f??s?,??f?????T?f??s?,?o s?f?????T?f??s?,?o obrigat?f?????T?f??s?,?rios.'], JSON_UNESCAPED_UNICODE);
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
                $id !== '' ? (int) $id : null
            );

            echo json_encode(['status' => 'ok', 'msg' => 'Permiss?f?????T?f??s?,?o salva com sucesso!'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'excluirPermissao') {
            $id = (int) ($_POST['idPermissao'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'erro', 'msg' => 'ID de permiss?f?????T?f??s?,?o inv?f?????T?f??s?,?lido.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $ok = PermissaoModel::excluirPermissao($id);
            if ($ok) {
                echo json_encode(['status' => 'ok', 'msg' => 'Permiss?f?????T?f??s?,?o exclu?f?????T?f??s?,?da com sucesso!'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['status' => 'erro', 'msg' => 'N?f?????T?f??s?,?o ?f?????T?f??s?,? poss?f?????T?f??s?,?vel excluir. Existem usu?f?????T?f??s?,?rios vinculados.'], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }

        echo json_encode(['status' => 'erro', 'msg' => 'A?f?????T?f??s?,??f?????T?f??s?,?o inv?f?????T?f??s?,?lida.'], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        echo json_encode(['status' => 'erro', 'msg' => 'Erro ao processar a solicita?f?????T?f??s?,??f?????T?f??s?,?o.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$view = $_GET['view'] ?? 'list';

if ($view === 'form') {
    $paginas = PermissaoModel::listarPaginas();
    $grupos = [];
    foreach ($paginas as $pagina) {
        $tipoApp = $pagina['TipoApp'] ?: 'Outros';
        $grupos[$tipoApp][] = $pagina;
    }
    ksort($grupos);
    if (isset($grupos['Outros'])) {
        $outros = $grupos['Outros'];
        unset($grupos['Outros']);
        $grupos['Outros'] = $outros;
    }

    require __DIR__ . '/formPermissaoView.php';
    exit;
}

require __DIR__ . '/listPermissaoView.php';
exit;





