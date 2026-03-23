
	(function() {
		const normalizePath = (value) => {
			if (!value) return '/';
			const cleaned = String(value).replace(/\/+$/, '');
			return cleaned === '' ? '/' : cleaned;
		};

		const currentPath = normalizePath(window.location.pathname);
		const links = document.querySelectorAll('.sidebar-link[href]');
		let bestMatch = null;
		let bestLength = -1;

		links.forEach((link) => {
			try {
				const href = link.getAttribute('href');
				if (!href || href.startsWith('#')) {
					return;
				}

				const linkPath = normalizePath(new URL(href, window.location.origin).pathname);
				const isExact = currentPath === linkPath;
				const isChild = linkPath !== '/' && currentPath.startsWith(linkPath + '/');

				if ((isExact || isChild) && linkPath.length > bestLength) {
					bestMatch = link;
					bestLength = linkPath.length;
				}
			} catch (e) {
				// ignora href invalido
			}
		});

		if (bestMatch) {
			const activeItem = bestMatch.closest('.sidebar-item');
			if (activeItem) {
				activeItem.classList.add('active');
			}
		}

		const groups = document.querySelectorAll('.sidebar-group[data-collapsible="true"]');

		groups.forEach((group) => {
			const toggle = group.querySelector('.sidebar-group-toggle');
			if (!toggle) {
				return;
			}

			toggle.addEventListener('click', () => {
				const isCollapsed = group.classList.toggle('is-collapsed');
				toggle.setAttribute('aria-expanded', (!isCollapsed).toString());
			});
		});

		const activeItem = document.querySelector('.sidebar-item.active');
		if (activeItem) {
			const activeGroup = activeItem.closest('.sidebar-group');
			if (activeGroup) {
				activeGroup.classList.remove('is-collapsed');
				const activeToggle = activeGroup.querySelector('.sidebar-group-toggle');
				if (activeToggle) {
					activeToggle.setAttribute('aria-expanded', 'true');
				}
			}
		}
	})();


	function abrirTelaInteira(url) {
		const largura = window.screen.availWidth;
		const altura = window.screen.availHeight;

		window.open(
			url,
			'_blank',
			`toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=${largura},height=${altura},top=0,left=0`
		);
	}


	function alternarFullscreen() {
		if (!document.fullscreenElement) {
			document.documentElement.requestFullscreen().catch(err => {
				console.warn(`Erro ao tentar entrar em tela cheia: ${err.message}`);
			});
		} else {
			document.exitFullscreen();
		}
	}


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

    function normalizeSearch(data) {
        if (typeof data !== 'string') return data;
        return data.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
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
                    <button type="submit" class="btn btn-warning">
                        <i class="fa-solid fa-pen-to-square" data-feather="edit-3"></i>
                    </button>
                </form>
                <form method="GET" action="/conectaosc3/relatorios/ficha-cadastro-pdf/" target="_blank" style="display:inline;">
                    <input type="hidden" name="id" value="${escapeHtml(item.IdUsuario)}">
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-file-pdf"></i>
                    </button>
                </form>
                <form method="POST" action="/conectaosc3/beneficiarios/cadastro/" style="display:inline;">
                    <input type="hidden" name="delete" value="${escapeHtml(item.IdUsuario)}">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir este benefici?f?????T?f??s?,?rio?');">
                        <i class="bi bi-trash-fill" data-feather="trash-2"></i>
                    </button>
                </form>
            </div>`;
    }

    function renderBeneficiarioRow(item) {
        let nome = item.Nome || '';
        if (item.Apelido) {
            nome = `(${item.Apelido}) ${nome}`;
        }
        return `<tr>
            <td class="text-center"><input type="checkbox" class="form-radio-input doacao" name="checkbox[]" value="${escapeHtml(item.IdUsuario)}"></td>
            <td class="text-center">${escapeHtml(item.IdUsuario)}</td>
            <td class="text-center">
                <span class="hover-container">
                    <img src="/conectaosc3/assets/img/fotos/${escapeHtml(item.Foto || 'padrao.jfif')}" class="rounded-circle img-cover hover-img" width="40" height="40" alt="">
                </span>
            </td>
            <td>${escapeHtml(nome)}</td>
            <td>${escapeHtml(item.TurmasAtivas || '')}</td>
            <td>${escapeHtml(item.HistoricoTurmas || '')}</td>
            <td class="text-center">${escapeHtml(item.Nascimento || '')}</td>
            <td class="text-center">${escapeHtml(item.Telefone || '')}</td>
            <td class="text-center">${escapeHtml(item.WhatsApp || '')}</td>
            <td>${escapeHtml(item.Obs || '')}</td>
            <td>${renderAcoes(item)}</td>
        </tr>`;
    }

    async function carregarBeneficiarios() {
        const response = await fetch(`${resolveApiBase()}/beneficiarios/resumo-ativos`, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Erro ao carregar benefici?f?????T?f??s?,?rios');
        }

        const tbody = document.getElementById('beneficiariosTbody');
        tbody.innerHTML = (result.data || []).map(renderBeneficiarioRow).join('');

        $.fn.dataTable.ext.type.search['locale-agnostic'] = function(data) {
            return normalizeSearch(data);
        };

        const table = $('#minhaTabela').DataTable({
            columnDefs: [{ targets: '_all', type: 'locale-agnostic' }],
            order: [[3, 'asc']]
        });

        $('#minhaTabela_filter input').on('input', function() {
            const normalizedInput = normalizeSearch($(this).val());
            $(this).val(normalizedInput);
            table.search(normalizedInput).draw();
        });

        if (window.feather) feather.replace();
        return result.data ? result.data.length : 0;
    }

    $(document).ready(async function() {
        const checkboxesSelecionados = {};

        mostrarOverlay({
            mensagem: 'Carregando benefici?f?????T?f??s?,?rios...',
            carregando: true
        });

        let total = 0;
        try {
            total = await carregarBeneficiarios();
        } catch (e) {
            console.error(e);
        } finally {
            const tabela = document.getElementById('minhaTabela');
            if (tabela) tabela.style.display = 'table';
            esconderOverlay();
        }

        const tituloElement = document.getElementById("titulo");
        if (tituloElement) {
            tituloElement.textContent = "Lista de Benefici?f?????T?f??s?,?rios: " + total;
        }

        setTimeout(() => {
            const alertElement = document.querySelector(".alert");
            if (alertElement) alertElement.style.display = "none";
        }, 2000);

        $('#minhaTabela').on('change', 'input[name="checkbox[]"]', function() {
            const id = $(this).val();
            if (this.checked) {
                checkboxesSelecionados[id] = true;
            } else {
                delete checkboxesSelecionados[id];
            }
        });

        $('#matricularAluno').click(function() {
            const selecionados = Object.keys(checkboxesSelecionados);
            if (selecionados.length > 0) {
                mostrarOverlay({ mensagem: 'Redirecionando para matr?f?????T?f??s?,?cula...', carregando: true });
                const form = $('<form>', {
                    action: "/conectaosc3/matriculas/realizar/",
                    method: 'post'
                });

                $.each(selecionados, function(index, valor) {
                    $(form).append($('<input>', { type: 'hidden', name: 'checkbox[]', value: valor }));
                });

                $('body').append(form);
                form.submit();
            } else {
                alert('Selecione pelo menos um aluno para matricular.');
            }
        });
    });

