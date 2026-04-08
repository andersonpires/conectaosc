<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\PlanoCursoAulaTodoRepository;
use InvalidArgumentException;

final class PlanoCursoAulaTodoService
{
    private const COR_PADRAO = '#FDE68A';

    public function __construct(private readonly PlanoCursoAulaTodoRepository $repository)
    {
    }

    public function listByAula(int $idPlanoCursoAula): array
    {
        if ($idPlanoCursoAula <= 0 || !$this->repository->existsPlanoAula($idPlanoCursoAula)) {
            throw new InvalidArgumentException('Aula do plano nao encontrada');
        }

        return $this->repository->listByAula($idPlanoCursoAula);
    }

    public function create(int $idPlanoCursoAula, array $payload, int $idColaborador): int
    {
        if ($idPlanoCursoAula <= 0 || !$this->repository->existsPlanoAula($idPlanoCursoAula)) {
            throw new InvalidArgumentException('Aula do plano nao encontrada');
        }
        if ($idColaborador <= 0) {
            throw new InvalidArgumentException('Usuario invalido');
        }

        $textoTopico = $this->validarTextoTopico($payload['TextoTopico'] ?? '');
        $corHex = $this->normalizarCor($payload['CorHex'] ?? self::COR_PADRAO);

        $pdo = $this->repository->pdo();
        $pdo->beginTransaction();
        try {
            $this->repository->normalizeDisabledOrdensByAula($idPlanoCursoAula);
            $ordemItem = isset($payload['OrdemItem']) ? (int) $payload['OrdemItem'] : 0;
            if ($ordemItem <= 0) {
                $ordemItem = $this->repository->nextOrdemItem($idPlanoCursoAula);
            }

            $id = $this->repository->create([
                'IdPlanoCursoAula' => $idPlanoCursoAula,
                'TextoTopico' => $textoTopico,
                'CorHex' => $corHex,
                'OrdemItem' => $ordemItem,
                'CriadoPor' => $idColaborador,
                'AtualizadoPor' => $idColaborador,
            ]);

            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function update(int $idPlanoCursoAulaTodo, array $payload, int $idColaborador): bool
    {
        $todo = $this->repository->findById($idPlanoCursoAulaTodo);
        if (!$todo) {
            throw new InvalidArgumentException('Topico nao encontrado');
        }
        if ($idColaborador <= 0) {
            throw new InvalidArgumentException('Usuario invalido');
        }

        $textoTopico = $this->validarTextoTopico($payload['TextoTopico'] ?? ($todo['TextoTopico'] ?? ''));
        $corHex = $this->normalizarCor($payload['CorHex'] ?? ($todo['CorHex'] ?? self::COR_PADRAO));

        return $this->repository->update($idPlanoCursoAulaTodo, [
            'TextoTopico' => $textoTopico,
            'CorHex' => $corHex,
            'AtualizadoPor' => $idColaborador,
        ]);
    }

    public function updateStatus(int $idPlanoCursoAulaTodo, array $payload, int $idColaborador): bool
    {
        $todo = $this->repository->findById($idPlanoCursoAulaTodo);
        if (!$todo) {
            throw new InvalidArgumentException('Topico nao encontrado');
        }
        if ($idColaborador <= 0) {
            throw new InvalidArgumentException('Usuario invalido');
        }

        $concluido = filter_var($payload['Concluido'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($concluido === null) {
            throw new InvalidArgumentException('Campo Concluido invalido');
        }

        return $this->repository->updateStatus($idPlanoCursoAulaTodo, $concluido, $idColaborador, $idColaborador);
    }

    public function reorder(int $idPlanoCursoAula, array $payload, int $idColaborador): array
    {
        if ($idPlanoCursoAula <= 0 || !$this->repository->existsPlanoAula($idPlanoCursoAula)) {
            throw new InvalidArgumentException('Aula do plano nao encontrada');
        }
        if ($idColaborador <= 0) {
            throw new InvalidArgumentException('Usuario invalido');
        }

        $ids = $payload['Itens'] ?? [];
        if (!is_array($ids) || $ids === []) {
            throw new InvalidArgumentException('Lista de itens obrigatoria');
        }

        $idsOrdenados = array_values(array_unique(array_map('intval', $ids)));
        if (count($idsOrdenados) !== count($ids)) {
            throw new InvalidArgumentException('Lista de itens contem IDs duplicados');
        }

        $atuais = $this->repository->listByAula($idPlanoCursoAula);
        $idsAtuais = array_map(static fn(array $row): int => (int) $row['IdPlanoCursoAulaTodo'], $atuais);
        sort($idsAtuais);
        $idsComparacao = $idsOrdenados;
        sort($idsComparacao);
        if ($idsAtuais !== $idsComparacao) {
            throw new InvalidArgumentException('Lista de itens inconsistente com a aula');
        }

        $pdo = $this->repository->pdo();
        $pdo->beginTransaction();
        try {
            $this->repository->normalizeDisabledOrdensByAula($idPlanoCursoAula);

            foreach ($idsOrdenados as $index => $idItem) {
                $this->repository->updateOrdemItem($idItem, -200000 - $index);
            }
            foreach ($idsOrdenados as $index => $idItem) {
                $this->repository->updateOrdemItem($idItem, $index + 1);
            }

            $pdo->commit();
            return [
                'itens_reordenados' => count($idsOrdenados),
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function delete(int $idPlanoCursoAulaTodo, int $idColaborador): bool
    {
        $todo = $this->repository->findById($idPlanoCursoAulaTodo);
        if (!$todo) {
            throw new InvalidArgumentException('Topico nao encontrado');
        }
        if ($idColaborador <= 0) {
            throw new InvalidArgumentException('Usuario invalido');
        }

        return $this->repository->softDelete($idPlanoCursoAulaTodo, $idColaborador);
    }

    private function validarTextoTopico(mixed $value): string
    {
        $texto = trim((string) $value);
        if ($texto === '') {
            throw new InvalidArgumentException('Texto do topico e obrigatorio');
        }
        $length = function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
        if ($length > 500) {
            throw new InvalidArgumentException('Texto do topico deve ter ate 500 caracteres');
        }
        return $texto;
    }

    private function normalizarCor(mixed $value): string
    {
        $cor = strtoupper(trim((string) $value));
        if ($cor === '') {
            return self::COR_PADRAO;
        }
        if (preg_match('/^#[0-9A-F]{6}$/', $cor) !== 1) {
            throw new InvalidArgumentException('CorHex invalida. Use o formato #RRGGBB');
        }
        return $cor;
    }
}
