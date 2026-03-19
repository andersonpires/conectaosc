<nav id="sidebar" class="sidebar js-sidebar">
	<div class="sidebar-content js-simplebar">
		<a class="sidebar-brand" href="<?php echo $_SESSION['BASE_URL'] ?>/index.php">
			<center>
				<image src="<?php echo $_SESSION['BASE_URL'] ?>/assets/img/sistema/logo.png" class="align-middle" style="width: 90%;">
			</center>
		</a>
		<ul class="sidebar-nav">
			<li class="sidebar-item" id="index.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL'] ?>/index.php">
					<i class="align-middle" data-feather="sliders"></i> <span class="align-middle">Dashboard</span>
				</a>
			</li>
			<li class="sidebar-item" id="swotController.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL'] ?>/app/swot/swotController.php">
					<i class="align-middle" data-feather="activity"></i> <span class="align-middle">SWOT</span>
				</a>
			</li>
			<li class="sidebar-item" id="listTipoChamada.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL'] ?>/app/chamada/listTipoChamada.php">
					<i class="align-middle" data-feather="user-check"></i> <span class="align-middle">Chamada</span>
				</a>
			</li>
			<li class="sidebar-item" id="feriadosController.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL'] ?>/app/feriados/feriadosController.php">
					<i class="align-middle" data-feather="calendar"></i> <span class="align-middle">Feriados</span>
				</a>
			</li>
			<?php
			if (!isset($_SESSION['profissional_saude']) && isset($_SESSION['Cod'])) {
				try {
					if (!isset($pdo)) require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
					$stmt = $pdo->prepare("SELECT COALESCE(profissional_saude, 0) FROM tbUser WHERE IdColaborador = ?");
					$stmt->execute([$_SESSION['Cod']]);
					$_SESSION['profissional_saude'] = (int)($stmt->fetchColumn() ?: 0);
				} catch (Throwable $e) {
					$_SESSION['profissional_saude'] = 0;
				}
			}
			$mostrarAppClinica = (int)($_SESSION['profissional_saude'] ?? 0) === 1;
			if ($mostrarAppClinica):
			?>
			<li class="sidebar-item" id="">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL'] ?>/clinica/" target="_blank">
					<i class="align-middle" data-feather="heart"></i> <span class="align-middle">App Clínica</span>
				</a>
			</li>
			<?php endif; ?>
			<li class="sidebar-item" id="crm.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL'] ?>/app/crm/crm.php">
					<i class="align-middle" data-feather="phone-call"></i> <span class="align-middle">CRM</span>
				</a>
			</li>
			<li class="sidebar-item" id="PDFupload.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL'] ?>/app/assinatura/PDFupload.php">
					<i class="align-middle" data-feather="phone-call"></i> <span class="align-middle">Assinar PDF</span>
				</a>
			</li>
			<li class="sidebar-item" id="configBackup.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL'] ?>/app/backup/configBackup.php">
					<i class="align-middle" data-feather="archive"></i> <span class="align-middle">Backup</span>
				</a>
			</li>
			<li class="sidebar-item" id="alerta.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL'] ?>/app/alerta/alerta.php">
					<i class="align-middle" data-feather="alert-triangle"></i> <span class="align-middle">Alerta</span>
				</a>
			</li>
			<li class="sidebar-item" id="permissaoController.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL'] ?>/app/permissao/permissaoController.php?view=list">
					<i class="align-middle" data-feather="lock"></i> <span class="align-middle">Permissões</span>
				</a>
			</li>

			<?php
			if ($_SESSION['Tipo'] == "Administrador") {
			?>
				<li class="sidebar-item" id="formPaginas.php">
					<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL'] ?>/app/permissao/formPaginas.php">
						<i class="align-middle" data-feather="file-plus"></i> <span class="align-middle">Nova página</span>
					</a>
				</li>
			<?php
			}
			?>
		</ul>

		<ul class="sidebar-nav sidebar-group is-collapsed" data-collapsible="true">
			<li class="sidebar-header">
				<button class="sidebar-group-toggle" type="button" data-sidebar-toggle="beneficiarios" aria-expanded="false">
					<i class="align-middle" data-feather="users"></i>
					<span>Beneficiários</span>
					<i class="fa-solid fa-chevron-down group-toggle-icon" aria-hidden="true"></i>
				</button>
			</li>
			<li class="sidebar-item" id="beneficiarioController.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL'] ?>/app/beneficiario/beneficiarioController.php">
					<i class="align-middle" data-feather="user-plus"></i> <span class="align-middle">Cadastrar beneficiário</span>
				</a>
			</li>
			<li class="sidebar-item" id="listagemSBenef.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL'] ?>/app/beneficiario/listagemSBenef.php">
					<i class="align-middle" data-feather="align-left"></i> <span class="align-middle">Lista de beneficiários</span>
				</a>
			</li>
			<li class="sidebar-item" id="listagemBeneficiarios.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL'] ?>/app/beneficiario/listagemBeneficiarios.php">
					<i class="align-middle" data-feather="list"></i> <span class="align-middle">Dados de beneficiários</span>
				</a>
			</li>
			<li class="sidebar-item" id="aniversariantes.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL'] ?>/app/aniversariantes/aniversariantes.php">
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
			<li class="sidebar-item" id="cadastro_projeto.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL']; ?>/app/projeto/cadastro_projeto.php">
					<i class="align-middle" data-feather="folder-plus"></i> <span class="align-middle">Cadastrar Projetos</span>
				</a>
			</li>
			<li class="sidebar-item" id="formCurso.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL']; ?>/app/curso/formCurso.php">
					<i class="align-middle" data-feather="shopping-cart"></i> <span class="align-middle">Cadastrar Cursos</span>
				</a>
			</li>
			<li class="sidebar-item" id="formTurma.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL']; ?>/app/turma/formTurma.php">
					<i class="align-middle" data-feather="shopping-cart"></i> <span class="align-middle">Cadastrar Turmas</span>
				</a>
			</li>
			<li class="sidebar-item" id="listagemSBenef.php?msg='Selecione as pessoas e clique em Matricular Aluno'">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL']; ?>/app/beneficiario/listagemSBenef.php?msg='Selecione as pessoas e clique em Matricular Aluno'&matricula=1">
					<i class="align-middle" data-feather="shopping-bag"></i> <span class="align-middle">Realizar matrículas</span>
				</a>
			</li>
			<li class="sidebar-item" id="listagemMatriculas.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL']; ?>/app/matricula/listagemMatriculas.php">
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
			<li class="sidebar-item" id="index.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL']; ?>/login.php">
					<i class="align-middle" data-feather="log-in"></i> <span class="align-middle">Login</span>
				</a>
			</li>
			<li class="sidebar-item" id="colaboradorController.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL']; ?>/app/colaborador/colaboradorController.php">
					<i class="align-middle" data-feather="user-plus"></i> <span class="align-middle">Colaboradores</span>
				</a>
			</li>
			<li class="sidebar-item" id="resetSenha.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL']; ?>/app/colaborador/resetSenha.php">
					<i class="align-middle" data-feather="unlock"></i> <span class="align-middle">Reset Senha</span>
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
			<li class="sidebar-item" id="formInscricao.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL']; ?>/app/evento/formInscricao.php">
					<i class="align-middle" data-feather="bar-chart-2"></i> <span class="align-middle">Inscrever</span>
				</a>
			</li>
			<li class="sidebar-item" id="listagemInscritos.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL']; ?>/app/evento/listagemInscritos.php">
					<i class="align-middle" data-feather="bar-chart-2"></i> <span class="align-middle">Lista de inscritos</span>
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
			<li class="sidebar-item" id="relFrequencia.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL']; ?>/app/relatorio/relFrequencia.php">
					<i class="align-middle" data-feather="bar-chart-2"></i> <span class="align-middle">Frequência</span>
				</a>
			</li>
			<li class="sidebar-item" id="relCustomize.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL']; ?>/app/relatorio/relCustomize.php">
					<i class="align-middle" data-feather="sliders"></i> <span class="align-middle">Customizado</span>
				</a>
			</li>
			<li class="sidebar-item" id="relatorioPresencaCursoTurma.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL']; ?>/app/relatorio/relatorioPresencaCursoTurma.php">
					<i class="align-middle" data-feather="sliders"></i> <span class="align-middle">Presença por aula/turma</span>
				</a>
			</li>
			<li class="sidebar-item" id="relMatriculados.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL']; ?>/app/relatorio/relMatriculados.php">
					<i class="align-middle" data-feather="bookmark"></i> <span class="align-middle">Matriculados</span>
				</a>
			</li>
			<li class="sidebar-item" id="swotRel.php">
				<a class="sidebar-link" href="<?php echo $_SESSION['BASE_URL']; ?>/app/relatorio/swotRel.php">
					<i class="align-middle" data-feather="bar-chart-2"></i> <span class="align-middle">SWOT</span>
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
</style><?php
$paginaAtual = basename($_SERVER['PHP_SELF']);

// Atribuir a classe "active" ao item da página atual
$idSeguro = preg_replace('/[^a-zA-Z0-9_-]/', '_', $paginaAtual);
echo '<script>
    var elemento = document.getElementById("' . $paginaAtual . '");
    if (elemento) {
        elemento.classList.add("active");
    }
</script>';
?>
<script>
	(function() {
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

