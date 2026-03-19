import {
  getProntuarios,
  getProntuario,
  putProntuario,
  deleteProntuario,
  getProntuarioPdfUrl,
  getConsultasAguardandoProntuario,
  getPacientesComProntuario,
  postProntuarioStreamIa,
  postProntuario
} from '../services/api.js';
import { markdownToHtml } from '../utils/markdownToHtml.js';
import { getSpinnerHtml, getButtonSpinnerHtml, showLoadingOverlay, hideLoadingOverlay } from '../utils/loading.js';

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

function openModalAssinarPdf(prontuarioId) {
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4';
  modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-monday-lg max-w-sm w-full p-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-3">Gerar PDF do prontuário</h3>
      <p class="text-gray-600 mb-5">Você deseja que o prontuário seja assinado digitalmente?</p>
      <div class="flex flex-col gap-2">
        <button type="button" class="btn-assinar-sim min-h-touch py-3 px-4 bg-monday-blue text-white rounded-xl font-medium">Sim, assinar</button>
        <button type="button" class="btn-assinar-nao min-h-touch py-3 px-4 border border-gray-300 rounded-xl font-medium hover:bg-gray-50">Não, assinarei manualmente</button>
      </div>
    </div>
  `;
  modal.querySelector('.btn-assinar-sim').onclick = () => {
    modal.remove();
    window.open(getProntuarioPdfUrl(prontuarioId, true), '_blank');
  };
  modal.querySelector('.btn-assinar-nao').onclick = () => {
    modal.remove();
    window.open(getProntuarioPdfUrl(prontuarioId, false), '_blank');
  };
  modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
  document.body.appendChild(modal);
}

let prontuariosFiltroPaciente = null;

export async function renderProntuarios(container, opts = {}) {
  if (opts?.aluno_id !== undefined) prontuariosFiltroPaciente = opts.aluno_id ? parseInt(opts.aluno_id, 10) : null;
  const alunoIdFiltro = prontuariosFiltroPaciente;
  container.innerHTML = getSpinnerHtml('Carregando...');
  let prontuarios = [];
  let consultasAguardando = [];
  try {
    const [prontRes, aguardandoRes] = await Promise.all([
      getProntuarios(alunoIdFiltro),
      getConsultasAguardandoProntuario().catch(() => [])
    ]);
    prontuarios = prontRes?.prontuarios ?? [];
    consultasAguardando = Array.isArray(aguardandoRes) ? aguardandoRes : [];
  } catch (e) {
    console.error('Erro ao carregar prontuários:', e);
  }

  let pacientesFiltro = [];
  try {
    pacientesFiltro = await getPacientesComProntuario();
  } catch (e) {
    console.warn('Não foi possível carregar filtro de pacientes:', e);
  }

  const filtroSelectHtml = pacientesFiltro.length > 0 ? `
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">Filtrar por paciente</label>
      <select id="filtro-paciente-pront" class="w-full md:w-64 px-3 py-2 border border-gray-300 rounded-lg text-sm">
        <option value="">Todos os pacientes</option>
        ${pacientesFiltro.map((pac) => `<option value="${pac.aluno_id}" ${alunoIdFiltro === pac.aluno_id ? 'selected' : ''}>${escapeHtml(pac.paciente_nome || '')}</option>`).join('')}
      </select>
    </div>
  ` : '';

  const tabHtml = `
    <h2 class="text-lg font-semibold text-gray-800 mb-3">Prontuários</h2>
    ${filtroSelectHtml}
    <div class="flex flex-nowrap gap-2 border-b-2 border-gray-200 mb-4 overflow-x-auto">
      <button type="button" data-tab="prontuarios" class="tab-pront shrink-0 px-4 py-3 font-medium text-monday-blue border-b-2 border-monday-blue -mb-0.5 min-h-touch">Prontuários salvos</button>
      <button type="button" data-tab="aguardando" class="tab-pront shrink-0 px-4 py-3 font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 min-h-touch flex items-center gap-1">Aguardando prontuário <span class="text-xs px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-semibold">${consultasAguardando.length}</span></button>
    </div>
  `;

  const prontuariosHtml = prontuarios.length === 0
    ? '<p class="text-gray-500 py-8 text-center">Nenhum prontuário registrado.</p>'
    : `
    <div class="space-y-3" id="pront-list">
      ${prontuarios.map((p) => {
        const dt = p.created_at ? new Date(p.created_at).toLocaleString('pt-BR') : '';
        const canEdit = p.can_edit === true;
        return `
        <div class="bg-white rounded-xl shadow-monday p-4">
          <div class="flex justify-between items-start">
            <div>
              <p class="font-medium">${escapeHtml(String(p.paciente_nome || ''))}</p>
              <p class="text-sm text-gray-500">${dt} - ${escapeHtml(String(p.profissional_nome || ''))}</p>
            </div>
            <div class="flex gap-2">
              ${canEdit ? `<button type="button" data-id="${p.id}" class="btn-editar min-w-touch min-h-touch p-2 rounded-lg bg-blue-50 hover:bg-blue-100 text-monday-blue" title="Editar"><i data-lucide="edit-3" class="w-5 h-5"></i></button>` : ''}
              <button type="button" data-id="${p.id}" class="btn-pdf min-w-touch min-h-touch p-2 rounded-lg bg-gray-100 hover:bg-gray-200" title="Imprimir PDF"><i data-lucide="file-down" class="w-5 h-5"></i></button>
              ${canEdit ? `<button type="button" data-id="${p.id}" class="btn-excluir min-w-touch min-h-touch p-2 rounded-lg bg-red-50 hover:bg-red-100 text-red-600" title="Excluir"><i data-lucide="trash-2" class="w-5 h-5"></i></a>` : ''}
            </div>
          </div>
        </div>
      `;
      }).join('')}
    </div>
  `;

  const aguardandoHtml = consultasAguardando.length === 0
    ? '<p class="text-gray-500 py-8 text-center">Nenhuma consulta aguardando prontuário.</p>'
    : `
    <div class="space-y-3" id="aguardando-list">
      <p class="text-sm text-gray-600 mb-3">Selecione uma consulta para gerar o prontuário com IA com base nos modelos do sistema.</p>
      ${consultasAguardando.map((c) => `
        <button type="button" data-consulta-id="${c.id}" data-aluno-id="${c.aluno_id}" data-paciente-nome="${escapeHtml(c.paciente_nome || '')}" data-data="${c.data_consulta || ''}" data-hora="${fmtTime(c.hora_inicio_prevista)}" data-especialidade="${escapeHtml(c.especialidade_nome || '')}" data-profissional="${escapeHtml(c.profissional_nome || '')}" class="aguardando-card w-full text-left bg-white rounded-xl shadow-monday p-4 hover:shadow-monday-lg hover:border-monday-blue border-2 border-transparent transition">
          <span class="font-medium text-gray-800 block">${escapeHtml(c.paciente_nome || '')}</span>
          <span class="text-sm text-gray-500">${c.data_consulta} ${fmtTime(c.hora_inicio_prevista)} — ${escapeHtml(c.especialidade_nome || '')}</span>
          <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">${escapeHtml(c.profissional_nome || 'Plantonista')}</span>
        </button>
      `).join('')}
    </div>
  `;

  container.innerHTML = tabHtml + `
    <div id="tab-content-prontuarios" class="tab-content-pront">${prontuariosHtml}</div>
    <div id="tab-content-aguardando" class="tab-content-pront hidden">${aguardandoHtml}</div>
  `;

  const filtroPacienteEl = container.querySelector('#filtro-paciente-pront');
  if (filtroPacienteEl) {
    filtroPacienteEl.addEventListener('change', () => {
      const val = filtroPacienteEl.value;
      prontuariosFiltroPaciente = val ? parseInt(val, 10) : null;
      renderProntuarios(container);
    });
  }

  container.querySelectorAll('.tab-pront').forEach((btn) => {
    btn.onclick = () => {
      container.querySelectorAll('.tab-pront').forEach((b) => {
        b.classList.remove('text-monday-blue', 'border-monday-blue');
        b.classList.add('text-gray-500');
      });
      btn.classList.add('text-monday-blue', 'border-b-2', 'border-monday-blue');
      btn.classList.remove('text-gray-500');
      const tab = btn.dataset.tab;
      container.querySelector('#tab-content-prontuarios').classList.toggle('hidden', tab !== 'prontuarios');
      container.querySelector('#tab-content-aguardando').classList.toggle('hidden', tab !== 'aguardando');
      if (typeof lucide !== 'undefined') lucide.createIcons();
    };
  });

  container.querySelectorAll('.btn-editar').forEach((btn) => {
    btn.onclick = async () => openModalEditarProntuario(parseInt(btn.dataset.id, 10), container);
  });
  container.querySelectorAll('.btn-excluir').forEach((btn) => {
    btn.onclick = async () => {
      if (!confirm('Excluir este prontuário?')) return;
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
        profissional_nome: btn.dataset.profissional || ''
      };
      openModalGerarProntuario(c, container);
    };
  });

  if (typeof lucide !== 'undefined') lucide.createIcons();
}

async function openModalGerarProntuario(consulta, container) {
  const dataConsulta = consulta.data_consulta || '';
  showLoadingOverlay('Abrindo...');
  try {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-y-auto';
    modal.innerHTML = `
      <div class="bg-white rounded-2xl shadow-monday-lg max-w-2xl w-full p-6 my-8 max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold mb-2">Gerar prontuário — ${escapeHtml(consulta.paciente_nome || '')}</h3>
        <p class="text-sm text-gray-500 mb-4">${dataConsulta} ${fmtTime(consulta.hora_inicio_prevista)} — ${escapeHtml(consulta.especialidade_nome || '')}</p>
        <p class="text-sm text-gray-600 mb-4">O prontuário será gerado com base em todos os dados do cadastro (tbAluno) e da anamnese (tb_anamnese_psi), seguindo os modelos do sistema.</p>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Observações adicionais (opcional)</label>
          <textarea id="modal-dados-clinicos" rows="2" class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="Complemente com queixa, achados ou notas do atendimento, se necessário"></textarea>
        </div>
        <button type="button" id="modal-btn-gerar" class="mt-3 w-full min-h-touch py-3 bg-purple-accent text-white rounded-xl font-medium hover:bg-purple-accent-hover transition">
          Gerar prontuário com IA
        </button>
        <div id="modal-resultado-ia" class="hidden mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Prontuário gerado (edite se necessário antes de salvar)</label>
          <textarea id="modal-pront-texto" rows="14" class="w-full px-4 py-3 border border-gray-300 rounded-lg font-mono text-sm" placeholder="O prontuário gerado pela IA aparecerá aqui."></textarea>
          <div class="flex gap-2 mt-3">
            <button type="button" id="modal-btn-salvar" class="flex-1 py-3 bg-monday-blue text-white rounded-lg font-medium">Salvar prontuário</button>
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
            onError: (msg) => { alert(msg || 'Erro ao gerar prontuário.'); }
          }
        );
      } catch (err) {
        alert(err.message || 'Erro ao gerar prontuário.');
      } finally {
        btn.disabled = false;
        btn.innerHTML = 'Gerar prontuário com IA';
      }
    };

    modal.querySelector('#modal-btn-salvar').onclick = async () => {
      const conteudo = (typeof tinymce !== 'undefined' && tinymce.get('modal-pront-texto'))
        ? tinymce.get('modal-pront-texto').getContent().trim()
        : modal.querySelector('#modal-pront-texto')?.value?.trim() || '';
      if (!conteudo) {
        alert('O prontuário está vazio. Gere primeiro com IA ou edite o texto.');
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
        btn.innerHTML = 'Salvar prontuário';
      }
    };

    document.body.appendChild(modal);
  } finally {
    hideLoadingOverlay();
  }
}

async function openModalEditarProntuario(id, container) {
  showLoadingOverlay('Carregando prontuário...');
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
      <label class="block text-sm font-medium text-gray-700 mb-1">Conteúdo do prontuário</label>
      <textarea id="pront-edit-conteudo" rows="14" class="w-full px-4 py-3 border border-gray-300 rounded-lg font-mono text-sm" ${canEdit ? '' : 'readonly disabled'}></textarea>
      <div class="flex gap-2 mt-4">
        ${canEdit ? `<button type="button" id="pront-btn-salvar" class="px-4 py-2 bg-monday-blue text-white rounded-lg">Salvar alterações</button>` : ''}
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
