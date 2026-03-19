<?php
$basePath = dirname(__DIR__, 2);
require_once $basePath . '/conectabd/conexao.php';

$apikeyPath = $basePath . '/temp/apikey-versatilis.php';
if (file_exists($apikeyPath)) {
    require_once $apikeyPath;
}

class VersatilisService
{
    private static function getConfig()
    {
        global $basePath;

        $config = [
            'base_url' => 'https://sistema.versatilis.com.br/Iteva',
            'username' => $GLOBALS['versatilisUsername'] ?? '',
            'password' => $GLOBALS['versatilisPassword'] ?? '',
            'default_password' => $GLOBALS['versatilisDefaultPassword'] ?? '123456',
            'log_path' => $GLOBALS['versatilisLogPath'] ?? ($basePath . '/temp/versatilis.log'),
        ];

        if (!empty($GLOBALS['versatilisBaseUrl'])) {
            $config['base_url'] = $GLOBALS['versatilisBaseUrl'];
        }

        return $config;
    }

    public static function sincronizarAlunoComVersatilis(array $aluno)
    {
        $config = self::getConfig();
        $cpf = self::formatarCpf($aluno['CPF'] ?? '');

        if ($cpf === '') {
            return [
                'status' => 'ERRO',
                'mensagem' => 'CPF invalido para sincronizar com Versatilis.',
            ];
        }

        $token = self::obterToken($config);
        if ($token === null) {
            return [
                'status' => 'ERRO',
                'mensagem' => 'Falha ao obter token do Versatilis.',
            ];
        }

        $consulta = self::consultarUsuarioPorCpf($config, $token, $cpf);
        if ($consulta['status'] === 200) {
            return [
                'status' => 'JA_EXISTE',
                'mensagem' => 'Usuario ja cadastrado no Versatilis.',
                'cod_usuario' => $consulta['cod_usuario'] ?? null,
            ];
        }

        if ($consulta['status'] !== 404) {
            return [
                'status' => 'ERRO',
                'mensagem' => 'Erro ao consultar usuario no Versatilis.',
            ];
        }

        $payload = self::montarPayloadCadastro($config, $aluno);
        $cadastro = self::cadastrarUsuario($config, $token, $payload);
        if ($cadastro['status'] >= 200 && $cadastro['status'] < 300) {
            return [
                'status' => 'CADASTRADO',
                'mensagem' => 'Usuario cadastrado no Versatilis.',
            ];
        }

        return [
            'status' => 'ERRO',
            'mensagem' => 'Erro ao cadastrar usuario no Versatilis.',
        ];
    }

    private static function obterToken(array $config)
    {
        if ($config['username'] === '' || $config['password'] === '') {
            self::registrarLog([
                'evento' => 'token',
                'erro' => 'Usuario ou senha do Versatilis nao configurados.',
            ]);
            return null;
        }

        $tokenUrl = rtrim($config['base_url'], '/') . '/Token';
        $postBody = http_build_query([
            'username' => $config['username'],
            'password' => $config['password'],
            'grant_type' => 'password',
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $tokenUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postBody,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        self::registrarLog([
            'evento' => 'token',
            'endpoint' => $tokenUrl,
            'status' => $httpCode,
            'erro' => $curlError ?: null,
            'resposta' => $response,
        ]);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            return null;
        }

        $tokenJson = json_decode($response, true);
        if (!is_array($tokenJson) || empty($tokenJson['access_token'])) {
            return null;
        }

        return $tokenJson['access_token'];
    }

    private static function consultarUsuarioPorCpf(array $config, $token, $cpf)
    {
        $url = rtrim($config['base_url'], '/') . '/api/Login/DadosUsuarioPorCPF?UserCPF=' . urlencode($cpf);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $codUsuario = null;
        if ($httpCode === 200) {
            $json = json_decode($response, true);
            if (is_array($json) && isset($json[0]['CodUsuario'])) {
                $codUsuario = $json[0]['CodUsuario'];
            }
        }

        self::registrarLog([
            'evento' => 'consulta',
            'endpoint' => rtrim($config['base_url'], '/') . '/api/Login/DadosUsuarioPorCPF',
            'cpf' => self::mascararCpf($cpf),
            'status' => $httpCode,
            'erro' => $curlError ?: null,
            'resposta' => $response,
        ]);

        return [
            'status' => $httpCode,
            'cod_usuario' => $codUsuario,
        ];
    }

    private static function cadastrarUsuario(array $config, $token, array $payload)
    {
        $url = rtrim($config['base_url'], '/') . '/api/Login/CadastrarUsuario';
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        self::registrarLog([
            'evento' => 'cadastro',
            'endpoint' => $url,
            'status' => $httpCode,
            'erro' => $curlError ?: null,
            'payload' => self::mascararPayload($payload),
            'resposta' => $response,
        ]);

        return [
            'status' => $httpCode,
            'resposta' => $response,
            'erro' => $curlError ?: null,
        ];
    }

    private static function montarPayloadCadastro(array $config, array $aluno)
    {
        $cpf = self::formatarCpf($aluno['CPF'] ?? '');
        $telefone = self::somenteDigitos($aluno['Telefone'] ?? '');
        $celular = self::somenteDigitos($aluno['WhatsApp'] ?? '');

        $email = trim((string) ($aluno['Email'] ?? ''));
        if ($email === '') {
            $email = self::somenteDigitos($cpf) . '@sem-email.com';
        }

        return [
            'Nome' => $aluno['Nome'] ?? '',
            'CPF' => $cpf,
            'Email' => $email,
            'Senha' => md5($config['default_password']),
            'DtNasc' => self::converterData($aluno['Nascimento'] ?? ''),
            'Sexo' => self::converterSexo($aluno['SexoBio'] ?? ''),
            'Telefone' => $telefone,
            'Celular' => $celular,
            'CEP' => '61760000',
            'Endereco' => $aluno['Endereco'] ?? '',
            'Numero' => '0',
            'Complemento' => '',
            'Bairro' => $aluno['Bairro'] ?? '',
            'Cidade' => $aluno['Cidade'] ?? '',
            'FacebookID' => '',
            'SemanasGestacao' => '',
            'DiasGestacao' => '',
            'DtParto' => '',
            'CodPlano' => '',
            'NumCarteirinha' => '',
            'CodPlanos' => [],
        ];
    }

    private static function converterData($data)
    {
        $data = trim((string) $data);
        if ($data === '') {
            return '';
        }

        if (strpos($data, '/') !== false) {
            $partes = explode('/', $data);
            if (count($partes) === 3) {
                $dia = str_pad($partes[0], 2, '0', STR_PAD_LEFT);
                $mes = str_pad($partes[1], 2, '0', STR_PAD_LEFT);
                $ano = $partes[2];
                return $ano . '-' . $mes . '-' . $dia;
            }
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return $data;
        }

        return '';
    }

    private static function converterSexo($sexo)
    {
        $sexo = strtolower(trim((string) $sexo));
        if ($sexo === 'masculino' || $sexo === 'm') {
            return 'M';
        }
        if ($sexo === 'feminino' || $sexo === 'feminimo' || $sexo === 'f') {
            return 'F';
        }
        return '';
    }

    private static function somenteDigitos($valor)
    {
        return preg_replace('/\D/', '', (string) $valor);
    }

    private static function formatarCpf($cpf)
    {
        $cpf = self::somenteDigitos($cpf);
        if (strlen($cpf) !== 11) {
            return '';
        }

        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }

    private static function mascararPayload(array $payload)
    {
        $mascarado = $payload;
        if (!empty($mascarado['CPF'])) {
            $mascarado['CPF'] = self::mascararCpf($mascarado['CPF']);
        }
        if (!empty($mascarado['Email'])) {
            $mascarado['Email'] = self::mascararEmail($mascarado['Email']);
        }
        if (isset($mascarado['Senha'])) {
            $mascarado['Senha'] = '***';
        }

        return $mascarado;
    }

    private static function mascararCpf($cpf)
    {
        $cpf = self::somenteDigitos($cpf);
        if (strlen($cpf) !== 11) {
            return '***';
        }
        return substr($cpf, 0, 3) . '.***.***-' . substr($cpf, 9, 2);
    }

    private static function mascararEmail($email)
    {
        $email = (string) $email;
        $pos = strpos($email, '@');
        if ($pos === false) {
            return '***';
        }
        return substr($email, 0, 2) . '***' . substr($email, $pos);
    }

    private static function registrarLog(array $dados)
    {
        $config = self::getConfig();
        $dados['timestamp'] = date('Y-m-d H:i:s');
        $linha = json_encode($dados, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        @file_put_contents($config['log_path'], $linha, FILE_APPEND);
    }
}
