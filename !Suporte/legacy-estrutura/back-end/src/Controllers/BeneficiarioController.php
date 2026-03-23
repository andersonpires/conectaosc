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
            'message' => 'Beneficiários carregados',
            'data' => $this->service->listResumoAtivos(),
            'errors' => [],
        ]);
    }

    public function detalhadoAtivos(): void
    {
        Response::json([
            'success' => true,
            'message' => 'Beneficiários detalhados carregados',
            'data' => $this->service->listDetalhadoAtivos(),
            'errors' => [],
        ]);
    }

    public function destroy(string $id): void
    {
        try {
            $deleted = $this->service->softDelete((int)$id);
            Response::json([
                'success' => true,
                'message' => $deleted ? 'Beneficiário excluído com sucesso' : 'Beneficiário não encontrado',
                'data' => ['deleted' => $deleted],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => 'Erro ao excluir beneficiário',
                'data' => (object)[],
                'errors' => [$e->getMessage()],
            ], 422);
        }
    }
}
