<?php
$tipoSessao = (string)($_SESSION['Tipo'] ?? '');
?>
<nav id="sidebar" class="sidebar js-sidebar">
	<div class="sidebar-content js-simplebar">
		<a class="sidebar-brand" href="<?php echo $BASE_para_URL ?>/dashboard/">
			<center>
				<image src="<?php echo $BASE_para_URL ?>/assets/img/sistema/logo.png" class="align-middle" style="width: 90%;">
			</center>
		</a>
		<ul class="sidebar-nav">
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL ?>/dashboard/">
					<i class="align-middle" data-feather="sliders"></i> <span class="align-middle">Dashboard</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL ?>/swot">
					<i class="align-middle" data-feather="activity"></i> <span class="align-middle">SWOT</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL ?>/chamada">
					<i class="align-middle" data-feather="user-check"></i> <span class="align-middle">Chamada</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL ?>/feriados">
					<i class="align-middle" data-feather="calendar"></i> <span class="align-middle">Feriados</span>
				</a>
			</li>
			<?php
			if ((!isset($_SESSION['profissional_saude']) || !isset($_SESSION['licenca_administrativa'])) && isset($_SESSION['Cod'])) {
				try {
					if (!isset($pdo)) require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
					$stmt = $pdo->prepare("SELECT COALESCE(profissional_saude, 0), COALESCE(licenca_administrativa, 0) FROM tbUser WHERE IdColaborador = ?");
					$stmt->execute([$_SESSION['Cod']]);
					$rowMenuClinica = $stmt->fetch(PDO::FETCH_NUM);
					$_SESSION['profissional_saude'] = (int)($rowMenuClinica[0] ?? 0);
					$_SESSION['licenca_administrativa'] = (int)($rowMenuClinica[1] ?? 0);
				} catch (Throwable $e) {
					$_SESSION['profissional_saude'] = 0;
					$_SESSION['licenca_administrativa'] = 0;
				}
			}
			$mostrarAppClinica = (int)($_SESSION['profissional_saude'] ?? 0) === 1
				|| (int)($_SESSION['licenca_administrativa'] ?? 0) === 1;
			if ($mostrarAppClinica):
			?>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL ?>/clinica/" target="_blank">
					<i class="align-middle" data-feather="heart"></i> <span class="align-middle">App Clínica</span>
				</a>
			</li>
			<?php endif; ?>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL ?>/crm">
					<i class="align-middle" data-feather="phone-call"></i> <span class="align-middle">CRM</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL ?>/assinatura/pdf">
					<i class="align-middle" data-feather="phone-call"></i> <span class="align-middle">Assinar PDF</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL ?>/backup">
					<i class="align-middle" data-feather="archive"></i> <span class="align-middle">Backup</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL ?>/alertas">
					<i class="align-middle" data-feather="alert-triangle"></i> <span class="align-middle">Alerta</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL ?>/permissoes">
					<i class="align-middle" data-feather="lock"></i> <span class="align-middle">Permissões</span>
				</a>
			</li>

			<?php
			if ($tipoSessao === 'Administrador') {
			?>
				<li class="sidebar-item">
					<a class="sidebar-link" href="<?php echo $BASE_para_URL ?>/permissoes/paginas">
						<i class="align-middle" data-feather="file-plus"></i> <span class="align-middle">Nova página</span>
					</a>
				</li>
			<?php
			}
			?>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL ?>/plano-cursos">
					<i class="align-middle" data-feather="calendar"></i> <span class="align-middle">Plano de Curso</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL ?>/bia">
					<i class="align-middle" data-feather="message-square"></i> <span class="align-middle">Ajuda</span>
				</a>
			</li>
		</ul>

		<ul class="sidebar-nav sidebar-group is-collapsed" data-collapsible="true">
			<li class="sidebar-header">
				<button class="sidebar-group-toggle" type="button" data-sidebar-toggle="beneficiarios" aria-expanded="false">
					<i class="align-middle" data-feather="users"></i>
					<span>Beneficiários</span>
					<i class="fa-solid fa-chevron-down group-toggle-icon" aria-hidden="true"></i>
				</button>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL ?>/beneficiarios/cadastro">
					<i class="align-middle" data-feather="user-plus"></i> <span class="align-middle">Cadastrar beneficiário</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL ?>/beneficiarios/lista">
					<i class="align-middle" data-feather="list"></i> <span class="align-middle">Lista de Beneficiários</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL ?>/beneficiarios/dados">
					<i class="align-middle" data-feather="align-left"></i> <span class="align-middle">Dados de Beneficiários</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL ?>/beneficiarios/aniversariantes">
					<i class="align-middle" data-feather="gift"></i> <span class="align-middle">Aniversariantes</span>
				</a>
			</li>
		</ul>

		<ul class="sidebar-nav sidebar-group is-collapsed" data-collapsible="true">
			<li class="sidebar-header">
				<button class="sidebar-group-toggle" type="button" data-sidebar-toggle="cursos" aria-expanded="false">
					<i class="align-middle" data-feather="book-open"></i>
					<span>Cursos e turmas</span>
					<i class="fa-solid fa-chevron-down group-toggle-icon" aria-hidden="true"></i>
				</button>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL; ?>/projetos">
					<i class="align-middle" data-feather="folder-plus"></i> <span class="align-middle">Cadastrar Projetos</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL; ?>/cursos">
					<i class="align-middle" data-feather="shopping-cart"></i> <span class="align-middle">Cadastrar Cursos</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL; ?>/turmas">
					<i class="align-middle" data-feather="shopping-cart"></i> <span class="align-middle">Cadastrar Turmas</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL; ?>/matriculas/realizar">
					<i class="align-middle" data-feather="shopping-bag"></i> <span class="align-middle">Realizar matrículas</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL; ?>/matriculas">
					<i class="align-middle" data-feather="list"></i> <span class="align-middle">Listar matrículas</span>
				</a>
			</li>
		</ul>

		<ul class="sidebar-nav sidebar-group is-collapsed" data-collapsible="true">
			<li class="sidebar-header">
				<button class="sidebar-group-toggle" type="button" data-sidebar-toggle="colaboradores" aria-expanded="false">
					<i class="align-middle" data-feather="briefcase"></i>
					<span>Dados de colaboradores</span>
					<i class="fa-solid fa-chevron-down group-toggle-icon" aria-hidden="true"></i>
				</button>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL; ?>/login">
					<i class="align-middle" data-feather="log-in"></i> <span class="align-middle">Login</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL; ?>/colaboradores">
					<i class="align-middle" data-feather="user-plus"></i> <span class="align-middle">Colaboradores</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL; ?>/colaboradores/reset-senha">
					<i class="align-middle" data-feather="unlock"></i> <span class="align-middle">Reset Senha</span>
				</a>
			</li>
		</ul>

		<ul class="sidebar-nav sidebar-group is-collapsed" data-collapsible="true">
			<li class="sidebar-header">
				<button class="sidebar-group-toggle" type="button" data-sidebar-toggle="relatorios" aria-expanded="false">
					<i class="align-middle" data-feather="bar-chart-2"></i>
					<span>Relatórios</span>
					<i class="fa-solid fa-chevron-down group-toggle-icon" aria-hidden="true"></i>
				</button>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL; ?>/relatorios/frequencia">
					<i class="align-middle" data-feather="bar-chart-2"></i> <span class="align-middle">Frequência</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL; ?>/relatorios/personalizado">
					<i class="align-middle" data-feather="sliders"></i> <span class="align-middle">Customizado</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL; ?>/relatorios/presenca-curso-turma">
					<i class="align-middle" data-feather="sliders"></i> <span class="align-middle">Presença por aula/turma</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL; ?>/relatorios/matriculados">
					<i class="align-middle" data-feather="bookmark"></i> <span class="align-middle">Matriculados</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL; ?>/relatorios/swot">
					<i class="align-middle" data-feather="bar-chart-2"></i> <span class="align-middle">SWOT</span>
				</a>
			</li>
		</ul>
		
		<ul class="sidebar-nav sidebar-group is-collapsed" data-collapsible="true">
			<li class="sidebar-header">
				<button class="sidebar-group-toggle" type="button" data-sidebar-toggle="eventos" aria-expanded="false">
					<i class="align-middle" data-feather="calendar"></i>
					<span>Evento 3o setor</span>
					<i class="fa-solid fa-chevron-down group-toggle-icon" aria-hidden="true"></i>
				</button>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL; ?>/eventos/inscricao">
					<i class="align-middle" data-feather="bar-chart-2"></i> <span class="align-middle">Inscrever</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class="sidebar-link" href="<?php echo $BASE_para_URL; ?>/eventos/inscritos">
					<i class="align-middle" data-feather="bar-chart-2"></i> <span class="align-middle">Lista de inscritos</span>
				</a>
			</li>
		</ul>
	</div>
</nav>
<style>
	.sidebar {
		min-height: 100vh;
	}

	.sidebar-content {
		height: auto;
		max-height: none;
	}

	.js-simplebar {
		height: auto;
		max-height: none;
	}

	.sidebar .simplebar-content-wrapper {
		max-height: 100vh;
		overflow-y: auto !important;
	}

	.sidebar .simplebar-content {
		padding-bottom: 0;
	}

	.sidebar-content {
		padding-bottom: 0;
	}

	.sidebar-nav:last-of-type {
		margin-bottom: 20rem;
	}

	.sidebar-group-toggle {
		align-items: center;
		background: none;
		border: 0;
		color: inherit;
		cursor: pointer;
		display: flex;
		font: inherit;
		gap: 0.5rem;
		padding: 0;
		text-align: left;
		width: 100%;
	}

	.sidebar-group-toggle .group-toggle-icon {
		margin-left: auto;
		transition: transform 0.2s ease;
	}

	.sidebar-group.is-collapsed > .sidebar-header .group-toggle-icon {
		transform: rotate(-90deg);
	}

	.sidebar-group.is-collapsed > li:not(.sidebar-header) {
		display: none;
	}
</style>
<script>
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
</script>
<script>
	function abrirTelaInteira(url) {
		const largura = window.screen.availWidth;
		const altura = window.screen.availHeight;

		window.open(
			url,
			'_blank',
			`toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=${largura},height=${altura},top=0,left=0`
		);
	}
</script>



