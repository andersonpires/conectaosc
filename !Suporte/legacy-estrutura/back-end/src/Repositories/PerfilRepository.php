<?php
declare(strict_types=1);

namespace BackEnd\Repositories;

use PDO;
use RuntimeException;

class PerfilRepository
{
    public function getById(int $idColaborador): ?array
    {
        $stmt = $this->pdo()->prepare("SELECT * FROM tbUser WHERE IdColaborador = :IdColaborador");
        $stmt->bindValue(':IdColaborador', $idColaborador, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function update(
        int $idColaborador,
        string $foto,
        ?string $nome,
        ?string $sobrenome,
        ?string $whatsApp,
        ?string $email,
        ?string $cidadeEstado,
        ?string $trabalho,
        int $profissionalSaude,
        mixed $especialidadeId,
        ?string $hashSenha
    ): bool {
        $sql = "UPDATE tbUser SET Foto = ?, Nome = ?, Sobrenome = ?, WhatsApp = ?, Email = ?, CidadeEstado = ?, Trabalho = ?, profissional_saude = ?, especialidade_id = ?";
        $params = [$foto, $nome, $sobrenome, $whatsApp, $email, $cidadeEstado, $trabalho, $profissionalSaude, $especialidadeId];
        if ($hashSenha !== null && $hashSenha !== '') {
            $sql .= ", Senha = ?";
            $params[] = $hashSenha;
        }
        $sql .= " WHERE IdColaborador = ?";
        $params[] = $idColaborador;

        $stmt = $this->pdo()->prepare($sql);
        return $stmt->execute($params);
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


