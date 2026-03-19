<nav class="navbar navbar-expand navbar-light navbar-bg">
	<a class="sidebar-toggle js-sidebar-toggle">
		<i class="hamburger align-self-center"></i>
	</a>

	<div class="navbar-collapse collapse">
		<ul class="navbar-nav navbar-align">
			<li class="nav-item dropdown">
				<!-- <a class="nav-icon dropdown-toggle" href="#" id="alertsDropdown" data-bs-toggle="dropdown">
					<div class="position-relative">
						<i class="align-middle" data-feather="bell"></i>
						<span class="indicator">4</span>
					</div>
				</a> -->
				<div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0" aria-labelledby="alertsDropdown">
					<div class="dropdown-menu-header">
						4 Novas Notificações
					</div>
					<div class="list-group">
						<a href="#" class="list-group-item">
							<div class="row g-0 align-items-center">
								<div class="col-2">
									<i class="text-danger" data-feather="alert-circle"></i>
								</div>
								<div class="col-10">
									<div class="text-dark">Update completo</div>
									<div class="text-muted small mt-1">Reinicie o servidor para completar o update.</div>
									<div class="text-muted small mt-1">30min atrás</div>
								</div>
							</div>
						</a>
						<a href="#" class="list-group-item">
							<div class="row g-0 align-items-center">
								<div class="col-2">
									<i class="text-warning" data-feather="bell"></i>
								</div>
								<div class="col-10">
									<div class="text-dark">Alerta</div>
									<div class="text-muted small mt-1">Erros estão afetando os registros</div>
									<div class="text-muted small mt-1">2h atrás</div>
								</div>
							</div>
						</a>
						<a href="#" class="list-group-item">
							<div class="row g-0 align-items-center">
								<div class="col-2">
									<i class="text-primary" data-feather="home"></i>
								</div>
								<div class="col-10">
									<?php
									// Cria as duas datas
									date_default_timezone_set('America/Sao_Paulo');
									if ($_SESSION['ultimoAcessoData'] != "Agora") {
										$dataUltimoAcesso = new DateTime($_SESSION['ultimoAcessoData']);
										$dataAtual = new DateTime(date("Y-m-d H:i:s"));

										// Calcula a diferença entre as duas datas
										$interval = $dataAtual->diff($dataUltimoAcesso);

										// Cria variável com resultado mais próximo
										$AnoUA = intval($interval->format("%y"));
										$MesUA = intval($interval->format("%m"));
										$DiaUA = intval($interval->format("%d"));
										$HoraUA = intval($interval->format("%h"));
										$MinUA = intval($interval->format("%i"));
										$SegUA = intval($interval->format("%s"));
										if ($AnoUA > 0) {
											$DiffUltimoAcesso = $AnoUA . " ano(s)";
										} elseif ($MesUA > 0) {
											$DiffUltimoAcesso = $MesUA . " mes(es)";
										} elseif ($DiaUA > 0) {
											$DiffUltimoAcesso = $DiaUA . " dia(s)";
										} elseif ($HoraUA > 0) {
											$DiffUltimoAcesso = $HoraUA . " hora(s)";
										} else {
											$DiffUltimoAcesso = $SegUA . " segundo(s)";
										}
									} else {
										$DiffUltimoAcesso = "Primeiro acesso. Seja bem-vindo(a).";
									}

									?>
									<div class="text-dark">Login feito pelo IP <?php echo strval($_SERVER['REMOTE_ADDR']) ?></div>
									<div class="text-muted small mt-1"><?php echo $DiffUltimoAcesso ?> </div>
								</div>
							</div>
						</a>
						<a href="#" class="list-group-item">
							<div class="row g-0 align-items-center">
								<div class="col-2">
									<i class="text-success" data-feather="user-plus"></i>
								</div>
								<div class="col-10">
									<div class="text-dark">Nova conexão</div>
									<div class="text-muted small mt-1">Christina aceitou seu convite.</div>
									<div class="text-muted small mt-1">14 horas atrás </div>
								</div>
							</div>
						</a>
					</div>
					<div class="dropdown-menu-footer">
						<a href="#" class="text-muted">Mostrar todas as notificações</a>
					</div>
				</div>
			</li>
			<li class="nav-item dropdown">
				<!-- <a class="nav-icon dropdown-toggle" href="#" id="messagesDropdown" data-bs-toggle="dropdown">
					<div class="position-relative">
						<i class="align-middle" data-feather="message-square"></i>
					</div>
				</a> -->
				<div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0" aria-labelledby="messagesDropdown">
					<div class="dropdown-menu-header">
						<div class="position-relative">
							4 New Messages
						</div>
					</div>
					<div class="list-group">
						<a href="#" class="list-group-item">
							<div class="row g-0 align-items-center">
								<div class="col-2">
									<img src="<?php echo $_SESSION['BASE_URL']; ?>/assets/img/avatars/avatar-5.jpg" class="avatar img-fluid rounded-circle" alt="">
								</div>
								<div class="col-10 ps-2">
									<div class="text-dark">Vanessa Tucker</div>
									<div class="text-muted small mt-1">Nam pretium turpis et arcu. Duis arcu tortor.</div>
									<div class="text-muted small mt-1">15m ago</div>
								</div>
							</div>
						</a>
						<a href="#" class="list-group-item">
							<div class="row g-0 align-items-center">
								<div class="col-2">
									<img src="<?php echo $_SESSION['BASE_URL']; ?>/assets/img/avatars/avatar-2.jpg" class="avatar img-fluid rounded-circle" alt="William Harris">
								</div>
								<div class="col-10 ps-2">
									<div class="text-dark">William Harris</div>
									<div class="text-muted small mt-1">Curabitur ligula sapien euismod vitae.</div>
									<div class="text-muted small mt-1">2h ago</div>
								</div>
							</div>
						</a>
						<a href="#" class="list-group-item">
							<div class="row g-0 align-items-center">
								<div class="col-2">
									<img src="<?php echo $_SESSION['BASE_URL']; ?>/assets/img/avatars/avatar-4.jpg" class="avatar img-fluid rounded-circle" alt="Christina Mason">
								</div>
								<div class="col-10 ps-2">
									<div class="text-dark">Christina Mason</div>
									<div class="text-muted small mt-1">Pellentesque auctor neque nec urna.</div>
									<div class="text-muted small mt-1">4h ago</div>
								</div>
							</div>
						</a>
						<a href="#" class="list-group-item">
							<div class="row g-0 align-items-center">
								<div class="col-2">
									<img src="<?php echo $_SESSION['BASE_URL']; ?>/assets/img/avatars/avatar-3.jpg" class="avatar img-fluid rounded-circle" alt="Sharon Lessman">
								</div>
								<div class="col-10 ps-2">
									<div class="text-dark">Sharon Lessman</div>
									<div class="text-muted small mt-1">Aenean tellus metus, bibendum sed, posuere ac, mattis non.</div>
									<div class="text-muted small mt-1">5h ago</div>
								</div>
							</div>
						</a>
					</div>
					<div class="dropdown-menu-footer">
						<a href="#" class="text-muted">Mostrar todas as mensagens</a>
					</div>
				</div>
			</li>
			<li class="nav-item dropdown">
				<a class="nav-icon dropdown-toggle d-inline-block d-sm-none" href="#" data-bs-toggle="dropdown">
					<i class="align-middle" data-feather="settings"></i>
				</a>

				<a class="nav-link dropdown-toggle d-none d-sm-inline-block" href="#" data-bs-toggle="dropdown">
					<img src="<?php echo $_SESSION['BASE_URL'] ?>/assets/img/fotos/<?php echo $_SESSION['Foto']; ?>" class="rounded-circle img-cover" width="40px" height="40px" alt="" /> <span class="text-dark"><?php echo $_SESSION['Nome']; ?></span>
				</a>
				<div class="dropdown-menu dropdown-menu-end">
					<a class="dropdown-item" href="<?php echo $_SESSION['BASE_URL']; ?>/app/perfil/perfilController.php"><i class="align-middle me-1" data-feather="user"></i> Perfil</a>
					<a class="dropdown-item" href="<?php echo $_SESSION['BASE_URL']; ?>/index.php"><i class="align-middle me-1" data-feather="pie-chart"></i> Analytics</a>
					<div class="dropdown-divider"></div>
					<a class="dropdown-item" href="<?php echo $_SESSION['BASE_URL']; ?>/app/config/formConfig.php"><i class="align-middle me-1" data-feather="settings"></i> Configurações</a>
					<a class="dropdown-item" href="<?php echo $_SESSION['BASE_URL']; ?>/app/backup/backup.php"><i class="align-middle me-1" data-feather="download"></i> Backup dados</a>
					<a class="dropdown-item" href="#" onclick="alternarFullscreen(); return false;">
						<i class="align-middle me-1" data-feather="maximize"></i> Alternar tela cheia
					</a>
					<!-- <a class="dropdown-item" href="#"><i class="align-middle me-1" data-feather="help-circle"></i> Central de ajuda</a> -->
					<div class="dropdown-divider"></div>
					<a class="dropdown-item" href="<?php echo $_SESSION['BASE_URL']; ?>/logout.php">Sair</a>
				</div>
			</li>
		</ul>
	</div>
</nav>
<script>
	function alternarFullscreen() {
		if (!document.fullscreenElement) {
			document.documentElement.requestFullscreen().catch(err => {
				console.warn(`Erro ao tentar entrar em tela cheia: ${err.message}`);
			});
		} else {
			document.exitFullscreen();
		}
	}
</script>
