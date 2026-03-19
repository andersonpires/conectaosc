<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\CursoRepository;

final class CursoService
{
    public function __construct(private readonly CursoRepository $repository)
    {
    }

    public function list(): array
    {
        return $this->repository->listAll();
    }

    public function listProjetos(): array
    {
        return $this->repository->listProjetos();
    }

    public function listAtivos(): array
    {
        return $this->repository->listAtivos();
    }

    public function create(array $payload): int
    {
        return $this->repository->create($this->normalizeCreatePayload($payload));
    }

    public function update(int $idCurso, array $payload): bool
    {
        return $this->repository->update($idCurso, $this->normalizeUpdatePayload($payload));
    }

    public function delete(int $idCurso): bool
    {
        return $this->repository->delete($idCurso);
    }

    public function hasDependenciasMatricula(int $idCurso): bool
    {
        return $this->repository->countDependenciasMatricula($idCurso) > 0;
    }

    private function normalizeCreatePayload(array $payload): array
    {
        return [
            'NomeCurso' => trim((string) ($payload['NomeCurso'] ?? '')),
            'Duracao' => $this->normalizeDecimal($payload['Duracao'] ?? null),
            'Tipo' => trim((string) ($payload['Tipo'] ?? '')),
            'CargaHoraria' => $this->normalizeDecimal($payload['CargaHoraria'] ?? null),
            'Termo' => $this->nullableString($payload['Termo'] ?? null),
            'IdProjeto' => $this->nullableInt($payload['Projeto'] ?? $payload['IdProjeto'] ?? null),
            'Programa' => $this->nullableString($payload['Programa'] ?? null),
            'Informacoes' => $this->stringOrEmpty($payload['Informacoes'] ?? null),
            'Habilitado' => $this->normalizeHabilitado($payload['Habilitado'] ?? null),
            'IdadeMin' => $this->nullableInt($payload['idade-min'] ?? $payload['IdadeMin'] ?? null),
            'IdadeMax' => $this->nullableInt($payload['idade-max'] ?? $payload['IdadeMax'] ?? null),
        ];
    }

    private function normalizeUpdatePayload(array $payload): array
    {
        return [
            'NomeCurso' => trim((string) ($payload['nomecurso'] ?? $payload['NomeCurso'] ?? '')),
            'Duracao' => $this->normalizeDecimal($payload['duracao'] ?? $payload['Duracao'] ?? null),
            'Tipo' => trim((string) ($payload['tipo'] ?? $payload['Tipo'] ?? '')),
            'CargaHoraria' => $this->normalizeDecimal($payload['cargah'] ?? $payload['CargaHoraria'] ?? null),
            'Termo' => $this->nullableString($payload['Termo'] ?? null),
            'IdProjeto' => $this->nullableInt($payload['Projeto'] ?? $payload['IdProjeto'] ?? null),
            'Programa' => $this->nullableString($payload['Programa'] ?? null),
            'Informacoes' => $this->stringOrEmpty($payload['informacoes'] ?? $payload['Informacoes'] ?? null),
            'Habilitado' => $this->normalizeHabilitado($payload['habilitado'] ?? $payload['Habilitado'] ?? null),
            'IdadeMin' => $this->nullableInt($payload['idade-min'] ?? $payload['IdadeMin'] ?? null),
            'IdadeMax' => $this->nullableInt($payload['idade-max'] ?? $payload['IdadeMax'] ?? null),
        ];
    }

    private function normalizeDecimal(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return str_replace(',', '.', trim((string) $value));
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
