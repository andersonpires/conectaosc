<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<?php
	require_once $BASE_para_PATH . '/app/views/partials/header.php';
	?>

<body>
	<div class="wrapper">
		<?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

		<div class="main">
			<?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
			<main class="content">


				[codigo aqui]

			</main>
			<footer class="footer">
				<?php require_once $BASE_para_PATH . '/app/views/partials/footer.php' ?>
			</footer>
		</div>
	</div>

	<script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>
</body>

</html>

