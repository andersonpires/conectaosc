<?php
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

class SwotModel
{
    private static function getValidColumns()
    {
        global $pdo;
        $colunasValidas = [];
        $stmt = $pdo->query("DESCRIBE tb_swot");
        while ($linha = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $colunasValidas[] = $linha['Field'];
        }
        return $colunasValidas;
    }

    public static function listByUser($idColaborador)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM tb_swot WHERE IdColaborador = ? ORDER BY DataCriacao DESC, IdSwot DESC");
        $stmt->execute([intval($idColaborador)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function listForReport($tema = null, $idColaborador = null)
    {
        global $pdo;
        $sql = "SELECT s.*, u.Nome, u.Sobrenome
                  FROM tb_swot s
                  JOIN tbUser u ON s.IdColaborador = u.IdColaborador
                 WHERE 1=1";
        $params = [];

        if (!empty($tema) && $tema !== 'todos') {
            $sql .= " AND s.Tema = ?";
            $params[] = $tema;
        }

        if (!empty($idColaborador) && $idColaborador !== 'todos') {
            $sql .= " AND s.IdColaborador = ?";
            $params[] = intval($idColaborador);
        }

        $sql .= " ORDER BY s.DataCriacao DESC, s.IdSwot DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getById($id)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM tb_swot WHERE IdSwot = ?");
        $stmt->execute([intval($id)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($dados)
    {
        global $pdo;
        $colunasValidas = self::getValidColumns();

        $dadosFiltrados = array_filter($dados, function ($key) use ($colunasValidas) {
            return in_array($key, $colunasValidas, true);
        }, ARRAY_FILTER_USE_KEY);

        if (empty($dadosFiltrados)) {
            return false;
        }

        $campos = array_keys($dadosFiltrados);
        $valores = [];
        foreach ($dadosFiltrados as $valor) {
            $valores[] = is_array($valor) ? implode(',', $valor) : $valor;
        }

        $colunas = implode(',', array_map(fn($campo) => "`$campo`", $campos));
        $placeholders = implode(',', array_fill(0, count($campos), '?'));

        $sql = "INSERT INTO tb_swot ($colunas) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($valores);
    }

    public static function update($dados)
    {
        global $pdo;
        $colunasValidas = self::getValidColumns();
        $camposAtualizar = [];
        $valores = [];

        foreach ($dados as $coluna => $valor) {
            if ($coluna !== 'IdSwot' && in_array($coluna, $colunasValidas, true)) {
                $camposAtualizar[] = "`$coluna` = ?";
                $valores[] = is_array($valor) ? implode(',', $valor) : $valor;
            }
        }

        $id = intval($dados['IdSwot'] ?? 0);
        if ($id <= 0) {
            return false;
        }

        if (empty($camposAtualizar)) {
            return true;
        }

        $valores[] = $id;
        $sql = "UPDATE tb_swot SET " . implode(', ', $camposAtualizar) . " WHERE IdSwot = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($valores);
    }

    public static function delete($id)
    {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM tb_swot WHERE IdSwot = ?");
        return $stmt->execute([intval($id)]);
    }
}
