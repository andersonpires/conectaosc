<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\PermissaoRepository;

final class PermissaoService
{
    public function __construct(private readonly PermissaoRepository $repository)
    {
    }

    public function listarPermissoes(): array
    {
        return $this->repository->listarPermissoes();
    }

    public function getPermissao(int $id): ?array
    {
        return $this->repository->getPermissao($id);
    }

    public function getPermissaoPaginas(int $id): array
    {
        return $this->repository->getPermissaoPaginas($id);
    }

    public function getPermissaoHorarios(int $id): array
    {
        return $this->repository->getPermissaoHorarios($id);
    }

    public function listarPaginas(): array
    {
        return $this->repository->listarPaginas();
    }

    public function salvarPermissao(string $nome, string $descricao, array $paginas, bool $todosHorariosDias, array $horariosSelecionados, ?int $id = null): int
    {
        return $this->repository->salvarPermissao($nome, $descricao, $paginas, $todosHorariosDias, $horariosSelecionados, $id);
    }

    public function excluirPermissao(int $id): bool
    {
        return $this->repository->excluirPermissao($id);
    }
}

