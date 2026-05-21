<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$appJsVersion = @filemtime($BASE_para_PATH . '/app/assets/js/app.js') ?: time();
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
} // 24 horas

// Verifica se as variáveis de sessão BASE_para_PATH e BASE_para_URL estão definidas
if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    // Salva a URL atual para redirecionar o usuário após o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endereço atual
    header("Location: " . rtrim((string) ($BASE_para_URL ?? ''), '/') . "/login/?redirect=$redirect_url");
    exit(); // Garante que o código abaixo não será executado
}
require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <style>
        .hover-container {
            display: inline-block;
            position: relative;
        }

        .hover-img {
            transition: transform 0.3s ease;
        }

        .hover-container:hover .hover-img {
            transform: scale(8);
            z-index: 1;
            position: absolute;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>
        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
            <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>
            <script src="https://npmcdn.com/flatpickr/dist/flatpickr.min.js"></script>
            <script src="https://npmcdn.com/flatpickr/dist/l10n/pt.js"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var date = new Date(Date.now() - 0 * 24 * 60 * 60 * 1000);
                    var defaultDate = date.getUTCFullYear() + "-" + (date.getUTCMonth() + 1) + "-" + date.getDate();
                    localStorage.setItem("diaSelecionado", formatDate(defaultDate));

                    function formatDate(dateStr) {
                        const dateObj = new Date(dateStr);
                        const day = dateObj.getDate().toString().padStart(2, "0");
                        const month = (dateObj.getMonth() + 1).toString().padStart(2, "0");
                        const year = dateObj.getFullYear();
                        return `${year}-${month}-${day}`;
                    }

                    document.getElementById("datetimepicker-dashboard").flatpickr({
                        locale: "pt",
                        inline: true,
                        prevArrow: "<span title=\"Mes anterior\">&laquo;</span>",
                        nextArrow: "<span title=\"Proximo mes\">&raquo;</span>",
                        defaultDate: defaultDate,
                        onChange: function(selectedDates) {
                            const selectedDay = selectedDates[0];
                            const diaSelecionado = formatDate(selectedDay);
                            localStorage.setItem("diaSelecionado", diaSelecionado);
                        }
                    });
                });
            </script>
            <main class="content">
                <div class="container-fluid p-0">

                    <div class="mb-3">
                        <h1 class="h3 d-inline align-middle" id="titulo">Verifique os alunos e selecione o curso</h1>
                    </div>
                    <div class="row">
                        <div class="col-md-4 col-xl-3">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="h6 card-title">Alunos</h5>
                                    <ul class="list-unstyled mb-0">
                                        <?php
                                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                                            if (isset($_POST['checkbox']) && is_array($_POST['checkbox']) && count($_POST['checkbox']) > 1) {
                                                $IdsBeneficiarios = implode(',', ($_POST['checkbox']));
                                                $TotalBenSelecionados = count($_POST['checkbox']);
                                                $sql = "SELECT * FROM tbAluno WHERE IdUsuario IN ($IdsBeneficiarios) ORDER BY Nome ASC";
                                                $jsonDados = json_encode($IdsBeneficiarios);
                                            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                                                if (isset($_POST['checkbox']) && is_array($_POST['checkbox']) && count($_POST['checkbox']) == 1) {
                                                    foreach ($_POST['checkbox'] as $idDoacao) {
                                                        $IdsBeneficiarios = $idDoacao;
                                                    }
                                                    $TotalBenSelecionados = '1';
                                                    $sql = "SELECT * FROM tbAluno WHERE IdUsuario = $IdsBeneficiarios ORDER BY Nome ASC";
                                                    $jsonDados = json_encode($IdsBeneficiarios);
                                                }
                                            }
                                        } else {
                                            echo "Método inválido. Por favor, envie os dados via POST.";
                                        }

                                        $busca = $pdo->query($sql);
                                        while ($dados = $busca->fetch(PDO::FETCH_ASSOC)) {
                                            $Foto = $dados['Foto'];
                                            $Nome = $dados['Nome'];
                                        ?>
                                            <li class="mb-1 hover-container"> <span>
                                                    <img src="<?php echo htmlspecialchars(bootstrap_foto_url((string)$Foto), ENT_QUOTES, 'UTF-8'); ?>" class="rounded-circle img-cover hover-img" width='20px' height='20px'>
                                                </span> <?php echo $Nome ?></li>
                                            <p></p>
                                        <?php } ?>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <h5 class="h6 card-title">Total de pessoas a matricular</h5>
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-1"><?php echo $TotalBenSelecionados ?> pessoa(s)</li>
                                    </ul>
                                </div>
                                <hr class="my-0" />
                                <div class="card-body">
                                    <div class="order-xxl-1">
                                        <div class="card flex-fill">
                                            <div class="card-header">
                                                <h5 class="card-title mb-0">Selecione a data da matrícula:</h5>
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
                            </div>
                        </div>

                        <div class="col-12 col-md-9">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Cursos</h5>
                                </div>
                                <div class="card-body" id="containerDIV">
                                    <div id="cursoTurmaContainer">
                                        <div class="row mb-3 curso-turma-item">
                                            <div class="form-group col-12 col-md-5">
                                                <label class="form-label">Curso</label>
                                                <select class="form-select js-select-curso" aria-label="Selecione o curso" name="NNomeCurso">
                                                    <option value="" selected>Selecione o curso</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-5">
                                                <label class="form-label">Turma</label>
                                                <select class="form-select js-select-turma" aria-label="Selecione a turma" name="NNomeTurma" disabled>
                                                    <option value="" selected>Selecione a turma</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-2 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-danger w-100 js-remove-row" disabled>Remover</button>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary" id="adicionarCursoTurma">Adicionar curso/turma</button>
                                </div>
                                <div class="d-grid">
                                    <button type="button" class="btn btn-primary mb-3" id="SalvarMatriculas">Efetuar matrícula(s)</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const container = document.getElementById("cursoTurmaContainer");
            const addButton = document.getElementById("adicionarCursoTurma");
            function resolveApiBase() {
                const basePath = "<?php echo rtrim((string)$BASE_para_URL, '/'); ?>";
                if (/^https?:\/\//i.test(basePath)) {
                    return `${basePath}/api/v1`;
                }
                return `${window.location.origin}${basePath}/api/v1`;
            }

            async function carregarCursosAtivos() {
                const response = await fetch(`${resolveApiBase()}/cursos/ativos`, {
                    method: "GET",
                    headers: { "Accept": "application/json" },
                    credentials: "same-origin"
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || "Erro ao carregar cursos");
                }

                const selects = container.querySelectorAll(".js-select-curso");
                selects.forEach((select) => {
                    const keep = select.value;
                    select.innerHTML = "<option value=''>Selecione o curso</option>";
                    (result.data || []).forEach((curso) => {
                        const option = document.createElement("option");
                        option.value = curso.IdCurso;
                        option.textContent = curso.NomeCurso;
                        select.appendChild(option);
                    });
                    if (keep) {
                        select.value = keep;
                    }
                });
            }

            function atualizarBotoesRemover() {
                const linhas = container.querySelectorAll(".curso-turma-item");
                linhas.forEach((linha, index) => {
                    const botao = linha.querySelector(".js-remove-row");
                    botao.disabled = linhas.length === 1 || index === 0;
                });
            }

            function carregarTurmas(selectCurso, selectTurma) {
                const cursoId = selectCurso.value;
                selectTurma.innerHTML = "<option value=''>Selecione a turma</option>";

                if (!cursoId) {
                    selectTurma.disabled = true;
                    return;
                }

                selectTurma.disabled = false;
                const turmasUrl = `${resolveApiBase()}/turmas/por-curso?IdCurso=${encodeURIComponent(cursoId)}&somenteAtivos=1`;

                fetch(turmasUrl, {
                        method: "GET",
                        headers: {
                            "Accept": "application/json"
                        },
                        credentials: "same-origin"
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || "Erro ao buscar turmas");
                        }
                        selectTurma.innerHTML = "<option value=''>Selecione a turma</option>";
                        (data.data || []).forEach((turma) => {
                            const option = document.createElement("option");
                            option.value = turma.IdTurma;
                            option.textContent = turma.NomeTurma;
                            selectTurma.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error("Erro ao buscar turmas:", error);
                        selectTurma.innerHTML = "<option value=''>Erro ao carregar turmas</option>";
                    });
            }

            function vincularEventosLinha(linha) {
                const selectCurso = linha.querySelector(".js-select-curso");
                const selectTurma = linha.querySelector(".js-select-turma");
                const botaoRemover = linha.querySelector(".js-remove-row");

                selectCurso.addEventListener("change", function() {
                    carregarTurmas(selectCurso, selectTurma);
                });

                botaoRemover.addEventListener("click", function() {
                    const totalLinhas = container.querySelectorAll(".curso-turma-item").length;
                    if (totalLinhas <= 1) {
                        return;
                    }
                    linha.remove();
                    atualizarBotoesRemover();
                });
            }

            addButton.addEventListener("click", function() {
                const primeiraLinha = container.querySelector(".curso-turma-item");
                const novaLinha = primeiraLinha.cloneNode(true);
                const selectCurso = novaLinha.querySelector(".js-select-curso");
                const selectTurma = novaLinha.querySelector(".js-select-turma");

                selectCurso.value = "";
                selectTurma.innerHTML = "<option value=''>Selecione a turma</option>";
                selectTurma.value = "";
                selectTurma.disabled = true;

                vincularEventosLinha(novaLinha);
                container.appendChild(novaLinha);
                atualizarBotoesRemover();
            });

            const linhaInicial = container.querySelector(".curso-turma-item");
            vincularEventosLinha(linhaInicial);
            atualizarBotoesRemover();

            carregarCursosAtivos().catch((error) => {
                console.error("Erro ao carregar cursos:", error);
            });
        });
    </script>

    <script>
        document.getElementById("SalvarMatriculas").addEventListener("click", function() {
            let Verifselects = document.querySelectorAll('[name="NNomeCurso"]');
            let erroSelect = 0;
            let erroQuant = 0;
            for (let Verifselect of Verifselects) {
                if (Verifselect.selectedOptions[0].value === '') {
                    erroSelect++;
                }
            }

            let VerifTurma = document.querySelectorAll('[name="NNomeTurma"]');
            for (let VerifQuantInput of VerifTurma) {
                if (VerifQuantInput.selectedOptions[0].value === '') {
                    erroQuant++;
                }
            }

            if (erroSelect > 0 && erroQuant > 0) {
                alert("Você precisa escolher quais cursos matricular e inserir a turma!");
            } else if (erroSelect > 0 && erroQuant == 0) {
                alert("Você precisa escolher cursos para matricular!");
            } else if (erroSelect == 0 && erroQuant > 0) {
                alert("Você precisa colocar a turma!");
            } else if (erroSelect == 0 && erroQuant == 0) {
                const selects = document.querySelectorAll('[name="NNomeCurso"]');
                let cursosSelect = [];
                for (const select of selects) {
                    let valor = select.selectedOptions[0].value;
                    cursosSelect.push({
                        IdCurso: valor
                    });
                }

                let turmasSelect = [];
                const Turmas = document.querySelectorAll('[name="NNomeTurma"]');
                for (const turma of Turmas) {
                    let valorTurma = turma.selectedOptions[0].value;
                    turmasSelect.push({
                        IdTurma: valorTurma
                    });
                }

                const jsonDados = `<?php echo $jsonDados; ?>`;
                const IdBenRequest = JSON.parse(jsonDados);

                const form = document.createElement('form');
                form.action = '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/matriculas/cadastrar/';
                form.method = 'post';

                const dataMatricula = localStorage.getItem("diaSelecionado");

                const CursoTurma = [];
                for (let i = 0; i < cursosSelect.length; i++) {
                    CursoTurma.push({
                        IdCurso: cursosSelect[i].IdCurso,
                        IdTurma: turmasSelect[i] ? turmasSelect[i].IdTurma : null
                    });
                }

                for (let i = 0; i < CursoTurma.length; i++) {
                    const cursoTurma = CursoTurma[i];

                    const inputHiddenCurso = document.createElement('input');
                    inputHiddenCurso.type = 'hidden';
                    inputHiddenCurso.name = `CursoTurma[${i}][IdCurso]`;
                    inputHiddenCurso.value = cursoTurma.IdCurso;
                    form.appendChild(inputHiddenCurso);

                    if (cursoTurma.IdTurma !== null) {
                        const inputHiddenTurma = document.createElement('input');
                        inputHiddenTurma.type = 'hidden';
                        inputHiddenTurma.name = `CursoTurma[${i}][IdTurma]`;
                        inputHiddenTurma.value = cursoTurma.IdTurma;
                        form.appendChild(inputHiddenTurma);
                    }
                }

                const alunos = String(IdBenRequest).split(",");
                let qntAlunos = 0;
                for (let aluno of alunos) {
                    const codInput = document.createElement('input');
                    codInput.type = 'hidden';
                    codInput.name = `codAluno[${qntAlunos}]`;
                    codInput.value = aluno;
                    form.appendChild(codInput);
                    qntAlunos++;
                }

                const qntBenef = document.createElement('input');
                qntBenef.type = 'hidden';
                qntBenef.name = 'qntBenef';
                qntBenef.value = qntAlunos;
                form.appendChild(qntBenef);

                const inputHidden = document.createElement('input');
                inputHidden.type = 'hidden';
                inputHidden.name = 'dataMatricula';
                inputHidden.value = dataMatricula;
                form.appendChild(inputHidden);

                document.body.appendChild(form);
                form.submit();
            }
        });
    </script>

</body>

</html>







