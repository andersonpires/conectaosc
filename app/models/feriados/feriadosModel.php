<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

class FeriadosModel
{
    private static function getValidColumns()
    {
        global $pdo;
        $colunasValidas = [];
        $stmt = $pdo->query("DESCRIBE tb_feriados");
        while ($linha = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $colunasValidas[] = $linha['Field'];
        }
        return $colunasValidas;
    }

    public static function listAll()
    {
        global $pdo;
        $stmt = $pdo->query("
            SELECT f.*, u.Nome AS NomeUsuario, u.Sobrenome AS SobrenomeUsuario
            FROM tb_feriados f
            LEFT JOIN tbUser u ON f.IdColaborador = u.IdColaborador
            ORDER BY f.DataFeriado ASC, f.Nome ASC, f.IdFeriado DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getById($id)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM tb_feriados WHERE IdFeriado = ?");
        $stmt->execute([intval($id)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function existsByNome($nome, $idIgnorar = 0)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT IdFeriado FROM tb_feriados WHERE Nome = ? AND IdFeriado <> ? LIMIT 1");
        $stmt->execute([$nome, intval($idIgnorar)]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getByDiasMes(array $diasMes)
    {
        global $pdo;
        $diasMes = array_values(array_filter($diasMes, fn($d) => is_string($d) && $d !== ''));
        if (empty($diasMes)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($diasMes), '?'));
        $stmt = $pdo->prepare("SELECT * FROM tb_feriados WHERE DataFeriado IN ($placeholders)");
        $stmt->execute($diasMes);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        $sql = "INSERT INTO tb_feriados ($colunas) VALUES ($placeholders)";
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
            if ($coluna !== 'IdFeriado' && in_array($coluna, $colunasValidas, true)) {
                $camposAtualizar[] = "`$coluna` = ?";
                $valores[] = is_array($valor) ? implode(',', $valor) : $valor;
            }
        }

        $id = intval($dados['IdFeriado'] ?? 0);
        if ($id <= 0) {
            return false;
        }

        if (empty($camposAtualizar)) {
            return true;
        }

        $valores[] = $id;
        $sql = "UPDATE tb_feriados SET " . implode(', ', $camposAtualizar) . " WHERE IdFeriado = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($valores);
    }

    public static function delete($id)
    {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM tb_feriados WHERE IdFeriado = ?");
        return $stmt->execute([intval($id)]);
    }
}

