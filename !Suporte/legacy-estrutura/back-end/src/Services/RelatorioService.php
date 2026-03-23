<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\RelatorioRepository;

final class RelatorioService
{
    public function __construct(private readonly RelatorioRepository $repository)
    {
    }

    public function cursosOptions(int $somenteAtivos = 1): array
    {
        $cursos = $this->repository->listCursos($somenteAtivos);
        return [
            ['value' => '', 'label' => 'Selecione o Curso'],
            ['value' => 'todas', 'label' => 'TODOS OS CURSOS'],
            ['value' => 'geral', 'label' => 'TODOS CADASTRADOS'],
            ...array_map(static fn(array $row): array => [
                'value' => (string)$row['IdCurso'],
                'label' => (string)$row['NomeCurso'],
            ], $cursos),
        ];
    }

    public function turmasOptions(int $idCurso, int $somenteAtivos = 1, int $incluirTodas = 1): array
    {
        $turmas = $this->repository->listTurmas($idCurso, $somenteAtivos);
        $options = [['value' => '', 'label' => 'Selecione a turma']];
        if ($incluirTodas === 1) {
            $options[] = ['value' => 'todas', 'label' => 'TODAS as turmas'];
        }
        foreach ($turmas as $row) {
            $options[] = [
                'value' => (string)$row['IdTurma'],
                'label' => (string)$row['NomeTurma'],
            ];
        }
        return $options;
    }

    public function turmasNomeOptions(string $idCurso): array
    {
        $turmas = $idCurso === 'all'
            ? $this->repository->listTurmasNome(null)
            : $this->repository->listTurmasNome((int)$idCurso);

        return array_map(static fn(array $row): array => [
            'value' => (string)$row['IdTurma'],
            'label' => (string)$row['NomeTurma'],
        ], $turmas);
    }

    public function projetos(): array
    {
        return $this->repository->listProjetos();
    }

    public function customAlunos(array $payload): array
    {
        return $this->repository->listCustomAlunos(
            (string)($payload['curso'] ?? ''),
            (string)($payload['turma'] ?? ''),
            !empty($payload['matriculasAtivas']) && (string)$payload['matriculasAtivas'] !== '0' ? 1 : 0,
            !empty($payload['filtroAtivos']) && (string)$payload['filtroAtivos'] !== '0' ? 1 : 0,
            $this->normalizeArray($payload['interesses'] ?? []),
            $this->normalizeArray($payload['colunas'] ?? [])
        );
    }

    public function customAlunosHtml(array $payload): string
    {
        $colunas = $this->normalizeArray($payload['colunas'] ?? []);
        $dados = $this->customAlunos($payload);
        if ($colunas === []) {
            return "<p style='color: red;'>Nenhuma coluna v&aacute;lida selecionada.</p>";
        }
        if ($dados === []) {
            return "<p style='color: orange;'>Nenhum dado encontrado com os filtros aplicados.</p>";
        }

        $html = "<style>
            thead { text-align: center; color: white; }
            th { background-color: rgb(83, 37, 126) !important; }
            tr:nth-child(even) { background-color: rgb(205, 241, 213); }
            tr:nth-child(odd) { background-color: #fff; }
        </style>";
        $html .= "<table class='table'><thead><tr><th>Ordem</th>";
        foreach ($colunas as $coluna) {
            $html .= "<th>" . htmlspecialchars((string)$coluna) . "</th>";
        }
        $html .= "</tr></thead><tbody>";
        foreach ($dados as $index => $linha) {
            $html .= "<tr><td>" . ($index + 1) . "</td>";
            foreach ($colunas as $coluna) {
                $html .= "<td>" . htmlspecialchars((string)($linha[$coluna] ?? '')) . "</td>";
            }
            $html .= "</tr>";
        }
        $html .= "</tbody></table>";
        return $html;
    }

    public function presencaCursoTurma(array $payload): array
    {
        $turmas = $payload['turma'] ?? [];
        if (!is_array($turmas)) {
            $turmas = $turmas !== '' ? [$turmas] : [];
        }
        $dias = $payload['dias'] ?? [];
        if (!is_array($dias)) {
            $dias = $dias !== '' ? [$dias] : [];
        }

        return $this->repository->listPresencaCursoTurma(
            (string)($payload['dataInicio'] ?? ''),
            (string)($payload['dataFim'] ?? ''),
            $turmas,
            $dias
        );
    }

    public function presencaCursoTurmaHtml(array $payload): string
    {
        $dados = $this->presencaCursoTurma($payload);
        $datas = $dados['datas'] ?? [];
        $linhas = $dados['linhas'] ?? [];
        if ($datas === []) {
            return "<p style='color: red;'>Nenhuma data encontrada para os filtros aplicados.</p>";
        }

        $map = ['0' => 'DOM', '1' => 'SEG', '2' => 'TER', '3' => 'QUA', '4' => 'QUI', '5' => 'SEX', '6' => 'SAB'];
        $html = '<table><thead><tr><th>No.</th><th>Curso</th><th>Turma</th>';
        foreach ($datas as $d) {
            $diaSemana = $map[date('w', strtotime((string)$d))] ?? '';
            $html .= "<th>" . date('d/m/Y', strtotime((string)$d)) . "<br><small>{$diaSemana}</small></th>";
        }
        $html .= '</tr></thead><tbody>';
        foreach ($linhas as $linha) {
            $html .= '<tr><td>' . (int)($linha['No.'] ?? 0) . '</td><td>' . htmlspecialchars((string)($linha['Curso'] ?? '')) . '</td><td>' . htmlspecialchars((string)($linha['Turma'] ?? '')) . '</td>';
            foreach ($datas as $data) {
                $chave = date('d/m/Y', strtotime((string)$data));
                $html .= '<td>' . (int)($linha[$chave] ?? 0) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    public function matriculados(array $payload): array
    {
        $curso = (int)($payload['curso'] ?? 0);
        $turma = (string)($payload['turma'] ?? '');
        if ($curso <= 0 || $turma === '') {
            return [];
        }
        return $this->repository->listMatriculados($curso, $turma);
    }

    public function frequenciaMensal(array $payload, bool $incluirProjetoDetalhes = false): array
    {
        $curso = (int)($payload['curso'] ?? 0);
        $turma = (string)($payload['turma'] ?? '');
        $mesAno = (string)($payload['mesAno'] ?? '');
        $somenteAtivos = (int)($payload['habilitado'] ?? 0);

        if ($curso <= 0 || $turma === '' || $mesAno === '' || !str_contains($mesAno, '-')) {
            return [];
        }
        [$ano, $mes] = array_map('intval', explode('-', $mesAno, 2));
        if ($ano <= 0 || $mes <= 0 || $mes > 12) {
            return [];
        }

        return $this->repository->listFrequenciaMensal(
            $curso,
            $turma,
            $mes,
            $ano,
            $somenteAtivos,
            $incluirProjetoDetalhes
        );
    }

    public function frequenciaIntervalo(
        array $payload,
        bool $ordenarPorTurma = false,
        bool $incluirProjetoDetalhes = false,
        bool $joinMatriculaSempre = false,
        bool $exigirMatriculaHabilitada = false
    ): array {
        $curso = (int)($payload['curso2'] ?? 0);
        $turma = (string)($payload['turma2'] ?? '');
        $dataInicio = (string)($payload['dataInicio'] ?? '');
        $dataFim = (string)($payload['dataFim'] ?? '');
        $somenteAtivos = (int)($payload['habilitado2'] ?? ($payload['habilitado'] ?? 0));

        if ($curso <= 0 || $turma === '' || $dataInicio === '' || $dataFim === '') {
            return [];
        }

        $dataInicioNorm = date('Y-m-d', strtotime($dataInicio));
        $dataFimNorm = date('Y-m-d', strtotime($dataFim));
        if ($dataInicioNorm === '' || $dataFimNorm === '' || $dataInicioNorm === '1970-01-01' || $dataFimNorm === '1970-01-01') {
            return [];
        }

        return $this->repository->listFrequenciaIntervalo(
            $curso,
            $turma,
            $dataInicioNorm,
            $dataFimNorm,
            $somenteAtivos,
            $ordenarPorTurma,
            $incluirProjetoDetalhes,
            $joinMatriculaSempre,
            $exigirMatriculaHabilitada
        );
    }

    public function logoImpressao(): string
    {
        return $this->repository->getLogoImpressao();
    }

    private function normalizeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if ($value === null || $value === '') {
            return [];
        }
        return [$value];
    }
}
