<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php
    include 'header.php';
    $gp = "graficoProfessor.php?v=" . time();
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráfico de Respostas</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
</head>

<body>
    <div class="wrapper">
        <?php include 'menuprofessor.php'; ?>

        <div class="main">
            <?php
            include 'topoinscrito.php';
            ?>
            <script src="js/app.js"></script>
            <div class="container mt-5">
                <h1 class="mb-4">Gráficos de Respostas dos Professores</h1>
                <main class="content">

                    <?php
                    include 'conexao_grava.php';

                    // Consulta para buscar os dados das perguntas e respostas
                    $sqlPerguntas = "SELECT IdPergunta, TextoPergunta FROM tbap_perguntas ORDER BY IdPergunta";
                    $resultPerguntas = mysqli_query($conexao_grava, $sqlPerguntas);

                    if ($resultPerguntas && mysqli_num_rows($resultPerguntas) > 0) {
                        while ($pergunta = mysqli_fetch_assoc($resultPerguntas)) {
                            $idPergunta = $pergunta['IdPergunta'];
                            $textoPergunta = $pergunta['TextoPergunta'];

                            // Consulta para buscar a contagem de respostas por opção para cada pergunta
                            $sqlRespostas = "
                    SELECT o.TextoOpcao, COUNT(r.IdOpcao) as Total
                    FROM tbap_respostas r
                    JOIN tbap_opcoesresposta o ON r.IdOpcao = o.IdOpcao
                    WHERE r.IdPergunta = ?
                    GROUP BY o.IdOpcao
                    ORDER BY o.IdOpcao
                ";
                            $stmt = mysqli_prepare($conexao_grava, $sqlRespostas);
                            mysqli_stmt_bind_param($stmt, 'i', $idPergunta);
                            mysqli_stmt_execute($stmt);
                            $resultRespostas = mysqli_stmt_get_result($stmt);

                            $labels = [];
                            $data = [];
                            while ($resposta = mysqli_fetch_assoc($resultRespostas)) {
                                $labels[] = $resposta['TextoOpcao'];
                                $data[] = $resposta['Total'];
                            }

                            // Serializa os dados para uso no JavaScript
                            $chartLabels = json_encode($labels);
                            $chartData = json_encode($data);
                    ?>

                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Pergunta: <?php echo htmlspecialchars($textoPergunta); ?></h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="position: relative; height:300px; width:100%;">
                                        <canvas id="chart-<?php echo $idPergunta; ?>"></canvas>
                                    </div>
                                    <script>
                                        document.addEventListener("DOMContentLoaded", function() {
                                            new Chart(document.getElementById("chart-<?php echo $idPergunta; ?>"), {
                                                type: "pie",
                                                data: {
                                                    labels: <?php echo $chartLabels; ?>,
                                                    datasets: [{
                                                        data: <?php echo $chartData; ?>,
                                                        backgroundColor: [
                                                            "#007bff",
                                                            "#ffc107",
                                                            "#28a745",
                                                            "#dc3545",
                                                            "#6f42c1",
                                                            "#fd7e14"
                                                        ],
                                                        borderWidth: 1
                                                    }]
                                                },
                                                options: {
                                                    responsive: true,
                                                    maintainAspectRatio: false,
                                                    plugins: {
                                                        legend: {
                                                            position: 'top'
                                                        }
                                                    }
                                                }
                                            });
                                        });
                                    </script>
                                </div>
                            </div>

                    <?php
                        }
                    } else {
                        echo "<p>Nenhuma pergunta encontrada.</p>";
                    }
                    ?>
            </div>
            </main>
            <footer class="footer">
                <?php include 'footer.php' ?>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>