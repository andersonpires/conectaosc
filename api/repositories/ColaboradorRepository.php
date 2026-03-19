<?php
declare(strict_types=1);

namespace BackEnd\Repositories;

use PDO;
use RuntimeException;

class ColaboradorRepository
{
    public function listProfissionaisSaude(?string $search = null, ?int $especialidadeId = null, ?int $habilitado = 1): array
    {
        $conditions = ['COALESCE(profissional_saude, 0) = 1'];
        $params = [];

        if ($habilitado !== null) {
            $conditions[] = 'COALESCE(Habilitado, 1) = ?';
            $params[] = $habilitado;
        }

        if ($especialidadeId !== null && $especialidadeId > 0) {
            $conditions[] = 'COALESCE(especialidade_id, 0) = ?';
            $params[] = $especialidadeId;
        }

        if ($search !== null && $search !== '') {
            $conditions[] = '(Nome LIKE ? OR Sobrenome LIKE ? OR CONCAT(Nome, " ", Sobrenome) LIKE ?)';
            $term = '%' . $search . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql = 'SELECT IdColaborador, Nome, Sobrenome, especialidade_id, profissional_saude, COALESCE(Habilitado, 1) AS Habilitado
                  FROM tbUser
                 WHERE ' . implode(' AND ', $conditions) . '
              ORDER BY Nome ASC, Sobrenome ASC';

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    public function findById(int $idColaborador): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT IdColaborador, Nome, Sobrenome, Email, especialidade_id, profissional_saude, COALESCE(Habilitado, 1) AS Habilitado
               FROM tbUser
              WHERE IdColaborador = ?'
        );
        $stmt->execute([$idColaborador]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    private function pdo(): PDO
    {
        $root = dirname(__DIR__, 2);
        require $root . '/api/conectabd/conexao.php';

        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new RuntimeException('Database connection unavailable');
        }

        return $pdo;
    }
}
