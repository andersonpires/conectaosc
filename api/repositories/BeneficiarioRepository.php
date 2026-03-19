<?php
declare(strict_types=1);

namespace BackEnd\Repositories;

use PDO;
use RuntimeException;

class BeneficiarioRepository
{
    private function pdo(): PDO
    {
        $root = dirname(__DIR__, 2);
        require $root . '/api/conectabd/conexao.php';

        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new RuntimeException('Database connection unavailable');
        }

        return $pdo;
    }

    public function listResumoAtivos(): array
    {
        $sql = "SELECT a.*,
                    (SELECT GROUP_CONCAT(
                        DISTINCT CASE
                            WHEN m.Habilitado = 1 THEN c.NomeTurma
                            ELSE CONCAT(c.NomeTurma, ' (Desmatriculado)')
                        END
                        ORDER BY c.NomeTurma SEPARATOR '; '
                    )
                    FROM tbMatricula m
                    JOIN tbTurma c ON m.IdTurma = c.IdTurma
                    JOIN tbCurso cu ON cu.IdCurso = m.IdCurso
                    WHERE m.IdUsuario = a.IdUsuario
                      AND c.Habilitado = 1
                      AND cu.Habilitado = 1) AS TurmasAtivas,
                    (SELECT GROUP_CONCAT(DISTINCT c.NomeTurma ORDER BY c.NomeTurma SEPARATOR '; ')
                    FROM tbMatricula m
                    JOIN tbTurma c ON m.IdTurma = c.IdTurma
                    JOIN tbCurso cu ON cu.IdCurso = m.IdCurso
                    WHERE m.IdUsuario = a.IdUsuario
                      AND (c.Habilitado = 0 OR cu.Habilitado = 0)) AS HistoricoTurmas
                FROM tbAluno a
                WHERE a.Habilitado = 1
                ORDER BY a.Nome ASC";

        $stmt = $this->pdo()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listDetalhadoAtivos(): array
    {
        $sql = "SELECT a.*,
                    (SELECT GROUP_CONCAT(
                        DISTINCT CASE
                            WHEN m.Habilitado = 1 THEN c.NomeTurma
                            ELSE CONCAT(c.NomeTurma, ' (Desmatriculado)')
                        END
                        ORDER BY c.NomeTurma SEPARATOR '; '
                    )
                    FROM tbMatricula m
                    JOIN tbTurma c ON m.IdTurma = c.IdTurma
                    JOIN tbCurso cu ON cu.IdCurso = m.IdCurso
                    WHERE m.IdUsuario = a.IdUsuario
                      AND c.Habilitado = 1
                      AND cu.Habilitado = 1) AS TurmasAtivas,
                    (SELECT GROUP_CONCAT(DISTINCT c.NomeTurma ORDER BY c.NomeTurma SEPARATOR '; ')
                    FROM tbMatricula m
                    JOIN tbTurma c ON m.IdTurma = c.IdTurma
                    JOIN tbCurso cu ON cu.IdCurso = m.IdCurso
                    WHERE m.IdUsuario = a.IdUsuario
                      AND (c.Habilitado = 0 OR cu.Habilitado = 0)) AS HistoricoTurmas,
                    (SELECT GROUP_CONCAT(p.NomeProjeto SEPARATOR '/ ')
                    FROM tbInteresse i
                    JOIN tbProjeto p ON p.IdProjeto = i.IdProjeto
                    WHERE i.IdUsuario = a.IdUsuario AND i.Valor = 1) AS Interesses,
                    u1.Nome AS NomeUserEnt,
                    u2.Nome AS NomeUserAlt
                FROM tbAluno a
                LEFT JOIN tbUser u1 ON u1.IdColaborador = a.IdColaboradorEnt
                LEFT JOIN tbUser u2 ON u2.IdColaborador = a.IdColaboradorAlt
                WHERE a.Habilitado = 1
                ORDER BY a.Nome ASC";

        $stmt = $this->pdo()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function softDelete(int $idUsuario): bool
    {
        $stmt = $this->pdo()->prepare("UPDATE tbAluno SET Habilitado = 0 WHERE IdUsuario = ?");
        $stmt->execute([$idUsuario]);
        return $stmt->rowCount() > 0;
    }

    public function findById(int $idUsuario): ?array
    {
        $stmt = $this->pdo()->prepare("SELECT * FROM tbAluno WHERE IdUsuario = ?");
        $stmt->execute([$idUsuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $ids = array_values(array_map('intval', $ids));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT * FROM tbAluno WHERE IdUsuario IN ($placeholders) ORDER BY Nome ASC";
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }

    public function search(string $search, int $page, int $perPage, ?int $habilitado = 1): array
    {
        $conditions = [];
        $params = [];

        if ($habilitado !== null) {
            $conditions[] = 'COALESCE(Habilitado, 1) = ?';
            $params[] = $habilitado;
        }

        if ($search !== '') {
            $conditions[] = '(Nome LIKE ? OR Apelido LIKE ? OR CPF LIKE ? OR Telefone LIKE ? OR WhatsApp LIKE ? OR Email LIKE ?)';
            $term = '%' . $search . '%';
            $params = array_merge($params, [$term, $term, $term, $term, $term, $term]);
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        $countStmt = $this->pdo()->prepare('SELECT COUNT(*) FROM tbAluno' . $where);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT IdUsuario, Nome, Apelido, SexoBio, Nascimento, CPF, Identidade, NomeResp1, Parentesco,
                       CpfResp1, TelefoneResp1, WhatsAppResp1, CEP, Endereco, Numero, Complemento, Bairro, Cidade,
                       UF, Telefone, WhatsApp, Email, COALESCE(Habilitado, 1) AS Habilitado
                  FROM tbAluno' . $where . '
              ORDER BY Nome ASC
                 LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items' => is_array($items) ? $items : [],
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ];
    }

    public function interessesTexto(int $idUsuario): string
    {
        $stmt = $this->pdo()->prepare(
            "SELECT GROUP_CONCAT(p.NomeProjeto ORDER BY p.NomeProjeto SEPARATOR ', ') AS Interesses
             FROM tbInteresse ti
             INNER JOIN tbProjeto p ON p.IdProjeto = ti.IdProjeto
             WHERE ti.IdUsuario = ? AND ti.Valor = 1"
        );
        $stmt->execute([$idUsuario]);
        $value = $stmt->fetchColumn();
        return is_string($value) ? $value : '';
    }
}
