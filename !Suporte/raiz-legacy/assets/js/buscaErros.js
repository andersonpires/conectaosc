// assets/js/buscaErros.js — v3: robustez p/ respostas não-JSON + fallback form-urlencoded

(function () {
  'use strict';

  const $ = (sel) => document.querySelector(sel);

  const btnScan = $('#btnScan');
  const btnDeleteAll = $('#btnDeleteAll');
  const btnStop = $('#btnStop');
  const fileList = $('#fileList');
  const logBox = $('#log');
  const progressText = $('#progressText');
  const counters = $('#counters');
  const progressBar = $('#progressBar');
  const baseDirSpan = $('#baseDir');
  const finalSummary = $('#finalSummary');

  const barCanvas = $('#barChart');
  const donutCanvas = $('#donutChart');

  const { endpoints } = window.BUSCA_ERROS_CONFIG || {};
  let files = [];
  let stats = { found: 0, deleted: 0, failed: 0 };
  let stopFlag = false;

  // ------------ UI helpers ------------
  function addLog(msg, type = 'info', details = null) {
    const row = document.createElement('div');
    row.className = `log-row ${type}`;
    row.textContent = msg;
    if (details) {
      const pre = document.createElement('pre');
      pre.style.whiteSpace = 'pre-wrap';
      pre.style.wordBreak = 'break-word';
      pre.style.marginTop = '6px';
      pre.textContent = typeof details === 'string' ? details : JSON.stringify(details, null, 2);
      row.appendChild(pre);
    }
    logBox.prepend(row);
  }

  function renderFiles(list) {
    fileList.innerHTML = '';
    if (!list.length) {
      fileList.innerHTML = '<li class="muted">Nenhum arquivo .htaccess encontrado.</li>';
      return;
    }
    const frag = document.createDocumentFragment();
    list.forEach((p) => {
      const li = document.createElement('li');
      li.innerHTML = `<code>${escapeHtml(p)}</code>`;
      frag.appendChild(li);
    });
    fileList.appendChild(frag);
  }

  function setProgress(percent) {
    const p = Math.max(0, Math.min(100, percent));
    progressBar.style.width = p + '%';
    progressText.textContent = p.toFixed(0) + '%';
  }

  function updateCounters() {
    counters.textContent = `Encontrados: ${stats.found} • Excluídos: ${stats.deleted} • Falhas: ${stats.failed}`;
  }

  function resetAll() {
    files = [];
    stats = { found: 0, deleted: 0, failed: 0 };
    stopFlag = false;
    setProgress(0);
    updateCounters();
    fileList.innerHTML = '';
    logBox.innerHTML = '';
    finalSummary.innerHTML = '';
    clearCanvas(barCanvas);
    clearCanvas(donutCanvas);
  }

  function escapeHtml(str) {
    return (str || '').replace(/[&<>"']/g, (m) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[m]));
  }

  // ------------ Charts (vanilla canvas) ------------
  function clearCanvas(cv) { const ctx = cv.getContext('2d'); ctx.clearRect(0, 0, cv.width, cv.height); }
  function drawBarChart(cv, data) {
    const ctx = cv.getContext('2d'); clearCanvas(cv);
    const labels = ['Encontrados', 'Excluídos', 'Falhas'];
    const values = [data.found, data.deleted, data.failed];
    const w = cv.width, h = cv.height, pad = 30;
    const chartW = w - pad * 2, chartH = h - pad * 2;
    const maxVal = Math.max(1, ...values);
    const barW = chartW / (values.length * 1.8);
    ctx.font = '14px ui-sans-serif, system-ui'; ctx.textBaseline = 'middle';
    values.forEach((v, i) => {
      const x = pad + (i * (chartW / values.length)) + (chartW / values.length - barW) / 2;
      const bh = (v / maxVal) * chartH; const y = h - pad - bh;
      const grad = ctx.createLinearGradient(0, y, 0, y + bh);
      [['#00F5D4','#00BBF9'],['#F15BB5','#FEE440'],['#9B5DE5','#F15BB5']][i].forEach((c,idx)=>grad.addColorStop(idx, c));
      ctx.fillStyle = grad; ctx.fillRect(x, y, barW, bh);
      ctx.fillStyle = '#E6F7FF';
      const label = `${labels[i]} (${v})`;
      ctx.fillText(label, x + barW/2 - ctx.measureText(label).width/2, h - pad/2);
    });
  }
  function drawDonutChart(cv, data) {
    const ctx = cv.getContext('2d'); clearCanvas(cv);
    const values = [data.deleted, data.failed, Math.max(0, data.found - data.deleted - data.failed)];
    const total = values.reduce((a,b)=>a+b,0) || 1;
    const colors = ['#00F5D4','#F15BB5','#2b2b2b'];
    const cx = cv.width/2, cy = cv.height/2;
    const rOuter = Math.min(cv.width, cv.height)*0.4, rInner = rOuter*0.55;
    let start = -Math.PI/2;
    values.forEach((v,i)=>{
      const angle = (v/total)*Math.PI*2, end = start+angle;
      ctx.beginPath(); ctx.arc(cx,cy,rOuter,start,end); ctx.arc(cx,cy,rInner,end,start,true);
      ctx.closePath(); ctx.fillStyle = colors[i]; ctx.fill(); start = end;
    });
    ctx.fillStyle = '#E6F7FF'; ctx.font = '600 18px ui-sans-serif, system-ui';
    const pct = total ? Math.round((data.deleted/total)*100) : 0;
    const txt = pct + '% sucesso'; ctx.fillText(txt, cx - ctx.measureText(txt).width/2, cy + 6);
  }
  function renderSummary() {
    const { found, deleted, failed } = stats;
    finalSummary.innerHTML = `
      <div class="summary-card">
        <div class="pill">Resumo</div>
        <p><strong>.htaccess encontrados:</strong> ${found}</p>
        <p><strong>Excluídos com sucesso:</strong> ${deleted}</p>
        <p><strong>Falhas:</strong> ${failed}</p>
      </div>`;
    drawBarChart(barCanvas, stats); drawDonutChart(donutCanvas, stats);
  }

  // ------------ Fetch helpers ------------
  async function parseMaybeJson(res) {
    const ctype = (res.headers.get('content-type') || '').toLowerCase();
    if (ctype.includes('application/json')) {
      try { return { kind: 'json', data: await res.json() }; }
      catch (e) { /* cai para text abaixo */ }
    }
    // Não é JSON ou falhou — devolve texto
    const text = await res.text();
    return { kind: 'text', data: text };
  }

  async function deleteOneWithFallback(path) {
    // 1) Tenta JSON
    let res = await fetch(endpoints.delete_one, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ action: 'delete_one', path })
    });
    let parsed = await parseMaybeJson(res);

    // Se veio JSON OK, retorna
    if (parsed.kind === 'json' && parsed.data && (parsed.data.ok !== undefined)) {
      return { response: res, data: parsed.data, via: 'json' };
    }

    // 2) Se não veio JSON (provável HTML de erro/WAF), tenta fallback form-urlencoded
    // (alguns WAFs bloqueiam application/json)
    res = await fetch(endpoints.delete_one, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'Accept': 'application/json' },
      body: new URLSearchParams({ action: 'delete_one', path })
    });
    parsed = await parseMaybeJson(res);

    // Se ainda não veio JSON, retorna como erro com payload de texto
    if (!(parsed.kind === 'json' && parsed.data && (parsed.data.ok !== undefined))) {
      const bodySnippet = (parsed.kind === 'text' ? parsed.data : JSON.stringify(parsed.data)).slice(0, 2000);
      return {
        response: res,
        data: {
          ok: false,
          message: 'Resposta não-JSON do servidor',
          debug: {
            status: res.status,
            statusText: res.statusText,
            contentType: res.headers.get('content-type'),
            bodySnippet
          }
        },
        via: 'fallback-text'
      };
    }

    return { response: res, data: parsed.data, via: 'form-urlencoded' };
  }

  // ------------ Actions ------------
  async function scan() {
    resetAll();
    btnScan.disabled = true;
    addLog('Iniciando varredura por .htaccess...', 'info');
    try {
      const res = await fetch(endpoints.scan, { method: 'GET', headers: { 'Accept': 'application/json' } });
      const parsed = await parseMaybeJson(res);
      if (parsed.kind !== 'json' || !parsed.data.ok) {
        addLog('Falha no scan: resposta não-JSON.', 'error', {
          status: res.status, contentType: res.headers.get('content-type'), bodySnippet: String(parsed.data).slice(0, 1200)
        });
        return;
      }
      const data = parsed.data;
      baseDirSpan.textContent = data.base || '';
      files = data.files || [];
      stats.found = data.total || files.length || 0;
      renderFiles(files);
      updateCounters();
      addLog(`Busca concluída: ${stats.found} arquivo(s) encontrado(s).`, 'success');
      btnDeleteAll.disabled = files.length === 0;
      btnStop.disabled = true;
    } catch (e) {
      addLog('Erro ao buscar arquivos.', 'error', { exception: String(e) });
    } finally {
      btnScan.disabled = false;
    }
  }

  async function deleteAll() {
    if (!files.length) return;
    let processed = 0;
    btnDeleteAll.disabled = true; btnScan.disabled = true; btnStop.disabled = false;
    addLog('Iniciando exclusão em lote...', 'warn');

    for (const relPath of files) {
      if (stopFlag) { addLog('Processo interrompido pelo usuário.', 'warn'); break; }
      try {
        const { response, data, via } = await deleteOneWithFallback(relPath);
        processed++;

        if (data.ok) {
          stats.deleted++;
          addLog(`✔ Excluído: ${relPath} (${via})`, 'success', data.debug || null);
        } else {
          stats.failed++;
          const details = data.debug || {
            status: response.status,
            statusText: response.statusText,
            contentType: response.headers.get('content-type')
          };
          addLog(`✖ Falha: ${relPath} — ${data.message || 'Erro desconhecido'}`, 'error', details);
        }

        const percent = (processed / stats.found) * 100;
        setProgress(percent); updateCounters();
      } catch (e) {
        processed++; stats.failed++;
        addLog(`✖ Erro inesperado ao excluir: ${relPath}`, 'error', { exception: String(e) });
        const percent = (processed / stats.found) * 100;
        setProgress(percent); updateCounters();
      }
      await new Promise(r => setTimeout(r, 40));
    }

    btnStop.disabled = true; btnScan.disabled = false;
    addLog('Processo finalizado.', 'info');
    renderSummary();
  }

  // ------------ Events ------------
  btnScan.addEventListener('click', scan);
  btnDeleteAll.addEventListener('click', deleteAll);
  btnStop.addEventListener('click', () => { stopFlag = true; btnStop.disabled = true; });

  const observer = new MutationObserver(() => {
    btnDeleteAll.disabled = fileList.children.length === 0 || /Nenhum arquivo/.test(fileList.textContent);
  });
  observer.observe(fileList, { childList: true });

})();
