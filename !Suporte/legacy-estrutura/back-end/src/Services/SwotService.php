<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\SwotRepository;

final class SwotService
{
    public function __construct(private readonly SwotRepository $repository)
    {
    }

    public function listUsuariosAtivos(): array
    {
        return $this->repository->listUsuariosAtivos();
    }

    public function listByUser(int $idColaborador): array
    {
        return $this->repository->listByUser($idColaborador);
    }

    public function listForReport(?string $tema = null, mixed $idColaborador = null): array
    {
        return $this->repository->listForReport($tema, $idColaborador);
    }

    public function getById(int $id): ?array
    {
        return $this->repository->getById($id);
    }

    public function create(array $dados): bool
    {
        return $this->repository->create($dados);
    }

    public function update(array $dados): bool
    {
        return $this->repository->update($dados);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
