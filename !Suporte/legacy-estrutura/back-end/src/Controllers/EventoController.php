<?php
declare(strict_types=1);

namespace BackEnd\Controllers;

use BackEnd\Core\Response;
use BackEnd\Services\EventoService;
use Throwable;

final class EventoController
{
    public function __construct(private readonly EventoService $service)
    {
    }

    public function index(): void
    {
        Response::json([
            'success' => true,
            'message' => 'Inscritos carregados',
            'data' => $this->service->listInscritos(),
            'errors' => [],
        ]);
    }

    public function show(string $id): void
    {
        $inscrito = $this->service->findInscrito((int)$id);
        Response::json([
            'success' => true,
            'message' => $inscrito ? 'Inscrito carregado' : 'Inscrito nao encontrado',
            'data' => $inscrito ?? (object)[],
            'errors' => [],
        ], $inscrito ? 200 : 404);
    }

    public function emailExists(): void
    {
        $email = (string)($_GET['email'] ?? '');
        $ignoreId = isset($_GET['ignoreId']) ? (int)$_GET['ignoreId'] : null;
        Response::json([
            'success' => true,
            'message' => 'Consulta realizada',
            'data' => ['exists' => $this->service->emailExists($email, $ignoreId)],
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        try {
            $id = $this->service->create($_POST);
            Response::json([
                'success' => true,
                'message' => 'Inscricao cadastrada com sucesso',
                'data' => ['id' => $id],
                'errors' => [],
            ], 201);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => 'Falha ao cadastrar inscricao',
                'data' => (object)[],
                'errors' => [$e->getMessage()],
            ], 422);
        }
    }

    public function update(string $id): void
    {
        try {
            $updated = $this->service->update((int)$id, $_POST !== [] ? $_POST : $this->requestPayload());
            Response::json([
                'success' => true,
                'message' => $updated ? 'Inscricao atualizada com sucesso' : 'Inscricao sem alteracao',
                'data' => ['updated' => $updated],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => 'Falha ao atualizar inscricao',
                'data' => (object)[],
                'errors' => [$e->getMessage()],
            ], 422);
        }
    }

    public function destroy(string $id): void
    {
        try {
            $deleted = $this->service->delete((int)$id);
            Response::json([
                'success' => true,
                'message' => $deleted ? 'Inscricao removida com sucesso' : 'Inscricao nao encontrada',
                'data' => ['deleted' => $deleted],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => 'Falha ao excluir inscricao',
                'data' => (object)[],
                'errors' => [$e->getMessage()],
            ], 422);
        }
    }

    private function requestPayload(): array
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || $raw === '') {
            return [];
        }
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }
        parse_str($raw, $parsed);
        return is_array($parsed) ? $parsed : [];
    }
}

