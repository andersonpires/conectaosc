
    let table;
    const checkboxesSelecionados = {};

    function resolveApiBase() {
        const basePath = "/conectaosc3";
        if (/^https?:\/\//i.test(basePath)) {
            return `${basePath}/api/v1`;
        }
        return `${window.location.origin}${basePath}/api/v1`;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function calcularIdade(nascimento) {
        if (!nascimento) return '-';
        const partes = String(nascimento).split('/');
        if (partes.length !== 3) return '-';
        const data = new Date(`${partes[2]}-${partes[1]}-${partes[0]}`);
        if (Number.isNaN(data.getTime())) return '-';
        const hoje = new Date();
        let idade = hoje.getFullYear() - data.getFullYear();
        const m = hoje.getMonth() - data.getMonth();
        if (m < 0 || (m === 0 && hoje.getDate() < data.getDate())) idade--;
        return `${idade} anos`;
    }

    function abrirFormularioEdicao(idUsuario, cpf) {
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = '/conectaosc3/beneficiarios/cadastro';
        form.target = '_self';
        form.innerHTML = `<input type="hidden" name="id" value="${escapeHtml(idUsuario)}"><input type="hidden" name="cpf" value="${escapeHtml(cpf)}">`;
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    function renderAcoes(item) {
        if ("Administrador" === "Anulado") {
            return '<button type="button" class="btn btn-primary btn-disabled" disabled>Desabilitado</button>';
        }
        const cpfNumerico = String(item.CPF || '').replace(/\D/g, '');
        return `
            <div class="d-flex justify-content-center gap-2">
                <form method="GET" action="/conectaosc3/beneficiarios/cadastro" style="display:inline;">
                    <input type="hidden" name="cpf" value="${escapeHtml(cpfNumerico)}">
                    <input type="hidden" name="id" value="${escapeHtml(item.IdUsuario)}">
                    <button type="submit" class="btn btn-warning"><i class="fa-solid fa-pen-to-square" data-feather="edit-3"></i></button>
                </form>
                <form method="POST" action="/conectaosc3/beneficiarios/cadastro/" style="display:inline;">
                    <input type="hidden" name="delete" value="${escapeHtml(item.IdUsuario)}">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir este benefici?f?????T?f??s?,?rio?');"><i class="bi bi-trash-fill" data-feather="trash-2"></i></button>
                </form>
            </div>`;
    }

    function renderRow(item) {
        const pcdTexto = (String(item.PCDEmCasa) === '1') ? 'Sim' : 'N?f?????T?f??s?,?o';
        const def = String(item.DeficienciasCasa || '');
        const defCurto = def.length > 40 ? def.slice(0, 40) + '...' : def;
        const obs = String(item.Obs || '');
        const obsCurto = obs.length > 50 ? obs.slice(0, 50) + '...' : obs;
        const nome = item.Nome || '';
        const paciente = Number(item.Versatilis || 0) === 1
            ? '<img src="/conectaosc3/assets/img/logos/logo_sys.png" alt="Versatilis" width="140" height="30">'
            : 'N?f?????T?f??s?,?o';

        return `<tr>
            <td><input type="checkbox" class="form-radio-input doacao" name="checkbox[]" value="${escapeHtml(item.IdUsuario)}"></td>
            <td>${escapeHtml(item.IdUsuario)}</td>
            <td><span class="hover-container"><img src="/conectaosc3/assets/img/fotos/${escapeHtml(item.Foto || 'padrao.jfif')}" class="rounded-circle img-cover hover-img" width="40" height="40"></span></td>
            <td><a href="#" onclick="abrirFormularioEdicao('${escapeHtml(item.IdUsuario)}','${escapeHtml(String(item.CPF || '').replace(/\\D/g,''))}'); return false;" style="text-decoration:none; color:inherit;">${escapeHtml(nome)}</a></td>
            <td title="${escapeHtml(item.Interesses || '')}">${escapeHtml(item.Interesses || '')}</td>
            <td class="text-center">${paciente}</td>
            <td>${escapeHtml(item.TurmasAtivas || '')}</td>
            <td>${escapeHtml(item.HistoricoTurmas || '')}</td>
            <td>${escapeHtml(item.Nascimento || '')}</td>
            <td>${escapeHtml(calcularIdade(item.Nascimento || ''))}</td>
            <td>${escapeHtml(item.CPF || '')}</td>
            <td>${escapeHtml(item.Identidade || '')}</td>
            <td>${escapeHtml(item.Endereco || '')}</td>
            <td>${escapeHtml(item.Bairro || '')}</td>
            <td>${escapeHtml(item.Cidade || '')}</td>
            <td>${escapeHtml(item.Telefone || '')}</td>
            <td>${escapeHtml(item.WhatsApp || '')}</td>
            <td>${escapeHtml(item.NomeResp1 || '')}</td>
            <td>${escapeHtml(item.WhatsAppResp1 || '')}</td>
            <td class="text-center">${pcdTexto}</td>
            <td title="${escapeHtml(def)}">${escapeHtml(defCurto || '-')}</td>
            <td title="${escapeHtml(obs)}">${escapeHtml(obsCurto)}</td>
            <td>${renderAcoes(item)}</td>
        </tr>`;
    }

    async function carregarDados() {
        const response = await fetch(`${resolveApiBase()}/beneficiarios/detalhado-ativos`, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Erro ao carregar benefici?f?????T?f??s?,?rios');

        const tbody = document.getElementById('beneficiariosTbody');
        tbody.innerHTML = (result.data || []).map(renderRow).join('');

        if ($.fn.DataTable.isDataTable('#minhaTabela')) $('#minhaTabela').DataTable().destroy();
        table = $('#minhaTabela').DataTable({ order: [[3, 'asc']] });
        if (window.feather) feather.replace();
        return result.data ? result.data.length : 0;
    }

    document.addEventListener('DOMContentLoaded', async function() {
        mostrarOverlay({ mensagem: 'Carregando benefici?f?????T?f??s?,?rios...', carregando: true });
        let total = 0;
        try {
            total = await carregarDados();
        } catch (error) {
            console.error(error);
        } finally {
            const tabela = document.getElementById('minhaTabela');
            if (tabela) tabela.style.display = 'table';
            esconderOverlay();
        }

        const tituloElement = document.getElementById('titulo');
        if (tituloElement) tituloElement.textContent = `Lista de Benefici?f?????T?f??s?,?rios: ${total}`;

        setTimeout(() => {
            const alertElement = document.querySelector('.alert');
            if (alertElement) alertElement.style.display = 'none';
        }, 2000);
    });

    $('#minhaTabela').on('change', 'input[name="checkbox[]"]', function() {
        const id = $(this).val();
        if (this.checked) checkboxesSelecionados[id] = true;
        else delete checkboxesSelecionados[id];
    });

    $('#matricularAluno').click(function() {
        const selecionados = Object.keys(checkboxesSelecionados);
        if (!selecionados.length) {
            alert('Selecione pelo menos um aluno para matricular.');
            return;
        }
        const form = $('<form>', { action: "/conectaosc3/matriculas/realizar/", method: 'post' });
        $.each(selecionados, function(index, valor) {
            $(form).append($('<input>', { type: 'hidden', name: 'checkbox[]', value: valor }));
        });
        $(form).appendTo('body').submit();
    });

    function extrairTexto(html) {
        const div = document.createElement('div');
        div.innerHTML = html;
        return div.textContent.trim();
    }

    function extrairPaciente(html) {
        if (html.includes('logo_sys.png')) return 'Sim';
        return extrairTexto(html) || 'N?f?????T?f??s?,?o';
    }

    document.getElementById('btnExportExcel').addEventListener('click', function() {
        if (!table) return;
        const dadosFiltrados = table.rows({ search: 'applied' }).data();
        if (dadosFiltrados.length === 0) {
            alert('Nenhum dado encontrado com o filtro atual.');
            return;
        }

        const headers = [
            "ID", "Nome", "Interesses", "Paciente", "Turmas Ativas", "Hist?f?????T?f??s?,?rico de turmas", "Nascimento", "CPF",
            "Endere?f?????T?f??s?,?o", "Bairro", "Cidade", "Telefone", "WhatsApp", "Respons?f?????T?f??s?,?vel", "Contato", "Obs"
        ];

        const dadosFormatados = [];
        dadosFiltrados.each(function(row) {
            dadosFormatados.push([
                row[1], row[3], row[4], extrairPaciente(row[5]), row[6], row[7], row[8], row[10],
                row[12], row[13], row[14], row[15], row[16], row[17], row[18], row[21]
            ]);
        });

        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet([headers, ...dadosFormatados]);
        XLSX.utils.book_append_sheet(wb, ws, "Beneficiarios");
        XLSX.writeFile(wb, "listagemBeneficiarios_filtrado.xlsx");
    });

