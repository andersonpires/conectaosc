/**
 * Utilitários de carregamento com spinner para o App Clínica.
 */

export const SPINNER_HTML = `
  <div class="flex flex-col items-center justify-center gap-3 py-12" role="status" aria-live="polite">
    <svg class="animate-spin h-10 w-10 text-monday-blue" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    <span class="text-sm text-gray-500">Carregando...</span>
  </div>
`;

/**
 * Retorna HTML do spinner para uso como placeholder de carregamento.
 * @param {string} [message='Carregando...'] - Mensagem exibida abaixo do spinner
 */
export function getSpinnerHtml(message = 'Carregando...') {
  return `
  <div class="flex flex-col items-center justify-center gap-3 py-12 min-h-[200px]" role="status" aria-live="polite">
    <svg class="animate-spin h-10 w-10 text-monday-blue" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    <span class="text-sm text-gray-500">${message}</span>
  </div>
  `;
}

/**
 * Spinner compacto para botões (inline).
 */
export function getButtonSpinnerHtml() {
  return `<svg class="animate-spin h-5 w-5 inline-block -mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;
}

const OVERLAY_ID = 'app-loading-overlay';

/**
 * Exibe overlay de carregamento em tela cheia (z-50).
 */
export function showLoadingOverlay(message = 'Carregando...') {
  let el = document.getElementById(OVERLAY_ID);
  if (!el) {
    el = document.createElement('div');
    el.id = OVERLAY_ID;
    el.className = 'fixed inset-0 bg-white/80 backdrop-blur-sm flex items-center justify-center z-50';
    document.body.appendChild(el);
  }
  el.innerHTML = getSpinnerHtml(message);
  el.style.display = 'flex';
}

/**
 * Remove o overlay de carregamento.
 */
export function hideLoadingOverlay() {
  const el = document.getElementById(OVERLAY_ID);
  if (el) el.style.display = 'none';
}
