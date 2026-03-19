<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_path'];
$BASE_para_URL = $runtime['base_url'];
file_put_contents('log_exec.txt', date('Y-m-d H:i:s') . " Executado\n", FILE_APPEND);
require_once __DIR__ . '/../../api/conectabd/conexao.php';
$idColaborador = 1;
$stmt = $pdo->prepare("SELECT u.Email, u.WhatsApp, n.Nota_Email, n.Nota_WhatsApp, n.Tarefa_Email, n.Tarefa_WhatsApp, u.Nome
                       FROM tbUser u
                       JOIN tbNotificacao n ON u.IdColaborador = n.IdColaborador
                       WHERE u.IdColaborador = ?");

$stmt->execute([$idColaborador]);
$dados = $stmt->fetch(PDO::FETCH_ASSOC);

$nome = $dados['Nome'];
$numero = $dados['WhatsApp'];
$agora = strval(date('d/m/Y H:i:s'));
$mensagem = " OlÒ�� �"Ò�a�¡, " . $nome . ". VocÒ�� �"Ò�a�ª acabou de cadastrar uma nota! (" . $agora . ")";
$url = "https://new-backend.botconversa.com.br/api/v1/webhooks-automation/catch/68204/ku5G1jLkRqZb/";
$numeroLimpo = preg_replace('/[^0-9]/', '', $numero);
// Dados que vocÒ�� �"Ò�a�ª quer enviar
$dados = [
    "nome" => $nome,
    "fone" => $numeroLimpo,
    "mensagem" => $mensagem
];

// Inicializa o cURL
$ch = curl_init($url);

// Configura as opÒ�� �"Ò�a�§Ò�� �"Ò�a�µes da requisiÒ�� �"Ò�a�§Ò�� �"Ò�a�£o
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados)); // Envia os dados em formato JSON
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Executa a requisiÒ�� �"Ò�a�§Ò�� �"Ò�a�£o
$resposta = curl_exec($ch);

// Fecha a conexÒ�� �"Ò�a�£o
curl_close($ch);

// (Opcional) Exibe a resposta do servidor
echo $resposta;
$dados_string = print_r($dados, true) . PHP_EOL; // Adiciona uma nova linha para melhor formataÒ�� �"Ò�a�§Ò�� �"Ò�a�£o

$arquivo = fopen('error.txt', 'a'); // Abre o arquivo em modo de anexaÒ�� �"Ò�a�§Ò�� �"Ò�a�£o

if ($arquivo) {
    fwrite($arquivo, "Dados do array: " . $dados_string);
    fclose($arquivo);
    echo "Dados gravados no arquivo error.txt com sucesso!";
} else {
    echo "Erro ao abrir o arquivo error.txt!";
}
header("Location: obrigado.php");
exit;
