<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\PaginaRepository;

final class PaginaService
{
    public function __construct(private readonly PaginaRepository $repository)
    {
    }

    public function listarPaginas(): array
    {
        return $this->repository->listarPaginas();
    }

    public function buscarPagina(int $id): ?array
    {
        return $this->repository->buscarPagina($id);
    }

    public function criarPagina(string $nome, string $descricao, string $tipoApp, string $arquivo): int
    {
        return $this->repository->criarPagina($nome, $descricao, $tipoApp, $arquivo);
    }

    public function atualizarPagina(int $id, string $nome, string $descricao, string $tipoApp, string $arquivo): bool
    {
        return $this->repository->atualizarPagina($id, $nome, $descricao, $tipoApp, $arquivo);
    }

    public function excluirPagina(int $id): bool
    {
        return $this->repository->excluirPagina($id);
    }
}

