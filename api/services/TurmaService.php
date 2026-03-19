<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\TurmaRepository;

final class TurmaService
{
    public function __construct(private readonly TurmaRepository $repository)
    {
    }

    public function list(): array
    {
        return $this->repository->listAll();
    }

    public function listByCurso(int $idCurso, int $somenteAtivos = 1): array
    {
        return $this->repository->listByCurso($idCurso, $somenteAtivos);
    }

    public function create(array $payload): int
    {
        $idCurso = (int) ($payload['IdCurso'] ?? 0);
        $nomes = $payload['NomeTurma'] ?? [];
        if (!is_array($nomes)) {
            $nomes = [$nomes];
        }

        $rows = [];
        foreach ($nomes as $nome) {
            $nomeLimpo = trim((string) $nome);
            if ($nomeLimpo === '') {
                continue;
            }

            $rows[] = [
                'NomeTurma' => $nomeLimpo,
                'IdCurso' => $idCurso,
                'Municipio' => $this->stringOrEmpty($payload['Municipio'] ?? null),
                'Local' => $this->stringOrEmpty($payload['Local'] ?? null),
                'Obs' => $this->stringOrEmpty($payload['Obs'] ?? null),
                'MaxMatriculas' => $this->nullableInt($payload['max-matriculas'] ?? $payload['MaxMatriculas'] ?? null),
            ];
        }

        return $this->repository->createMany($rows);
    }

    public function update(int $idTurma, array $payload): bool
    {
        return $this->repository->update($idTurma, [
            'NomeTurma' => trim((string) ($payload['nometurma'] ?? $payload['NomeTurma'] ?? '')),
            'IdCurso' => (int) ($payload['curso'] ?? $payload['IdCurso'] ?? 0),
            'Municipio' => $this->stringOrEmpty($payload['municipio'] ?? $payload['Municipio'] ?? null),
            'Local' => $this->stringOrEmpty($payload['local'] ?? $payload['Local'] ?? null),
            'Habilitado' => $this->normalizeHabilitado($payload['habilitado'] ?? $payload['Habilitado'] ?? null),
            'Obs' => $this->stringOrEmpty($payload['obs'] ?? $payload['Obs'] ?? null),
            'MaxMatriculas' => $this->nullableInt($payload['max-matriculas'] ?? $payload['MaxMatriculas'] ?? null),
        ]);
    }

    public function delete(int $idTurma): bool
    {
        return $this->repository->delete($idTurma);
    }

    public function hasDependenciasChamada(int $idTurma): bool
    {
        return $this->repository->countDependenciasChamada($idTurma) > 0;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }

    private function stringOrEmpty(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function normalizeHabilitado(mixed $value): int
    {
        if ($value === 1 || $value === '1' || $value === true) {
            return 1;
        }

        $text = trim((string) $value);
        return strcasecmp($text, 'Ativo') === 0 ? 1 : 0;
    }
}
