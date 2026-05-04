const API_BASE = './api';
const LEGACY_API_BASE = '../api/v1';

async function handleResponse(res) {
  const text = await res.text();
  if (!text || text.trim() === '') {
    throw new Error(
      res.status === 401 ? 'Sessão expirada. Faça login no ConectaOSC e acesse o App Clínica novamente.'
      : `Resposta vazia da API (status ${res.status}). Verifique se está logado e se o servidor está correto.`
    );
  }
  let json;
  try {
    json = JSON.parse(text);
  } catch (e) {
    const preview = text.substring(0, 150).replace(/<[^>]+>/g, '');
    throw new Error(
      res.status === 404 ? 'Rota da API não encontrada. Verifique se o mod_rewrite está ativo no Apache.'
      : `A API retornou conteúdo inválido (HTML/erro?). Início: ${preview}...`
    );
  }
  if (!res.ok) {
    const msg = json.message || json.errors?.join('; ') || `Erro ${res.status}`;
    throw new Error(msg);
  }
  return json;
}

export async function getPacientes(search = '', options = {}) {
  const params = new URLSearchParams();
  if (search) params.set('search', search);
  if (options.limit) params.set('limit', String(options.limit));
  const query = params.toString();
  const res = await fetch(`${API_BASE}/pacientes${query ? `?${query}` : ''}`, { credentials: 'include' });
  const j = await handleResponse(res);
  return j.data;
}

export async function getPaciente(id) {
  const res = await fetch(`${API_BASE}/pacientes/${id}`, { credentials: 'include' });
  const j = await handleResponse(res);
  return j.data.paciente;
}

export async function getEspecialidades() {
  const res = await fetch(`${API_BASE}/especialidades`, { credentials: 'include' });
  const j = await handleResponse(res);
  return j.data.especialidades;
}

export async function getTiposConsulta() {
  const res = await fetch(`${API_BASE}/tipos-consulta`, { credentials: 'include' });
  const j = await handleResponse(res);
  return j.data.tipos_consulta;
}

export async function getProfissionais() {
  const res = await fetch(`${API_BASE}/profissionais`, { credentials: 'include' });
  const j = await handleResponse(res);
  return j.data.profissionais;
}

export async function postConsulta(data) {
  const res = await fetch(`${API_BASE}/consultas`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(data),
  });
  const j = await handleResponse(res);
  return j.data;
}

export async function putConsulta(id, data) {
  const res = await fetch(`${API_BASE}/consultas/${id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(data),
  });
  await handleResponse(res);
}

export async function getConsulta(id) {
  const res = await fetch(`${API_BASE}/consultas/${id}`, { credentials: 'include' });
  const j = await handleResponse(res);
  return j.data.consulta;
}

export async function postConfirmacao(id) {
  const res = await fetch(`${API_BASE}/consultas/${id}/confirmacao`, { method: 'POST', credentials: 'include' });
  await handleResponse(res);
}

export async function postCancelar(id) {
  const res = await fetch(`${API_BASE}/consultas/${id}/cancelar`, { method: 'POST', credentials: 'include' });
  await handleResponse(res);
}

export async function postExcluirConsulta(id) {
  const res = await fetch(`${API_BASE}/consultas/${id}/excluir`, { method: 'POST', credentials: 'include' });
  await handleResponse(res);
}

export async function postIniciarAtendimento(id) {
  const res = await fetch(`${API_BASE}/consultas/${id}/iniciar-atendimento`, { method: 'POST', credentials: 'include' });
  await handleResponse(res);
}

export async function postReverterAtendimento(id) {
  const res = await fetch(`${API_BASE}/consultas/${id}/reverter-atendimento`, { method: 'POST', credentials: 'include' });
  await handleResponse(res);
}

export async function getConsultasEmAtendimento() {
  const res = await fetch(`${API_BASE}/agenda/em-atendimento`, { credentials: 'include' });
  const j = await handleResponse(res);
  return j.data.consultas;
}

export async function getConsultasParaAtendimento(date) {
  const d = date || new Date().toISOString().slice(0, 10);
  const res = await fetch(`${API_BASE}/agenda/consultas-atendimento?date=${d}`, { credentials: 'include' });
  const j = await handleResponse(res);
  return j.data.consultas;
}

export async function getConsultasAguardandoProntuario() {
  const res = await fetch(`${API_BASE}/agenda/consultas-aguardando-prontuario`, { credentials: 'include' });
  const j = await handleResponse(res);
  return j.data.consultas;
}

export async function getFeriadosVerificar(datas) {
  const arr = Array.isArray(datas) ? datas : [datas];
  const str = arr.filter(Boolean).join(',');
  if (!str) return { feriados: {} };
  const res = await fetch(`${API_BASE}/feriados/verificar?datas=${encodeURIComponent(str)}`, { credentials: 'include' });
  const j = await handleResponse(res);
  return j.data;
}

export async function getDiasComAgendamento(mes) {
  const res = await fetch(`${API_BASE}/agenda/dias-com-agendamento?mes=${encodeURIComponent(mes)}`, { credentials: 'include' });
  const j = await handleResponse(res);
  return j.data;
}

export async function getAgenda(view, date, filters = {}) {
  const params = new URLSearchParams({ view, date });
  if (filters.especialidade_id) params.set('especialidade_id', filters.especialidade_id);
  if (filters.profissional_id !== undefined && filters.profissional_id !== '') params.set('profissional_id', filters.profissional_id);
  if (filters.start_date) params.set('start_date', filters.start_date);
  if (filters.end_date) params.set('end_date', filters.end_date);
  if (filters.paciente) params.set('paciente', filters.paciente);
  if (filters.hora_inicio) params.set('hora_inicio', filters.hora_inicio);
  if (filters.hora_fim) params.set('hora_fim', filters.hora_fim);
  params.set('_ts', String(Date.now()));
  const res = await fetch(`${API_BASE}/agenda?${params}`, { credentials: 'include', cache: 'no-store' });
  const j = await handleResponse(res);
  return j.data;
}

export async function getProntuario(id) {
  const res = await fetch(`${API_BASE}/prontuarios/${id}`, { credentials: 'include' });
  const j = await handleResponse(res);
  return j.data.prontuario;
}

export async function getProntuarios(filters = {}) {
  const params = new URLSearchParams();
  if (filters.aluno_id) params.set('aluno_id', String(filters.aluno_id));
  if (filters.paciente_nome) params.set('paciente_nome', String(filters.paciente_nome));
  if (filters.data) params.set('data', String(filters.data));
  if (filters.hora_inicio) params.set('hora_inicio', String(filters.hora_inicio));
  if (filters.hora_fim) params.set('hora_fim', String(filters.hora_fim));
  if (filters.profissional_id) params.set('profissional_id', String(filters.profissional_id));
  const query = params.toString();
  const res = await fetch(`${API_BASE}/prontuarios${query ? `?${query}` : ''}`, { credentials: 'include' });
  const j = await handleResponse(res);
  return j.data;
}

export async function getPacientesComProntuario(search = '') {
  const q = search ? `?search=${encodeURIComponent(search)}` : '';
  const res = await fetch(`${API_BASE}/prontuarios/pacientes${q}`, { credentials: 'include' });
  const j = await handleResponse(res);
  return j.data.pacientes || [];
}

export async function postProntuarioGerarIa(data) {
  const res = await fetch(`${API_BASE}/prontuarios/gerar-ia`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(data),
  });
  const j = await handleResponse(res);
  return j.data;
}

/**
 * Gera prontuário com IA via streaming (SSE).
 * @param {Object} data - { consulta_id, aluno_id, observacoes_adicionais }
 * @param {Object} callbacks - { onDelta(chunk, fullText), onDone(fullText), onError(msg) }
 */
export function postProntuarioStreamIa(data, callbacks = {}) {
  const { onDelta = () => {}, onDone = () => {}, onError = () => {} } = callbacks;
  return fetch(`${API_BASE}/prontuarios/stream-ia`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(data),
  }).then(async (res) => {
    if (!res.ok) {
      const text = await res.text();
      let msg = `Erro ${res.status}`;
      try {
        const j = JSON.parse(text);
        msg = j.message || msg;
      } catch {}
      onError(msg);
      throw new Error(msg);
    }
    const reader = res.body.getReader();
    const decoder = new TextDecoder();
    let fullText = '';
    let buffer = '';
    let currentEvent = '';
    let dataLines = [];
    let hasReceivedDone = false;
    const flushEvent = () => {
      const dataStr = dataLines.join('\n');
      if (currentEvent === 'delta' && dataStr) {
        fullText += dataStr;
        onDelta(dataStr, fullText);
      } else if (currentEvent === 'done' && dataStr) {
        fullText = dataStr;
        hasReceivedDone = true;
        onDone(fullText);
      } else if (currentEvent === 'error') {
        onError(dataStr);
        throw new Error(dataStr);
      }
      dataLines = [];
      currentEvent = '';
    };
    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      buffer += decoder.decode(value, { stream: true });
      const lines = buffer.split('\n');
      buffer = lines.pop() || '';
      for (const line of lines) {
        if (line.startsWith('event:')) {
          if (currentEvent) flushEvent();
          currentEvent = line.slice(6).trim();
        } else if (line.startsWith('data:')) {
          dataLines.push(line.slice(5).replace(/^ /, ''));
        } else if (line === '' && currentEvent) {
          flushEvent();
        }
      }
    }
    if (currentEvent) flushEvent();
    if (!hasReceivedDone && fullText) onDone(fullText);
    return fullText;
  });
}

export async function postProntuario(data) {
  const res = await fetch(`${API_BASE}/prontuarios`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(data),
  });
  const j = await handleResponse(res);
  return j.data;
}

export async function putProntuario(id, data) {
  const res = await fetch(`${API_BASE}/prontuarios/${id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(data),
  });
  await handleResponse(res);
}

export async function deleteProntuario(id) {
  const res = await fetch(`${API_BASE}/prontuarios/${id}`, { method: 'DELETE', credentials: 'include' });
  await handleResponse(res);
}

export function getProntuarioPdfEndpoint() {
  return `${API_BASE}/prontuarios/pdf`;
}

export async function getAnamneseByConsulta(consultaId) {
  const res = await fetch(`${API_BASE}/anamnese?consulta_id=${consultaId}`, { credentials: 'include' });
  const j = await handleResponse(res);
  const list = j.data.anamneses || [];
  return list.length > 0 ? list[0] : null;
}

export async function getAnamnesesByAluno(alunoId) {
  const res = await fetch(`${API_BASE}/anamnese?aluno_id=${alunoId}`, { credentials: 'include' });
  const j = await handleResponse(res);
  return j.data.anamneses || [];
}

async function postLegacy(path, payload = {}) {
  const body = new URLSearchParams();
  Object.entries(payload).forEach(([key, value]) => {
    if (value === undefined || value === null) return;
    body.append(key, String(value));
  });

  const res = await fetch(`${LEGACY_API_BASE}${path}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    credentials: 'include',
    body: body.toString(),
  });
  const j = await handleResponse(res);
  return j.data;
}

export async function getCursosFiltroProntuarios(somenteAtivos = 1) {
  const data = await postLegacy('/relatorios/cursos/options', { somenteAtivos });
  if (!Array.isArray(data)) return [];
  return data
    .map((item) => ({
      value: String(item?.value ?? '').trim(),
      label: String(item?.label ?? '').trim(),
    }))
    .filter((item) => {
      const id = parseInt(item.value, 10);
      return Number.isInteger(id) && id > 0;
    });
}

export async function getTurmasFiltroProntuarios(cursoId, somenteAtivos = 1) {
  const idCurso = parseInt(cursoId, 10);
  if (!Number.isInteger(idCurso) || idCurso <= 0) return [];
  const data = await postLegacy('/relatorios/turmas/options', {
    IdCurso: idCurso,
    somenteAtivos,
    todasTurmas: 0,
  });
  if (!Array.isArray(data)) return [];
  return data
    .map((item) => ({
      value: String(item?.value ?? '').trim(),
      label: String(item?.label ?? '').trim(),
    }))
    .filter((item) => {
      const id = parseInt(item.value, 10);
      return Number.isInteger(id) && id > 0;
    });
}

export async function getBeneficiariosFiltroProntuarios(cursoId, turmaId) {
  const idCurso = parseInt(cursoId, 10);
  const idTurma = String(turmaId || '').trim();
  if (!Number.isInteger(idCurso) || idCurso <= 0 || idTurma === '') return [];

  const data = await postLegacy('/relatorios/matriculados', {
    curso: idCurso,
    turma: idTurma,
  });
  if (!Array.isArray(data)) return [];

  const vistos = new Set();
  return data
    .map((item) => {
      const id = parseInt(item?.IdUsuario, 10);
      const nome = String(item?.Nome ?? '').trim();
      return Number.isInteger(id) && id > 0 && nome ? { id, nome } : null;
    })
    .filter((item) => {
      if (!item) return false;
      if (vistos.has(item.id)) return false;
      vistos.add(item.id);
      return true;
    });
}

export async function postAnamnese(data) {
  const res = await fetch(`${API_BASE}/anamnese`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(data),
  });
  const j = await handleResponse(res);
  return j.data;
}

export async function putAnamnese(id, data) {
  const res = await fetch(`${API_BASE}/anamnese/${id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(data),
  });
  await handleResponse(res);
}
