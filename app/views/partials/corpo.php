<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
// Inclui o arquivo de conexao PDO (deve definir a variavel $pdo)
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

// Inicializa o array com todos os meses zerados
$data = array_fill(0, 12, 0);

// Primeira consulta: obtem contagens de registros em diversas tabelas
$sql = "
    SELECT
        (SELECT COUNT(*) FROM tbAluno WHERE Habilitado = 1) AS totalAlunos,
        (SELECT COUNT(*) FROM tbCurso) AS totalCursos,
        (SELECT COUNT(*) FROM tbMatricula) AS totalMatriculas,
		(SELECT COUNT(*) FROM tbMatricula WHERE Habilitado = 1) AS totalMatriculasAtivas,
        (SELECT COUNT(*) FROM tbTurma) AS totalTurmas
";

try {
	// Prepara e executa a consulta
	$stmt = $pdo->prepare($sql);
	$stmt->execute();

	// Busca o resultado
	if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$totalAlunos    = $row['totalAlunos'] > 0 ? $row['totalAlunos'] : "Sem registros";
		$totalCursos    = $row['totalCursos'] > 0 ? $row['totalCursos'] : "Sem registros";
		$totalMatriculas = $row['totalMatriculas'] > 0 ? $row['totalMatriculas'] : "Sem registros";
		$totalMatriculasAtivas = $row['totalMatriculasAtivas'] > 0 ? $row['totalMatriculasAtivas'] : "Sem registros";
		$totalTurmas    = $row['totalTurmas'] > 0 ? $row['totalTurmas'] : "Sem registros";
	} else {
		// Caso nao retorne resultados, inicializa com "Sem registros"
		$totalAlunos = $totalCursos = $totalMatriculas = $totalTurmas = "Sem registros";
	}

	// Segunda consulta: obtem a contagem de aulas realizadas por mes
	$sql2 = "
        SELECT 
            Mes,
            COUNT(DISTINCT CONCAT(Dia, Mes, Ano, IdTurma, IdCurso)) AS totalAulas
        FROM 
            tbChamada
        GROUP BY 
            Mes
        ORDER BY 
            Mes
    ";

	$stmt2 = $pdo->prepare($sql2);
	$stmt2->execute();

	// Preenche o array $data com os totais de aulas, ajustando o indice (mes - 1)
	while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
		$data[$row['Mes'] - 1] = $row['totalAulas'];
	}

	// Garante que todos os meses estejam preenchidos, mesmo que sem registros
	for ($i = 0; $i < 12; $i++) {
		if (!isset($data[$i])) {
			$data[$i] = 0;
		}
	}

	// Converte os dados do array para JSON para uso no JavaScript
	$jsonData = json_encode(array_values($data));
} catch (PDOException $e) {
	die("Erro na consulta SQL: " . $e->getMessage());
}

$sql3 = "
        SELECT 
            MONTH(STR_TO_DATE(vData, '%d/%m/%Y')) AS mes,
            YEAR(STR_TO_DATE(vData, '%d/%m/%Y')) AS ano,
            COUNT(*) AS total
        FROM tbMatricula
        WHERE STR_TO_DATE(vData, '%d/%m/%Y') >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
        GROUP BY ano, mes
        ORDER BY ano DESC, mes ASC
    ";

$stmt3 = $pdo->prepare($sql3);
$stmt3->execute();
$resultados_Matricula = $stmt3->fetchAll(PDO::FETCH_ASSOC);

// Criando um array para armazenar os dados do grafico
$dados = array_fill(1, 12, 0); // Inicializa todos os meses com 0
$total_Matricula = 0;
// Preenchendo o array com os valores retornados do banco
foreach ($resultados_Matricula as $row) {
	$mes = intval($row['mes']);
	$dados[$mes] = intval($row['total']);
	$total_Matricula = $total_Matricula + $row['total'];
}

$dadosFormatados = [];
for ($i = 1; $i <= 12; $i++) {
	$dadosFormatados[] = $dados[$i]; // Mantem a ordem fixa de Janeiro a Dezembro
}

$query = "
    SELECT a.Nome AS NomeAluno, c.NomeCurso, t.NomeTurma, a.Nascimento, a.WhatsApp, a.Foto, u.Nome
    FROM tbMatricula m
    INNER JOIN tbAluno a ON m.IdUsuario = a.IdUsuario
    INNER JOIN tbCurso c ON m.IdCurso = c.IdCurso
    INNER JOIN tbTurma t ON m.IdTurma = t.IdTurma
	INNER JOIN tbUser u ON a.IdColaboradorAlt = u.IdColaborador
    ORDER BY m.IdMatricula DESC
    LIMIT 6
";
$matriculas = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

?>
<main class="content">
	<!-- Área para mensagens de boas-vindas ou novidades -->
	<!-- <h2 class="h4">Oie, temos mais novidade, <strong><?php echo $_SESSION['Nome'] ?> </strong> <strong></strong></h2>
	<h7 class="h7 mb-3">Agora, você pode ver os aniversariantes do mês! Ah, e se alguém faz ou fez aniversário por esses dias, ele aparece destacado em amarelo.</h7>
	<br><br> -->


	<div class="container-fluid p-0">

		<!-- <h1 class="h3 mb-3">Painel de <strong>Analise</strong> </h1> -->

		<div class="row">
			<div class="col-xl-6 col-xxl-5 d-flex">
				<div class="w-100">
					<div class="row">
						<div class="col-sm-6">
							<div class="card">
								<div class="card-body">
									<div class="row">
										<div class="col mt-0">
											<h5 class="card-title">Total de beneficiarios ativos cadastrados</h5>
										</div>
										<div class="col-auto">
											<div class="stat text-primary">
												<i class="align-middle" data-feather="user-check"></i>
											</div>
										</div>
									</div>
									<h1 class="mt-1 mb-3"><?php echo $totalAlunos; ?></h1>
								</div>
							</div>
							<div class="card">
								<div class="card-body">
									<div class="row">
										<div class="col mt-0">
											<h5 class="card-title">Cursos ofertados</h5>
										</div>
										<div class="col-auto">
											<div class="stat text-primary">
												<i class="align-middle" data-feather="award"></i>
											</div>
										</div>
									</div>
									<h1 class="mt-1 mb-3"><?php echo $totalCursos; ?></h1>
								</div>
							</div>
						</div>
						<div class="col-sm-6">
							<div class="card">
								<div class="card-body">
									<div class="row">
										<div class="col mt-0">
											<h5 class="card-title">Matriculas ativas / Total matriculados</h5>
										</div>
										<div class="col-auto">
											<div class="stat text-primary">
												<i class="align-middle" data-feather="file"></i>
											</div>
										</div>
									</div>
									<h1 class="mt-1 mb-3"><?php echo $totalMatriculasAtivas; ?> / <?php echo $totalMatriculas; ?></h1>
								</div>
							</div>
							<div class="card">
								<div class="card-body">
									<div class="row">
										<div class="col mt-0">
											<h5 class="card-title">Turmas abertas</h5>
										</div>
										<div class="col-auto">
											<div class="stat text-primary">
												<i class="align-middle" data-feather="users"></i>
											</div>
										</div>
									</div>
									<h1 class="mt-1 mb-3"><?php echo $totalTurmas; ?></h1>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="col-xl-6 col-xxl-7">
				<div class="card flex-fill w-100">
					<div class="card-header">
						<h5 class="card-title mb-0">Aulas realizadas</h5>
					</div>
					<div class="card-body py-3">
						<div class="chart chart-sm">
							<canvas id="chartjs-dashboard-line"></canvas>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-12 col-md-6 col-xxl-3 d-flex order-2 order-xxl-3">
				<div class="card flex-fill w-100">
					<div class="card-header">

						<h5 class="card-title mb-0">Beneficiarios <?php echo date("Y") . ": " .  $Total . " no total"; ?></h5>
					</div>
					<div class="card-body d-flex">
						<div class="align-self-center w-100">
							<div class="py-3">
								<div class="chart chart-xs">
									<canvas id="chartjs-dashboard-pie"></canvas>
								</div>
							</div>

							<table class="table mb-0">
								<tbody>
									<tr>
										<td><span class="badge bg-primary">Infantojuvenil</span></td>
										<td class="text-end"><?php echo $Infantojuvenil ?></td>
									</tr>
									<tr>
										<td><span class="badge bg-warning">Idosos</span></td>
										<td class="text-end"><?php echo $Idosos ?></td>
									</tr>
									<tr>
										<td><span class="badge bg-success">Outros</span></td>
										<td class="text-end"><?php echo $Outros ?></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
			<div class="col-12 col-md-12 col-xxl-6 d-flex order-3 order-xxl-2">
				<div class="card flex-fill w-100">
					<div class="card-header">

						<h5 class="card-title mb-0">Qtd Matriculas Ultimos 12 meses - <?php echo $total_Matricula ?> no total</h5>
					</div>
					<div class="card-body d-flex w-100">
						<div class="align-self-center chart chart-lg">
							<canvas id="chartjs-dashboard-bar"></canvas>
						</div>
					</div>
				</div>
			</div>
			<div class="col-12 col-md-6 col-xxl-3 d-flex order-1 order-xxl-1">
				<div class="card flex-fill">
					<div class="card-header">

						<h5 class="card-title mb-0">Calendario</h5>
					</div>
					<div class="card-body d-flex">
						<div class="align-self-center w-100">
							<div class="chart">
								<div id="datetimepicker-dashboard"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- <div class="row">
			<div class="col-12 col-lg-8 col-xxl-9 d-flex">
				<div class="card flex-fill">
					<div class="card-header">
						<h5 class="card-title mb-0">Ultimos Beneficiarios Matriculados</h5>
					</div>
					<table class="table table-hover my-0">
						<thead>
							<tr>
								<th>Foto</th>
								<th>Nome</th>
								<th>WhatsApp</th>
								<th>Nascimento</th>
								<th class="d-none d-xl-table-cell">Curso</th>
								<th class="d-none d-xl-table-cell">Turma</th>
								<th class="d-none d-xl-table-cell">Cadastrado por:</th>
							</tr>
						</thead>
						<tbody>
							<?php //foreach ($matriculas as $linha): ?>
								<tr>
									<td><img src="<?php //echo $BASE_para_URL; ?>/assets/img/fotos/<?php //echo $linha['Foto']; ?>" id="fotoMatriculado" class="rounded-circle img-cover fotografia" width="30px" height="30px"></td>
									<td><?php //echo htmlspecialchars($linha['NomeAluno']); ?></td>
									<td class="d-none d-xl-table-cell"><?php //echo htmlspecialchars($linha['WhatsApp']); ?></td>
									<td class="d-none d-xl-table-cell"><?php //echo htmlspecialchars($linha['Nascimento']); ?></td>
									<td class="d-none d-xl-table-cell"><?php //echo htmlspecialchars($linha['NomeCurso']); ?></td>
									<td class="d-none d-xl-table-cell"><?php //echo htmlspecialchars($linha['NomeTurma']); ?></td>
									<td class="d-none d-xl-table-cell"><?php //echo htmlspecialchars($linha['Nome']); ?></td>
								</tr>
							<?php //endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

			<div class="col-12 col-lg-4 col-xxl-3 d-flex">
				<div class="card flex-fill w-100">
					<div class="card-header">

						<h5 class="card-title mb-0">Qtd Matriculas Ultimos 12 meses - <?php //echo $total_Matricula ?> no total</h5>
					</div>
					<div class="card-body d-flex w-100">
						<div class="align-self-center chart chart-lg">
							<canvas id="chartjs-dashboard-bar"></canvas>
						</div>
					</div>
				</div>
			</div>
		</div> -->

	</div>
</main>


