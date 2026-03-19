<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode((string) ($_SERVER['REQUEST_URI'] ?? ''));
    header('Location: ' . rtrim((string) ($BASE_para_URL ?? ''), '/') . '/login/?redirect=' . $redirect_url);
    exit();
}
require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
<style>
.pc-page{padding:1rem 0 1.25rem;background:#f5f7fb;color:#1f2937}
.pc-title{font-size:2rem;line-height:1.1;font-weight:800;color:#0f172a;margin:0}
.pc-sub{margin:.35rem 0 0;color:#64748b;font-size:1rem}
.pc-kpis{display:grid;grid-template-columns:1fr;gap:1rem;margin-top:1.25rem}
.pc-kpi{background:rgba(255,255,255,.86);border:1px solid rgba(255,255,255,.45);border-radius:1rem;padding:1rem 1.1rem;display:flex;align-items:center;gap:.8rem;box-shadow:0 6px 18px rgba(15,23,42,.05)}
.pc-kpi-icon{width:3rem;height:3rem;border-radius:.85rem;display:flex;align-items:center;justify-content:center;font-size:1.1rem}
.pc-kpi-icon .fa-solid{font-size:1.15rem;line-height:1}
.pc-kpi-icon.c1{background:#dbeafe;color:#1d4ed8}
.pc-kpi-icon.c2{background:#e0e7ff;color:#4338ca}
.pc-kpi-icon.c3{background:#d1fae5;color:#047857}
.pc-kpi-label{font-size:.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;font-weight:700}
.pc-kpi-value{font-size:2rem;line-height:1;font-weight:800;color:#0f172a;margin-top:.2rem;display:flex;align-items:center;gap:.4rem}
.pc-hero{margin-top:1.25rem;background:linear-gradient(130deg,#0b4aac 0%,#08347f 100%);border-radius:1.05rem;padding:1.2rem;box-shadow:0 14px 28px rgba(8,52,127,.24)}
.pc-hero h2{margin:0;color:#f8fafc;font-weight:800}
.pc-hero p{margin:.35rem 0 0;color:#dbeafe}
.pc-hero .form-control{height:3rem;border:0;border-radius:.7rem}
.pc-hero .btn{height:3rem;border-radius:.7rem;border:1px solid rgba(255,255,255,.3);background:rgba(255,255,255,.14);color:#fff;font-weight:700;padding:0 1.25rem}
.pc-hero .btn:hover{background:rgba(255,255,255,.23);color:#fff}
.pc-card{margin-top:1.5rem;background:rgba(255,255,255,.92);border:1px solid rgba(255,255,255,.45);border-radius:1.05rem;box-shadow:0 10px 25px rgba(15,23,42,.06);overflow:hidden}
.pc-card-head{padding:1.1rem 1.2rem;border-bottom:1px solid #e2e8f0;background:rgba(255,255,255,.7);display:flex;flex-direction:column;gap:.9rem}
.pc-card-title{margin:0;font-size:1.35rem;font-weight:800;color:#1e293b}
.pc-card-sub{margin:.15rem 0 0;font-size:.86rem;color:#64748b}
.pc-tools{display:grid;grid-template-columns:1fr;gap:.55rem}
.pc-search-wrap{position:relative}
.pc-search-wrap .bi{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:#94a3b8}
.pc-search{height:2.7rem;border-radius:.7rem;padding-left:2.25rem;border:1px solid #dbe3ef}
.pc-len-wrap{display:flex;align-items:center;gap:.45rem;font-size:.86rem;color:#64748b;justify-content:flex-end}
.pc-len{height:2.7rem;max-width:5rem;border-radius:.7rem;border:1px solid #dbe3ef}
.pc-table-wrap{overflow-x:auto}
#tbPlanoCursos{margin-bottom:0}
#tbPlanoCursos thead th{background:#f8fafc;color:#64748b;text-transform:uppercase;font-size:.72rem;letter-spacing:.05em;font-weight:700;padding:1rem;border-bottom:1px solid #e2e8f0}
#tbPlanoCursos tbody td{padding:1rem;vertical-align:middle}
#tbPlanoCursos tbody tr:nth-child(even){background:#fcfdff}
#tbPlanoCursos tbody tr:hover{background:#f1f5f9}
.pc-badge{display:inline-flex;align-items:center;justify-content:center;min-width:1.6rem;padding:.25rem .55rem;border-radius:999px;font-size:.75rem;font-weight:800}
.pc-badge.ok{background:#d1fae5;color:#047857}
.pc-badge.muted{background:#e2e8f0;color:#475569}
.pc-actions{display:flex;gap:.35rem;justify-content:center;flex-wrap:wrap}
.pc-btn-plan{border-radius:999px;padding:.2rem .85rem;font-size:.76rem;font-weight:700;background:#0b4aac;border-color:#0b4aac;color:#fff}
.pc-btn-plan:hover{background:#08347f;border-color:#08347f;color:#fff}
.pc-btn-turmas{border-radius:999px;padding:.2rem .85rem;font-size:.76rem;font-weight:700;border:1px solid #dbe3ef;background:#fff;color:#334155}
.pc-foot{padding:1rem 1.2rem;border-top:1px solid #e2e8f0;background:#f8fafc;display:flex;flex-direction:column;gap:.75rem}
.pc-info{font-size:.85rem;color:#64748b;font-weight:600}
.pc-pager{display:flex;gap:.3rem;justify-content:flex-end;flex-wrap:wrap}
.pc-pager .btn{height:1.9rem;min-width:1.9rem;padding:0 .5rem;border-radius:.45rem;border:1px solid #dbe3ef;background:#fff;color:#475569;font-size:.8rem;font-weight:700}
.pc-pager .btn.active{background:#0b4aac;border-color:#0b4aac;color:#fff}
.pc-pager .btn:disabled{opacity:.45}
#statusMsg{margin-top:.75rem;font-size:.88rem}
@media (min-width:768px){
  .pc-kpis{grid-template-columns:repeat(3,minmax(0,1fr))}
  .pc-hero{padding:1.4rem 1.5rem}
  .pc-card-head{padding:1.25rem 1.35rem;flex-direction:row;justify-content:space-between;align-items:flex-end}
  .pc-tools{grid-template-columns:minmax(280px,1fr) auto;align-items:end}
  .pc-foot{flex-direction:row;align-items:center;justify-content:space-between}
}
</style>
</head>
<body>
<div class="wrapper">
<?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>
<div class="main">
<?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
<main class="content">
<div class="container-fluid p-0">
<section class="pc-page">
<h1 class="pc-title">Plano de Curso</h1>
<p class="pc-sub">Gerencie o planejamento acadêmico e vínculos de turmas de forma centralizada.</p>
<div id="statusMsg" class="text-muted"></div>

<section class="pc-kpis">
<article class="pc-kpi"><div class="pc-kpi-icon c1"><i class="fa-solid fa-book-open" aria-hidden="true"></i></div><div><div class="pc-kpi-label">Cursos mapeados</div><div class="pc-kpi-value" id="kpiCursos"><span class="spinner-border spinner-border-sm text-secondary" role="status" aria-hidden="true"></span><span class="visually-hidden">Carregando...</span></div></div></article>
<article class="pc-kpi"><div class="pc-kpi-icon c2"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></div><div><div class="pc-kpi-label">Planos cadastrados</div><div class="pc-kpi-value" id="kpiPlanos"><span class="spinner-border spinner-border-sm text-secondary" role="status" aria-hidden="true"></span><span class="visually-hidden">Carregando...</span></div></div></article>
<article class="pc-kpi"><div class="pc-kpi-icon c3"><i class="fa-solid fa-users" aria-hidden="true"></i></div><div><div class="pc-kpi-label">Turmas com vínculo</div><div class="pc-kpi-value" id="kpiTurmasVinc"><span class="spinner-border spinner-border-sm text-secondary" role="status" aria-hidden="true"></span><span class="visually-hidden">Carregando...</span></div></div></article>
</section>

<section class="pc-hero">
<div class="row g-3 align-items-end">
<div class="col-12 col-lg-7">
<h2 class="h3">Iniciar Elaboração</h2>
<p>Selecione o curso para acessar o ambiente de planejamento pedagógico.</p>
</div>
<div class="col-12 col-lg-5">
<div class="d-flex flex-column flex-md-row gap-2">
<input id="inputCursoInicio" class="form-control" list="listaCursosInicio" placeholder="Selecione um curso ativo...">
<datalist id="listaCursosInicio"></datalist>
<button id="btnIrPlanejamento" class="btn text-nowrap">Abrir Planejamento</button>
</div>
</div>
</div>
</section>

<section class="pc-card">
<div class="pc-card-head">
<div>
<h3 class="pc-card-title">Cursos com planos elaborados</h3>
<p class="pc-card-sub">Lista detalhada de status de planejamento por modalidade</p>
</div>
<div class="pc-tools">
<div class="pc-search-wrap">
<i class="bi bi-search"></i>
<input id="tbSearch" type="text" class="form-control pc-search" placeholder="Pesquisar registros...">
</div>
<div class="pc-len-wrap">
<span>Exibir</span>
<select id="tbLen" class="form-select form-select-sm pc-len">
<option value="10" selected>10</option>
<option value="25">25</option>
<option value="50">50</option>
</select>
</div>
</div>
</div>
<div class="pc-table-wrap">
<table id="tbPlanoCursos" class="table">
<thead><tr><th>Nome do Curso</th><th class="text-center">Qtd Planos</th><th class="text-center">Turmas Vinculadas</th><th class="text-center">Ações</th></tr></thead>
<tbody id="tbPlanoCursosBody"></tbody>
</table>
</div>
<div class="pc-foot">
<div id="tbInfo" class="pc-info">Mostrando de 0 até 0 de 0 registros</div>
<div id="tbPagination" class="pc-pager"></div>
</div>
</section>
</section>
</div>
</main>
<footer class="footer"><?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?></footer>
</div>
</div>

<div class="modal fade" id="modalTurmasCurso" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title">Turmas do Curso</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
<div class="modal-body">
<div id="modalTurmasTitulo" class="small text-muted mb-2"></div>
<div class="table-responsive"><table class="table table-sm table-striped">
<thead><tr><th>Turma</th><th>Plano vinculado</th><th>Situação</th><th>Ação</th></tr></thead>
<tbody id="modalTurmasBody"></tbody>
</table></div>
</div>
</div></div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>
<script src="<?php echo $BASE_para_URL; ?>/assets/js/jquery.dataTables.min.js"></script>
<script>
let tabela = null;
let allRows = [];
let cursosAtivos = [];
let cursosInicio = [];

const apiBase = () => {
  const b = "<?php echo rtrim((string)$BASE_para_URL,'/'); ?>";
  return /^https?:\/\//i.test(b) ? `${b}/api/v1` : `${window.location.origin}${b}/api/v1`;
};
const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
const showStatus = (m, t = 'muted') => {
  const e = document.getElementById('statusMsg');
  e.className = `text-${t}`;
  e.textContent = m || '';
};
function setKpisLoading() {
  const html = '<span class="spinner-border spinner-border-sm text-secondary" role="status" aria-hidden="true"></span><span class="visually-hidden">Carregando...</span>';
  ['kpiCursos','kpiPlanos','kpiTurmasVinc'].forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.innerHTML = html;
  });
}
async function apiFetch(path, opt = {}) {
  const r = await fetch(`${apiBase()}${path}`, {credentials:'same-origin', ...opt});
  const j = await r.json().catch(() => ({}));
  if (!r.ok || j.success === false) throw new Error((j.errors && j.errors[0]) || j.message || 'Erro');
  return j;
}

async function carregarCursosPlano() {
  const [resumoResp, ativosResp] = await Promise.all([
    apiFetch('/plano-cursos/resumo'),
    apiFetch('/cursos/ativos')
  ]);

  allRows = (resumoResp.data || []).map((r) => ({
    IdCurso: Number(r.IdCurso || 0),
    NomeCurso: String(r.NomeCurso || ''),
    QtdPlanos: Number(r.QtdPlanos || 0),
    QtdTurmasComPlano: Number(r.QtdTurmasComPlano || 0)
  }));

  cursosAtivos = (ativosResp.data || []).map((c) => ({
    IdCurso: Number(c.IdCurso || 0),
    NomeCurso: String(c.NomeCurso || '')
  }));

  const idsSemPlano = new Set(
    allRows
      .filter((r) => Number(r.QtdPlanos || 0) === 0)
      .map((r) => Number(r.IdCurso))
  );
  cursosInicio = cursosAtivos.filter((c) => idsSemPlano.has(Number(c.IdCurso)));

  document.getElementById('kpiCursos').textContent = String(allRows.length);
  document.getElementById('kpiPlanos').textContent = String(allRows.reduce((a, r) => a + Number(r.QtdPlanos || 0), 0));
  document.getElementById('kpiTurmasVinc').textContent = String(allRows.reduce((a, r) => a + Number(r.QtdTurmasComPlano || 0), 0));

  const lista = document.getElementById('listaCursosInicio');
  lista.innerHTML = cursosInicio.map((c) => `<option value="${esc(c.NomeCurso)}"></option>`).join('');
  const inputInicio = document.getElementById('inputCursoInicio');
  const btnInicio = document.getElementById('btnIrPlanejamento');
  if (cursosInicio.length === 0) {
    if (inputInicio) {
      inputInicio.value = '';
      inputInicio.placeholder = 'Todos os cursos ativos já possuem plano.';
      inputInicio.disabled = true;
    }
    if (btnInicio) btnInicio.disabled = true;
  } else {
    if (inputInicio) inputInicio.disabled = false;
    if (btnInicio) btnInicio.disabled = false;
  }

  renderTabela();
}

function rowsComPlano() {
  return allRows.filter((r) => Number(r.QtdPlanos || 0) > 0);
}

function renderTabela() {
  const rows = rowsComPlano();
  const tbody = document.getElementById('tbPlanoCursosBody');
  tbody.innerHTML = rows.map((r) => `
    <tr>
      <td><div class="fw-bold">${esc(r.NomeCurso)}</div></td>
      <td class="text-center"><span class="pc-badge ${Number(r.QtdPlanos) > 0 ? 'ok' : 'muted'}">${Number(r.QtdPlanos)}</span></td>
      <td class="text-center"><span class="pc-badge ${Number(r.QtdTurmasComPlano) > 0 ? 'ok' : 'muted'}">${Number(r.QtdTurmasComPlano)}</span></td>
      <td class="text-center">
        <div class="pc-actions">
          <a class="btn pc-btn-plan btn-sm" href="<?php echo rtrim((string)$BASE_para_URL,'/'); ?>/cursos/planejamento?id=${Number(r.IdCurso)}">Planejar</a>
          <button class="btn pc-btn-turmas btn-sm" data-action="turmas" data-id="${Number(r.IdCurso)}">Turmas</button>
        </div>
      </td>
    </tr>`).join('');

  if (tabela && $.fn.dataTable.isDataTable('#tbPlanoCursos')) tabela.destroy();
  tabela = $('#tbPlanoCursos').DataTable({
    pageLength: Number(document.getElementById('tbLen').value || 10),
    order: [[0, 'asc']],
    language: {url:'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'},
    dom: 't',
    drawCallback: updateTableMeta
  });

  tbody.querySelectorAll('button[data-action="turmas"]').forEach((btn) => {
    btn.addEventListener('click', () => abrirModalTurmas(Number(btn.dataset.id)));
  });

  updateTableMeta();
}

function updateTableMeta() {
  if (!tabela) return;
  const info = tabela.page.info();
  const total = Number(info.recordsDisplay || 0);
  const ini = total > 0 ? Number(info.start) + 1 : 0;
  const fim = Number(info.end || 0);
  document.getElementById('tbInfo').textContent = `Mostrando de ${ini} até ${fim} de ${total} registros`;

  const pager = document.getElementById('tbPagination');
  const pages = Number(info.pages || 0);
  const current = Number(info.page || 0);

  if (pages <= 1) {
    pager.innerHTML = '';
    return;
  }

  let html = `<button class="btn" data-page="prev" ${current === 0 ? 'disabled' : ''}>&lsaquo;</button>`;
  for (let i = 0; i < pages; i++) {
    if (i < current - 1 || i > current + 1) continue;
    html += `<button class="btn ${i === current ? 'active' : ''}" data-page="${i}">${i + 1}</button>`;
  }
  html += `<button class="btn" data-page="next" ${current >= pages - 1 ? 'disabled' : ''}>&rsaquo;</button>`;
  pager.innerHTML = html;

  pager.querySelectorAll('button[data-page]').forEach((b) => {
    b.addEventListener('click', () => {
      const p = b.dataset.page;
      if (p === 'prev') tabela.page('previous').draw('page');
      else if (p === 'next') tabela.page('next').draw('page');
      else tabela.page(Number(p)).draw('page');
    });
  });
}

async function abrirModalTurmas(idCurso) {
  const row = allRows.find((r) => Number(r.IdCurso) === Number(idCurso));
  if (!row) return;
  document.getElementById('modalTurmasTitulo').textContent = `Curso: ${row.NomeCurso}`;
  const body = document.getElementById('modalTurmasBody');
  body.innerHTML = '<tr><td colspan="4" class="text-muted">Carregando turmas...</td></tr>';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTurmasCurso')).show();

  try {
    const resp = await apiFetch(`/cursos/${Number(idCurso)}/turmas-com-plano`);
    const turmas = resp.data || [];
    body.innerHTML = turmas.length ? turmas.map((t) => {
      const possui = Number(t.IdPlanoCurso || 0) > 0;
      return `<tr>
        <td>${esc(t.NomeTurma || '-')}</td>
        <td>${possui ? `${esc(t.NomePlano || '-')}${t.Versao ? ` (v${esc(t.Versao)})` : ''}` : '<span class="text-muted">Sem vínculo</span>'}</td>
        <td>${Number(t.Habilitado || 0) === 1 ? 'Ativa' : 'Concluída/Inativa'}</td>
        <td><a class="btn btn-sm btn-outline-primary ${possui ? '' : 'disabled'}" href="<?php echo rtrim((string)$BASE_para_URL,'/'); ?>/turmas/cronograma?idTurma=${Number(t.IdTurma)}">Abrir agenda</a></td>
      </tr>`;
    }).join('') : '<tr><td colspan="4" class="text-muted">Nenhuma turma encontrada.</td></tr>';
  } catch (err) {
    body.innerHTML = `<tr><td colspan="4" class="text-danger">${esc(err.message || 'Falha ao carregar turmas.')}</td></tr>`;
  }
}

function idCursoPorNome(nome) {
  const n = String(nome || '').trim().toLowerCase();
  if (!n) return 0;
  const exato = cursosInicio.find((c) => String(c.NomeCurso || '').trim().toLowerCase() === n);
  if (exato) return Number(exato.IdCurso);
  const matches = cursosInicio.filter((c) => String(c.NomeCurso || '').toLowerCase().includes(n));
  return matches.length === 1 ? Number(matches[0].IdCurso) : 0;
}

document.getElementById('btnIrPlanejamento')?.addEventListener('click', () => {
  const input = document.getElementById('inputCursoInicio');
  const idCurso = idCursoPorNome(input ? input.value : '');
  if (!idCurso) {
    showStatus('Digite e selecione um curso válido.', 'warning');
    return;
  }
  window.location.href = `<?php echo rtrim((string)$BASE_para_URL,'/'); ?>/cursos/planejamento?id=${idCurso}`;
});

document.getElementById('tbSearch')?.addEventListener('input', (e) => {
  if (!tabela) return;
  tabela.search(String(e.target.value || '')).draw();
});

document.getElementById('tbLen')?.addEventListener('change', (e) => {
  if (!tabela) return;
  tabela.page.len(Number(e.target.value || 10)).draw();
});

document.addEventListener('DOMContentLoaded', async () => {
  setKpisLoading();
  showStatus('Carregando cursos e planos...');
  try {
    await carregarCursosPlano();
    showStatus('Módulo de Plano de Curso carregado.');
  } catch (err) {
    document.getElementById('kpiCursos').textContent = '-';
    document.getElementById('kpiPlanos').textContent = '-';
    document.getElementById('kpiTurmasVinc').textContent = '-';
    showStatus(err.message, 'danger');
  }
});
</script>
</body>
</html>
