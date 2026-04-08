<?php
declare(strict_types=1);

namespace BackEnd\Controllers;

use BackEnd\Core\Response;
use BackEnd\Services\AuditLogService;
use BackEnd\Services\PlanoCursoAulaTodoService;
use InvalidArgumentException;
use Throwable;

final class PlanoCursoAulaTodoController
{
    private AuditLogService $audit;

    public function __construct(private readonly PlanoCursoAulaTodoService $service, ?AuditLogService $audit = null)
    {
        $this->audit = $audit ?? new AuditLogService();
    }

    public function listByAula(string $idPlanoCursoAula): void
    {
        try {
            $data = $this->service->listByAula((int) $idPlanoCursoAula);
            Response::json([
                'success' => true,
                'message' => 'Topicos da aula carregados',
                'data' => $data,
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao carregar topicos da aula', 'list_by_aula', ['id_aula' => (int) $idPlanoCursoAula]);
        }
    }

    public function store(string $idPlanoCursoAula): void
    {
        try {
            $id = $this->service->create((int) $idPlanoCursoAula, $this->requestPayload(), (int) ($_SESSION['Cod'] ?? 0));
            $this->audit->action('plano_curso_aula_todo', 'store', ['id_aula' => (int) $idPlanoCursoAula, 'id_item' => $id]);
            Response::json([
                'success' => true,
                'message' => 'Topico cadastrado com sucesso',
                'data' => ['id' => $id],
                'errors' => [],
            ], 201);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao cadastrar topico', 'store', ['id_aula' => (int) $idPlanoCursoAula]);
        }
    }

    public function update(string $idPlanoCursoAulaTodo): void
    {
        try {
            $updated = $this->service->update((int) $idPlanoCursoAulaTodo, $this->requestPayload(), (int) ($_SESSION['Cod'] ?? 0));
            $this->audit->action('plano_curso_aula_todo', 'update', ['id_item' => (int) $idPlanoCursoAulaTodo, 'updated' => $updated]);
            Response::json([
                'success' => true,
                'message' => $updated ? 'Topico atualizado com sucesso' : 'Topico sem alteracao',
                'data' => ['updated' => $updated],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao atualizar topico', 'update', ['id_item' => (int) $idPlanoCursoAulaTodo]);
        }
    }

    public function updateStatus(string $idPlanoCursoAulaTodo): void
    {
        try {
            $updated = $this->service->updateStatus((int) $idPlanoCursoAulaTodo, $this->requestPayload(), (int) ($_SESSION['Cod'] ?? 0));
            $this->audit->action('plano_curso_aula_todo', 'update_status', ['id_item' => (int) $idPlanoCursoAulaTodo, 'updated' => $updated]);
            Response::json([
                'success' => true,
                'message' => $updated ? 'Status do topico atualizado com sucesso' : 'Topico sem alteracao',
                'data' => ['updated' => $updated],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao atualizar status do topico', 'update_status', ['id_item' => (int) $idPlanoCursoAulaTodo]);
        }
    }

    public function reorder(string $idPlanoCursoAula): void
    {
        try {
            $result = $this->service->reorder((int) $idPlanoCursoAula, $this->requestPayload(), (int) ($_SESSION['Cod'] ?? 0));
            $this->audit->action('plano_curso_aula_todo', 'reorder', ['id_aula' => (int) $idPlanoCursoAula, 'result' => $result]);
            Response::json([
                'success' => true,
                'message' => 'Ordem dos topicos atualizada com sucesso',
                'data' => $result,
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao reordenar topicos', 'reorder', ['id_aula' => (int) $idPlanoCursoAula]);
        }
    }

    public function destroy(string $idPlanoCursoAulaTodo): void
    {
        try {
            $deleted = $this->service->delete((int) $idPlanoCursoAulaTodo, (int) ($_SESSION['Cod'] ?? 0));
            $this->audit->action('plano_curso_aula_todo', 'destroy', ['id_item' => (int) $idPlanoCursoAulaTodo, 'deleted' => $deleted]);
            Response::json([
                'success' => true,
                'message' => $deleted ? 'Topico excluido com sucesso' : 'Topico sem alteracao',
                'data' => ['deleted' => $deleted],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao excluir topico', 'destroy', ['id_item' => (int) $idPlanoCursoAulaTodo]);
        }
    }

    private function handleException(Throwable $e, string $publicMessage, string $action, array $context = []): void
    {
        if ($e instanceof InvalidArgumentException) {
            Response::json([
                'success' => false,
                'message' => $publicMessage,
                'data' => (object) [],
                'errors' => [$e->getMessage()],
            ], 422);
        }

        $traceId = $this->audit->error('plano_curso_aula_todo', $action, $e, $context);
        Response::json([
            'success' => false,
            'message' => $publicMessage,
            'data' => (object) [],
            'errors' => ['Erro interno. Referencia: ' . $traceId],
        ], 500);
    }

    private function requestPayload(): array
    {
        $raw = file_get_contents('php://input');
        $json = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($json)) {
            return $json;
        }

        return $_POST ?? [];
    }
}
