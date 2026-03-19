<?php
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

class EspecialidadeProfissionalModel
{
    public static function listByColaborador(int $idColaborador): array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT
                IdEspecialidadeProfissional,
                IdColaborador,
                especialidade_id,
                Conselho,
                UF,
                NumeroRegistro
            FROM tb_especialidade_profissional
            WHERE IdColaborador = ?
            ORDER BY IdEspecialidadeProfissional ASC
        ");
        $stmt->execute([$idColaborador]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function replaceForColaborador(int $idColaborador, array $itens): bool
    {
        global $pdo;
        $pdo->beginTransaction();
        try {
            $stmtDel = $pdo->prepare("DELETE FROM tb_especialidade_profissional WHERE IdColaborador = ?");
            $stmtDel->execute([$idColaborador]);

            if (!empty($itens)) {
                $stmtIns = $pdo->prepare("
                    INSERT INTO tb_especialidade_profissional
                        (IdColaborador, especialidade_id, Conselho, UF, NumeroRegistro)
                    VALUES
                        (?, ?, ?, ?, ?)
                ");

                foreach ($itens as $item) {
                    $stmtIns->execute([
                        $idColaborador,
                        $item['especialidade_id'],
                        $item['Conselho'],
                        $item['UF'],
                        $item['NumeroRegistro']
                    ]);
                }
            }

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }
}
