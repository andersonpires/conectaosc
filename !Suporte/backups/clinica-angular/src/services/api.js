const API_BASE = './api';

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

export async function getPacientes(search = '') {
  const q = search ? `?search=${encodeURIComponent(search)}` : '';
  const res = await fetch(`${API_BASE}/pacientes${q}`, { credentials: 'include' });
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
  if (filters.hora_inicio) params.set('hora_inicio', filters.hora_inicio);
  if (filters.hora_fim) params.set('hora_fim', filters.hora_fim);
  const res = await fetch(`${API_BASE}/agenda?${params}`, { credentials: 'include' });
  const j = await handleResponse(res);
  return j.data;
}

export async function getProntuario(id) {
  const res = await fetch(`${API_BASE}/prontuarios/${id}`, { credentials: 'include' });
  const j = await handleResponse(res);
  return j.data.prontuario;
}

export async function getProntuarios(alunoId) {
  const q = alunoId ? `?aluno_id=${alunoId}` : '';
  const res = await fetch(`${API_BASE}/prontuarios${q}`, { credentials: 'include' });
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

export function getProntuarioPdfUrl(id, assinar = false) {
  const url = `${API_BASE}/prontuarios/${id}/pdf`;
  return assinar ? `${url}?assinar=1` : url;
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
