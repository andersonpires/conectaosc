<?php $runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

require_once __DIR__ . '/../../../api/repositories/CrmRepository.php';
require_once __DIR__ . '/../../../api/services/CrmService.php';

use BackEnd\Repositories\CrmRepository;
use BackEnd\Services\CrmService;

function notificarUsuario($conecta, $idColaborador, $tipo, $alunoNotificado, $dataExecucao = null)
{
    $service = new CrmService(new CrmRepository());
    $dados = $service->dadosNotificacaoColaborador((int)$idColaborador);

    if (!$dados) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Colaborador nao encontrado.']);
        return '';
    }

    $nome = $dados['Nome'] ?? 'Usuario';
    $mensagens = [];
    $url = $service->apiZap() ?? '';

    if ($tipo === 'nota') {
        if (!empty($dados['Nota_Email'])) {
            $email = $dados['Email'] ?? '';
            $agora = (string)date('d/m/Y H:i:s');
            $mensagemHtml = "<p>Olá, <strong>$nome</strong>. Você acabou de cadastrar uma <strong>$tipo</strong> para <strong>" . trim((string)$alunoNotificado) . "</strong>.</p><p>Data e hora: <strong>$agora</strong></p>";
            $mensagens[] = enviarEmail($nome, $email, $mensagemHtml, $tipo);
        }

        if (!empty($dados['Nota_WhatsApp'])) {
            $numero = $dados['WhatsApp'] ?? '';
            $agora = (string)date('d/m/Y H:i:s');
            $mensagemTexto = " Ola, $nome. Voce acabou de cadastrar uma $tipo para *" . trim((string)$alunoNotificado) . "*. ($agora)";
            $mensagens[] = enviarWhatsApp($numero, $nome, $mensagemTexto, $url);
        }
    } elseif ($tipo === 'tarefa') {
        $execucaoFormatada = $dataExecucao ? date('d/m/Y H:i', strtotime((string)$dataExecucao)) : 'em breve';
        $mensagemTexto = "Ola, $nome. Voce tem uma tarefa agendada para *" . trim((string)$alunoNotificado) . "* que devera executar em *$execucaoFormatada*.";
        $mensagemHtml = "<p>Olá, <strong>$nome</strong>. Você tem uma tarefa agendada para <strong>" . trim((string)$alunoNotificado) . "</strong> que deverá ser executada em <strong>$execucaoFormatada</strong>.</p>";

        if (!empty($dados['Tarefa_Email'])) {
            $email = $dados['Email'] ?? '';
            $mensagens[] = enviarEmail($nome, $email, $mensagemHtml, $tipo);
        }
        if (!empty($dados['Tarefa_WhatsApp'])) {
            $numero = $dados['WhatsApp'] ?? '';
            $mensagens[] = enviarWhatsApp($numero, $nome, $mensagemTexto, $url);
        }
    } elseif ($tipo === 'whatsapp_assinatura') {
        $numero = $dados['WhatsApp'] ?? '';
        if ($numero !== '') {
            $mensagens[] = enviarWhatsApp($numero, $nome, (string)$alunoNotificado, $url);
        }
    }

    return implode('<br> ', $mensagens);
}

function enviarWhatsApp($numero, $nome, $mensagem, $url)
{
    if (trim((string)$url) === '') {
        return 'API WhatsApp nao configurada.';
    }

    $numeroLimpo = preg_replace('/[^0-9]/', '', (string)$numero);
    $dados = [
        'nome' => $nome,
        'fone' => $numeroLimpo,
        'mensagem' => $mensagem
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $resposta = curl_exec($ch);
    curl_close($ch);

    $respostaDecode = json_decode((string)$resposta, true);
    if (is_array($respostaDecode) && empty($respostaDecode)) {
        return 'WhatsApp enviado com sucesso.';
    }
    return 'Erro ao enviar WhatsApp: ' . $resposta;
}

function enviarEmail($nomeCad, $emailCad, $mensagemHtml, $tipo)
{
    $para = $emailCad;
    $assunto = ($tipo === 'nota') ? 'Sistema ITEVA - Notificação' : 'Sistema ITEVA - TAREFA para Você';

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: iteva@iteva.org.br\r\n";

    if (@mail($para, $assunto, $mensagemHtml, $headers)) {
        return 'E-mail enviado com sucesso.';
    }

    $erro = error_get_last();
    return 'Erro ao enviar e-mail: ' . ($erro['message'] ?? 'erro desconhecido');
}






