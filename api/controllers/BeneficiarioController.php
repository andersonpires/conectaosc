<?php
declare(strict_types=1);

namespace BackEnd\Controllers;

use BackEnd\Core\Response;
use BackEnd\Services\BeneficiarioService;
use Throwable;

final class BeneficiarioController
{
    public function __construct(private readonly BeneficiarioService $service)
    {
    }

    public function resumoAtivos(): void
    {
        Response::json([
            'success' => true,
            'message' => 'Beneficiarios carregados',
            'data' => $this->service->listResumoAtivos(),
            'errors' => [],
        ]);
    }

    public function detalhadoAtivos(): void
    {
        Response::json([
            'success' => true,
            'message' => 'Beneficiarios detalhados carregados',
            'data' => $this->service->listDetalhadoAtivos(),
            'errors' => [],
        ]);
    }

    public function search(): void
    {
        Response::json([
            'success' => true,
            'message' => 'Beneficiarios carregados',
            'data' => $this->service->search($_GET),
            'errors' => [],
        ]);
    }

    public function show(string $id): void
    {
        $beneficiario = $this->service->findById((int)$id);
        if ($beneficiario === null) {
            Response::json([
                'success' => false,
                'message' => 'Beneficiario nao encontrado',
                'data' => (object)[],
                'errors' => ['Registro inexistente'],
            ], 404);
        }

        Response::json([
            'success' => true,
            'message' => 'Beneficiario carregado',
            'data' => $beneficiario,
            'errors' => [],
        ]);
    }

    public function destroy(string $id): void
    {
        try {
            $deleted = $this->service->softDelete((int)$id);
            Response::json([
                'success' => true,
                'message' => $deleted ? 'Beneficiario excluido com sucesso' : 'Beneficiario nao encontrado',
                'data' => ['deleted' => $deleted],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => 'Erro ao excluir beneficiario',
                'data' => (object)[],
                'errors' => [$e->getMessage()],
            ], 422);
        }
    }
}
