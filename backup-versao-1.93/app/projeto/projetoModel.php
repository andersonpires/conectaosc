<?php
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

class ProjetoModel
{
    public static function listAll()
    {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM tbProjeto ORDER BY NomeProjeto ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getById($id)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM tbProjeto WHERE IdProjeto = ?");
        $stmt->execute([intval($id)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($dados)
    {
        global $pdo;
        $stmt = $pdo->prepare("
            INSERT INTO tbProjeto (NomeProjeto, LogoProjeto, TermosContrato)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([
            $dados['NomeProjeto'] ?? '',
            $dados['LogoProjeto'] ?? null,
            $dados['TermosContrato'] ?? null
        ]);
    }

    public static function update($dados)
    {
        global $pdo;
        $stmt = $pdo->prepare("
            UPDATE tbProjeto
               SET NomeProjeto = ?,
                   LogoProjeto = ?,
                   TermosContrato = ?
             WHERE IdProjeto = ?
        ");
        return $stmt->execute([
            $dados['NomeProjeto'] ?? '',
            $dados['LogoProjeto'] ?? null,
            $dados['TermosContrato'] ?? null,
            intval($dados['IdProjeto'] ?? 0)
        ]);
    }

    public static function delete($id)
    {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM tbProjeto WHERE IdProjeto = ?");
        return $stmt->execute([intval($id)]);
    }
}
