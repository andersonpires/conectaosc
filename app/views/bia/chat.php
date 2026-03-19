<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$usuarioLogado = !empty($_SESSION['Cod']);
$nomeUsuarioTela = htmlspecialchars((string)($biaState['nome_usuario'] ?? 'Usuário'), ENT_QUOTES, 'UTF-8');
$erroTela = htmlspecialchars((string)($biaState['erro'] ?? ''), ENT_QUOTES, 'UTF-8');
$mensagens = is_array($biaState['mensagens'] ?? null) ? $biaState['mensagens'] : [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php require $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <style>
        :root {
            --ajuda-bg: #f5f7fb;
            --ajuda-surface: #ffffff;
            --ajuda-border: #e5e7eb;
            --ajuda-text: #111827;
            --ajuda-muted: #6b7280;
            --ajuda-primary: #111827;
            --ajuda-primary-soft: #eef2ff;
            --ajuda-user: #111827;
            --ajuda-user-text: #ffffff;
        }
        .ajuda-shell { max-width: 980px; margin: 0 auto; }
        .ajuda-public-topbar { background: rgba(255,255,255,.92); backdrop-filter: blur(12px); border-bottom: 1px solid var(--ajuda-border); }
        .ajuda-board { min-height: calc(100vh - 220px); display: flex; flex-direction: column; gap: 1.25rem; }
        .ajuda-head { padding: 1.25rem 0 .25rem; }
        .ajuda-title { font-size: clamp(1.8rem, 3vw, 2.7rem); line-height: 1.05; letter-spacing: -.04em; margin: 0; color: var(--ajuda-text); }
        .ajuda-subtitle { color: var(--ajuda-muted); max-width: 42rem; margin-top: .75rem; }
        .ajuda-chat { display: flex; flex-direction: column; gap: 1rem; padding-bottom: .75rem; }
        .ajuda-message { display: flex; gap: .85rem; align-items: flex-start; }
        .ajuda-message.user { justify-content: flex-end; }
        .ajuda-avatar { width: 2.5rem; height: 2.5rem; border-radius: .85rem; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; flex: 0 0 auto; background: var(--ajuda-primary-soft); color: var(--ajuda-primary); }
        .ajuda-message.user .ajuda-avatar { order: 2; background: #dbeafe; color: #1d4ed8; }
        .ajuda-bubble { max-width: min(760px, 100%); padding: 1rem 1.1rem; border-radius: 1.2rem; border: 1px solid var(--ajuda-border); background: var(--ajuda-surface); color: var(--ajuda-text); box-shadow: 0 14px 34px rgba(15,23,42,.05); word-break: break-word; line-height: 1.65; }
        .ajuda-message.user .ajuda-bubble { background: var(--ajuda-user); color: var(--ajuda-user-text); border-color: var(--ajuda-user); white-space: pre-wrap; }
        .ajuda-bubble p { margin: 0 0 .85rem; }
        .ajuda-bubble p:last-child { margin-bottom: 0; }
        .ajuda-bubble ul,
        .ajuda-bubble ol { margin: .35rem 0 .9rem 1.25rem; padding: 0; }
        .ajuda-bubble li + li { margin-top: .25rem; }
        .ajuda-bubble strong { font-weight: 700; }
        .ajuda-bubble em { font-style: italic; }
        .ajuda-empty { border: 1px dashed var(--ajuda-border); border-radius: 1.4rem; padding: 2rem; background: rgba(255,255,255,.7); color: var(--ajuda-muted); }
        .ajuda-compose-wrap { position: sticky; bottom: 1rem; z-index: 5; margin-top: auto; }
        .ajuda-compose { background: rgba(255,255,255,.92); border: 1px solid var(--ajuda-border); border-radius: 1.5rem; box-shadow: 0 24px 60px rgba(15,23,42,.12); padding: .9rem; backdrop-filter: blur(14px); }
        .ajuda-textarea { width: 100%; border: 0; background: transparent; min-height: 84px; max-height: 260px; resize: none; color: var(--ajuda-text); padding: .35rem .2rem; }
        .ajuda-textarea:focus { outline: none; box-shadow: none; }
        .ajuda-actions { display: flex; justify-content: space-between; align-items: center; gap: .75rem; margin-top: .5rem; }
        .ajuda-meta { color: var(--ajuda-muted); font-size: .92rem; }
        .ajuda-submit { min-width: 140px; border-radius: 999px; padding-inline: 1rem; }
        .ajuda-status { min-height: 1.5rem; color: var(--ajuda-muted); font-size: .92rem; }
        .ajuda-cursor::after { content: ""; display: inline-block; width: .6ch; height: 1.05em; margin-left: .14rem; vertical-align: -.12em; background: currentColor; animation: ajudaBlink 1s steps(1) infinite; }
        @keyframes ajudaBlink { 50% { opacity: 0; } }
        @media (max-width: 767.98px) {
            .ajuda-shell { max-width: 100%; }
            .ajuda-compose-wrap { bottom: .5rem; }
            .ajuda-compose { border-radius: 1.2rem; }
            .ajuda-actions { flex-direction: column; align-items: stretch; }
            .ajuda-submit { width: 100%; }
        }
    </style>
</head>
<body style="background: var(--ajuda-bg);">
<?php if ($usuarioLogado): ?>
    <div class="wrapper">
        <?php require $BASE_para_PATH . '/app/views/partials/menu.php'; ?>
        <div class="main">
            <?php require $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
            <main class="content">
<?php else: ?>
    <div class="ajuda-public-topbar py-3 mb-3">
        <div class="container-fluid ajuda-shell d-flex justify-content-between align-items-center">
            <strong>Ajuda</strong>
            <a href="<?php echo htmlspecialchars(rtrim((string)$BASE_para_URL, '/'), ENT_QUOTES, 'UTF-8'); ?>/login" class="btn btn-outline-dark btn-sm">Entrar</a>
        </div>
    </div>
    <main class="content py-2">
<?php endif; ?>
                <div class="container-fluid">
                    <div class="ajuda-shell ajuda-board">
                        <section class="ajuda-head">
                            <h1 class="ajuda-title">Como posso te ajudar hoje?</h1>
                            <p class="ajuda-subtitle">Escreva sua dúvida sobre o sistema e receba a resposta em tempo real.</p>
                        </section>

                        <section class="ajuda-chat" id="ajudaChat">
                            <?php if ($mensagens === [] && $erroTela === ''): ?>
                                <div class="ajuda-empty" id="ajudaEmpty">
                                    <strong>Ajuda rápida, sem rodeio.</strong><br>
                                    Pergunte como fazer uma ação no sistema e a Bia organiza a explicação para você.
                                </div>
                            <?php endif; ?>

                            <?php foreach ($mensagens as $mensagem): ?>
                                <?php
                                $isUser = ($mensagem['role'] ?? '') === 'user';
                                $textoMensagem = (string)($mensagem['content'] ?? '');
                                ?>
                                <div class="ajuda-message<?php echo $isUser ? ' user' : ''; ?>">
                                    <?php if ($isUser): ?>
                                        <div class="ajuda-bubble"><?php echo htmlspecialchars($textoMensagem, ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="ajuda-avatar"><?php echo mb_substr($nomeUsuarioTela, 0, 1, 'UTF-8'); ?></div>
                                    <?php else: ?>
                                        <div class="ajuda-avatar">B</div>
                                        <div class="ajuda-bubble" data-assistant-content="1"><?php echo htmlspecialchars($textoMensagem, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <?php if ($erroTela !== ''): ?>
                                <div class="alert alert-danger mb-0" role="alert"><?php echo $erroTela; ?></div>
                            <?php endif; ?>
                        </section>

                        <div class="ajuda-compose-wrap">
                            <form method="post" action="<?php echo htmlspecialchars(rtrim((string)$BASE_para_URL, '/'), ENT_QUOTES, 'UTF-8'); ?>/bia" id="biaForm" class="ajuda-compose">
                                <textarea class="ajuda-textarea" id="duvida_usuario" name="duvida_usuario" placeholder="Pergunte qualquer coisa sobre o uso do sistema..." autofocus></textarea>
                                <div class="ajuda-actions">
                                    <div class="ajuda-meta">A Bia responde em português e vai escrevendo enquanto pensa.</div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary" id="btnNovaConversa">Nova conversa</button>
                                        <button type="button" class="btn btn-light" id="btnLimparBia">Limpar</button>
                                        <button type="submit" class="btn btn-dark ajuda-submit" id="btnEnviarBia"><span class="btn-label">Enviar</span></button>
                                    </div>
                                </div>
                                <div class="ajuda-status mt-2" id="ajudaStatus" aria-live="polite"></div>
                            </form>
                        </div>
                    </div>
                </div>
<?php if ($usuarioLogado): ?>
            </main>
            <footer class="footer">
                <?php require $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>
<?php else: ?>
    </main>
    <footer class="footer py-4">
        <?php require $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
    </footer>
<?php endif; ?>

<script src="<?php echo htmlspecialchars((string)$BASE_para_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/js/app.js"></script>
<script>
    (function () {
        const form = document.getElementById('biaForm');
        const chat = document.getElementById('ajudaChat');
        const status = document.getElementById('ajudaStatus');
        const textarea = document.getElementById('duvida_usuario');
        const botao = document.getElementById('btnEnviarBia');
        const botaoLimpar = document.getElementById('btnLimparBia');
        const botaoNovaConversa = document.getElementById('btnNovaConversa');
        const emptyState = document.getElementById('ajudaEmpty');
        const nomeUsuario = <?php echo json_encode((string)($biaState['nome_usuario'] ?? 'Usuário'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        let respostaAtual = null;
        let carregandoInterval = null;
        let recebeuPrimeiroDelta = false;
        let bufferVisual = '';

        const mensagensCarregando = [
            'Analisando pergunta...',
            'Organizando o contexto...',
            'Buscando a melhor resposta...',
            'Conferindo os detalhes...',
            'Montando a explicação...'
        ];
        let filaMensagensCarregando = [];

        function scrollChat() {
            window.requestAnimationFrame(() => {
                window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
            });
        }

        function criarMensagem(texto, tipo) {
            const wrapper = document.createElement('div');
            wrapper.className = tipo === 'user' ? 'ajuda-message user' : 'ajuda-message';

            const avatar = document.createElement('div');
            avatar.className = 'ajuda-avatar';
            avatar.textContent = tipo === 'user' ? (nomeUsuario.trim().charAt(0) || 'U').toUpperCase() : 'B';

            const bubble = document.createElement('div');
            bubble.className = 'ajuda-bubble';
            if (tipo === 'assistant') {
                bubble.setAttribute('data-assistant-content', '1');
                bubble.dataset.rawText = '';
            }
            bubble.textContent = texto || '';

            if (tipo === 'user') {
                wrapper.appendChild(bubble);
                wrapper.appendChild(avatar);
            } else {
                wrapper.appendChild(avatar);
                wrapper.appendChild(bubble);
            }

            chat.appendChild(wrapper);
            scrollChat();
            return bubble;
        }

        function escaparHtml(texto) {
            return String(texto)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function sanitizarTextoAssistente(texto) {
            let textoNormalizado = String(texto || '')
                .replace(/\r\n?/g, '\n')
                .replace(/&lt;br\s*\/?&gt;/gi, '\n')
                .replace(/&#60;br\s*\/?&#62;/gi, '\n')
                .replace(/&#x3c;br\s*\/?&#x3e;/gi, '\n')
                .replace(/<br\s*\/?>/gi, '\n')
                .replace(/<\/?(p|div)\s*>/gi, '\n\n')
                .replace(/<(strong|b)>([\s\S]*?)<\/\1>/gi, '**$2**')
                .replace(/<(em|i)>([\s\S]*?)<\/\1>/gi, '*$2*')
                .replace(/<li\s*>/gi, '\n- ')
                .replace(/<\/li>/gi, '')
                .replace(/<\/?(ul|ol)\s*>/gi, '\n')
                .replace(/<[^>]+>/g, '')
                .replace(/^[ \t]+- /gm, '- ')
                .replace(/\n[ \t]+/g, '\n')
                .replace(/\n{3,}/g, '\n\n');

            // Last-mile cleanup for any stray <br> tokens that survived partial streaming chunks.
            while (/(<br\s*\/?>|&lt;br\s*\/?&gt;|&#60;br\s*\/?&#62;|&#x3c;br\s*\/?&#x3e;)/i.test(textoNormalizado)) {
                textoNormalizado = textoNormalizado
                    .replace(/&lt;br\s*\/?&gt;/gi, '\n')
                    .replace(/&#60;br\s*\/?&#62;/gi, '\n')
                    .replace(/&#x3c;br\s*\/?&#x3e;/gi, '\n')
                    .replace(/<br\s*\/?>/gi, '\n');
            }

            return textoNormalizado.trim();
        }

        function renderizarMarkdownSimples(texto) {
            const linhas = String(texto || '').replace(/\r/g, '').split('\n');
            let html = '';
            let emListaUl = false;
            let emListaOl = false;
            let paragrafo = [];

            function inline(valor) {
                return escaparHtml(valor)
                    .replace(/&lt;br\s*\/?&gt;/gi, '<br>')
                    .replace(/&#60;br\s*\/?&#62;/gi, '<br>')
                    .replace(/&#x3c;br\s*\/?&#x3e;/gi, '<br>')
                    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.+?)\*/g, '<em>$1</em>');
            }

            function fecharParagrafo() {
                if (paragrafo.length > 0) {
                    html += '<p>' + inline(paragrafo.join('<br>')) + '</p>';
                    paragrafo = [];
                }
            }

            function fecharListas() {
                if (emListaUl) {
                    html += '</ul>';
                    emListaUl = false;
                }
                if (emListaOl) {
                    html += '</ol>';
                    emListaOl = false;
                }
            }

            linhas.forEach((linha) => {
                const textoLinha = linha.trim();

                if (textoLinha === '') {
                    fecharParagrafo();
                    fecharListas();
                    return;
                }

                const matchOl = textoLinha.match(/^\d+[\.\)]\s+(.*)$/);
                if (matchOl) {
                    fecharParagrafo();
                    if (emListaUl) {
                        html += '</ul>';
                        emListaUl = false;
                    }
                    if (!emListaOl) {
                        html += '<ol>';
                        emListaOl = true;
                    }
                    html += '<li>' + inline(matchOl[1]) + '</li>';
                    return;
                }

                const matchUl = textoLinha.match(/^[-*]\s+(.*)$/);
                if (matchUl) {
                    fecharParagrafo();
                    if (emListaOl) {
                        html += '</ol>';
                        emListaOl = false;
                    }
                    if (!emListaUl) {
                        html += '<ul>';
                        emListaUl = true;
                    }
                    html += '<li>' + inline(matchUl[1]) + '</li>';
                    return;
                }

                fecharListas();
                paragrafo.push(textoLinha);
            });

            fecharParagrafo();
            fecharListas();

            return html || '<p></p>';
        }

        function atualizarBubbleAssistente(bubble, texto) {
            const textoNormalizado = sanitizarTextoAssistente(texto);
            bubble.dataset.rawText = textoNormalizado;
            bubble.innerHTML = renderizarMarkdownSimples(textoNormalizado);
        }

        function consumirTrechoExibivel() {
            const match = bufferVisual.match(/^([\s\S]*?(?:\n\n|\n|[.!?:;]\s|,\s|\)\s|\]\s|- |\* |\d+\.\s))/);
            if (!match) {
                return '';
            }

            const trecho = match[1];
            bufferVisual = bufferVisual.slice(trecho.length);
            return trecho;
        }

        function limparEstadoVazio() {
            if (emptyState) {
                emptyState.remove();
            }
        }

        function iniciarMensagensDeEspera() {
            recebeuPrimeiroDelta = false;
            filaMensagensCarregando = [...mensagensCarregando]
                .sort(() => Math.random() - 0.5)
                .slice(0, Math.min(mensagensCarregando.length, 2 + Math.floor(Math.random() * 2)));
            if (respostaAtual) {
                respostaAtual.textContent = filaMensagensCarregando[0] || mensagensCarregando[0];
            }
            status.textContent = '';

            if (carregandoInterval) {
                window.clearInterval(carregandoInterval);
            }

            carregandoInterval = window.setInterval(() => {
                const jaExisteTexto =
                    (respostaAtual && (respostaAtual.dataset.rawText || '').trim() !== '') ||
                    bufferVisual.trim() !== '';

                if (recebeuPrimeiroDelta || jaExisteTexto) {
                    pararMensagensDeEspera();
                    return;
                }
                if (filaMensagensCarregando.length <= 1) {
                    return;
                }
                filaMensagensCarregando.shift();
                if (respostaAtual) {
                    respostaAtual.textContent = filaMensagensCarregando[0];
                }
            }, 2600);
        }

        function pararMensagensDeEspera() {
            if (carregandoInterval) {
                window.clearInterval(carregandoInterval);
                carregandoInterval = null;
            }
            filaMensagensCarregando = [];
        }

        async function enviarComStreaming() {
            const duvida = textarea.value.trim();
            if (!duvida) {
                status.textContent = 'Digite sua dúvida antes de enviar.';
                textarea.focus();
                return;
            }

            limparEstadoVazio();
            criarMensagem(duvida, 'user');
            respostaAtual = criarMensagem('', 'assistant');
            respostaAtual.classList.add('ajuda-cursor');
            bufferVisual = '';

            botao.disabled = true;
            iniciarMensagensDeEspera();
            const label = botao.querySelector('.btn-label');
            if (label) {
                label.textContent = 'Respondendo...';
            }

            const formData = new FormData();
            formData.append('duvida_usuario', duvida);
            textarea.value = '';

            try {
                const response = await fetch('<?php echo htmlspecialchars(rtrim((string)$BASE_para_URL, '/'), ENT_QUOTES, 'UTF-8'); ?>/bia/stream', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'text/event-stream' }
                });

                if (!response.ok || !response.body) {
                    throw new Error('Falha ao iniciar a resposta em tempo real.');
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder('utf-8');
                let buffer = '';

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    let boundary = buffer.indexOf('\n\n');

                    while (boundary !== -1) {
                        const chunk = buffer.slice(0, boundary);
                        buffer = buffer.slice(boundary + 2);

                        let eventName = 'message';
                        const dataLines = [];

                        chunk.split('\n').forEach((line) => {
                            if (line.startsWith('event:')) {
                                eventName = line.slice(6).trim();
                                return;
                            }
                            if (line.startsWith('data:')) {
                                dataLines.push(line.slice(5).trimStart());
                            }
                        });

                        const data = dataLines.join('\n');
                        if (eventName === 'delta') {
                            if (!recebeuPrimeiroDelta) {
                                recebeuPrimeiroDelta = true;
                                pararMensagensDeEspera();
                                if (respostaAtual) {
                                    atualizarBubbleAssistente(respostaAtual, '');
                                }
                            }

                            bufferVisual += data;

                            let trecho = consumirTrechoExibivel();
                            while (trecho !== '') {
                                atualizarBubbleAssistente(
                                    respostaAtual,
                                    (respostaAtual.dataset.rawText || '') + trecho
                                );
                                trecho = consumirTrechoExibivel();
                            }
                            scrollChat();
                        } else if (eventName === 'error') {
                            throw new Error(data || 'Erro ao gerar a resposta.');
                        } else if (eventName === 'done') {
                            pararMensagensDeEspera();
                            atualizarBubbleAssistente(
                                respostaAtual,
                                data || ((respostaAtual.dataset.rawText || '') + bufferVisual)
                            );
                            bufferVisual = '';
                            respostaAtual.classList.remove('ajuda-cursor');
                            status.textContent = 'Resposta concluída.';
                        }

                        boundary = buffer.indexOf('\n\n');
                    }
                }

                respostaAtual.classList.remove('ajuda-cursor');
                if (!(respostaAtual.dataset.rawText || '').trim()) {
                    atualizarBubbleAssistente(respostaAtual, 'Não veio texto na resposta.');
                }
            } catch (error) {
                pararMensagensDeEspera();
                if (respostaAtual) {
                    respostaAtual.classList.remove('ajuda-cursor');
                    if (!(respostaAtual.dataset.rawText || '').trim()) {
                        atualizarBubbleAssistente(respostaAtual, error.message || 'Erro ao gerar a resposta.');
                    }
                }
                status.textContent = error.message || 'Erro ao gerar a resposta.';
            } finally {
                pararMensagensDeEspera();
                botao.disabled = false;
                if (label) {
                    label.textContent = 'Enviar';
                }
                textarea.focus();
            }
        }

        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                enviarComStreaming();
            });
        }

        if (botaoLimpar) {
            botaoLimpar.addEventListener('click', function () {
                textarea.value = '';
                status.textContent = '';
                textarea.focus();
            });
        }

        if (botaoNovaConversa) {
            botaoNovaConversa.addEventListener('click', async function () {
                botaoNovaConversa.disabled = true;
                status.textContent = 'Limpando conversa...';

                try {
                    const response = await fetch('<?php echo htmlspecialchars(rtrim((string)$BASE_para_URL, '/'), ENT_QUOTES, 'UTF-8'); ?>/bia/reset', {
                        method: 'POST'
                    });

                    if (!response.ok) {
                        throw new Error('Não foi possível iniciar uma nova conversa.');
                    }

                    window.location.href = '<?php echo htmlspecialchars(rtrim((string)$BASE_para_URL, '/'), ENT_QUOTES, 'UTF-8'); ?>/bia';
                } catch (error) {
                    status.textContent = error.message || 'Não foi possível iniciar uma nova conversa.';
                    botaoNovaConversa.disabled = false;
                }
            });
        }

        if (textarea) {
            window.requestAnimationFrame(() => {
                textarea.focus();
                const tamanho = textarea.value.length;
                textarea.setSelectionRange(tamanho, tamanho);
            });

            textarea.addEventListener('input', function () {
                textarea.style.height = 'auto';
                textarea.style.height = Math.min(textarea.scrollHeight, 260) + 'px';
            });

            textarea.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    if (form) {
                        form.requestSubmit();
                    }
                }
            });
        }

        document.querySelectorAll('[data-assistant-content="1"]').forEach((bubble) => {
            atualizarBubbleAssistente(bubble, bubble.textContent || '');
        });
    })();
</script>
</body>
</html>
