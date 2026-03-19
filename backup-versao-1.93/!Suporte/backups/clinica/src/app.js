import { renderPacientes } from './pages/pacientes.js';
import { renderAgenda } from './pages/agenda.js';
import { renderProntuarios } from './pages/prontuarios.js';
import { renderAtendimento } from './pages/atendimento.js';
import { getSpinnerHtml } from './utils/loading.js';

const main = document.getElementById('main-content');
let currentPage = 'pacientes';

function setActiveNav(page) {
  currentPage = page;
  document.querySelectorAll('.nav-btn').forEach((btn) => {
    const isActive = btn.dataset.page === page;
    btn.classList.toggle('text-monday-blue', isActive);
    btn.classList.toggle('font-semibold', isActive);
    btn.classList.toggle('text-gray-500', !isActive);
  });
}

let lastNavigateOptions = {};

async function navigate(page, options = {}) {
  setActiveNav(page);
  lastNavigateOptions = options;
  main.innerHTML = getSpinnerHtml('Carregando...');
  try {
    switch (page) {
      case 'pacientes':
        await renderPacientes(main);
        break;
      case 'agenda':
        await renderAgenda(main);
        break;
      case 'prontuarios':
        await renderProntuarios(main, options);
        break;
      case 'atendimento':
        await renderAtendimento(main, options);
        break;
      default:
        main.innerHTML = '<p>Página não encontrada</p>';
    }
  } catch (err) {
    const loginUrl = (typeof window !== 'undefined' && window.location?.origin)
      ? `${window.location.origin}/conectaosc/login.php?redirect=${encodeURIComponent('/conectaosc/clinica/')}`
      : '/conectaosc/login.php';
    const msg = String(err.message || 'Erro desconhecido');
    main.innerHTML = `
      <div class="rounded-xl bg-red-50 border border-red-200 p-4">
        <p class="text-red-800 font-medium">${msg.replace(/</g, '&lt;')}</p>
        <p class="text-sm text-red-700 mt-2">Soluções:</p>
        <ul class="list-disc list-inside text-sm text-red-700 mt-1">
          <li>Faça login no ConectaOSC e abra o App Clínica novamente</li>
          <li>Certifique-se de acessar via <strong>localhost/conectaosc/</strong> ou o domínio correto</li>
          <li>Execute as migrations em !Suporte/migrations se ainda não fez</li>
        </ul>
        <a href="${loginUrl}" class="inline-block mt-4 px-4 py-2 bg-monday-blue text-white rounded-lg text-sm">Fazer login no ConectaOSC</a>
      </div>
    `;
  }
  if (typeof lucide !== 'undefined') lucide.createIcons();
}

document.body.addEventListener('click', (e) => {
  if (e.target.closest('#btn-refresh')) {
    e.preventDefault();
    navigate(currentPage);
  }
});

document.querySelectorAll('.nav-btn').forEach((btn) => {
  btn.addEventListener('click', () => navigate(btn.dataset.page));
});

window.addEventListener('open-atendimento', (e) => {
  const consultaId = e.detail?.consultaId;
  const date = e.detail?.date;
  navigate('atendimento', consultaId || date ? { consultaId, date } : {});
});

window.addEventListener('navigate-to', (e) => {
  const { page, options = {} } = e.detail || {};
  if (page) navigate(page, options);
});

navigate('pacientes');
