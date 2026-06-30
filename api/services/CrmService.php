<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\CrmRepository;
use DateTime;

final class CrmService
{
    public function __construct(private readonly CrmRepository $repository)
    {
    }

    public function listarTarefasComCor(): array
    {
        $tarefas = $this->repository->listarTarefas();
        $hoje = new DateTime();
        $hoje->setTime(0, 0);

        foreach ($tarefas as &$row) {
            $dataExecucao = new DateTime((string)$row['DataHoraExecucao']);
            $cor = 'branco';
            if ($dataExecucao < $hoje) {
                $cor = 'vermelho';
            } elseif ($dataExecucao->format('Y-m-d') === $hoje->format('Y-m-d')) {
                $cor = 'amarelo';
            }
            $row['cor'] = $cor;
        }
        unset($row);

        return $tarefas;
    }

    public function marcarFeito(int $idTarefa): bool
    {
        return $this->repository->marcarTarefaFeito($idTarefa);
    }

    public function excluirTarefa(int $idTarefa): bool
    {
        return $this->repository->excluirTarefa($idTarefa);
    }

    public function buscarAlunosFaltosos(array $payload, bool $considerarMatricula): array
    {
        $statusFrequencia = $this->normalizarStatusFrequencia((string)($payload['statusFrequencia'] ?? 'F'));
        $quantidadeMinima = (int)($payload['quantidadeMinima'] ?? ($payload['faltas'] ?? 0));
        $dataInicioRaw = (string)($payload['dataInicio'] ?? '');
        $dataFimRaw = (string)($payload['dataFim'] ?? '');
        $dataInicio = $this->normalizarData($dataInicioRaw);
        $dataFim = $this->normalizarData($dataFimRaw);

        if ((trim($dataInicioRaw) !== '' && $dataInicio === '') || (trim($dataFimRaw) !== '' && $dataFim === '')) {
            throw new \InvalidArgumentException('Informe datas válidas para início e fim.');
        }
        if (($dataInicio === '') !== ($dataFim === '')) {
            throw new \InvalidArgumentException('Informe a data de início e a data de fim.');
        }
        if ($dataInicio !== '' && $dataFim !== '' && $dataInicio > $dataFim) {
            throw new \InvalidArgumentException('A data de início não pode ser maior que a data de fim.');
        }

        return $this->repository->buscarAlunosFaltosos(
            (string)($payload['curso'] ?? ''),
            (string)($payload['turma'] ?? ''),
            $quantidadeMinima,
            (int)($payload['dias'] ?? 0),
            $considerarMatricula,
            (int)($payload['somenteMatriculados'] ?? 1),
            $statusFrequencia,
            $dataInicio,
            $dataFim
        );
    }

    public function exportarFrequenciaBuscaCrm(array $payload, bool $considerarMatricula): array
    {
        $dataInicioRaw = (string)($payload['dataInicio'] ?? '');
        $dataFimRaw = (string)($payload['dataFim'] ?? '');
        $dataInicio = $this->normalizarData($dataInicioRaw);
        $dataFim = $this->normalizarData($dataFimRaw);
        if ((trim($dataInicioRaw) !== '' && $dataInicio === '') || (trim($dataFimRaw) !== '' && $dataFim === '')) {
            throw new \InvalidArgumentException('Informe datas válidas para início e fim.');
        }
        if ($dataInicio === '' || $dataFim === '') {
            throw new \InvalidArgumentException('Informe a data de início e a data de fim para exportar.');
        }
        if ($dataInicio > $dataFim) {
            throw new \InvalidArgumentException('A data de início não pode ser maior que a data de fim.');
        }

        $alunosBusca = $this->buscarAlunosFaltosos($payload, $considerarMatricula);
        if ($alunosBusca === []) {
            return [];
        }

        $frequencias = $this->repository->listarFrequenciaDetalhadaPorAlunos($alunosBusca, $dataInicio, $dataFim);
        return $this->montarExcelFrequenciaCrm($alunosBusca, $frequencias, $dataInicio, $dataFim);
    }

    public function dadosAluno(int $idUsuario, bool $incluirIdsCursoTurma): ?array
    {
        return $this->repository->dadosAluno($idUsuario, $incluirIdsCursoTurma);
    }

    public function listarNotasAluno(int $idUsuario): array
    {
        return $this->repository->listarNotasAluno($idUsuario);
    }

    public function salvarNota(int $idUsuario, int $idColaborador, string $textoNota): array
    {
        $ok = $this->repository->salvarNota($idUsuario, $idColaborador, $textoNota);
        $aluno = $ok ? $this->repository->getNomeAluno($idUsuario) : null;
        return ['ok' => $ok, 'nomeAluno' => $aluno];
    }

    public function salvarTarefa(int $idUsuario, int $idColaborador, string $descricaoTarefa, string $dataExecucao): array
    {
        $ok = $this->repository->salvarTarefa($idUsuario, $idColaborador, $descricaoTarefa, $dataExecucao);
        $aluno = $ok ? $this->repository->getNomeAluno($idUsuario) : null;
        return ['ok' => $ok, 'nomeAluno' => $aluno];
    }

    public function dadosColaborador(int $idColaborador): ?array
    {
        return $this->repository->dadosColaborador($idColaborador);
    }

    public function getPreferenciasNotificacao(int $idColaborador): array
    {
        $usuario = $this->repository->dadosColaborador($idColaborador) ?? ['Email' => null, 'WhatsApp' => null];
        $prefs = $this->repository->getPreferenciasNotificacao($idColaborador);
        if (!$prefs) {
            $this->repository->garantirPreferenciasNotificacao($idColaborador);
            $prefs = [
                'Nota_Email' => 0,
                'Nota_WhatsApp' => 0,
                'Tarefa_Email' => 0,
                'Tarefa_WhatsApp' => 0,
            ];
        }
        return array_merge($prefs, $usuario);
    }

    public function salvarPreferenciasNotificacao(
        int $idColaborador,
        int $notaEmail,
        int $notaWhatsapp,
        int $tarefaEmail,
        int $tarefaWhatsapp
    ): bool {
        $this->repository->garantirPreferenciasNotificacao($idColaborador);
        return $this->repository->salvarPreferenciasNotificacao(
            $idColaborador,
            $notaEmail,
            $notaWhatsapp,
            $tarefaEmail,
            $tarefaWhatsapp
        );
    }

    public function dadosNotificacaoColaborador(int $idColaborador): ?array
    {
        return $this->repository->dadosNotificacaoColaborador($idColaborador);
    }

    public function apiZap(): ?string
    {
        return $this->repository->apiZap();
    }

    public function listarTarefasPendentesParaNotificacao(string $agora, string $limite): array
    {
        return $this->repository->listarTarefasPendentesParaNotificacao($agora, $limite);
    }

    public function marcarTarefaNotificada(int $idTarefa): void
    {
        $this->repository->marcarTarefaNotificada($idTarefa);
    }

    public function registrarLogNotificacao(?int $idTarefa, ?int $tipoEnvio, string $dataEnvio, ?string $erro): void
    {
        $this->repository->registrarLogNotificacao($idTarefa, $tipoEnvio, $dataEnvio, $erro);
    }

    public function salvarImagemNota(array $file, string $baseDir, string $baseUrl): array
    {
        [$assetsImgPath, $assetsImgUrl] = $this->resolveAssetsImgConfig($baseDir, $baseUrl);
        $dir = rtrim($assetsImgPath, '/\\') . DIRECTORY_SEPARATOR . 'notas' . DIRECTORY_SEPARATOR;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!isset($file['tmp_name']) || !is_uploaded_file((string)$file['tmp_name'])) {
            return ['ok' => false, 'error' => 'Nenhum arquivo enviado', 'statusCode' => 400];
        }

        $extensao = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        $extPermitidas = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($extensao, $extPermitidas, true)) {
            return ['ok' => false, 'error' => 'Extensao de arquivo invalida', 'statusCode' => 400];
        }

        $nome = uniqid('nota_') . '.' . $extensao;
        $destino = $dir . $nome;
        if (!move_uploaded_file((string)$file['tmp_name'], $destino)) {
            return ['ok' => false, 'error' => 'Erro ao mover a imagem', 'statusCode' => 500];
        }

        if (preg_match('/^https?:\\/\\//i', $assetsImgUrl) === 1) {
            $urlFinal = rtrim($assetsImgUrl, '/') . '/notas/' . $nome;
        } else {
            $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $urlFinal = $protocolo . $host . rtrim($assetsImgUrl, '/') . '/notas/' . $nome;
        }

        return ['ok' => true, 'location' => $urlFinal, 'statusCode' => 200];
    }

    private function resolveAssetsImgConfig(string $baseDir, string $baseUrl): array
    {
        $runtimeFile = dirname(__DIR__, 2) . '/bootstrap/runtime.php';
        if (is_file($runtimeFile)) {
            $runtime = require $runtimeFile;
            $path = (string)($runtime['assets_img_path'] ?? '');
            $url = (string)($runtime['assets_img_url'] ?? '');
            if ($path !== '' && $url !== '') {
                return [$path, $url];
            }
        }

        return [
            rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img',
            rtrim($baseUrl, '/') . '/assets/img',
        ];
    }

    private function normalizarStatusFrequencia(string $status): string
    {
        $status = strtoupper(trim($status));
        return in_array($status, ['P', 'F', 'FJ'], true) ? $status : 'F';
    }

    private function normalizarData(string $data): string
    {
        $data = trim($data);
        if ($data === '') {
            return '';
        }

        $timestamp = strtotime($data);
        if ($timestamp === false) {
            return '';
        }

        return date('Y-m-d', $timestamp);
    }

    private function montarExcelFrequenciaCrm(array $alunosBusca, array $frequencias, string $dataInicio, string $dataFim): array
    {
        $dias = [];
        $tabela = [];
        $alunos = [];

        foreach ($alunosBusca as $alunoBusca) {
            $key = $this->chaveAlunoFrequencia($alunoBusca);
            $alunos[$key] = [
                'nome' => (string)($alunoBusca['Nome'] ?? ''),
                'turma' => (string)($alunoBusca['NomeTurma'] ?? ''),
                'curso' => (string)($alunoBusca['NomeCurso'] ?? ''),
            ];
            $tabela[$key] = [];
        }

        foreach ($frequencias as $row) {
            $key = $this->chaveAlunoFrequencia([
                'IdUsuario' => $row['IdUsuario'] ?? 0,
                'IdCurso' => $row['IdCurso'] ?? 0,
                'IdTurma' => $row['IdTurma'] ?? 0,
            ]);
            if (!isset($alunos[$key])) {
                continue;
            }

            $data = (string)($row['Data'] ?? '');
            if ($data === '') {
                continue;
            }
            if (!in_array($data, $dias, true)) {
                $dias[] = $data;
            }

            $status = 'NA';
            if ((int)($row['presenca'] ?? 0) === 1) {
                $status = 'P';
            } elseif ((int)($row['falta'] ?? 0) === 1) {
                $status = 'F';
            } elseif ((int)($row['faltajust'] ?? 0) === 1) {
                $status = 'FJ';
            }
            $tabela[$key][$data] = $status;
        }

        usort($dias, static fn(string $a, string $b): int => strtotime($a) <=> strtotime($b));

        $cursos = array_values(array_unique(array_filter(array_column($alunos, 'curso'))));
        $turmas = array_values(array_unique(array_filter(array_column($alunos, 'turma'))));
        $cursoNome = count($cursos) === 1 ? $cursos[0] : 'TODOS';
        $turmaNome = count($turmas) === 1 ? $turmas[0] : 'TODAS';

        $excelData = [];
        $excelData[] = ['SISTEMA DE FREQUÊNCIA'];
        $excelData[] = ['Curso: ' . $cursoNome];
        $excelData[] = ['Turma: ' . $turmaNome];
        $excelData[] = ['De ' . date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim))];
        $excelData[] = [''];
        $excelData[] = array_merge(
            ['No', 'Nome do Aluno', 'Turma', 'Qtd P', 'Qtd F', 'Qtd FJ', 'Qtd Aulas'],
            array_map(static fn(string $dia): string => date('d/m/y', strtotime($dia)), $dias)
        );

        $contador = 1;
        foreach ($alunos as $key => $aluno) {
            $totalP = 0;
            $totalF = 0;
            $totalFJ = 0;

            foreach ($dias as $dia) {
                $status = $tabela[$key][$dia] ?? 'NA';
                if ($status === 'P') {
                    $totalP++;
                } elseif ($status === 'F') {
                    $totalF++;
                } elseif ($status === 'FJ') {
                    $totalFJ++;
                }
            }

            $rowData = [
                $contador++,
                $aluno['nome'],
                $aluno['turma'],
                $totalP,
                $totalF,
                $totalFJ,
                $totalP + $totalF + $totalFJ,
            ];

            foreach ($dias as $dia) {
                $rowData[] = $tabela[$key][$dia] ?? 'NA';
            }

            $excelData[] = $rowData;
        }

        $excelData[] = [''];
        $excelData[] = ['Obs.: P = Presença; F = Falta; FJ = Falta Justificada; e NA = Não se Aplica.'];
        return $excelData;
    }

    private function chaveAlunoFrequencia(array $row): string
    {
        return (int)($row['IdUsuario'] ?? 0) . '|' . (int)($row['IdCurso'] ?? 0) . '|' . (int)($row['IdTurma'] ?? 0);
    }
}
