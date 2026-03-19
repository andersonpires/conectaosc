<?php
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '../../../api/conectabd/conexao.php';

function notificarUsuario($conecta, $idColaborador, $tipo, $alunoNotificado, $dataExecucao = null)
{
    $stmt = $conecta->prepare("SELECT u.Email, u.WhatsApp, n.Nota_Email, n.Nota_WhatsApp, n.Tarefa_Email, n.Tarefa_WhatsApp, u.Nome
                       FROM tbUser u
                       JOIN tbNotificacao n ON u.IdColaborador = n.IdColaborador
                       WHERE u.IdColaborador = ?");

    $stmt->execute([$idColaborador]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dados) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Colaborador nÒ��£o encontrado.']);
        return;
    }

    $nome = $dados['Nome'] ? 'UsuÒ��¡rio';
    $mensagemTexto = '';
    $mensagens = [];

    // URL do webhook
    $stmt2 = $conecta->query("SELECT APIzap FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $url = $stmt2['APIzap'];

    if ($tipo === 'nota') {
        if ($dados['Nota_Email']) {
            $email = $dados['Email'];
            $agora = strval(date('d/m/Y H:i:s'));
            $mensagemHtml = "<p>OlÒ��¡, <strong>$nome</strong>. VocÒ��ª acabou de cadastrar uma <strong>$tipo</strong> para <strong>" . trim($alunoNotificado) . "</strong>.</p><p>Data e hora: <strong>$agora</strong></p>";
            $mensagens[] = enviarEmail($nome, $email, $mensagemHtml, $tipo);
        }

        if ($dados['Nota_WhatsApp']) {
            $numero = $dados['WhatsApp'];
            $agora = strval(date('d/m/Y H:i:s'));
            $mensagemTexto = " OlÒ��¡, " . $nome . ". VocÒ��ª acabou de cadastrar uma " . $tipo . " para *" . trim($alunoNotificado) . "*. (" . $agora . ")";
            $mensagens[] = enviarWhatsApp($numero, $nome, $mensagemTexto, $url);
        }
    } elseif ($tipo === 'tarefa') {
        $execucaoFormatada = $dataExecucao ? date('d/m/Y H:i', strtotime($dataExecucao)) : 'em breve';
        $mensagemTexto = "OlÒ��¡, $nome. VocÒ��ª tem uma tarefa agendada para *" . trim($alunoNotificado) . "* que deverÒ��¡ executar em *$execucaoFormatada*.";
        $mensagemHtml = "<p>OlÒ��¡, <strong>$nome</strong>. VocÒ��ª tem uma tarefa agendada para <strong>" . trim($alunoNotificado) . "</strong> que deverÒ��¡ ser executada em <strong>$execucaoFormatada</strong>.</p>";


        if ($dados['Tarefa_Email']) {
            $email = $dados['Email'];
            $mensagens[] = enviarEmail($nome, $email, $mensagemHtml, $tipo);
        }
        if ($dados['Tarefa_WhatsApp']) {
            $numero = $dados['WhatsApp'];
            $mensagens[] = enviarWhatsApp($numero, $nome, $mensagemTexto, $url);
        }
    }

    return implode('<br> ', $mensagens);
}

function enviarWhatsApp($numero, $nome, $mensagem, $url)
{
    $numeroLimpo = preg_replace('/[^0-9]/', '', $numero);
    // Dados que vocÒ��ª quer enviar
    $dados = [
        "nome" => $nome,
        "fone" => $numeroLimpo,
        "mensagem" => $mensagem
    ];

    // Inicializa o cURL
    $ch = curl_init($url);

    // Configura as opÒ��§Ò��µes da requisiÒ��§Ò��£o
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados)); // Envia os dados em formato JSON
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Executa a requisiÒ��§Ò��£o
    $resposta = curl_exec($ch);

    // Fecha a conexÒ��£o
    curl_close($ch);

    // Exibe a resposta do servidor
    $respostaDecode = json_decode($resposta, true);
    if (is_array($respostaDecode) && empty($respostaDecode)) {
        return "WhatsApp enviado com sucesso.";
    }
    return "Erro ao enviar WhatsApp: " . $resposta;
}

function enviarEmail($nomeCad, $emailCad, $mensagemHtml, $tipo)
{
    $para = $emailCad;
    if ($tipo == 'nota') {
        $assunto = "Sistema ITEVA - NotificaÒ��§Ò��£o";
    } else {
        $assunto = "Sistema ITEVA - TAREFA para vocÒ��ª";
    }
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: iteva@iteva.org.br\r\n";

    if (@mail($para, $assunto, $mensagemHtml, $headers)) {
        return "E-mail enviado com sucesso.";
    } else {
        $erro = error_get_last();
        return "Erro ao enviar e-mail: " . ($erro['message'] ? 'erro desconhecido');
    }
}
