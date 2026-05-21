import {
  getProntuarios,
  getProntuario,
  putProntuario,
  deleteProntuario,
  getProntuarioPdfEndpoint,
  getConsultasAguardandoProntuario,
  getProfissionais,
  postProntuarioStreamIa,
  postProntuario,
  deleteAnamnese,
  deleteAnamneseRoteiro,
  getCursosFiltroProntuarios,
  getTurmasFiltroProntuarios,
  getBeneficiariosFiltroProntuarios
} from '../services/api.js?v=20260502a';
import { markdownToHtml } from '../utils/markdownToHtml.js?v=20260301d';
import { getSpinnerHtml, getButtonSpinnerHtml, showLoadingOverlay, hideLoadingOverlay } from '../utils/loading.js?v=20260301d';

function fmtTime(t) {
  if (!t) return '';
  const [h, m] = String(t).split(':');
  return `${h}:${m || '00'}`;
}

function escapeHtml(s) {
  const d = document.createElement('div');
  d.textContent = s ?? '';
  return d.innerHTML;
}

function escapeAttribute(s) {
  return escapeHtml(String(s ?? '')).replace(/"/g, '&quot;');
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

function getCurrentUser() {
  return (window.__CLINICA_BOOTSTRAP__ || {}).usuario || {};
}

function getPacienteFotoUrl(paciente) {
  const rawUrl = String(paciente?.FotoUrl || '').trim();
  if (rawUrl !== '') return rawUrl;

  const rawFoto = String(paciente?.Foto || '').trim();
  if (rawFoto === '') return getFallbackFotoUrl();

  const safeFileName = encodeURIComponent(rawFoto.split('/').pop());
  const currentUrl = typeof window !== 'undefined' && window.location ? window.location.href : 'http://localhost/clinica/';
  return new URL(`../assets/img/fotos/${safeFileName}`, currentUrl).toString();
}

const PDF_OPCOES_PADRAO = {
  incluir_profissional: true,
  incluir_data_hora: true,
  incluir_foto: true,
  incluir_cursos_turmas: true
};

function normalizarOpcoesPdf(opcoes) {
  return {
    ...PDF_OPCOES_PADRAO,
    ...(opcoes && typeof opcoes === 'object' ? opcoes : {})
  };
}

function appendPdfOptionInputs(form, opcoes) {
  const normalized = normalizarOpcoesPdf(opcoes);
  Object.entries(normalized).forEach(([name, checked]) => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = checked ? '1' : '0';
    form.appendChild(input);
  });
}

function abrirProntuarioPdf(prontuarioId, assinar, opcoes) {
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = getProntuarioPdfEndpoint();
  form.target = '_blank';
  form.style.display = 'none';

  const idInput = document.createElement('input');
  idInput.type = 'hidden';
  idInput.name = 'id';
  idInput.value = String(prontuarioId);
  form.appendChild(idInput);

  const assinarInput = document.createElement('input');
  assinarInput.type = 'hidden';
  assinarInput.name = 'assinar';
  assinarInput.value = assinar ? '1' : '0';
  form.appendChild(assinarInput);
  appendPdfOptionInputs(form, opcoes);

  document.body.appendChild(form);
  form.submit();
  form.remove();
}

function normalizarProntuariosLote(prontuarios) {
  if (!Array.isArray(prontuarios)) return [];
  return prontuarios
    .map((item) => {
      if (item && typeof item === 'object') {
        const id = parseInt(item.id, 10);
        const nome = String(item.nome || '').trim();
        return Number.isInteger(id) && id > 0 ? { id, nome } : null;
      }
      const id = parseInt(item, 10);
      return Number.isInteger(id) && id > 0 ? { id, nome: '' } : null;
    })
    .filter((item) => item !== null);
}

function abrirProntuariosPdfLote(prontuarios, assinar, opcoes, modoLote = 'unico') {
  const items = normalizarProntuariosLote(prontuarios);
  const ids = items.map((item) => item.id);
  if (ids.length === 0) return;
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = getProntuarioPdfEndpoint();
  form.target = '_blank';
  form.style.display = 'none';

  ids.forEach((id) => {
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'ids[]';
    idInput.value = String(id);
    form.appendChild(idInput);
  });

  const assinarInput = document.createElement('input');
  assinarInput.type = 'hidden';
  assinarInput.name = 'assinar';
  assinarInput.value = assinar && modoLote !== 'individual' ? '1' : '0';
  form.appendChild(assinarInput);

  const modoLoteInput = document.createElement('input');
  modoLoteInput.type = 'hidden';
  modoLoteInput.name = 'modo_lote';
  modoLoteInput.value = modoLote === 'individual' ? 'individual' : 'unico';
  form.appendChild(modoLoteInput);
  appendPdfOptionInputs(form, opcoes);

  document.body.appendChild(form);
  form.submit();
  form.remove();
}

function openModalEscolhaAssinatura(onChoose) {
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4';
  modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-monday-lg max-w-sm w-full p-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-3">Gerar documento em PDF</h3>
      <p class="text-gray-600 mb-3">Selecione os dados que quer que constem no PDF</p>
      <div class="mb-4 space-y-2">
        <div class="form-check form-switch">
          <input class="form-check-input pdf-opcao" type="checkbox" role="switch" id="pdf-opt-profissional" data-option="incluir_profissional" checked>
          <label class="form-check-label text-sm text-gray-700" for="pdf-opt-profissional">Profissional que atendeu</label>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input pdf-opcao" type="checkbox" role="switch" id="pdf-opt-data-hora" data-option="incluir_data_hora" checked>
          <label class="form-check-label text-sm text-gray-700" for="pdf-opt-data-hora">Data/hora do atendimento</label>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input pdf-opcao" type="checkbox" role="switch" id="pdf-opt-foto" data-option="incluir_foto" checked>
          <label class="form-check-label text-sm text-gray-700" for="pdf-opt-foto">Foto</label>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input pdf-opcao" type="checkbox" role="switch" id="pdf-opt-cursos-turmas" data-option="incluir_cursos_turmas" checked>
          <label class="form-check-label text-sm text-gray-700" for="pdf-opt-cursos-turmas">Cursos e turmas em que ele está matriculado</label>
        </div>
      </div>
      <p class="text-gray-600 mb-5">Você deseja assinar digitalmente este prontuário agora?</p>
      <div class="flex flex-col gap-2">
        <button type="button" class="btn-assinar-sim min-h-touch py-3 px-4 bg-monday-blue text-white rounded-xl font-medium">Sim, assinar digitalmente</button>
        <button type="button" class="btn-assinar-nao min-h-touch py-3 px-4 border border-gray-300 rounded-xl font-medium hover:bg-gray-50">Não, gerar sem assinatura</button>
      </div>
    </div>
  `;
  const getPdfOpcoesSelecionadas = () => {
    const opcoes = { ...PDF_OPCOES_PADRAO };
    modal.querySelectorAll('.pdf-opcao').forEach((input) => {
      const key = String(input.dataset.option || '').trim();
      if (key) opcoes[key] = !!input.checked;
    });
    return opcoes;
  };

  modal.querySelector('.btn-assinar-sim').onclick = () => {
    const opcoes = getPdfOpcoesSelecionadas();
    modal.remove();
    onChoose(true, opcoes);
  };
  modal.querySelector('.btn-assinar-nao').onclick = () => {
    const opcoes = getPdfOpcoesSelecionadas();
    modal.remove();
    onChoose(false, opcoes);
  };
  modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
  document.body.appendChild(modal);
}

function openModalAssinarPdf(prontuarioId) {
  openModalEscolhaAssinatura((assinar, opcoes) => abrirProntuarioPdf(prontuarioId, assinar, opcoes));
}

function openModalAssinarPdfLote(prontuarios) {
  const items = normalizarProntuariosLote(prontuarios);
  if (items.length === 0) return;
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4';
  modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-monday-lg max-w-md w-full p-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-3">Gerar documento em PDF</h3>
      <p class="text-gray-600 mb-3">Selecione os dados que quer que constem no PDF</p>
      <div class="mb-4 space-y-2">
        <div class="form-check form-switch">
          <input class="form-check-input pdf-opcao" type="checkbox" role="switch" id="pdf-lote-opt-profissional" data-option="incluir_profissional" checked>
          <label class="form-check-label text-sm text-gray-700" for="pdf-lote-opt-profissional">Profissional que atendeu</label>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input pdf-opcao" type="checkbox" role="switch" id="pdf-lote-opt-data-hora" data-option="incluir_data_hora" checked>
          <label class="form-check-label text-sm text-gray-700" for="pdf-lote-opt-data-hora">Data/hora do atendimento</label>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input pdf-opcao" type="checkbox" role="switch" id="pdf-lote-opt-foto" data-option="incluir_foto" checked>
          <label class="form-check-label text-sm text-gray-700" for="pdf-lote-opt-foto">Foto</label>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input pdf-opcao" type="checkbox" role="switch" id="pdf-lote-opt-cursos-turmas" data-option="incluir_cursos_turmas" checked>
          <label class="form-check-label text-sm text-gray-700" for="pdf-lote-opt-cursos-turmas">Cursos e turmas em que ele está matriculado</label>
        </div>
      </div>

      <p class="text-gray-600 mb-2">Formato do download</p>
      <div class="mb-4 space-y-2">
        <label class="flex items-start gap-2 rounded-xl border border-slate-200 px-3 py-2 cursor-pointer">
          <input type="radio" name="pdf-lote-modo" value="unico" checked class="mt-1">
          <span class="text-sm text-slate-700">PDF único com todos os prontuários</span>
        </label>
        <label class="flex items-start gap-2 rounded-xl border border-slate-200 px-3 py-2 cursor-pointer">
          <input type="radio" name="pdf-lote-modo" value="individual" class="mt-1">
          <span class="text-sm text-slate-700">Um PDF por paciente (sem assinatura)</span>
        </label>
      </div>

      <div data-aviso-individual class="hidden mb-4 rounded-xl bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800">
        No modo individual, os PDFs serão gerados sem assinatura digital e baixados automaticamente um a um em uma nova guia.
      </div>

      <div data-acoes-unico>
        <p class="text-gray-600 mb-3">Você deseja assinar digitalmente os prontuários agora?</p>
        <div class="flex flex-col gap-2">
          <button type="button" class="btn-lote-assinar-sim min-h-touch py-3 px-4 bg-monday-blue text-white rounded-xl font-medium">Sim, assinar digitalmente</button>
          <button type="button" class="btn-lote-assinar-nao min-h-touch py-3 px-4 border border-gray-300 rounded-xl font-medium hover:bg-gray-50">Não, gerar sem assinatura</button>
        </div>
      </div>

      <div data-acoes-individual class="hidden">
        <button type="button" class="btn-lote-individual min-h-touch w-full py-3 px-4 bg-monday-blue text-white rounded-xl font-medium">Gerar um PDF por paciente</button>
      </div>
    </div>
  `;

  const getPdfOpcoesSelecionadas = () => {
    const opcoes = { ...PDF_OPCOES_PADRAO };
    modal.querySelectorAll('.pdf-opcao').forEach((input) => {
      const key = String(input.dataset.option || '').trim();
      if (key) opcoes[key] = !!input.checked;
    });
    return opcoes;
  };

  const getModoSelecionado = () => {
    const selected = modal.querySelector('input[name="pdf-lote-modo"]:checked');
    return selected?.value === 'individual' ? 'individual' : 'unico';
  };

  const syncModo = () => {
    const modo = getModoSelecionado();
    modal.querySelector('[data-acoes-unico]')?.classList.toggle('hidden', modo !== 'unico');
    modal.querySelector('[data-acoes-individual]')?.classList.toggle('hidden', modo !== 'individual');
    modal.querySelector('[data-aviso-individual]')?.classList.toggle('hidden', modo !== 'individual');
  };

  modal.querySelectorAll('input[name="pdf-lote-modo"]').forEach((input) => {
    input.addEventListener('change', syncModo);
  });

  modal.querySelector('.btn-lote-assinar-sim').onclick = () => {
    const opcoes = getPdfOpcoesSelecionadas();
    modal.remove();
    abrirProntuariosPdfLote(items, true, opcoes, 'unico');
  };

  modal.querySelector('.btn-lote-assinar-nao').onclick = () => {
    const opcoes = getPdfOpcoesSelecionadas();
    modal.remove();
    abrirProntuariosPdfLote(items, false, opcoes, 'unico');
  };

  modal.querySelector('.btn-lote-individual').onclick = () => {
    const opcoes = getPdfOpcoesSelecionadas();
    modal.remove();
    abrirProntuariosPdfLote(items, false, opcoes, 'individual');
  };

  modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
  syncModo();
  document.body.appendChild(modal);
}
let prontuariosFiltroPaciente = null;
let prontuariosFiltroNome = '';
let prontuariosFiltroData = '';
let prontuariosFiltroHoraInicio = '';
let prontuariosFiltroHoraFim = '';
let prontuariosFiltroProfissional = '';
let prontuariosFiltroCurso = '';
let prontuariosFiltroTurma = '';
let prontuariosBuscaRealizada = false;
let aguardandoBuscaRealizada = false;
let prontuariosTabAtiva = 'prontuarios';
let prontuariosSelecionados = new Set();
let prontuariosCursosDisponiveis = [];

function filtrarConsultasAguardandoProntuario(consultas, filtros = {}) {
  const {
    alunoId = null,
    nome = '',
    data = '',
    horaInicio = '',
    horaFim = '',
    profissionalId = '',
    idsPermitidosTurma = null,
  } = filtros;

  const termoNome = String(nome || '').trim().toLowerCase();
  const horaInicioFiltro = String(horaInicio || '').trim();
  const horaFimFiltro = String(horaFim || '').trim();
  const dataFiltro = String(data || '').trim();
  const profissionalFiltro = String(profissionalId || '').trim();
  const temFiltroTurma = idsPermitidosTurma instanceof Set && idsPermitidosTurma.size > 0;

  return (Array.isArray(consultas) ? consultas : []).filter((consulta) => {
    const consultaAlunoId = Number(consulta?.aluno_id || 0);
    const consultaNome = String(consulta?.paciente_nome || '').trim().toLowerCase();
    const consultaData = String(consulta?.data_consulta || '').trim();
    const consultaHora = String(consulta?.hora_inicio_prevista || '').slice(0, 5);
    const consultaProfissionalId = String(consulta?.profissional_id || '').trim();

    if (alunoId && consultaAlunoId !== Number(alunoId)) return false;
    if (!alunoId && termoNome && !consultaNome.includes(termoNome)) return false;
    if (dataFiltro && consultaData !== dataFiltro) return false;
    if (horaInicioFiltro && consultaHora && consultaHora < horaInicioFiltro) return false;
    if (horaFimFiltro && consultaHora && consultaHora > horaFimFiltro) return false;
    if (profissionalFiltro && consultaProfissionalId !== profissionalFiltro) return false;
    if (temFiltroTurma && !idsPermitidosTurma.has(consultaAlunoId)) return false;
    return true;
  });
}

export async function renderProntuarios(container, opts = {}) {
  if (opts?.aluno_id !== undefined) {
    const parsedAlunoId = opts.aluno_id ? parseInt(opts.aluno_id, 10) : null;
    prontuariosFiltroPaciente = Number.isInteger(parsedAlunoId) ? parsedAlunoId : null;
    prontuariosBuscaRealizada = prontuariosFiltroPaciente !== null;
    prontuariosFiltroNome = '';
    prontuariosFiltroCurso = '';
    prontuariosFiltroTurma = '';
    prontuariosSelecionados = new Set();
  }
  const cursoIdSelecionado = parseInt(prontuariosFiltroCurso, 10);
  const turmaIdSelecionada = parseInt(prontuariosFiltroTurma, 10);
  const alunoIdFiltro = prontuariosFiltroPaciente || null;
  container.innerHTML = getSpinnerHtml('Carregando...');
  let prontuarios = [];
  let consultasAguardando = [];
  let consultasAguardandoFiltradas = [];
  let profissionaisFiltro = [];
  let turmasDisponiveis = [];
  let idsPermitidosTurma = new Set();
  try {
    const cursosPromise = prontuariosCursosDisponiveis.length > 0
      ? Promise.resolve(prontuariosCursosDisponiveis)
      : getCursosFiltroProntuarios(1).catch(() => []);
    const turmasPromise = Number.isInteger(cursoIdSelecionado) && cursoIdSelecionado > 0
      ? getTurmasFiltroProntuarios(cursoIdSelecionado, 1).catch(() => [])
      : Promise.resolve([]);
    const beneficiariosPromise = Number.isInteger(cursoIdSelecionado) && cursoIdSelecionado > 0 && Number.isInteger(turmaIdSelecionada) && turmaIdSelecionada > 0
      ? getBeneficiariosFiltroProntuarios(cursoIdSelecionado, turmaIdSelecionada).catch(() => [])
      : Promise.resolve([]);

    const filtrosApi = {
      aluno_id: alunoIdFiltro || undefined,
      paciente_nome: alunoIdFiltro ? undefined : (prontuariosFiltroNome || undefined),
      data: prontuariosFiltroData || undefined,
      hora_inicio: prontuariosFiltroHoraInicio || undefined,
      hora_fim: prontuariosFiltroHoraFim || undefined,
      profissional_id: prontuariosFiltroProfissional || undefined
    };
    const [prontRes, aguardandoRes, profissionaisRes, cursosRes, turmasRes, beneficiariosRes] = await Promise.all([
      prontuariosBuscaRealizada ? getProntuarios(filtrosApi) : Promise.resolve({ prontuarios: [] }),
      getConsultasAguardandoProntuario().catch(() => []),
      getProfissionais().catch(() => []),
      cursosPromise,
      turmasPromise,
      beneficiariosPromise
    ]);
    prontuarios = prontRes?.prontuarios ?? [];
    consultasAguardando = Array.isArray(aguardandoRes) ? aguardandoRes : [];
    profissionaisFiltro = Array.isArray(profissionaisRes) ? profissionaisRes : [];
    prontuariosCursosDisponiveis = Array.isArray(cursosRes) ? cursosRes : [];
    turmasDisponiveis = Array.isArray(turmasRes) ? turmasRes : [];
    const beneficiariosTurma = Array.isArray(beneficiariosRes) ? beneficiariosRes : [];
    idsPermitidosTurma = new Set(
      beneficiariosTurma
        .map((item) => parseInt(item.id, 10))
        .filter((id) => Number.isInteger(id) && id > 0)
    );

    if (prontuariosFiltroTurma) {
      const turmaExiste = turmasDisponiveis.some((turma) => String(turma.value) === String(prontuariosFiltroTurma));
      if (!turmaExiste) {
        prontuariosFiltroTurma = '';
        prontuariosFiltroPaciente = null;
        idsPermitidosTurma = new Set();
      }
    }

    if (Number.isInteger(turmaIdSelecionada) && turmaIdSelecionada > 0 && prontuariosBuscaRealizada) {
      prontuarios = prontuarios.filter((item) => idsPermitidosTurma.has(Number(item.aluno_id)));
    }

    consultasAguardandoFiltradas = aguardandoBuscaRealizada
      ? filtrarConsultasAguardandoProntuario(consultasAguardando, {
          alunoId: alunoIdFiltro,
          nome: prontuariosFiltroNome,
          data: prontuariosFiltroData,
          horaInicio: prontuariosFiltroHoraInicio,
          horaFim: prontuariosFiltroHoraFim,
          profissionalId: prontuariosFiltroProfissional,
          idsPermitidosTurma: Number.isInteger(turmaIdSelecionada) && turmaIdSelecionada > 0 ? idsPermitidosTurma : null,
        })
      : consultasAguardando;

    const idsAtuais = new Set(prontuarios.map((p) => Number(p.id)));
    prontuariosSelecionados = new Set(Array.from(prontuariosSelecionados).filter((id) => idsAtuais.has(Number(id))));
  } catch (e) {
    if (String(e?.message || '').toLowerCase().includes('autentic')) throw e;
    console.error('Erro ao carregar prontu?rios:', e);
  }

  if (alunoIdFiltro && !prontuariosFiltroNome && prontuarios.length > 0) {
    prontuariosFiltroNome = String(prontuarios[0]?.paciente_nome || '');
  }

  const filtroOptionsHtml = '';
  const filtroSelectHtml = '';
  const cursosOptionsHtml = prontuariosCursosDisponiveis
    .map((curso) => `<option value="${escapeAttribute(curso.value)}" ${String(prontuariosFiltroCurso) === String(curso.value) ? 'selected' : ''}>${escapeHtml(curso.label)}</option>`)
    .join('');
  const turmasOptionsHtml = turmasDisponiveis
    .map((turma) => `<option value="${escapeAttribute(turma.value)}" ${String(prontuariosFiltroTurma) === String(turma.value) ? 'selected' : ''}>${escapeHtml(turma.label)}</option>`)
    .join('');

  const colaborador = getCurrentUser();
  const colaboradorNome = escapeAttribute(colaborador.nome || 'Perfil do Médico');
  const colaboradorFoto = escapeAttribute(colaborador.fotoUrl || getFallbackFotoUrl());

  const shellPrefix = `
    <section class="mx-auto w-full max-w-none 2xl:max-w-6xl">
      <div class="rounded-[28px] bg-slate-100/90 px-3 py-6 shadow-[0_18px_45px_rgba(15,23,42,0.08)] sm:px-4 lg:px-6 lg:py-7">
        <div class="flex items-start justify-between gap-4 lg:items-center">
          <div>
            <p class="text-sm font-medium text-slate-400">Clínica Médica</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 lg:text-[2.15rem]">Prontuários</h1>
          </div>
          <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center overflow-hidden border-2 border-white shadow-sm">
            <span class="text-sm font-semibold text-slate-500">P</span>
          </div>
        </div>
        <div class="lg:mt-6 lg:flex lg:items-end lg:justify-between lg:gap-5">
          <div class="lg:flex-1 lg:max-w-3xl"></div>
        </div>
  `;

  const shellSuffix = `
      </div>
    </section>
  `;

  const tabHtml = `
    <h2 class="text-lg font-semibold text-gray-800 mb-3">Prontu&aacute;rios</h2>
    ${filtroSelectHtml}
    <div class="flex flex-nowrap gap-2 border-b-2 border-gray-200 mb-4 overflow-x-auto">
      <button type="button" data-tab="prontuarios" class="tab-pront shrink-0 px-4 py-3 font-medium text-monday-blue border-b-2 border-monday-blue -mb-0.5 min-h-touch">Prontu&aacute;rios salvos</button>
      <button type="button" data-tab="aguardando" class="tab-pront shrink-0 px-4 py-3 font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 min-h-touch flex items-center gap-1">Aguardando prontu&aacute;rio <span class="text-xs px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-semibold">${consultasAguardando.length}</span></button>
    </div>
  `;

  const shellHtml = `
    <section class="mx-auto w-full max-w-none 2xl:max-w-6xl">
      <div class="rounded-[28px] bg-slate-100/90 px-3 py-6 shadow-[0_18px_45px_rgba(15,23,42,0.08)] sm:px-4 lg:px-6 lg:py-7">
        <div class="flex items-start justify-between gap-4 lg:items-center">
          <div>
            <p class="text-sm font-medium text-slate-400">Cl&iacute;nica M&eacute;dica</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 lg:text-[2.15rem]">Prontu&aacute;rios</h1>
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
          <div class="lg:flex-1 lg:max-w-4xl">
            <div class="mt-6 grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4 lg:mt-0">
              <label class="block">
                <span class="mb-2 block px-1 text-sm font-medium text-slate-500">Paciente (nome livre)</span>
                <input type="text" id="filtro-paciente-pront" list="pacientes-pront-list" value="${escapeAttribute(prontuariosFiltroNome || '')}" placeholder="Digite para filtrar" class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-2xl text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                <datalist id="pacientes-pront-list">${filtroOptionsHtml}</datalist>
              </label>
              <label class="block">
                <span class="mb-2 block px-1 text-sm font-medium text-slate-500">Data</span>
                <input type="date" id="filtro-data-pront" value="${escapeAttribute(prontuariosFiltroData || '')}" class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-2xl text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
              </label>
              <label class="block">
                <span class="mb-2 block px-1 text-sm font-medium text-slate-500">Profissional</span>
                <select id="filtro-profissional-pront" class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-2xl text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                  <option value="">Todos</option>
                  ${profissionaisFiltro.map((p) => `<option value="${p.id}" ${String(prontuariosFiltroProfissional) === String(p.id) ? 'selected' : ''}>${escapeHtml(p.nome || '')}</option>`).join('')}
                </select>
              </label>
              <label class="block">
                <span class="mb-2 block px-1 text-sm font-medium text-slate-500">Hora inicial</span>
                <input type="time" id="filtro-hora-inicio-pront" value="${escapeAttribute(prontuariosFiltroHoraInicio || '')}" class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-2xl text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
              </label>
              <label class="block">
                <span class="mb-2 block px-1 text-sm font-medium text-slate-500">Hora final</span>
                <input type="time" id="filtro-hora-fim-pront" value="${escapeAttribute(prontuariosFiltroHoraFim || '')}" class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-2xl text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
              </label>
              <label class="block">
                <span class="mb-2 block px-1 text-sm font-medium text-slate-500">Curso</span>
                <select id="filtro-curso-pront" class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-2xl text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                  <option value="">Selecione o curso</option>
                  ${cursosOptionsHtml}
                </select>
              </label>
              <label class="block">
                <span class="mb-2 block px-1 text-sm font-medium text-slate-500">Turma</span>
                <select id="filtro-turma-pront" class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-2xl text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20" ${!prontuariosFiltroCurso ? 'disabled' : ''}>
                  <option value="">${prontuariosFiltroCurso ? 'Selecione a turma' : 'Selecione o curso primeiro'}</option>
                  ${turmasOptionsHtml}
                </select>
              </label>
              <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-2">
                <button type="button" id="btn-pesquisar-pront" class="min-h-touch px-4 py-3 bg-monday-blue text-white rounded-xl text-sm font-semibold">Pesquisar</button>
                <button type="button" id="btn-limpar-pront" class="min-h-touch px-4 py-3 border border-slate-300 rounded-xl text-sm font-semibold text-slate-600">Limpar</button>
              </div>
            </div>
          </div>
        </div>
  `;

  const tabsShellHtml = `
        <div class="mt-6 flex flex-nowrap gap-2 overflow-x-auto border-b border-slate-200 pb-1">
          <button type="button" data-tab="prontuarios" class="tab-pront shrink-0 rounded-full bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm min-h-touch">Prontu&aacute;rios salvos</button>
          <button type="button" data-tab="aguardando" class="tab-pront shrink-0 rounded-full bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 ring-1 ring-slate-200 transition hover:text-slate-700 min-h-touch flex items-center gap-1.5">Aguardando prontu&aacute;rio <span class="text-xs px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-semibold">${consultasAguardando.length}</span></button>
        </div>
  `;

  const prontuariosHtml = !prontuariosBuscaRealizada
    ? '<p class="text-gray-500 py-8 text-center">Clique em "Pesquisar" para listar os prontu&aacute;rios.</p>'
    : prontuarios.length === 0
      ? '<p class="text-gray-500 py-8 text-center">Nenhum prontu&aacute;rio registrado.</p>'
      : `
      <div class="mb-3 flex flex-wrap items-center gap-2">
        <button type="button" id="btn-selecionar-todos-pront" class="px-3 py-2 text-sm border border-slate-300 rounded-xl hover:bg-slate-50">Selecionar todos</button>
        <button type="button" id="btn-baixar-selecionados-pront" class="px-3 py-2 text-sm bg-monday-blue text-white rounded-xl ${prontuariosSelecionados.size === 0 ? 'opacity-50 cursor-not-allowed' : ''}" ${prontuariosSelecionados.size === 0 ? 'disabled' : ''}>Baixar selecionados (${prontuariosSelecionados.size})</button>
      </div>
      <div class="space-y-2" id="pront-list">
        ${prontuarios.map((p) => {
          const dt = p.created_at ? new Date(p.created_at).toLocaleString('pt-BR') : '';
          const canEdit = p.can_edit === true;
          const checked = prontuariosSelecionados.has(Number(p.id));
          return `
          <div class="pront-card bg-white p-3 rounded-2xl shadow-sm border border-slate-50 transition-all hover:shadow-md" data-id="${p.id}">
            <div class="flex items-start gap-2 sm:gap-3">
              <input type="checkbox" class="chk-pront mt-1 h-4 w-4 rounded border-slate-300" data-id="${p.id}" ${checked ? 'checked' : ''}>
              <img
                alt="Foto de ${escapeAttribute(String(p.paciente_nome || 'Paciente'))}"
                class="h-11 w-11 rounded-full object-cover shrink-0"
                src="${escapeAttribute(getPacienteFotoUrl(p))}"
                loading="lazy"
                onerror="this.onerror=null;this.src='${escapeAttribute(getFallbackFotoUrl())}';"
              >
              <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slate-900 truncate">${escapeHtml(String(p.paciente_nome || ''))}</p>
                <p class="mt-0.5 text-xs text-slate-500 truncate">${dt}</p>
                <p class="mt-0.5 text-xs text-slate-500 truncate">Profissional: ${escapeHtml(String(p.profissional_nome || ''))}</p>
              </div>
              <div class="flex gap-1">
                ${canEdit ? `<button type="button" data-id="${p.id}" class="btn-editar min-w-touch min-h-touch p-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-monday-blue" title="Editar"><i data-lucide="edit-3" class="w-4 h-4"></i></button>` : ''}
                <button type="button" data-id="${p.id}" class="btn-pdf min-w-touch min-h-touch p-2 rounded-xl bg-gray-100 hover:bg-gray-200" title="Imprimir PDF"><i data-lucide="file-down" class="w-4 h-4"></i></button>
                ${canEdit ? `<button type="button" data-id="${p.id}" class="btn-excluir min-w-touch min-h-touch p-2 rounded-xl bg-red-50 hover:bg-red-100 text-red-600" title="Excluir"><i data-lucide="trash-2" class="w-4 h-4"></i></button>` : ''}
              </div>
            </div>
          </div>
        `;
        }).join('')}
      </div>
    `;

  const aguardandoHtml = consultasAguardandoFiltradas.length === 0
    ? (aguardandoBuscaRealizada
      ? '<p class="text-gray-500 py-8 text-center">Nenhuma consulta aguardando prontuário para os filtros informados.</p>'
      : '<p class="text-gray-500 py-8 text-center">Nenhuma consulta aguardando prontuário.</p>')
    : `
    <div class="space-y-3" id="aguardando-list">
      <p class="text-sm text-gray-600 mb-3">Selecione uma consulta para gerar o prontuário com IA com base nos modelos do sistema.</p>
      ${consultasAguardandoFiltradas.map((c) => `
        <button type="button" data-consulta-id="${c.id}" data-aluno-id="${c.aluno_id}" data-paciente-nome="${escapeHtml(c.paciente_nome || '')}" data-data="${c.data_consulta || ''}" data-hora="${fmtTime(c.hora_inicio_prevista)}" data-especialidade="${escapeHtml(c.especialidade_nome || '')}" data-profissional="${escapeHtml(c.profissional_nome || '')}" data-profissional-id="${escapeAttribute(c.profissional_id || '')}" data-profissional-livre="${escapeAttribute(c.profissional_nome_livre || '')}" data-tem-anamnese-adulto="${Number(c.tem_anamnese_adulto || 0)}" data-tem-anamnese-infantojuvenil="${Number(c.tem_anamnese_infantojuvenil || 0)}" data-anamnese-adulto-id="${escapeAttribute(c.anamnese_adulto_id || '')}" data-anamnese-infantojuvenil-id="${escapeAttribute(c.anamnese_infantojuvenil_id || '')}" class="aguardando-card w-full text-left bg-white rounded-xl shadow-monday p-4 hover:shadow-monday-lg hover:border-monday-blue border-2 border-transparent transition">
          <span class="font-medium text-gray-800 block">${escapeHtml(c.paciente_nome || '')}</span>
          <span class="text-sm text-gray-500">${c.data_consulta} ${fmtTime(c.hora_inicio_prevista)} — ${escapeHtml(c.especialidade_nome || '')}</span>
          <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">${escapeHtml(c.profissional_nome || 'Plantonista')}</span>
        </button>
      `).join('')}
    </div>
  `;

  container.innerHTML = shellHtml + tabsShellHtml + `
    <div id="tab-content-prontuarios" class="tab-content-pront">${prontuariosHtml}</div>
    <div id="tab-content-aguardando" class="tab-content-pront hidden">${aguardandoHtml}</div>
  ` + shellSuffix;

  const filtroCursoEl = container.querySelector('#filtro-curso-pront');
  const filtroTurmaEl = container.querySelector('#filtro-turma-pront');
  const filtroPacienteEl = container.querySelector('#filtro-paciente-pront');
  const btnPesquisarPront = container.querySelector('#btn-pesquisar-pront');
  const btnLimparPront = container.querySelector('#btn-limpar-pront');

  if (filtroCursoEl) {
    filtroCursoEl.addEventListener('change', async () => {
      prontuariosFiltroCurso = String(filtroCursoEl.value || '').trim();
      prontuariosFiltroTurma = '';
      prontuariosFiltroPaciente = null;
      prontuariosBuscaRealizada = false;
      await renderProntuarios(container);
    });
  }

  if (filtroTurmaEl) {
    filtroTurmaEl.addEventListener('change', async () => {
      prontuariosFiltroTurma = String(filtroTurmaEl.value || '').trim();
      prontuariosFiltroPaciente = null;
      prontuariosBuscaRealizada = false;
      await renderProntuarios(container);
    });
  }

  const executarBuscaPront = async () => {
    prontuariosFiltroCurso = String(filtroCursoEl?.value || '').trim();
    prontuariosFiltroTurma = String(filtroTurmaEl?.value || '').trim();
    const nome = String(filtroPacienteEl?.value || '').trim();
    prontuariosFiltroPaciente = null;
    prontuariosFiltroNome = nome;
    prontuariosFiltroData = String(container.querySelector('#filtro-data-pront')?.value || '').trim();
    prontuariosFiltroHoraInicio = String(container.querySelector('#filtro-hora-inicio-pront')?.value || '').trim();
    prontuariosFiltroHoraFim = String(container.querySelector('#filtro-hora-fim-pront')?.value || '').trim();
    prontuariosFiltroProfissional = String(container.querySelector('#filtro-profissional-pront')?.value || '').trim();
    if (prontuariosTabAtiva === 'aguardando') {
      aguardandoBuscaRealizada = true;
    } else {
      prontuariosBuscaRealizada = true;
    }
    await renderProntuarios(container);
  };
  if (btnPesquisarPront) {
    btnPesquisarPront.addEventListener('click', async () => {
      btnPesquisarPront.disabled = true;
      btnPesquisarPront.innerHTML = getButtonSpinnerHtml() + ' Pesquisando...';
      try {
        await executarBuscaPront();
      } finally {
        btnPesquisarPront.disabled = false;
        btnPesquisarPront.textContent = 'Pesquisar';
      }
    });
  }
  if (btnLimparPront) {
    btnLimparPront.addEventListener('click', async () => {
      prontuariosFiltroPaciente = null;
      prontuariosFiltroNome = '';
      prontuariosFiltroData = '';
      prontuariosFiltroHoraInicio = '';
      prontuariosFiltroHoraFim = '';
      prontuariosFiltroProfissional = '';
      prontuariosFiltroCurso = '';
      prontuariosFiltroTurma = '';
      prontuariosBuscaRealizada = false;
      aguardandoBuscaRealizada = false;
      prontuariosSelecionados = new Set();
      await renderProntuarios(container);
    });
  }

  const syncProntuarioTabs = (tabAtiva) => {
    container.querySelectorAll('.tab-pront').forEach((b) => {
      const ativa = b.dataset.tab === tabAtiva;
      b.classList.toggle('bg-primary', ativa);
      b.classList.toggle('text-white', ativa);
      b.classList.toggle('shadow-sm', ativa);
      b.classList.toggle('bg-white', !ativa);
      b.classList.toggle('text-slate-500', !ativa);
      b.classList.toggle('ring-1', !ativa);
      b.classList.toggle('ring-slate-200', !ativa);
    });
    container.querySelector('#tab-content-prontuarios').classList.toggle('hidden', tabAtiva !== 'prontuarios');
    container.querySelector('#tab-content-aguardando').classList.toggle('hidden', tabAtiva !== 'aguardando');
  };

  syncProntuarioTabs(prontuariosTabAtiva);

  container.querySelectorAll('.tab-pront').forEach((btn) => {
    btn.onclick = () => {
      prontuariosTabAtiva = btn.dataset.tab || 'prontuarios';
      syncProntuarioTabs(prontuariosTabAtiva);
      if (typeof lucide !== 'undefined') lucide.createIcons();
    };
  });

  const atualizarControlesSelecao = () => {
    const btnBaixarSelecionados = container.querySelector('#btn-baixar-selecionados-pront');
    if (btnBaixarSelecionados) {
      const totalSelecionados = prontuariosSelecionados.size;
      btnBaixarSelecionados.textContent = `Baixar selecionados (${totalSelecionados})`;
      btnBaixarSelecionados.disabled = totalSelecionados === 0;
      btnBaixarSelecionados.classList.toggle('opacity-50', totalSelecionados === 0);
      btnBaixarSelecionados.classList.toggle('cursor-not-allowed', totalSelecionados === 0);
    }

    const btnSelecionarTodos = container.querySelector('#btn-selecionar-todos-pront');
    if (btnSelecionarTodos) {
      const checks = Array.from(container.querySelectorAll('.chk-pront'));
      const total = checks.length;
      const marcados = checks.filter((chk) => chk.checked).length;
      btnSelecionarTodos.textContent = total > 0 && marcados === total ? 'Desmarcar todos' : 'Selecionar todos';
    }
  };

  container.querySelectorAll('.chk-pront').forEach((chk) => {
    chk.addEventListener('change', () => {
      const id = Number(chk.dataset.id);
      if (chk.checked) prontuariosSelecionados.add(id);
      else prontuariosSelecionados.delete(id);
      atualizarControlesSelecao();
    });
  });

  container.querySelectorAll('.pront-card').forEach((card) => {
    card.addEventListener('click', (ev) => {
      if (ev.target.closest('button, a, input, label, textarea, select')) return;
      const chk = card.querySelector('.chk-pront');
      if (!chk) return;
      chk.checked = !chk.checked;
      chk.dispatchEvent(new Event('change', { bubbles: true }));
    });
  });

  container.querySelector('#btn-selecionar-todos-pront')?.addEventListener('click', () => {
    const checks = Array.from(container.querySelectorAll('.chk-pront'));
    const todosSelecionados = checks.length > 0 && checks.every((chk) => chk.checked);
    checks.forEach((chk) => {
      const id = Number(chk.dataset.id);
      chk.checked = !todosSelecionados;
      if (chk.checked) prontuariosSelecionados.add(id);
      else prontuariosSelecionados.delete(id);
    });
    atualizarControlesSelecao();
  });

  container.querySelector('#btn-baixar-selecionados-pront')?.addEventListener('click', () => {
    const selecionados = prontuarios
      .filter((p) => prontuariosSelecionados.has(Number(p.id)))
      .map((p) => ({
        id: Number(p.id),
        nome: String(p.paciente_nome || '')
      }));
    openModalAssinarPdfLote(selecionados);
  });

  container.querySelectorAll('.btn-editar').forEach((btn) => {
    btn.onclick = async () => openModalEditarProntuario(parseInt(btn.dataset.id, 10), container);
  });
  container.querySelectorAll('.btn-excluir').forEach((btn) => {
    btn.onclick = async () => {
      if (!confirm('Excluir este prontu?rio?')) return;
      btn.disabled = true;
      container.innerHTML = getSpinnerHtml('Excluindo...');
      try {
        await deleteProntuario(parseInt(btn.dataset.id, 10));
        await renderProntuarios(container);
      } finally {}
    };
  });

  container.querySelectorAll('.btn-pdf').forEach((btn) => {
    btn.onclick = () => openModalAssinarPdf(parseInt(btn.dataset.id, 10));
  });

  container.querySelectorAll('.aguardando-card').forEach((btn) => {
    btn.onclick = () => {
      const c = {
        id: parseInt(btn.dataset.consultaId, 10),
        aluno_id: parseInt(btn.dataset.alunoId, 10),
        paciente_nome: btn.dataset.pacienteNome || '',
        data_consulta: btn.dataset.data || '',
        hora_inicio_prevista: btn.dataset.hora || '',
        especialidade_nome: btn.dataset.especialidade || '',
        profissional_nome: btn.dataset.profissional || '',
        profissional_id: parseInt(btn.dataset.profissionalId, 10) || null,
        profissional_nome_livre: btn.dataset.profissionalLivre || '',
        tem_anamnese_adulto: parseInt(btn.dataset.temAnamneseAdulto, 10) || 0,
        tem_anamnese_infantojuvenil: parseInt(btn.dataset.temAnamneseInfantojuvenil, 10) || 0,
        anamnese_adulto_id: parseInt(btn.dataset.anamneseAdultoId, 10) || null,
        anamnese_infantojuvenil_id: parseInt(btn.dataset.anamneseInfantojuvenilId, 10) || null
      };
      openModalConsultaAguardando(c, container);
    };
  });

  atualizarControlesSelecao();

  if (typeof lucide !== 'undefined') lucide.createIcons();
}

function abrirEditorAnamnesePendente(consulta) {
  const tipo = Number(consulta?.tem_anamnese_infantojuvenil || 0) > 0 ? 'roteiro' : 'adulto';
  window.dispatchEvent(new CustomEvent('open-atendimento', {
    detail: {
      consultaId: consulta.id,
      date: consulta.data_consulta || undefined,
      anamneseTipo: tipo
    }
  }));
}

async function openModalExcluirAnamnesePendente(consulta, container) {
  const isPlantonista = !(parseInt(consulta?.profissional_id, 10) > 0)
    || String(consulta?.profissional_nome_livre || '').trim().toLowerCase() === 'plantonista';
  const tipo = Number(consulta?.tem_anamnese_infantojuvenil || 0) > 0 ? 'roteiro' : 'adulto';
  const anamneseId = tipo === 'roteiro'
    ? parseInt(consulta?.anamnese_infantojuvenil_id, 10)
    : parseInt(consulta?.anamnese_adulto_id, 10);
  if (!Number.isInteger(anamneseId) || anamneseId <= 0) {
    throw new Error('Nenhuma anamnese vinculada foi localizada para esta consulta.');
  }

  return new Promise((resolve) => {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4';
    modal.innerHTML = `
      <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-monday-lg">
        <h3 class="text-lg font-semibold text-slate-900">Excluir anamnese</h3>
        <p class="mt-2 text-sm text-slate-600">
          Esta exclusao reativara o agendamento e a consulta voltara a ficar aguardando atendimento.
        </p>
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
          <p><strong>Paciente:</strong> ${escapeHtml(consulta?.paciente_nome || '')}</p>
          <p><strong>Consulta:</strong> ${escapeHtml(consulta?.data_consulta || '')} ${escapeHtml(fmtTime(consulta?.hora_inicio_prevista || ''))}</p>
        </div>
        ${isPlantonista ? '' : `
          <label class="mt-4 block">
            <span class="mb-2 block text-sm font-medium text-slate-700">Senha do profissional que atendeu</span>
            <input type="password" id="senha-profissional-exclusao" class="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="Digite a senha do profissional responsavel">
          </label>
        `}
        <div class="mt-5 flex gap-2">
          <button type="button" class="btn-excluir-confirmar flex-1 rounded-xl bg-red-600 px-4 py-3 text-white">Excluir mesmo assim</button>
          <button type="button" class="btn-excluir-cancelar rounded-xl border border-slate-300 px-4 py-3 text-slate-700">Cancelar</button>
        </div>
      </div>
    `;

    const fechar = () => {
      modal.remove();
      resolve(false);
    };

    modal.querySelector('.btn-excluir-cancelar')?.addEventListener('click', fechar);
    modal.addEventListener('click', (event) => {
      if (event.target === modal) fechar();
    });
    modal.querySelector('.btn-excluir-confirmar')?.addEventListener('click', async () => {
      const senha = String(modal.querySelector('#senha-profissional-exclusao')?.value || '').trim();
      if (!isPlantonista && !senha) {
        alert('Informe a senha do profissional responsavel pela anamnese.');
        return;
      }
      try {
        if (tipo === 'roteiro') {
          await deleteAnamneseRoteiro(anamneseId, { senha_profissional: senha });
        } else {
          await deleteAnamnese(anamneseId, { senha_profissional: senha });
        }
        modal.remove();
        await renderProntuarios(container);
        resolve(true);
      } catch (err) {
        alert(err.message || 'Erro ao excluir anamnese.');
      }
    });

    document.body.appendChild(modal);
  });
}

async function openModalConsultaAguardando(consulta, container) {
  const temAnamneseAdulto = Number(consulta?.tem_anamnese_adulto || 0) > 0;
  const temAnamneseInfantojuvenil = Number(consulta?.tem_anamnese_infantojuvenil || 0) > 0;
  const temAnamnese = temAnamneseAdulto || temAnamneseInfantojuvenil;

  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4';
  modal.innerHTML = `
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-monday-lg">
      <h3 class="text-lg font-semibold text-slate-900">${escapeHtml(consulta?.paciente_nome || '')}</h3>
      <p class="mt-1 text-sm text-slate-500">${escapeHtml(consulta?.data_consulta || '')} ${escapeHtml(fmtTime(consulta?.hora_inicio_prevista || ''))} - ${escapeHtml(consulta?.especialidade_nome || '')}</p>
      <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
        ${temAnamnese ? 'Esta consulta ja possui anamnese salva e esta aguardando a geracao do prontuario.' : 'Esta consulta ainda nao possui anamnese identificada, mas voce pode gerar o prontuario se o fluxo clinico ja estiver pronto.'}
      </div>
      <div class="mt-5 flex flex-col gap-2">
        <button type="button" class="btn-gerar-prontuario min-h-touch rounded-xl bg-monday-blue px-4 py-3 text-white">Gerar prontuario</button>
        ${temAnamnese ? '<button type="button" class="btn-editar-anamnese min-h-touch rounded-xl border border-slate-300 px-4 py-3 text-slate-700">Editar anamnese</button>' : ''}
        ${temAnamnese ? '<button type="button" class="btn-excluir-anamnese min-h-touch rounded-xl bg-red-600 px-4 py-3 text-white">Excluir anamnese</button>' : ''}
        <button type="button" class="btn-fechar-acoes min-h-touch rounded-xl border border-slate-300 px-4 py-3 text-slate-700">Fechar</button>
      </div>
    </div>
  `;

  modal.querySelector('.btn-fechar-acoes')?.addEventListener('click', () => modal.remove());
  modal.addEventListener('click', (event) => {
    if (event.target === modal) modal.remove();
  });
  modal.querySelector('.btn-gerar-prontuario')?.addEventListener('click', () => {
    modal.remove();
    openModalGerarProntuario(consulta, container);
  });
  modal.querySelector('.btn-editar-anamnese')?.addEventListener('click', () => {
    modal.remove();
    abrirEditorAnamnesePendente(consulta);
  });
  modal.querySelector('.btn-excluir-anamnese')?.addEventListener('click', async () => {
    const excluiu = await openModalExcluirAnamnesePendente(consulta, container);
    if (excluiu) modal.remove();
  });

  document.body.appendChild(modal);
}

async function openModalGerarProntuario(consulta, container) {
  const dataConsulta = consulta.data_consulta || '';
  showLoadingOverlay('Abrindo...');
  try {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-y-auto';
    modal.innerHTML = `
      <div class="bg-white rounded-2xl shadow-monday-lg max-w-2xl w-full p-6 my-8 max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold mb-2">Gerar prontu&aacute;rio - ${escapeHtml(consulta.paciente_nome || '')}</h3>
        <p class="text-sm text-gray-500 mb-4">${dataConsulta} ${fmtTime(consulta.hora_inicio_prevista)} - ${escapeHtml(consulta.especialidade_nome || '')}</p>
        <p class="text-sm text-gray-600 mb-4">O prontu&aacute;rio ser&aacute; gerado com base em todos os dados do cadastro e da anamnese, seguindo os modelos do sistema.</p>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Instru&ccedil;&otilde;es adicionais (opcional)</label>
          <textarea id="modal-dados-clinicos" rows="2" class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="Par&acirc;metros, pedidos ou instru&ccedil;&otilde;es para IA..."></textarea>
        </div>
        <button type="button" id="modal-btn-gerar" class="mt-3 w-full min-h-touch py-3 bg-purple-accent text-white rounded-xl font-medium hover:bg-purple-accent-hover transition">
          Gerar prontu&aacute;rio com IA
        </button>
        <div id="modal-resultado-ia" class="hidden mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Prontu&aacute;rio gerado (edite se necess&aacute;rio antes de salvar)</label>
          <textarea id="modal-pront-texto" rows="14" class="w-full px-4 py-3 border border-gray-300 rounded-lg font-mono text-sm" placeholder="O prontu&aacute;rio gerado pela IA aparecer&aacute; aqui."></textarea>
          <div class="flex gap-2 mt-3">
            <button type="button" id="modal-btn-salvar" class="flex-1 py-3 bg-monday-blue text-white rounded-lg font-medium">Salvar prontu&aacute;rio</button>
            <button type="button" class="modal-btn-fechar px-4 py-3 border border-gray-300 rounded-lg">Fechar</button>
          </div>
        </div>
      </div>
    `;

    const removeTinyMCE = (id) => {
      if (typeof tinymce !== 'undefined' && tinymce.get(id)) tinymce.get(id).remove();
    };
    modal.querySelector('.modal-btn-fechar').onclick = () => {
      removeTinyMCE('modal-pront-texto');
      modal.remove();
    };
    modal.onclick = (e) => { if (e.target === modal) { removeTinyMCE('modal-pront-texto'); modal.remove(); } };

    const initTinyMCEPront = (id) => {
      if (typeof tinymce === 'undefined') return;
      if (tinymce.get(id)) return;
      tinymce.init({
        selector: '#' + id,
        menubar: false,
        height: 360,
        plugins: 'lists code',
        toolbar: 'undo redo | bold italic underline | bullist numlist | code',
        language: 'pt_BR',
        branding: false
      });
    };

    modal.querySelector('#modal-btn-gerar').onclick = async () => {
      const btn = modal.querySelector('#modal-btn-gerar');
      const dados = modal.querySelector('#modal-dados-clinicos').value.trim();
      const textoEl = modal.querySelector('#modal-pront-texto');
      btn.disabled = true;
      btn.innerHTML = getButtonSpinnerHtml() + ' Gerando...';
      modal.querySelector('#modal-resultado-ia').classList.remove('hidden');
      textoEl.value = '';
      try {
        await postProntuarioStreamIa(
          { consulta_id: consulta.id, aluno_id: consulta.aluno_id, observacoes_adicionais: dados || '' },
        {
          onDelta: (chunk, fullText) => { textoEl.value = fullText; },
          onDone: (fullText) => {
            const conteudo = fullText || textoEl.value;
            textoEl.value = markdownToHtml(conteudo);
            initTinyMCEPront('modal-pront-texto');
          },
            onError: (msg) => { alert(msg || 'Erro ao gerar prontuario.'); }
          }
        );
      } catch (err) {
        alert(err.message || 'Erro ao gerar prontuario.');
      } finally {
        btn.disabled = false;
        btn.innerHTML = 'Gerar prontu&aacute;rio com IA';
      }
    };

    modal.querySelector('#modal-btn-salvar').onclick = async () => {
      const conteudo = (typeof tinymce !== 'undefined' && tinymce.get('modal-pront-texto'))
        ? tinymce.get('modal-pront-texto').getContent().trim()
        : modal.querySelector('#modal-pront-texto')?.value?.trim() || '';
      if (!conteudo) {
        alert('O prontuario esta vazio. Gere primeiro com IA ou edite o texto.');
        return;
      }
      const btn = modal.querySelector('#modal-btn-salvar');
      btn.disabled = true;
      btn.innerHTML = getButtonSpinnerHtml() + ' Salvando...';
      try {
        await postProntuario({
          consulta_id: consulta.id,
          aluno_id: consulta.aluno_id,
          conteudo_ia: conteudo,
          conteudo_editado: conteudo,
          status: 'finalizado'
        });
        removeTinyMCE('modal-pront-texto');
        modal.remove();
        await renderProntuarios(container);
      } catch (err) {
        alert(err.message || 'Erro ao salvar.');
      } finally {
        btn.disabled = false;
        btn.innerHTML = 'Salvar prontu?rio';
      }
    };

    document.body.appendChild(modal);
  } finally {
    hideLoadingOverlay();
  }
}

async function openModalEditarProntuario(id, container) {
  showLoadingOverlay('Carregando prontu?rio...');
  try {
  const p = await getProntuario(id);
  const canEdit = p.can_edit === true;
  const conteudo = p.conteudo_editado || p.conteudo_ia || '';

  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-y-auto';
  modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-monday-lg max-w-2xl w-full p-6 my-8 max-h-[90vh] overflow-y-auto">
      <h3 class="text-lg font-semibold mb-2">${escapeHtml(p.paciente_nome || '')}</h3>
      <p class="text-sm text-gray-500 mb-4">${p.created_at ? new Date(p.created_at).toLocaleString('pt-BR') : ''} - ${escapeHtml(p.profissional_nome || '')}</p>
      <label class="block text-sm font-medium text-gray-700 mb-1">Conte?do do prontu?rio</label>
      <textarea id="pront-edit-conteudo" rows="14" class="w-full px-4 py-3 border border-gray-300 rounded-lg font-mono text-sm" ${canEdit ? '' : 'readonly disabled'}></textarea>
      <div class="flex gap-2 mt-4">
        ${canEdit ? `<button type="button" id="pront-btn-salvar" class="px-4 py-2 bg-monday-blue text-white rounded-lg">Salvar altera??es</button>` : ''}
        <button type="button" class="pront-btn-pdf px-4 py-2 bg-gray-200 rounded-lg">Imprimir PDF</button>
        <button type="button" class="pront-btn-fechar px-4 py-2 border border-gray-300 rounded-lg">Fechar</button>
      </div>
    </div>
  `;

  modal.querySelector('#pront-edit-conteudo').value = String(conteudo || '');
  document.body.appendChild(modal);
  if (canEdit && typeof tinymce !== 'undefined') {
    tinymce.init({
      selector: '#pront-edit-conteudo',
      menubar: false,
      height: 360,
      plugins: 'lists code',
      toolbar: 'undo redo | bold italic underline | bullist numlist | code',
      language: 'pt_BR',
      branding: false
    });
  }
  const removeTinyMCEEdit = () => {
    if (typeof tinymce !== 'undefined' && tinymce.get('pront-edit-conteudo')) tinymce.get('pront-edit-conteudo').remove();
  };
  modal.querySelector('.pront-btn-fechar').onclick = () => { removeTinyMCEEdit(); modal.remove(); };
  modal.querySelector('.pront-btn-pdf').onclick = () => openModalAssinarPdf(id);
  if (canEdit) {
    modal.querySelector('#pront-btn-salvar').onclick = async () => {
      const btn = modal.querySelector('#pront-btn-salvar');
      const orig = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = getButtonSpinnerHtml() + ' Salvando...';
      const conteudoEditado = (typeof tinymce !== 'undefined' && tinymce.get('pront-edit-conteudo'))
        ? tinymce.get('pront-edit-conteudo').getContent() : modal.querySelector('#pront-edit-conteudo')?.value || '';
      try {
        await putProntuario(id, { conteudo_editado: conteudoEditado });
        removeTinyMCEEdit();
        modal.remove();
        await renderProntuarios(container);
      } catch (e) {
        btn.disabled = false;
        btn.innerHTML = orig;
        throw e;
      }
    };
  }
  modal.onclick = (e) => { if (e.target === modal) { removeTinyMCEEdit(); modal.remove(); } };
  } finally {
    hideLoadingOverlay();
  }
}
