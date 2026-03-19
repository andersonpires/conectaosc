<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\EventoRepository;
use InvalidArgumentException;

final class EventoService
{
    public function __construct(private readonly EventoRepository $repository)
    {
    }

    public function listInscritos(): array
    {
        return $this->repository->listInscritos();
    }

    public function findInscrito(int $idInscrito): ?array
    {
        return $this->repository->findInscrito($idInscrito);
    }

    public function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $email = trim($email);
        if ($email === '') {
            return false;
        }
        return $this->repository->emailExists($email, $ignoreId);
    }

    public function create(array $payload): int
    {
        $email = trim((string)($payload['email'] ?? ''));
        if ($email !== '' && $this->repository->emailExists($email)) {
            throw new InvalidArgumentException('Esse email ja esta cadastrado no sistema, nao precisa se cadastrar novamente');
        }
        return $this->repository->create($this->normalizePayload($payload));
    }

    public function update(int $idInscrito, array $payload): bool
    {
        if ($idInscrito <= 0) {
            throw new InvalidArgumentException('ID do inscrito invalido');
        }
        $email = trim((string)($payload['email'] ?? ''));
        if ($email !== '' && $this->repository->emailExists($email, $idInscrito)) {
            throw new InvalidArgumentException('Esse email ja esta cadastrado no sistema, nao precisa se cadastrar novamente');
        }
        return $this->repository->update($idInscrito, $this->normalizePayload($payload));
    }

    public function delete(int $idInscrito): bool
    {
        if ($idInscrito <= 0) {
            throw new InvalidArgumentException('ID do inscrito invalido');
        }
        return $this->repository->delete($idInscrito);
    }

    private function normalizePayload(array $payload): array
    {
        return [
            'NomeCompleto' => trim((string)($payload['nomeCompleto'] ?? '')),
            'Email' => trim((string)($payload['email'] ?? '')),
            'Telefone' => trim((string)($payload['telefone'] ?? '')),
            'Telefone2' => trim((string)($payload['tel2'] ?? '')),
            'DataNascimento' => trim((string)($payload['dataNascimento'] ?? '')),
            'OrganizacaoSocial' => trim((string)($payload['organizacaoSocial'] ?? '')),
            'CargoOuFuncao' => trim((string)($payload['cargoFuncao'] ?? '')),
            'EnderecoOrganizacao' => trim((string)($payload['enderecoOrganizacao'] ?? '')),
            'MotivacaoEvento' => trim((string)($payload['motivacaoEvento'] ?? '')),
            'NecessidadesEspeciais' => trim((string)($payload['necessidadesEspeciais'] ?? '')),
            'ConfirmacaoParticipacao' => isset($payload['confirmacaoParticipacao']) && (string)$payload['confirmacaoParticipacao'] !== '0' ? 1 : 0,
        ];
    }
}

