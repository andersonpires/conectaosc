<?php
declare(strict_types=1);

namespace BackEnd\Controllers;

use BackEnd\Core\Response;
use BackEnd\Services\AuditLogService;
use BackEnd\Services\PlanoCursoService;
use InvalidArgumentException;
use Throwable;

final class PlanoCursoController
{
    private AuditLogService $audit;

    public function __construct(private readonly PlanoCursoService $service, ?AuditLogService $audit = null)
    {
        $this->audit = $audit ?? new AuditLogService();
    }

    public function index(): void
    {
        $idCurso = isset($_GET['IdCurso']) ? (int) $_GET['IdCurso'] : 0;
        Response::json([
            'success' => true,
            'message' => 'Planos de curso carregados',
            'data' => $this->service->list($idCurso),
            'errors' => [],
        ]);
    }

    public function resumoCursos(): void
    {
        $estado = isset($_GET['Estado']) ? (string) $_GET['Estado'] : 'todos';
        Response::json([
            'success' => true,
            'message' => 'Resumo de plano por curso carregado',
            'data' => $this->service->resumoCursos($estado),
            'errors' => [],
        ]);
    }

    public function show(string $id): void
    {
        $plano = $this->service->show((int) $id);
        if (!$plano) {
            Response::json([
                'success' => false,
                'message' => 'Plano não encontrado',
                'data' => (object) [],
                'errors' => ['Plano não encontrado'],
            ], 404);
        }

        Response::json([
            'success' => true,
            'message' => 'Plano carregado',
            'data' => $plano,
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        try {
            $id = $this->service->create($this->requestPayload(), (int) ($_SESSION['Cod'] ?? 0));
            $this->audit->action('plano_curso', 'store', ['id' => $id]);
            Response::json([
                'success' => true,
                'message' => 'Plano cadastrado com sucesso',
                'data' => ['id' => $id],
                'errors' => [],
            ], 201);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao cadastrar plano', 'store');
        }
    }

    public function update(string $id): void
    {
        try {
            $updated = $this->service->update((int) $id, $this->requestPayload(), (int) ($_SESSION['Cod'] ?? 0));
            $this->audit->action('plano_curso', 'update', ['id' => (int) $id, 'updated' => $updated]);
            Response::json([
                'success' => true,
                'message' => $updated ? 'Plano atualizado com sucesso' : 'Plano sem alteração',
                'data' => ['updated' => $updated],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao atualizar plano', 'update', ['id' => (int) $id]);
        }
    }

    public function destroy(string $id): void
    {
        try {
            $payload = $this->requestPayload();
            $forcarExclusao = (bool) ($payload['ForcarExclusao'] ?? false);
            $deleted = $this->service->deletePermanente((int) $id, $forcarExclusao);
            $this->audit->action('plano_curso', 'destroy', ['id' => (int) $id, 'deleted' => $deleted, 'forcado' => $forcarExclusao]);
            Response::json([
                'success' => true,
                'message' => $deleted ? 'Plano excluído com sucesso' : 'Plano sem alteração',
                'data' => ['deleted' => $deleted, 'forcado' => $forcarExclusao],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao excluir plano', 'destroy', ['id' => (int) $id]);
        }
    }

    public function deleteImpact(string $id): void
    {
        try {
            $data = $this->service->deleteImpact((int) $id);
            Response::json([
                'success' => true,
                'message' => 'Impacto de exclusão calculado',
                'data' => $data,
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao calcular impacto de exclusão', 'delete_impact', ['id' => (int) $id]);
        }
    }

    public function duplicate(string $id): void
    {
        try {
            $novoId = $this->service->duplicate((int) $id, $this->requestPayload(), (int) ($_SESSION['Cod'] ?? 0));
            $this->audit->action('plano_curso', 'duplicate', ['id_origem' => (int) $id, 'id_novo' => $novoId]);
            Response::json([
                'success' => true,
                'message' => 'Plano duplicado com sucesso',
                'data' => ['id' => $novoId],
                'errors' => [],
            ], 201);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao duplicar plano', 'duplicate', ['id_origem' => (int) $id]);
        }
    }

    public function listAulas(string $idPlanoCurso): void
    {
        try {
            $data = $this->service->listAulas((int) $idPlanoCurso);
            Response::json([
                'success' => true,
                'message' => 'Aulas carregadas',
                'data' => $data,
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => 'Falha ao carregar aulas',
                'data' => [],
                'errors' => [$e->getMessage()],
            ], 422);
        }
    }

    public function storeAula(string $idPlanoCurso): void
    {
        try {
            $id = $this->service->createAula((int) $idPlanoCurso, $this->requestPayload());
            $this->audit->action('plano_curso_aula', 'store', ['id_plano' => (int) $idPlanoCurso, 'id_aula' => $id]);
            Response::json([
                'success' => true,
                'message' => 'Aula cadastrada com sucesso',
                'data' => ['id' => $id],
                'errors' => [],
            ], 201);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao cadastrar aula', 'store_aula', ['id_plano' => (int) $idPlanoCurso]);
        }
    }

    public function updateAula(string $idPlanoCursoAula): void
    {
        try {
            $updated = $this->service->updateAula((int) $idPlanoCursoAula, $this->requestPayload());
            $this->audit->action('plano_curso_aula', 'update', ['id_aula' => (int) $idPlanoCursoAula, 'updated' => $updated]);
            Response::json([
                'success' => true,
                'message' => $updated ? 'Aula atualizada com sucesso' : 'Aula sem alteração',
                'data' => ['updated' => $updated],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao atualizar aula', 'update_aula', ['id_aula' => (int) $idPlanoCursoAula]);
        }
    }

    public function destroyAula(string $idPlanoCursoAula): void
    {
        try {
            $deleted = $this->service->deleteAula((int) $idPlanoCursoAula);
            $this->audit->action('plano_curso_aula', 'destroy', ['id_aula' => (int) $idPlanoCursoAula, 'deleted' => $deleted]);
            Response::json([
                'success' => true,
                'message' => $deleted ? 'Aula excluída com sucesso' : 'Aula sem alteração',
                'data' => ['deleted' => $deleted],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao excluir aula', 'destroy_aula', ['id_aula' => (int) $idPlanoCursoAula]);
        }
    }

    public function reorderAulas(string $idPlanoCurso): void
    {
        try {
            $result = $this->service->reorderAulas((int) $idPlanoCurso, $this->requestPayload());
            $this->audit->action('plano_curso_aula', 'reorder', ['id_plano' => (int) $idPlanoCurso, 'result' => $result]);
            Response::json([
                'success' => true,
                'message' => 'Ordem das aulas atualizada com sucesso',
                'data' => $result,
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao reordenar aulas', 'reorder', ['id_plano' => (int) $idPlanoCurso]);
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

        $traceId = $this->audit->error('plano_curso', $action, $e, $context);
        Response::json([
            'success' => false,
            'message' => $publicMessage,
            'data' => (object) [],
            'errors' => ['Erro interno. Referência: ' . $traceId],
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
