let overlayInterval = null;

function mostrarOverlay({
    mensagem = '',
    carregando = true,
    contador = null,
    onClose = null
}) {
    const overlay = document.getElementById('overlayLoading');
    const spinner = document.getElementById('overlaySpinner');
    const texto = document.getElementById('overlayText');
    const botao = document.getElementById('overlayBtnOk');

    // 🔥 limpa contador anterior (caso exista)
    if (overlayInterval) {
        clearInterval(overlayInterval);
        overlayInterval = null;
    }

    // 🔹 Texto simples (sem contador)
    if (contador === null) {
        texto.textContent = mensagem;
    } else {
        texto.innerHTML = `
            <div style="text-align:center;">
                <div>${mensagem}</div>
                <div id="overlayCountdown"
                     style="margin-top:8px;font-weight:bold;font-size:16px;">
                    ${contador}s
                </div>
            </div>
        `;

        let tempo = contador;

        overlayInterval = setInterval(() => {
            tempo--;

            const el = document.getElementById('overlayCountdown');
            if (!el) {
                clearInterval(overlayInterval);
                overlayInterval = null;
                return;
            }

            el.textContent = tempo + 's';

            if (tempo <= 0) {
                clearInterval(overlayInterval);
                overlayInterval = null;
            }
        }, 1000);
    }

    if (carregando) {
        spinner.style.display = 'inline-block';
        botao.style.display = 'none';
    } else {
        spinner.style.display = 'none';
        botao.style.display = 'inline-block';

        botao.onclick = () => {
            esconderOverlay();
            if (typeof onClose === 'function') onClose();
        };
    }

    overlay.style.display = 'flex';
}

function esconderOverlay() {
    if (overlayInterval) {
        clearInterval(overlayInterval);
        overlayInterval = null;
    }
    document.getElementById('overlayLoading').style.display = 'none';
}
