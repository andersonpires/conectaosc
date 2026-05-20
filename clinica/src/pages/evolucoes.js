import { getCurrentClinicaUser, getEvolucoes, getEvolucao, postEvolucao, putEvolucao, getPaciente, getConsulta, getPacientes, getProfissionais, getEspecialidades } from '../services/api.js';
import { getButtonSpinnerHtml, getSpinnerHtml } from '../utils/loading.js';

const TEMPLATE_INICIAL = `
<p><strong>Dados observados durante o atendimento:</strong><br>Ex.: postura, comunica&ccedil;&atilde;o, intera&ccedil;&atilde;o, ades&atilde;o ao atendimento e sinais observ&aacute;veis relevantes.</p>
<p><strong>Queixas ou temas principais abordados:</strong><br>Ex.: ansiedade, conflito familiar, dificuldade escolar, rotina, sono, alimenta&ccedil;&atilde;o ou demanda trazida pelo paciente/respons&aacute;vel.</p>
<p><strong>Estado cognitivo:</strong><br>Ex.: orientado em tempo e espa&ccedil;o, aten&ccedil;&atilde;o preservada, pensamento organizado, mem&oacute;ria sem altera&ccedil;&otilde;es aparentes.</p>
<p><strong>Estado de humor:</strong><br>Ex.: humor est&aacute;vel, ansioso, deprimido, irritadi&ccedil;o, oscilante, compat&iacute;vel ou incompat&iacute;vel com o contexto.</p>
<p><strong>Aspectos sociais:</strong><br>Ex.: apoio familiar, conviv&ecirc;ncia, v&iacute;nculos, contexto escolar/profissional e fatores sociais que impactam o caso.</p>
<p><strong>Impress&otilde;es do profissional:</strong><br>Ex.: hip&oacute;teses cl&iacute;nicas iniciais, leitura t&eacute;cnica do momento e pontos de aten&ccedil;&atilde;o observados na consulta.</p>
<p><strong>Condutas adotadas:</strong><br>Ex.: escuta qualificada, interven&ccedil;&atilde;o breve, t&eacute;cnicas aplicadas, combinados realizados e estrat&eacute;gias definidas.</p>
<p><strong>Encaminhamentos ou orienta&ccedil;&otilde;es:</strong><br>Ex.: orienta&ccedil;&otilde;es ao paciente/respons&aacute;vel, solicita&ccedil;&atilde;o de retorno, articula&ccedil;&atilde;o com rede ou encaminhamento para outro profissional.</p>
<p><strong>Postura/plano para pr&oacute;xima consulta:</strong><br>Ex.: aprofundar tema espec&iacute;fico, acompanhar evolu&ccedil;&atilde;o, revisar combinados e monitorar resposta &agrave;s orienta&ccedil;&otilde;es.</p>
`.trim();

function escapeHtml(value) {
  const div = document.createElement('div');
  div.textContent = value ?? '';
  return div.innerHTML;
}

function escapeAttribute(value) {
  return escapeHtml(String(value ?? '')).replace(/"/g, '&quot;');
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

function fmtDate(value) {
  if (!value) return '';
  return new Date(`${value}T00:00:00`).toLocaleDateString('pt-BR');
}

function fmtDateTime(value, time) {
  const data = fmtDate(value);
  const hora = String(time || '').slice(0, 5);
  return [data, hora].filter(Boolean).join(' ');
}

function getCurrentUserProfile() {
  return window.__CLINICA_BOOTSTRAP__ || {};
}

function resumoAtendimento(item) {
  const partes = [];
  if (item.consulta_status) partes.push(`Status: ${item.consulta_status}`);
  if (item.tem_anamnese_adulto) partes.push('Anamnese adulto');
  if (item.tem_anamnese_infantojuvenil) partes.push('Roteiro infantojuvenil');
  if (item.tem_prontuario) partes.push('Prontu\u00E1rio');
  if (item.tem_evolucao) partes.push('Evolu\u00E7\u00E3o');
  if (item.conteudo) partes.push(String(item.conteudo).replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim().slice(0, 120));
  return partes.join(' | ') || 'Consulta sem registro cl\u00EDnico vinculado';
}

function pacienteMeta(paciente) {
  const partes = [
    paciente?.CPF ? `CPF: ${paciente.CPF}` : '',
    paciente?.Nascimento ? `Nascimento: ${fmtDate(paciente.Nascimento)}` : '',
    paciente?.WhatsApp || paciente?.Telefone ? `Contato: ${paciente.WhatsApp || paciente.Telefone}` : '',
  ].filter(Boolean);
  return partes.join(' | ');
}

function getEditorValue() {
  if (typeof tinymce !== 'undefined' && tinymce.get('evolucao-conteudo')) {
    return tinymce.get('evolucao-conteudo').getContent().trim();
  }
  return String(document.getElementById('evolucao-conteudo')?.value || '').trim();
}

function destroyEditor() {
  if (typeof tinymce !== 'undefined' && tinymce.get('evolucao-conteudo')) {
    tinymce.get('evolucao-conteudo').remove();
  }
}

function initEditor() {
  destroyEditor();
  if (typeof tinymce === 'undefined') return;
  tinymce.init({
    selector: '#evolucao-conteudo',
    menubar: false,
    height: 320,
    plugins: 'lists code',
    toolbar: 'undo redo | bold italic underline | bullist numlist | code',
    language: 'pt_BR',
    branding: false,
  });
}

function getPdfPath(id) {
  return `./pdf_evolucao.php?id=${id}`;
}

async function openDetalheModal(id, canEdit, onSaved) {
  const user = getCurrentClinicaUser();
  const evolucao = await getEvolucao(id);
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4';
  modal.innerHTML = `
    <div class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h3 class="text-xl font-semibold text-slate-900">${escapeHtml(evolucao.paciente_nome || 'Evolu\u00E7\u00E3o cl\u00EDnica')}</h3>
          <p class="text-sm text-slate-500">${fmtDate(evolucao.data_evolucao)} \u00E0s ${escapeHtml(String(evolucao.hora_evolucao || '').slice(0, 5))} - ${escapeHtml(evolucao.profissional_nome || '')}</p>
        </div>
        <button type="button" class="btn-fechar rounded-xl border border-slate-300 px-4 py-2">Fechar</button>
      </div>
      <div class="mt-4 grid gap-3 md:grid-cols-3">
        <input type="date" id="evolucao-data" class="rounded-xl border border-slate-300 px-4 py-3" value="${escapeHtml(evolucao.data_evolucao || '')}" ${canEdit ? '' : 'disabled'}>
        <input type="time" id="evolucao-hora" class="rounded-xl border border-slate-300 px-4 py-3" value="${escapeHtml(String(evolucao.hora_evolucao || '').slice(0, 5))}" ${canEdit ? '' : 'disabled'}>
        <input type="text" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3" value="${escapeHtml(evolucao.especialidade_nome || '')}" disabled>
      </div>
      <div class="mt-4">
        <textarea id="evolucao-conteudo" class="w-full rounded-2xl border border-slate-300 px-4 py-3" ${canEdit ? '' : 'disabled'}>${escapeHtml(evolucao.conteudo || '')}</textarea>
      </div>
      <div class="mt-4 flex flex-wrap justify-end gap-2">
        ${canEdit ? '<button type="button" class="btn-salvar rounded-xl bg-monday-blue px-4 py-2 text-white">Salvar</button>' : ''}
        ${user.profissional_saude === 1 ? `<button type="button" class="btn-imprimir rounded-xl border border-slate-300 px-4 py-2" data-id="${evolucao.id}">Imprimir/PDF</button>` : ''}
      </div>
    </div>
  `;

  modal.querySelector('.btn-fechar')?.addEventListener('click', () => {
    destroyEditor();
    modal.remove();
  });
  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      destroyEditor();
      modal.remove();
    }
  });
  document.body.appendChild(modal);
  if (canEdit) initEditor();

  modal.querySelector('.btn-salvar')?.addEventListener('click', async () => {
    await putEvolucao(id, {
      data_evolucao: modal.querySelector('#evolucao-data')?.value,
      hora_evolucao: modal.querySelector('#evolucao-hora')?.value,
      conteudo: getEditorValue(),
    });
    destroyEditor();
    modal.remove();
    await onSaved();
  });

  modal.querySelector('.btn-imprimir')?.addEventListener('click', () => {
    window.open(getPdfPath(evolucao.id), '_blank', 'noopener');
  });
}

async function openNovaEvolucaoModal(prefill, onSaved) {
  const [paciente, consulta] = await Promise.all([
    prefill.aluno_id ? getPaciente(prefill.aluno_id) : Promise.resolve(null),
    prefill.consulta_id ? getConsulta(prefill.consulta_id) : Promise.resolve(null),
  ]);

  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4';
  modal.innerHTML = `
    <div class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h3 class="text-xl font-semibold text-slate-900">Nova evolu\u00E7\u00E3o cl\u00EDnica</h3>
          <p class="text-sm text-slate-500">${escapeHtml(paciente?.Nome || '')}</p>
        </div>
        <button type="button" class="btn-fechar rounded-xl border border-slate-300 px-4 py-2">Fechar</button>
      </div>
      <div class="mt-4 grid gap-3 md:grid-cols-2">
        <input type="date" id="evolucao-data" class="rounded-xl border border-slate-300 px-4 py-3" value="${escapeHtml(consulta?.data_consulta || new Date().toISOString().slice(0, 10))}">
        <input type="time" id="evolucao-hora" class="rounded-xl border border-slate-300 px-4 py-3" value="${escapeHtml(String(consulta?.hora_inicio_prevista || '08:00').slice(0, 5))}">
      </div>
      <div class="mt-4">
        <textarea id="evolucao-conteudo" class="w-full rounded-2xl border border-slate-300 px-4 py-3"></textarea>
      </div>
      <div class="mt-4 flex justify-end gap-2">
        <button type="button" class="btn-salvar rounded-xl bg-monday-blue px-4 py-2 text-white">Salvar</button>
      </div>
    </div>
  `;

  modal.querySelector('.btn-fechar')?.addEventListener('click', () => {
    destroyEditor();
    modal.remove();
  });
  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      destroyEditor();
      modal.remove();
    }
  });
  document.body.appendChild(modal);
  const textarea = modal.querySelector('#evolucao-conteudo');
  if (textarea) textarea.value = TEMPLATE_INICIAL;
  initEditor();

  modal.querySelector('.btn-salvar')?.addEventListener('click', async () => {
    await postEvolucao({
      aluno_id: prefill.aluno_id,
      consulta_id: prefill.consulta_id || null,
      especialidade_id: consulta?.especialidade_id || null,
      data_evolucao: modal.querySelector('#evolucao-data')?.value,
      hora_evolucao: modal.querySelector('#evolucao-hora')?.value,
      conteudo: getEditorValue(),
    });
    destroyEditor();
    modal.remove();
    await onSaved();
  });
}

export async function renderEvolucoes(container, opts = {}) {
  const user = getCurrentClinicaUser();
  if (user.profissional_saude !== 1) {
    container.innerHTML = '<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">Acesso restrito a profissionais de sa\u00FAde.</div>';
    return;
  }

  container.innerHTML = getSpinnerHtml('Carregando evolu\u00E7\u00F5es...');
  const alunoIdSelecionado = parseInt(opts.aluno_id || '', 10);
  const temPacienteSelecionado = Number.isFinite(alunoIdSelecionado) && alunoIdSelecionado > 0;
  const [profissionais, especialidades, pacienteSelecionado, evolucoes] = await Promise.all([
    getProfissionais().catch(() => []),
    getEspecialidades().catch(() => []),
    temPacienteSelecionado ? getPaciente(alunoIdSelecionado).catch(() => null) : Promise.resolve(null),
    temPacienteSelecionado ? getEvolucoes(opts).catch(() => []) : Promise.resolve([]),
  ]);

  const colaborador = getCurrentUserProfile();
  const colaboradorNome = escapeAttribute(colaborador.nome || 'Profissional');
  const colaboradorFoto = escapeAttribute(colaborador.fotoUrl || getFallbackFotoUrl());
  const listaHtml = !temPacienteSelecionado
    ? '<p class="py-8 text-center text-gray-500">Busque e selecione um benefici\u00E1rio para listar o hist\u00F3rico.</p>'
    : evolucoes.length === 0
      ? '<p class="py-8 text-center text-gray-500">Nenhum atendimento encontrado para este benefici\u00E1rio.</p>'
      : `
      <div class="space-y-3" id="evol-list">
        ${evolucoes.map((item) => `
          <div class="rounded-2xl border border-slate-50 bg-white p-4 shadow-sm transition-all hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <p class="truncate text-sm font-semibold text-slate-900">${escapeHtml(item.paciente_nome || '')}</p>
                  <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">${escapeHtml(item.tipo_nome || item.registro_tipo || 'Consulta')}</span>
                </div>
                <p class="mt-1 text-xs text-slate-500">${escapeHtml(fmtDateTime(item.data_evolucao, item.hora_evolucao))}</p>
                <p class="mt-0.5 text-xs text-slate-500">Profissional: ${escapeHtml(item.profissional_nome || '')}</p>
                <p class="mt-0.5 text-xs text-slate-500">Resumo: ${escapeHtml(resumoAtendimento(item))}</p>
              </div>
              <div class="flex shrink-0 flex-wrap justify-end gap-1">
                ${item.tem_evolucao ? `<button type="button" class="btn-ver-evolucao min-h-touch rounded-xl bg-blue-50 px-3 py-2 text-xs font-medium text-monday-blue hover:bg-blue-100" data-id="${item.evolucao_id}" data-autor="${item.profissional_id}">Evolu\u00E7\u00E3o</button>` : ''}
                ${(item.tem_anamnese_adulto || item.tem_anamnese_infantojuvenil) ? `<button type="button" class="btn-ver-anamnese min-h-touch rounded-xl border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50" data-consulta-id="${item.consulta_id}" data-aluno-id="${item.aluno_id}">Anamnese</button>` : ''}
                ${item.tem_prontuario ? `<button type="button" class="btn-ver-prontuario min-h-touch rounded-xl border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50" data-aluno-id="${item.aluno_id}">Prontu\u00E1rio</button>` : ''}
                ${!item.tem_evolucao && !item.tem_anamnese_adulto && !item.tem_anamnese_infantojuvenil && !item.tem_prontuario ? '<span class="px-2 py-2 text-xs text-slate-400">Sem registros</span>' : ''}
              </div>
            </div>
          </div>
        `).join('')}
      </div>
    `;

  container.innerHTML = `
    <section class="mx-auto w-full max-w-none 2xl:max-w-6xl">
      <div class="rounded-[28px] bg-slate-100/90 px-3 py-6 shadow-[0_18px_45px_rgba(15,23,42,0.08)] sm:px-4 lg:px-6 lg:py-7">
        <div class="flex items-start justify-between gap-4 lg:items-center">
          <div>
            <p class="text-sm font-medium text-slate-400">Cl\u00EDnica M\u00E9dica</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 lg:text-[2.15rem]">Evolu\u00E7\u00F5es</h1>
          </div>
          <div class="h-10 w-10 overflow-hidden rounded-full border-2 border-white bg-slate-200 shadow-sm">
            <img
              alt="${colaboradorNome}"
              class="h-full w-full object-cover"
              src="${colaboradorFoto}"
              onerror="this.onerror=null;this.src='${escapeAttribute(getFallbackFotoUrl())}';"
            >
          </div>
        </div>
        <div class="lg:mt-6 lg:flex lg:items-end lg:justify-between lg:gap-5">
          <div class="lg:max-w-4xl lg:flex-1">
            <div class="mt-6 grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4 lg:mt-0">
              <label class="relative block sm:col-span-2 xl:col-span-4">
                <span class="mb-2 block px-1 text-sm font-medium text-slate-500">Benefici\u00E1rio</span>
                <input type="hidden" id="filtro-aluno" value="${escapeHtml(opts.aluno_id || '')}">
                <input type="search" id="filtro-paciente-busca" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3.5 text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Digite nome, CPF ou contato do benefici\u00E1rio" value="${escapeHtml(pacienteSelecionado?.Nome || '')}" autocomplete="off">
                <div id="filtro-paciente-resultados" class="absolute z-30 mt-2 hidden max-h-72 w-full overflow-auto rounded-2xl border border-slate-200 bg-white shadow-xl"></div>
                <p id="filtro-paciente-meta" class="mt-2 px-1 text-xs text-slate-500">${pacienteSelecionado ? escapeHtml(pacienteMeta(pacienteSelecionado)) : 'Selecione um benefici\u00E1rio para carregar o hist\u00F3rico.'}</p>
              </label>
              <label class="block">
                <span class="mb-2 block px-1 text-sm font-medium text-slate-500">Data</span>
                <input type="date" id="filtro-data" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3.5 text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20" value="${escapeHtml(opts.data || '')}">
              </label>
              <label class="block">
                <span class="mb-2 block px-1 text-sm font-medium text-slate-500">Profissional</span>
                <select id="filtro-profissional" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3.5 text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                  <option value="">Todos</option>
                  ${profissionais.map((item) => `<option value="${item.id}" ${String(opts.profissional_id || '') === String(item.id) ? 'selected' : ''}>${escapeHtml(item.nome)}</option>`).join('')}
                </select>
              </label>
              <label class="block">
                <span class="mb-2 block px-1 text-sm font-medium text-slate-500">Especialidade</span>
                <select id="filtro-especialidade" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3.5 text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                  <option value="">Todas</option>
                  ${especialidades.map((item) => `<option value="${item.id}" ${String(opts.especialidade_id || '') === String(item.id) ? 'selected' : ''}>${escapeHtml(item.nome)}</option>`).join('')}
                </select>
              </label>
              <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-1">
                <button type="button" id="btn-pesquisar-filtros" class="min-h-touch rounded-xl bg-monday-blue px-4 py-3 text-sm font-semibold text-white" ${temPacienteSelecionado ? '' : 'disabled'}>Pesquisar</button>
                <button type="button" id="btn-limpar-filtros" class="min-h-touch rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-600">Limpar</button>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold text-slate-800">Registros cl\u00EDnicos</h2>
            <p class="text-sm text-slate-500">Hist\u00F3rico consolidado de consultas, anamneses, prontu\u00E1rios e evolu\u00E7\u00F5es do paciente.</p>
          </div>
          <button type="button" id="btn-nova-evolucao" class="rounded-xl bg-monday-blue px-4 py-2 text-sm font-medium text-white ${temPacienteSelecionado ? '' : 'opacity-50 cursor-not-allowed'}" ${temPacienteSelecionado ? '' : 'disabled'}>Nova evolu\u00E7\u00E3o</button>
        </div>
        <div class="mt-5">
          ${listaHtml}
        </div>
      </div>
    </section>
  `;

  const reload = async (extra = {}) => {
    const alunoId = parseInt(container.querySelector('#filtro-aluno')?.value || '', 10);
    const data = String(container.querySelector('#filtro-data')?.value || '').trim();
    const profissionalId = String(container.querySelector('#filtro-profissional')?.value || '').trim();
    const especialidadeId = String(container.querySelector('#filtro-especialidade')?.value || '').trim();
    const nextOptions = {
      ...(Number.isFinite(alunoId) && alunoId > 0 ? { aluno_id: alunoId } : {}),
      ...(data ? { data } : {}),
      ...(profissionalId ? { profissional_id: profissionalId } : {}),
      ...(especialidadeId ? { especialidade_id: especialidadeId } : {}),
      ...extra,
    };
    window.dispatchEvent(new CustomEvent('navigate-to', { detail: { page: 'evolucoes', options: nextOptions } }));
  };

  const pacienteBuscaEl = container.querySelector('#filtro-paciente-busca');
  const pacienteResultadosEl = container.querySelector('#filtro-paciente-resultados');
  const pacienteMetaEl = container.querySelector('#filtro-paciente-meta');
  const pacienteIdEl = container.querySelector('#filtro-aluno');
  const btnPesquisarFiltros = container.querySelector('#btn-pesquisar-filtros');
  const btnLimparFiltros = container.querySelector('#btn-limpar-filtros');
  let pacienteSearchTimer = null;

  const esconderResultadosPaciente = () => {
    if (pacienteResultadosEl) {
      pacienteResultadosEl.classList.add('hidden');
      pacienteResultadosEl.innerHTML = '';
    }
  };

  const executarBusca = async () => {
    if (!btnPesquisarFiltros) return;
    btnPesquisarFiltros.disabled = true;
    btnPesquisarFiltros.innerHTML = `${getButtonSpinnerHtml()} Pesquisando...`;
    try {
      reload();
    } finally {
      btnPesquisarFiltros.disabled = false;
      btnPesquisarFiltros.textContent = 'Pesquisar';
    }
  };

  pacienteBuscaEl?.addEventListener('input', () => {
    const term = String(pacienteBuscaEl.value || '').trim();
    if (pacienteIdEl) pacienteIdEl.value = '';
    if (pacienteMetaEl) pacienteMetaEl.textContent = 'Selecione um benefici\u00E1rio para carregar o hist\u00F3rico.';
    if (btnPesquisarFiltros) btnPesquisarFiltros.disabled = true;
    clearTimeout(pacienteSearchTimer);
    if (term.length < 2) {
      esconderResultadosPaciente();
      return;
    }
    pacienteSearchTimer = setTimeout(async () => {
      try {
        const { pacientes } = await getPacientes(term, { limit: 8 });
        if (!pacienteResultadosEl) return;
        if (!Array.isArray(pacientes) || pacientes.length === 0) {
          pacienteResultadosEl.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">Nenhum benefici\u00E1rio encontrado.</div>';
          pacienteResultadosEl.classList.remove('hidden');
          return;
        }
        pacienteResultadosEl.innerHTML = pacientes.map((paciente) => `
          <button type="button" class="paciente-opcao block w-full px-4 py-3 text-left hover:bg-slate-50" data-id="${paciente.IdUsuario}" data-nome="${escapeHtml(paciente.Nome || '')}">
            <span class="block font-medium text-slate-900">${escapeHtml(paciente.Nome || '')}</span>
            <span class="block text-xs text-slate-500">${escapeHtml(pacienteMeta(paciente))}</span>
          </button>
        `).join('');
        pacienteResultadosEl.classList.remove('hidden');
        pacienteResultadosEl.querySelectorAll('.paciente-opcao').forEach((button) => {
          button.addEventListener('click', () => {
            if (pacienteIdEl) pacienteIdEl.value = button.dataset.id || '';
            if (pacienteBuscaEl) pacienteBuscaEl.value = button.dataset.nome || '';
            if (pacienteMetaEl) {
              const paciente = pacientes.find((item) => String(item.IdUsuario) === String(button.dataset.id || ''));
              pacienteMetaEl.textContent = paciente ? pacienteMeta(paciente) : '';
            }
            if (btnPesquisarFiltros) btnPesquisarFiltros.disabled = false;
            esconderResultadosPaciente();
            reload({ aluno_id: Number(button.dataset.id || 0) });
          });
        });
      } catch {
        esconderResultadosPaciente();
      }
    }, 250);
  });

  pacienteBuscaEl?.addEventListener('search', () => {
    if (!pacienteBuscaEl.value && pacienteIdEl) {
      pacienteIdEl.value = '';
      reload({ aluno_id: undefined });
    }
  });

  pacienteBuscaEl?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && pacienteIdEl?.value) {
      event.preventDefault();
      executarBusca();
    }
  });

  document.addEventListener('click', (event) => {
    if (!container.contains(event.target)) esconderResultadosPaciente();
  }, { once: true });

  [container.querySelector('#filtro-data'), container.querySelector('#filtro-profissional'), container.querySelector('#filtro-especialidade')].forEach((el) => {
    el?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') executarBusca();
    });
  });

  btnPesquisarFiltros?.addEventListener('click', executarBusca);
  btnLimparFiltros?.addEventListener('click', () => {
    reload({
      aluno_id: undefined,
      data: undefined,
      profissional_id: undefined,
      especialidade_id: undefined,
    });
  });

  container.querySelectorAll('.btn-ver-evolucao').forEach((button) => {
    button.addEventListener('click', async () => {
      const canEdit = Number(button.dataset.autor || 0) === Number(user.id || 0) || user.is_admin === 1;
      await openDetalheModal(button.dataset.id, canEdit, async () => reload());
    });
  });

  container.querySelectorAll('.btn-ver-anamnese').forEach((button) => {
    button.addEventListener('click', () => {
      window.dispatchEvent(new CustomEvent('open-atendimento', {
        detail: { consultaId: Number(button.dataset.consultaId || 0) }
      }));
    });
  });

  container.querySelectorAll('.btn-ver-prontuario').forEach((button) => {
    button.addEventListener('click', () => {
      window.dispatchEvent(new CustomEvent('navigate-to', {
        detail: { page: 'prontuarios', options: { aluno_id: Number(button.dataset.alunoId || 0) } }
      }));
    });
  });

  container.querySelector('#btn-nova-evolucao')?.addEventListener('click', async () => {
    if (!opts.aluno_id) return;
    await openNovaEvolucaoModal(opts, async () => reload());
  });

  if (opts.nova && opts.aluno_id) {
    await openNovaEvolucaoModal(opts, async () => reload({ nova: undefined }));
  }
}
