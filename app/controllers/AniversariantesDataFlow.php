<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

use DateTimeImmutable;
use PDO;
use PDOException;

final class AniversariantesDataFlow
{
    public function __construct(
        private readonly string $basePath,
        private readonly string $baseUrl
    ) {
    }

    public function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($this->basePath === '' || $this->baseUrl === '') {
            http_response_code(401);
            echo json_encode(['status' => 'erro', 'msg' => 'Acesso não autorizado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        require_once $this->basePath . '/api/conectabd/conexao.php';

        $action = $_POST['action'] ?? '';
        if ($action !== 'buscarAniversariantes') {
            echo json_encode(['status' => 'erro', 'msg' => 'Ação inválida.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $mes = $_POST['mes'] ?? '';
        if (!is_numeric($mes) || (int) $mes < 1 || (int) $mes > 12) {
            echo json_encode(['status' => 'erro', 'msg' => 'Mês inválido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $mes = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);

        try {
            global $pdo;
            $stmt = $pdo->prepare("
                SELECT a.IdUsuario,
                       a.Nome,
                       a.Apelido,
                       a.Foto,
                       a.Nascimento,
                       SUBSTRING_INDEX(SUBSTRING_INDEX(a.Nascimento, '/', 1), '/', -1) AS dia,
                       SUBSTRING_INDEX(SUBSTRING_INDEX(a.Nascimento, '/', 2), '/', -1) AS mes,
                       SUBSTRING_INDEX(a.Nascimento, '/', -1) AS ano_nasc,
                       GROUP_CONCAT(DISTINCT c.NomeCurso ORDER BY c.NomeCurso SEPARATOR ' / ') AS NomeCurso,
                       GROUP_CONCAT(DISTINCT t.NomeTurma ORDER BY t.NomeTurma SEPARATOR ' / ') AS NomeTurma
                  FROM tbAluno a
                  INNER JOIN tbMatricula m ON a.IdUsuario = m.IdUsuario
                  INNER JOIN tbCurso c ON m.IdCurso = c.IdCurso
                  INNER JOIN tbTurma t ON m.IdTurma = t.IdTurma
                 WHERE a.Habilitado = 1
                   AND m.Habilitado = 1
                   AND c.Habilitado = 1
                   AND t.Habilitado = 1
                   AND SUBSTRING_INDEX(SUBSTRING_INDEX(a.Nascimento, '/', 2), '/', -1) = ?
                 GROUP BY a.IdUsuario, a.Nome, a.Apelido, a.Foto, a.Nascimento, dia, mes, ano_nasc
                 ORDER BY CAST(dia AS UNSIGNED)
            ");
            $stmt->execute([$mes]);
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $anoAtual = (int) date('Y');
            $hoje = new DateTimeImmutable('today');

            $aniversariantes = array_map(static function (array $item) use ($anoAtual, $hoje): array {
                $nome = trim((string) ($item['Nome'] ?? ''));
                $apelido = trim((string) ($item['Apelido'] ?? ''));
                $dia = str_pad((string) ($item['dia'] ?? ''), 2, '0', STR_PAD_LEFT);
                $mesNasc = str_pad((string) ($item['mes'] ?? ''), 2, '0', STR_PAD_LEFT);
                $anoNasc = (int) ($item['ano_nasc'] ?? 0);

                // A idade que completa no aniversario do ano corrente e simplesmente
                // a diferenca entre ano atual e ano de nascimento.
                $idade = $anoAtual - $anoNasc;

                // Guarda de consistencia: se a data vier fora do padrao esperado,
                // tenta recalcular via DateTime para evitar erros de exibicao.
                $nascimento = DateTimeImmutable::createFromFormat('!d/m/Y', (string) ($item['Nascimento'] ?? ''));
                if ($nascimento instanceof DateTimeImmutable) {
                    $aniversarioAnoAtual = $nascimento->setDate($anoAtual, (int) $nascimento->format('m'), (int) $nascimento->format('d'));
                    $idade = (int) $nascimento->diff($aniversarioAnoAtual)->y;
                }

                return [
                    'Nome' => $nome,
                    'Apelido' => $apelido,
                    'Foto' => $item['Foto'] ?? '',
                    'Nascimento' => "{$dia}/{$mesNasc}/{$anoNasc}",
                    'Idade' => $idade,
                    'Curso' => $item['NomeCurso'] ?? '',
                    'Turma' => $item['NomeTurma'] ?? '',
                ];
            }, $dados);

            echo json_encode(['status' => 'ok', 'dados' => $aniversariantes], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['status' => 'erro', 'msg' => 'Erro ao buscar aniversariantes: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
