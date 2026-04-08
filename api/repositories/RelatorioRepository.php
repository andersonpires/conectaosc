<?php
declare(strict_types=1);

namespace BackEnd\Repositories;

use PDO;
use RuntimeException;

class RelatorioRepository
{
    public function listCursos(int $somenteAtivos = 1): array
    {
        $sql = "SELECT DISTINCT IdCurso, NomeCurso FROM tbCurso";
        if ($somenteAtivos === 1) {
            $sql .= " WHERE Habilitado = 1";
        }
        $sql .= " ORDER BY NomeCurso";

        $stmt = $this->pdo()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listTurmas(int $idCurso, int $somenteAtivos = 1): array
    {
        $sql = "SELECT t.IdTurma, t.NomeTurma
                FROM tbTurma t
                INNER JOIN tbCurso c ON c.IdCurso = t.IdCurso
                WHERE t.IdCurso = ?";
        if ($somenteAtivos === 1) {
            $sql .= " AND t.Habilitado = 1 AND c.Habilitado = 1";
        }
        $sql .= " ORDER BY t.NomeTurma";

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute([$idCurso]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listTurmasNome(?int $idCurso = null): array
    {
        $sql = "SELECT t.IdTurma, t.NomeTurma
                FROM tbTurma t
                INNER JOIN tbCurso c ON c.IdCurso = t.IdCurso";
        $params = [];
        if ($idCurso !== null) {
            $sql .= " WHERE t.IdCurso = ? AND t.Habilitado = 1 AND c.Habilitado = 1";
            $params[] = $idCurso;
        } else {
            $sql .= " WHERE t.Habilitado = 1 AND c.Habilitado = 1";
        }
        $sql .= " ORDER BY t.NomeTurma";

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listProjetos(): array
    {
        $stmt = $this->pdo()->query("SELECT IdProjeto, NomeProjeto FROM tbProjeto ORDER BY NomeProjeto ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listCustomAlunos(
        string $curso,
        string $turma,
        int $matriculasAtivas,
        int $filtroAtivos,
        array $interesses,
        array $colunasSelecionadas
    ): array {
        $cursoGeral = ($curso === 'geral');
        $map = [
            'Nome' => 'a.Nome',
            'Nascimento' => 'a.Nascimento',
            'Endereco' => 'a.Endereco',
            'Cidade' => 'a.Cidade',
            'UF' => 'a.UF',
            'CPF' => 'a.CPF',
            'Interesses' => 'interesses.Interesses',
            'Bairro' => 'a.Bairro',
            'Telefone' => 'a.Telefone',
            'WhatsApp' => 'a.WhatsApp',
            'NomeCurso' => $cursoGeral
                ? "GROUP_CONCAT(DISTINCT cu.NomeCurso ORDER BY cu.NomeCurso SEPARATOR ', ')"
                : 'cu.NomeCurso',
            'NomeTurma' => $cursoGeral
                ? "GROUP_CONCAT(DISTINCT t.NomeTurma ORDER BY t.NomeTurma SEPARATOR ', ')"
                : 't.NomeTurma',
        ];

        $colunasSQL = [];
        foreach ($colunasSelecionadas as $coluna) {
            if (isset($map[$coluna])) {
                $colunasSQL[] = $map[$coluna] . " AS `" . $coluna . "`";
            }
        }
        if ($curso === '' || $colunasSQL === []) {
            return [];
        }

        $sql = "SELECT " . implode(", ", $colunasSQL) . "
                FROM tbAluno a
                LEFT JOIN tbMatricula m ON a.IdUsuario = m.IdUsuario
                LEFT JOIN tbCurso cu ON cu.IdCurso = m.IdCurso
                LEFT JOIN tbTurma t ON t.IdTurma = m.IdTurma
                LEFT JOIN (
                    SELECT ti.IdUsuario,
                           GROUP_CONCAT(p.NomeProjeto ORDER BY p.NomeProjeto SEPARATOR ', ') AS Interesses
                    FROM tbInteresse ti
                    INNER JOIN tbProjeto p ON p.IdProjeto = ti.IdProjeto
                    WHERE ti.Valor = 1
                    GROUP BY ti.IdUsuario
                ) interesses ON interesses.IdUsuario = a.IdUsuario
                WHERE a.Nome IS NOT NULL AND a.Nome != ''";

        $params = [];
        if (!$cursoGeral && $curso !== 'todas') {
            $sql .= " AND m.IdCurso = ?";
            $params[] = $curso;
        }
        if (!$cursoGeral && $turma !== '' && $turma !== 'todas') {
            $sql .= " AND m.IdTurma = ?";
            $params[] = $turma;
        }
        if ($matriculasAtivas === 1) {
            $sql .= " AND (m.Habilitado = 1 OR m.IdUsuario IS NULL)";
        }

        $interessesFiltrados = array_values(array_filter($interesses, static fn($id): bool => is_numeric((string)$id)));
        if ($interessesFiltrados !== []) {
            $placeholders = implode(',', array_fill(0, count($interessesFiltrados), '?'));
            $sql .= " AND EXISTS (
                SELECT 1 FROM tbInteresse ti
                WHERE ti.IdUsuario = a.IdUsuario
                  AND ti.Valor = 1
                  AND ti.IdProjeto IN ($placeholders)
            )";
            $params = array_merge($params, $interessesFiltrados);
        }

        if ($filtroAtivos === 1) {
            $sql .= " AND ((cu.Habilitado = 1 AND t.Habilitado = 1) OR m.IdUsuario IS NULL)";
        }

        $sql .= " GROUP BY a.IdUsuario ORDER BY a.Nome";
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_values(array_filter($dados, static fn(array $row): bool => !empty($row['Nome'])));
    }

    public function listPresencaCursoTurma(
        string $dataInicio,
        string $dataFim,
        array $turmas,
        array $diasSelecionados
    ): array {
        if ($dataInicio === '' || $dataFim === '') {
            return ['datas' => [], 'linhas' => []];
        }

        $params = [$dataInicio, $dataFim];
        $whereTurma = '';
        if ($turmas !== []) {
            $turmasFiltradas = array_values(array_filter($turmas, static fn($id): bool => is_numeric((string)$id)));
            if ($turmasFiltradas !== []) {
                $placeholdersTurma = implode(',', array_fill(0, count($turmasFiltradas), '?'));
                $whereTurma = " AND c.IdTurma IN ($placeholdersTurma)";
                $params = array_merge($params, $turmasFiltradas);
            }
        }

        $stmtDatas = $this->pdo()->prepare("SELECT DISTINCT Data FROM tbChamada WHERE Data BETWEEN ? AND ? ORDER BY Data ASC");
        $stmtDatas->execute([$dataInicio, $dataFim]);
        $datas = $stmtDatas->fetchAll(PDO::FETCH_COLUMN);

        if ($diasSelecionados !== []) {
            $datas = array_values(array_filter($datas, static function ($data) use ($diasSelecionados): bool {
                $map = ['0' => 'DOM', '1' => 'SEG', '2' => 'TER', '3' => 'QUA', '4' => 'QUI', '5' => 'SEX', '6' => 'SAB'];
                $dia = $map[date('w', strtotime((string)$data))] ?? '';
                return in_array($dia, $diasSelecionados, true);
            }));
        }

        if ($datas === []) {
            return ['datas' => [], 'linhas' => []];
        }

        $placeholdersData = implode(',', array_fill(0, count($datas), '?'));
        $paramsFinal = array_merge($params, $datas);
        $stmt = $this->pdo()->prepare(
            "SELECT c.IdCurso, cr.NomeCurso, c.IdTurma, t.NomeTurma, c.Data, COUNT(*) as TotalPresencas
             FROM tbChamada c
             INNER JOIN tbCurso cr ON cr.IdCurso = c.IdCurso
             INNER JOIN tbTurma t ON t.IdTurma = c.IdTurma
             WHERE c.Data BETWEEN ? AND ? AND c.presenca = 1 $whereTurma AND c.Data IN ($placeholdersData)
             GROUP BY c.IdCurso, cr.NomeCurso, c.IdTurma, t.NomeTurma, c.Data
             ORDER BY cr.NomeCurso, t.NomeTurma, c.Data"
        );
        $stmt->execute($paramsFinal);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tabela = [];
        foreach ($resultados as $linha) {
            $key = $linha['NomeCurso'] . '||' . $linha['NomeTurma'];
            if (!isset($tabela[$key])) {
                $tabela[$key] = ['Curso' => $linha['NomeCurso'], 'Turma' => $linha['NomeTurma']];
                foreach ($datas as $data) {
                    $tabela[$key][$data] = 0;
                }
            }
            $tabela[$key][$linha['Data']] = (int)$linha['TotalPresencas'];
        }

        $saida = [];
        $contador = 1;
        foreach ($tabela as $linha) {
            $linhaFormatada = ['No.' => $contador++, 'Curso' => $linha['Curso'], 'Turma' => $linha['Turma']];
            foreach ($datas as $data) {
                $linhaFormatada[date('d/m/Y', strtotime((string)$data))] = $linha[$data];
            }
            $saida[] = $linhaFormatada;
        }

        return ['datas' => $datas, 'linhas' => $saida];
    }

    public function listMatriculados(int $curso, string $turma): array
    {
        $sql = "SELECT a.IdUsuario, a.Nome, a.Foto, a.Nascimento, a.Endereco, a.Bairro, a.Cidade, a.UF,
                       cu.NomeCurso, t.NomeTurma, a.Habilitado, p.NomeProjeto, p.LogoProjeto
                FROM tbMatricula m
                INNER JOIN tbAluno a ON m.IdUsuario = a.IdUsuario
                INNER JOIN tbCurso cu ON m.IdCurso = cu.IdCurso
                INNER JOIN tbTurma t ON m.IdTurma = t.IdTurma
                LEFT JOIN tbProjeto p ON cu.IdProjeto = p.IdProjeto
                WHERE m.IdCurso = ? AND m.Habilitado = 1";

        $params = [$curso];
        if ($turma !== 'todas') {
            $sql .= " AND m.IdTurma = ?";
            $params[] = $turma;
        }

        $sql .= " ORDER BY a.Nome";
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listFrequenciaMensal(
        int $curso,
        string $turma,
        int $mes,
        int $ano,
        int $somenteAtivosAluno = 0,
        bool $incluirProjetoDetalhes = false
    ): array {
        $selectProjeto = '';
        $joinProjeto = '';
        if ($incluirProjetoDetalhes) {
            $selectProjeto = ", cu.Duracao, cu.Tipo, cu.CargaHoraria, cu.Termo, p.NomeProjeto, p.LogoProjeto";
            $joinProjeto = " LEFT JOIN tbProjeto p ON cu.IdProjeto = p.IdProjeto";
        }

        $sql = "SELECT a.IdUsuario AS IdAluno, a.IdUsuario, a.Nome AS Aluno, a.Foto, a.CPF, c.IdChamada, c.Obs, c.IdCurso, c.IdTurma, c.Dia, c.Mes, c.Ano, c.Data,
                       c.presenca, c.falta, c.faltajust, cu.NomeCurso, t.NomeTurma, a.Habilitado
                       $selectProjeto
                FROM tbChamada c
                INNER JOIN tbAluno a ON c.IdAluno = a.IdUsuario
                INNER JOIN tbCurso cu ON c.IdCurso = cu.IdCurso
                INNER JOIN tbTurma t ON c.IdTurma = t.IdTurma
                $joinProjeto
                WHERE c.IdCurso = ? AND c.Mes = ? AND c.Ano = ?";

        $params = [$curso, $mes, $ano];
        if ($turma !== 'todas') {
            $sql .= " AND c.IdTurma = ?";
            $params[] = $turma;
        }
        if ($somenteAtivosAluno === 1) {
            $sql .= " AND a.Habilitado = 1";
        }

        $sql .= " AND (c.presenca = 1 OR c.falta = 1 OR c.faltajust = 1) ORDER BY a.Nome, c.Dia";
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listFrequenciaIntervalo(
        int $curso,
        string $turma,
        string $dataInicio,
        string $dataFim,
        int $somenteAtivos = 0,
        bool $ordenarPorTurma = false,
        bool $incluirProjetoDetalhes = false,
        bool $joinMatriculaSempre = false,
        bool $exigirMatriculaHabilitada = false
    ): array {
        $selectProjeto = '';
        $joinProjeto = '';
        if ($incluirProjetoDetalhes) {
            $selectProjeto = ", cu.Duracao, cu.Tipo, cu.CargaHoraria, cu.Termo, p.NomeProjeto, p.LogoProjeto";
            $joinProjeto = " LEFT JOIN tbProjeto p ON cu.IdProjeto = p.IdProjeto";
        }

        $sql = "SELECT a.IdUsuario AS IdAluno, a.IdUsuario, a.Nome AS Aluno, a.Foto, a.CPF, c.IdChamada, c.Obs, c.IdCurso, c.IdTurma, c.Dia, c.Mes, c.Ano, c.Data,
                       c.presenca, c.falta, c.faltajust, cu.NomeCurso, t.NomeTurma, a.Habilitado
                       $selectProjeto
                FROM tbChamada c
                INNER JOIN tbAluno a ON c.IdAluno = a.IdUsuario
                INNER JOIN tbCurso cu ON c.IdCurso = cu.IdCurso
                INNER JOIN tbTurma t ON c.IdTurma = t.IdTurma
                $joinProjeto";

        if ($joinMatriculaSempre || $somenteAtivos === 1) {
            $sql .= " INNER JOIN tbMatricula m ON c.IdMatricula = m.IdMatricula";
        }

        $sql .= " WHERE c.IdCurso = ? AND c.Data >= ? AND c.Data <= ?
                  AND (c.presenca = 1 OR c.falta = 1 OR c.faltajust = 1)";

        $params = [$curso, $dataInicio, $dataFim];
        if ($turma !== 'todas') {
            $sql .= " AND c.IdTurma = ?";
            $params[] = $turma;
        }

        if ($somenteAtivos === 1) {
            $sql .= " AND a.Habilitado = 1";
            if ($exigirMatriculaHabilitada) {
                $sql .= " AND m.Habilitado = 1";
            }
        }

        $sql .= $ordenarPorTurma ? " ORDER BY t.NomeTurma, a.Nome, c.Data" : " ORDER BY a.Nome, c.Data";

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLogoImpressao(): string
    {
        $stmt = $this->pdo()->query("SELECT LogoImpressao FROM tbConfig LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (string)($row['LogoImpressao'] ?? '');
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

