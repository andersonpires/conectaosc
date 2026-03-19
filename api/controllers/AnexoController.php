<?php
declare(strict_types=1);

namespace BackEnd\Controllers;

use BackEnd\Core\Response;
use BackEnd\Services\AnexoService;
use BackEnd\Services\AuditLogService;
use InvalidArgumentException;
use Throwable;

final class AnexoController
{
    private AuditLogService $audit;

    public function __construct(private readonly AnexoService $service, ?AuditLogService $audit = null)
    {
        $this->audit = $audit ?? new AuditLogService();
    }

    public function listPlanoAula(string $idPlanoCursoAula): void
    {
        try {
            $data = $this->service->listPlanoAula((int) $idPlanoCursoAula);
            Response::json([
                'success' => true,
                'message' => 'Anexos da aula do plano carregados',
                'data' => $data,
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao carregar anexos da aula do plano', 'list_plano_aula', ['id_aula' => (int) $idPlanoCursoAula]);
        }
    }

    public function uploadPlanoAula(string $idPlanoCursoAula): void
    {
        try {
            $data = $this->service->uploadPlanoAula(
                (int) $idPlanoCursoAula,
                $_FILES,
                (int) ($_SESSION['Cod'] ?? 0),
                $_POST
            );
            $this->audit->action('anexo', 'upload_plano_aula', ['id_aula' => (int) $idPlanoCursoAula, 'total' => (int) ($data['total'] ?? 0)]);
            Response::json([
                'success' => true,
                'message' => 'Upload realizado com sucesso',
                'data' => $data,
                'errors' => [],
            ], 201);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha no upload de anexos da aula do plano', 'upload_plano_aula', ['id_aula' => (int) $idPlanoCursoAula]);
        }
    }

    public function listCronogramaAula(string $idCronogramaAula): void
    {
        try {
            $data = $this->service->listCronogramaAula((int) $idCronogramaAula);
            Response::json([
                'success' => true,
                'message' => 'Anexos do cronograma carregados',
                'data' => $data,
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao carregar anexos do cronograma', 'list_cronograma_aula', ['id_cronograma' => (int) $idCronogramaAula]);
        }
    }

    public function uploadCronogramaAula(string $idCronogramaAula): void
    {
        try {
            $data = $this->service->uploadCronogramaAula(
                (int) $idCronogramaAula,
                $_FILES,
                (int) ($_SESSION['Cod'] ?? 0)
            );
            $this->audit->action('anexo', 'upload_cronograma_aula', ['id_cronograma' => (int) $idCronogramaAula, 'total' => (int) ($data['total'] ?? 0)]);
            Response::json([
                'success' => true,
                'message' => 'Upload realizado com sucesso',
                'data' => $data,
                'errors' => [],
            ], 201);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha no upload de anexos do cronograma', 'upload_cronograma_aula', ['id_cronograma' => (int) $idCronogramaAula]);
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

        $traceId = $this->audit->error('anexo', $action, $e, $context);
        Response::json([
            'success' => false,
            'message' => $publicMessage,
            'data' => (object) [],
            'errors' => ['Erro interno. Referência: ' . $traceId],
        ], 500);
    }
}
