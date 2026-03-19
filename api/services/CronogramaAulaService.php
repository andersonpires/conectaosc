<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\CronogramaAulaRepository;
use InvalidArgumentException;

final class CronogramaAulaService
{
    public function __construct(private readonly CronogramaAulaRepository $repository)
    {
    }

    public function agendar(int $idCronogramaAula, array $payload): array
    {
        $item = $this->repository->findById($idCronogramaAula);
        if (!$item) {
            throw new InvalidArgumentException('Cronograma não encontrado');
        }

        $dataAula = $this->nullableDate($payload['DataAula'] ?? null);
        $horaInicio = $this->nullableTime($payload['HoraInicio'] ?? null);

        if ($dataAula === null || $horaInicio === null) {
            throw new InvalidArgumentException('DataAula e HoraInicio são obrigatórios');
        }

        $duracao = (int) ($item['DuracaoMinutos'] ?? 0);
        if ($duracao <= 0) {
            throw new InvalidArgumentException('Duração da aula inválida');
        }

        $inicioTs = strtotime($dataAula . ' ' . $horaInicio);
        if ($inicioTs === false) {
            throw new InvalidArgumentException('Data/Hora inválida');
        }
        $fimTs = $inicioTs + ($duracao * 60);
        $horaFim = date('H:i:s', $fimTs);

        $status = $this->normalizeStatus($payload['StatusExecucao'] ?? 'futura', ['futura', 'pendente', 'atrasada']);
        $this->repository->updateAgendamento($idCronogramaAula, $dataAula, $horaInicio, $horaFim, $status);

        return [
            'IdCronogramaAula' => $idCronogramaAula,
            'DataAula' => $dataAula,
            'HoraInicio' => $horaInicio,
            'HoraFim' => $horaFim,
            'StatusExecucao' => $status,
        ];
    }

    public function atualizarStatus(int $idCronogramaAula, array $payload): bool
    {
        $item = $this->repository->findById($idCronogramaAula);
        if (!$item) {
            throw new InvalidArgumentException('Cronograma não encontrado');
        }

        $status = $this->normalizeStatus(
            $payload['StatusExecucao'] ?? '',
            ['pendente', 'futura', 'atrasada', 'realizada', 'adiada']
        );
        if ($status === '') {
            throw new InvalidArgumentException('StatusExecucao inválido');
        }

        return $this->repository->updateStatus(
            $idCronogramaAula,
            $status,
            $this->nullableString($payload['JustificativaAdiamento'] ?? null),
            $this->nullableString($payload['FeedbackProfessor'] ?? null),
            $this->nullableString($payload['Observacoes'] ?? null)
        );
    }

    public function listarComentarios(int $idCronogramaAula): array
    {
        $item = $this->repository->findById($idCronogramaAula);
        if (!$item) {
            throw new InvalidArgumentException('Cronograma não encontrado');
        }
        return $this->repository->listComentarios($idCronogramaAula);
    }

    public function comentar(int $idCronogramaAula, int $idColaborador, array $payload): int
    {
        $item = $this->repository->findById($idCronogramaAula);
        if (!$item) {
            throw new InvalidArgumentException('Cronograma não encontrado');
        }
        if ($idColaborador <= 0) {
            throw new InvalidArgumentException('Usuário inválido');
        }
        $comentario = trim((string) ($payload['Comentario'] ?? ''));
        if ($comentario === '') {
            throw new InvalidArgumentException('Comentário é obrigatório');
        }
        return $this->repository->addComentario($idCronogramaAula, $idColaborador, $comentario);
    }

    private function nullableDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $text) === 1 ? $text : null;
    }

    private function nullableTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }
        if (preg_match('/^\d{2}:\d{2}$/', $text) === 1) {
            return $text . ':00';
        }
        return preg_match('/^\d{2}:\d{2}:\d{2}$/', $text) === 1 ? $text : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }

    /**
     * @param array<int,string> $allowed
     */
    private function normalizeStatus(mixed $value, array $allowed): string
    {
        $status = strtolower(trim((string) $value));
        return in_array($status, $allowed, true) ? $status : '';
    }
}
