<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\ColaboradorRepository;

final class ColaboradorService
{
    public function __construct(private readonly ColaboradorRepository $repository)
    {
    }

    public function listProfissionaisSaude(array $query = []): array
    {
        $search = trim((string)($query['search'] ?? ''));
        $especialidadeId = $this->nullableInt($query['especialidade_id'] ?? null);
        $habilitado = $this->nullableInt($query['habilitado'] ?? 1);

        $rows = $this->repository->listProfissionaisSaude($search, $especialidadeId, $habilitado);

        return array_map(static function (array $row): array {
            $nome = trim((string)($row['Nome'] ?? ''));
            $sobrenome = trim((string)($row['Sobrenome'] ?? ''));
            return [
                'id' => (int)($row['IdColaborador'] ?? 0),
                'nome' => $nome,
                'sobrenome' => $sobrenome,
                'nome_completo' => trim($nome . ' ' . $sobrenome),
                'especialidade_id' => isset($row['especialidade_id']) && $row['especialidade_id'] !== null ? (int)$row['especialidade_id'] : null,
                'profissional_saude' => (int)($row['profissional_saude'] ?? 0),
                'habilitado' => (int)($row['Habilitado'] ?? 0),
            ];
        }, $rows);
    }

    public function findById(int $idColaborador): ?array
    {
        $row = $this->repository->findById($idColaborador);
        if ($row === null) {
            return null;
        }

        $nome = trim((string)($row['Nome'] ?? ''));
        $sobrenome = trim((string)($row['Sobrenome'] ?? ''));

        return [
            'id' => (int)($row['IdColaborador'] ?? 0),
            'nome' => $nome,
            'sobrenome' => $sobrenome,
            'nome_completo' => trim($nome . ' ' . $sobrenome),
            'email' => (string)($row['Email'] ?? ''),
            'especialidade_id' => isset($row['especialidade_id']) && $row['especialidade_id'] !== null ? (int)$row['especialidade_id'] : null,
            'profissional_saude' => (int)($row['profissional_saude'] ?? 0),
            'habilitado' => (int)($row['Habilitado'] ?? 0),
        ];
    }

    public function findPublicNameById(int $idColaborador): ?array
    {
        $row = $this->repository->findById($idColaborador);
        if ($row === null) {
            return null;
        }

        $nome = trim((string)($row['Nome'] ?? ''));
        $sobrenome = trim((string)($row['Sobrenome'] ?? ''));

        return [
            'id' => (int)($row['IdColaborador'] ?? 0),
            'nome_completo' => trim($nome . ' ' . $sobrenome),
        ];
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
