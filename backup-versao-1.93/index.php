<?php
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400); // 24 horas
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/config.php';

// Excluir o cookie de login antigo, caso exista
if (isset($_COOKIE['login_remember'])) {
    setcookie('login_remember', '', time() - 3600, "/");
    setcookie('login_remember', '', time() - 3600, "/", "", false, false);
	if (isset($_SESSION['BASE_PATH']) || isset($_SESSION['BASE_URL'])) {
		unset($_SESSION['BASE_PATH']);
		unset($_SESSION['BASE_URL']);
	}
}

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
	// Salva a URL atual para redirecionar o usuário após o login
	//$redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endereço atual
	//header("Location: https://www.iteva.com.br/conectaosc/login.php?redirect=$redirect_url"); // Redireciona para o login com o endereço de volta via GET
	$redirect_url = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : BASE_URL . "/login.php";
	header("Location: " . $redirect_url);
	exit(); // Garante que o código abaixo não será executado
}


if (isset($_GET['erro']) && $_GET['erro'] !== '') {
	echo "<script>alert('" . $_GET['erro'] . "');</script>";
}
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

// Consulta SQL para pegar os dados da view
$sql = "SELECT * FROM vwFaixaetaria";

try {
	// Prepara e executa a consulta
	$stmt = $pdo->prepare($sql);
	$stmt->execute();

	// Como a consulta retorna apenas uma linha, usamos fetch()
	$row = $stmt->fetch();

	// Array para armazenar os dados do gráfico
	$data = [];

	if ($row) {
		$data = [
			$Infantojuvenil = (int)$row['Infantojuvenil'],
			$Idosos = (int)$row['60+'],
			$Outros = (int)$row['Outros']
		];
		$Total = (int)$row['Total'];
	}
} catch (PDOException $e) {
	die("Erro na consulta SQL: " . $e->getMessage());
}

// Gera os dados em formato JSON para serem usados no gráfico
$chartData = json_encode($data);
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
			<?php
			require_once $_SESSION['BASE_PATH'] . '/template/topo.php';
			?>

			<?php require_once $_SESSION['BASE_PATH'] . '/template/corpo.php'; ?>

			<footer class="footer">
				<?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php' ?>
			</footer>
		</div>
	</div>

	<script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>

	<!-- Gráfico em linha Aulas realizadas -->
	<script>
		document.addEventListener("DOMContentLoaded", function() {
			var ctx = document.getElementById("chartjs-dashboard-line").getContext("2d");
			var gradient = ctx.createLinearGradient(0, 0, 0, 225);
			gradient.addColorStop(0, "rgba(215, 227, 244, 1)");
			gradient.addColorStop(1, "rgba(215, 227, 244, 0)");
			// Line chart
			new Chart(document.getElementById("chartjs-dashboard-line"), {
				type: "line",
				data: {
					labels: ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez"],
					datasets: [{
						label: "Aulas Distintas",
						fill: true,
						backgroundColor: gradient,
						borderColor: window.theme.primary,
						data: <?php echo $jsonData; ?>
					}]
				},
				options: {
					maintainAspectRatio: false,
					legend: {
						display: false
					},
					tooltips: {
						intersect: false
					},
					hover: {
						intersect: true
					},
					plugins: {
						filler: {
							propagate: false
						}
					},
					scales: {
						xAxes: [{
							reverse: true,
							gridLines: {
								color: "rgba(0,0,0,0.0)"
							}
						}],
						yAxes: [{
							ticks: {
								stepSize: 10
							},
							display: true,
							borderDash: [3, 3],
							gridLines: {
								color: "rgba(0,0,0,0.0)"
							}
						}]
					}
				}
			});
		});
	</script>

	<!-- Gráfico pizza Faixa etária -->
	<script>
		document.addEventListener("DOMContentLoaded", function() {
			// Pie chart
			new Chart(document.getElementById("chartjs-dashboard-pie"), {
				type: "pie",
				data: {
					labels: ["Infantojuvenil", "60+", "Outros"],
					datasets: [{
						data: <?php echo $chartData; ?>,
						backgroundColor: [
							window.theme.primary,
							window.theme.warning,
							window.theme.success
						],
						borderWidth: 5
					}]
				},
				options: {
					responsive: !window.MSInputMethodContext,
					maintainAspectRatio: false,
					legend: {
						display: false
					},
					cutoutPercentage: 75
				}
			});
		});
	</script>

	<!-- Gráfico em barra -->
	<script>
		document.addEventListener("DOMContentLoaded", function() {
			// Dados organizados corretamente para o gráfico
			var dadosPHP = <?php echo json_encode($dadosFormatados); ?>;

			// Criando o gráfico com os meses na ordem correta
			new Chart(document.getElementById("chartjs-dashboard-bar"), {
				type: "bar",
				data: {
					labels: ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez"],
					datasets: [{
						label: "Registros",
						backgroundColor: window.theme.primary,
						borderColor: window.theme.primary,
						hoverBackgroundColor: window.theme.primary,
						hoverBorderColor: window.theme.primary,
						data: dadosPHP,
						barPercentage: .75,
						categoryPercentage: .5
					}]
				},
				options: {
					maintainAspectRatio: false,
					legend: {
						display: false
					},
					scales: {
						yAxes: [{
							gridLines: {
								display: false
							},
							stacked: false,
							ticks: {
								stepSize: 20
							}
						}],
						xAxes: [{
							stacked: false,
							gridLines: {
								color: "transparent"
							}
						}]
					}
				}
			});
		});
	</script>

	<!-- Mapa -->
	<script>
		document.addEventListener("DOMContentLoaded", function() {
			var markers = [{
					coords: [-3.9075554, -38.3843327],
					name: "Aquiraz"
				},
				{
					coords: [28.704060, 77.102493],
					name: "Delhi"
				},
				{
					coords: [6.524379, 3.379206],
					name: "Lagos"
				},
				{
					coords: [35.689487, 139.691711],
					name: "Tokyo"
				},
				{
					coords: [23.129110, 113.264381],
					name: "Guangzhou"
				},
				{
					coords: [40.7127837, -74.0059413],
					name: "New York"
				},
				{
					coords: [34.052235, -118.243683],
					name: "Los Angeles"
				},
				{
					coords: [41.878113, -87.629799],
					name: "Chicago"
				},
				{
					coords: [51.507351, -0.127758],
					name: "London"
				},
				{
					coords: [40.416775, -3.703790],
					name: "Madrid "
				}
			];
			var map = new jsVectorMap({
				map: "world",
				selector: "#world_map",
				zoomButtons: true,
				markers: markers,
				markerStyle: {
					initial: {
						r: 9,
						strokeWidth: 7,
						stokeOpacity: .4,
						fill: window.theme.primary
					},
					hover: {
						fill: window.theme.primary,
						stroke: window.theme.primary
					}
				},
				zoomOnScroll: true
			});
			window.addEventListener("resize", () => {
				map.updateSize();
			});
		});
	</script>
	<!-- Calendario -->
	<script>
		document.addEventListener("DOMContentLoaded", function() {
			var date = new Date(Date.now()); //- 5 * 24 * 60 * 60 * 1000
			var defaultDate = date.getUTCFullYear() + "-" + (date.getUTCMonth() + 1) + "-" + date.getUTCDate();
			document.getElementById("datetimepicker-dashboard").flatpickr({
				inline: true,
				prevArrow: "<span title=\"Previous month\">&laquo;</span>",
				nextArrow: "<span title=\"Next month\">&raquo;</span>",
				defaultDate: defaultDate
			});
		});
	</script>
	<script>
		feather.replace();
	</script>
</body>


</html>