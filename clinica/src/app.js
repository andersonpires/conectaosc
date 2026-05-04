import { renderPacientes } from './pages/pacientes.js?v=20260302a';
import { renderAgenda } from './pages/agenda.js?v=20260311a';
import { renderProntuarios } from './pages/prontuarios.js?v=20260502a';
import { renderAtendimento } from './pages/atendimento.js?v=20260311a';
import { getSpinnerHtml } from './utils/loading.js?v=20260302a';

const main = document.getElementById('main-content');
const ROUTE_BY_PAGE = {
  pacientes: 'paciente',
  agenda: 'agenda',
  prontuarios: 'prontuario',
  atendimento: 'atendimento'
};
const PAGE_BY_ROUTE = Object.entries(ROUTE_BY_PAGE).reduce((acc, [page, route]) => {
  acc[route] = page;
  return acc;
}, {});

let currentPage = 'pacientes';

function getAppBasePath() {
  if (typeof window === 'undefined' || !window.location) return '';
  const pathname = window.location.pathname || '/';
  const clinicaIndex = pathname.indexOf('/clinica');
  return clinicaIndex >= 0 ? pathname.slice(0, clinicaIndex) : '';
}

function getClinicaBasePath() {
  return `${getAppBasePath()}/clinica`;
}

function getPageFromLocation() {
  if (typeof window === 'undefined' || !window.location) return 'pacientes';
  const pathname = (window.location.pathname || '/').replace(/\/+$/, '');
  const basePath = getClinicaBasePath();
  let suffix = pathname.startsWith(basePath) ? pathname.slice(basePath.length) : '';
  suffix = suffix.replace(/^\/+/, '');
  if (suffix === '' || suffix === 'index.php') {
    return 'pacientes';
  }
  return PAGE_BY_ROUTE[suffix] || 'pacientes';
}

function buildQueryParams(page, options = {}) {
  const params = new URLSearchParams();
  if (page === 'pacientes') {
    const search = String(options?.search || '').trim();
    if (search !== '') {
      params.set('search', search);
    }
  }
  if (page === 'prontuarios' && options?.aluno_id) {
    params.set('aluno_id', String(options.aluno_id));
  }
  if (page === 'atendimento') {
    if (options?.consultaId) {
      params.set('consultaId', String(options.consultaId));
    }
    if (options?.date) {
      params.set('date', String(options.date));
    }
  }
  return params;
}

function getOptionsFromLocation(page) {
  if (typeof window === 'undefined' || !window.location) return {};
  const params = new URLSearchParams(window.location.search || '');
  if (page === 'pacientes') {
    const search = (params.get('search') || '').trim();
    return search !== '' ? { search } : {};
  }
  if (page === 'prontuarios') {
    const alunoId = parseInt(params.get('aluno_id') || '', 10);
    return Number.isFinite(alunoId) && alunoId > 0 ? { aluno_id: alunoId } : {};
  }
  if (page === 'atendimento') {
    const options = {};
    const consultaId = parseInt(params.get('consultaId') || '', 10);
    const date = (params.get('date') || '').trim();
    if (Number.isFinite(consultaId) && consultaId > 0) {
      options.consultaId = consultaId;
    }
    if (date !== '') {
      options.date = date;
    }
    return options;
  }
  return {};
}

function getUrlForPage(page) {
  const route = ROUTE_BY_PAGE[page] || ROUTE_BY_PAGE.pacientes;
  const params = buildQueryParams(page, lastNavigateOptions);
  const query = params.toString();
  return `${getClinicaBasePath()}/${route}${query ? `?${query}` : ''}`;
}

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

async function navigate(page, options = {}, navigation = {}) {
  const { updateHistory = true, historyMode = 'push' } = navigation;
  setActiveNav(page);
  lastNavigateOptions = options;
  if (updateHistory && typeof window !== 'undefined' && window.history) {
    const url = getUrlForPage(page);
    const state = { page, options };
    if (historyMode === 'replace') {
      window.history.replaceState(state, '', url);
    } else {
      window.history.pushState(state, '', url);
    }
  }
  main.innerHTML = getSpinnerHtml('Carregando...');
  try {
    switch (page) {
      case 'pacientes':
        await renderPacientes(main, options);
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
        main.innerHTML = '<p>Pagina nao encontrada</p>';
    }
  } catch (err) {
    const loginUrl = (() => {
      if (typeof window === 'undefined' || !window.location) return '../login/';
      const pathname = window.location.pathname || '/';
      const clinicaIndex = pathname.indexOf('/clinica');
      const appBasePath = clinicaIndex >= 0 ? pathname.slice(0, clinicaIndex) : '';
      if (!window.location.origin) return `${appBasePath}/login/`;
      const redirectPath = `${appBasePath}/clinica/`;
      return `${window.location.origin}${appBasePath}/login/?redirect=${encodeURIComponent(redirectPath)}`;
    })();
    const msg = String(err.message || 'Erro desconhecido');
    main.innerHTML = `
      <div class="rounded-xl bg-red-50 border border-red-200 p-4">
        <p class="text-red-800 font-medium">${msg.replace(/</g, '&lt;')}</p>
        <p class="text-sm text-red-700 mt-2">Solucoes:</p>
        <ul class="list-disc list-inside text-sm text-red-700 mt-1">
          <li>Faca login no ConectaOSC e abra o App Clinica novamente</li>
          <li>Certifique-se de acessar via <strong>caminho da aplicacao</strong> ou o dominio correto</li>
          <li>Execute as migrations em !Suporte/migrations se ainda nao fez</li>
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
    navigate(currentPage, lastNavigateOptions);
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

window.addEventListener('popstate', (e) => {
  const page = e.state?.page || getPageFromLocation();
  const options = e.state?.options || getOptionsFromLocation(page);
  navigate(page, options, { updateHistory: false });
});

const initialPage = getPageFromLocation();
const initialOptions = getOptionsFromLocation(initialPage);
navigate(initialPage, initialOptions, { updateHistory: true, historyMode: 'replace' });


