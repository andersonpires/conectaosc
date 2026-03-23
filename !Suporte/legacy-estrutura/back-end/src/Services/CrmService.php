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
        return $this->repository->buscarAlunosFaltosos(
            (string)($payload['curso'] ?? ''),
            (string)($payload['turma'] ?? ''),
            (int)($payload['faltas'] ?? 0),
            (int)($payload['dias'] ?? 0),
            $considerarMatricula,
            (int)($payload['somenteMatriculados'] ?? 1)
        );
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
        $dir = rtrim($baseDir, '/\\') . '/assets/img/notas/';
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

        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $urlFinal = $protocolo . $host . rtrim($baseUrl, '/') . '/assets/img/notas/' . $nome;

        return ['ok' => true, 'location' => $urlFinal, 'statusCode' => 200];
    }
}
