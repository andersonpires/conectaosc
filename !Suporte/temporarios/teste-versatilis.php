<?php

/**
 * Configurações
 */
$baseUrl   = "https://sistema.versatilis.com.br/Iteva"; // ajuste se necessário
$username  = "3618c6fd-1b89-49a5-bd70-d39794f78fac"; // solicitar por e-mail
$password  = "api@versatilis"; // solicitar por e-mail

/**
 * 1) Obter Token (POST /Token)
 */
$tokenUrl = $baseUrl . "/Token";

$postBody = http_build_query([
    "username"   => $username,
    "password"   => $password,
    "grant_type" => "password",
]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $tokenUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postBody,
    CURLOPT_HTTPHEADER     => [
        "Content-Type: text/plain",
        "Accept: application/json",
    ],
]);

$tokenResponse = curl_exec($ch);

if ($tokenResponse === false) {
    throw new Exception("Erro cURL ao obter token: " . curl_error($ch));
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode < 200 || $httpCode >= 300) {
    throw new Exception("Falha ao obter token. HTTP $httpCode. Resposta: " . $tokenResponse);
}

$tokenJson = json_decode($tokenResponse, true);
if (!is_array($tokenJson) || empty($tokenJson["access_token"])) {
    throw new Exception("Resposta do token não contém access_token. Resposta: " . $tokenResponse);
}

$accessToken = $tokenJson["access_token"];


/**
 * 2) Cadastrar usuário (POST /api/Login/CadastrarUsuario)
 */
$cadastrarUrl = $baseUrl . "/api/Login/CadastrarUsuario";

// Monte o payload conforme o contrato da API
$payload = [
    "Nome"           => "Anderson Teste",
    "CPF"            => "912.618.133-91",
    "Email"          => "sem@email.com",
    "Senha"          => md5("123456"), // EXEMPLO: eles estão enviando MD5. Confirme se é isso mesmo.
    "DtNasc"         => "1982-07-18",
    "Sexo"           => "M",
    "Telefone"       => "",
    "Celular"        => "85996164562",
    "CEP"            => "61700000",
    "Endereco"       => "Rua Major José Câmara",
    "Numero"         => "267",
    "Complemento"    => "Teste",
    "Bairro"         => "Centro",
    "Cidade"         => "Eusébio",
    "FacebookID"     => "",
    "SemanasGestacao"=> "",
    "DiasGestacao"   => "",
    "DtParto"        => "",
    "CodPlano"       => "",
    "NumCarteirinha" => "123",
    "CodPlanos"      => [1,2,3,4],
];

$jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $cadastrarUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $jsonPayload,
    CURLOPT_HTTPHEADER     => [
        "Content-Type: application/json",
        "Accept: application/json",
        "Authorization: Bearer " . $accessToken,
    ],
]);

$response = curl_exec($ch);

if ($response === false) {
    throw new Exception("Erro cURL ao cadastrar usuário: " . curl_error($ch));
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Exibe resultado
echo "HTTP: " . $httpCode . PHP_EOL;
echo "Resposta: " . $response . PHP_EOL;
