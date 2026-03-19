import { getPacientes, getProntuarios, getAnamnesesByAluno } from '../services/api.js';
import { openModalAgendar } from './agenda.js';
import { getSpinnerHtml } from '../utils/loading.js';

export async function renderPacientes(container) {
  container.innerHTML = getSpinnerHtml('Carregando pacientes...');
  const { pacientes } = await getPacientes();
  const searchHtml = `
    <div class="mb-4">
      <input type="search" id="search-pacientes" placeholder="Buscar nome, contato, documento..." 
        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-monday-blue focus:border-monday-blue focus-visible:outline-none transition min-h-[44px]">
    </div>
  `;
  const listHtml = pacientes.length === 0
    ? '<div id="pacientes-list-area" class="bg-white rounded-xl shadow-monday p-8 text-center text-gray-500"><p>Nenhum paciente encontrado.</p></div>'
    : `
    <div id="pacientes-list-area" class="space-y-3 md:grid md:grid-cols-2 md:gap-3 md:space-y-0 lg:grid-cols-3">
      ${pacientes.map((p) => `
        <div class="bg-white rounded-xl shadow-monday p-4 flex justify-between items-center gap-3 hover:shadow-monday-lg transition-shadow cursor-pointer btn-paciente-nome" data-id="${p.IdUsuario}" data-nome="${escapeHtml(String(p.Nome || ''))}">
          <div class="min-w-0 flex-1">
            <span class="font-medium text-gray-800 block truncate hover:text-monday-blue hover:underline">${escapeHtml(String(p.Nome || ''))}</span>
            ${p.Telefone || p.WhatsApp ? `<span class="block text-sm text-gray-500 truncate">${escapeHtml(String(p.Telefone || p.WhatsApp || ''))}</span>` : ''}
          </div>
        </div>
      `).join('')}
    </div>
  `;
  container.innerHTML = searchHtml + listHtml;

  const searchEl = container.querySelector('#search-pacientes');
  const doSearch = async () => {
    const term = searchEl.value.trim();
    const containerList = container.querySelector('#pacientes-list-area');
    if (containerList) containerList.innerHTML = getSpinnerHtml('Buscando...');
    const { pacientes: list } = await getPacientes(term);
    if (containerList) {
      containerList.innerHTML = list.length === 0
        ? '<div class="col-span-full bg-white rounded-xl shadow-monday p-8 text-center text-gray-500"><p>Nenhum paciente encontrado.</p></div>'
        : list.map((p) => `
          <div class="bg-white rounded-xl shadow-monday p-4 flex justify-between items-center gap-3 hover:shadow-monday-lg transition-shadow cursor-pointer btn-paciente-nome" data-id="${p.IdUsuario}" data-nome="${escapeHtml(String(p.Nome || ''))}">
            <div class="min-w-0 flex-1">
              <span class="font-medium text-gray-800 block truncate hover:text-monday-blue hover:underline">${escapeHtml(String(p.Nome || ''))}</span>
              ${p.Telefone || p.WhatsApp ? `<span class="block text-sm text-gray-500 truncate">${escapeHtml(String(p.Telefone || p.WhatsApp || ''))}</span>` : ''}
            </div>
          </div>
        `).join('');
      if (list.length > 0) containerList.className = 'space-y-3 md:grid md:grid-cols-2 md:gap-3 md:space-y-0 lg:grid-cols-3';
    }
    bindPacienteNome(container);
  };
  searchEl.addEventListener('input', debounce(doSearch, 200));
  bindPacienteNome(container);
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
      </div>
      <button type="button" class="modal-close mt-4 w-full min-h-touch py-2 border border-gray-300 rounded-xl focus-visible:ring-2 focus-visible:ring-offset-2">Fechar</button>
    </div>
  `;

  modal.querySelector('.modal-close').onclick = () => modal.remove();
  modal.querySelector('.modal-agendar').onclick = () => {
    modal.remove();
    openModalAgendar(main, alunoId, { skipRefreshAgenda: true });
  };
  modal.querySelector('.modal-prontuarios').onclick = () => {
    modal.remove();
    window.dispatchEvent(new CustomEvent('navigate-to', { detail: { page: 'prontuarios', options: { aluno_id: alunoId } } }));
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
        return `<li class="py-2 border-b border-gray-100 last:border-0">Anamnese • ${escapeHtml(dt)}</li>`;
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

function escapeHtml(s) {
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

function debounce(fn, ms) {
  let t;
  return (...a) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...a), ms);
  };
}
