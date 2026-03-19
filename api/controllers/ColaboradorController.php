<?php
declare(strict_types=1);

namespace BackEnd\Controllers;

use BackEnd\Core\Response;
use BackEnd\Services\ColaboradorService;

final class ColaboradorController
{
    public function __construct(private readonly ColaboradorService $service)
    {
    }

    public function profissionaisSaude(): void
    {
        Response::json([
            'success' => true,
            'message' => 'Profissionais carregados',
            'data' => $this->service->listProfissionaisSaude($_GET),
            'errors' => [],
        ]);
    }

    public function show(string $id): void
    {
        $colaborador = $this->service->findById((int)$id);
        if ($colaborador === null) {
            Response::json([
                'success' => false,
                'message' => 'Colaborador não encontrado',
                'data' => (object) [],
                'errors' => ['Registro inexistente'],
            ], 404);
        }

        Response::json([
            'success' => true,
            'message' => 'Colaborador carregado',
            'data' => $colaborador,
            'errors' => [],
        ]);
    }

    public function publicName(string $id): void
    {
        $colaborador = $this->service->findPublicNameById((int)$id);
        if ($colaborador === null) {
            Response::json([
                'success' => false,
                'message' => 'Colaborador nao encontrado',
                'data' => (object) [],
                'errors' => ['Registro inexistente'],
            ], 404);
        }

        Response::json([
            'success' => true,
            'message' => 'Nome publico do colaborador carregado',
            'data' => $colaborador,
            'errors' => [],
        ]);
    }
}
