<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\MatriculaRepository;
use DateTime;
use PDO;
use PDOException;

final class MatriculaService
{
    public function __construct(private readonly MatriculaRepository $repository)
    {
    }

    public function processarLote(array $payload): array
    {
        $pdo = $this->repository->pdo();

        $beneficiarios = isset($payload['codAluno']) && is_array($payload['codAluno']) ? $payload['codAluno'] : [];
        $cursoTurmasPost = isset($payload['CursoTurma']) && is_array($payload['CursoTurma']) ? $payload['CursoTurma'] : [];
        $dataMatricula = isset($payload['dataMatricula']) ? (string)$payload['dataMatricula'] : date('Y-m-d');

        if (empty($beneficiarios) || empty($cursoTurmasPost)) {
            return ['ok' => false, 'erro' => 'Selecione beneficiarios, cursos e turmas para continuar.'];
        }

        $beneficiariosIds = array_values(array_unique(array_filter(array_map('intval', $beneficiarios), static fn($id) => $id > 0)));
        if (empty($beneficiariosIds)) {
            return ['ok' => false, 'erro' => 'Nenhum beneficiario valido foi informado.'];
        }

        $selecoesSolicitadas = [];
        $turmaIdsSolicitados = [];
        foreach ($cursoTurmasPost as $item) {
            $idCurso = isset($item['IdCurso']) ? (int)$item['IdCurso'] : 0;
            $idTurma = isset($item['IdTurma']) ? (int)$item['IdTurma'] : 0;
            if ($idCurso <= 0 || $idTurma <= 0) {
                continue;
            }
            $chave = $idCurso . '-' . $idTurma;
            if (!isset($selecoesSolicitadas[$chave])) {
                $selecoesSolicitadas[$chave] = ['IdCurso' => $idCurso, 'IdTurma' => $idTurma];
                $turmaIdsSolicitados[] = $idTurma;
            }
        }
        if (empty($selecoesSolicitadas)) {
            return ['ok' => false, 'erro' => 'Nenhum par curso/turma valido foi informado.'];
        }

        $turmasValidas = $this->carregarTurmasValidas($pdo, $turmaIdsSolicitados);
        $selecoes = [];
        $cursoIdsSelecionados = [];
        $turmaIdsSelecionados = [];

        foreach ($selecoesSolicitadas as $item) {
            $idCurso = $item['IdCurso'];
            $idTurma = $item['IdTurma'];
            if (!isset($turmasValidas[$idTurma])) {
                continue;
            }
            if ($turmasValidas[$idTurma]['IdCurso'] !== $idCurso) {
                continue;
            }

            $dadosTurma = $turmasValidas[$idTurma];
            $selecoes[] = [
                'IdCurso' => $idCurso,
                'IdTurma' => $idTurma,
                'NomeCurso' => $dadosTurma['NomeCurso'],
                'NomeTurma' => $dadosTurma['NomeTurma'],
                'IdadeMin' => $dadosTurma['IdadeMin'],
                'IdadeMax' => $dadosTurma['IdadeMax'],
            ];
            $cursoIdsSelecionados[] = $idCurso;
            $turmaIdsSelecionados[] = $idTurma;
        }

        if (empty($selecoes)) {
            return ['ok' => false, 'erro' => 'Nao foi possivel validar os pares curso/turma selecionados.'];
        }

        $cursoIdsSelecionados = array_values(array_unique($cursoIdsSelecionados));
        $turmaIdsSelecionados = array_values(array_unique($turmaIdsSelecionados));

        $alunos = $this->carregarAlunos($pdo, $beneficiariosIds);
        $totaisAtivos = $this->carregarTotaisAtivosTurma($pdo, $turmaIdsSelecionados);
        $restantesTurma = [];
        foreach ($turmaIdsSelecionados as $idTurma) {
            $maxMatriculas = $turmasValidas[$idTurma]['MaxMatriculas'];
            $restantesTurma[$idTurma] = $maxMatriculas === null ? null : max(0, $maxMatriculas - ($totaisAtivos[$idTurma] ?? 0));
        }

        $matriculasAtivas = $this->carregarMatriculasAtivas($pdo, $beneficiariosIds, $cursoIdsSelecionados);
        $stmtInsert = $pdo->prepare("INSERT INTO tbMatricula (IdCurso, IdTurma, IdUsuario, vData, Habilitado) VALUES (?, ?, ?, ?, 1)");

        $dataTimestamp = strtotime($dataMatricula);
        $dataConvertida = $dataTimestamp ? date('d/m/Y', $dataTimestamp) : date('d/m/Y');
        $mensagemPadraoObs = "Por favor, verifique se o(a) beneficiario(a) ja se encontra matriculado(a) no(s) referido(s) curso(s); se a idade do(a) beneficiario(a) atende as regras do projeto; e se o limite maximo de pessoas nao foi atingido.";
        $agora = new DateTime('today');
        $resultadoLinhas = [];

        foreach ($beneficiariosIds as $idUsuario) {
            $dadosAluno = $alunos[$idUsuario] ?? null;
            $nomeAluno = $dadosAluno ? $dadosAluno['Nome'] : ('ID ' . $idUsuario);
            $idade = $this->calcularIdade($dadosAluno['Nascimento'] ?? null, $agora);
            $matriculadoEm = [];
            $observacoes = [];

            foreach ($selecoes as $selecao) {
                $idCurso = $selecao['IdCurso'];
                $idTurma = $selecao['IdTurma'];
                $rotulo = $selecao['NomeCurso'] . ' - ' . $selecao['NomeTurma'];

                if (!$dadosAluno || (int)$dadosAluno['Habilitado'] !== 1) {
                    $observacoes[] = $rotulo . ' (beneficiario inativo ou nao encontrado)';
                    continue;
                }

                $chave = $idUsuario . '-' . $idCurso;
                if (isset($matriculasAtivas[$chave])) {
                    $observacoes[] = $rotulo . ' (ja matriculado no curso)';
                    continue;
                }

                $idadeMin = $selecao['IdadeMin'];
                $idadeMax = $selecao['IdadeMax'];
                if (($idadeMin !== null || $idadeMax !== null) && $idade === null) {
                    $observacoes[] = $rotulo . ' (sem data de nascimento valida para validar idade)';
                    continue;
                }
                if (($idadeMin !== null && $idade < $idadeMin) || ($idadeMax !== null && $idade > $idadeMax)) {
                    $observacoes[] = $rotulo . ' (fora da faixa etaria do curso)';
                    continue;
                }

                $restante = $restantesTurma[$idTurma] ?? null;
                if ($restante !== null && $restante <= 0) {
                    $observacoes[] = $rotulo . ' (limite maximo da turma atingido)';
                    continue;
                }

                try {
                    $stmtInsert->execute([$idCurso, $idTurma, $idUsuario, $dataConvertida]);
                    $matriculadoEm[] = $rotulo;
                    $matriculasAtivas[$chave] = true;
                    if ($restante !== null) {
                        $restantesTurma[$idTurma] = $restante - 1;
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        $observacoes[] = $rotulo . ' (ja matriculado no curso)';
                    } else {
                        $observacoes[] = $rotulo . ' (erro ao efetuar matricula)';
                    }
                }
            }

            $resultadoLinhas[] = [
                'aluno' => $nomeAluno,
                'matriculas' => !empty($matriculadoEm) ? implode('; ', $matriculadoEm) : 'Nenhuma matricula efetivada',
                'observacoes' => !empty($observacoes) ? (implode('; ', $observacoes) . '. ' . $mensagemPadraoObs) : 'Sem impedimentos.',
            ];
        }

        return [
            'ok' => true,
            'resultado' => [
                'linhas' => $resultadoLinhas,
                'data_execucao' => date('d/m/Y H:i:s'),
            ],
        ];
    }

    public function update(array $payload): bool
    {
        return $this->repository->update(
            (int)($payload['idmatricula'] ?? 0),
            (int)($payload['curso'] ?? 0),
            (int)($payload['idturma'] ?? 0),
            (string)($payload['datamatricula'] ?? '')
        );
    }

    public function listByTurma(int $idTurma, bool $somenteAtivas = true): array
    {
        return $this->repository->listByTurma($idTurma, $somenteAtivas);
    }

    public function ativar(int $idMatricula, int $userId): bool
    {
        return $this->repository->ativar($idMatricula, $userId);
    }

    public function softDelete(array $payload, int $userId): bool
    {
        return $this->repository->softDelete(
            (int)($payload['idmatricula'] ?? 0),
            (int)($payload['curso'] ?? 0),
            (int)($payload['idturma'] ?? 0),
            $userId
        );
    }

    private function carregarTurmasValidas(PDO $pdo, array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT t.IdTurma, t.IdCurso, t.NomeTurma, t.MaxMatriculas, c.NomeCurso, c.IdadeMin, c.IdadeMax
                               FROM tbTurma t
                               INNER JOIN tbCurso c ON c.IdCurso = t.IdCurso
                               WHERE t.IdTurma IN ($placeholders) AND t.Habilitado = 1 AND c.Habilitado = 1");
        $stmt->execute($ids);
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $idTurma = (int)$row['IdTurma'];
            $result[$idTurma] = [
                'IdTurma' => $idTurma,
                'IdCurso' => (int)$row['IdCurso'],
                'NomeTurma' => $row['NomeTurma'],
                'NomeCurso' => $row['NomeCurso'],
                'IdadeMin' => $row['IdadeMin'] !== null ? (int)$row['IdadeMin'] : null,
                'IdadeMax' => $row['IdadeMax'] !== null ? (int)$row['IdadeMax'] : null,
                'MaxMatriculas' => $row['MaxMatriculas'] !== null ? (int)$row['MaxMatriculas'] : null,
            ];
        }

        return $result;
    }

    private function carregarAlunos(PDO $pdo, array $ids): array
    {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT IdUsuario, Nome, Nascimento, Habilitado FROM tbAluno WHERE IdUsuario IN ($placeholders)");
        $stmt->execute($ids);
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[(int)$row['IdUsuario']] = $row;
        }
        return $result;
    }

    private function carregarTotaisAtivosTurma(PDO $pdo, array $turmaIds): array
    {
        $placeholders = implode(',', array_fill(0, count($turmaIds), '?'));
        $stmt = $pdo->prepare("SELECT IdTurma, COUNT(*) AS TotalAtivos
                               FROM tbMatricula
                               WHERE IdTurma IN ($placeholders) AND Habilitado = 1
                               GROUP BY IdTurma");
        $stmt->execute($turmaIds);
        $totais = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $totais[(int)$row['IdTurma']] = (int)$row['TotalAtivos'];
        }
        return $totais;
    }

    private function carregarMatriculasAtivas(PDO $pdo, array $alunos, array $cursos): array
    {
        $phAluno = implode(',', array_fill(0, count($alunos), '?'));
        $phCurso = implode(',', array_fill(0, count($cursos), '?'));
        $stmt = $pdo->prepare("SELECT IdUsuario, IdCurso
                               FROM tbMatricula
                               WHERE IdUsuario IN ($phAluno)
                                 AND IdCurso IN ($phCurso)
                                 AND Habilitado = 1");
        $stmt->execute(array_merge($alunos, $cursos));
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[(int)$row['IdUsuario'] . '-' . (int)$row['IdCurso']] = true;
        }
        return $result;
    }

    private function calcularIdade(?string $nascimento, DateTime $hoje): ?int
    {
        if (!$nascimento) {
            return null;
        }

        $nascimento = trim($nascimento);
        $data = DateTime::createFromFormat('d/m/Y', $nascimento) ?: DateTime::createFromFormat('Y-m-d', $nascimento);
        if (!$data) {
            return null;
        }

        return $data->diff($hoje)->y;
    }
}
