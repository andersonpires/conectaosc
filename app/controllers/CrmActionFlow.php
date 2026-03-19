<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

use BackEnd\Repositories\CrmRepository;
use BackEnd\Services\CrmService;
use Throwable;

final class CrmActionFlow
{
    public function __construct(
        private readonly string $basePath,
        private readonly string $baseUrl
    ) {
    }

    public function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        require_once $this->basePath . '/api/repositories/CrmRepository.php';
        require_once $this->basePath . '/api/services/CrmService.php';

        $service = new CrmService(new CrmRepository());
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'listarTarefas':
                try {
                    echo json_encode(['status' => 'ok', 'tarefas' => $service->listarTarefasComCor()], JSON_UNESCAPED_UNICODE);
                } catch (Throwable $e) {
                    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                }
                break;

            case 'marcarFeito':
                $id = (int) ($_POST['id'] ?? 0);
                echo json_encode($id > 0 && $service->marcarFeito($id)
                    ? ['status' => 'ok']
                    : ['status' => 'erro', 'mensagem' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
                break;

            case 'excluirTarefa':
                $id = (int) ($_POST['id'] ?? 0);
                echo json_encode($id > 0 && $service->excluirTarefa($id)
                    ? ['status' => 'ok']
                    : ['status' => 'erro', 'mensagem' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
                break;

            case 'buscarAlunosFaltosos':
                echo json_encode(['status' => 'ok', 'alunos' => $service->buscarAlunosFaltosos($_POST, true)], JSON_UNESCAPED_UNICODE);
                break;

            case 'dadosAluno':
                $idUsuario = (int) ($_POST['idUsuario'] ?? 0);
                if ($idUsuario <= 0) {
                    echo json_encode(['status' => 'erro', 'mensagem' => 'ID do aluno não fornecido'], JSON_UNESCAPED_UNICODE);
                    break;
                }
                $aluno = $service->dadosAluno($idUsuario, true);
                echo json_encode($aluno
                    ? ['status' => 'ok', 'aluno' => $aluno]
                    : ['status' => 'erro', 'mensagem' => 'Aluno não encontrado'], JSON_UNESCAPED_UNICODE);
                break;

            case 'listarNotasAluno':
                $idUsuario = (int) ($_POST['idUsuario'] ?? 0);
                if ($idUsuario <= 0) {
                    echo json_encode(['status' => 'erro', 'mensagem' => 'ID do aluno inválido'], JSON_UNESCAPED_UNICODE);
                    break;
                }
                echo json_encode(['status' => 'ok', 'notas' => $service->listarNotasAluno($idUsuario)], JSON_UNESCAPED_UNICODE);
                break;

            case 'uploadImagemNota':
                $acceptedOrigins = [
                    'http://localhost',
                    'http://127.0.0.1',
                    'http://localhost:8080',
                    $_SERVER['HTTP_ORIGIN'] ?? '',
                ];
                if (isset($_SERVER['HTTP_ORIGIN']) && !in_array($_SERVER['HTTP_ORIGIN'], $acceptedOrigins, true)) {
                    header('HTTP/1.1 403 Origin Denied');
                    exit;
                }

                $result = $service->salvarImagemNota(
                    $_FILES['file'] ?? [],
                    $this->basePath !== '' ? $this->basePath : dirname(__DIR__, 2),
                    $this->baseUrl
                );

                if (!$result['ok']) {
                    http_response_code((int) $result['statusCode']);
                    echo json_encode(['error' => $result['error']], JSON_UNESCAPED_UNICODE);
                    break;
                }

                if (ob_get_length()) {
                    ob_end_clean();
                }
                echo json_encode(['location' => $result['location']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                break;

            case 'salvarNota':
                $idUsuario = (int) ($_POST['idUsuario'] ?? 0);
                $idColaborador = (int) ($_SESSION['Cod'] ?? 0);
                $textoNota = trim((string) ($_POST['textoNota'] ?? ''));

                if ($idUsuario <= 0 || $idColaborador <= 0 || $textoNota === '') {
                    echo json_encode(['status' => 'erro', 'mensagem' => 'Preencha os campos obrigatórios'], JSON_UNESCAPED_UNICODE);
                    break;
                }

                $salvo = $service->salvarNota($idUsuario, $idColaborador, $textoNota);
                $mensagemNotificacao = '';
                if ($salvo['ok'] && !empty($salvo['nomeAluno'])) {
                    require_once $this->basePath . '/app/views/crm/notificarUsuario.php';
                    $mensagemNotificacao = notificarUsuario(null, $idColaborador, 'nota', (string) $salvo['nomeAluno']);
                }

                echo json_encode(['status' => $salvo['ok'] ? 'ok' : 'erro', 'mensagem' => $mensagemNotificacao], JSON_UNESCAPED_UNICODE);
                break;

            case 'salvarTarefa':
                $idUsuario = (int) ($_POST['idUsuario'] ?? 0);
                $idColaborador = (int) ($_SESSION['Cod'] ?? 0);
                $descricaoTarefa = trim((string) ($_POST['descricaoTarefa'] ?? ''));
                $dataExecucao = trim((string) ($_POST['dataExecucao'] ?? ''));

                if ($idUsuario <= 0 || $idColaborador <= 0 || $descricaoTarefa === '' || $dataExecucao === '') {
                    echo json_encode(['status' => 'erro', 'mensagem' => 'Preencha todos os campos obrigatórios.'], JSON_UNESCAPED_UNICODE);
                    break;
                }

                $salvo = $service->salvarTarefa($idUsuario, $idColaborador, $descricaoTarefa, $dataExecucao);
                echo json_encode(['status' => $salvo['ok'] ? 'ok' : 'erro', 'mensagem' => ''], JSON_UNESCAPED_UNICODE);
                break;

            case 'dadosColaborador':
                $id = (int) ($_SESSION['Cod'] ?? 0);
                if ($id <= 0) {
                    echo json_encode(['status' => 'erro', 'mensagem' => 'Usuário não autenticado'], JSON_UNESCAPED_UNICODE);
                    break;
                }
                echo json_encode(['status' => 'ok', 'colaborador' => $service->dadosColaborador($id)], JSON_UNESCAPED_UNICODE);
                break;

            case 'getPreferenciasNotificacao':
                $id = (int) ($_SESSION['Cod'] ?? 0);
                if ($id <= 0) {
                    echo json_encode(['status' => 'erro', 'mensagem' => 'Colaborador não identificado'], JSON_UNESCAPED_UNICODE);
                    break;
                }
                echo json_encode(['status' => 'ok', 'preferencias' => $service->getPreferenciasNotificacao($id)], JSON_UNESCAPED_UNICODE);
                break;

            case 'salvarPreferenciasNotificacao':
                $id = (int) ($_SESSION['Cod'] ?? 0);
                if ($id <= 0) {
                    echo json_encode(['status' => 'erro', 'mensagem' => 'Colaborador não identificado'], JSON_UNESCAPED_UNICODE);
                    break;
                }

                $ok = $service->salvarPreferenciasNotificacao(
                    $id,
                    (int) ($_POST['nota_email'] ?? 0),
                    (int) ($_POST['nota_whatsapp'] ?? 0),
                    (int) ($_POST['tarefa_email'] ?? 0),
                    (int) ($_POST['tarefa_whatsapp'] ?? 0)
                );
                echo json_encode(['status' => $ok ? 'ok' : 'erro'], JSON_UNESCAPED_UNICODE);
                break;

            default:
                echo json_encode(['status' => 'erro', 'mensagem' => 'Ação inválida'], JSON_UNESCAPED_UNICODE);
                break;
        }
    }
}

