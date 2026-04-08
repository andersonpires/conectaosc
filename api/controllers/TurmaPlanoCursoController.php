<?php
declare(strict_types=1);

namespace BackEnd\Controllers;

use BackEnd\Core\Response;
use BackEnd\Services\AuditLogService;
use BackEnd\Services\TurmaPlanoCursoService;
use InvalidArgumentException;
use Throwable;

final class TurmaPlanoCursoController
{
    private AuditLogService $audit;

    public function __construct(private readonly TurmaPlanoCursoService $service, ?AuditLogService $audit = null)
    {
        $this->audit = $audit ?? new AuditLogService();
    }

    public function show(string $idTurma): void
    {
        try {
            $vinculo = $this->service->showByTurma((int) $idTurma);
            Response::json([
                'success' => true,
                'message' => 'Plano da turma carregado',
                'data' => $vinculo ?: (object) [],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao carregar plano da turma', 'show', ['id_turma' => (int) $idTurma]);
        }
    }

    public function vincular(string $idTurma): void
    {
        try {
            $payload = $this->requestPayload();
            $idPlanoCurso = (int) ($payload['IdPlanoCurso'] ?? 0);
            $id = $this->service->vincular((int) $idTurma, $idPlanoCurso, (int) ($_SESSION['Cod'] ?? 0));
            $this->audit->action('turma_plano_curso', 'vincular', [
                'id_turma' => (int) $idTurma,
                'id_plano' => $idPlanoCurso,
                'id_vinculo' => $id,
            ]);
            Response::json([
                'success' => true,
                'message' => 'Plano vinculado à turma com sucesso',
                'data' => ['id' => $id],
                'errors' => [],
            ], 201);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao vincular plano à turma', 'vincular', ['id_turma' => (int) $idTurma]);
        }
    }

    public function cronograma(string $idTurma): void
    {
        try {
            $data = $this->service->listCronogramaByTurma((int) $idTurma);
            Response::json([
                'success' => true,
                'message' => 'Cronograma da turma carregado',
                'data' => $data,
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao carregar cronograma da turma', 'cronograma', ['id_turma' => (int) $idTurma]);
        }
    }

    public function desvincular(string $idTurma): void
    {
        try {
            $ok = $this->service->desvincular((int) $idTurma);
            $this->audit->action('turma_plano_curso', 'desvincular', [
                'id_turma' => (int) $idTurma,
                'desvinculado' => $ok,
            ]);
            Response::json([
                'success' => true,
                'message' => $ok ? 'Plano desvinculado da turma com sucesso' : 'Turma sem vínculo ativo de plano',
                'data' => ['desvinculado' => $ok],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao desvincular plano da turma', 'desvincular', ['id_turma' => (int) $idTurma]);
        }
    }

    public function listByCurso(string $idCurso): void
    {
        try {
            $data = $this->service->listTurmasComPlanoByCurso((int) $idCurso);
            Response::json([
                'success' => true,
                'message' => 'Turmas do curso carregadas',
                'data' => $data,
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao carregar turmas do curso', 'list_by_curso', ['id_curso' => (int) $idCurso]);
        }
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

    private function handleException(Throwable $e, string $publicMessage, string $action, array $context = []): void
    {
        if ($e instanceof InvalidArgumentException) {
            Response::json([
                'success' => false,
                'message' => $publicMessage,
                'data' => (object) [],
                'errors' => [$e->getMessage()],
            ], 422);
            return;
        }

        $traceId = $this->audit->error('turma_plano_curso', $action, $e, $context);
        Response::json([
            'success' => false,
            'message' => $publicMessage,
            'data' => (object) [],
            'errors' => ['Erro interno. Referência: ' . $traceId],
        ], 500);
    }
}
