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

    public function findByIds(array $ids): array
    {
        $idsFiltrados = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn (int $id): bool => $id > 0
        )));

        if ($idsFiltrados === []) {
            return [];
        }

        return $this->repository->findByIds($idsFiltrados);
    }

    public function search(array $query): array
    {
        $search = trim((string)($query['search'] ?? ''));
        $page = max(1, (int)($query['page'] ?? 1));
        $perPage = max(1, min(100, (int)($query['per_page'] ?? 15)));
        $habilitado = $this->nullableInt($query['habilitado'] ?? 1);

        return $this->repository->search($search, $page, $perPage, $habilitado);
    }

    public function interessesTexto(int $idUsuario): string
    {
        return $this->repository->interessesTexto($idUsuario);
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int)$value;
    }
}
