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
$idTurma = isset($_GET['idTurma']) ? (int) $_GET['idTurma'] : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link rel="stylesheet" href="<?php echo $BASE_para_URL; ?>/assets/css/overlayNotifica.css">
<script src="<?php echo $BASE_para_URL; ?>/assets/js/overlayNotifica.js"></script>
<style>
.pc-page { padding: 1rem 0 1.25rem; background: #f5f7fb; color: #1f2937; overflow-x: hidden; }
.pc-hero { background: linear-gradient(130deg, #0b4aac 0%, #08347f 100%); border-radius: 1rem; padding: 1rem 1.2rem; box-shadow: 0 12px 24px rgba(8, 52, 127, .24); margin-bottom: 1rem; }
.pc-hero h1 { color: #f8fafc; margin: 0; font-weight: 800; }
.pc-hero .sub { color: #dbeafe; margin-top: .35rem; }
.pc-hero .btn { border-radius: .7rem; white-space: nowrap; }
.cronograma-grid { display: grid; gap: 1rem; grid-template-columns: 1fr; }
.cronograma-grid > .card-shell { min-width: 0; }
.card-shell { border: 1px solid rgba(255,255,255,.45); border-radius: 1rem; background: rgba(255,255,255,.92); box-shadow: 0 10px 24px rgba(15, 23, 42, .06); min-width: 0; }
.card-head { padding: .9rem 1rem; border-bottom: 1px solid #edf2f8; background: rgba(255,255,255,.75); }
.card-body-shell { padding: 1rem; min-width: 0; overflow: hidden; }
.backlog-list { display: grid; gap: .6rem; max-height: 420px; overflow-y: auto; }
.aula-card { border: 1px solid #dbe5f3; border-radius: .75rem; padding: .7rem; background: #f8fbff; cursor: grab; }
.aula-card .tag { font-size: .78rem; display: inline-block; border-radius: 999px; background: #e8f0ff; color: #0b4aac; padding: .1rem .45rem; }
.agenda-scroll { width: 100%; max-width: 100%; max-height: 70vh; overflow: auto; cursor: grab; user-select: none; -webkit-overflow-scrolling: touch; }
.agenda-scroll.dragging { cursor: grabbing; }
.agenda-table { width: max-content; min-width: 100%; }
.agenda-table table { width: max-content; min-width: 100%; border-collapse: collapse; }
.agenda-table th,.agenda-table td { border-left: 1px solid #e9eef7; border-right: 1px solid #e9eef7; border-top: 0; border-bottom: 0; padding: .35rem; text-align: center; }
.agenda-table th:not(:first-child), .agenda-table td:not(:first-child) { min-width: 170px; }
.agenda-table th { vertical-align: top; background: #f8fafc; position: sticky; top: 0; z-index: 3; }
.agenda-table th:first-child { left: 0; z-index: 5; }
.agenda-table td:first-child { position: sticky; left: 0; z-index: 2; background: #f8fafc; font-weight: 400; font-size: .72rem; vertical-align: top; text-align: center; padding: .35rem; line-height: 1; }
.agenda-table tr.half-hour td { position: relative; border-top-color: transparent; }
.agenda-table tr.half-hour td::before { content: ""; position: absolute; left: 0; right: 0; top: -1px; border-top: 1px dashed rgba(207,216,234,.5); pointer-events: none; }
.agenda-table tr.full-hour td { border-top: 1px solid #b8c7df; }
.agenda-table tbody tr:last-child td { border-bottom: 1px solid #e9eef7; }
.agenda-dia { font-weight: 600; font-size: .82rem; }
.agenda-dia small { display: block; color: #667085; font-weight: 500; text-transform: uppercase; }
.agenda-dia .badge { margin-top: .25rem; }
.drop-zone { min-height: 30px; background: #fbfdff; }
.drop-zone.drop-hover { background: #eef5ff; outline: 2px dashed #0b4aac; }
.event-cell { padding: 0 !important; vertical-align: top; position: relative; }
.event-block { display: flex; flex-direction: column; justify-content: flex-start; position: absolute; inset: 0; width: 100%; height: 100%; border-radius: 0; padding: .45rem .5rem; text-align: left; cursor: pointer; border: 1px solid transparent; box-sizing: border-box; }
.event-title { font-weight: 700; font-size: .78rem; }
.event-time,.event-meta { font-size: .72rem; opacity: .9; }
.status-futura { background: #fff6cc; color: #7a5a00; border-color: #f7e6a4; }
.status-realizada { background: #d6f6dc; color: #146c2e; border-color: #bde8c6; }
.status-atrasada { background: #ffd9d9; color: #8a1d1d; border-color: #f4bcbc; }
.status-pendente { background: #e7edf7; color: #344054; border-color: #d3deee; }
.status-adiada { background: #eadcff; color: #4b2f91; border-color: #d8c5ff; }
.comment-thread { display: grid; gap: .5rem; max-height: 220px; overflow-y: auto; }
.comment-card { background: #f8fbff; border: 1px solid #e4ecfa; border-radius: .6rem; padding: .55rem; }
.comment-head { font-size: .78rem; color: #475467; }
.form-control:focus,.form-select:focus { border-color: #9db7e8; box-shadow: 0 0 0 .2rem rgba(13, 78, 194, .12); }
@media (min-width: 992px) { .cronograma-grid { grid-template-columns: 340px 1fr; } .pc-hero { padding: 1.1rem 1.25rem; } }
</style>
</head>
<body>
<div class="wrapper">
<?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>
<div class="main"><?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
<main class="content">
<div class="container-fluid p-0 pc-page" id="cronogramaContent">
<div class="pc-hero d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
  <div><h1 class="h3 mb-1">Cronograma da Turma</h1><div id="turmaTitulo" class="sub small">Carregando turma...</div></div>
  <a class="btn btn-light" href="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/plano-cursos">Voltar Planos</a>
</div>
<div id="statusMsg" class="small text-muted mb-2"></div>
<div class="cronograma-grid">
<section class="card-shell"><div class="card-head"><strong>Backlog (pendentes)</strong></div><div class="card-body-shell">
<div class="mb-2"><label class="form-label small">Data base</label><input type="date" id="dataBase" class="form-control form-control-sm"></div>
<div class="mb-2"><label class="form-label small">A&ccedil;&atilde;o r&aacute;pida</label><div class="d-flex gap-1"><input type="time" id="horaMobile" class="form-control form-control-sm"><button type="button" id="btnAgendarMobile" class="btn btn-sm btn-outline-primary">Agendar</button></div></div>
<div id="backlogList" class="backlog-list"></div></div></section>
<section class="card-shell"><div class="card-head d-flex justify-content-between align-items-center"><strong>Agenda</strong><button type="button" id="btnAtualizarAgenda" class="btn btn-sm btn-outline-secondary">Atualizar</button></div><div class="card-body-shell">
<div class="row g-2 mb-2">
  <div class="col-6 col-md-2"><label class="form-label small">In&iacute;cio (data)</label><input type="date" id="dataInicioAgenda" class="form-control form-control-sm"></div>
  <div class="col-6 col-md-2"><label class="form-label small">Fim (data)</label><input type="date" id="dataFimAgenda" class="form-control form-control-sm"></div>
  <div class="col-6 col-md-2"><label class="form-label small">In&iacute;cio (hora)</label><input type="time" id="horaInicioAgenda" step="1800" class="form-control form-control-sm"></div>
  <div class="col-6 col-md-2"><label class="form-label small">Fim (hora)</label><input type="time" id="horaFimAgenda" step="1800" class="form-control form-control-sm"></div>
  <div class="col-12 col-md-4"><label class="form-label small">Dias</label><div class="d-flex flex-wrap gap-2 small"><label><input type="checkbox" class="filtro-dia-semana" value="1" checked>Seg</label><label><input type="checkbox" class="filtro-dia-semana" value="2" checked>Ter</label><label><input type="checkbox" class="filtro-dia-semana" value="3" checked>Qua</label><label><input type="checkbox" class="filtro-dia-semana" value="4" checked>Qui</label><label><input type="checkbox" class="filtro-dia-semana" value="5" checked>Sex</label><label><input type="checkbox" class="filtro-dia-semana" value="6">Sab</label><label><input type="checkbox" class="filtro-dia-semana" value="0">Dom</label></div></div>
</div>
<div class="agenda-scroll" id="agendaScroll"><div class="agenda-table" id="agendaContainer"></div></div></div></section></div></div></main>
<footer class="footer"><?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?></footer></div></div>

<div id="overlayLoading" style="display:none;"><div class="overlay-bg"></div><div class="overlay-content" id="overlayContent"><div class="spinner-border text-primary" role="status" id="overlaySpinner"><span class="visually-hidden">Carregando...</span></div><p class="mt-2" id="overlayText">Carregando agenda...</p><button id="overlayBtnOk" class="btn btn-primary mt-3" style="display:none;">OK</button></div></div>

<div class="modal fade" id="modalConfirmAction" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Confirmar a&ccedil;&atilde;o</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div id="confirmActionText">Confirmar?</div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="btnConfirmActionOk">Confirmar</button></div></div></div></div>
<div class="modal fade" id="modalRemarcarAula" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Remarcar aula</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Nova data</label><input type="date" id="remarcarData" class="form-control mb-2"><label class="form-label">Novo hor&aacute;rio</label><input type="time" id="remarcarHora" class="form-control"></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="btnRemarcarOk">Salvar</button></div></div></div></div>

<div class="modal fade" id="modalAula" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Detalhes da Aula</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
<div id="detResumo" class="small mb-2"></div><div class="small mb-1"><strong>Recursos:</strong> <span id="detRec"></span></div><div class="small mb-2"><strong>Materiais:</strong> <span id="detMat"></span></div>
<div class="row g-2 mb-2"><div class="col-12 col-md-6"><textarea id="detFeedback" class="form-control form-control-sm" rows="2" placeholder="Feedback"></textarea></div><div class="col-12 col-md-6"><textarea id="detObs" class="form-control form-control-sm" rows="2" placeholder="Observa&ccedil;&otilde;es"></textarea></div></div>
<div class="mb-2"><textarea id="detJust" class="form-control form-control-sm" rows="2" placeholder="Justificativa de adiamento"></textarea></div>
<div class="d-flex flex-wrap gap-2 mb-3"><button class="btn btn-sm btn-outline-success" id="detBtnRealizada">Realizada</button><button class="btn btn-sm btn-outline-warning" id="detBtnAdiada">Adiada</button><button class="btn btn-sm btn-outline-primary" id="detBtnRemarcar">Remarcar</button><button class="btn btn-sm btn-dark" id="detBtnSalvar">Salvar</button></div>
<h6>Anexos do Plano</h6><div id="detAnexos" class="small mb-3 text-muted"></div>
<h6>Coment&aacute;rios</h6><div id="detComentarios" class="comment-thread mb-2"></div><div class="input-group input-group-sm"><input id="detNovoComentario" class="form-control" placeholder="Comentar..."><button class="btn btn-outline-dark" id="detBtnComentar">Comentar</button></div>
</div></div></div></div>

<script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>
<script>
const ID_TURMA = <?php echo (int)$idTurma; ?>;
let cronograma = [], backlog = [], selecionadaMobile = null, detalheAtual = null;

const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
const cls = (s) => ({realizada:'status-realizada',atrasada:'status-atrasada',futura:'status-futura',adiada:'status-adiada'}[String(s || '').toLowerCase()] || 'status-pendente');
const apiBase = () => {const b="<?php echo rtrim((string)$BASE_para_URL,'/'); ?>"; return /^https?:\/\//i.test(b)?`${b}/api/v1`:`${location.origin}${b}/api/v1`;};
const show = (m,t='muted') => {const e=document.getElementById('statusMsg');e.className=`small mb-2 text-${t}`;e.textContent=m||'';};
const notifySaving = () => { if (window.toastr) toastr.info('Salvando alterações...'); else show('Salvando alterações...', 'info'); };
const notifySaved = (msg = 'Alteração salva.') => { if (window.toastr) { toastr.clear(); toastr.success(msg); } else show(msg, 'success'); };
const notifySaveError = (msg = 'Falha ao salvar alterações.') => { if (window.toastr) { toastr.clear(); toastr.error(msg); } else show(msg, 'danger'); };
const min = (t) => {const [h,m]=String(t||'00:00').split(':').map(Number); return h*60+m;};
const hhmm = (v) => `${String(Math.floor(v/60)).padStart(2,'0')}:${String(v%60).padStart(2,'0')}`;
const duracaoVisualMin = (horaInicio, horaFim, duracaoCadastro) => {
  const porCadastro = Number(duracaoCadastro || 0);
  if (Number.isFinite(porCadastro) && porCadastro > 0) return porCadastro;
  const ini = min(String(horaInicio || '').slice(0,5));
  const fimTxt = String(horaFim || '').slice(0,5);
  const fim = fimTxt ? min(fimTxt) : 0;
  const porHorario = (fim > ini) ? (fim - ini) : 0;
  if (porHorario > 0) return porHorario;
  return 30;
};
if (window.toastr) {
  toastr.options = {
    closeButton: true,
    debug: false,
    newestOnTop: true,
    progressBar: true,
    positionClass: 'toast-top-center',
    preventDuplicates: false,
    onclick: null,
    showDuration: '300',
    hideDuration: '1000',
    timeOut: '3000',
    extendedTimeOut: '1000',
    showEasing: 'swing',
    hideEasing: 'linear',
    showMethod: 'fadeIn',
    hideMethod: 'fadeOut'
  };
}

function overlayShow(msg){
  if (typeof window.mostrarOverlay === 'function') window.mostrarOverlay({mensagem: msg || 'Carregando...', carregando: true});
  else {
    const el = document.getElementById('overlayLoading');
    const txt = document.getElementById('overlayText');
    if (txt) txt.textContent = msg || 'Carregando...';
    if (el) el.style.display = 'block';
  }
}
function overlayHide(){
  if (typeof window.esconderOverlay === 'function') window.esconderOverlay();
  else { const el = document.getElementById('overlayLoading'); if (el) el.style.display = 'none'; }
}

function bindAgendaDrag(){
  const wrap = document.getElementById('agendaScroll');
  if (!wrap || wrap.dataset.dragBound === '1') return;
  wrap.dataset.dragBound = '1';
  let dragging = false;
  let startX = 0;
  let startY = 0;
  let scrollLeft = 0;
  let scrollTop = 0;
  const isInteractiveTarget = (target) => Boolean(target && target.closest('button,a,input,textarea,select,.event-block,.aula-card'));
  const getClientX = (ev) => {
    if (ev.touches && ev.touches.length) return ev.touches[0].clientX;
    if (ev.changedTouches && ev.changedTouches.length) return ev.changedTouches[0].clientX;
    return ev.clientX;
  };
  const getClientY = (ev) => {
    if (ev.touches && ev.touches.length) return ev.touches[0].clientY;
    if (ev.changedTouches && ev.changedTouches.length) return ev.changedTouches[0].clientY;
    return ev.clientY;
  };
  const onStart = (ev) => {
    if (ev.type === 'mousedown' && ev.button !== 0) return;
    if (isInteractiveTarget(ev.target)) return;
    dragging = true;
    startX = getClientX(ev);
    startY = getClientY(ev);
    scrollLeft = wrap.scrollLeft;
    scrollTop = wrap.scrollTop;
    wrap.classList.add('dragging');
  };
  const onMove = (ev) => {
    if (!dragging) return;
    const dx = getClientX(ev) - startX;
    const dy = getClientY(ev) - startY;
    wrap.scrollLeft = scrollLeft - dx;
    wrap.scrollTop = scrollTop - dy;
    if (ev.cancelable) ev.preventDefault();
  };
  const onEnd = () => { dragging = false; wrap.classList.remove('dragging'); };
  wrap.addEventListener('mousedown', onStart);
  wrap.addEventListener('touchstart', onStart, { passive: true });
  window.addEventListener('mousemove', onMove, { passive: false });
  window.addEventListener('touchmove', onMove, { passive: false });
  window.addEventListener('mouseup', onEnd);
  window.addEventListener('touchend', onEnd);
  window.addEventListener('touchcancel', onEnd);
}

function confirmModal(text){
  return new Promise((resolve)=>{
    const m = document.getElementById('modalConfirmAction');
    document.getElementById('confirmActionText').textContent = text;
    const btn = document.getElementById('btnConfirmActionOk');
    const modal = bootstrap.Modal.getOrCreateInstance(m);
    const onOk = ()=>{btn.removeEventListener('click',onOk);resolve(true);modal.hide();};
    btn.addEventListener('click', onOk);
    m.addEventListener('hidden.bs.modal', ()=>resolve(false), {once:true});
    modal.show();
  });
}

function remarcarModal(dataAtual, horaAtual){
  return new Promise((resolve)=>{
    const m=document.getElementById('modalRemarcarAula');
    const d=document.getElementById('remarcarData');
    const h=document.getElementById('remarcarHora');
    d.value = dataAtual || '';
    h.value = horaAtual || '08:00';
    const btn=document.getElementById('btnRemarcarOk');
    const modal=bootstrap.Modal.getOrCreateInstance(m);
    const onOk=()=>{
      btn.removeEventListener('click',onOk);
      if(!d.value || !h.value){ show('Informe data e horário.', 'warning'); return; }
      resolve({data:d.value, hora:h.value});
      modal.hide();
    };
    btn.addEventListener('click',onOk);
    m.addEventListener('hidden.bs.modal', ()=>resolve(null), {once:true});
    modal.show();
  });
}

async function api(p,o={}){ const r=await fetch(`${apiBase()}${p}`,{credentials:'same-origin',...o}); const j=await r.json().catch(()=>({})); if(!r.ok||j.success===false) throw new Error((j.errors&&j.errors[0])||j.message||'Erro'); return j; }
function rangeDates(i,f,dows){const a=new Date(`${i}T00:00:00`),b=new Date(`${f}T00:00:00`);if(isNaN(a)||isNaN(b)||a>b)return[];const o=[];for(let d=new Date(a);d<=b;d.setDate(d.getDate()+1)) if(dows.includes(d.getDay())) o.push(new Date(d).toISOString().slice(0,10));return o;}
function buildSlots(){const s=[];for(let h=0;h<24;h++){s.push(`${String(h).padStart(2,'0')}:00`);s.push(`${String(h).padStart(2,'0')}:30`);}return s;}
function slotInRange(slot, hIni, hFim){
  const m = min(slot);
  const ini = min(hIni || '00:00');
  const fim = min(hFim || '23:30');
  if (ini <= fim) return m >= ini && m <= fim;
  return m >= ini || m <= fim;
}
async function feriados(datas){
  const q=new URLSearchParams();
  q.set('acao','verificar');
  q.set('datas',JSON.stringify(datas));
  const r=await fetch("<?php echo rtrim((string)$BASE_para_URL,'/'); ?>/feriados",{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:q.toString()});
  const j=await r.json().catch(()=>({success:false}));
  return j.success&&j.data?j.data:{};
}

function updateFiltersInUrl(){
  const u = new URL(window.location.href);
  const p = u.searchParams;
  const dIni = document.getElementById('dataInicioAgenda').value;
  const dFim = document.getElementById('dataFimAgenda').value;
  const hIni = document.getElementById('horaInicioAgenda').value;
  const hFim = document.getElementById('horaFimAgenda').value;
  if (dIni) p.set('dataInicio', dIni); else p.delete('dataInicio');
  if (dFim) p.set('dataFim', dFim); else p.delete('dataFim');
  if (hIni) p.set('horaInicio', hIni); else p.delete('horaInicio');
  if (hFim) p.set('horaFim', hFim); else p.delete('horaFim');
  window.history.replaceState({}, '', `${u.pathname}?${p.toString()}`);
}

function renderBacklog(){
  const el=document.getElementById('backlogList');
  if(!backlog.length){el.innerHTML='<div class="text-muted small">Sem pend&ecirc;ncias.</div>';return;}
  el.innerHTML=backlog.map(i=>`<div class="aula-card" draggable="true" data-id="${Number(i.IdCronogramaAula)}"><div class="d-flex justify-content-between mb-1"><span class="tag">Aula ${Number(i.OrdemAula||0)}</span><span class="small text-muted">${Number(i.DuracaoMinutos||0)} min</span></div><div class="fw-bold small">${esc(i.NomeAula)}</div><div class="small text-muted">${esc(i.Categoria||'-')}</div></div>`).join('');
  el.querySelectorAll('.aula-card').forEach(c=>{c.addEventListener('dragstart',e=>e.dataTransfer.setData('text/plain',c.dataset.id));c.addEventListener('click',()=>{selecionadaMobile=Number(c.dataset.id);el.querySelectorAll('.aula-card').forEach(x=>x.style.borderColor='#dbe5f3');c.style.borderColor='#0d6efd';});});
}

async function renderAgenda(){
  updateFiltersInUrl();
  const ini=document.getElementById('dataInicioAgenda').value;
  const fim=document.getElementById('dataFimAgenda').value;
  const hIni=document.getElementById('horaInicioAgenda').value || '07:00';
  const hFim=document.getElementById('horaFimAgenda').value || '18:00';
  const dows=[...document.querySelectorAll('.filtro-dia-semana:checked')].map(i=>Number(i.value));
  const dias=rangeDates(ini,fim,dows);
  const slots=buildSlots().filter((s)=>slotInRange(s,hIni,hFim));
  const el=document.getElementById('agendaContainer');
  if(!dias.length){el.innerHTML='<div class="text-muted small">Selecione per&iacute;odo e dias.</div>';return;}
  if(!slots.length){el.innerHTML='<div class="text-muted small">Selecione um intervalo de horas v&aacute;lido.</div>';return;}

  const fer=await feriados(dias),map={};dias.forEach(d=>map[d]={});
  for(const it of cronograma.filter(c=>c.DataAula&&c.HoraInicio&&dias.includes(String(c.DataAula).slice(0,10)))){
    const d=String(it.DataAula).slice(0,10),h0=String(it.HoraInicio||'').slice(0,5); if(!h0) continue;
    const slot=hhmm(Math.floor(min(h0)/30)*30); if(!slots.includes(slot)) continue;
    const iniM=min(h0);
    const dur=duracaoVisualMin(it.HoraInicio, it.HoraFim, it.DuracaoMinutos);
    const fimM = iniM + dur;
    map[d][slot]={item:{...it,DataAula:d,HoraFim:hhmm(fimM)},span:Math.max(1,Math.ceil((fimM-iniM)/30))};
  }

  let html='<table><thead><tr><th>Hor&aacute;rio</th>'+dias.map(d=>{const wd=['DOM','SEG','TER','QUA','QUI','SEX','SAB'][new Date(`${d}T00:00:00`).getDay()],b=(fer[d]&&fer[d].feriado)?`<span class="badge bg-danger">${esc(fer[d].nome||'Feriado')}</span>`:'',p=d.split('-');return `<th><div class="agenda-dia">${p[2]}/${p[1]}/${p[0]}<small>${wd}</small>${b}</div></th>`;}).join('')+'</tr></thead><tbody>';
  const skip={};dias.forEach(d=>skip[d]=0);
  for(const t of slots){
    const isHalf = t.endsWith(':30');
    const horaLabel = isHalf ? '&nbsp;' : `${Number(t.slice(0,2))}h`;
    html+=`<tr class="${isHalf ? 'half-hour' : 'full-hour'}"><td>${horaLabel}</td>`;
    for(const d of dias){
      if(skip[d]>0){skip[d]--;continue;}
      const st=map[d][t];
      if(st){
        skip[d]=Math.max(0,st.span-1);
        const i=st.item;
        html+=`<td class="event-cell" rowspan="${st.span}"><div class="event-block ${cls(i.StatusExecucao)}" data-id="${Number(i.IdCronogramaAula)}" draggable="true"><div class="event-title">${esc(i.NomeAula)}</div><div class="event-time">${esc(String(i.HoraInicio||'').slice(0,5))}-${esc(String(i.HoraFim||'').slice(0,5))}</div><div class="event-meta">${Number(i.DuracaoMinutos||0)} min</div></div></td>`;
      } else {
        html+=`<td class="drop-zone" data-date="${d}" data-time="${t}"></td>`;
      }
    }
    html+='</tr>';
  }
  html+='</tbody></table>';
  el.innerHTML=html;

  const wrap=document.getElementById('agendaScroll');
  const rows = [...el.querySelectorAll('tbody tr')];
  const rowStart = rows[slots.indexOf(hIni)] || null;
  if (wrap && rowStart) wrap.scrollTop = Math.max(0, rowStart.offsetTop - 48);

  el.querySelectorAll('.drop-zone').forEach(z=>{
    z.addEventListener('dragover',e=>{e.preventDefault();z.classList.add('drop-hover');});
    z.addEventListener('dragleave',()=>z.classList.remove('drop-hover'));
    z.addEventListener('drop',async e=>{e.preventDefault();z.classList.remove('drop-hover');const id=Number(e.dataTransfer.getData('text/plain'));if(id)await agendar(id,z.dataset.date,z.dataset.time);});
    z.addEventListener('click',async()=>{if(selecionadaMobile)await agendar(selecionadaMobile,z.dataset.date,z.dataset.time);});
  });
  el.querySelectorAll('.event-block').forEach(b=>{
    b.addEventListener('dragstart',e=>e.dataTransfer.setData('text/plain',b.dataset.id));
    b.addEventListener('click',async()=>{const i=cronograma.find(x=>Number(x.IdCronogramaAula)===Number(b.dataset.id||0));if(i)await openModal(i);});
  });
}

async function agendar(id,data,hora){
  const q=new URLSearchParams();
  q.set('acao','verificar');
  q.set('datas',JSON.stringify([data]));
  const r=await fetch("<?php echo rtrim((string)$BASE_para_URL,'/'); ?>/feriados",{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:q.toString()});
  const j=await r.json().catch(()=>({success:false}));
  const f=j.success&&j.data?j.data[data]:null;
  if(f&&f.feriado){
    const ok = await confirmModal(`A data ${data} \u00e9 feriado (${f.nome||'Feriado'}). Deseja continuar?`);
    if(!ok) return;
  }
  const idx = cronograma.findIndex((x)=>Number(x.IdCronogramaAula)===Number(id));
  const prev = idx >= 0 ? { ...cronograma[idx] } : null;
  if (idx >= 0) {
    const horaInicio = /^\d{2}:\d{2}$/.test(hora) ? `${hora}:00` : hora;
    const dur = Number(cronograma[idx].DuracaoMinutos || 30);
    const fimMin = min(String(horaInicio).slice(0,5)) + (dur > 0 ? dur : 30);
    cronograma[idx] = {
      ...cronograma[idx],
      DataAula: data,
      HoraInicio: horaInicio,
      HoraFim: `${hhmm(fimMin)}:00`,
      StatusExecucao: 'futura'
    };
    backlog = cronograma.filter(c=>!c.DataAula||String(c.StatusExecucao).toLowerCase()==='pendente');
    renderBacklog();
    await renderAgenda();
  }
  try{
    notifySaving();
    await api(`/cronograma-aulas/${id}/agendar`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({DataAula:data,HoraInicio:hora,StatusExecucao:'futura'})});
    notifySaved('Alteração salva.');
    show('Aula agendada.','success');
  } catch (e) {
    if (idx >= 0 && prev) {
      cronograma[idx] = prev;
      backlog = cronograma.filter(c=>!c.DataAula||String(c.StatusExecucao).toLowerCase()==='pendente');
      renderBacklog();
      await renderAgenda();
    }
    notifySaveError(e.message || 'Falha ao salvar alteração.');
    show(e.message || 'Falha ao salvar alteração.', 'danger');
    return;
  }
}

async function loadCronograma(){ const j=await api(`/turmas/${ID_TURMA}/plano-curso/cronograma`); cronograma=j.data||[]; backlog=cronograma.filter(c=>!c.DataAula||String(c.StatusExecucao).toLowerCase()==='pendente'); renderBacklog(); await renderAgenda(); show(`Cronograma carregado. ${cronograma.length} registro(s).`); }
async function openModal(i){ detalheAtual=i; document.getElementById('detResumo').innerHTML=`<strong>${esc(i.NomeAula||'')}</strong><br>Ordem ${Number(i.OrdemAula||0)} | ${Number(i.DuracaoMinutos||0)} min | ${esc(i.Categoria||'-')}<br>${esc(String(i.DataAula||'').slice(0,10))} ${esc(String(i.HoraInicio||'').slice(0,5))}-${esc(String(i.HoraFim||'').slice(0,5))}`; document.getElementById('detRec').textContent=i.Recursos||'-'; document.getElementById('detMat').textContent=i.Materiais||'-'; document.getElementById('detFeedback').value=i.FeedbackProfessor||''; document.getElementById('detObs').value=i.Observacoes||''; document.getElementById('detJust').value=i.JustificativaAdiamento||''; await Promise.all([loadAnexos(),loadCom()]); bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAula')).show(); }
async function loadAnexos(){ if(!detalheAtual)return; const p=await api(`/planos-curso/aulas/${Number(detalheAtual.IdPlanoCursoAula)}/anexos`); const l=(p.data||[]); document.getElementById('detAnexos').innerHTML=l.length?l.map(a=>`<div><a href="<?php echo rtrim((string)$BASE_para_URL,'/'); ?>/${esc(a.CaminhoRelativo||'')}" target="_blank">${esc(a.NomeArquivo||a.NomeOriginal||a.NomeFisico||'Arquivo')}</a>${a.Descricao?`<div class="small text-muted">${esc(a.Descricao)}</div>`:''}</div>`).join(''):'Sem anexos.'; }
async function loadCom(){ if(!detalheAtual)return; const j=await api(`/cronograma-aulas/${Number(detalheAtual.IdCronogramaAula)}/comentarios`),l=j.data||[]; document.getElementById('detComentarios').innerHTML=l.length?l.map(c=>`<div class="comment-card"><div class="comment-head"><strong>${esc(`${c.Nome||''} ${c.Sobrenome||''}`.trim()||'Usu\u00e1rio')}</strong> | ${esc(c.DataCriacao||'')}</div><div class="small">${esc(c.Comentario||'')}</div></div>`).join(''):'<div class="small text-muted">Sem coment\u00e1rios.</div>'; }
async function saveStatus(s){ if(!detalheAtual)return; notifySaving(); try{ await api(`/cronograma-aulas/${Number(detalheAtual.IdCronogramaAula)}/status`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({StatusExecucao:s,FeedbackProfessor:document.getElementById('detFeedback').value,Observacoes:document.getElementById('detObs').value,JustificativaAdiamento:document.getElementById('detJust').value})}); await loadCronograma(); notifySaved(); } catch (e) { notifySaveError(); throw e; } }

document.getElementById('detBtnRealizada').addEventListener('click',async()=>{try{await saveStatus('realizada');show('Marcada como realizada.','success');}catch(e){show(e.message,'danger');}});
document.getElementById('detBtnAdiada').addEventListener('click',async()=>{try{if(!document.getElementById('detJust').value.trim()){show('Informe a justificativa de adiamento.','warning');return;}await saveStatus('adiada');show('Marcada como adiada.','success');}catch(e){show(e.message,'danger');}});
document.getElementById('detBtnRemarcar').addEventListener('click',async()=>{ if(!detalheAtual)return; const res=await remarcarModal(String(detalheAtual.DataAula||'').slice(0,10), String(detalheAtual.HoraInicio||'').slice(0,5)||'08:00'); if(!res) return; try{ notifySaving(); await api(`/cronograma-aulas/${Number(detalheAtual.IdCronogramaAula)}/agendar`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({DataAula:res.data,HoraInicio:res.hora,StatusExecucao:'futura'})}); await loadCronograma(); notifySaved(); show('Aula remarcada.','success'); } catch(e){ notifySaveError(); show(e.message,'danger'); } });
document.getElementById('detBtnSalvar').addEventListener('click',async()=>{try{await saveStatus(String((detalheAtual&&detalheAtual.StatusExecucao)||'futura').toLowerCase());show('Dados salvos.','success');}catch(e){show(e.message,'danger');}});
document.getElementById('detBtnComentar').addEventListener('click',async()=>{if(!detalheAtual)return;const c=document.getElementById('detNovoComentario'),t=(c.value||'').trim();if(!t)return;try{notifySaving();await api(`/cronograma-aulas/${Number(detalheAtual.IdCronogramaAula)}/comentarios`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({Comentario:t})});c.value='';await loadCom();notifySaved();}catch(e){notifySaveError();show(e.message,'danger');}});

const rerender = () => { renderAgenda().catch((e)=>show(e.message,'danger')); };
document.getElementById('btnAtualizarAgenda').addEventListener('click', rerender);
document.getElementById('btnAgendarMobile').addEventListener('click',async()=>{if(!selecionadaMobile){show('Selecione uma aula no backlog.','warning');return;}const d=document.getElementById('dataBase').value,h=document.getElementById('horaMobile').value;if(!d||!h){show('Informe data e hora.','warning');return;}await agendar(selecionadaMobile,d,h);});
document.getElementById('dataInicioAgenda').addEventListener('change',rerender);
document.getElementById('dataFimAgenda').addEventListener('change',rerender);
document.getElementById('horaInicioAgenda').addEventListener('change',rerender);
document.getElementById('horaFimAgenda').addEventListener('change',rerender);
document.querySelectorAll('.filtro-dia-semana').forEach(e=>e.addEventListener('change',rerender));

document.addEventListener('DOMContentLoaded',async()=>{
  bindAgendaDrag();
  const params = new URLSearchParams(window.location.search);
  const tdy=new Date().toISOString().slice(0,10),dt=new Date(`${tdy}T00:00:00`),dw=dt.getDay()===0?7:dt.getDay(),mon=new Date(dt);mon.setDate(dt.getDate()-(dw-1));const sun=new Date(mon);sun.setDate(mon.getDate()+6);
  document.getElementById('dataBase').value=tdy;
  document.getElementById('dataInicioAgenda').value=params.get('dataInicio') || mon.toISOString().slice(0,10);
  document.getElementById('dataFimAgenda').value=params.get('dataFim') || sun.toISOString().slice(0,10);
  document.getElementById('horaInicioAgenda').value=params.get('horaInicio') || '07:00';
  document.getElementById('horaFimAgenda').value=params.get('horaFim') || '18:00';
  try {
    overlayShow('Carregando agenda...');
    const ts=await api('/turmas');
    const t=(ts.data||[]).find(x=>Number(x.IdTurma)===ID_TURMA);
    document.getElementById('turmaTitulo').textContent=t?`Turma: ${t.NomeTurma} | Curso: ${t.NomeCurso}`:'Turma n\u00e3o encontrada.';
    await loadCronograma();
  } catch(e) { show(e.message,'danger'); }
  finally { overlayHide(); }
});
</script>
</body>
</html>
