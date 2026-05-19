import { getAnamnesesByAluno, getPacientes, getCurrentClinicaUser } from '../services/api.js';
import { openModalAgendar } from './agenda.js';
import { getSpinnerHtml } from '../utils/loading.js';

const SEARCH_LIMIT = 4;
const SEARCH_DEBOUNCE_MS = 250;
let latestSearchToken = 0;

export async function renderPacientes(container, opts = {}) {
  const initialSearch = String(opts?.search || '').trim();
  container.innerHTML = buildPacientesShell(initialSearch);

  const searchEl = container.querySelector('#search-pacientes');
  const listEl = container.querySelector('#pacientes-list-area');
  const helperEl = container.querySelector('#pacientes-helper');

  if (initialSearch !== '') {
    await performSearch(container, initialSearch);
  }

  const doSearch = async () => {
    const term = searchEl.value.trim();
    syncSearchUrl(term);
    await performSearch(container, term);

    if (helperEl) {
      helperEl.textContent = term === ''
        ? 'Digite para buscar pacientes pelo nome, apelido, documento ou contato.'
        : 'Selecione um paciente para ver as ações disponíveis.';
    }
  };

  searchEl.addEventListener('input', debounce(doSearch, SEARCH_DEBOUNCE_MS));
  searchEl.focus();

  if (listEl) {
    bindPacienteNome(container);
  }
}

async function performSearch(container, term) {
  const listEl = container.querySelector('#pacientes-list-area');
  if (!listEl) return;

  if (term === '') {
    latestSearchToken += 1;
    listEl.className = 'mt-5';
    listEl.innerHTML = buildEmptyState(
      'Comece a digitar',
      'Busque pacientes pelo nome, apelido, documento ou contato.'
    );
    return;
  }

  listEl.className = 'mt-5 space-y-3.5 lg:grid lg:grid-cols-2 lg:gap-4 lg:space-y-0 2xl:grid-cols-3';
  listEl.innerHTML = getSpinnerHtml('Buscando pacientes...');

  const searchToken = ++latestSearchToken;
  const { pacientes } = await getPacientes(term, { limit: SEARCH_LIMIT });
  if (searchToken !== latestSearchToken) return;

  listEl.innerHTML = pacientes.length === 0
    ? buildEmptyState('Nenhum paciente encontrado', 'Ajuste o termo digitado para tentar outra combinação.')
    : pacientes.map(renderPacienteCard).join('');

  bindPacienteNome(container);
}

function buildPacientesShell(initialSearch) {
  const colaborador = getCurrentUser();
  const colaboradorNome = escapeAttribute(colaborador.nome || 'Perfil do Médico');
  const colaboradorFoto = escapeAttribute(colaborador.fotoUrl || getFallbackFotoUrl());

  return `
    <section class="mx-auto w-full max-w-none 2xl:max-w-6xl">
      <div class="rounded-[28px] bg-slate-100/90 px-3 py-6 shadow-[0_18px_45px_rgba(15,23,42,0.08)] sm:px-4 lg:px-6 lg:py-7">
        <div class="flex items-start justify-between gap-4 lg:items-center">
          <div>
            <p class="text-sm font-medium text-slate-400">Clínica Médica</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 lg:text-[2.15rem]">Lista de Pacientes</h1>
          </div>
          <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center overflow-hidden border-2 border-white shadow-sm">
            <img
              alt="${colaboradorNome}"
              class="w-full h-full object-cover"
              src="${colaboradorFoto}"
              onerror="this.onerror=null;this.src='${escapeAttribute(getFallbackFotoUrl())}';"
            >
          </div>
        </div>

        <div class="lg:mt-6 lg:flex lg:items-end lg:justify-between lg:gap-5">
          <div class="lg:flex-1 lg:max-w-3xl">
            <label for="search-pacientes" class="mt-6 flex items-center gap-3 rounded-2xl bg-white px-4 py-3.5 shadow-sm ring-1 ring-slate-200 transition focus-within:ring-2 focus-within:ring-primary/20 lg:mt-0">
              <svg class="h-5 w-5 flex-none text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
              </svg>
              <input
                type="search"
                id="search-pacientes"
                placeholder="Buscar por nome, contato ou documento..."
                value="${escapeHtml(initialSearch)}"
                class="w-full border-0 bg-transparent p-0 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none"
                autocomplete="off"
              >
            </label>

            <p id="pacientes-helper" class="mt-3 px-1 text-sm text-slate-500">
              ${initialSearch === '' ? 'Digite para buscar pacientes pelo nome, apelido, documento ou contato.' : 'Selecione um paciente para ver as ações disponíveis.'}
            </p>
          </div>

          <div class="hidden lg:block lg:w-80 lg:rounded-3xl lg:bg-white/80 lg:px-5 lg:py-4 lg:shadow-sm lg:ring-1 lg:ring-slate-200">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Busca rápida</p>
            <p class="mt-2 text-sm leading-6 text-slate-500">Use nome, apelido, CPF, telefone ou WhatsApp para localizar pacientes sem carregar a lista completa.</p>
          </div>
        </div>

        <div id="pacientes-list-area" class="mt-5">
          ${initialSearch === '' ? buildEmptyState('Comece a digitar', 'Busque pacientes pelo nome, apelido, documento ou contato.') : getSpinnerHtml('Buscando pacientes...')}
        </div>
      </div>
    </section>
  `;
}

function renderPacienteCard(paciente) {
  const nomeRaw = String(paciente.Nome || 'Sem nome');
  const nome = escapeHtml(nomeRaw);
  const cpfRaw = String(paciente.CPF || '').trim();
  const cpf = escapeHtml(cpfRaw || 'CPF não informado');
  const contatoRaw = String(paciente.WhatsApp || paciente.Telefone || paciente.Email || 'Sem contato principal');
  const contato = escapeHtml(contatoRaw);
  const emailRaw = String(paciente.Email || '').trim();
  const fotoUrl = escapeAttribute(getPacienteFotoUrl(paciente));
  const tooltip = escapeAttribute(buildPacienteTooltip({
    nome: nomeRaw,
    cpf: cpfRaw,
    contato: contatoRaw,
    email: emailRaw,
    apelido: String(paciente.Apelido || '').trim(),
    nascimento: String(paciente.Nascimento || '').trim(),
  }));

  return `
    <button
      type="button"
      class="btn-paciente-nome w-full bg-white p-4 rounded-2xl flex items-center text-left shadow-sm border border-slate-50 group active:scale-[0.98] transition-all hover:shadow-md lg:h-full"
      data-id="${escapeAttribute(String(paciente.IdUsuario || ''))}"
      data-nome="${escapeAttribute(String(paciente.Nome || ''))}"
      title="${tooltip}"
      aria-label="${tooltip}"
    >
      <div class="relative">
        <img
          alt="Foto de ${nome}"
          class="w-14 h-14 rounded-full object-cover"
          src="${fotoUrl}"
          loading="lazy"
          onerror="this.onerror=null;this.src='${escapeAttribute(getFallbackFotoUrl())}';"
        >
        <div class="absolute bottom-0 right-0 w-3.5 h-3.5 bg-green-500 border-2 border-white rounded-full"></div>
      </div>
      <div class="ml-4 flex-1 min-w-0">
        <h3 class="truncate text-[15px] font-semibold text-slate-900">${nome}</h3>
        <p class="truncate mt-0.5 text-[12px] text-slate-400">${cpf}</p>
        <p class="text-slate-500 text-[13px] flex items-center mt-0.5">
          <span class="inline-flex mr-1 text-[15px] text-slate-400" aria-hidden="true">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
              <path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1-.24 11.4 11.4 0 0 0 3.59.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1C10.3 21 3 13.7 3 4a1 1 0 0 1 1-1h3.49a1 1 0 0 1 1 1 11.4 11.4 0 0 0 .57 3.59 1 1 0 0 1-.25 1.01z"></path>
            </svg>
          </span>
          <span class="truncate">${contato}</span>
        </p>
      </div>
      <span class="w-10 h-10 flex items-center justify-center text-slate-300 group-hover:text-primary transition-colors" aria-hidden="true">
        <span class="inline-flex" aria-hidden="true">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m9 18 6-6-6-6"></path>
          </svg>
        </span>
      </span>
    </button>
  `;
}

function buildPacienteTooltip({ nome, cpf, contato, email, apelido, nascimento }) {
  const linhas = [
    `Nome: ${nome || 'Não informado'}`,
    `CPF: ${cpf || 'Não informado'}`,
    `Contato: ${contato || 'Não informado'}`,
  ];

  if (email) linhas.push(`E-mail: ${email}`);
  if (apelido) linhas.push(`Apelido: ${apelido}`);
  if (nascimento) linhas.push(`Nascimento: ${nascimento}`);

  return linhas.join('\n');
}

function buildEmptyState(title, description) {
  return `
    <div class="rounded-[26px] border border-dashed border-slate-300 bg-white/75 px-6 py-10 text-center shadow-[0_10px_30px_rgba(148,163,184,0.10)] lg:px-10 lg:py-14">
      <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-sky-50 text-sky-500">
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <circle cx="11" cy="11" r="7"></circle>
          <path d="m20 20-3.5-3.5"></path>
        </svg>
      </div>
      <p class="mt-4 text-lg font-semibold text-slate-700">${escapeHtml(title)}</p>
      <p class="mt-2 text-sm leading-6 text-slate-500">${escapeHtml(description)}</p>
    </div>
  `;
}

function bindPacienteNome(container) {
  (container.querySelectorAll?.('.btn-paciente-nome') || []).forEach((el) => {
    el.onclick = (e) => {
      e.preventDefault();
      const alunoId = parseInt(el.dataset.id, 10);
      const nome = el.dataset.nome || '';
      openModalPaciente(alunoId, nome);
    };
  });
}

async function openModalPaciente(alunoId, nomePaciente) {
  const main = document.getElementById('main-content');
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4';
  modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-monday-lg max-w-md w-full p-6">
      <h3 class="text-lg font-semibold mb-4">${escapeHtml(nomePaciente)}</h3>
      <div class="flex flex-col gap-2">
        <button type="button" class="modal-agendar min-h-touch px-4 py-3 bg-monday-blue text-white rounded-xl font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-monday-blue">Agendar consulta</button>
        <button type="button" class="modal-prontuarios min-h-touch px-4 py-3 bg-slate-600 text-white rounded-xl font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-slate-500">Ver prontuários</button>
        <button type="button" class="modal-anamneses min-h-touch px-4 py-3 bg-slate-600 text-white rounded-xl font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-slate-500">Ver anamneses</button>
        <button type="button" class="modal-evolucoes min-h-touch px-4 py-3 bg-slate-600 text-white rounded-xl font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-slate-500">Ver evoluções</button>
      </div>
      <button type="button" class="modal-close mt-4 w-full min-h-touch py-2 border border-gray-300 rounded-xl focus-visible:ring-2 focus-visible:ring-offset-2">Fechar</button>
    </div>
  `;
  const currentUser = getCurrentClinicaUser();
  if (currentUser.licenca_administrativa === 1 && currentUser.profissional_saude !== 1) {
    modal.querySelector('.modal-evolucoes')?.classList.add('hidden');
  }

  modal.querySelector('.modal-close').onclick = () => modal.remove();
  modal.querySelector('.modal-agendar').onclick = () => {
    modal.remove();
    openModalAgendar(main, alunoId, { skipRefreshAgenda: true });
  };
  modal.querySelector('.modal-prontuarios').onclick = () => {
    modal.remove();
    window.dispatchEvent(new CustomEvent('navigate-to', { detail: { page: 'prontuarios', options: { aluno_id: alunoId } } }));
  };
  modal.querySelector('.modal-evolucoes').onclick = () => {
    modal.remove();
    window.dispatchEvent(new CustomEvent('navigate-to', { detail: { page: 'evolucoes', options: { aluno_id: alunoId } } }));
  };
  modal.querySelector('.modal-anamneses').onclick = async () => {
    try {
      const anamneses = await getAnamnesesByAluno(alunoId);
      if (anamneses.length === 0) {
        alert('Nenhuma anamnese registrada para este paciente.');
        return;
      }
      const lista = anamneses.map((a) => {
        const dt = a.created_at ? new Date(a.created_at).toLocaleDateString('pt-BR') : '';
        return `<li class="py-2 border-b border-gray-100 last:border-0">Anamnese - ${escapeHtml(dt)}</li>`;
      }).join('');
      const subModal = document.createElement('div');
      subModal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-[60] p-4';
      subModal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-monday-lg max-w-md w-full p-6 max-h-[80vh] overflow-y-auto">
          <h4 class="font-semibold mb-3">Anamneses de ${escapeHtml(nomePaciente)}</h4>
          <ul class="text-sm text-gray-700">${lista}</ul>
          <button type="button" class="sub-fechar mt-4 w-full py-2 border border-gray-300 rounded-xl">Fechar</button>
        </div>
      `;
      subModal.querySelector('.sub-fechar').onclick = () => subModal.remove();
      subModal.onclick = (e) => { if (e.target === subModal) subModal.remove(); };
      document.body.appendChild(subModal);
    } catch (err) {
      alert(err.message || 'Erro ao carregar anamneses.');
    }
  };
  modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
  document.body.appendChild(modal);
}

function getCurrentUser() {
  const usuario = getCurrentClinicaUser();
  return {
    nome: String(usuario.nome || '').trim(),
    fotoUrl: String(usuario.fotoUrl || '').trim(),
  };
}

function getPacienteFotoUrl(paciente) {
  const rawUrl = String(paciente?.FotoUrl || '').trim();
  if (rawUrl !== '') return rawUrl;

  const rawFoto = String(paciente?.Foto || '').trim();
  return joinFotoPath(rawFoto !== '' ? rawFoto : 'padrao.jpg');
}

function getFallbackFotoUrl() {
  return 'data:image/svg+xml;utf8,' + encodeURIComponent(`
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
      <rect width="64" height="64" rx="32" fill="#e2e8f0"/>
      <circle cx="32" cy="24" r="12" fill="#94a3b8"/>
      <path d="M14 54c3-10 12-16 18-16s15 6 18 16" fill="#94a3b8"/>
    </svg>
  `.trim());
}

function joinFotoPath(fileName) {
  const safeFileName = encodeURIComponent(String(fileName || 'padrao.jpg').split('/').pop());
  const currentUrl = typeof window !== 'undefined' && window.location ? window.location.href : 'http://localhost/clinica/';
  return new URL(`../assets/img/fotos/${safeFileName}`, currentUrl).toString();
}

function syncSearchUrl(term) {
  if (typeof window === 'undefined' || !window.history) return;

  const url = new URL(window.location.href);
  if (term !== '') {
    url.searchParams.set('search', term);
  } else {
    url.searchParams.delete('search');
  }

  window.history.replaceState({ page: 'pacientes', options: term !== '' ? { search: term } : {} }, '', url.toString());
}

function escapeHtml(s) {
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

function escapeAttribute(s) {
  return escapeHtml(s).replace(/"/g, '&quot;');
}

function debounce(fn, ms) {
  let t;
  return (...a) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...a), ms);
  };
}
