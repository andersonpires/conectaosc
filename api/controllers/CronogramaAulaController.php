<?php
declare(strict_types=1);

namespace BackEnd\Controllers;

use BackEnd\Core\Response;
use BackEnd\Services\AuditLogService;
use BackEnd\Services\CronogramaAulaService;
use InvalidArgumentException;
use Throwable;

final class CronogramaAulaController
{
    private AuditLogService $audit;

    public function __construct(private readonly CronogramaAulaService $service, ?AuditLogService $audit = null)
    {
        $this->audit = $audit ?? new AuditLogService();
    }

    public function agendar(string $id): void
    {
        try {
            $result = $this->service->agendar((int) $id, $this->requestPayload());
            $this->audit->action('cronograma_aula', 'agendar', ['id' => (int) $id, 'data' => $result['DataAula'] ?? null, 'hora' => $result['HoraInicio'] ?? null]);
            Response::json([
                'success' => true,
                'message' => 'Aula agendada com sucesso',
                'data' => $result,
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao agendar aula', 'agendar', ['id' => (int) $id]);
        }
    }

    public function status(string $id): void
    {
        try {
            $updated = $this->service->atualizarStatus((int) $id, $this->requestPayload());
            $this->audit->action('cronograma_aula', 'status', ['id' => (int) $id, 'updated' => $updated]);
            Response::json([
                'success' => true,
                'message' => $updated ? 'Status atualizado com sucesso' : 'Status sem alteração',
                'data' => ['updated' => $updated],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao atualizar status da aula', 'status', ['id' => (int) $id]);
        }
    }

    public function comentarios(string $id): void
    {
        try {
            $data = $this->service->listarComentarios((int) $id);
            Response::json([
                'success' => true,
                'message' => 'Comentários carregados',
                'data' => $data,
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao carregar comentários', 'comentarios', ['id' => (int) $id]);
        }
    }

    public function comentar(string $id): void
    {
        try {
            $newId = $this->service->comentar((int) $id, (int) ($_SESSION['Cod'] ?? 0), $this->requestPayload());
            $this->audit->action('cronograma_aula', 'comentar', ['id' => (int) $id, 'id_comentario' => $newId]);
            Response::json([
                'success' => true,
                'message' => 'Comentário registrado com sucesso',
                'data' => ['id' => $newId],
                'errors' => [],
            ], 201);
        } catch (Throwable $e) {
            $this->handleException($e, 'Falha ao registrar comentário', 'comentar', ['id' => (int) $id]);
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
        }

        $traceId = $this->audit->error('cronograma_aula', $action, $e, $context);
        Response::json([
            'success' => false,
            'message' => $publicMessage,
            'data' => (object) [],
            'errors' => ['Erro interno. Referência: ' . $traceId],
        ], 500);
    }
}

