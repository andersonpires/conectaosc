<?php
declare(strict_types=1);

namespace BackEnd\Repositories;

use PDO;
use RuntimeException;

class EventoRepository
{
    public function listInscritos(): array
    {
        $stmt = $this->pdo()->query("SELECT * FROM InscritosEvento ORDER BY NomeCompleto ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findInscrito(int $id): ?array
    {
        $stmt = $this->pdo()->prepare("SELECT * FROM InscritosEvento WHERE IdInscrito = :IdInscrito");
        $stmt->bindValue(':IdInscrito', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM InscritosEvento WHERE Email = :email";
        if ($ignoreId !== null && $ignoreId > 0) {
            $sql .= " AND IdInscrito <> :id";
        }
        $stmt = $this->pdo()->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        if ($ignoreId !== null && $ignoreId > 0) {
            $stmt->bindValue(':id', $ignoreId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $pdo = $this->pdo();
        $sql = "INSERT INTO InscritosEvento (
                    NomeCompleto, Email, Telefone, Telefone2, DataNascimento,
                    OrganizacaoSocial, CargoOuFuncao, EnderecoOrganizacao,
                    MotivacaoEvento, NecessidadesEspeciais, ConfirmacaoParticipacao
                ) VALUES (
                    :NomeCompleto, :Email, :Telefone, :Telefone2, :DataNascimento,
                    :OrganizacaoSocial, :CargoOuFuncao, :EnderecoOrganizacao,
                    :MotivacaoEvento, :NecessidadesEspeciais, :ConfirmacaoParticipacao
                )";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        return (int)$pdo->lastInsertId();
    }

    public function update(int $idInscrito, array $data): bool
    {
        $sql = "UPDATE InscritosEvento SET
                    NomeCompleto = :NomeCompleto,
                    Email = :Email,
                    Telefone = :Telefone,
                    Telefone2 = :Telefone2,
                    DataNascimento = :DataNascimento,
                    OrganizacaoSocial = :OrganizacaoSocial,
                    CargoOuFuncao = :CargoOuFuncao,
                    EnderecoOrganizacao = :EnderecoOrganizacao,
                    MotivacaoEvento = :MotivacaoEvento,
                    NecessidadesEspeciais = :NecessidadesEspeciais,
                    ConfirmacaoParticipacao = :ConfirmacaoParticipacao
                WHERE IdInscrito = :IdInscrito";
        $stmt = $this->pdo()->prepare($sql);
        $data['IdInscrito'] = $idInscrito;
        $stmt->execute($data);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $idInscrito): bool
    {
        $stmt = $this->pdo()->prepare("DELETE FROM InscritosEvento WHERE IdInscrito = :IdInscrito");
        $stmt->bindValue(':IdInscrito', $idInscrito, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function pdo(): PDO
    {
        $root = dirname(__DIR__, 3);
        require $root . '/conectabd/conexao.php';
        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new RuntimeException('Database connection unavailable');
        }
        return $pdo;
    }
}


