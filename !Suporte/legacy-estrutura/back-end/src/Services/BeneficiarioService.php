<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\BeneficiarioRepository;

final class BeneficiarioService
{
    public function __construct(private readonly BeneficiarioRepository $repository)
    {
    }

    public function listResumoAtivos(): array
    {
        return $this->repository->listResumoAtivos();
    }

    public function listDetalhadoAtivos(): array
    {
        return $this->repository->listDetalhadoAtivos();
    }

    public function softDelete(int $idUsuario): bool
    {
        return $this->repository->softDelete($idUsuario);
    }

    public function findById(int $idUsuario): ?array
    {
        return $this->repository->findById($idUsuario);
    }

    public function interessesTexto(int $idUsuario): string
    {
        return $this->repository->interessesTexto($idUsuario);
    }
}
