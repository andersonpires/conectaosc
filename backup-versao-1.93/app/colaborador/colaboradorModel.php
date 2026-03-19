<?php
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

class ColaboradorModel
{
    public static function listAll($apenasAtivos = true)
    {
        global $pdo;
        $sql = "SELECT u.*, p.NomePermissao
                FROM tbUser u
                LEFT JOIN tbPermissao p ON u.IdPermissao = p.IdPermissao";
        $params = [];

        if ($apenasAtivos) {
            $sql .= " WHERE u.Habilitado = 1";
        }

        $sql .= " ORDER BY u.Nome ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function listByStatus($habilitado)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT u.*, p.NomePermissao
                               FROM tbUser u
                               LEFT JOIN tbPermissao p ON u.IdPermissao = p.IdPermissao
                               WHERE u.Habilitado = ?
                               ORDER BY u.Nome ASC");
        $stmt->execute([intval($habilitado)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function listPermissoes()
    {
        global $pdo;
        $stmt = $pdo->query("SELECT IdPermissao, NomePermissao FROM tbPermissao ORDER BY NomePermissao");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getById($id)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT u.*, p.NomePermissao
                               FROM tbUser u
                               LEFT JOIN tbPermissao p ON u.IdPermissao = p.IdPermissao
                               WHERE u.IdColaborador = ?");
        $stmt->execute([intval($id)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getTipoPermissao($idPermissao)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT NomePermissao FROM tbPermissao WHERE IdPermissao = ?");
        $stmt->execute([intval($idPermissao)]);
        return $stmt->fetchColumn();
    }

    public static function create(array $dados)
    {
        global $pdo;
        $profissionalSaude = isset($dados['profissional_saude']) ? (int) $dados['profissional_saude'] : 0;
        $especialidadeId = isset($dados['especialidade_id']) && $dados['especialidade_id'] ? (int) $dados['especialidade_id'] : null;
        $sql = "INSERT INTO tbUser
                    (Foto, Nome, Sobrenome, CPF, IdPermissao, WhatsApp, Email, profissional_saude, especialidade_id, Senha, Habilitado, Email_confere, Tipo, IdColaboradorAlt, TimeAlterado)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            $dados['Foto'],
            $dados['Nome'],
            $dados['Sobrenome'],
            $dados['CPF'],
            $dados['IdPermissao'],
            $dados['WhatsApp'],
            $dados['Email'],
            $profissionalSaude,
            $especialidadeId,
            $dados['Senha'],
            $dados['Habilitado'],
            $dados['Email_confere'],
            $dados['Tipo'],
            $dados['IdColaboradorAlt'],
            $dados['TimeAlterado']
        ]);

        if (!$ok) {
            return false;
        }

        return (int) $pdo->lastInsertId();
    }

    public static function update(array $dados)
    {
        global $pdo;
        $profissionalSaude = isset($dados['profissional_saude']) ? (int) $dados['profissional_saude'] : 0;
        $especialidadeId = isset($dados['especialidade_id']) && $dados['especialidade_id'] ? (int) $dados['especialidade_id'] : null;
        $sql = "UPDATE tbUser
                   SET Foto = ?,
                       Nome = ?,
                       Sobrenome = ?,
                       IdPermissao = ?,
                       WhatsApp = ?,
                       Email = ?,
                       profissional_saude = ?,
                       especialidade_id = ?,
                       Tipo = ?,
                       IdColaboradorAlt = ?,
                       TimeAlterado = ?";
        $params = [
            $dados['Foto'],
            $dados['Nome'],
            $dados['Sobrenome'],
            $dados['IdPermissao'],
            $dados['WhatsApp'],
            $dados['Email'],
            $profissionalSaude,
            $especialidadeId,
            $dados['Tipo'],
            $dados['IdColaboradorAlt'],
            $dados['TimeAlterado']
        ];

        if (!empty($dados['Senha'])) {
            $sql .= ", Senha = ?";
            $params[] = $dados['Senha'];
        }

        $sql .= " WHERE IdColaborador = ?";
        $params[] = $dados['IdColaborador'];

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function inativar($id, $idColaboradorAlt, $timeAlterado)
    {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE tbUser SET Habilitado = 0, IdColaboradorAlt = ?, TimeAlterado = ? WHERE IdColaborador = ?");
        return $stmt->execute([
            $idColaboradorAlt,
            $timeAlterado,
            intval($id)
        ]);
    }

    public static function reativar($id, $idColaboradorAlt, $timeAlterado)
    {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE tbUser SET Habilitado = 1, IdColaboradorAlt = ?, TimeAlterado = ? WHERE IdColaborador = ?");
        return $stmt->execute([
            $idColaboradorAlt,
            $timeAlterado,
            intval($id)
        ]);
    }
}
