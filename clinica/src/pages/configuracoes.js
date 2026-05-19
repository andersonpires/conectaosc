import { getTiposConsulta, postTipoConsulta, putTipoConsulta, postToggleTipoConsulta, getCurrentClinicaUser } from '../services/api.js';
import { getSpinnerHtml } from '../utils/loading.js';

function escapeHtml(value) {
  const div = document.createElement('div');
  div.textContent = value ?? '';
  return div.innerHTML;
}

export async function renderConfiguracoes(container) {
  const user = getCurrentClinicaUser();
  if (user.is_admin !== 1) {
    container.innerHTML = '<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">Acesso restrito a administradores.</div>';
    return;
  }

  container.innerHTML = getSpinnerHtml('Carregando configurações...');
  const tipos = await getTiposConsulta();

  container.innerHTML = `
    <section class="space-y-4">
      <div class="flex items-center justify-between gap-3">
        <div>
          <h2 class="text-xl font-semibold text-slate-900">Configurações</h2>
          <p class="text-sm text-slate-500">Gerencie os tipos de consulta usados no agendamento.</p>
        </div>
        <button type="button" id="btn-novo-tipo" class="rounded-xl bg-monday-blue px-4 py-2 text-sm font-medium text-white">Novo tipo</button>
      </div>
      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Nome</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
              <th class="px-4 py-3 text-right font-semibold text-slate-600">Ações</th>
            </tr>
          </thead>
          <tbody>
            ${tipos.map((tipo) => `
              <tr class="border-t border-slate-100">
                <td class="px-4 py-3">${escapeHtml(tipo.nome)}</td>
                <td class="px-4 py-3">${Number(tipo.ativo || 0) === 1 ? '<span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800">Ativo</span>' : '<span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-medium text-slate-700">Inativo</span>'}</td>
                <td class="px-4 py-3 text-right">
                  <button type="button" class="btn-editar-tipo rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700" data-id="${tipo.id}" data-nome="${escapeHtml(tipo.nome)}">Editar</button>
                  <button type="button" class="btn-toggle-tipo rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700" data-id="${tipo.id}">${Number(tipo.ativo || 0) === 1 ? 'Inativar' : 'Ativar'}</button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </section>
  `;

  const openModal = (title, initialValue, onSubmit) => {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4';
    modal.innerHTML = `
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-slate-900">${escapeHtml(title)}</h3>
        <form id="form-tipo" class="mt-4 space-y-4">
          <input type="text" name="nome" value="${escapeHtml(initialValue || '')}" class="w-full rounded-xl border border-slate-300 px-4 py-3" required>
          <div class="flex justify-end gap-2">
            <button type="button" class="btn-fechar rounded-xl border border-slate-300 px-4 py-2">Cancelar</button>
            <button type="submit" class="rounded-xl bg-monday-blue px-4 py-2 text-white">Salvar</button>
          </div>
        </form>
      </div>
    `;
    modal.querySelector('.btn-fechar')?.addEventListener('click', () => modal.remove());
    modal.querySelector('#form-tipo')?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const nome = String(new FormData(event.target).get('nome') || '').trim();
      if (!nome) return;
      await onSubmit(nome);
      modal.remove();
      await renderConfiguracoes(container);
    });
    modal.addEventListener('click', (event) => {
      if (event.target === modal) modal.remove();
    });
    document.body.appendChild(modal);
  };

  container.querySelector('#btn-novo-tipo')?.addEventListener('click', () => {
    openModal('Novo tipo de consulta', '', async (nome) => postTipoConsulta({ nome }));
  });

  container.querySelectorAll('.btn-editar-tipo').forEach((button) => {
    button.addEventListener('click', () => {
      openModal('Editar tipo de consulta', button.dataset.nome || '', async (nome) => putTipoConsulta(button.dataset.id, { nome }));
    });
  });

  container.querySelectorAll('.btn-toggle-tipo').forEach((button) => {
    button.addEventListener('click', async () => {
      await postToggleTipoConsulta(button.dataset.id);
      await renderConfiguracoes(container);
    });
  });
}
