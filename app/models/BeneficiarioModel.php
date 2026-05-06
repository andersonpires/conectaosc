<?php
$runtime = require __DIR__ . '/../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
$apikeyTwilio = bootstrap_env('TWILIO_API_CREDENTIALS', '');
$twilioAccountSid = bootstrap_env('TWILIO_ACCOUNT_SID', '');
$twilioAuthToken = bootstrap_env('TWILIO_AUTH_TOKEN', '');
$twilioFromNumber = bootstrap_env('TWILIO_FROM_NUMBER', '+14632596685');
if ($apikeyTwilio === '' && $twilioAccountSid !== '' && $twilioAuthToken !== '') {
    $apikeyTwilio = $twilioAccountSid . ':' . $twilioAuthToken;
}
if ($twilioAccountSid === '' && str_contains($apikeyTwilio, ':')) {
    [$twilioAccountSid] = explode(':', $apikeyTwilio, 2);
}
require_once $BASE_para_PATH . '/app/views/beneficiario/versatilis.php';

class BeneficiarioModel
{

    public static function getRead($cpf = null, $id = null)
    {
        global $pdo, $apikeyTwilio, $twilioAccountSid, $twilioFromNumber;

        if (!empty($cpf)) {
            $stmt = $pdo->prepare("
            SELECT * FROM tbAluno 
            WHERE REPLACE(REPLACE(REPLACE(CPF, '.', ''), '-', ''), ' ', '') = ?
              AND Habilitado = 1
        ");
            $stmt->execute([$cpf]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!empty($id)) {
            $stmt = $pdo->prepare("
            SELECT * FROM tbAluno 
            WHERE IdUsuario = ?
              AND Habilitado = 1
        ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return false;
    }



    public static function create($dados)
    {
        global $pdo;

        // Garante campos obrigatórios
        $dados['IdTipo'] = $dados['IdTipo'] ?? 1;
        $dados['Habilitado'] = $dados['Habilitado'] ?? 1;
        $dados['Foto'] = $dados['Foto'] ?? 'padrao.jfif';

        // Normalização dos campos de moeda
        if (isset($dados['RendaMensal'])) {
            $dados['RendaMensal'] = self::limparMoeda($dados['RendaMensal']);
        }
        if (isset($dados['RendaFamiliar'])) {
            $dados['RendaFamiliar'] = self::limparMoeda($dados['RendaFamiliar']);
        }


        // Obtem os nomes e tipos das colunas da tabela tbAluno
        $colunasValidas = [];
        $tiposColunas = [];
        $stmt = $pdo->query("DESCRIBE tbAluno");
        while ($linha = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $colunasValidas[] = $linha['Field'];
            $tiposColunas[$linha['Field']] = $linha['Type'];
        }

        // Filtra apenas os campos existentes na tabela
        $dadosFiltrados = array_filter($dados, function ($key) use ($colunasValidas) {
            return in_array($key, $colunasValidas);
        }, ARRAY_FILTER_USE_KEY);

        // Prepara campos e valores
        $campos = array_keys($dadosFiltrados);
        $valores = [];

        foreach ($dadosFiltrados as $campo => $valor) {
            if (is_array($valor)) {
                $valores[] = implode(',', $valor);
            } elseif ($valor === '' && preg_match('/^(tinyint|smallint|mediumint|int|bigint)/i', $tiposColunas[$campo] ?? '')) {
                $valores[] = null;
            } else {
                $valores[] = $valor;
            }
        }

        $colunas = implode(',', array_map(fn($campo) => "`$campo`", $campos));
        $placeholders = implode(',', array_fill(0, count($campos), '?'));

        // Monta e executa o SQL
        $sql = "INSERT INTO tbAluno ($colunas) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($valores);

        // return $pdo->lastInsertId(); // Retorna ID inserido
        $idInserido = $pdo->lastInsertId(); // pega ID inserido

        // --------------------------------------------------
        // ENVIO DE SMS VIA TWILIO APOS O INSERT
        // --------------------------------------------------

        $telefone = $dadosFiltrados['WhatsApp'] ?? $dadosFiltrados['Telefone'] ?? null;

        if (!empty($telefone) && !empty($apikeyTwilio) && function_exists('curl_init')) {

            // Remove qualquer caractere nao numerico
            $telefone = preg_replace('/\D/', '', $telefone);

            // Twilio exige numero no formato +55DDDNUMERO
            // Ajuste conforme sua necessidade:
            if (strlen($telefone) == 11) {
                $telefone = "+55" . $telefone;
            }

            // Dados do SMS
            if ($twilioAccountSid === '') {
                error_log('TWILIO_ACCOUNT_SID não configurado.');
                return $idInserido;
            }

            $url = "https://api.twilio.com/2010-04-01/Accounts/{$twilioAccountSid}/Messages.json";
            $from = $twilioFromNumber;
            $body = "Olá, " . $dadosFiltrados['Nome'] . ", temos uma ótima notícia: seu cadastro no Iteva foi um sucesso!";

            // Prepara os dados para envio
            $payload = http_build_query([
                "To" => $telefone,
                "From" => $from,
                "Body" => $body
            ]);

            // Inicia o CURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Autenticacao Twilio
            curl_setopt($ch, CURLOPT_USERPWD, $apikeyTwilio);

            // Executa
            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            unset($ch);

            if (!empty($curlError)) {
                error_log("Erro ao enviar SMS Twilio: " . $curlError);
            }
        }

        // --------------------------------------------------
        // FIM DO BLOCO DO SMS
        // --------------------------------------------------

        return $idInserido;
    }

    public static function salvarInteresses($idUsuario, $idsProjetos)
    {
        global $pdo;

        // Garante que os IDs sejam inteiros validos
        $idsProjetos = array_map('intval', $idsProjetos);

        // Busca todos os projetos validos na tabela tbProjeto
        $stmt = $pdo->query("SELECT IdProjeto FROM tbProjeto");
        $todosProjetos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Prepara a query com ON DUPLICATE KEY para atualizar caso ja exista
        $sql = "INSERT INTO tbInteresse (IdProjeto, IdUsuario, Valor)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE Valor = VALUES(Valor)";
        $stmtInsert = $pdo->prepare($sql);

        foreach ($todosProjetos as $idProjeto) {
            $valor = in_array((int)$idProjeto, $idsProjetos) ? 1 : 0;
            $stmtInsert->execute([$idProjeto, $idUsuario, $valor]);
        }
    }


    public static function update($dados)
    {
        global $pdo;

        // Obtem as colunas validas e seus tipos da tabela
        $colunasValidas = [];
        $tiposColunas = [];
        $stmt = $pdo->query("DESCRIBE tbAluno");
        while ($linha = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $colunasValidas[] = $linha['Field'];
            $tiposColunas[$linha['Field']] = $linha['Type'];
        }

        $camposAtualizar = [];
        $valores = [];

        // Normalizacao dos campos de moeda (antes do foreach)
        if (isset($dados['RendaMensal'])) {
            $dados['RendaMensal'] = self::limparMoeda($dados['RendaMensal']);
        }
        if (isset($dados['RendaFamiliar'])) {
            $dados['RendaFamiliar'] = self::limparMoeda($dados['RendaFamiliar']);
        }


        foreach ($dados as $coluna => $valor) {
            if ($coluna !== 'IdUsuario' && in_array($coluna, $colunasValidas)) {
                $camposAtualizar[] = "`$coluna` = ?";
                if (is_array($valor)) {
                    $valores[] = implode(',', $valor);
                } elseif ($valor === '' && preg_match('/^(tinyint|smallint|mediumint|int|bigint)/i', $tiposColunas[$coluna] ?? '')) {
                    $valores[] = null;
                } else {
                    $valores[] = $valor;
                }
            }
        }


        $valores[] = $dados['IdUsuario'];


        if (empty($dados['IdUsuario']) || !is_numeric($dados['IdUsuario'])) {
            return false;
        }

        if (empty($camposAtualizar)) {
            // nada para atualizar
            return true; // ou false, voce decide
        }

        $sql = "UPDATE tbAluno SET " . implode(', ', $camposAtualizar) . " WHERE IdUsuario = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($valores);
    }

    public static function salvarComVersatilis($dados)
    {
        global $pdo;

        $cpf = preg_replace('/\D/', '', $dados['CPF'] ?? '');
        $idUsuario = !empty($dados['IdUsuario']) ? (int) $dados['IdUsuario'] : null;

        $alunoExistente = self::getRead($cpf ?: null, $idUsuario);
        if ($alunoExistente && (int) ($alunoExistente['Versatilis'] ?? 0) === 1) {
            return [
                'ok' => false,
                'status' => 'JA_SINCRONIZADO',
                'mensagem' => 'Este usuario ja foi cadastrado no Versatilis. Qualquer alteracao deve ser feita diretamente no sistema.',
            ];
        }

        $alunoParaSync = $alunoExistente ? array_merge($alunoExistente, $dados) : $dados;
        $sync = VersatilisService::sincronizarAlunoComVersatilis($alunoParaSync);

        if ($sync['status'] === 'JA_EXISTE') {
            if ($alunoExistente && !empty($alunoExistente['IdUsuario'])) {
                $stmt = $pdo->prepare('UPDATE tbAluno SET Versatilis = 1 WHERE IdUsuario = ?');
                $stmt->execute([$alunoExistente['IdUsuario']]);
            }

            return [
                'ok' => false,
                'status' => 'JA_EXISTE',
                'mensagem' => 'Usuario ja cadastrado no Versatilis. Qualquer alteracao deve ser feita diretamente no sistema.',
            ];
        }

        if ($sync['status'] !== 'CADASTRADO') {
            return [
                'ok' => false,
                'status' => 'ERRO',
                'mensagem' => $sync['mensagem'] ?? 'Erro ao sincronizar com Versatilis.',
            ];
        }

        $dados['Versatilis'] = 1;

        if ($idUsuario) {
            $salvou = self::update($dados);
            return [
                'ok' => (bool) $salvou,
                'status' => $salvou ? 'ATUALIZADO' : 'ERRO',
                'idUsuario' => $idUsuario,
                'tipo' => 'update',
                'mensagem' => $salvou ? 'Registro atualizado com sucesso!' : 'Erro ao atualizar registro.',
            ];
        }

        $novoId = self::create($dados);
        return [
            'ok' => (bool) $novoId,
            'status' => $novoId ? 'CRIADO' : 'ERRO',
            'idUsuario' => $novoId,
            'tipo' => 'create',
            'mensagem' => $novoId ? 'Cadastro realizado com sucesso!' : 'Erro ao cadastrar registro.',
        ];
    }


    public static function delete($idUsuario)
    {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE tbAluno SET Habilitado = 0 WHERE IdUsuario = ?");
        return $stmt->execute([$idUsuario]);
    }

    private static function limparMoeda($valor)
    {
        if ($valor === null || $valor === "") return null;

        // Remove pontos de milhar e troca virgula por ponto
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);

        return $valor;
    }
}



