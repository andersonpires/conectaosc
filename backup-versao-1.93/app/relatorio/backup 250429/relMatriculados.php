<?php
// Inicialização da sessão e configuração de timezone
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400); // 24 horas
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

// Verifica se as variáveis de sessão BASE_PATH e BASE_URL estão definidas
if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    // Salva a URL atual para redirecionar o usuário após o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endereço atual
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url"); // Redireciona para o login com o endereço de volta via GET
    exit(); // Garante que o código abaixo não será executado
}

require_once $_SESSION['BASE_PATH'] . '/checa-token.php';
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $_SESSION['BASE_PATH'] . '/template/header.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            border: 1px solid #000 !important;
        }

        thead {
            text-align: center;
            color: white;
        }

        th,
        td {
            border: 1px solid #000 !important;
            padding: 10px;
            text-align: center !important;
            vertical-align: middle;
        }

        th {
            background-color: rgb(83, 37, 126) !important;

        }

        tr:nth-child(even) {
            background-color: rgb(205, 241, 213);
        }

        tr:nth-child(odd) {
            background-color: #fff;
        }

        .header2 {
            margin-bottom: 20px;
            text-align: center;
        }

        .header2 div {
            margin: 5px 0;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>

        <div class="main">
            <?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php';

            // Consulta os cursos e turmas disponíveis
            $stmt = $pdo->query("SELECT DISTINCT IdCurso, NomeCurso FROM tbCurso ORDER BY NomeCurso");
            $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>
            <main class="content">
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3">Gerar Relatório de Matriculados</h1>
                    <div class="col-12 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <form id="filterForm">
                                    <div class="mb-3">
                                        <label for="curso" class="form-label">Curso:</label>
                                        <select id="curso" name="curso" class="form-select">
                                            <option value="">Selecione o Curso</option>
                                            <?php foreach ($cursos as $curso): ?>
                                                <option value="<?= $curso['IdCurso'] ?>"><?= $curso['NomeCurso'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="turma" class="form-label">Turma:</label>
                                        <select id="turma" name="turma" class="form-select">
                                            <option value="">Selecione um curso primeiro</option>
                                        </select>
                                    </div>

                                    <button type="button" id="gerarRelatorio" class="btn btn-primary">Gerar Relatório</button>
                                    <button type="button" id="salvarPDF" style="display: none; " class="btn btn-primary">Salvar PDF</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="relatorio">

                </div>

            </main>
            <footer class="footer">
                <?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php'; ?>
            </footer>

            <script>
                $('#gerarRelatorio').click(function(e) {
                    e.preventDefault(); // Evita recarregar a página

                    const curso = $('#curso').val();
                    const turma = $('#turma').val();
                    console.log({
                        curso,
                        turma
                    });
                    let getMatricula = "<?php echo $_SESSION['BASE_URL'] ?>/get/getMatricula.php";
                    $.post(getMatricula, {
                        curso,
                        turma
                    }, function(data) {
                        $('#relatorio').empty(); // Limpa a tabela antiga
                        console.log('Dados recebidos:', data);
                        // Cria a tabela dinamicamente
                        let table = `
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ordem</th>
                            <th>Matriculados</th>
                            <th>Nascimento</th>
                            <th>Endereço</th>
                            <th>Cidade</th>
                            <th>Estado</th>
                            <th>UF</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

                        // Preenche as linhas da tabela com os dados do JSON
                        data.forEach((row, index) => {
                            table += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${row.Nome}</td>
                                <td>${row.Nascimento}</td>
                                <td>${row.Endereco}</td>
                                <td>${row.Cidade}</td>
                                <td>${row.Bairro}</td>
                                <td>${row.UF}</td>
                            </tr>
                        `;
                        });

                        table += `
                    </tbody>
                </table>
            `;

                        $('#relatorio').html(table); // Insere a nova tabela

                        $('#salvarPDF').show(); // Exibe o botão para salvar em PDF
                    }, 'json').fail(function() {
                        alert('Erro ao carregar os dados.');
                    });

                });

                $('#salvarPDF').click(function(e) {
                    e.preventDefault(); // Impede a recarga da página

                    const curso = $('#curso').val();
                    const turma = $('#turma').val();
                    let getPDF = "<?php echo $_SESSION['BASE_URL'] ?>/get/";
                    // Abre o PDF em uma nova aba diretamente
                    const url = getPDF + `getMatriculaPDF.php?curso=${curso}&turma=${turma}`;
                    window.open(url, '_blank');
                });
            </script>

</body>
<script>
    async function carregarTurmas(idCurso) {
        const turmaSelect = document.getElementById('turma');
        turmaSelect.innerHTML = '<option value="">Carregando turmas...</option>';

        if (!idCurso) {
            turmaSelect.innerHTML = '<option value="">Selecione um curso primeiro</option>';
            return;
        }

        try {
            const formData = new FormData();
            formData.append('IdCurso', idCurso);
            let getTurmas = "<?php echo $_SESSION['BASE_URL'] ?>/get/getTurmas.php";
            const response = await fetch(getTurmas, {
                method: 'POST',
                body: formData,
            });

            const turmasHtml = await response.text();
            turmaSelect.innerHTML = turmasHtml;
        } catch (error) {
            turmaSelect.innerHTML = '<option value="">Erro ao carregar turmas</option>';
            console.error('Erro ao carregar turmas:', error);
        }
    }
</script>
<script>
    $('#curso').change(function() {
        const idCurso = $(this).val();
        const turmaSelect = $('#turma');
        turmaSelect.html('<option value="">Carregando turmas...</option>');

        if (!idCurso) {
            turmaSelect.html('<option value="">Selecione um curso primeiro</option>');
            return;
        }
        let getTurmas = "<?php echo $_SESSION['BASE_URL'] ?>/get/getTurmas.php";
        $.post(getTurmas, {
            IdCurso: idCurso
        }, function(data) {
            turmaSelect.html(data);
        }).fail(function() {
            turmaSelect.html('<option value="">Erro ao carregar turmas</option>');
        });
    });
</script>

</html>