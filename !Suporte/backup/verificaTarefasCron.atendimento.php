<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

require_once __DIR__ . '/../../api/repositories/CrmRepository.php';
require_once __DIR__ . '/../../api/services/CrmService.php';
require_once __DIR__ . '/../../app/views/atendimento/notificarUsuario.php';

use BackEnd\Repositories\CrmRepository;
use BackEnd\Services\CrmService;

$service = new CrmService(new CrmRepository());
$agora = date('Y-m-d H:i:s');
$limite = date('Y-m-d H:i:s', strtotime('+35 minutes'));

$tarefas = $service->listarTarefasPendentesParaNotificacao($agora, $limite);

if (empty($tarefas)) {
    $service->registrarLogNotificacao(null, 0, $agora, null);
    exit;
}

foreach ($tarefas as $tarefa) {
    try {
        $idTarefa = (int) ($tarefa['IdTarefa'] ?? 0);
        $idColaborador = (int) ($tarefa['IdColaborador'] ?? 0);
        $nomeAluno = (string) ($tarefa['NomeAluno'] ?? '');
        $dataExecucao = (string) ($tarefa['DataHoraExecucao'] ?? '');

        $mensagem = notificarUsuario(null, $idColaborador, 'tarefa', $nomeAluno, $dataExecucao);
        $tipoEnvio = !empty($mensagem) ? 1 : 0;

        if ($tipoEnvio === 1 && $idTarefa > 0) {
            $service->marcarTarefaNotificada($idTarefa);
        }

        $service->registrarLogNotificacao($idTarefa > 0 ? $idTarefa : null, $tipoEnvio, $agora, null);
        echo "Tarefa ID {$idTarefa} processada. Resultado: {$mensagem}\n";
    } catch (Throwable $e) {
        $idTarefaErro = isset($tarefa['IdTarefa']) ? (int) $tarefa['IdTarefa'] : null;
        $service->registrarLogNotificacao($idTarefaErro, null, $agora, $e->getMessage());
        echo "Erro ao processar tarefa ID {$idTarefaErro}: {$e->getMessage()}\n";
    }
}
