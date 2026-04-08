<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\TurmaPlanoCursoRepository;
use InvalidArgumentException;

final class TurmaPlanoCursoService
{
    public function __construct(private readonly TurmaPlanoCursoRepository $repository)
    {
    }

    public function showByTurma(int $idTurma): ?array
    {
        if ($idTurma <= 0) {
            throw new InvalidArgumentException('Turma inválida');
        }
        if (!$this->repository->existsTurma($idTurma)) {
            throw new InvalidArgumentException('Turma não encontrada');
        }
        return $this->repository->getActiveByTurma($idTurma);
    }

    public function vincular(int $idTurma, int $idPlanoCurso, int $idColaborador): int
    {
        if ($idTurma <= 0 || $idPlanoCurso <= 0) {
            throw new InvalidArgumentException('Turma e plano são obrigatórios');
        }

        $turma = $this->repository->findTurmaById($idTurma);
        if (!$turma) {
            throw new InvalidArgumentException('Turma não encontrada');
        }

        $plano = $this->repository->findPlanoById($idPlanoCurso);
        if (!$plano) {
            throw new InvalidArgumentException('Plano não encontrado');
        }

        if ((int) ($turma['IdCurso'] ?? 0) !== (int) ($plano['IdCurso'] ?? 0)) {
            throw new InvalidArgumentException('Plano não pertence ao mesmo curso da turma');
        }

        $pdo = $this->repository->pdo();
        $pdo->beginTransaction();
        try {
            $ativoAtual = $this->repository->getActiveByTurma($idTurma);
            if ($ativoAtual && (int) ($ativoAtual['IdPlanoCurso'] ?? 0) === $idPlanoCurso) {
                $idAtivo = (int) ($ativoAtual['IdTurmaPlanoCurso'] ?? 0);
                if ($idAtivo > 0) {
                    $this->repository->createPendenciasFromPlano($idAtivo, $idPlanoCurso);
                }
                $pdo->rollBack();
                return $idAtivo;
            }

            if ($ativoAtual) {
                $id = (int) ($ativoAtual['IdTurmaPlanoCurso'] ?? 0);
                if ($id <= 0) {
                    throw new InvalidArgumentException('Vínculo ativo inválido para a turma');
                }
                $this->repository->clearCronogramaByTurmaPlano($id);
                $this->repository->updateActiveVinculo($id, $idPlanoCurso, $idColaborador);
                $this->repository->createPendenciasFromPlano($id, $idPlanoCurso);
                $pdo->commit();
                return $id;
            }

            $id = $this->repository->createVinculo($idTurma, $idPlanoCurso, $idColaborador);
            $this->repository->createPendenciasFromPlano($id, $idPlanoCurso);
            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function listCronogramaByTurma(int $idTurma): array
    {
        if ($idTurma <= 0) {
            throw new InvalidArgumentException('Turma inválida');
        }
        if (!$this->repository->existsTurma($idTurma)) {
            throw new InvalidArgumentException('Turma não encontrada');
        }

        $ativo = $this->repository->getActiveByTurma($idTurma);
        if ($ativo) {
            $idTurmaPlanoCurso = (int) ($ativo['IdTurmaPlanoCurso'] ?? 0);
            $idPlanoCurso = (int) ($ativo['IdPlanoCurso'] ?? 0);
            if ($idTurmaPlanoCurso > 0 && $idPlanoCurso > 0) {
                $this->repository->createPendenciasFromPlano($idTurmaPlanoCurso, $idPlanoCurso);
            }
        }

        return $this->repository->listCronogramaByTurma($idTurma);
    }

    public function listTurmasComPlanoByCurso(int $idCurso): array
    {
        if ($idCurso <= 0) {
            throw new InvalidArgumentException('Curso inválido');
        }
        if (!$this->repository->existsCurso($idCurso)) {
            throw new InvalidArgumentException('Curso não encontrado');
        }
        return $this->repository->listTurmasComPlanoByCurso($idCurso);
    }

    public function desvincular(int $idTurma): bool
    {
        if ($idTurma <= 0) {
            throw new InvalidArgumentException('Turma inválida');
        }
        if (!$this->repository->existsTurma($idTurma)) {
            throw new InvalidArgumentException('Turma não encontrada');
        }

        $ativo = $this->repository->getActiveByTurma($idTurma);
        if (!$ativo) {
            return false;
        }

        $idTurmaPlanoCurso = (int) ($ativo['IdTurmaPlanoCurso'] ?? 0);
        if ($idTurmaPlanoCurso <= 0) {
            throw new InvalidArgumentException('Vínculo ativo inválido para a turma');
        }

        $pdo = $this->repository->pdo();
        $pdo->beginTransaction();
        try {
            $this->repository->disableActiveByTurma($idTurma);
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
