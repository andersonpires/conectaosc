<?php
require_once $_SESSION['BASE_PATH'] . '/checa-token.php';
date_default_timezone_set('America/Sao_Paulo');
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<?php
	require_once $_SESSION['BASE_PATH'] . '/template/header.php';
	?>

<body>
	<div class="wrapper">
		<?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>

		<div class="main">
			<?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php'; ?>
			<main class="content">


				[codigo aqui]

			</main>
			<footer class="footer">
				<?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php' ?>
			</footer>
		</div>
	</div>

	<script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>
</body>

</html>