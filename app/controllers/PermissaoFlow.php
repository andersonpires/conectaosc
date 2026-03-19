<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

use Throwable;

final class PermissaoFlow
{
    public function __construct(
        private readonly string $basePath,
        private readonly string $baseUrl
    ) {
    }

    public function handle(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.gc_maxlifetime', '86400');
        }

        require_once $this->basePath . '/api/legacy/checa-token.php';
        require_once $this->basePath . '/app/models/permissao/permissaoModel.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $this->handlePostJson();
            return;
        }

        $BASE_para_PATH = $this->basePath;
        $BASE_para_URL = $this->baseUrl;
        $view = $_GET['view'] ?? 'list';

        if ($view === 'form') {
            $paginas = \PermissaoModel::listarPaginas();
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

            require $this->basePath . '/app/views/permissao/formPermissaoView.php';
            exit;
        }

        require $this->basePath . '/app/views/permissao/listPermissaoView.php';
        exit;
    }

    private function handlePostJson(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $action = (string) ($_POST['action'] ?? '');

        try {
            if ($action === 'listar') {
                $permissoes = \PermissaoModel::listarPermissoes();
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
                $dados = \PermissaoModel::getPermissao($id);
                $paginas = \PermissaoModel::getPermissaoPaginas($id);
                $horarios = \PermissaoModel::getPermissaoHorarios($id);
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
                    echo json_encode(['status' => 'erro', 'msg' => 'Nome e Descrição são obrigatórios.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $todosHorariosDias = isset($_POST['todosHorariosDias']);
                $horariosSelecionados = $_POST['horariosSelecionados'] ?? [];

                \PermissaoModel::salvarPermissao(
                    $nome,
                    $descricao,
                    $paginas,
                    $todosHorariosDias,
                    $horariosSelecionados,
                    $id !== '' ? (int) $id : null
                );

                echo json_encode(['status' => 'ok', 'msg' => 'Permissão salva com sucesso!'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($action === 'excluirPermissao') {
                $id = (int) ($_POST['idPermissao'] ?? 0);
                if ($id <= 0) {
                    echo json_encode(['status' => 'erro', 'msg' => 'ID de Permissão inválido.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $ok = \PermissaoModel::excluirPermissao($id);
                if ($ok) {
                    echo json_encode(['status' => 'ok', 'msg' => 'Permissão excluída com sucesso!'], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['status' => 'erro', 'msg' => 'Não é possível excluir. Existem usuários vinculados.'], JSON_UNESCAPED_UNICODE);
                }
                exit;
            }

            echo json_encode(['status' => 'erro', 'msg' => 'Ação inválida.'], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable) {
            echo json_encode(['status' => 'erro', 'msg' => 'Erro ao processar a solicitação.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
