<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\PlanoCursoRepository;
use InvalidArgumentException;

final class PlanoCursoService
{
    public function __construct(private readonly PlanoCursoRepository $repository)
    {
    }

    public function list(int $idCurso = 0): array
    {
        return $this->repository->listAll($idCurso);
    }

    public function resumoCursos(string $estado = 'todos'): array
    {
        return $this->repository->listResumoCursos($estado);
    }

    public function show(int $idPlanoCurso): ?array
    {
        return $this->repository->findById($idPlanoCurso);
    }

    public function create(array $payload, int $idColaborador): int
    {
        $idCurso = (int) ($payload['IdCurso'] ?? 0);
        if ($idCurso <= 0) {
            throw new InvalidArgumentException('IdCurso é obrigatório');
        }

        $nomePlano = trim((string) ($payload['NomePlano'] ?? ''));
        if ($nomePlano === '') {
            throw new InvalidArgumentException('NomePlano é obrigatório');
        }

        if ($idColaborador <= 0) {
            throw new InvalidArgumentException('Usuário inválido');
        }

        return $this->repository->create([
            'IdCurso' => $idCurso,
            'NomePlano' => $nomePlano,
            'Versao' => $this->nullableString($payload['Versao'] ?? null),
            'Descricao' => $this->nullableString($payload['Descricao'] ?? null),
            'CriadoPor' => $idColaborador,
            'AtualizadoPor' => $idColaborador,
        ]);
    }

    public function update(int $idPlanoCurso, array $payload, int $idColaborador): bool
    {
        if ($idPlanoCurso <= 0) {
            throw new InvalidArgumentException('Plano inválido');
        }

        $plano = $this->repository->findById($idPlanoCurso);
        if (!$plano) {
            throw new InvalidArgumentException('Plano não encontrado');
        }

        $nomePlano = trim((string) ($payload['NomePlano'] ?? $plano['NomePlano'] ?? ''));
        if ($nomePlano === '') {
            throw new InvalidArgumentException('NomePlano é obrigatório');
        }

        return $this->repository->update($idPlanoCurso, [
            'NomePlano' => $nomePlano,
            'Versao' => $this->nullableString($payload['Versao'] ?? $plano['Versao'] ?? null),
            'Descricao' => $this->nullableString($payload['Descricao'] ?? $plano['Descricao'] ?? null),
            'AtualizadoPor' => $idColaborador > 0 ? $idColaborador : null,
        ]);
    }

    public function delete(int $idPlanoCurso, int $idColaborador): bool
    {
        if ($idPlanoCurso <= 0) {
            throw new InvalidArgumentException('Plano inválido');
        }
        $plano = $this->repository->findById($idPlanoCurso);
        if (!$plano) {
            throw new InvalidArgumentException('Plano não encontrado');
        }

        if ($this->repository->countVinculosAtivos($idPlanoCurso) > 0) {
            throw new InvalidArgumentException('Plano possui turmas vinculadas');
        }

        return $this->repository->softDelete($idPlanoCurso, $idColaborador);
    }

    public function deleteImpact(int $idPlanoCurso): array
    {
        if ($idPlanoCurso <= 0) {
            throw new InvalidArgumentException('Plano inválido');
        }
        $plano = $this->repository->findById($idPlanoCurso);
        if (!$plano) {
            throw new InvalidArgumentException('Plano não encontrado');
        }

        $impact = $this->repository->deleteImpact($idPlanoCurso);
        return [
            'IdPlanoCurso' => $idPlanoCurso,
            'NomePlano' => (string) ($plano['NomePlano'] ?? ''),
            'QtdAulas' => (int) ($impact['QtdAulas'] ?? 0),
            'QtdVinculos' => (int) ($impact['QtdVinculos'] ?? 0),
            'QtdVinculosAtivos' => (int) ($impact['QtdVinculosAtivos'] ?? 0),
            'QtdTurmas' => (int) ($impact['QtdTurmas'] ?? 0),
            'QtdCronogramas' => (int) ($impact['QtdCronogramas'] ?? 0),
            'QtdCronogramasAgendados' => (int) ($impact['QtdCronogramasAgendados'] ?? 0),
        ];
    }

    public function deletePermanente(int $idPlanoCurso, bool $forcarExclusao): bool
    {
        if ($idPlanoCurso <= 0) {
            throw new InvalidArgumentException('Plano inválido');
        }
        $plano = $this->repository->findById($idPlanoCurso);
        if (!$plano) {
            throw new InvalidArgumentException('Plano não encontrado');
        }

        $impact = $this->deleteImpact($idPlanoCurso);
        $temDependencias = ($impact['QtdVinculos'] > 0) || ($impact['QtdCronogramas'] > 0);
        if ($temDependencias && !$forcarExclusao) {
            throw new InvalidArgumentException('Plano possui turmas vinculadas e cronograma planejado. Confirme a exclusão forçada.');
        }

        return $this->repository->hardDelete($idPlanoCurso);
    }

    public function duplicate(int $idPlanoCurso, array $payload, int $idColaborador): int
    {
        $origem = $this->repository->findById($idPlanoCurso);
        if (!$origem) {
            throw new InvalidArgumentException('Plano de origem não encontrado');
        }

        $nomePlano = trim((string) ($payload['NomePlano'] ?? ''));
        if ($nomePlano === '') {
            $nomePlano = (string) ($origem['NomePlano'] ?? 'Plano duplicado') . ' (Cópia)';
        }

        $novoId = $this->repository->create([
            'IdCurso' => (int) ($origem['IdCurso'] ?? 0),
            'NomePlano' => $nomePlano,
            'Versao' => $this->nullableString($payload['Versao'] ?? ($origem['Versao'] ?? null)),
            'Descricao' => $this->nullableString($payload['Descricao'] ?? ($origem['Descricao'] ?? null)),
            'CriadoPor' => $idColaborador,
            'AtualizadoPor' => $idColaborador,
        ]);

        $aulasOrigem = $this->repository->listAulas($idPlanoCurso);
        foreach ($aulasOrigem as $aula) {
            $this->repository->createAula([
                'IdPlanoCurso' => $novoId,
                'OrdemAula' => (int) ($aula['OrdemAula'] ?? 0),
                'NomeAula' => trim((string) ($aula['NomeAula'] ?? 'Aula')),
                'Descricao' => $this->nullableString($aula['Descricao'] ?? null),
                'DuracaoMinutos' => (int) ($aula['DuracaoMinutos'] ?? 0),
                'Categoria' => $this->nullableString($aula['Categoria'] ?? null),
                'Recursos' => $this->nullableString($aula['Recursos'] ?? null),
                'Materiais' => $this->nullableString($aula['Materiais'] ?? null),
                'Observacoes' => $this->nullableString($aula['Observacoes'] ?? null),
            ]);
        }

        return $novoId;
    }

    public function listAulas(int $idPlanoCurso): array
    {
        if ($idPlanoCurso <= 0 || !$this->repository->findById($idPlanoCurso)) {
            throw new InvalidArgumentException('Plano não encontrado');
        }
        return $this->repository->listAulas($idPlanoCurso);
    }

    public function createAula(int $idPlanoCurso, array $payload): int
    {
        if ($idPlanoCurso <= 0 || !$this->repository->findById($idPlanoCurso)) {
            throw new InvalidArgumentException('Plano não encontrado');
        }

        $nomeAula = trim((string) ($payload['NomeAula'] ?? ''));
        if ($nomeAula === '') {
            throw new InvalidArgumentException('NomeAula é obrigatório');
        }

        $duracao = (int) ($payload['DuracaoMinutos'] ?? 0);
        if ($duracao <= 0) {
            throw new InvalidArgumentException('DuracaoMinutos deve ser maior que zero');
        }

        $ordem = isset($payload['OrdemAula']) ? (int) $payload['OrdemAula'] : 0;
        if ($ordem <= 0) {
            $ordem = $this->repository->nextOrdemAula($idPlanoCurso);
        }

        $idAula = $this->repository->createAula([
            'IdPlanoCurso' => $idPlanoCurso,
            'OrdemAula' => $ordem,
            'NomeAula' => $nomeAula,
            'Descricao' => $this->nullableString($payload['Descricao'] ?? null),
            'DuracaoMinutos' => $duracao,
            'Categoria' => $this->nullableString($payload['Categoria'] ?? null),
            'Recursos' => $this->nullableString($payload['Recursos'] ?? null),
            'Materiais' => $this->nullableString($payload['Materiais'] ?? null),
            'Observacoes' => $this->nullableString($payload['Observacoes'] ?? null),
        ]);

        $vinculos = $this->repository->listVinculosAtivosByPlano($idPlanoCurso);
        foreach ($vinculos as $vinculo) {
            $this->repository->createPendenteCronogramaIfMissing(
                (int) ($vinculo['IdTurmaPlanoCurso'] ?? 0),
                $idAula
            );
        }

        return $idAula;
    }

    public function updateAula(int $idPlanoCursoAula, array $payload): bool
    {
        $aula = $this->repository->findAulaById($idPlanoCursoAula);
        if (!$aula) {
            throw new InvalidArgumentException('Aula não encontrada');
        }

        $nomeAula = trim((string) ($payload['NomeAula'] ?? $aula['NomeAula'] ?? ''));
        if ($nomeAula === '') {
            throw new InvalidArgumentException('NomeAula é obrigatório');
        }

        $duracao = (int) ($payload['DuracaoMinutos'] ?? $aula['DuracaoMinutos'] ?? 0);
        if ($duracao <= 0) {
            throw new InvalidArgumentException('DuracaoMinutos deve ser maior que zero');
        }

        return $this->repository->updateAula($idPlanoCursoAula, [
            'NomeAula' => $nomeAula,
            'Descricao' => $this->nullableString($payload['Descricao'] ?? $aula['Descricao'] ?? null),
            'DuracaoMinutos' => $duracao,
            'Categoria' => $this->nullableString($payload['Categoria'] ?? $aula['Categoria'] ?? null),
            'Recursos' => $this->nullableString($payload['Recursos'] ?? $aula['Recursos'] ?? null),
            'Materiais' => $this->nullableString($payload['Materiais'] ?? $aula['Materiais'] ?? null),
            'Observacoes' => $this->nullableString($payload['Observacoes'] ?? $aula['Observacoes'] ?? null),
        ]);
    }

    public function deleteAula(int $idPlanoCursoAula): bool
    {
        $aula = $this->repository->findAulaById($idPlanoCursoAula);
        if (!$aula) {
            throw new InvalidArgumentException('Aula não encontrada');
        }
        return $this->repository->softDeleteAula($idPlanoCursoAula);
    }

    public function reorderAulas(int $idPlanoCurso, array $payload): array
    {
        if ($idPlanoCurso <= 0 || !$this->repository->findById($idPlanoCurso)) {
            throw new InvalidArgumentException('Plano não encontrado');
        }

        $modo = strtoupper(trim((string) ($payload['Modo'] ?? 'B')));
        if ($modo !== 'A' && $modo !== 'B') {
            throw new InvalidArgumentException('Modo inválido. Use A ou B');
        }

        $ids = $payload['Aulas'] ?? [];
        if (!is_array($ids) || $ids === []) {
            throw new InvalidArgumentException('Lista de aulas é obrigatória');
        }

        $idsOrdenados = array_values(array_unique(array_map('intval', $ids)));
        if (count($idsOrdenados) !== count($ids)) {
            throw new InvalidArgumentException('Lista de aulas contém IDs duplicados');
        }

        $aulasAtuais = $this->repository->listAulas($idPlanoCurso);
        $idsAtuais = array_map(static fn(array $a): int => (int) $a['IdPlanoCursoAula'], $aulasAtuais);
        sort($idsAtuais);
        $idsComparacao = $idsOrdenados;
        sort($idsComparacao);
        if ($idsAtuais !== $idsComparacao) {
            throw new InvalidArgumentException('Lista de aulas inconsistente com o plano');
        }

        $pdo = $this->repository->pdo();
        $pdo->beginTransaction();
        try {
            // Atualiza em duas fases para evitar colisão da unique (IdPlanoCurso, OrdemAula).
            foreach ($idsOrdenados as $index => $idAula) {
                $this->repository->updateOrdemAula($idAula, -100000 - $index);
            }
            foreach ($idsOrdenados as $index => $idAula) {
                $this->repository->updateOrdemAula($idAula, $index + 1);
            }

            $slotsAtualizados = 0;
            if ($modo === 'A') {
                $vinculos = $this->repository->listVinculosAtivosByPlano($idPlanoCurso);
                foreach ($vinculos as $vinculo) {
                    $idTurmaPlanoCurso = (int) ($vinculo['IdTurmaPlanoCurso'] ?? 0);
                    if ($idTurmaPlanoCurso <= 0) {
                        continue;
                    }

                    $slots = $this->repository->listCronogramaSlotsByVinculo($idTurmaPlanoCurso);
                    $limite = min(count($slots), count($idsOrdenados));
                    for ($i = 0; $i < $limite; $i++) {
                        $idCronograma = (int) ($slots[$i]['IdCronogramaAula'] ?? 0);
                        $novoIdAula = (int) $idsOrdenados[$i];
                        if ($idCronograma <= 0 || $novoIdAula <= 0) {
                            continue;
                        }
                        if ((int) ($slots[$i]['IdPlanoCursoAula'] ?? 0) === $novoIdAula) {
                            continue;
                        }
                        if ($this->repository->updateCronogramaAulaRef($idCronograma, $novoIdAula)) {
                            $slotsAtualizados++;
                        }
                    }
                }
            }

            $pdo->commit();
            return [
                'modo' => $modo,
                'aulas_reordenadas' => count($idsOrdenados),
                'slots_atualizados' => $slotsAtualizados,
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }
}
