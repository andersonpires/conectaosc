<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class BiaFlow
{
    private const SESSION_MESSAGES_KEY = 'bia_chat_messages';
    private const SESSION_PREVIOUS_RESPONSE_ID_KEY = 'bia_previous_response_id';

    public function __construct(
        private readonly string $basePath
    ) {
    }

    public function handle(): void
    {
        $nomeUsuario = trim((string)($_SESSION['Nome'] ?? 'Usuário'));
        $duvida = trim((string)($_POST['duvida_usuario'] ?? ''));

        $biaState = [
            'duvida' => $duvida,
            'resposta' => '',
            'erro' => '',
            'nome_usuario' => $nomeUsuario !== '' ? $nomeUsuario : 'Usuário',
            'mensagens' => $this->mensagensSessao(),
        ];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if ($duvida === '') {
                $biaState['erro'] = 'Digite sua dúvida antes de enviar.';
            } else {
                $this->adicionarMensagemSessao('user', $duvida);
                $resultado = $this->gerarResposta($biaState['nome_usuario'], $duvida, false);

                if ($resultado['erro'] !== '') {
                    $biaState['erro'] = $resultado['erro'];
                } else {
                    $biaState['resposta'] = $resultado['texto'];
                    $this->adicionarMensagemSessao('assistant', $resultado['texto']);
                    $this->atualizarPreviousResponseId($resultado['response_id']);
                }

                $biaState['mensagens'] = $this->mensagensSessao();
            }
        }

        require $this->basePath . '/app/views/bia/chat.php';
    }

    public function handleStream(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            require $this->basePath . '/bootstrap/runtime.php';
        }

        $nomeUsuario = trim((string)($_SESSION['Nome'] ?? 'Usuário'));
        if ($nomeUsuario === '') {
            $nomeUsuario = 'Usuário';
        }

        $duvida = trim((string)($_POST['duvida_usuario'] ?? ''));

        $this->iniciarStream();

        if ($duvida === '') {
            $this->enviarEvento('error', 'Digite sua dúvida antes de enviar.');
            exit;
        }

        $this->adicionarMensagemSessao('user', $duvida);
        $resultado = $this->gerarResposta($nomeUsuario, $duvida, true);

        if ($resultado['erro'] !== '') {
            $this->enviarEvento('error', $resultado['erro']);
            exit;
        }

        $this->adicionarMensagemSessao('assistant', $resultado['texto']);
        $this->atualizarPreviousResponseId($resultado['response_id']);
        $this->enviarEvento('done', $resultado['texto']);
        exit;
    }

    public function reset(): void
    {
        unset($_SESSION[self::SESSION_MESSAGES_KEY], $_SESSION[self::SESSION_PREVIOUS_RESPONSE_ID_KEY]);

        $runtime = require $this->basePath . '/bootstrap/runtime.php';
        $baseUrl = rtrim((string)($runtime['base_para_url'] ?? ''), '/');
        header('Location: ' . $baseUrl . '/bia');
        exit;
    }

    /**
     * @return array{texto:string, erro:string, response_id:string}
     */
    private function gerarResposta(string $nomeUsuario, string $duvida, bool $stream): array
    {
        if ($this->duvidaEhGenerica($duvida)) {
            return [
                'texto' => $this->respostaParaDuvidaGenerica($nomeUsuario),
                'erro' => '',
                'response_id' => '',
            ];
        }

        $conteudoReferencia = $this->carregarConteudoReferencia();
        if ($conteudoReferencia === '') {
            return ['texto' => '', 'erro' => 'O conteúdo de ajuda não está disponível no momento.', 'response_id' => ''];
        }

        require_once $this->basePath . '/bootstrap/runtime.php';
        $apiKey = bootstrap_openai_api_key($this->basePath);
        if ($apiKey === '') {
            return ['texto' => '', 'erro' => 'A chave da OpenAI não está configurada.', 'response_id' => ''];
        }

        $payload = [
            'model' => 'gpt-5-nano',
            'store' => true,
            'instructions' => $this->montarPrompt($nomeUsuario, $conteudoReferencia),
            'input' => $duvida,
        ];

        $previousResponseId = $this->previousResponseIdSessao();
        if ($previousResponseId !== '') {
            $payload['previous_response_id'] = $previousResponseId;
        }

        if ($stream) {
            $payload['stream'] = true;
        }

        $resultado = '';
        $erro = '';
        $responseId = '';

        $ch = curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => !$stream,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . trim($apiKey),
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => $stream ? 300 : 90,
        ]);

        if ($stream) {
            $buffer = '';
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($curl, $chunk) use (&$buffer, &$resultado, &$erro, &$responseId): int {
                $buffer .= $chunk;

                while (($pos = strpos($buffer, "\n\n")) !== false) {
                    $bloco = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 2);

                    foreach (explode("\n", $bloco) as $linha) {
                        $linha = trim($linha);
                        if ($linha === '' || !str_starts_with($linha, 'data:')) {
                            continue;
                        }

                        $payloadLinha = trim(substr($linha, 5));
                        if ($payloadLinha === '' || $payloadLinha === '[DONE]') {
                            continue;
                        }

                        $json = json_decode($payloadLinha, true);
                        if (!is_array($json)) {
                            continue;
                        }

                        $responseIdExtraido = $this->extrairResponseId($json);
                        if ($responseIdExtraido !== '') {
                            $responseId = $responseIdExtraido;
                        }

                        $type = (string)($json['type'] ?? '');
                        if ($type === 'response.output_text.delta') {
                            $delta = (string)($json['delta'] ?? '');
                            if ($delta !== '') {
                                $resultado .= $delta;
                                $this->enviarEvento('delta', $this->normalizarTextoResposta($delta));
                            }
                            continue;
                        }

                        if ($type === 'response.completed') {
                            $resultadoCompleto = $this->extrairTextoResposta($json['response'] ?? []);
                            if ($resultadoCompleto !== '') {
                                $resultado = $this->normalizarTextoResposta($resultadoCompleto);
                            }
                            continue;
                        }

                        if ($type === 'error') {
                            $erro = (string)($json['error']['message'] ?? 'Erro desconhecido da OpenAI.');
                        }
                    }
                }

                return strlen($chunk);
            });
        }

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($curlErrno) {
            return ['texto' => '', 'erro' => 'Erro de comunicação com a OpenAI: ' . $curlError, 'response_id' => ''];
        }

        if ($stream) {
            $resultado = $this->ajustarRespostaFinal(
                $this->normalizarTextoResposta($resultado),
                $nomeUsuario,
                $duvida
            );
            if ($httpCode < 200 || $httpCode >= 300) {
                return ['texto' => '', 'erro' => $erro !== '' ? $erro : 'Erro ao gerar a resposta.', 'response_id' => ''];
            }

            if ($resultado === '') {
                return ['texto' => '', 'erro' => $erro !== '' ? $erro : 'A IA não retornou uma resposta válida.', 'response_id' => ''];
            }

            return ['texto' => $resultado, 'erro' => '', 'response_id' => $responseId];
        }

        $data = json_decode((string)$response, true);
        if (!is_array($data)) {
            return ['texto' => '', 'erro' => 'Resposta inválida da OpenAI.', 'response_id' => ''];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return ['texto' => '', 'erro' => (string)($data['error']['message'] ?? 'Erro desconhecido da OpenAI.'), 'response_id' => ''];
        }

        $resultado = $this->ajustarRespostaFinal(
            $this->normalizarTextoResposta($this->extrairTextoResposta($data)),
            $nomeUsuario,
            $duvida
        );
        if ($resultado === '') {
            return ['texto' => '', 'erro' => 'A IA não retornou uma resposta válida.', 'response_id' => ''];
        }

        return [
            'texto' => $resultado,
            'erro' => '',
            'response_id' => $this->extrairResponseId($data),
        ];
    }

    /**
     * @return list<array{role:string, content:string}>
     */
    private function mensagensSessao(): array
    {
        $mensagens = $_SESSION[self::SESSION_MESSAGES_KEY] ?? [];
        if (!is_array($mensagens)) {
            return [];
        }

        $normalizadas = [];
        foreach ($mensagens as $mensagem) {
            if (!is_array($mensagem)) {
                continue;
            }

            $role = (string)($mensagem['role'] ?? '');
            $content = trim((string)($mensagem['content'] ?? ''));
            if ($content === '' || !in_array($role, ['user', 'assistant'], true)) {
                continue;
            }

            $normalizadas[] = [
                'role' => $role,
                'content' => $content,
            ];
        }

        return $normalizadas;
    }

    private function adicionarMensagemSessao(string $role, string $content): void
    {
        $content = trim($content);
        if ($content === '') {
            return;
        }

        $mensagens = $this->mensagensSessao();
        $mensagens[] = [
            'role' => $role,
            'content' => $content,
        ];

        $_SESSION[self::SESSION_MESSAGES_KEY] = array_slice($mensagens, -20);
    }

    private function previousResponseIdSessao(): string
    {
        return trim((string)($_SESSION[self::SESSION_PREVIOUS_RESPONSE_ID_KEY] ?? ''));
    }

    private function atualizarPreviousResponseId(string $responseId): void
    {
        $responseId = trim($responseId);
        if ($responseId !== '') {
            $_SESSION[self::SESSION_PREVIOUS_RESPONSE_ID_KEY] = $responseId;
        }
    }

    private function carregarConteudoReferencia(): string
    {
        require_once $this->basePath . '/bootstrap/runtime.php';

        $caminhoConfigurado = function_exists('bootstrap_env')
            ? trim((string)bootstrap_env('BIA_REFERENCE_FILE', ''))
            : trim((string)(getenv('BIA_REFERENCE_FILE') ?: ''));

        $candidatos = [];
        if ($caminhoConfigurado !== '') {
            $ehAbsoluto = str_starts_with($caminhoConfigurado, '/')
                || preg_match('/^[A-Za-z]:[\\\/]/', $caminhoConfigurado) === 1;

            $candidatos[] = $ehAbsoluto
                ? $caminhoConfigurado
                : $this->basePath . '/' . ltrim(str_replace('\\', '/', $caminhoConfigurado), '/');
        }

        $candidatos[] = $this->basePath . '/temp/FAQ.MD';
        $candidatos[] = $this->basePath . '/temp/FAQ.md';
        $candidatos[] = $this->basePath . '/temp/faq.md';

        foreach (array_unique($candidatos) as $faqPath) {
            if (!is_file($faqPath) || !is_readable($faqPath)) {
                continue;
            }

            $faqContent = file_get_contents($faqPath);
            if (!is_string($faqContent)) {
                continue;
            }

            $faqContent = trim($faqContent);
            if ($faqContent !== '') {
                return $faqContent;
            }
        }

        return '';
    }

    private function iniciarStream(): void
    {
        set_time_limit(0);
        ignore_user_abort(true);

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-transform');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        header('Content-Encoding: none');

        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        ob_implicit_flush(true);
    }

    private function enviarEvento(string $evento, string $data): void
    {
        $data = str_replace(["\r\n", "\r"], "\n", $data);
        echo "event: {$evento}\n";
        foreach (explode("\n", $data) as $linha) {
            echo 'data: ' . $linha . "\n";
        }
        echo "\n";

        if (function_exists('flush')) {
            flush();
        }
    }

    private function normalizarTextoResposta(string $texto): string
    {
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $texto) ?? $texto;
        $texto = preg_replace('/<\s*\/?\s*p\s*>/i', "\n\n", $texto) ?? $texto;
        $texto = preg_replace('/<\s*\/?\s*div\s*>/i', "\n", $texto) ?? $texto;
        $texto = preg_replace('/<\s*(strong|b)\s*>(.*?)<\s*\/\s*\1\s*>/is', '**$2**', $texto) ?? $texto;
        $texto = preg_replace('/<\s*(em|i)\s*>(.*?)<\s*\/\s*\1\s*>/is', '*$2*', $texto) ?? $texto;
        $texto = preg_replace('/<\s*li\s*>/i', "\n- ", $texto) ?? $texto;
        $texto = preg_replace('/<\s*\/\s*li\s*>/i', '', $texto) ?? $texto;
        $texto = preg_replace('/<\s*\/?\s*(ul|ol)\s*>/i', "\n", $texto) ?? $texto;
        $texto = strip_tags($texto);
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/^[ \t]+- /m', '- ', $texto) ?? $texto;
        $texto = preg_replace('/\n[ \t]+/m', "\n", $texto) ?? $texto;
        $texto = preg_replace("/\n{3,}/", "\n\n", $texto) ?? $texto;

        return trim($texto);
    }

    private function normalizarTextoAnalise(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $textoSemAcento = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if (is_string($textoSemAcento) && $textoSemAcento !== '') {
            $texto = mb_strtolower($textoSemAcento, 'UTF-8');
        }
        $texto = preg_replace('/[^\p{L}\p{N}\s\?]/u', ' ', $texto) ?? $texto;

        return preg_replace('/\s+/u', ' ', trim($texto)) ?? trim($texto);
    }

    private function mensagemEhAgradecimento(string $duvida): bool
    {
        $texto = $this->normalizarTextoAnalise($duvida);

        return in_array($texto, [
            'obrigado',
            'obrigada',
            'muito obrigado',
            'muito obrigada',
            'valeu',
            'valeu mesmo',
            'show',
            'perfeito',
            'deu certo',
            'funcionou',
            'entendi obrigado',
            'entendi obrigada',
        ], true);
    }

    private function mensagemEhSaudacao(string $duvida): bool
    {
        $texto = $this->normalizarTextoAnalise($duvida);

        return in_array($texto, ['oi', 'ola', 'bom dia', 'boa tarde', 'boa noite'], true);
    }

    private function duvidaEhGenerica(string $duvida): bool
    {
        $texto = $this->normalizarTextoAnalise($duvida);
        if ($texto === '') {
            return false;
        }

        $frasesGenericas = [
            'ajuda',
            'me ajuda',
            'me ajude',
            'preciso de ajuda',
            'pode me ajudar',
            'voce pode me ajudar',
            'pode ajudar',
            'tenho uma duvida',
            'tenho duvida',
            'duvida',
            'como voce pode ajudar',
            'o que voce faz',
        ];

        return in_array($texto, $frasesGenericas, true);
    }

    private function respostaParaDuvidaGenerica(string $nomeUsuario): string
    {
        $nomeUsuario = trim($nomeUsuario) !== '' ? trim($nomeUsuario) : 'Usu&aacute;rio';

        return $this->normalizarTextoResposta(
            $nomeUsuario . ', me diga qual a&ccedil;&atilde;o voc&ecirc; quer fazer no sistema ou em qual tela voc&ecirc; est&aacute;, que eu sigo com voc&ecirc; no passo a passo.'
        );
    }

    private function respostaParaAgradecimento(string $nomeUsuario): string
    {
        $nomeUsuario = trim($nomeUsuario) !== '' ? trim($nomeUsuario) : 'Usu&aacute;rio';

        return $this->normalizarTextoResposta(
            $nomeUsuario . ', por nada. Se quiser, me diga a pr&oacute;xima a&ccedil;&atilde;o ou a tela em que voc&ecirc; est&aacute; que eu continuo com voc&ecirc;.'
        );
    }

    private function respostaParaSaudacao(string $nomeUsuario): string
    {
        $nomeUsuario = trim($nomeUsuario) !== '' ? trim($nomeUsuario) : 'Usu&aacute;rio';

        return $this->normalizarTextoResposta(
            'Oi, ' . $nomeUsuario . '. Me diga o que voc&ecirc; quer fazer no sistema ou em qual tela voc&ecirc; est&aacute;, e eu te guio.'
        );
    }

    private function ajustarRespostaFinal(string $texto, string $nomeUsuario, string $duvida): string
    {
        $texto = $this->normalizarTextoResposta($texto);
        if ($texto === '') {
            return '';
        }

        if ($this->mensagemEhAgradecimento($duvida)) {
            return $this->respostaParaAgradecimento($nomeUsuario);
        }

        if ($this->mensagemEhSaudacao($duvida)) {
            return $this->respostaParaSaudacao($nomeUsuario);
        }

        if ($this->duvidaEhGenerica($duvida)) {
            return $this->respostaParaDuvidaGenerica($nomeUsuario);
        }

        $textoAnalise = $this->normalizarTextoAnalise($texto);
        $sinaisDeMenu = [
            'qual tarefa voce quer fazer',
            'sugestoes de onde eu posso te ajudar agora',
            'sugestoes de onde posso te ajudar agora',
            'diga qual opcao voce quer seguir',
            'qual tarefa você quer fazer',
            'posso te guiar em',
            'qual voce prefere',
            'qual você prefere',
            'se quiser eu sigo com outra tarefa',
            'aqui vao opcoes',
            'aqui vão opções',
        ];

        foreach ($sinaisDeMenu as $sinal) {
            if (mb_strpos($textoAnalise, $sinal) !== false) {
                return $this->respostaParaDuvidaGenerica($nomeUsuario);
            }
        }

        return $texto;
    }

    private function montarPrompt(string $nomeUsuario, string $conteudoReferencia): string
    {
        return str_replace(
            ['{{NOME_USUARIO}}', '{{CONTEUDO_REFERENCIA}}'],
            [$nomeUsuario, $conteudoReferencia],
            <<<'PROMPT'
Você é Bia, a assistente virtual do sistema.

Sua função é responder às dúvidas do usuário usando exclusivamente o conteúdo de referência fornecido abaixo, que contém explicações e orientações sobre como usar o sistema.

## Identidade e personalidade
- Seu nome é Bia.
- Você é alegre, divertida, espontânea, irônica e inteligente.
- Seu jeito de falar passa a sensação de que você conhece o usuário há anos.
- Você tem pitadas sutis de humor ácido, mas nunca deve ser ofensiva, grosseira ou humilhantemas pode, se precisar, debochar do usuário brincando.
- Seu humor, quando usuário parecer bravo ou chateado, deve soar carismático e acolhedor.
- Você sempre chama o usuário pelo nome informado no sistema.
- Você explica de forma clara, completa e prática, como uma amiga muito esperta ajudando alguém querido, mas sempre de maneira concisa, nada de respostas longas demais. Você pode quebrar a resposta em passos e já orientar o usuário que, caso ele queira, pode passar para próxima etapa que seria... aí você diz qual ou quais seriam.

## Fonte de verdade
- Use somente as informações contidas no conteúdo de referência.
- Não invente passos, funções, regras, telas, botões ou comportamentos que não estejam no conteúdo de referência.
- Se a resposta não estiver clara ou não existir no conteúdo de referência, diga que ainda não foi treinada para essa ação, mas que o usuário pode entrar em contato com desenvolvedor ou suporte através do e-mail iteva@iteva.org.br.
- Nunca finja ter certeza quando não tiver.
- Nunca mencione o nome do arquivo, o nome do material consultado ou que você recebeu um texto-base, haja como conhecedora de tudo.

## Objetivo da resposta
Ao receber a dúvida do usuário:
1. Entenda exatamente o que ele quer fazer.
2. Busque a informação mais relevante no conteúdo de referência.
3. Responda de forma objetiva, completa, mas concisa, dando opção de maior detalhamento.
4. Explique o passo a passo, quando existir, sempre separando em tópicos, usando negrito e marcações que ajudem.
5. Destaque com caixa alta ou negrito cuidados importantes, exceções ou observações relevantes presentes no conteúdo.
6. Se fizer sentido, reorganize a explicação para ficar mais fácil de entender e adaptado à pergunta e contexto solicitado.
7. Sempre mantenha foco em ajudar o usuário a concluir a ação no sistema.

## Estilo de escrita
- Sempre comece chamando o usuário pelo nome.
- Fale em português do Brasil.
- Use linguagem natural, próxima e fluida, podendo usar emojis no início ou final da resposta.
- Evite linguagem robótica, excessivamente técnica ou engessada.
- Sempre que fizer sentido, use frases leves, calor humano e um toque de humor.
- Seja didática: explique o como fazer, onde clicar, o que esperar e, quando houver base para isso, o porquê.
- Prefira parágrafos curtos e boa escaneabilidade.
- Quando houver passo a passo, organize em lista numerada.
- Não use tabelas.
- Se a dúvida do usuário estiver ambígua, responda com base na interpretação mais provável segundo o conteúdo disponível e deixe isso claro de forma natural.

## Expressões da personagem
Quando combinar com o contexto, use naturalmente, preferencialmente no início ou final da resposta, algumas destas expressões:
- "Você é engraçaaaada" (para mulher, quando falar algo que demonstre ela ser uma pessoa difícil, encomodada, engraçada ou diferente do padrão)
- "Você é engraçaaado" (para homem quando falar algo que demonstre ele ser uma pessoa difícil, encomodado, engraçado ou diferente do padrão)
- "Vou te dar um sabôor do meu conhecimento" (essa você pode usar quando ver que a pessoa te pede ajuda literalmente)

Regras para uso dessas expressões:
- Não force o uso em toda resposta.
- Só use quando ficar natural e encaixar no contexto.
- Varie para não ficar repetitivo.
- Nunca use de um jeito que pareça zombaria real.
- Se o gênero do usuário não estiver claro, evite usar a variação de gênero dessa expressão.
- Use essas expressões apenas de vez em quando, quando realmente combinarem com a resposta.
- Evite repetir a mesma expressão em mensagens seguidas da mesma conversa.

## Limites importantes
- Sempre inicie verificando o que o usuário quer, e caso ele não seja específico e pela apenas ajuda ou questione da sua capacidade de responder, brinque com ele falando textos curtos e fazendo rappot.
- As respostas ao usuário sempre devem ser curtas, concisas, entregando apenas o que ele pediu e as vezes, até menos, separando em etapas curtas!
- Não mencione que você acha, supõe ou imagina se o conteúdo não sustentar isso.
- Não cite fontes externas.
- Não diga que consultou internet, base externa, banco de dados ou documentação fora do conteúdo recebido.
- Não responda com informações genéricas fora do conteúdo disponível.
- Se não houver informação suficiente, diga algo como:
  "Olha, [nome], fui até onde eu sei ir sem inventar moda, e essa parte que você pede não está em minha base de conhecimento."
- Se houver informação parcial, entregue o que for possível e deixe claro o limite.

## Estrutura ideal da resposta
Sempre que possível, siga esta lógica:
1. Saudação com o nome do usuário
2. Resposta direta à dúvida
3. Passo a passo ou explicação detalhada, mas concisa
4. Observações úteis
5. Encerramento simpático, podendo dar sugestões para continuar ajudando, mantendo a personalidade da Bia

## Importante para continuidade
- Considere o contexto da conversa anterior quando ele estiver disponível.
- Responda apenas à mensagem mais recente do usuário, mas usando o contexto acumulado para manter continuidade e coerência.

## Variáveis de entrada
Você receberá:
- NOME_USUARIO: nome do usuário no sistema
- CONTEUDO_REFERENCIA: conteúdo completo de referência

### Dados de entrada
NOME_USUARIO: {{NOME_USUARIO}}

CONTEUDO_REFERENCIA:
{{CONTEUDO_REFERENCIA}}

### Reforcos finais de formato
- Responda como texto normal.
- Nunca escreva tags HTML.
- Nunca use <br> ou qualquer outra tag visual.
- Nunca devolva menu numerado de opcoes quando o usuario so pedir ajuda.
- Se precisar listar passos, use apenas numeracao comum em texto.
PROMPT
        );
    }

    /**
     * @param mixed $data
     */
    private function extrairTextoResposta(mixed $data): string
    {
        if (!is_array($data)) {
            return '';
        }

        $resultado = '';
        if (!empty($data['output_text']) && is_string($data['output_text'])) {
            $resultado = trim($data['output_text']);
        }

        if ($resultado !== '') {
            return $resultado;
        }

        if (empty($data['output']) || !is_array($data['output'])) {
            return '';
        }

        foreach ($data['output'] as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (!empty($item['content']) && is_array($item['content'])) {
                foreach ($item['content'] as $content) {
                    if (is_array($content) && !empty($content['text']) && is_string($content['text'])) {
                        $resultado .= $content['text'];
                    }
                }
            }

            if (!empty($item['text']) && is_string($item['text'])) {
                $resultado .= $item['text'];
            }
        }

        return trim($resultado);
    }

    /**
     * @param mixed $data
     */
    private function extrairResponseId(mixed $data): string
    {
        if (!is_array($data)) {
            return '';
        }

        if (!empty($data['response']['id']) && is_string($data['response']['id'])) {
            return trim($data['response']['id']);
        }

        if (!empty($data['id']) && is_string($data['id']) && str_starts_with($data['id'], 'resp_')) {
            return trim($data['id']);
        }

        return '';
    }
}
