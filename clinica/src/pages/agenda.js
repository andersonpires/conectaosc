import { getAgenda, getFeriadosVerificar, getDiasComAgendamento, postConfirmacao, postCancelar, postExcluirConsulta, postIniciarAtendimento, postReverterAtendimento, postConsulta, putConsulta, getPacientes, getPaciente, getEspecialidades, getTiposConsulta, getProfissionais } from '../services/api.js';
import { getSpinnerHtml, getButtonSpinnerHtml, showLoadingOverlay, hideLoadingOverlay } from '../utils/loading.js';

let currentDate = new Date().toISOString().slice(0, 10);
let currentView = 'day';
let navDelegateAttached = false;
let lastAgendaResponse = null;
let agendaPreviewEl = null;
let agendaPreviewHideTimer = null;
let filterAgenda = {
  especialidade_id: '',
  profissional_id: '',
  paciente: '',
  start_date: '',
  end_date: '',
  hora_inicio: '',
  hora_fim: '',
  status_keys: ['futuro', 'atrasada', 'concluida']
};
const agendaAuxCache = {
  especialidades: null,
  profissionais: null,
  feriados: new Map(),
  inFlightEspecialidades: null,
  inFlightProfissionais: null,
  inFlightFeriados: new Map()
};

function getDefaultStatusKeys() {
  return STATUS_FILTER_OPTIONS.map((item) => item.key);
}

const STATUS_FILTER_OPTIONS = [
  { key: 'futuro', label: 'Amarelo: futuro', activeClass: 'bg-amber-100 text-amber-800 border-amber-200' },
  { key: 'atrasada', label: 'Vermelho: atrasado', activeClass: 'bg-red-100 text-red-800 border-red-200' },
  { key: 'concluida', label: 'Verde: concluído', activeClass: 'bg-emerald-100 text-emerald-800 border-emerald-200' }
];

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

function formatDateBr(dateStr, options = {}) {
  if (!dateStr) return '';
  return parseLocalDate(dateStr).toLocaleDateString('pt-BR', options);
}

function formatDateShort(dateStr) {
  return formatDateBr(dateStr, { day: '2-digit', month: '2-digit', year: '2-digit' });
}

function parseDateTime(ev) {
  const dateStr = ev?.data_consulta || '';
  const timeStr = String(ev?.hora_inicio_prevista || '00:00').slice(0, 5);
  if (!dateStr) return null;
  return new Date(`${dateStr}T${timeStr}:00`);
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

function getStatusAppearance(ev) {
  const status = String(ev?.status || '').toLowerCase();
  if (status.includes('cancelada')) {
    return { key: 'cancelada', label: 'Cancelada', accent: '#94a3b8', soft: '#f8fafc', chip: 'bg-slate-100 text-slate-700' };
  }
  if (status.includes('concluida') || status.includes('concluída') || status.includes('em_atendimento')) {
    return { key: 'concluida', label: 'Concluído', accent: '#22c55e', soft: '#ecfdf3', chip: 'bg-emerald-100 text-emerald-800' };
  }
  const inicio = parseDateTime(ev);
  if (inicio && inicio < new Date()) {
    return { key: 'atrasada', label: 'Atrasado', accent: '#ef4444', soft: '#fff1f2', chip: 'bg-rose-100 text-rose-800' };
  }
  return { key: 'futuro', label: 'Futuro', accent: '#facc15', soft: '#fffbeb', chip: 'bg-amber-100 text-amber-800' };
}

function isStatusVisible(ev) {
  const activeKeys = Array.isArray(filterAgenda.status_keys) && filterAgenda.status_keys.length
    ? filterAgenda.status_keys
    : STATUS_FILTER_OPTIONS.map((item) => item.key);
  return activeKeys.includes(getStatusAppearance(ev).key);
}

function buildStatusFilterButtons() {
  const activeKeys = Array.isArray(filterAgenda.status_keys) && filterAgenda.status_keys.length
    ? filterAgenda.status_keys
    : STATUS_FILTER_OPTIONS.map((item) => item.key);
  return STATUS_FILTER_OPTIONS.map((item) => {
    const active = activeKeys.includes(item.key);
    const className = active
      ? item.activeClass
      : 'bg-slate-100 text-slate-400 border-slate-200';
    return `
      <button
        type="button"
        class="status-filter-btn px-2 py-1 rounded-full border text-xs transition ${className}"
        data-status-key="${item.key}"
        aria-pressed="${active ? 'true' : 'false'}"
      >${item.label}</button>
    `;
  }).join('');
}

const ESPECIALIDADE_CORES = {
  fisioterapia: { bg: '#edf9f7', border: '#54A5A0', iconBg: '#d7f1ee', text: '#2b7f79' },
  psicologia: { bg: '#eef2ff', border: '#7c3aed', iconBg: '#e0e7ff', text: '#5b21b6' },
  nutrição: { bg: '#fff7ed', border: '#f59e0b', iconBg: '#ffedd5', text: '#c2410c' },
  nutricao: { bg: '#fff7ed', border: '#f59e0b', iconBg: '#ffedd5', text: '#c2410c' },
  fonoaudiologia: { bg: '#ecfeff', border: '#06b6d4', iconBg: '#cffafe', text: '#0f766e' }
};

function especialidadeColor(nome) {
  if (!nome) return { bg: '#f8fafc', border: '#94a3b8', iconBg: '#e2e8f0', text: '#475569' };
  const key = String(nome).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  if (key.includes('fisioterapia')) return ESPECIALIDADE_CORES.fisioterapia;
  if (key.includes('psicologia')) return ESPECIALIDADE_CORES.psicologia;
  if (key.includes('nutricao') || key.includes('nutrição')) return ESPECIALIDADE_CORES.nutricao;
  if (key.includes('fonoaudiologia') || key.includes('fonodialogia')) return ESPECIALIDADE_CORES.fonoaudiologia;
  return { bg: '#f8fafc', border: '#94a3b8', iconBg: '#e2e8f0', text: '#475569' };
}

function getProfissionalFotoUrl(ev) {
  if (!ev?.profissional_id) return getFallbackFotoUrl();
  const foto = String(ev?.profissional_foto || '').trim();
  if (!foto) return getFallbackFotoUrl();
  return joinFotoPath(foto);
}

function getPacienteFotoUrl(ev) {
  const foto = String(ev?.paciente_foto || ev?.Foto || '').trim();
  if (!foto) return getFallbackFotoUrl();
  return joinFotoPath(foto);
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

function buildEventTooltipData(ev) {
  const partes = [];
  partes.push(`Paciente: ${ev.paciente_nome || '—'}`);
  partes.push(`Data: ${ev.data_consulta ? formatDateShort(ev.data_consulta) : '—'}`);
  partes.push(`Horário: ${fmtTime(ev.hora_inicio_prevista)}${ev.duracao_minutos_prevista ? ` (${ev.duracao_minutos_prevista} min)` : ''}`);
  partes.push(`Especialidade: ${ev.especialidade_nome || '—'}`);
  partes.push(`Profissional: ${ev.profissional_nome || ev.profissional_nome_livre || 'Plantonista'}`);
  if (ev.tipo_nome) partes.push(`Tipo: ${ev.tipo_nome}`);
  partes.push(`Status: ${statusLabel(ev.status)}`);
  if (ev.paciente_telefone) partes.push(`Contato: ${ev.paciente_telefone}`);
  if (ev.observacao) partes.push(`Obs: ${ev.observacao}`);
  return partes.join('\n');
}

function buildPreviewPayload(ev) {
  const appearance = getStatusAppearance(ev);
  return {
    title: ev.paciente_nome || '',
    accent: appearance.accent,
    pacienteFoto: getPacienteFotoUrl(ev),
    profissionalFoto: getProfissionalFotoUrl(ev),
    lines: [
      `${formatDateShort(ev.data_consulta)} • ${fmtTime(ev.hora_inicio_prevista)}${ev.duracao_minutos_prevista ? ` • ${ev.duracao_minutos_prevista} min` : ''}`,
      ev.especialidade_nome || '',
      ev.profissional_nome || ev.profissional_nome_livre || 'Plantonista',
      statusLabel(ev.status),
      ev.observacao || ''
    ].filter(Boolean)
  };
}

function ensureAgendaPreview() {
  if (agendaPreviewEl?.isConnected) return agendaPreviewEl;
  agendaPreviewEl = document.createElement('div');
  agendaPreviewEl.className = 'fixed z-[70] hidden max-w-[280px] rounded-2xl border border-slate-200 bg-white px-3 py-3 shadow-2xl';
  document.body.appendChild(agendaPreviewEl);
  return agendaPreviewEl;
}

function hideAgendaPreview(delay = 0) {
  window.clearTimeout(agendaPreviewHideTimer);
  agendaPreviewHideTimer = window.setTimeout(() => {
    if (!agendaPreviewEl) return;
    agendaPreviewEl.classList.add('hidden');
    agendaPreviewEl.innerHTML = '';
  }, delay);
}

function showAgendaPreview(sourceEl) {
  const preview = ensureAgendaPreview();
  const payloadRaw = sourceEl.dataset.preview;
  if (!payloadRaw) return;
  const payload = JSON.parse(payloadRaw);
  preview.innerHTML = `
    <div class="relative min-h-[64px] pr-4">
      <img
        src="${escapeHtml(payload.pacienteFoto)}"
        alt="Paciente"
        class="h-12 w-12 shrink-0 rounded-full object-cover border border-slate-200"
        onerror="this.onerror=null;this.src='${escapeHtml(getFallbackFotoUrl())}';"
      >
      <div class="min-w-0 -mt-12 ml-16 pr-2">
        <div class="text-sm font-semibold text-slate-900">${escapeHtml(payload.title)}</div>
        ${payload.lines.map((line) => `<div class="mt-1 text-xs text-slate-600">${escapeHtml(line)}</div>`).join('')}
      </div>
      <img
        src="${escapeHtml(payload.profissionalFoto)}"
        alt="Profissional"
        class="absolute bottom-0 right-0 h-6 w-6 rounded-full object-cover border border-white shadow-sm"
        onerror="this.onerror=null;this.src='${escapeHtml(getFallbackFotoUrl())}';"
      >
    </div>
  `;
  preview.style.borderLeft = `4px solid ${payload.accent}`;
  const rect = sourceEl.getBoundingClientRect();
  preview.classList.remove('hidden');
  preview.style.visibility = 'hidden';
  preview.style.top = '16px';
  preview.style.left = '16px';
  const previewWidth = preview.offsetWidth || 280;
  const previewHeight = preview.offsetHeight || 160;
  const preferredLeft = rect.right + 12;
  const maxLeft = window.innerWidth - previewWidth - 12;
  const left = preferredLeft <= maxLeft ? preferredLeft : maxLeft;
  const top = Math.min(
    window.innerHeight - previewHeight - 12,
    Math.max(12, rect.top + (rect.height / 2) - (previewHeight / 2))
  );
  preview.style.left = `${left}px`;
  preview.style.top = `${top}px`;
  preview.style.visibility = 'visible';
}

function attachCompactEventPreviews(container) {
  container.querySelectorAll('.event-block[data-preview]').forEach((el) => {
    let longPressTimer = null;
    el.addEventListener('mouseenter', () => showAgendaPreview(el));
    el.addEventListener('mouseleave', () => hideAgendaPreview(80));
    el.addEventListener('focus', () => showAgendaPreview(el));
    el.addEventListener('blur', () => hideAgendaPreview());
    el.addEventListener('touchstart', () => {
      longPressTimer = window.setTimeout(() => {
        el.dataset.previewLocked = '1';
        showAgendaPreview(el);
      }, 350);
    }, { passive: true });
    el.addEventListener('touchend', () => {
      window.clearTimeout(longPressTimer);
      window.setTimeout(() => { delete el.dataset.previewLocked; }, 250);
      hideAgendaPreview(1500);
    }, { passive: true });
    el.addEventListener('touchmove', () => window.clearTimeout(longPressTimer), { passive: true });
  });
}

function getDatasParaFeriados(view, dateStr) {
  const datas = [];
  const hasCustomRange = filterAgenda.start_date || filterAgenda.end_date;
  if (hasCustomRange && view !== 'day') {
    return enumerateDates(filterAgenda.start_date || filterAgenda.end_date, filterAgenda.end_date || filterAgenda.start_date);
  }
  if (view === 'day') {
    datas.push(dateStr);
  } else if (view === 'week') {
    const d = parseLocalDate(dateStr);
    const sunday = new Date(d);
    sunday.setDate(d.getDate() - d.getDay());
    for (let i = 0; i < 7; i++) {
      const x = new Date(sunday);
      x.setDate(sunday.getDate() + i);
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

function getFeriadosCacheKey(view, dateStr) {
  return `${view}:${getDatasParaFeriados(view, dateStr).join(',')}`;
}

async function loadEspecialidadesCached() {
  if (Array.isArray(agendaAuxCache.especialidades)) return agendaAuxCache.especialidades;
  if (agendaAuxCache.inFlightEspecialidades) return agendaAuxCache.inFlightEspecialidades;
  agendaAuxCache.inFlightEspecialidades = getEspecialidades()
    .then((rows) => {
      agendaAuxCache.especialidades = Array.isArray(rows) ? rows : [];
      return agendaAuxCache.especialidades;
    })
    .catch(() => [])
    .finally(() => { agendaAuxCache.inFlightEspecialidades = null; });
  return agendaAuxCache.inFlightEspecialidades;
}

async function loadProfissionaisCached() {
  if (Array.isArray(agendaAuxCache.profissionais)) return agendaAuxCache.profissionais;
  if (agendaAuxCache.inFlightProfissionais) return agendaAuxCache.inFlightProfissionais;
  agendaAuxCache.inFlightProfissionais = getProfissionais()
    .then((rows) => {
      agendaAuxCache.profissionais = Array.isArray(rows) ? rows : [];
      return agendaAuxCache.profissionais;
    })
    .catch(() => [])
    .finally(() => { agendaAuxCache.inFlightProfissionais = null; });
  return agendaAuxCache.inFlightProfissionais;
}

async function loadFeriadosCached(view, dateStr) {
  const key = getFeriadosCacheKey(view, dateStr);
  if (agendaAuxCache.feriados.has(key)) return agendaAuxCache.feriados.get(key);
  if (agendaAuxCache.inFlightFeriados.has(key)) return agendaAuxCache.inFlightFeriados.get(key);
  const req = getFeriadosVerificar(getDatasParaFeriados(view, dateStr))
    .then(({ feriados }) => {
      const mapFeriados = feriados || {};
      agendaAuxCache.feriados.set(key, mapFeriados);
      return mapFeriados;
    })
    .catch(() => ({}))
    .finally(() => { agendaAuxCache.inFlightFeriados.delete(key); });
  agendaAuxCache.inFlightFeriados.set(key, req);
  return req;
}

function buildEspecialidadeOptions(especialidades) {
  const rows = Array.isArray(especialidades) ? especialidades : [];
  if (!rows.length) {
    return '<option value="">Todas especialidades</option>';
  }
  return `<option value="">Todas especialidades</option>${rows.map((e) => `<option value="${e.id}" ${filterAgenda.especialidade_id == e.id ? 'selected' : ''}>${escapeHtml(e.nome)}</option>`).join('')}`;
}

function buildProfissionalOptions(profissionais) {
  const rows = Array.isArray(profissionais) ? profissionais : [];
  return `<option value="">Todos profissionais</option>
          <option value="plantonista" ${filterAgenda.profissional_id === 'plantonista' ? 'selected' : ''}>Plantonista</option>
          ${rows.map((p) => `<option value="${p.id}" ${filterAgenda.profissional_id == p.id ? 'selected' : ''}>${escapeHtml(p.nome)}</option>`).join('')}`;
}

function getBadgeFeriadoHtml(infoFeriado) {
  if (!infoFeriado?.feriado) return '';
  return `<span class="badge bg-danger text-white text-center w-full py-2 mt-1" title="Feriado">${escapeHtml(infoFeriado.nome || 'Feriado')}</span>`;
}

function getEffectiveRange() {
  const start = filterAgenda.start_date || '';
  const end = filterAgenda.end_date || '';
  if (start || end) {
    return { start: start || end, end: end || start };
  }
  if (currentView === 'day') {
    return { start: currentDate, end: currentDate };
  }
  if (currentView === 'week') {
    const d = parseLocalDate(currentDate);
    const sunday = new Date(d);
    sunday.setDate(d.getDate() - d.getDay());
    const saturday = new Date(sunday);
    saturday.setDate(sunday.getDate() + 6);
    return { start: fmtDate(sunday), end: fmtDate(saturday) };
  }
  const startMonth = currentDate.slice(0, 8) + '01';
  const base = parseLocalDate(currentDate);
  const endDate = new Date(base.getFullYear(), base.getMonth() + 1, 0);
  return { start: startMonth, end: fmtDate(endDate) };
}

function buildRangeTitle(range) {
  if (currentView === 'month' && range.start !== range.end) {
    return parseLocalDate(currentDate.slice(0, 8) + '01').toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
  }
  if (range.start === range.end) {
    return formatDateBr(range.start, { weekday: 'short', day: '2-digit', month: 'long', year: 'numeric' });
  }
  if (currentView === 'week') {
    return `${formatDateBr(range.start, { day: 'numeric', month: 'long' })} - ${formatDateBr(range.end, { day: 'numeric', month: 'long', year: 'numeric' })}`;
  }
  return `${formatDateBr(range.start, { day: '2-digit', month: 'short' })} - ${formatDateBr(range.end, { day: '2-digit', month: 'short', year: 'numeric' })}`;
}

function groupEventsByDate(events) {
  return (Array.isArray(events) ? events : []).reduce((acc, ev) => {
    const key = ev.data_consulta || currentDate;
    if (!acc[key]) acc[key] = [];
    acc[key].push(ev);
    return acc;
  }, {});
}

function getWeekRangeFromDate(dateStr) {
  const base = parseLocalDate(dateStr);
  const start = new Date(base);
  start.setDate(base.getDate() - base.getDay());
  const end = new Date(start);
  end.setDate(start.getDate() + 6);
  return { start: fmtDate(start), end: fmtDate(end) };
}

function getMonthGridRange(dateStr) {
  const base = parseLocalDate(dateStr.slice(0, 8) + '01');
  const start = new Date(base);
  start.setDate(base.getDate() - base.getDay());
  const end = new Date(start);
  end.setDate(start.getDate() + 41);
  return { start: fmtDate(start), end: fmtDate(end) };
}

function enumerateDates(startStr, endStr) {
  const datas = [];
  for (let data = parseLocalDate(startStr); fmtDate(data) <= endStr; data.setDate(data.getDate() + 1)) {
    datas.push(fmtDate(data));
  }
  return datas;
}

function getEventStartMinutes(ev) {
  const [hour, minute] = String(ev?.hora_inicio_prevista || '00:00').slice(0, 5).split(':').map(Number);
  return ((hour || 0) * 60) + (minute || 0);
}

function getEventEndMinutes(ev) {
  return getEventStartMinutes(ev) + Number(ev?.duracao_minutos_prevista || 60);
}

function sortEventsByStart(events) {
  return [...(Array.isArray(events) ? events : [])].sort((a, b) => {
    const dateCompare = String(a.data_consulta || '').localeCompare(String(b.data_consulta || ''));
    if (dateCompare !== 0) return dateCompare;
    return String(a.hora_inicio_prevista || '').localeCompare(String(b.hora_inicio_prevista || ''));
  });
}

function buildCalendarEventCard(ev, compact = false) {
  const appearance = getStatusAppearance(ev);
  const isCancelada = String(ev.status || '').toLowerCase().includes('cancelada');
  const tooltipText = buildEventTooltipData(ev);
  const profissional = ev.profissional_nome || ev.profissional_nome_livre || 'Plantonista';
  const profissionalFoto = getProfissionalFotoUrl(ev);
  const horarios = fmtTime(ev.hora_inicio_prevista);
  const previewPayload = escapeAttribute(JSON.stringify(buildPreviewPayload(ev)));
  if (compact) {
    return `
      <button
        type="button"
        class="event-block w-full text-left rounded-lg px-2 py-1.5 transition hover:opacity-95 active:opacity-95 ${isCancelada ? 'opacity-60' : ''}"
        data-id="${ev.id}"
        data-data-consulta="${ev.data_consulta || currentDate}"
        data-preview="${previewPayload}"
        aria-label="${escapeAttribute(tooltipText)}"
        style="background:${appearance.soft}; border-left:4px solid ${appearance.accent}"
      >
        <span class="flex items-center gap-1.5 min-w-0 whitespace-nowrap">
          <span class="shrink-0 text-[10px] font-semibold text-slate-700">${escapeHtml(horarios)}</span>
          <span class="min-w-0 truncate text-[10px] font-bold text-slate-900">${escapeHtml(ev.paciente_nome || '')}</span>
          <img
            src="${escapeHtml(profissionalFoto)}"
            alt="Profissional"
            class="ml-auto h-5 w-5 shrink-0 rounded-full object-cover border border-white/80 shadow-sm"
            onerror="this.onerror=null;this.src='${escapeHtml(getFallbackFotoUrl())}';"
          >
        </span>
      </button>
    `;
  }
  return `
    <button
      type="button"
      class="event-block w-full text-left rounded-xl px-3 py-2 transition hover:opacity-95 ${isCancelada ? 'opacity-60' : ''}"
      data-id="${ev.id}"
      data-data-consulta="${ev.data_consulta || currentDate}"
      aria-label="${escapeAttribute(tooltipText)}"
      style="background:${appearance.soft}; border-left:4px solid ${appearance.accent}"
    >
      <span class="block text-[11px] font-semibold text-slate-700">${escapeHtml(horarios)}</span>
      <span class="block font-semibold text-slate-900 text-sm mt-1">${escapeHtml(ev.paciente_nome || '')}</span>
      <span class="block text-xs text-slate-500 mt-0.5">${escapeHtml(ev.especialidade_nome || '')}</span>
      <span class="block ${compact ? 'text-[11px]' : 'text-xs'} text-slate-500 truncate">${escapeHtml(profissional)}</span>
    </button>
  `;
}

function renderFilteredEventList(events) {
  const rows = sortEventsByStart(events);
  if (!rows.length) {
    return `
      <div class="bg-white rounded-2xl shadow-monday border border-slate-200 px-4 py-8 text-center text-sm text-slate-500">
        Nenhum atendimento encontrado no período informado.
      </div>
    `;
  }
  return `
    <div class="bg-white rounded-2xl shadow-monday overflow-hidden border border-slate-200">
      <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
        <h3 class="text-sm font-semibold text-slate-800">Atendimentos filtrados</h3>
      </div>
      <div class="divide-y divide-slate-100">
        ${rows.map((ev) => {
          const appearance = getStatusAppearance(ev);
          const tooltipText = buildEventTooltipData(ev);
          return `
            <button
              type="button"
              class="event-block w-full text-left px-4 py-3 hover:bg-slate-50 transition"
              data-id="${ev.id}"
              data-data-consulta="${ev.data_consulta || currentDate}"
              aria-label="${escapeAttribute(tooltipText)}"
            >
              <span class="block text-xs font-semibold" style="color:${appearance.accent}">${formatDateShort(ev.data_consulta)} • ${fmtTime(ev.hora_inicio_prevista)}</span>
              <span class="block mt-1 text-sm font-semibold text-slate-900">${escapeHtml(ev.paciente_nome || '')}</span>
              <span class="block text-xs text-slate-500">${escapeHtml(ev.especialidade_nome || '')} • ${escapeHtml(ev.profissional_nome || ev.profissional_nome_livre || 'Plantonista')}</span>
            </button>
          `;
        }).join('')}
      </div>
    </div>
  `;
}

function renderAgendaWeekGrid(events, range, mapFeriados) {
  const diasSemana = enumerateDates(range.start, range.end);
  const eventosPorDia = groupEventsByDate(sortEventsByStart(events));
  const slotMinStep = 60;
  const dayStartMins = 7 * 60;
  const dayEndMins = 21 * 60;
  const totalMinutes = dayEndMins - dayStartMins;
  const hourSlots = Array.from({ length: totalMinutes / slotMinStep }, (_, index) => dayStartMins + (index * slotMinStep));
  const nomesDias = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
  const headerHtml = diasSemana.map((dataStr) => {
    const data = parseLocalDate(dataStr);
    const feriado = mapFeriados[dataStr];
    return `
      <div class="border-l border-slate-200 px-3 py-3 min-h-[88px] bg-white">
        <span class="block text-sm font-medium text-slate-700 capitalize">${nomesDias[data.getDay()]}</span>
        <span class="block text-2xl font-semibold text-slate-900 mt-1">${data.getDate()}</span>
        ${feriado?.feriado ? `<span class="inline-flex mt-2 text-[11px] px-2 py-1 rounded-full bg-rose-100 text-rose-700">${escapeHtml(feriado.nome || 'Feriado')}</span>` : ''}
      </div>
    `;
  }).join('');
  const gridColumns = `72px repeat(${diasSemana.length}, minmax(0, 1fr))`;
  const minGridWidth = 72 + (diasSemana.length * 150);
  const bodyHtml = hourSlots.map((minutes) => {
    const label = `${String(Math.floor(minutes / 60)).padStart(2, '0')}:00`;
    const cells = diasSemana.map((dataStr) => {
      const eventosDia = (eventosPorDia[dataStr] || []).filter((ev) => {
        const start = getEventStartMinutes(ev);
        return start >= minutes && start < (minutes + slotMinStep);
      });
      return `
        <div class="border-l border-t border-slate-200 px-2 py-2 min-h-[96px] bg-white/95 align-top">
          <div class="space-y-2">
            ${eventosDia.map((ev) => buildCalendarEventCard(ev, true)).join('')}
          </div>
        </div>
      `;
    }).join('');
    return `
      <div class="contents">
        <div class="border-t border-slate-200 px-2 py-3 text-xs font-medium text-slate-500 bg-slate-50">${label}</div>
        ${cells}
      </div>
    `;
  }).join('');
  return `
    <div class="bg-white rounded-2xl shadow-monday overflow-hidden border border-slate-200">
      <div class="overflow-x-auto">
        <div class="grid" style="grid-template-columns:${gridColumns}; min-width:${minGridWidth}px">
          <div class="bg-slate-50 border-b border-slate-200"></div>
          ${headerHtml}
          ${bodyHtml}
        </div>
      </div>
    </div>
  `;
}

function renderAgendaMonthGrid(events, dateStr, mapFeriados, visibleRange = null) {
  const diasSemana = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
  const monthBase = parseLocalDate(dateStr.slice(0, 8) + '01');
  const monthGrid = visibleRange || getMonthGridRange(dateStr);
  const eventosPorDia = groupEventsByDate(sortEventsByStart(events));
  const firstDate = parseLocalDate(monthGrid.start);
  const leadingBlanks = Array.from({ length: firstDate.getDay() }, () => '<div class="min-h-[150px] border-r border-b border-slate-200 bg-slate-50/60"></div>');
  const dias = enumerateDates(monthGrid.start, monthGrid.end);
  const headerHtml = diasSemana.map((dia) => `<div class="px-3 py-3 text-sm font-semibold text-slate-700 border-b border-slate-200 bg-slate-50 capitalize">${dia}</div>`).join('');
  const cellsHtml = dias.map((dataStr) => {
    const data = parseLocalDate(dataStr);
    const isCurrentMonth = data.getMonth() === monthBase.getMonth();
    const isToday = dataStr === fmtDate(new Date());
    const feriado = mapFeriados[dataStr];
    const eventosDia = (eventosPorDia[dataStr] || []).slice(0, 3);
    const extraCount = Math.max(0, (eventosPorDia[dataStr] || []).length - eventosDia.length);
    return `
      <div class="min-h-[150px] border-r border-b border-slate-200 p-3 ${isCurrentMonth ? 'bg-white' : 'bg-slate-50/70'}">
        <div class="flex items-center justify-between gap-2">
          <span class="inline-flex items-center justify-center h-8 min-w-8 px-2 rounded-full text-sm font-semibold ${isToday ? 'bg-monday-blue text-white' : isCurrentMonth ? 'text-slate-900' : 'text-slate-400'}">${data.getDate()}</span>
          ${feriado?.feriado ? '<span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>' : ''}
        </div>
        ${feriado?.feriado ? `<div class="mt-2 text-[11px] font-medium text-rose-700 truncate">${escapeHtml(feriado.nome || 'Feriado')}</div>` : ''}
        <div class="mt-3 space-y-2">
          ${eventosDia.map((ev) => buildCalendarEventCard(ev, true)).join('')}
          ${extraCount > 0 ? `<div class="text-[11px] font-medium text-slate-500 px-1">+${extraCount} atendimento(s)</div>` : ''}
        </div>
      </div>
    `;
  }).join('');
  const totalCells = leadingBlanks.length + dias.length;
  const trailingCount = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
  const trailingBlanks = Array.from({ length: trailingCount }, () => '<div class="min-h-[150px] border-r border-b border-slate-200 bg-slate-50/60"></div>').join('');
  return `
    <div class="bg-white rounded-2xl shadow-monday overflow-hidden border border-slate-200">
      <div class="overflow-x-auto">
        <div class="min-w-[1120px] grid grid-cols-7">
          ${headerHtml}
          ${leadingBlanks.join('')}
          ${cellsHtml}
          ${trailingBlanks}
        </div>
      </div>
    </div>
  `;
}

async function hydrateAgendaAuxUi(container, context) {
  const [especialidades, profissionais, mapFeriados] = await Promise.all([
    loadEspecialidadesCached(),
    loadProfissionaisCached(),
    loadFeriadosCached(context.view, context.date)
  ]);
  if (!container.isConnected) return;
  if (currentView !== context.view || currentDate !== context.date) return;

  const selectEspecialidade = container.querySelector('#filtro-especialidade');
  if (selectEspecialidade) {
    selectEspecialidade.innerHTML = buildEspecialidadeOptions(especialidades);
    selectEspecialidade.value = filterAgenda.especialidade_id || '';
  }
  const selectProfissional = container.querySelector('#filtro-profissional');
  if (selectProfissional) {
    selectProfissional.innerHTML = buildProfissionalOptions(profissionais);
    selectProfissional.value = filterAgenda.profissional_id ?? '';
  }
  const badgeFeriado = container.querySelector('#badge-feriado');
  if (badgeFeriado) {
    badgeFeriado.innerHTML = getBadgeFeriadoHtml(mapFeriados[currentDate]);
  }
}

export async function renderAgenda(container, opts = {}) {
  const { useCachedData = false } = opts;
  hideAgendaPreview();
  if (!useCachedData) {
    container.innerHTML = getSpinnerHtml('Carregando agenda...');
  }
  const context = { view: currentView, date: currentDate };
  const feriadosCacheKey = getFeriadosCacheKey(currentView, currentDate);
  const especialidades = Array.isArray(agendaAuxCache.especialidades) ? agendaAuxCache.especialidades : [];
  const profissionais = Array.isArray(agendaAuxCache.profissionais) ? agendaAuxCache.profissionais : [];
  const mapFeriados = agendaAuxCache.feriados.get(feriadosCacheKey) || {};
  const agendaData = useCachedData && lastAgendaResponse
    ? lastAgendaResponse
    : await getAgenda(currentView, currentDate, {
        especialidade_id: filterAgenda.especialidade_id || undefined,
        profissional_id: filterAgenda.profissional_id,
        paciente: filterAgenda.paciente || undefined,
        start_date: filterAgenda.start_date || undefined,
        end_date: filterAgenda.end_date || undefined,
        hora_inicio: filterAgenda.hora_inicio || undefined,
        hora_fim: filterAgenda.hora_fim || undefined
      });
  const { eventos } = agendaData;
  lastAgendaResponse = agendaData;
  const range = getEffectiveRange();
  const hasCustomRange = Boolean(filterAgenda.start_date || filterAgenda.end_date);
  const viewRange = hasCustomRange && currentView !== 'day'
    ? range
    : currentView === 'week'
      ? getWeekRangeFromDate(currentDate)
      : currentView === 'month'
        ? { start: currentDate.slice(0, 8) + '01', end: fmtDate(new Date(parseLocalDate(currentDate.slice(0, 8) + '01').getFullYear(), parseLocalDate(currentDate.slice(0, 8) + '01').getMonth() + 1, 0)) }
        : { start: currentDate, end: currentDate };
  const titleRange = hasCustomRange ? range : viewRange;
  const hoje = new Date();
  const isHoje = titleRange.start === `${hoje.getFullYear()}-${String(hoje.getMonth() + 1).padStart(2, '0')}-${String(hoje.getDate()).padStart(2, '0')}` && titleRange.end === titleRange.start;
  const tituloData = buildRangeTitle(titleRange);
  const badgeFeriado = getBadgeFeriadoHtml(mapFeriados[currentDate]);
  const navHtml = `
    <div class="flex flex-col gap-3 mb-4 bg-white rounded-xl shadow-monday p-3 md:flex-row md:items-center md:justify-between overflow-visible">
      <button type="button" id="btn-prev" class="min-w-[44px] min-h-[44px] w-11 h-11 flex items-center justify-center rounded-lg hover:bg-gray-100 flex-shrink-0" aria-label="Período anterior">
        <i data-lucide="chevron-left" class="w-6 h-6 pointer-events-none block"></i>
      </button>
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-center md:gap-2 flex-1 min-w-0 overflow-hidden">
        <button type="button" id="btn-today" class="w-full md:w-auto px-3 py-2 bg-monday-blue text-white rounded-lg text-sm font-medium shrink-0">Hoje</button>
        <div class="flex flex-col items-center justify-center flex-1 min-w-0 text-center" id="titulo-data">
          <span class="font-semibold py-2 text-center">${tituloData}${isHoje ? ' (Hoje)' : ''}</span>
          <div id="badge-feriado" class="w-full">${badgeFeriado}</div>
        </div>
        <button type="button" id="btn-ir-data" class="w-full md:w-auto flex items-center justify-center gap-1 px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 text-sm shrink-0" title="Ir para data específica">
          <i data-lucide="calendar-days" class="w-4 h-4 pointer-events-none"></i>
          <span>Data</span>
        </button>
      </div>
      <button type="button" id="btn-next" class="min-w-[44px] min-h-[44px] w-11 h-11 flex items-center justify-center rounded-lg hover:bg-gray-100 flex-shrink-0" aria-label="Próximo período">
        <i data-lucide="chevron-right" class="w-6 h-6 pointer-events-none block"></i>
      </button>
    </div>
    <div class="flex flex-col gap-2 mb-4 md:flex-row md:items-center md:flex-wrap">
      <button type="button" data-view="day" class="view-btn px-3 py-2 rounded-lg text-sm ${currentView === 'day' ? 'bg-monday-blue text-white' : 'bg-gray-200'}">Dia</button>
      <button type="button" data-view="week" class="view-btn px-3 py-2 rounded-lg text-sm ${currentView === 'week' ? 'bg-monday-blue text-white' : 'bg-gray-200'}">Semana</button>
      <button type="button" data-view="month" class="view-btn px-3 py-2 rounded-lg text-sm ${currentView === 'month' ? 'bg-monday-blue text-white' : 'bg-gray-200'}">Mês</button>
      <div class="flex flex-col gap-2 md:ml-auto md:flex-row">
        <button type="button" id="btn-refresh" class="w-full md:w-auto px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-100 text-sm flex items-center justify-center gap-1.5" title="Atualizar" aria-label="Atualizar">
          <i data-lucide="refresh-cw" class="w-4 h-4"></i>
          <span>Atualizar</span>
        </button>
        <button type="button" id="btn-novo-agendar" class="w-full md:w-auto px-4 py-2 bg-monday-blue text-white rounded-lg text-sm">Novo agendamento</button>
      </div>
    </div>
    <div class="mb-4 p-3 bg-gray-50 rounded-xl border border-gray-200">
      <span class="text-xs font-medium text-gray-600 block mb-2">Filtros</span>
      <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-4">
        <input type="search" id="filtro-paciente" value="${escapeAttribute(filterAgenda.paciente || '')}" placeholder="Paciente específico" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg min-w-0">
        <select id="filtro-especialidade" class="text-sm px-3 py-2 border border-gray-300 rounded-lg min-w-[140px]">
          ${buildEspecialidadeOptions(especialidades)}
        </select>
        <select id="filtro-profissional" class="text-sm px-3 py-2 border border-gray-300 rounded-lg min-w-[140px]">
          ${buildProfissionalOptions(profissionais)}
        </select>
        <label class="flex flex-col gap-1 text-sm">
          <span class="text-gray-600">Hora</span>
          <span class="flex items-center gap-1.5">
            <input type="time" id="filtro-hora-inicio" value="${filterAgenda.hora_inicio || ''}" class="w-full px-2 py-2 border border-gray-300 rounded-lg text-sm" title="Hora inicial (deixe vazio para qualquer)">
            <span class="text-gray-400">até</span>
            <input type="time" id="filtro-hora-fim" value="${filterAgenda.hora_fim || ''}" class="w-full px-2 py-2 border border-gray-300 rounded-lg text-sm" title="Hora final (deixe vazio para qualquer)">
          </span>
        </label>
        <label class="md:col-span-2 xl:col-span-2 flex flex-col gap-1 text-sm">
          <span class="text-gray-600">Período</span>
          <span class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <span class="flex items-center gap-2 w-full">
              <input type="date" id="filtro-data-inicio" value="${filterAgenda.start_date || viewRange.start}" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm" title="${escapeHtml(formatDateShort(filterAgenda.start_date || viewRange.start))}">
              <span class="inline-flex items-center gap-1 shrink-0">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span class="w-2 h-2 rounded-full bg-red-400"></span>
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
              </span>
            </span>
            <span class="hidden sm:inline text-gray-400">até</span>
            <span class="flex items-center gap-2 w-full">
              <input type="date" id="filtro-data-fim" value="${filterAgenda.end_date || viewRange.end}" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm" title="${escapeHtml(formatDateShort(filterAgenda.end_date || viewRange.end))}">
              <span class="inline-flex items-center gap-1 shrink-0">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span class="w-2 h-2 rounded-full bg-red-400"></span>
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
              </span>
            </span>
          </span>
        </label>
        <div class="md:col-span-2 xl:col-span-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
          <button type="button" id="btn-pesquisar-filtros" class="px-3 py-2 text-sm bg-monday-blue text-white rounded-lg">Pesquisar</button>
          <button type="button" id="btn-limpar-filtros" class="px-3 py-2 text-sm text-gray-600 hover:bg-gray-200 rounded-lg">Limpar</button>
        </div>
      </div>
      <div class="mt-2 flex flex-wrap gap-2 text-xs">
        ${buildStatusFilterButtons()}
      </div>
    </div>
  `;

  const eventosOrdenados = sortEventsByStart(eventos).filter((ev) => isStatusVisible(ev));
  const dayEvents = currentView === 'day'
    ? eventosOrdenados.filter((e) => {
        const inicio = String(e.inicio || e.data_consulta || '');
        return inicio.startsWith(currentDate) || (e.data_consulta && e.data_consulta === currentDate);
      })
    : eventosOrdenados;

  const slotMinStep = 20;
  const rowHeight = 2.2;
  const dayStartMins = 7 * 60;
  const dayEndMins = 22 * 60;
  const slotsCount = Math.ceil((dayEndMins - dayStartMins) / slotMinStep);
  const nowMins = (hoje.getHours() * 60) + hoje.getMinutes();
  const showCurrentTimeLine = currentView === 'day' && isHoje && nowMins >= dayStartMins && nowMins <= dayEndMins;

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
  const currentTimeTopPct = showCurrentTimeLine ? (((nowMins - dayStartMins) / slotMinStep) / slotsCount) * 100 : null;
  const eventsHtml = currentView === 'day'
    ? `<div class="bg-white rounded-xl shadow-monday overflow-hidden">
        <div class="px-4 py-2 border-b border-gray-200 flex flex-wrap gap-3 items-center text-xs">
          <span class="font-medium text-gray-600">Legenda:</span>
          ${LEGENDA_ESPECIALIDADES.map((e) => `<span class="flex items-center gap-1.5"><span class="w-4 h-4 rounded border-2 shrink-0" style="background:${e.cor.bg};border-color:${e.cor.border}"></span>${e.nome}</span>`).join('')}
        </div>
        <div class="flex border-t border-slate-100" style="min-height:${totalHeightRem}rem">
          <div class="w-16 shrink-0 border-r border-slate-100 flex flex-col bg-slate-50/70">
            ${Array.from({ length: slotsCount }, (_, i) => {
              const totalMins = dayStartMins + i * slotMinStep;
              const h = Math.floor(totalMins / 60);
              const m = totalMins % 60;
              const t = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`;
              const isFullHour = m === 0;
              return `<div class="px-2 shrink-0 border-b border-slate-100 ${isFullHour ? 'font-semibold text-slate-500' : 'text-slate-300'}" style="height:${rowHeight}rem">
                <span class="block pt-1 text-[11px] ${isFullHour ? '' : 'opacity-70'}">${t}</span>
              </div>`;
            }).join('')}
          </div>
          <div class="flex-1 relative min-w-0 agenda-events-area border-t border-slate-100 bg-slate-50/30" style="height:${totalHeightRem}rem; background-image: repeating-linear-gradient(to bottom, transparent 0, transparent calc(${rowHeight}rem - 1px), rgba(226,232,240,0.8) calc(${rowHeight}rem - 1px), rgba(226,232,240,0.8) ${rowHeight}rem)">
            ${showCurrentTimeLine ? `
              <div class="absolute left-0 right-0 z-10 px-3" style="top:${currentTimeTopPct}%;">
                <div class="relative flex items-center gap-3 -translate-y-1/2">
                  <div class="bg-primary text-white text-[10px] px-2 py-0.5 rounded-full font-bold shadow-sm">${String(hoje.getHours()).padStart(2, '0')}:${String(hoje.getMinutes()).padStart(2, '0')}</div>
                  <div class="flex-1 h-[2px] bg-primary/30 relative">
                    <div class="absolute -left-1 -top-[3px] w-2 h-2 rounded-full bg-primary"></div>
                  </div>
                </div>
              </div>
            ` : ''}
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
              const appearance = getStatusAppearance(ev);
              const isCancelada = (ev.status || '').toLowerCase().includes('cancelada');
              const tooltipText = buildEventTooltipData(ev);
              const horaInicio = fmtTime(ev.hora_inicio_prevista);
              const horaFim = fmtTime(`${String(Math.floor((ev.endMins % 1440) / 60)).padStart(2, '0')}:${String(ev.endMins % 60).padStart(2, '0')}`);
              const profissionalFoto = getProfissionalFotoUrl(ev);
              return `
              <div class="event-block absolute rounded-2xl cursor-pointer hover:opacity-95 hover:z-20 active:scale-[0.99] transition overflow-hidden shadow-sm ${isCancelada ? 'opacity-60' : ''}"
                data-id="${ev.id}"
                data-data-consulta="${ev.data_consulta || currentDate}"
                aria-label="${escapeAttribute(tooltipText)}"
                style="top:${topPct}%; height:${heightPct}%; left:${leftCalc}; width:${colWidth}; min-height:1.1rem; z-index:${ev.col}; background:${appearance.soft}; border-left:4px solid ${appearance.accent}">
                <div class="relative px-1.5 py-1 h-full overflow-hidden">
                  <img
                    src="${escapeHtml(profissionalFoto)}"
                    alt="Profissional"
                    class="absolute top-1 right-1 h-[18px] w-[18px] rounded-full object-cover border border-white/80 shadow-sm"
                    onerror="this.onerror=null;this.src='${escapeHtml(getFallbackFotoUrl())}';"
                  >
                  <div class="min-w-0 pr-6 overflow-hidden">
                    <span class="block truncate text-[10px] font-semibold leading-[1.1] text-slate-800">${escapeHtml(ev.paciente_nome || '')}${ev.duracao_minutos_prevista ? ` (${ev.duracao_minutos_prevista}min)` : ''}</span>
                    <span class="block truncate text-[8px] leading-[1.1] font-medium text-slate-500">${horaInicio} - ${horaFim}</span>
                  </div>
                </div>
              </div>`;
            }).join('')}
            ${dayEvents.length === 0 ? '<p class="absolute inset-0 flex items-center justify-center text-gray-500 text-sm">Nenhum evento para este dia.</p>' : ''}
          </div>
        </div>
      </div>`
    : currentView === 'week'
      ? renderAgendaWeekGrid(eventosOrdenados, viewRange, mapFeriados)
      : renderAgendaMonthGrid(eventosOrdenados, currentDate, mapFeriados, hasCustomRange ? range : null);
  const shouldShowFilteredList = currentView !== 'day' || range.start !== range.end;
  const filteredListHtml = shouldShowFilteredList ? `<div class="mt-4">${renderFilteredEventList(eventosOrdenados)}</div>` : '';

  container.innerHTML = navHtml + eventsHtml + filteredListHtml;

  if (!navDelegateAttached) {
    navDelegateAttached = true;
    container.addEventListener('click', (e) => {
      if (e.target.closest('#btn-prev')) {
        e.preventDefault();
        if (currentView === 'month') {
          const base = parseLocalDate(currentDate.slice(0, 8) + '01');
          base.setMonth(base.getMonth() - 1);
          currentDate = fmtDate(base);
        } else {
          const delta = currentView === 'week' ? -7 : -1;
          currentDate = addDaysToDateStr(currentDate, delta);
        }
        renderAgenda(container);
      } else if (e.target.closest('#btn-next')) {
        e.preventDefault();
        if (currentView === 'month') {
          const base = parseLocalDate(currentDate.slice(0, 8) + '01');
          base.setMonth(base.getMonth() + 1);
          currentDate = fmtDate(base);
        } else {
          const delta = currentView === 'week' ? 7 : 1;
          currentDate = addDaysToDateStr(currentDate, delta);
        }
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
      filterAgenda.start_date = novaData;
      filterAgenda.end_date = novaData;
      renderAgenda(container);
    });
  }
  const openPeriodPicker = (fieldId) => {
    const input = container.querySelector(`#${fieldId}`);
    const currentValue = input?.value || currentDate;
    openModalCalendarioData(currentValue, (novaData) => {
      if (input) input.value = novaData;
      if (fieldId === 'filtro-data-inicio') {
        const endInput = container.querySelector('#filtro-data-fim');
        if (endInput?.value && endInput.value < novaData) {
          endInput.value = novaData;
        }
      } else {
        const startInput = container.querySelector('#filtro-data-inicio');
        if (startInput?.value && startInput.value > novaData) {
          startInput.value = novaData;
        }
      }
    });
  };
  container.querySelector('#filtro-data-inicio')?.addEventListener('click', (e) => {
    e.preventDefault();
    openPeriodPicker('filtro-data-inicio');
  });
  container.querySelector('#filtro-data-fim')?.addEventListener('click', (e) => {
    e.preventDefault();
    openPeriodPicker('filtro-data-fim');
  });
  container.querySelector('#filtro-data-inicio')?.addEventListener('focus', (e) => {
    e.target.blur();
    openPeriodPicker('filtro-data-inicio');
  });
  container.querySelector('#filtro-data-fim')?.addEventListener('focus', (e) => {
    e.target.blur();
    openPeriodPicker('filtro-data-fim');
  });
  container.querySelectorAll('.view-btn').forEach((b) => {
    b.onclick = () => {
      currentView = b.dataset.view;
      renderAgenda(container);
    };
  });

  const applyFilters = () => {
    filterAgenda.especialidade_id = container.querySelector('#filtro-especialidade')?.value || '';
    filterAgenda.profissional_id = container.querySelector('#filtro-profissional')?.value ?? '';
    filterAgenda.paciente = container.querySelector('#filtro-paciente')?.value?.trim() || '';
    filterAgenda.start_date = container.querySelector('#filtro-data-inicio')?.value || '';
    filterAgenda.end_date = container.querySelector('#filtro-data-fim')?.value || '';
    filterAgenda.hora_inicio = container.querySelector('#filtro-hora-inicio')?.value || '';
    filterAgenda.hora_fim = container.querySelector('#filtro-hora-fim')?.value || '';
    if (filterAgenda.start_date) currentDate = filterAgenda.start_date;
    renderAgenda(container);
  };

  container.querySelector('#btn-pesquisar-filtros')?.addEventListener('click', () => applyFilters());

  container.querySelector('#btn-limpar-filtros')?.addEventListener('click', () => {
    filterAgenda = {
      especialidade_id: '',
      profissional_id: '',
      paciente: '',
      start_date: '',
      end_date: '',
      hora_inicio: '',
      hora_fim: '',
      status_keys: STATUS_FILTER_OPTIONS.map((item) => item.key)
    };
    renderAgenda(container);
  });

  container.querySelector('#btn-refresh')?.addEventListener('click', () => renderAgenda(container));

  container.querySelectorAll('.status-filter-btn').forEach((button) => {
    button.addEventListener('click', () => {
      const key = button.dataset.statusKey;
      const activeKeys = Array.isArray(filterAgenda.status_keys) && filterAgenda.status_keys.length
        ? [...filterAgenda.status_keys]
        : STATUS_FILTER_OPTIONS.map((item) => item.key);
      filterAgenda.status_keys = activeKeys.includes(key)
        ? activeKeys.filter((item) => item !== key)
        : [...activeKeys, key];
      renderAgenda(container, { useCachedData: true });
    });
  });

  attachCompactEventPreviews(container);
  container.querySelectorAll('.event-block').forEach((el) => {
    const dataConsulta = el.dataset.dataConsulta || currentDate;
    el.onclick = (event) => {
      if (el.dataset.previewLocked === '1') {
        event.preventDefault();
        delete el.dataset.previewLocked;
        return;
      }
      openModalEvento(parseInt(el.dataset.id, 10), container, dataConsulta);
    };
  });

  container.querySelector('#btn-novo-agendar').onclick = () => openModalAgendar(container, null);

  window.addEventListener('open-agendar', (e) => {
    const alunoId = e.detail?.alunoId;
    openModalAgendar(container, alunoId);
  }, { once: false });

  void hydrateAgendaAuxUi(container, context);

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
  const [pacientePreselected, especialidades, tiposConsulta, profissionais] = await Promise.all([
    alunoIdPreselected ? getPaciente(alunoIdPreselected).catch(() => null) : Promise.resolve(null),
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
          <input type="hidden" name="aluno_id" value="${pacientePreselected?.IdUsuario || alunoIdPreselected || ''}">
          <div class="relative">
            <input
              type="search"
              id="agendar-paciente-search"
              value="${escapeHtml(String(pacientePreselected?.Nome || ''))}"
              placeholder="Digite nome, CPF ou contato..."
              class="w-full px-4 py-2 border border-gray-300 rounded-lg"
              autocomplete="off"
              required
            >
            <div id="agendar-paciente-results" class="hidden absolute z-20 mt-2 w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg"></div>
          </div>
          <p id="agendar-paciente-feedback" class="mt-2 text-xs text-gray-500">
            ${pacientePreselected ? escapeHtml(formatPacienteMeta(pacientePreselected)) : 'Busque um paciente para selecionar o agendamento.'}
          </p>
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
  setupPacienteAutocomplete(modal, pacientePreselected);

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
    const alunoId = parseInt(fd.get('aluno_id'), 10);
    if (!Number.isFinite(alunoId) || alunoId <= 0) {
      throw new Error('Selecione um paciente válido antes de agendar.');
    }
    const data = {
      aluno_id: alunoId,
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
    await renderAgenda(container);
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
        await renderAgenda(container);
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
        await renderAgenda(container);
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
        await renderAgenda(container);
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

function setupPacienteAutocomplete(modal, pacienteInicial) {
  const input = modal.querySelector('#agendar-paciente-search');
  const hiddenInput = modal.querySelector('input[name="aluno_id"]');
  const resultsEl = modal.querySelector('#agendar-paciente-results');
  const feedbackEl = modal.querySelector('#agendar-paciente-feedback');
  if (!input || !hiddenInput || !resultsEl || !feedbackEl) return;

  let latestToken = 0;

  const clearResults = () => {
    resultsEl.innerHTML = '';
    resultsEl.classList.add('hidden');
  };

  const setSelectedPaciente = (paciente) => {
    hiddenInput.value = String(paciente?.IdUsuario || '');
    input.value = String(paciente?.Nome || '');
    feedbackEl.textContent = paciente ? formatPacienteMeta(paciente) : 'Busque um paciente para selecionar o agendamento.';
    clearResults();
  };

  if (pacienteInicial) {
    setSelectedPaciente(pacienteInicial);
  }

  const runSearch = async () => {
    const term = input.value.trim();

    if (term === '') {
      feedbackEl.textContent = 'Busque um paciente para selecionar o agendamento.';
      clearResults();
      return;
    }

    resultsEl.classList.remove('hidden');
    resultsEl.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500">Buscando pacientes...</div>';

    const token = ++latestToken;
    try {
      const { pacientes } = await getPacientes(term, { limit: 4 });
      if (token !== latestToken) return;

      if (!pacientes.length) {
        resultsEl.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500">Nenhum paciente encontrado.</div>';
        feedbackEl.textContent = 'Refine o termo para localizar o paciente correto.';
        return;
      }

      resultsEl.innerHTML = pacientes.map((paciente) => `
        <button
          type="button"
          class="agendar-paciente-option flex w-full items-start gap-3 px-4 py-3 text-left hover:bg-slate-50 transition border-b border-gray-100 last:border-b-0"
          data-id="${escapeAttribute(String(paciente.IdUsuario || ''))}"
          data-nome="${escapeAttribute(String(paciente.Nome || ''))}"
          data-cpf="${escapeAttribute(String(paciente.CPF || ''))}"
          data-telefone="${escapeAttribute(String(paciente.WhatsApp || paciente.Telefone || ''))}"
          data-email="${escapeAttribute(String(paciente.Email || ''))}"
        >
          <span class="mt-1 h-2.5 w-2.5 rounded-full bg-monday-blue shrink-0"></span>
          <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-medium text-gray-800">${escapeHtml(String(paciente.Nome || ''))}</span>
            <span class="block truncate text-xs text-gray-500">${escapeHtml(formatPacienteMeta(paciente))}</span>
          </span>
        </button>
      `).join('');

      resultsEl.querySelectorAll('.agendar-paciente-option').forEach((button) => {
        button.onclick = () => {
          setSelectedPaciente({
            IdUsuario: button.dataset.id,
            Nome: button.dataset.nome,
            CPF: button.dataset.cpf,
            WhatsApp: button.dataset.telefone,
            Email: button.dataset.email,
          });
        };
      });
    } catch (err) {
      if (token !== latestToken) return;
      resultsEl.innerHTML = '<div class="px-4 py-3 text-sm text-red-500">Erro ao buscar pacientes.</div>';
      feedbackEl.textContent = err.message || 'Não foi possível carregar os pacientes.';
    }
  };

  const debouncedSearch = debounce(runSearch, 250);
  input.addEventListener('input', () => {
    hiddenInput.value = '';
    feedbackEl.textContent = 'Selecione um paciente da lista para concluir o agendamento.';
    debouncedSearch();
  });
  input.addEventListener('focus', () => {
    if (input.value.trim() !== '' && hiddenInput.value === '') {
      runSearch();
    }
  });
  input.addEventListener('blur', () => {
    setTimeout(() => clearResults(), 150);
  });
}

function formatPacienteMeta(paciente) {
  const partes = [];
  const cpf = String(paciente?.CPF || '').trim();
  const contato = String(paciente?.WhatsApp || paciente?.Telefone || '').trim();
  const email = String(paciente?.Email || '').trim();

  if (cpf) partes.push(`CPF: ${cpf}`);
  if (contato) partes.push(`Contato: ${contato}`);
  if (email) partes.push(email);

  return partes.join(' • ') || 'Paciente sem dados complementares.';
}

function escapeHtml(s) {
  const d = document.createElement('div');
  d.textContent = s || '';
  return d.innerHTML;
}

function escapeAttribute(s) {
  return escapeHtml(s).replace(/"/g, '&quot;');
}

function debounce(fn, ms) {
  let timer = null;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), ms);
  };
}
