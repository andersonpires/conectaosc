<?php
declare(strict_types=1);

namespace BackEnd\Repositories;

use PDO;
use RuntimeException;
use Throwable;

class PermissaoRepository
{
    public function listarPermissoes(): array
    {
        $stmt = $this->pdo()->query("SELECT IdPermissao, NomePermissao, Descricao FROM tbPermissao ORDER BY NomePermissao ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function getPermissao(int $id): ?array
    {
        $stmt = $this->pdo()->prepare("SELECT NomePermissao, Descricao FROM tbPermissao WHERE IdPermissao = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getPermissaoPaginas(int $id): array
    {
        $stmt = $this->pdo()->prepare("SELECT Pagina FROM tbPermissaoPagina WHERE IdPermissao = ?");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getPermissaoHorarios(int $id): array
    {
        $stmt = $this->pdo()->prepare("SELECT DiaSemana, Hora FROM tbPermissaoHorario WHERE IdPermissao = ?");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPaginas(): array
    {
        $stmt = $this->pdo()->query("SELECT NomePagina, DescricaoPagina, ArquivoPHP, TipoApp FROM tbPaginas ORDER BY TipoApp, NomePagina ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function salvarPermissao(string $nome, string $descricao, array $paginas, bool $todosHorariosDias, array $horariosSelecionados, ?int $id = null): int
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();
        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE tbPermissao SET NomePermissao = ?, Descricao = ? WHERE IdPermissao = ?");
                $stmt->execute([$nome, $descricao, $id]);
                $pdo->prepare("DELETE FROM tbPermissaoPagina WHERE IdPermissao = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM tbPermissaoHorario WHERE IdPermissao = ?")->execute([$id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO tbPermissao (NomePermissao, Descricao) VALUES (?, ?)");
                $stmt->execute([$nome, $descricao]);
                $id = (int)$pdo->lastInsertId();
            }

            if (!empty($paginas)) {
                $values = [];
                $params = [];
                foreach ($paginas as $pagina) {
                    $values[] = "(?, ?)";
                    $params[] = $id;
                    $params[] = $pagina;
                }
                $sql = "INSERT INTO tbPermissaoPagina (IdPermissao, Pagina) VALUES " . implode(', ', $values);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }

            if ($todosHorariosDias) {
                $diasSemana = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab', 'Dom'];
                $values = [];
                $params = [];
                foreach ($diasSemana as $dia) {
                    for ($hora = 0; $hora <= 23; $hora++) {
                        $values[] = "(?, ?, ?)";
                        $params[] = $id;
                        $params[] = $dia;
                        $params[] = $hora;
                    }
                }
                $sql = "INSERT INTO tbPermissaoHorario (IdPermissao, DiaSemana, Hora) VALUES " . implode(', ', $values);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            } elseif (!empty($horariosSelecionados)) {
                $values = [];
                $params = [];
                foreach ($horariosSelecionados as $valor) {
                    if (!is_string($valor) || strpos($valor, '_') === false) {
                        continue;
                    }
                    [$dia, $hora] = explode('_', $valor);
                    $values[] = "(?, ?, ?)";
                    $params[] = $id;
                    $params[] = $dia;
                    $params[] = (int)$hora;
                }
                if (!empty($values)) {
                    $sql = "INSERT INTO tbPermissaoHorario (IdPermissao, DiaSemana, Hora) VALUES " . implode(', ', $values);
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                }
            }

            $pdo->commit();
            return (int)$id;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function excluirPermissao(int $id): bool
    {
        $pdo = $this->pdo();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbPermissaoUsuario WHERE IdPermissao = ?");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            return false;
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM tbPermissaoHorario WHERE IdPermissao = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tbPermissaoPagina WHERE IdPermissao = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tbPermissao WHERE IdPermissao = ?")->execute([$id]);
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
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


