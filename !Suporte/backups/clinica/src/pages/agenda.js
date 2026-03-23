import { getAgenda, getFeriadosVerificar, getDiasComAgendamento, postConfirmacao, postCancelar, postExcluirConsulta, postIniciarAtendimento, postReverterAtendimento, postConsulta, putConsulta, getPacientes, getEspecialidades, getTiposConsulta, getProfissionais } from '../services/api.js';
import { getSpinnerHtml, getButtonSpinnerHtml, showLoadingOverlay, hideLoadingOverlay } from '../utils/loading.js';

let currentDate = new Date().toISOString().slice(0, 10);
let currentView = 'day';
let navDelegateAttached = false;
let filterAgenda = {
  especialidade_id: '',
  profissional_id: '',
  hora_inicio: '',
  hora_fim: ''
};

function fmtDate(d) {
  return d.toISOString().slice(0, 10);
}

function parseLocalDate(str) {
  const [y, m, d] = str.split('-').map(Number);
  return new Date(y, m - 1, d);
}

function addDaysToDateStr(str, delta) {
  const d = parseLocalDate(str);
  d.setDate(d.getDate() + delta);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function fmtTime(t) {
  if (!t) return '';
  const [h, m] = String(t).split(':');
  return `${h}:${m || '00'}`;
}

function statusLabel(status) {
  const s = (status || '').toLowerCase();
  if (s.includes('concluida') || s.includes('concluída')) return 'Consultada';
  if (s.includes('em_atendimento') || s.includes('atendimento')) return 'Em atendimento';
  if (s.includes('cancelada')) return 'Cancelada';
  if (s.includes('confirmacao') || s.includes('confirmação')) return 'Confirmação solicitada';
  return 'Agendada';
}

function statusBadgeClass(status) {
  const s = (status || '').toLowerCase();
  if (s.includes('concluida') || s.includes('concluída')) return 'bg-emerald-100 text-emerald-800';
  if (s.includes('em_atendimento') || s.includes('atendimento')) return 'bg-blue-100 text-blue-800';
  if (s.includes('cancelada')) return 'bg-red-100 text-red-800';
  if (s.includes('confirmacao') || s.includes('confirmação')) return 'bg-amber-100 text-amber-800';
  return 'bg-gray-100 text-gray-700';
}

const ESPECIALIDADE_CORES = {
  fisioterapia: { bg: '#dcfce7', border: '#22c55e' },
  psicologia: { bg: '#ede9fe', border: '#8b5cf6' },
  nutrição: { bg: '#ffedd5', border: '#f97316' },
  nutricao: { bg: '#ffedd5', border: '#f97316' },
  fonoaudiologia: { bg: '#dbeafe', border: '#3b82f6' }
};

function especialidadeColor(nome) {
  if (!nome) return { bg: '#f3f4f6', border: '#6b7280' };
  const key = String(nome).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  if (key.includes('fisioterapia')) return ESPECIALIDADE_CORES.fisioterapia;
  if (key.includes('psicologia')) return ESPECIALIDADE_CORES.psicologia;
  if (key.includes('nutricao') || key.includes('nutrição')) return ESPECIALIDADE_CORES.nutricao;
  if (key.includes('fonoaudiologia') || key.includes('fonodialogia')) return ESPECIALIDADE_CORES.fonoaudiologia;
  return { bg: '#f3f4f6', border: '#6b7280' };
}

function buildEventTooltipData(ev) {
  const partes = [];
  partes.push(`Paciente: ${ev.paciente_nome || '—'}`);
  partes.push(`Data: ${ev.data_consulta || '—'}`);
  partes.push(`Horário: ${fmtTime(ev.hora_inicio_prevista)}${ev.duracao_minutos_prevista ? ` (${ev.duracao_minutos_prevista} min)` : ''}`);
  partes.push(`Especialidade: ${ev.especialidade_nome || '—'}`);
  partes.push(`Profissional: ${ev.profissional_nome || ev.profissional_nome_livre || 'Plantonista'}`);
  if (ev.tipo_nome) partes.push(`Tipo: ${ev.tipo_nome}`);
  partes.push(`Status: ${statusLabel(ev.status)}`);
  if (ev.paciente_telefone) partes.push(`Contato: ${ev.paciente_telefone}`);
  if (ev.observacao) partes.push(`Obs: ${ev.observacao}`);
  return partes.join('\n');
}

function getDatasParaFeriados(view, dateStr) {
  const datas = [];
  if (view === 'day') {
    datas.push(dateStr);
  } else if (view === 'week') {
    const d = parseLocalDate(dateStr);
    const dayOfWeek = d.getDay();
    const mondayOffset = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
    const monday = new Date(d);
    monday.setDate(d.getDate() + mondayOffset);
    for (let i = 0; i < 7; i++) {
      const x = new Date(monday);
      x.setDate(monday.getDate() + i);
      datas.push(fmtDate(x));
    }
  } else {
    const [y, m] = dateStr.split('-').map(Number);
    const lastDay = new Date(y, m, 0).getDate();
    for (let d = 1; d <= lastDay; d++) {
      datas.push(`${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`);
    }
  }
  return datas;
}

export async function renderAgenda(container) {
  container.innerHTML = getSpinnerHtml('Carregando agenda...');
  const [especialidades, profissionais, { feriados: mapFeriados }] = await Promise.all([
    getEspecialidades(),
    getProfissionais(),
    getFeriadosVerificar(getDatasParaFeriados(currentView, currentDate)).catch(() => ({ feriados: {} }))
  ]);
  const { eventos } = await getAgenda(currentView, currentDate, {
    especialidade_id: filterAgenda.especialidade_id || undefined,
    profissional_id: filterAgenda.profissional_id,
    hora_inicio: filterAgenda.hora_inicio || undefined,
    hora_fim: filterAgenda.hora_fim || undefined
  });
  const dLocal = parseLocalDate(currentDate);
  const hoje = new Date();
  const isHoje = currentDate === `${hoje.getFullYear()}-${String(hoje.getMonth() + 1).padStart(2, '0')}-${String(hoje.getDate()).padStart(2, '0')}`;
  const tituloData = currentView === 'day'
    ? dLocal.toLocaleDateString('pt-BR', { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' })
    : dLocal.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
  const infoFeriado = mapFeriados[currentDate];
  const badgeFeriado = infoFeriado?.feriado
    ? `<span class="badge bg-danger text-white text-center w-full py-2 mt-1" title="Feriado">${escapeHtml(infoFeriado.nome || 'Feriado')}</span>`
    : '';
  const navHtml = `
    <div class="flex items-center justify-between gap-2 mb-4 bg-white rounded-xl shadow-monday p-3 flex-nowrap overflow-visible">
      <button type="button" id="btn-prev" class="min-w-[44px] min-h-[44px] w-11 h-11 flex items-center justify-center rounded-lg hover:bg-gray-100 flex-shrink-0" aria-label="Dia anterior">
        <i data-lucide="chevron-left" class="w-6 h-6 pointer-events-none block"></i>
      </button>
      <div class="flex items-center justify-center gap-2 flex-1 min-w-0 flex-shrink overflow-hidden">
        <button type="button" id="btn-today" class="px-3 py-1.5 bg-monday-blue text-white rounded-lg text-sm font-medium shrink-0">Hoje</button>
        <div class="flex flex-col items-center justify-center flex-1 min-w-0" id="titulo-data">
          <span class="font-semibold py-2 text-center">${tituloData}${isHoje ? ' (Hoje)' : ''}</span>
          ${badgeFeriado}
        </div>
        <button type="button" id="btn-ir-data" class="flex items-center gap-1 px-2 py-1.5 rounded-lg border border-gray-300 hover:bg-gray-50 text-sm shrink-0" title="Ir para data específica">
          <i data-lucide="calendar-days" class="w-4 h-4 pointer-events-none"></i>
          <span>Data</span>
        </button>
      </div>
      <button type="button" id="btn-next" class="min-w-[44px] min-h-[44px] w-11 h-11 flex items-center justify-center rounded-lg hover:bg-gray-100 flex-shrink-0" aria-label="Próximo dia">
        <i data-lucide="chevron-right" class="w-6 h-6 pointer-events-none block"></i>
      </button>
    </div>
    <div class="flex gap-2 mb-4 items-center flex-wrap">
      <button type="button" data-view="day" class="view-btn px-3 py-2 rounded-lg text-sm ${currentView === 'day' ? 'bg-monday-blue text-white' : 'bg-gray-200'}">Dia</button>
      <button type="button" data-view="week" class="view-btn px-3 py-2 rounded-lg text-sm ${currentView === 'week' ? 'bg-monday-blue text-white' : 'bg-gray-200'}">Semana</button>
      <button type="button" data-view="month" class="view-btn px-3 py-2 rounded-lg text-sm ${currentView === 'month' ? 'bg-monday-blue text-white' : 'bg-gray-200'}">Mês</button>
      <div class="ml-auto flex gap-2">
        <button type="button" id="btn-refresh" class="px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-100 text-sm flex items-center gap-1.5" title="Atualizar" aria-label="Atualizar">
          <i data-lucide="refresh-cw" class="w-4 h-4"></i>
          <span>Atualizar</span>
        </button>
        <button type="button" id="btn-novo-agendar" class="px-4 py-2 bg-monday-blue text-white rounded-lg text-sm">Novo agendamento</button>
      </div>
    </div>
    <div class="mb-4 p-3 bg-gray-50 rounded-xl border border-gray-200">
      <span class="text-xs font-medium text-gray-600 block mb-2">Filtros</span>
      <div class="flex flex-wrap gap-2 items-center">
        <select id="filtro-especialidade" class="text-sm px-3 py-2 border border-gray-300 rounded-lg min-w-[140px]">
          <option value="">Todas especialidades</option>
          ${especialidades.map((e) => `<option value="${e.id}" ${filterAgenda.especialidade_id == e.id ? 'selected' : ''}>${escapeHtml(e.nome)}</option>`).join('')}
        </select>
        <select id="filtro-profissional" class="text-sm px-3 py-2 border border-gray-300 rounded-lg min-w-[140px]">
          <option value="">Todos profissionais</option>
          <option value="plantonista" ${filterAgenda.profissional_id === 'plantonista' ? 'selected' : ''}>Plantonista</option>
          ${profissionais.map((p) => `<option value="${p.id}" ${filterAgenda.profissional_id == p.id ? 'selected' : ''}>${escapeHtml(p.nome)}</option>`).join('')}
        </select>
        <label class="flex items-center gap-1.5 text-sm">
          <span class="text-gray-600">Data:</span>
          <input type="date" id="filtro-data" value="${currentDate}" class="px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
        </label>
        <label class="flex items-center gap-1.5 text-sm">
          <span class="text-gray-600">Hora:</span>
          <input type="time" id="filtro-hora-inicio" value="${filterAgenda.hora_inicio || ''}" class="px-2 py-1.5 border border-gray-300 rounded-lg text-sm w-24" title="Hora inicial (deixe vazio para qualquer)">
          <span class="text-gray-400">até</span>
          <input type="time" id="filtro-hora-fim" value="${filterAgenda.hora_fim || ''}" class="px-2 py-1.5 border border-gray-300 rounded-lg text-sm w-24" title="Hora final (deixe vazio para qualquer)">
        </label>
        <button type="button" id="btn-limpar-filtros" class="px-3 py-2 text-sm text-gray-600 hover:bg-gray-200 rounded-lg">Limpar</button>
      </div>
    </div>
  `;

  const dayEvents = currentView === 'day'
    ? eventos.filter((e) => {
        const inicio = String(e.inicio || e.data_consulta || '');
        return inicio.startsWith(currentDate) || (e.data_consulta && e.data_consulta === currentDate);
      }).sort((a, b) => (a.inicio || '').localeCompare(b.inicio || ''))
    : eventos;

  const slotMinStep = 30;
  const rowHeight = 2.25;
  const dayStartMins = 7 * 60;
  const dayEndMins = 22 * 60;
  const slotsCount = Math.ceil((dayEndMins - dayStartMins) / slotMinStep);

  function parseEventMins(ev) {
    const hi = String(ev.hora_inicio_prevista || '').substring(0, 5);
    const [eh, em] = hi.split(':').map(Number);
    return (eh || 0) * 60 + (em || 0);
  }
  function assignOverlapColumns(events) {
    const parsed = events.map((e) => ({
      ...e,
      startMins: parseEventMins(e),
      endMins: parseEventMins(e) + ((e.duracao_minutos_prevista || 60)),
      col: 0
    }));
    parsed.sort((a, b) => a.startMins - b.startMins);
    for (let i = 0; i < parsed.length; i++) {
      const taken = new Set();
      for (let j = 0; j < i; j++) {
        if (parsed[j].startMins < parsed[i].endMins && parsed[i].startMins < parsed[j].endMins) {
          taken.add(parsed[j].col);
        }
      }
      let c = 0;
      while (taken.has(c)) c++;
      parsed[i].col = c;
    }
    const maxCol = Math.max(0, ...parsed.map((e) => e.col)) + 1;
    return parsed.map((e) => ({ ...e, maxCol }));
  }

  const LEGENDA_ESPECIALIDADES = [
    { nome: 'Fisioterapia', cor: ESPECIALIDADE_CORES.fisioterapia },
    { nome: 'Psicologia', cor: ESPECIALIDADE_CORES.psicologia },
    { nome: 'Nutrição', cor: ESPECIALIDADE_CORES.nutricao },
    { nome: 'Fonoaudiologia', cor: ESPECIALIDADE_CORES.fonoaudiologia },
    { nome: 'Outros', cor: { bg: '#f3f4f6', border: '#6b7280' } }
  ];

  const eventsWithCols = assignOverlapColumns(dayEvents);
  const totalHeightRem = slotsCount * rowHeight;
  const eventsHtml = currentView === 'day'
    ? `<div class="bg-white rounded-xl shadow-monday overflow-hidden">
        <div class="px-4 py-2 border-b border-gray-200 flex flex-wrap gap-3 items-center text-xs">
          <span class="font-medium text-gray-600">Legenda:</span>
          ${LEGENDA_ESPECIALIDADES.map((e) => `<span class="flex items-center gap-1.5"><span class="w-4 h-4 rounded border-2 shrink-0" style="background:${e.cor.bg};border-color:${e.cor.border}"></span>${e.nome}</span>`).join('')}
        </div>
        <div class="flex border-t border-gray-200" style="min-height:${totalHeightRem}rem">
          <div class="w-14 shrink-0 border-r border-gray-200 flex flex-col bg-gray-50/50">
            ${Array.from({ length: slotsCount }, (_, i) => {
              const totalMins = dayStartMins + i * slotMinStep;
              const h = Math.floor(totalMins / 60);
              const m = totalMins % 60;
              const t = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`;
              const isFullHour = m === 0;
              return `<div class="text-xs font-mono py-0.5 px-1 shrink-0 border-b border-gray-200 ${isFullHour ? 'font-bold text-gray-700' : 'text-gray-500'}" style="height:${rowHeight}rem">${t}</div>`;
            }).join('')}
          </div>
          <div class="flex-1 relative min-w-0 agenda-events-area border-t border-gray-200" style="height:${totalHeightRem}rem; background-image: repeating-linear-gradient(to bottom, transparent 0, transparent calc(${rowHeight}rem - 1px), #d1d5db calc(${rowHeight}rem - 1px), #d1d5db ${rowHeight}rem)">
            ${eventsWithCols.map((ev) => {
              const visStart = Math.max(ev.startMins, dayStartMins);
              const visEnd = Math.min(ev.endMins, dayEndMins);
              if (visStart >= visEnd) return '';
              const startRow = Math.max(0, (visStart - dayStartMins) / slotMinStep);
              const spanRows = Math.max(1, (visEnd - visStart) / slotMinStep);
              const topPct = (startRow / slotsCount) * 100;
              const heightPct = (spanRows / slotsCount) * 100;
              const gap = ev.maxCol > 1 ? 3 : 0;
              const totalGaps = (ev.maxCol - 1) * gap;
              const colWidth = ev.maxCol > 1 ? `calc((100% - ${totalGaps}px) / ${ev.maxCol})` : '100%';
              const leftOffset = ev.col * gap;
              const leftCalc = ev.maxCol > 1
                ? `calc(${ev.col} * (100% - ${totalGaps}px) / ${ev.maxCol} + ${leftOffset}px)`
                : '0';
              const cor = especialidadeColor(ev.especialidade_nome);
              const statusTexto = statusLabel(ev.status);
              const isCancelada = (ev.status || '').toLowerCase().includes('cancelada');
              const tooltipText = buildEventTooltipData(ev);
              return `
              <div class="event-block absolute rounded-lg cursor-pointer hover:opacity-95 hover:z-20 active:scale-[0.99] transition overflow-hidden shadow-sm ${isCancelada ? 'opacity-60' : ''}"
                data-id="${ev.id}"
                data-data-consulta="${ev.data_consulta || currentDate}"
                title="${escapeHtml(tooltipText)}"
                style="top:${topPct}%; height:${heightPct}%; left:${leftCalc}; width:${colWidth}; min-height:2rem; z-index:${ev.col}; background:${cor.bg}; border-left:4px solid ${cor.border}">
                <div class="p-1.5 h-full overflow-hidden text-[11px] leading-tight flex flex-col">
                  <span class="text-[9px] font-semibold uppercase tracking-wide ${isCancelada ? 'text-red-600' : 'text-gray-600'} shrink-0">${escapeHtml(statusTexto)}</span>
                  <span class="font-mono font-medium text-gray-600">${fmtTime(ev.hora_inicio_prevista)}${ev.duracao_minutos_prevista ? ` (${ev.duracao_minutos_prevista}min)` : ''}</span>
                  <span class="font-semibold text-gray-800 block truncate">${escapeHtml(ev.paciente_nome || '')}</span>
                  <span class="text-gray-500 block truncate">${escapeHtml(ev.especialidade_nome || '')} — ${escapeHtml((ev.profissional_nome || ev.profissional_nome_livre || 'Plantonista'))}</span>
                </div>
              </div>`;
            }).join('')}
            ${dayEvents.length === 0 ? '<p class="absolute inset-0 flex items-center justify-center text-gray-500 text-sm">Nenhum evento para este dia.</p>' : ''}
          </div>
        </div>
      </div>`
    : `
    <div class="bg-white rounded-xl shadow-monday overflow-hidden">
      ${dayEvents.length === 0 ? '<p class="text-gray-500 py-8 px-4 text-center">Nenhum evento.</p>' : `
      <div class="divide-y divide-gray-100">
        ${dayEvents.map((e) => {
          const tooltipText = buildEventTooltipData(e);
          const isCanceladaList = (e.status || '').toLowerCase().includes('cancelada');
          return `
          <div class="py-4 px-4 event-block cursor-pointer hover:bg-slate-50 transition flex justify-between items-center gap-3 ${isCanceladaList ? 'opacity-60' : ''}" data-id="${e.id}" data-data-consulta="${e.data_consulta || currentDate}" title="${escapeHtml(tooltipText)}">
            <div>
              <span class="font-medium text-gray-800">${escapeHtml(e.paciente_nome || '')}</span>
              <span class="block text-sm text-gray-500">${e.data_consulta || ''} ${fmtTime(e.hora_inicio_prevista)} — ${escapeHtml(e.especialidade_nome || '')} — ${escapeHtml((e.profissional_nome || e.profissional_nome_livre || 'Plantonista'))}</span>
            </div>
            ${e.status ? `<span class="shrink-0 text-xs px-2 py-1 rounded-full ${statusBadgeClass(e.status)}">${escapeHtml(e.status)}</span>` : ''}
          </div>
        `;
        }).join('')}
      </div>
      `}
    </div>
  `;

  container.innerHTML = navHtml + eventsHtml;

  if (!navDelegateAttached) {
    navDelegateAttached = true;
    container.addEventListener('click', (e) => {
      if (e.target.closest('#btn-prev')) {
        e.preventDefault();
        const delta = currentView === 'week' ? -7 : currentView === 'month' ? -30 : -1;
        currentDate = addDaysToDateStr(currentDate, delta);
        renderAgenda(container);
      } else if (e.target.closest('#btn-next')) {
        e.preventDefault();
        const delta = currentView === 'week' ? 7 : currentView === 'month' ? 30 : 1;
        currentDate = addDaysToDateStr(currentDate, delta);
        renderAgenda(container);
      }
    });
  }

  container.querySelector('#btn-today').onclick = () => {
    const now = new Date();
    currentDate = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    renderAgenda(container);
  };
  const btnIrData = container.querySelector('#btn-ir-data');
  if (btnIrData) {
    btnIrData.onclick = () => openModalCalendarioData(currentDate, (novaData) => {
      currentDate = novaData;
      renderAgenda(container);
    });
  }
  container.querySelectorAll('.view-btn').forEach((b) => {
    b.onclick = () => {
      currentView = b.dataset.view;
      renderAgenda(container);
    };
  });

  const applyFilters = () => {
    filterAgenda.especialidade_id = container.querySelector('#filtro-especialidade')?.value || '';
    filterAgenda.profissional_id = container.querySelector('#filtro-profissional')?.value ?? '';
    filterAgenda.hora_inicio = container.querySelector('#filtro-hora-inicio')?.value || '';
    filterAgenda.hora_fim = container.querySelector('#filtro-hora-fim')?.value || '';
    const dataVal = container.querySelector('#filtro-data')?.value;
    if (dataVal) currentDate = dataVal;
    renderAgenda(container);
  };

  container.querySelector('#filtro-especialidade')?.addEventListener('change', () => applyFilters());
  container.querySelector('#filtro-profissional')?.addEventListener('change', () => applyFilters());
  container.querySelector('#filtro-data')?.addEventListener('change', () => applyFilters());
  container.querySelector('#filtro-hora-inicio')?.addEventListener('change', () => applyFilters());
  container.querySelector('#filtro-hora-fim')?.addEventListener('change', () => applyFilters());

  container.querySelector('#btn-limpar-filtros')?.addEventListener('click', () => {
    filterAgenda = { especialidade_id: '', profissional_id: '', hora_inicio: '', hora_fim: '' };
    renderAgenda(container);
  });

  container.querySelector('#btn-refresh')?.addEventListener('click', () => renderAgenda(container));

  container.querySelectorAll('.event-block').forEach((el) => {
    const dataConsulta = el.dataset.dataConsulta || currentDate;
    el.onclick = () => openModalEvento(parseInt(el.dataset.id, 10), container, dataConsulta);
  });

  container.querySelector('#btn-novo-agendar').onclick = () => openModalAgendar(container, null);

  window.addEventListener('open-agendar', (e) => {
    const alunoId = e.detail?.alunoId;
    openModalAgendar(container, alunoId);
  }, { once: false });

  if (typeof lucide !== 'undefined') lucide.createIcons();
}

function getIndicadorCor(diaInfo) {
  if (!diaInfo || diaInfo.total === 0) return null;
  if ((diaInfo.pendentes_passado || 0) > 0) return 'bg-red-500';
  if ((diaInfo.futuras || 0) > 0) return 'bg-amber-500';
  if ((diaInfo.concluidas || 0) > 0) return 'bg-emerald-500';
  return null;
}

async function openModalCalendarioData(dataAtual, onSelecionar) {
  const [y, m] = dataAtual.split('-').map(Number);
  let mesAtual = `${y}-${String(m).padStart(2, '0')}`;
  const nomesDias = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
  const mesesPt = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4';
  modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-monday-lg max-w-sm w-full p-4">
      <div class="flex items-center justify-between mb-4">
        <button type="button" id="cal-prev" class="min-w-touch min-h-touch p-2 rounded-lg hover:bg-gray-100"><i data-lucide="chevron-left" class="w-5 h-5"></i></button>
        <h3 id="cal-titulo-mes" class="font-semibold text-gray-800">${mesesPt[m - 1]} ${y}</h3>
        <button type="button" id="cal-next" class="min-w-touch min-h-touch p-2 rounded-lg hover:bg-gray-100"><i data-lucide="chevron-right" class="w-5 h-5"></i></button>
      </div>
      <div class="grid grid-cols-7 gap-1 text-center text-xs font-medium text-gray-600 mb-2">
        ${nomesDias.map((d) => `<span class="py-1">${d}</span>`).join('')}
      </div>
      <div id="cal-grid" class="grid grid-cols-7 gap-1"></div>
      <button type="button" class="cal-fechar mt-4 w-full py-2 border border-gray-300 rounded-lg text-sm">Fechar</button>
    </div>
  `;

  const renderGrid = async (mesStr) => {
    const grid = modal.querySelector('#cal-grid');
    grid.innerHTML = '<span class="col-span-7 text-center py-4 text-gray-400">Carregando...</span>';
    try {
      const { dias } = await getDiasComAgendamento(mesStr);
      const [anoNum, mesNum] = mesStr.split('-').map(Number);
      const primeiroDia = new Date(anoNum, mesNum - 1, 1);
      const ultimoDia = new Date(anoNum, mesNum, 0).getDate();
      const offsetCol = primeiroDia.getDay();
      const celulas = [];
      for (let i = 0; i < offsetCol; i++) celulas.push('<div class="h-10"></div>');
      const mapaDias = {};
      (dias || []).forEach((d) => { mapaDias[d.data] = d; });
      for (let d = 1; d <= ultimoDia; d++) {
        const dataStr = `${anoNum}-${String(mesNum).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const info = mapaDias[dataStr] || { data: dataStr, total: 0, concluidas: 0, pendentes_passado: 0, futuras: 0 };
        const cor = getIndicadorCor(info);
        const hoje = new Date().toISOString().slice(0, 10);
        const isHoje = dataStr === hoje;
        celulas.push(`
          <button type="button" data-data="${dataStr}" class="min-h-touch flex flex-col items-center justify-center p-1 rounded-lg hover:bg-gray-100 ${isHoje ? 'ring-2 ring-monday-blue bg-blue-50' : ''}">
            <span class="text-sm font-medium">${d}</span>
            ${cor ? `<span class="w-2 h-2 rounded-full mt-0.5 ${cor}"></span>` : ''}
          </button>
        `);
      }
      grid.innerHTML = celulas.join('');
      modal.querySelector('#cal-titulo-mes').textContent = `${mesesPt[mesNum - 1]} ${anoNum}`;
      grid.querySelectorAll('button[data-data]').forEach((btn) => {
        btn.onclick = () => {
          onSelecionar(btn.dataset.data);
          modal.remove();
        };
      });
    } catch (e) {
      grid.innerHTML = '<span class="col-span-7 text-center py-4 text-red-500">Erro ao carregar.</span>';
    }
  };

  modal.querySelector('#cal-prev').onclick = () => {
    const [y2, m2] = mesAtual.split('-').map(Number);
    const d = new Date(y2, m2 - 2, 1);
    mesAtual = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    renderGrid(mesAtual);
  };
  modal.querySelector('#cal-next').onclick = () => {
    const [y2, m2] = mesAtual.split('-').map(Number);
    const d = new Date(y2, m2, 1);
    mesAtual = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    renderGrid(mesAtual);
  };
  modal.querySelector('.cal-fechar').onclick = () => modal.remove();
  modal.onclick = (e) => { if (e.target === modal) modal.remove(); };

  document.body.appendChild(modal);
  await renderGrid(mesAtual);
  if (typeof lucide !== 'undefined') lucide.createIcons();
}

export async function openModalAgendar(container, alunoIdPreselected, opts = {}) {
  const { skipRefreshAgenda = false } = opts;
  showLoadingOverlay('Abrindo agendamento...');
  try {
  const [{ pacientes }, especialidades, tiposConsulta, profissionais] = await Promise.all([
    getPacientes(),
    getEspecialidades(),
    getTiposConsulta(),
    getProfissionais(),
  ]);

  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-y-auto';
  modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-monday-lg max-w-md w-full p-6 my-8">
      <h3 class="text-lg font-semibold mb-4">Agendar consulta</h3>
      <form id="form-agendar" class="space-y-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Paciente</label>
          <select name="aluno_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">Selecione...</option>
            ${pacientes.map((p) => `<option value="${p.IdUsuario}" ${alunoIdPreselected == p.IdUsuario ? 'selected' : ''}>${escapeHtml(String(p.Nome || ''))}</option>`).join('')}
          </select>
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Data</label>
            <input type="date" name="data_consulta" id="agendar-data-consulta" value="${currentDate}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            <div id="agendar-feriado-aviso" class="mt-1 text-xs min-h-[1.25rem]"></div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Hora</label>
            <input type="time" name="hora_inicio_prevista" value="09:00" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Duração (min)</label>
            <input type="number" name="duracao_minutos_prevista" value="60" min="10" step="10" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Especialidade</label>
            <select name="especialidade_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
              ${especialidades.map((e) => `<option value="${e.id}">${escapeHtml(e.nome)}</option>`).join('')}
            </select>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de consulta</label>
          <select name="tipo_consulta_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            ${tiposConsulta.map((t) => `<option value="${t.id}">${escapeHtml(t.nome)}</option>`).join('')}
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Profissional</label>
          <select name="profissional_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">Plantonista</option>
            ${profissionais.map((p) => `<option value="${p.id}">${escapeHtml(p.nome)}</option>`).join('')}
          </select>
        </div>
        <div class="flex gap-2 pt-4">
          <button type="submit" class="flex-1 py-3 bg-monday-blue text-white rounded-lg font-medium">Agendar</button>
          <button type="button" class="btn-cancel-agendar px-4 py-3 border border-gray-300 rounded-lg">Cancelar</button>
        </div>
      </form>
    </div>
  `;

  modal.querySelector('.btn-cancel-agendar').onclick = () => modal.remove();

  const atualizarFeriadoAgendar = async () => {
    const input = modal.querySelector('#agendar-data-consulta');
    const aviso = modal.querySelector('#agendar-feriado-aviso');
    if (!input || !aviso) return;
    const data = input.value;
    if (!data) { aviso.innerHTML = ''; return; }
    try {
      const { feriados } = await getFeriadosVerificar([data]);
      const info = feriados[data];
      if (info?.feriado) {
        aviso.innerHTML = `<span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-amber-100 text-amber-800"><i data-lucide="party-popper" class="w-3.5 h-3.5"></i><strong>Feriado:</strong> ${escapeHtml(info.nome || '')}</span>`;
        if (typeof lucide !== 'undefined') lucide.createIcons();
      } else {
        aviso.innerHTML = '';
      }
    } catch {
      aviso.innerHTML = '';
    }
  };
  modal.querySelector('#agendar-data-consulta')?.addEventListener('change', atualizarFeriadoAgendar);
  atualizarFeriadoAgendar();

  modal.querySelector('#form-agendar').onsubmit = async (ev) => {
    ev.preventDefault();
    const btn = ev.target.querySelector('button[type="submit"]');
    const originalText = btn?.innerHTML;
    if (btn) { btn.disabled = true; btn.innerHTML = getButtonSpinnerHtml() + ' Agendando...'; }
    try {
    const fd = new FormData(ev.target);
    const data = {
      aluno_id: parseInt(fd.get('aluno_id'), 10),
      data_consulta: fd.get('data_consulta'),
      hora_inicio_prevista: fd.get('hora_inicio_prevista') + ':00',
      duracao_minutos_prevista: parseInt(fd.get('duracao_minutos_prevista'), 10) || 60,
      especialidade_id: parseInt(fd.get('especialidade_id'), 10),
      tipo_consulta_id: parseInt(fd.get('tipo_consulta_id'), 10),
      profissional_id: fd.get('profissional_id') || null,
      profissional_nome_livre: fd.get('profissional_id') ? null : 'Plantonista',
    };
    await postConsulta(data);
    modal.remove();
    if (!skipRefreshAgenda) renderAgenda(container);
    } catch (e) {
      if (btn) { btn.disabled = false; btn.innerHTML = originalText || 'Agendar'; }
      throw e;
    }
  };
  modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
  document.body.appendChild(modal);
  } finally {
    hideLoadingOverlay();
  }
}

async function openModalEvento(id, container, dataConsulta) {
  const dateToFetch = dataConsulta || currentDate;
  showLoadingOverlay('Carregando consulta...');
  try {
  const { getAgenda } = await import('../services/api.js');
  const { eventos } = await getAgenda('day', dateToFetch);
  const ev = eventos.find((e) => e.id === id);
  if (!ev) { hideLoadingOverlay(); return; }

  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4';
  const detalhes = [
    ev.especialidade_nome && ev.tipo_nome ? `${ev.especialidade_nome} • ${ev.tipo_nome}` : (ev.especialidade_nome || ev.tipo_nome || ''),
    `${ev.data_consulta} ${fmtTime(ev.hora_inicio_prevista)}${ev.duracao_minutos_prevista ? ` (${ev.duracao_minutos_prevista} min)` : ''}`,
    `Profissional: ${ev.profissional_nome || ev.profissional_nome_livre || 'Plantonista'}`,
    ev.paciente_telefone ? `Contato: ${ev.paciente_telefone}` : null,
    ev.observacao ? `Obs: ${ev.observacao}` : null
  ].filter(Boolean);
  modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-monday-lg max-w-md w-full p-6">
      <h3 class="text-lg font-semibold mb-2">${escapeHtml(ev.paciente_nome || '')}</h3>
      ${ev.status ? `<span class="inline-block text-xs px-2 py-1 rounded-full mb-3 ${statusBadgeClass(ev.status)}">${escapeHtml(statusLabel(ev.status))}</span>` : ''}
      <div class="text-sm text-gray-600 space-y-1 mb-4">${detalhes.map((d) => `<p>${escapeHtml(d)}</p>`).join('')}</div>
      <div class="flex flex-col gap-2">
        <button type="button" class="modal-editar min-h-touch px-4 py-3 bg-monday-blue text-white rounded-xl font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-monday-blue">Editar agendamento</button>
        <button type="button" class="modal-confirmar min-h-touch px-4 py-3 bg-amber-500 text-white rounded-xl font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-amber-500">Solicitar confirmação</button>
        <button type="button" class="modal-cancelar min-h-touch px-4 py-3 bg-red-500 text-white rounded-xl font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-red-500">Cancelar agendamento</button>
        <button type="button" class="modal-excluir min-h-touch px-4 py-3 bg-slate-800 text-white rounded-xl font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-slate-600">Excluir agendamento</button>
        ${(ev.status || '').toLowerCase().includes('em_atendimento')
          ? '<button type="button" class="modal-reverter min-h-touch px-4 py-3 bg-slate-600 text-white rounded-xl font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-slate-500">Reverter para agendada</button>'
          : '<button type="button" class="modal-iniciar min-h-touch px-4 py-3 bg-emerald-600 text-white rounded-xl font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-emerald-500">Iniciar atendimento</button>'}
      </div>
      <button type="button" class="modal-close mt-4 w-full min-h-touch py-2 border border-gray-300 rounded-xl focus-visible:ring-2 focus-visible:ring-offset-2">Fechar</button>
    </div>
  `;
  modal.querySelector('.modal-close').onclick = () => modal.remove();
  modal.querySelector('.modal-editar').onclick = () => {
    modal.remove();
    openModalEditarConsulta(id, container, ev);
  };
  modal.querySelector('.modal-confirmar').onclick = async () => {
    await postConfirmacao(id);
    modal.remove();
    renderAgenda(container);
  };
  modal.querySelector('.modal-cancelar').onclick = async () => {
    if (confirm('Cancelar este agendamento? Ele ficará semi-transparente na agenda.')) {
      await postCancelar(id);
      modal.remove();
      renderAgenda(container);
    }
  };
  modal.querySelector('.modal-excluir').onclick = async () => {
    if (confirm('Excluir definitivamente este agendamento? Esta ação não pode ser desfeita.')) {
      try {
        await postExcluirConsulta(id);
        modal.remove();
        renderAgenda(container);
      } catch (err) {
        alert(err.message || 'Erro ao excluir.');
      }
    }
  };
  const btnIniciar = modal.querySelector('.modal-iniciar');
  const btnReverter = modal.querySelector('.modal-reverter');
  if (btnIniciar) {
    btnIniciar.onclick = async () => {
      try {
        await postIniciarAtendimento(id);
        modal.remove();
        window.dispatchEvent(new CustomEvent('open-atendimento', { detail: { consultaId: id, date: ev.data_consulta || undefined } }));
      } catch (err) {
        alert(err.message || 'Erro ao iniciar atendimento.');
      }
    };
  }
  if (btnReverter) {
    btnReverter.onclick = async () => {
      try {
        await postReverterAtendimento(id);
        modal.remove();
        renderAgenda(container);
      } catch (err) {
        alert(err.message || 'Erro ao reverter atendimento.');
      }
    };
  }
  modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
  document.body.appendChild(modal);
  } finally {
    hideLoadingOverlay();
  }
}

async function openModalEditarConsulta(id, container, ev) {
  showLoadingOverlay('Carregando edição...');
  try {
  const [especialidades, tiposConsulta, profissionais] = await Promise.all([
    getEspecialidades(),
    getTiposConsulta(),
    getProfissionais(),
  ]);
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-y-auto';
  const horaInput = fmtTime(ev.hora_inicio_prevista) || '09:00';
  modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-monday-lg max-w-md w-full p-6 my-8">
      <h3 class="text-lg font-semibold mb-4">Editar agendamento</h3>
      <form id="form-editar" class="space-y-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Paciente</label>
          <input type="text" value="${escapeHtml(ev.paciente_nome || '')}" disabled class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50">
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Data</label>
            <input type="date" name="data_consulta" id="editar-data-consulta" value="${ev.data_consulta}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            <div id="editar-feriado-aviso" class="mt-1 text-xs min-h-[1.25rem]"></div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Hora</label>
            <input type="time" name="hora_inicio_prevista" value="${horaInput}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Duração (min)</label>
            <input type="number" name="duracao_minutos_prevista" value="${ev.duracao_minutos_prevista || 60}" min="10" step="10" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Especialidade</label>
            <select name="especialidade_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
              ${especialidades.map((e) => `<option value="${e.id}" ${e.id === ev.especialidade_id ? 'selected' : ''}>${escapeHtml(e.nome)}</option>`).join('')}
            </select>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Profissional</label>
          <select name="profissional_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            <option value="" ${!ev.profissional_id ? 'selected' : ''}>Plantonista</option>
            ${profissionais.map((p) => `<option value="${p.id}" ${ev.profissional_id == p.id ? 'selected' : ''}>${escapeHtml(p.nome)}</option>`).join('')}
          </select>
        </div>
        <div class="flex gap-2 pt-4">
          <button type="submit" class="flex-1 py-3 bg-monday-blue text-white rounded-lg font-medium">Salvar</button>
          <button type="button" class="btn-cancel-editar px-4 py-3 border border-gray-300 rounded-lg">Cancelar</button>
        </div>
      </form>
    </div>
  `;
  modal.querySelector('.btn-cancel-editar').onclick = () => modal.remove();

  const atualizarFeriadoEditar = async () => {
    const input = modal.querySelector('#editar-data-consulta');
    const aviso = modal.querySelector('#editar-feriado-aviso');
    if (!input || !aviso) return;
    const data = input.value;
    if (!data) { aviso.innerHTML = ''; return; }
    try {
      const { feriados } = await getFeriadosVerificar([data]);
      const info = feriados[data];
      if (info?.feriado) {
        aviso.innerHTML = `<span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-amber-100 text-amber-800"><i data-lucide="party-popper" class="w-3.5 h-3.5"></i><strong>Feriado:</strong> ${escapeHtml(info.nome || '')}</span>`;
        if (typeof lucide !== 'undefined') lucide.createIcons();
      } else {
        aviso.innerHTML = '';
      }
    } catch {
      aviso.innerHTML = '';
    }
  };
  modal.querySelector('#editar-data-consulta')?.addEventListener('change', atualizarFeriadoEditar);
  atualizarFeriadoEditar();

  modal.querySelector('#form-editar').onsubmit = async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn?.innerHTML;
    if (btn) { btn.disabled = true; btn.innerHTML = getButtonSpinnerHtml() + ' Salvando...'; }
    try {
    const fd = new FormData(e.target);
    const horaVal = fd.get('hora_inicio_prevista');
    await putConsulta(id, {
      data_consulta: fd.get('data_consulta'),
      hora_inicio_prevista: (horaVal.length === 5 ? horaVal + ':00' : horaVal),
      duracao_minutos_prevista: parseInt(fd.get('duracao_minutos_prevista'), 10) || 60,
      especialidade_id: parseInt(fd.get('especialidade_id'), 10),
      profissional_id: fd.get('profissional_id') || null,
      profissional_nome_livre: fd.get('profissional_id') ? null : 'Plantonista',
    });
    modal.remove();
    renderAgenda(container);
    } catch (err) {
      if (btn) { btn.disabled = false; btn.innerHTML = originalText || 'Salvar'; }
      throw err;
    }
  };
  modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
  document.body.appendChild(modal);
  } finally {
    hideLoadingOverlay();
  }
}

function escapeHtml(s) {
  const d = document.createElement('div');
  d.textContent = s || '';
  return d.innerHTML;
}
