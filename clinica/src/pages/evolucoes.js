import { getCurrentClinicaUser, getEvolucoes, getEvolucao, postEvolucao, putEvolucao, getPaciente, getConsulta, getPacientes, getProfissionais, getEspecialidades } from '../services/api.js';
import { getSpinnerHtml } from '../utils/loading.js';

const TEMPLATE_INICIAL = `Dados observados durante o atendimento:

Queixas ou temas principais abordados:

Estado cognitivo:

Estado de humor:

Aspectos sociais:

Impressões do profissional:

Condutas adotadas:

Encaminhamentos ou orientações:

Postura/plano para próxima consulta:`;

function escapeHtml(value) {
  const div = document.createElement('div');
  div.textContent = value ?? '';
  return div.innerHTML;
}

function fmtDate(value) {
  if (!value) return '';
  return new Date(`${value}T00:00:00`).toLocaleDateString('pt-BR');
}

function resumoAtendimento(item) {
  const partes = [];
  if (item.consulta_status) partes.push(`Status: ${item.consulta_status}`);
  if (item.tem_anamnese_adulto) partes.push('Anamnese adulto');
  if (item.tem_anamnese_infantojuvenil) partes.push('Roteiro infantojuvenil');
  if (item.tem_prontuario) partes.push('Prontuario');
  if (item.tem_evolucao) partes.push('Evolucao');
  if (item.conteudo) partes.push(String(item.conteudo).replace(/<[^>]+>/g, '').slice(0, 120));
  return partes.join(' | ') || 'Consulta sem registro clinico vinculado';
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

async function openDetalheModal(id, canEdit, onSaved) {
  const user = getCurrentClinicaUser();
  const evolucao = await getEvolucao(id);
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4';
  modal.innerHTML = `
    <div class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h3 class="text-xl font-semibold text-slate-900">${escapeHtml(evolucao.paciente_nome || 'Evolução clínica')}</h3>
          <p class="text-sm text-slate-500">${fmtDate(evolucao.data_evolucao)} às ${escapeHtml(String(evolucao.hora_evolucao || '').slice(0, 5))} • ${escapeHtml(evolucao.profissional_nome || '')}</p>
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
    window.open(`./pdf_evolucao.php?id=${evolucao.id}`, '_blank', 'noopener');
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
          <h3 class="text-xl font-semibold text-slate-900">Nova evolução clínica</h3>
          <p class="text-sm text-slate-500">${escapeHtml(paciente?.Nome || '')}</p>
        </div>
        <button type="button" class="btn-fechar rounded-xl border border-slate-300 px-4 py-2">Fechar</button>
      </div>
      <div class="mt-4 grid gap-3 md:grid-cols-2">
        <input type="date" id="evolucao-data" class="rounded-xl border border-slate-300 px-4 py-3" value="${escapeHtml(consulta?.data_consulta || new Date().toISOString().slice(0, 10))}">
        <input type="time" id="evolucao-hora" class="rounded-xl border border-slate-300 px-4 py-3" value="${escapeHtml(String(consulta?.hora_inicio_prevista || '08:00').slice(0, 5))}">
      </div>
      <div class="mt-4">
        <textarea id="evolucao-conteudo" class="w-full rounded-2xl border border-slate-300 px-4 py-3">${escapeHtml(TEMPLATE_INICIAL)}</textarea>
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
    container.innerHTML = '<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">Acesso restrito a profissionais de saúde.</div>';
    return;
  }

  container.innerHTML = getSpinnerHtml('Carregando evoluções...');
  const alunoIdSelecionado = parseInt(opts.aluno_id || '', 10);
  const temPacienteSelecionado = Number.isFinite(alunoIdSelecionado) && alunoIdSelecionado > 0;
  const [profissionais, especialidades, pacienteSelecionado, evolucoes] = await Promise.all([
    getProfissionais().catch(() => []),
    getEspecialidades().catch(() => []),
    temPacienteSelecionado ? getPaciente(alunoIdSelecionado).catch(() => null) : Promise.resolve(null),
    temPacienteSelecionado ? getEvolucoes(opts).catch(() => []) : Promise.resolve([]),
  ]);

  container.innerHTML = `
    <section class="space-y-4">
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h2 class="text-xl font-semibold text-slate-900">Evoluções clínicas</h2>
          <p class="text-sm text-slate-500">Consulte o histórico de consultas, anamneses, prontuários e evoluções do paciente.</p>
        </div>
        <button type="button" id="btn-nova-evolucao" class="rounded-xl bg-monday-blue px-4 py-2 text-sm font-medium text-white" ${temPacienteSelecionado ? '' : 'disabled'}>Nova evolução</button>
      </div>
      <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-4">
        <div class="relative md:col-span-4">
          <label class="mb-1 block text-sm font-medium text-slate-700">Beneficiário</label>
          <input type="hidden" id="filtro-aluno" value="${escapeHtml(opts.aluno_id || '')}">
          <input type="search" id="filtro-paciente-busca" class="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="Digite nome, CPF ou contato do beneficiário" value="${escapeHtml(pacienteSelecionado?.Nome || '')}" autocomplete="off">
          <div id="filtro-paciente-resultados" class="absolute z-30 mt-2 hidden max-h-72 w-full overflow-auto rounded-2xl border border-slate-200 bg-white shadow-xl"></div>
          <p id="filtro-paciente-meta" class="mt-2 text-xs text-slate-500">${pacienteSelecionado ? escapeHtml(pacienteMeta(pacienteSelecionado)) : 'Selecione um beneficiário para carregar o histórico.'}</p>
        </div>
        <input type="date" id="filtro-data" class="rounded-xl border border-slate-300 px-4 py-3" value="${escapeHtml(opts.data || '')}">
        <select id="filtro-profissional" class="rounded-xl border border-slate-300 px-4 py-3">
          <option value="">Todos profissionais</option>
          ${profissionais.map((item) => `<option value="${item.id}" ${String(opts.profissional_id || '') === String(item.id) ? 'selected' : ''}>${escapeHtml(item.nome)}</option>`).join('')}
        </select>
        <select id="filtro-especialidade" class="rounded-xl border border-slate-300 px-4 py-3">
          <option value="">Todas especialidades</option>
          ${especialidades.map((item) => `<option value="${item.id}" ${String(opts.especialidade_id || '') === String(item.id) ? 'selected' : ''}>${escapeHtml(item.nome)}</option>`).join('')}
        </select>
      </div>
      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Paciente</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Data</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Profissional</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Tipo/Status</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Resumo</th>
              <th class="px-4 py-3 text-right font-semibold text-slate-600">Ações</th>
            </tr>
          </thead>
          <tbody>
            ${!temPacienteSelecionado ? '<tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Busque e selecione um beneficiário para listar o histórico.</td></tr>' : evolucoes.length === 0 ? '<tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Nenhum atendimento encontrado para este beneficiário.</td></tr>' : evolucoes.map((item) => `
              <tr class="border-t border-slate-100">
                <td class="px-4 py-3">${escapeHtml(item.paciente_nome || '')}</td>
                <td class="px-4 py-3">${fmtDate(item.data_evolucao)} ${escapeHtml(String(item.hora_evolucao || '').slice(0, 5))}</td>
                <td class="px-4 py-3">${escapeHtml(item.profissional_nome || '')}</td>
                <td class="px-4 py-3">${escapeHtml(item.tipo_nome || item.registro_tipo || 'Consulta')}</td>
                <td class="px-4 py-3">${escapeHtml(resumoAtendimento(item))}</td>
                <td class="px-4 py-3 text-right">
                  <div class="flex flex-wrap justify-end gap-1">
                    ${item.tem_evolucao ? `<button type="button" class="btn-ver-evolucao rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700" data-id="${item.evolucao_id}" data-autor="${item.profissional_id}">Evolução</button>` : ''}
                    ${(item.tem_anamnese_adulto || item.tem_anamnese_infantojuvenil) ? `<button type="button" class="btn-ver-anamnese rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700" data-consulta-id="${item.consulta_id}" data-aluno-id="${item.aluno_id}">Anamnese</button>` : ''}
                    ${item.tem_prontuario ? `<button type="button" class="btn-ver-prontuario rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700" data-aluno-id="${item.aluno_id}">Prontuário</button>` : ''}
                    ${!item.tem_evolucao && !item.tem_anamnese_adulto && !item.tem_anamnese_infantojuvenil && !item.tem_prontuario ? '<span class="text-xs text-slate-400">Sem registros</span>' : ''}
                  </div>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
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
  let pacienteSearchTimer = null;

  const esconderResultadosPaciente = () => {
    if (pacienteResultadosEl) {
      pacienteResultadosEl.classList.add('hidden');
      pacienteResultadosEl.innerHTML = '';
    }
  };

  pacienteBuscaEl?.addEventListener('input', () => {
    const term = String(pacienteBuscaEl.value || '').trim();
    if (pacienteIdEl) pacienteIdEl.value = '';
    if (pacienteMetaEl) pacienteMetaEl.textContent = 'Selecione um beneficiário para carregar o histórico.';
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
          pacienteResultadosEl.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">Nenhum beneficiário encontrado.</div>';
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

  document.addEventListener('click', (event) => {
    if (!container.contains(event.target)) esconderResultadosPaciente();
  }, { once: true });

  container.querySelectorAll('#filtro-data, #filtro-profissional, #filtro-especialidade').forEach((el) => {
    el.addEventListener('change', () => reload());
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
