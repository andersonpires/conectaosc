<?php
require_once __DIR__ . '../../../api/conectabd/conexao.php';
require_once 'notificarUsuario.php';

date_default_timezone_set('America/Sao_Paulo');

$agora = date('Y-m-d H:i:s');
$limite = date('Y-m-d H:i:s', strtotime('+35 minutes'));

$sql = "SELECT t.*, a.Nome AS NomeAluno, t.IdColaborador, t.DataHoraExecucao
        FROM tbTarefa t
        JOIN tbAluno a ON t.IdUsuario = a.IdUsuario
        JOIN tbUser u ON t.IdColaborador = u.IdColaborador
        WHERE t.DataHoraExecucao BETWEEN ? AND ?
          AND t.Status = 'pendente'
          AND (t.Notificado IS NULL OR t.Notificado = 0)";

$stmt = $pdo->prepare($sql);
$stmt->execute([$agora, $limite]);
$tarefas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($tarefas)) {
  // Nenhuma tarefa encontrada, mas ainda queremos registrar um log "vazio"
  $stmtLog = $pdo->prepare("INSERT INTO tbLogNotificaTarefa (IdTarefa, TipoEnvio, DataEnvio, Erro) VALUES (NULL, 0, ?, NULL)");
  $stmtLog->execute([$agora]);
} else {
  foreach ($tarefas as $tarefa) {
    try {
      $mensagem = notificarUsuario($pdo, $tarefa['IdColaborador'], 'tarefa', $tarefa['NomeAluno'], $tarefa['DataHoraExecucao']);

      $tipoEnvio = !empty($mensagem) ? 1 : 0;

      // Atualiza tarefa como notificada caso tenha mensagem
      if (!empty($mensagem)) {
        $update = $pdo->prepare("UPDATE tbTarefa SET Notificado = 1 WHERE IdTarefa = ?");
        $update->execute([$tarefa['IdTarefa']]);
      }

      // Registra o log da notificaÒ��§Ò��£o
      $stmtLog = $pdo->prepare("INSERT INTO tbLogNotificaTarefa (IdTarefa, TipoEnvio, DataEnvio, Erro) VALUES (?, ?, ?, NULL)");
      $stmtLog->execute([$tarefa['IdTarefa'], $tipoEnvio, $agora]);

      echo "Tarefa ID {$tarefa['IdTarefa']} processada. Resultado: {$mensagem}\n";
    } catch (Exception $e) {
      // Em caso de erro, salva no log com a mensagem de erro
      $stmtLog = $pdo->prepare("INSERT INTO tbLogNotificaTarefa (IdTarefa, TipoEnvio, DataEnvio, Erro) VALUES (?, NULL, ?, ?)");
      $stmtLog->execute([$tarefa['IdTarefa'], $agora, $e->getMessage()]);
      echo "Erro ao processar tarefa ID {$tarefa['IdTarefa']}: {$e->getMessage()}\n";
    }
  }
}
