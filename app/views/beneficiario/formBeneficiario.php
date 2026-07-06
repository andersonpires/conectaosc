<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$invertextoApiToken = bootstrap_invertexto_api_token($BASE_para_PATH);
require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/app/models/BeneficiarioModel.php';

$tipoPermissao = $_SESSION['Tipo'] ?? '';
$podeUsarVersatilis = in_array($tipoPermissao, ['Geral', 'Versatilis', 'Administrador', 'Superadministrador'], true);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <style>
        .tooltip .tooltip-inner {
            background-color: #007bff;
            color: #ffffff;
        }

        .img-cover {
            object-fit: cover;
            object-position: center;
        }

        .checkbox-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            align-items: center;
            margin: 20px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-item input {
            transform: scale(1.2);
        }

        :root {
            --tab-inscricao: #1e88e5;
            --tab-socio: #2e7d32;
            --tab-vulnerabilidade: #c77800;
            --tab-medico: #ef6c00;
            --tab-ipai: #00838f;
            --tab-outros: #6a1b9a;
        }

        .nav-pills .nav-link {
            border-radius: 999px;
            font-weight: 600;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .nav-pills .nav-link.active {
            color: #ffffff !important;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.12);
        }

        .nav-pills .nav-link.tab-inscricao {
            color: var(--tab-inscricao);
            border-color: var(--tab-inscricao);
        }

        .nav-pills .nav-link.tab-inscricao.active {
            background: var(--tab-inscricao);
            border-color: var(--tab-inscricao);
        }

        .nav-pills .nav-link.tab-socio {
            color: var(--tab-socio);
            border-color: var(--tab-socio);
        }

        .nav-pills .nav-link.tab-socio.active {
            background: var(--tab-socio);
            border-color: var(--tab-socio);
        }

        .nav-pills .nav-link.tab-vulnerabilidade {
            color: var(--tab-vulnerabilidade);
            border-color: var(--tab-vulnerabilidade);
        }

        .nav-pills .nav-link.tab-vulnerabilidade.active {
            background: var(--tab-vulnerabilidade);
            border-color: var(--tab-vulnerabilidade);
        }

        .nav-pills .nav-link.tab-medico {
            color: var(--tab-medico);
            border-color: var(--tab-medico);
        }

        .nav-pills .nav-link.tab-medico.active {
            background: var(--tab-medico);
            border-color: var(--tab-medico);
        }

        .nav-pills .nav-link.tab-ipai {
            color: var(--tab-ipai);
            border-color: var(--tab-ipai);
        }

        .nav-pills .nav-link.tab-ipai.active {
            background: var(--tab-ipai);
            border-color: var(--tab-ipai);
        }

        .nav-pills .nav-link.tab-outros {
            color: var(--tab-outros);
            border-color: var(--tab-outros);
        }

        .nav-pills .nav-link.tab-outros.active {
            background: var(--tab-outros);
            border-color: var(--tab-outros);
        }

        #tab-inscricao {
            --tab-color: var(--tab-inscricao);
        }

        #tab-socio {
            --tab-color: var(--tab-socio);
        }

        #tab-vulnerabilidade {
            --tab-color: var(--tab-vulnerabilidade);
        }

        #tab-medico {
            --tab-color: var(--tab-medico);
        }

        #tab-ipai {
            --tab-color: var(--tab-ipai);
        }

        #tab-outros {
            --tab-color: var(--tab-outros);
        }

        .section-title {
            border-left: 6px solid var(--tab-color, #0d6efd);
            padding-left: 12px;
        }
    </style>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/compressorjs@1.2.1/dist/compressor.min.js"></script>
    <link rel="stylesheet" href="<?php echo $BASE_para_URL; ?>/assets/css/overlayNotifica.css">
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/overlayNotifica.js"></script>

    <style>
        #overlayLoading {
            display: none;
        }
    </style>
</head>

<body>
    <div id="overlayLoading">
        <div class="overlay-bg"></div>
        <div class="overlay-content" id="overlayContent">
            <div class="spinner-border text-primary" role="status" id="overlaySpinner">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <div class="mt-2" id="overlayText"></div>

            <button id="overlayBtnOk" class="btn btn-primary mt-3" style="display: none;">OK</button>
        </div>
    </div>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
            <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>

            <main class="content">
                <div class="container-fluid p-0">

                    <?php
                    // Exibe mensagem vinda da URL
                    $msg = $_POST['msg'] ?? '';
                    $erro = $_POST['erro'] ?? '';
                    if ($msg): ?>
                        <div class="alert alert-success" role="alert">
                            <?= htmlspecialchars($msg) ?>
                        </div>
                    <?php elseif ($erro): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($erro) ?>
                        </div>
                    <?php endif;

                    $cpf = $_POST['CPF'] ?? '';
                    $modoEdicao = isset($_POST['IdUsuario']);
                    $modoCrianca = ($_POST['modo'] ?? '') === 'crianca'
                        || ($modoEdicao && trim((string)$cpf) === '');
                    $cpfInformado = isset($_POST['CPF']) || $modoCrianca;
                    $cpfNaoEncontrado = isset($cpfNaoEncontrado) ? $cpfNaoEncontrado : false;
                    $tabAtiva = $_GET['tab'] ?? 'inscricao';
                    $tabsValidas = ['inscricao', 'socio', 'vulnerabilidade', 'medico', 'ipai', 'outros'];
                    if (!in_array($tabAtiva, $tabsValidas, true)) {
                        $tabAtiva = 'inscricao';
                    }

                    // Se não tiver CPF, mostra apenas o campo para digitá-lo
                    if (!$cpfInformado) {
                    ?>
                        <form method="GET" action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/beneficiarios/cadastro/">
                            <div class="card shadow p-4 col-md-6 mx-auto mt-5">
                                <h3 class="mb-3">Digite o CPF para continuar</h3>
                                <div class="mb-3">
                                    <input type="text" class="form-control" name="cpf" id="cpfInput" maxlength="11" placeholder="Somente números" required autofocus>
                                    <div id="cpfStatus" class="mt-2" style="font-weight: bold;"></div>
                                </div>
                                <button type="submit" class="btn btn-primary">Enviar</button>
                                <div class="text-center mt-3">
                                    <a href="<?= rtrim((string)$BASE_para_URL, '/') ?>/beneficiarios/cadastro?modo=crianca"
                                       class="text-secondary small text-decoration-none">
                                        <i class="fa-solid fa-child"></i> Criança sem CPF
                                    </a>
                                </div>
                            </div>
                        </form>

            </main>

            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
</body>

</html>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.12/jquery.mask.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        const cpfInput = $('#cpfInput');
        const submitButton = cpfInput.closest('.card').find('button[type="submit"]');
        const statusDiv = $('#cpfStatus');
        const token = <?= json_encode($invertextoApiToken, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

        // Inicialização da Máscara
        cpfInput.mask('000.000.000-00', {
            reverse: true
        });

        // Função de atualização de status
        function updateValidationStatus(isValid) {
            submitButton.html('Enviar');
            if (isValid === null) {
                statusDiv.html('');
                submitButton.prop('disabled', false);
            } else if (isValid === true) {
                statusDiv.html('<span style="color: green;">CPF validado.</span>');
                submitButton.prop('disabled', false);
            } else if (isValid === false) {
                statusDiv.html('<span style="color: red;">CPF inválido.</span>');
                submitButton.prop('disabled', false);
            } else {
                statusDiv.html('<span style="color: orange;">Erro na validação.</span>');
                submitButton.prop('disabled', false);
            }
        }

        // Lógica de Validação AJAX
        cpfInput.on('keyup blur', function() {
            const rawCpf = $(this).val().replace(/\D/g, '');

            if (rawCpf.length === 11) {
                submitButton.prop('disabled', false);
                submitButton.html('<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> <span role="status">Validando...</span>');
                statusDiv.html('');

                $.ajax({
                    url: 'https://api.invertexto.com/v1/validator',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        token: token, // O jQuery vai codificar o pipe '|' corretamente
                        value: rawCpf, // Voltamos a usar a variável digitada, em vez do fixo
                        type: 'cpf'
                    },
                    success: function(response) {
                        // A API retorna valid: true ou false
                        const isValid = response.valid === true;
                        updateValidationStatus(isValid);
                    },
                    error: function(xhr, status, error) {
                        // Abra o console (F12) para ver o erro real se cair aqui
                        console.error('Detalhes do erro:', xhr.responseText);
                        updateValidationStatus('error');
                    }
                });
            } else {
                updateValidationStatus(null);
            }
        });
    });
</script>

<?php exit;
                    }
?>

<form action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/beneficiarios/cadastro/" id="formulario" enctype="multipart/form-data" method="post">
    <input type="hidden" name="tab" id="tabDestino" value="<?= htmlspecialchars($tabAtiva) ?>">
    <input type="hidden" name="fechar" id="fecharCadastro" value="">
    <input type="hidden" name="modo" value="<?= $modoCrianca ? 'crianca' : htmlspecialchars($_POST['modo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($modoEdicao): ?>
        <input type="hidden" name="IdUsuario" value="<?= $_POST['IdUsuario'] ?>">
        <input type="hidden" name="IdColaboradorAlt" value="<?= (int)($_SESSION['Cod'] ?? 0) ?>">
        <input type="hidden" name="TimeAlterado" value="<?= date('Y-m-d H:i:s') ?>">

    <?php else: ?>
        <input type="hidden" name="IdColaboradorEnt" value="<?= (int)($_SESSION['Cod'] ?? 0) ?>">
        <input type="hidden" name="TimeEntrada" value="<?= date('Y-m-d H:i:s') ?>">
    <?php endif; ?>
    <ul class="nav nav-pills gap-2 mb-4" id="benefTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link tab-inscricao <?= $tabAtiva === 'inscricao' ? 'active' : '' ?>" id="tab-inscricao-btn" data-bs-toggle="tab" data-bs-target="#tab-inscricao" type="button" role="tab" aria-controls="tab-inscricao" aria-selected="<?= $tabAtiva === 'inscricao' ? 'true' : 'false' ?>" data-tab="inscricao">
                Dados de Inscrição
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link tab-socio <?= $tabAtiva === 'socio' ? 'active' : '' ?>" id="tab-socio-btn" data-bs-toggle="tab" data-bs-target="#tab-socio" type="button" role="tab" aria-controls="tab-socio" aria-selected="<?= $tabAtiva === 'socio' ? 'true' : 'false' ?>" data-tab="socio">
                Dados Econômicos e Socioassistenciais
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link tab-vulnerabilidade <?= $tabAtiva === 'vulnerabilidade' ? 'active' : '' ?>" id="tab-vulnerabilidade-btn" data-bs-toggle="tab" data-bs-target="#tab-vulnerabilidade" type="button" role="tab" aria-controls="tab-vulnerabilidade" aria-selected="<?= $tabAtiva === 'vulnerabilidade' ? 'true' : 'false' ?>" data-tab="vulnerabilidade">
                Avaliação de Vulnerabilidade
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link tab-medico <?= $tabAtiva === 'medico' ? 'active' : '' ?>" id="tab-medico-btn" data-bs-toggle="tab" data-bs-target="#tab-medico" type="button" role="tab" aria-controls="tab-medico" aria-selected="<?= $tabAtiva === 'medico' ? 'true' : 'false' ?>" data-tab="medico">
                Histórico Médico
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link tab-ipai <?= $tabAtiva === 'ipai' ? 'active' : '' ?>" id="tab-ipai-btn" data-bs-toggle="tab" data-bs-target="#tab-ipai" type="button" role="tab" aria-controls="tab-ipai" aria-selected="<?= $tabAtiva === 'ipai' ? 'true' : 'false' ?>" data-tab="ipai">
                IPAI
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link tab-outros <?= $tabAtiva === 'outros' ? 'active' : '' ?>" id="tab-outros-btn" data-bs-toggle="tab" data-bs-target="#tab-outros" type="button" role="tab" aria-controls="tab-outros" aria-selected="<?= $tabAtiva === 'outros' ? 'true' : 'false' ?>" data-tab="outros">
                Outros Dados
            </button>
        </li>
    </ul>

    <div class="tab-content" id="benefTabsContent">
        <div class="tab-pane fade <?= $tabAtiva === 'inscricao' ? 'show active' : '' ?>" id="tab-inscricao" role="tabpanel" aria-labelledby="tab-inscricao-btn">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h1 class="h3 mb-0 section-title">
                    DADOS DE INSCRIÇÃO<?= $modoCrianca ? ' - CRIANÇA' : '' ?>
                </h1>
            </div>
    <div class="col-11">
        <div class="card shadow-lg">
            <div class="card-body">
                <div class="d-flex justify-content-end mb-3">
                    <?php if (!empty($_POST['IdUsuario'])): ?>
                        <a class="btn btn-success" target="_blank" href="<?= $BASE_para_URL ?>/get/getPDF_ficha_cadastro.php?id=<?= $_POST['IdUsuario'] ?>">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </a>
                    <?php else: ?>
                        <button class="btn btn-success" type="button" disabled>
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </button>
                    <?php endif; ?>
                </div>
                <h5>OBRIGATÓRIOS</h5>
                <hr class="close-hr">
                <div class="row mb-3">
                    <div class="mb-3 col-8">
                        <label for="Nome" class="form-label"><?= $modoCrianca ? 'Nome da Criança*' : 'Nome*' ?></label>
                        <input type="text" class="form-control" id="Nome" name="Nome" value="<?= htmlspecialchars($_POST['Nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required autocomplete="off">
                        <input type="hidden" name="Colaborador" value="<?php echo (int)($_SESSION['Cod'] ?? 0) ?>">
                    </div>
                    <div class="mb-3 col-4">
                        <label for="Apelido" class="form-label">Apelido</label>
                        <input type="text" class="form-control" id="Apelido" name="Apelido" value="<?= $_POST['Apelido'] ?? '' ?>" autocomplete="off">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label for="Nascimento" class="form-label">
                            <?= $modoCrianca ? 'Nascimento da Criança*' : 'Nascimento*' ?> <span id="classificacaoIdade" style="font-weight:bold; color:#007bff; margin-left:5px;"></span>
                        </label>

                        <input type="text" class="form-control" id="Nascimento" name="Nascimento" required pattern="^\d{2}/\d{2}/\d{4}$" maxlength="10" inputmode="numeric" title="Informe a data no formato dd/mm/aaaa, com ano de 4 dígitos." value="<?= $_POST['Nascimento'] ?? '' ?>" autocomplete="off">
                    </div>
                    <div class="col-6">
                        <label for="SexoBio" class="form-label">Sexo</label>
                        <select class="form-select" aria-label="Selecione" id="SexoBio" name="SexoBio">
                            <option value="Feminino" <?= ($_POST['SexoBio'] ?? '') == 'Feminino' ? 'selected' : '' ?>>Feminino</option>
                            <option value="Masculino" <?= ($_POST['SexoBio'] ?? '') == 'Masculino' ? 'selected' : '' ?>>Masculino</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-2">
                        <label for="CEP" class="form-label">CEP</label>
                        <div class="input-group">
                            <input type="text" class="form-control cep" id="CEP" name="CEP" value="<?= $_POST['CEP'] ?? '' ?>" autocomplete="off">
                            <span class="input-group-text" id="cepSpinner" style="display: none;">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            </span>
                        </div>
                    </div>
                    <div class="col-7">
                        <label for="Endereco" class="form-label">Endereço*</label>
                        <input type="text" class="form-control" id="Endereco" name="Endereco" required value="<?= $_POST['Endereco'] ?? '' ?>" autocomplete="off">
                    </div>
                    <div class="col-3">
                        <label for="Numero" class="form-label">Número</label>
                        <input type="text" class="form-control" id="Numero" name="Numero" value="<?= $_POST['Numero'] ?? '' ?>" autocomplete="off">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-4">
                        <label for="Complemento" class="form-label">Complemento</label>
                        <input type="text" class="form-control" id="Complemento" name="Complemento" value="<?= $_POST['Complemento'] ?? '' ?>" autocomplete="off">
                    </div>
                    <div class="col-3">
                        <label for="Bairro" class="form-label">Bairro*</label>
                        <input type="text" class="form-control" id="Bairro" name="Bairro" required value="<?= $_POST['Bairro'] ?? '' ?>" autocomplete="off">
                    </div>
                    <div class="col-3">
                        <label for="Cidade" class="form-label">Cidade*</label>
                        <input type="text" class="form-control" id="Cidade" name="Cidade" value="<?= $_POST['Cidade'] ?? '' ?>" required autocomplete="off">
                    </div>
                    <div class="col-2">
                        <label for="UF" class="form-label">UF*</label>
                        <select class="form-select" aria-label="Selecione o Estado" id="UF" name="UF">
                            <?php
                            $estados = [
                                "AC",
                                "AL",
                                "AP",
                                "AM",
                                "BA",
                                "CE",
                                "DF",
                                "ES",
                                "GO",
                                "MA",
                                "MT",
                                "MS",
                                "MG",
                                "PA",
                                "PB",
                                "PR",
                                "PE",
                                "PI",
                                "RJ",
                                "RN",
                                "RS",
                                "RO",
                                "RR",
                                "SC",
                                "SP",
                                "SE",
                                "TO"
                            ];
                            $estadoSelecionado = $_POST['UF'] ?? 'CE'; // padrão para CE se não vier nada
                            foreach ($estados as $uf) {
                                $selected = ($uf === $estadoSelecionado) ? 'selected' : '';
                                echo "<option value=\"$uf\" $selected>$uf</option>";
                            }
                            ?>
                        </select>

                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label for="WhatsApp" class="form-label">WhatsApp</label>
                        <input type="text" class="form-control" id="WhatsApp" name="WhatsApp" value="<?= $_POST['WhatsApp'] ?? '' ?>" autocomplete="off">
                    </div>
                    <div class="col-6">
                        <label for="Telefone" class="form-label">Telefone</label>
                        <input type="text" class="form-control" id="Telefone" name="Telefone" value="<?= $_POST['Telefone'] ?? '' ?>" autocomplete="off">
                    </div>
                </div>
                <?php if ($modoCrianca): ?>
                    <br>
                    <h5>RESPONSÁVEL <small class="text-danger">(obrigatório para criança sem CPF)</small></h5>
                    <hr class="close-hr"><br>
                    <div class="mb-3">
                        <label for="NomeResp1" class="form-label">Nome do Responsável 1*</label>
                        <input type="text" class="form-control" id="NomeResp1" name="NomeResp1"
                               value="<?= htmlspecialchars($_POST['NomeResp1'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               required autocomplete="off">
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="Parentesco" class="form-label">Parentesco*</label>
                            <select class="form-select" id="Parentesco" name="Parentesco" required>
                                <?php
                                $opcoesParentesco = [
                                    '' => 'Selecione',
                                    'Mae' => 'Mãe',
                                    'Pai' => 'Pai',
                                    'Avo/Avo' => 'Avô/Avó',
                                    'Madrasta' => 'Madrasta',
                                    'Padrasto' => 'Padrasto',
                                    'Cuidador' => 'Cuidador',
                                    'Outro' => 'Outro',
                                ];
                                $parentescoSel = $_POST['Parentesco'] ?? '';
                                foreach ($opcoesParentesco as $val => $label) {
                                    $sel = ($val === $parentescoSel) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . "\" $sel>" . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="CpfResp1" class="form-label">CPF do Responsável*</label>
                            <input type="text" class="form-control" id="CpfResp1" name="CpfResp1"
                                   value="<?= htmlspecialchars($_POST['CpfResp1'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   required autocomplete="off">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="WhatsAppResp1" class="form-label">WhatsApp do Responsável*</label>
                            <input type="text" class="form-control" id="WhatsAppResp1" name="WhatsAppResp1"
                                   value="<?= htmlspecialchars($_POST['WhatsAppResp1'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   required autocomplete="off">
                        </div>
                        <div class="col-6">
                            <label for="TelefoneResp1" class="form-label">Telefone do Responsável</label>
                            <input type="text" class="form-control" id="TelefoneResp1" name="TelefoneResp1"
                                   value="<?= htmlspecialchars($_POST['TelefoneResp1'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   autocomplete="off">
                        </div>
                    </div>
                <?php endif; ?>
                <div class="mb-3">
                    <?php
                    $fotoAtual = $_POST['Foto'] ?? '';
                    $caminhoFoto = bootstrap_foto_url((string)$fotoAtual);
                    ?>
                    <br>
                    <img src="<?= htmlspecialchars($caminhoFoto) ?>" class="rounded-circle img-cover" width="100px" height="100px" id="currentPhoto">
                    <br>
                    <label for="uploadImage" class="form-label">Foto do beneficiário</label>
                    <input type="hidden" name="fotoAtual" value="<?= $_POST['Foto'] ?? 'padrao.jfif' ?>">
                    <input class="form-control mb-3" type="file" name="foto" id="uploadImage" accept="image/*">

                    <div id="previewContainer" style="display: none; margin-top: 10px;">
                        <img id="preview" style="width: 300px; height: 300px; margin-top: 10px;">
                        <div id="cropControls" style="display: none; margin-top: 10px;">
                            <button type="button" id="rotateLeft">Girar Esquerda</button>
                            <button type="button" id="rotateRight">Girar Direita</button>
                            <button type="button" id="flipHorizontal">Inverter Horizontal</button>
                            <button type="button" id="flipVertical">Inverter Vertical</button>
                        </div>
                    </div>

                </div>

                <?php
                require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
                $sql = "SELECT * FROM tbProjeto ORDER BY NomeProjeto ASC";
                $busca = $pdo->query($sql);
                if ($busca && $busca->rowCount() > 0) {
                ?>
                    <br>
                    <div class="text-center mb3">
                        <p class="text-center">SE FOR INSCRIÇÃO, SELECIONE OS INTERESSES:</p>
                    </div>
                    <div class="checkbox-container">
                        <?php
                        $projetosSelecionados = [];

                        if (isset($_POST['IdUsuario'])) {
                            // Pega os projetos que o usuário já selecionou
                            $stmt = $pdo->prepare("SELECT IdProjeto FROM tbInteresse WHERE IdUsuario = ? AND Valor = 1");
                            $stmt->execute([$_POST['IdUsuario']]);
                            $projetosSelecionados = $stmt->fetchAll(PDO::FETCH_COLUMN); // array com os IDs dos projetos selecionados
                        }

                        while ($dados = $busca->fetch(PDO::FETCH_ASSOC)) {
                            $nomeProjeto = htmlspecialchars($dados['NomeProjeto'], ENT_QUOTES, 'UTF-8');
                            $IdProjeto = $dados['IdProjeto'];
                            $checked = in_array($IdProjeto, $projetosSelecionados) ? 'checked' : '';

                            echo '<div class="checkbox-item">';
                            echo "<input type=\"checkbox\" name=\"projetos[]\" value=\"$IdProjeto\" id=\"$IdProjeto\" $checked>";
                            echo "<label for=\"$IdProjeto\">$nomeProjeto</label>";
                            echo '</div>';
                        }

                        ?>
                    </div>
                <?php
                } else {
                    echo "<br><br>";
                    echo '<p class="text-center">Nenhum interesse disponível ou erro ao acessar o banco de dados.</p>';
                }
                ?>
                <div class="d-flex justify-content-end gap-2 flex-wrap">
                    <button type="submit" name="acao" value="salvar" class="btn btn-outline-secondary" data-acao="fechar">
                        Salvar e fechar
                    </button>
                    <button type="submit" name="acao" value="salvar" class="btn btn-primary" data-acao="continuar" data-next-tab="socio">
                        Salvar e continuar
                    </button>
                </div>
            </div>
        </div>
    </div>
        </div>
        <div class="tab-pane fade <?= $tabAtiva === 'socio' ? 'show active' : '' ?>" id="tab-socio" role="tabpanel" aria-labelledby="tab-socio-btn">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h1 class="h3 mb-0 section-title">DADOS ECONÔMICOS E SOCIOASSISTENCIAIS</h1>
            </div>
    <div class="col-11">
        <div class="card shadow-lg">
            <div class="card-body">
                <div class="d-flex justify-content-end mb-3">
                    <?php if (!empty($_POST['IdUsuario'])): ?>
                        <a class="btn btn-success" target="_blank" href="<?= $BASE_para_URL ?>/get/getPDF_ficha_cadastro.php?id=<?= $_POST['IdUsuario'] ?>">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </a>
                    <?php else: ?>
                        <button class="btn btn-success" type="button" disabled>
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </button>
                    <?php endif; ?>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Número de pessoas na residência</label>
                        <input type="number" name="NumPessoasReside" class="form-control" value="<?= $_POST['NumPessoasReside'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Total de cômodos em sua casa?</label>
                        <select name="TotalComodosCasa" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["1", "2", "3", "mais de 3"];
                            $valor = $_POST['TotalComodosCasa'] ?? '';
                            foreach ($opcoes as $op) {
                                $selected = ($valor == $op) ? 'selected' : '';
                                echo "<option value=\"$op\" $selected>$op</option>";
                            }
                            ?>
                        </select>

                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Com quem mora</label>
                        <select name="ComQuemMora" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = [
                                "Sozinho" => "Sozinho",
                                "Familia nuclear" => "Família nuclear",
                                "Parentes" => "Parentes",
                                "Outros" => "Outros",
                            ];
                            $valor = $_POST['ComQuemMora'] ?? '';
                            foreach ($opcoes as $valorOpcao => $labelOpcao) {
                                $selected = ($valor == $valorOpcao || $valor == $labelOpcao) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($valorOpcao, ENT_QUOTES, 'UTF-8') . "\" $selected>" . htmlspecialchars($labelOpcao, ENT_QUOTES, 'UTF-8') . '</option>';
                            }
                            ?>
                        </select>

                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Dependentes sob responsabilidade</label>
                    <textarea name="DependentesResponsabilidade" class="form-control"><?= $_POST['DependentesResponsabilidade'] ?? '' ?></textarea>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Renda mensal (R$)</label>
                        <input type="text" name="RendaMensal" class="form-control money" value="<?= $_POST['RendaMensal'] ?? '' ?>">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Renda familiar (R$)</label>
                        <input type="text" name="RendaFamiliar" class="form-control money" value="<?= $_POST['RendaFamiliar'] ?? '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Situação</label>
                        <input type="text" name="Calculo" class="form-control" value="" readonly>
                    </div>
                </div>

                <div class="row mb-3">

                    <!-- PCD em casa -->
                    <div class="col-md-2">
                        <label class="form-label">PCDs em casa</label>
                        <select name="PCDEmCasa" class="form-select">
                            <option value="">Selecione</option>
                            <option value="1" <?= ($_POST['PCDEmCasa'] ?? '') == '1' ? 'selected' : '' ?>>Sim</option>
                            <option value="0" <?= ($_POST['PCDEmCasa'] ?? '') == '0' ? 'selected' : '' ?>>Não</option>
                        </select>
                    </div>

                    <!-- Quantas e quais deficiências -->
                    <div class="col-md-5">
                        <label class="form-label">Quantas e quais deficiências</label>
                        <input type="text"
                            name="DeficienciasCasa"
                            class="form-control"
                            value="<?= $_POST['DeficienciasCasa'] ?? '' ?>">
                    </div>

                    <!-- Participa de programa social (AJUSTADO PARA col-md-5) -->
                    <div class="col-md-5">
                        <label class="form-label">Participa de programa social</label>
                        <input type="text"
                            name="ParticipaProgramaSocial"
                            class="form-control"
                            value="<?= $_POST['ParticipaProgramaSocial'] ?? '' ?>">
                    </div>

                </div>

                <div class="mb-3">
                    <label class="form-label">Outros projetos sociais (quais e onde)</label>
                    <textarea name="BenefOutroProjeto" class="form-control"><?= $_POST['BenefOutroProjeto'] ?? '' ?></textarea>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Situação de moradia</label>
                        <select name="SituacaoMoradia" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["Própria", "Alugada", "Cedida", "Ocupação / Invasão", "Outros"];
                            $valor = $_POST['SituacaoMoradia'] ?? '';
                            foreach ($opcoes as $op) {
                                $selected = ($valor == $op) ? 'selected' : '';
                                echo "<option value=\"$op\" $selected>$op</option>";
                            }
                            ?>
                        </select>

                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipo da construção</label>
                        <select name="TipoConstrucao" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["Alvenaria", "Madeira", "Mista"];
                            $valor = $_POST['TipoConstrucao'] ?? '';
                            foreach ($opcoes as $op) {
                                $selected = ($valor == $op) ? 'selected' : '';
                                echo "<option value=\"$op\" $selected>$op</option>";
                            }
                            ?>
                        </select>

                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Abastecimento de água</label>
                        <select name="AbastecimentoAgua" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = [
                                "Rede publica" => "Rede pública",
                                "Poco" => "Poço",
                                "Outro" => "Outro",
                            ];
                            $valor = $_POST['AbastecimentoAgua'] ?? '';
                            foreach ($opcoes as $valorOpcao => $labelOpcao) {
                                $selected = ($valor == $valorOpcao || $valor == $labelOpcao) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($valorOpcao, ENT_QUOTES, 'UTF-8') . "\" $selected>" . htmlspecialchars($labelOpcao, ENT_QUOTES, 'UTF-8') . '</option>';
                            }
                            ?>
                        </select>

                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Esgotamento sanitário</label>
                        <select name="EsgotamentoSanitario" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = [
                                "Rede publica" => "Rede pública",
                                "Fossa septica" => "Fossa séptica",
                                "Ceu aberto" => "Céu aberto",
                            ];
                            $valor = $_POST['EsgotamentoSanitario'] ?? '';
                            foreach ($opcoes as $valorOpcao => $labelOpcao) {
                                $selected = ($valor == $valorOpcao || $valor == $labelOpcao) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($valorOpcao, ENT_QUOTES, 'UTF-8') . "\" $selected>" . htmlspecialchars($labelOpcao, ENT_QUOTES, 'UTF-8') . '</option>';
                            }
                            ?>
                        </select>

                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Rede elétrica?</label>
                        <select name="PossuiRedeEletrica" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["1" => "Sim", "0" => "Não"];
                            $valor = $_POST['PossuiRedeEletrica'] ?? '';
                            foreach ($opcoes as $val => $label) {
                                $selected = ($valor == $val) ? 'selected' : '';
                                echo "<option value=\"$val\" $selected>$label</option>";
                            }
                            ?>
                        </select>

                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Iluminação pública?</label>

                        <select name="PossuiIluminacaoPublica" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["1" => "Sim", "0" => "Não"];
                            $valor = $_POST['PossuiIluminacaoPublica'] ?? '';
                            foreach ($opcoes as $val => $label) {
                                $selected = ($valor == $val) ? 'selected' : '';
                                echo "<option value=\"$val\" $selected>$label</option>";
                            }
                            ?>
                        </select>

                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Transporte utilizado</label>
                        <input type="text" name="TransporteUtilizado" class="form-control" value="<?= $_POST['TransporteUtilizado'] ?? '' ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tem cuidador?</label>
                        <select name="TemCuidador" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["1" => "Sim", "0" => "Não"];
                            $valor = $_POST['TemCuidador'] ?? '';
                            foreach ($opcoes as $val => $label) {
                                $selected = ($valor == $val) ? 'selected' : '';
                                echo "<option value=\"$val\" $selected>$label</option>";
                            }
                            ?>
                        </select>

                        <input type="text" name="NomeContatoCuidador" placeholder="Nome e contato do cuidador" class="form-control mt-2" value="<?= $_POST['NomeContatoCuidador'] ?? '' ?>">
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 flex-wrap">
                    <button type="submit" name="acao" value="salvar" class="btn btn-outline-secondary" data-acao="fechar">
                        Salvar e fechar
                    </button>
                    <button type="submit" name="acao" value="salvar" class="btn btn-primary" data-acao="continuar" data-next-tab="vulnerabilidade">
                        Salvar e continuar
                    </button>
                </div>

            </div>
        </div>
    </div>


        </div>
        <div class="tab-pane fade <?= $tabAtiva === 'vulnerabilidade' ? 'show active' : '' ?>" id="tab-vulnerabilidade" role="tabpanel" aria-labelledby="tab-vulnerabilidade-btn">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h1 class="h3 mb-0 section-title">AVALIAÇÃO DE VULNERABILIDADE</h1>
            </div>
    <div class="col-11">
        <div class="card shadow-lg">
            <div class="card-body">
                <div class="mb-3">
                    <?php if ($modoEdicao): ?>
                        <button type="submit" name="acao" value="avaliar_vulnerabilidade" class="btn btn-warning" id="btnAvaliarVuln">
                            Avaliar Vulnerabilidade Social
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-warning" disabled>
                            Avaliar Vulnerabilidade Social (salve o cadastro primeiro)
                        </button>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Avaliação de Vulnerabilidade Social (IA)</label>
                    <textarea
                        name="AvaliacaoVulnerabilidadeIA"
                        class="form-control"
                        rows="8"
                        readonly><?= $_POST['AvaliacaoVulnerabilidadeIA'] ?? '' ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 flex-wrap">
                    <button type="submit" name="acao" value="salvar" class="btn btn-outline-secondary" data-acao="fechar">
                        Salvar e fechar
                    </button>
                    <button type="submit" name="acao" value="salvar" class="btn btn-primary" data-acao="continuar" data-next-tab="medico">
                        Salvar e continuar
                    </button>
                </div>
            </div>
        </div>
    </div>
        </div>
        <div class="tab-pane fade <?= $tabAtiva === 'medico' ? 'show active' : '' ?>" id="tab-medico" role="tabpanel" aria-labelledby="tab-medico-btn">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h1 class="h3 mb-0 section-title">HISTÓRICO MÉDICO</h1>
            </div>
    <div class="col-11">
        <div class="card shadow-lg">
            <div class="card-body">
                <div class="d-flex justify-content-end mb-3">
                    <?php if (!empty($_POST['IdUsuario'])): ?>
                        <a class="btn btn-success" target="_blank" href="<?= $BASE_para_URL ?>/get/getPDF_ficha_cadastro.php?id=<?= $_POST['IdUsuario'] ?>">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </a>
                    <?php else: ?>
                        <button class="btn btn-success" type="button" disabled>
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </button>
                    <?php endif; ?>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Está em acompanhamento médico?</label>
                        <select name="AcompanhamentoMedico" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["1" => "Sim", "0" => "Não"];
                            $valor = $_POST['AcompanhamentoMedico'] ?? '';
                            foreach ($opcoes as $val => $label) {
                                $selected = ($valor == $val) ? 'selected' : '';
                                echo "<option value=\"$val\" $selected>$label</option>";
                            }
                            ?>
                        </select>

                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Motivo / Especialidade</label>
                        <input type="text" name="MotivoAcompanhamento" class="form-control" value="<?= $_POST['MotivoAcompanhamento'] ?? '' ?>">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Uso contínuo de medicamentos?</label>
                        <select name="UsoMedicamentos" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["1" => "Sim", "0" => "Não"];
                            $valor = $_POST['UsoMedicamentos'] ?? '';
                            foreach ($opcoes as $val => $label) {
                                $selected = ($valor == $val) ? 'selected' : '';
                                echo "<option value=\"$val\" $selected>$label</option>";
                            }
                            ?>
                        </select>

                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Quais?</label>
                        <input type="text" name="QuaisMedicamentos" class="form-control" value="<?= $_POST['QuaisMedicamentos'] ?? '' ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Doença crônica diagnosticada</label>
                    <input type="text" name="DoencaCronica" class="form-control" value="<?= $_POST['DoencaCronica'] ?? '' ?>">
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Já realizou cirurgia?</label>
                        <select name="CirurgiaRealizada" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["1" => "Sim", "0" => "Não"];
                            $valor = $_POST['CirurgiaRealizada'] ?? '';
                            foreach ($opcoes as $val => $label) {
                                $selected = ($valor == $val) ? 'selected' : '';
                                echo "<option value=\"$val\" $selected>$label</option>";
                            }
                            ?>
                        </select>

                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Qual / Quando?</label>
                        <input type="text" name="QualCirurgiaQuando" class="form-control" value="<?= $_POST['QualCirurgiaQuando'] ?? '' ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Limitações físicas / locomoção</label>
                    <input type="text" name="LimitacoesLocomocao" class="form-control" value="<?= $_POST['LimitacoesLocomocao'] ?? '' ?>">
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Já fez fisioterapia?</label>
                        <select name="TratamentoFisioterapia" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["1" => "Sim", "0" => "Não"];
                            $valor = $_POST['TratamentoFisioterapia'] ?? '';
                            foreach ($opcoes as $val => $label) {
                                $selected = ($valor == $val) ? 'selected' : '';
                                echo "<option value=\"$val\" $selected>$label</option>";
                            }
                            ?>
                        </select>

                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Motivo</label>
                        <input type="text" name="MotivoFisioterapia" class="form-control" value="<?= $_POST['MotivoFisioterapia'] ?? '' ?>">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Pratica atividade física?</label>
                        <select name="PraticaAtividadeFisica" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["1" => "Sim", "0" => "Não"];
                            $valor = $_POST['PraticaAtividadeFisica'] ?? '';
                            foreach ($opcoes as $val => $label) {
                                $selected = ($valor == $val) ? 'selected' : '';
                                echo "<option value=\"$val\" $selected>$label</option>";
                            }
                            ?>
                        </select>

                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Qual / Frequência</label>
                        <input type="text" name="TipoFrequenciaAtividade" class="form-control" value="<?= $_POST['TipoFrequenciaAtividade'] ?? '' ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Recomendação médica para evitar esforço físico?</label>
                    <select name="RecomendacaoEsforcoFisico" class="form-select">
                        <option value="">Selecione</option>
                        <?php
                        $opcoes = ["1" => "Sim", "0" => "Não"];
                        $valor = $_POST['RecomendacaoEsforcoFisico'] ?? '';
                        foreach ($opcoes as $val => $label) {
                            $selected = ($valor == $val) ? 'selected' : '';
                            echo "<option value=\"$val\" $selected>$label</option>";
                        }
                        ?>
                    </select>

                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">É fumante?</label>
                        <select name="Fumante" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["1" => "Sim", "0" => "Não"];
                            $valor = $_POST['Fumante'] ?? '';
                            foreach ($opcoes as $val => $label) {
                                $selected = ($valor == $val) ? 'selected' : '';
                                echo "<option value=\"$val\" $selected>$label</option>";
                            }
                            ?>
                        </select>

                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Há quanto tempo?</label>
                        <input type="text" name="TempoFumante" class="form-control" value="<?= $_POST['TempoFumante'] ?? '' ?>">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Usa bebidas alcoólicas?</label>
                        <select name="BebidaAlcoolica" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["1" => "Sim", "0" => "Não"];
                            $valor = $_POST['BebidaAlcoolica'] ?? '';
                            foreach ($opcoes as $val => $label) {
                                $selected = ($valor == $val) ? 'selected' : '';
                                echo "<option value=\"$val\" $selected>$label</option>";
                            }
                            ?>
                        </select>

                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Frequência</label>
                        <input type="text" name="FrequenciaAlcool" class="form-control" value="<?= $_POST['FrequenciaAlcool'] ?? '' ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Histórico familiar de doenças</label>
                    <textarea name="HistoricoFamiliarDoencas" class="form-control"><?= $_POST['HistoricoFamiliarDoencas'] ?? '' ?></textarea>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Alergia a medicamentos?</label>
                        <select name="AlergiaMedicamento" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["1" => "Sim", "0" => "Não"];
                            $valor = $_POST['AlergiaMedicamento'] ?? '';
                            foreach ($opcoes as $val => $label) {
                                $selected = ($valor == $val) ? 'selected' : '';
                                echo "<option value=\"$val\" $selected>$label</option>";
                            }
                            ?>
                        </select>

                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Quais?</label>
                        <input type="text" name="QuaisAlergias" class="form-control" value="<?= $_POST['QuaisAlergias'] ?? '' ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Observações adicionais de saúde</label>
                    <textarea name="ObsSaude" class="form-control"><?= $_POST['ObsSaude'] ?? '' ?></textarea>
                </div>
                <div class="d-flex justify-content-end gap-2 flex-wrap">
                    <button type="submit" name="acao" value="salvar" class="btn btn-outline-secondary" data-acao="fechar">
                        Salvar e fechar
                    </button>
                    <button type="submit" name="acao" value="salvar" class="btn btn-primary" data-acao="continuar" data-next-tab="ipai">
                        Salvar e continuar
                    </button>
                </div>
            </div>
        </div>
    </div>

        </div>
        <div class="tab-pane fade <?= $tabAtiva === 'ipai' ? 'show active' : '' ?>" id="tab-ipai" role="tabpanel" aria-labelledby="tab-ipai-btn">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h1 class="h3 mb-0 section-title">IPAI</h1>
            </div>
    <div class="col-11">
        <div class="card shadow-lg">
            <div class="card-body">
                <div class="d-flex justify-content-end mb-3">
                    <?php if (!empty($_POST['IdUsuario'])): ?>
                        <a class="btn btn-success" target="_blank" href="<?= $BASE_para_URL ?>/get/getPDF_ficha_cadastro.php?id=<?= $_POST['IdUsuario'] ?>">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </a>
                    <?php else: ?>
                        <button class="btn btn-success" type="button" disabled>
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </button>
                    <?php endif; ?>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Desenvolve alguma atividade remunerada? Se sim, qual?</label>
                        <input type="text" name="AtividadeRemunerada" class="form-control" value="<?= $_POST['AtividadeRemunerada'] ?? '' ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Em caso de emergência, a quem costuma ligar?</label>
                        <input type="text" name="ContatoEmergencia" class="form-control" value="<?= $_POST['ContatoEmergencia'] ?? '' ?>">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Qual sua cor ou raça?</label>
                        <select name="CorRaca" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["Branco", "Pardo", "Preto", "Amarelo", "Outra", "Prefiro não responder"];
                            $valor = $_POST['CorRaca'] ?? '';
                            foreach ($opcoes as $op) {
                                $selected = ($valor == $op) ? 'selected' : '';
                                echo "<option value=\"$op\" $selected>$op</option>";
                            }
                            ?>
                        </select>

                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Qual importância você atribui na participação da sua família/amigos no curso?</label>
                        <select name="ImportanciaFamiliaAmigos" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["Irrelevante", "Pouco", "Muito", "Prefiro não responder"];
                            $valor = $_POST['ImportanciaFamiliaAmigos'] ?? '';
                            foreach ($opcoes as $op) {
                                $selected = ($valor == $op) ? 'selected' : '';
                                echo "<option value=\"$op\" $selected>$op</option>";
                            }
                            ?>
                        </select>

                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Quem é a pessoa com quem você tem maior vínculo familiar?</label>
                        <input type="text" name="VinculoFamiliar" class="form-control" value="<?= $_POST['VinculoFamiliar'] ?? '' ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Você costuma receber visitas em casa?</label>
                        <select name="RecebeVisitas" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["Sim", "Não", "Raramente", "Sempre"];
                            $valor = $_POST['RecebeVisitas'] ?? '';
                            foreach ($opcoes as $op) {
                                $selected = ($valor == $op) ? 'selected' : '';
                                echo "<option value=\"$op\" $selected>$op</option>";
                            }
                            ?>
                        </select>

                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Até que ponto sua audição ou visão prejudicam sua capacidade de interagir no projeto?</label>
                        <select name="ImpactoAudicaoVisao" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["Nada", "Pouco", "Médio", "Muito"];
                            $valor = $_POST['ImpactoAudicaoVisao'] ?? '';
                            foreach ($opcoes as $op) {
                                $selected = ($valor == $op) ? 'selected' : '';
                                echo "<option value=\"$op\" $selected>$op</option>";
                            }
                            ?>
                        </select>

                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Como você avalia sua escrita?</label>
                        <select name="AvaliacaoEscrita" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["Não sei escrever", "Baixa", "Média", "Boa", "Ótima"];
                            $valor = $_POST['AvaliacaoEscrita'] ?? '';
                            foreach ($opcoes as $op) {
                                $selected = ($valor == $op) ? 'selected' : '';
                                echo "<option value=\"$op\" $selected>$op</option>";
                            }
                            ?>
                        </select>

                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Como você avalia sua leitura?</label>
                        <select name="AvaliacaoLeitura" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["Não sei ler", "Baixa", "Média", "Boa", "Ótima"];
                            $valor = $_POST['AvaliacaoLeitura'] ?? '';
                            foreach ($opcoes as $op) {
                                $selected = ($valor == $op) ? 'selected' : '';
                                echo "<option value=\"$op\" $selected>$op</option>";
                            }
                            ?>
                        </select>

                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Como você avalia seu condicionamento físico?</label>
                        <select name="CondicionamentoFisico" class="form-select">
                            <option value="">Selecione</option>
                            <?php
                            $opcoes = ["Baixo", "Médio", "Bom", "Ótimo"];
                            $valor = $_POST['CondicionamentoFisico'] ?? '';
                            foreach ($opcoes as $op) {
                                $selected = ($valor == $op) ? 'selected' : '';
                                echo "<option value=\"$op\" $selected>$op</option>";
                            }
                            ?>
                        </select>

                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label">Atualmente você frequenta algum outro curso? Se sim, qual?</label>
                        <input type="text" name="OutroCursoAtual" class="form-control" value="<?= $_POST['OutroCursoAtual'] ?? '' ?>">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label">Você tem interesse em algum curso específico que ainda não fez, mas gostaria de fazer? Se sim, qual?</label>
                        <input type="text" name="InteresseCursoEspecifico" class="form-control" value="<?= $_POST['InteresseCursoEspecifico'] ?? '' ?>">
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 flex-wrap">
                    <button type="submit" name="acao" value="salvar" class="btn btn-outline-secondary" data-acao="fechar">
                        Salvar e fechar
                    </button>
                    <button type="submit" name="acao" value="salvar" class="btn btn-primary" data-acao="continuar" data-next-tab="outros">
                        Salvar e continuar
                    </button>
                </div>
            </div>
        </div>
    </div>

        </div>
        <div class="tab-pane fade <?= $tabAtiva === 'outros' ? 'show active' : '' ?>" id="tab-outros" role="tabpanel" aria-labelledby="tab-outros-btn">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h1 class="h3 mb-0 section-title">OUTROS DADOS</h1>
            </div>
    <div class="col-11">
        <div class="card shadow-lg">
            <div class="card-body">
                <div class="d-flex justify-content-end mb-3">
                    <?php if (!empty($_POST['IdUsuario'])): ?>
                        <a class="btn btn-success" target="_blank" href="<?= $BASE_para_URL ?>/get/getPDF_ficha_cadastro.php?id=<?= $_POST['IdUsuario'] ?>">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </a>
                    <?php else: ?>
                        <button class="btn btn-success" type="button" disabled>
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </button>
                    <?php endif; ?>
                </div>
                <h5>DADOS PARA CADASTRO</h5>
                <hr class="close-hr">
                <br>
                <div class="row mb-3">
                    <div class="col-6">
                        <label for="CPF" class="form-label">CPF</label>
                        <input type="text" class="form-control" id="CPF" name="CPF" value="<?= htmlspecialchars((string)$cpf, ENT_QUOTES, 'UTF-8') ?>" <?= $modoEdicao ? '' : 'readonly' ?> autocomplete="off">
                    </div>
                    <div class="col-6">
                        <label for="Identidade" class="form-label">Identidade</label>
                        <input type="text" class="form-control" id="Identidade" name="Identidade" value="<?= $_POST['Identidade'] ?? '' ?>" autocomplete="off">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <label for="NIS" class="form-label">NIS</label>
                        <input type="text" class="form-control" id="NIS" name="NIS" value="<?= $_POST['NIS'] ?? '' ?>" autocomplete="off">
                    </div>
                </div>
                <br>
                <h5>INFORMAÇÕES ADICIONAIS</h5>
                <hr class="close-hr"><br>
                <div class="row mb-3">
                    <div class="col-12">
                        <label for="Email" class="form-label">E-mail</label>
                        <input type="mail" class="form-control" id="Email" name="Email" value="<?= $_POST['Email'] ?? '' ?>" autocomplete="off">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label for="Escolaridade" class="form-label">Escolaridade</label>
                        <select class="form-select" aria-label="Selecione sua Escolaridade" id="Escolaridade" name="Escolaridade">
                            <?php
                            $opcoesEscolaridade = [
                                "Sem educação",
                                "Fundamental incompleto",
                                "Fundamental completo",
                                "Médio incompleto",
                                "Médio completo",
                                "Superior incompleto",
                                "Superior completo"
                            ];
                            $escolaridadeSelecionada = $_POST['Escolaridade'] ?? 'Fundamental incompleto'; // valor padrão
                            foreach ($opcoesEscolaridade as $escolaridade) {
                                $selected = ($escolaridade === $escolaridadeSelecionada) ? 'selected' : '';
                                echo "<option value=\"$escolaridade\" $selected>$escolaridade</option>";
                            }
                            ?>
                        </select>

                    </div>
                    <div class="col-6">
                        <label for="EstadoCivil" class="form-label">Estado civil</label>
                        <select class="form-select" aria-label="Selecione seu estado civil" id="EstadoCivil" name="EstadoCivil">
                            <?php
                            $opcoesEstadoCivil = [
                                "Solteiro",
                                "Casado",
                                "Separado",
                                "Viúvo",
                                "Uniao estavel" => "União estável",
                                "Outro"
                            ];
                            $estadoCivilSelecionado = $_POST['EstadoCivil'] ?? 'Solteiro'; // valor padrão
                            foreach ($opcoesEstadoCivil as $valorEstadoCivil => $labelEstadoCivil) {
                                if (is_int($valorEstadoCivil)) {
                                    $valorEstadoCivil = $labelEstadoCivil;
                                }
                                $selected = ($valorEstadoCivil === $estadoCivilSelecionado || $labelEstadoCivil === $estadoCivilSelecionado) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars((string)$valorEstadoCivil, ENT_QUOTES, 'UTF-8') . "\" $selected>" . htmlspecialchars((string)$labelEstadoCivil, ENT_QUOTES, 'UTF-8') . '</option>';
                            }
                            ?>
                        </select>

                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <label for="Profissao" class="form-label">Profissão</label>
                        <input type="text" class="form-control" id="Profissao" name="Profissao" value="<?= $_POST['Profissao'] ?? '' ?>" autocomplete="off">
                    </div>
                </div>
                <?php if (!$modoCrianca): ?>
                <br>
                <h5>RESPONSÁVEIS</h5>
                <hr class="close-hr"><br>
                <div class="mb-3">
                    <label for="NomeResp1" class="form-label">Nome do responsável 1</label>
                    <input type="text" class="form-control" id="NomeResp1" name="NomeResp1" value="<?= $_POST['NomeResp1'] ?? '' ?>" autocomplete="off">
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label for="Parentesco" class="form-label">Parentesco</label>
                        <select class="form-select" aria-label="Parentesco" id="Parentesco" name="Parentesco">
                            <?php
                            $opcoesParentesco = [
                                "" => "", // opcao vazia
                                "Mae" => "Mãe",
                                "Pai" => "Pai",
                                "Avo/Avo" => "Avô/Avó",
                                "Madrasta" => "Madrasta",
                                "Padrasto" => "Padrasto",
                                "Cuidador" => "Cuidador",
                                "Outro" => "Outro"
                            ];
                            $parentescoSelecionado = $_POST['Parentesco'] ?? '';
                            foreach ($opcoesParentesco as $valor => $label) {
                                $selected = ($valor === $parentescoSelecionado) ? 'selected' : '';
                                echo "<option value=\"$valor\" $selected>$label</option>";
                            }
                            ?>
                        </select>

                    </div>
                    <div class="col-6">
                        <label for="CpfResp1" class="form-label">CPF</label>
                        <input type="text" class="form-control" id="CpfResp1" name="CpfResp1" value="<?= $_POST['CpfResp1'] ?? '' ?>" autocomplete="off">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label for="WhatsAppResp1" class="form-label">WhatsApp</label>
                        <input type="text" class="form-control" id="WhatsAppResp1" name="WhatsAppResp1" value="<?= $_POST['WhatsAppResp1'] ?? '' ?>" autocomplete="off">
                    </div>
                    <div class="col-6">
                        <label for="TelefoneResp1" class="form-label">Telefone</label>
                        <input type="text" class="form-control" id="TelefoneResp1" name="TelefoneResp1" value="<?= $_POST['TelefoneResp1'] ?? '' ?>" autocomplete="off">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <label for="NomeResp2" class="form-label">Nome do responsável 2</label>
                        <input type="text" class="form-control" id="NomeResp2" name="NomeResp2" value="<?= $_POST['NomeResp2'] ?? '' ?>" autocomplete="off">
                    </div>
                </div>
                <?php endif; ?>
                <br>
                <h5>OUTROS</h5>
                <hr class="close-hr"><br>
                <div class="mb-3">
                    <label for="Obs" class="form-label">Observação</label>
                    <textarea rows="5" class="form-control" aria-label="Observações" id="Obs" name="Obs" autocomplete="off"><?= $_POST['Obs'] ?? '' ?></textarea>
                </div>
                <div class="d-flex justify-content-end gap-2 flex-wrap">
                    <button type="submit" name="acao" value="salvar" class="btn btn-outline-secondary" data-acao="fechar">
                        Salvar e fechar
                    </button>
                    <button type="submit" name="acao" value="salvar" class="btn btn-primary">
                        Salvar
                    </button>
                    <?php if (!$modoCrianca): ?>
                        <button type="submit" name="acao" value="salvar_versatilis" class="btn" style="background-color: #03989E; color: #ffffff;" <?= $podeUsarVersatilis ? '' : 'disabled' ?> title="<?= $podeUsarVersatilis ? '' : 'Sem permissão Versatilis' ?>">
                            Salvar + Versatilis
                        </button>
                    <?php endif; ?>
                </div>
            </div>
     

                       </div>
    </div>
        </div>
    </div>

    <?php
    $cpfNumericoBenef = preg_replace('/\D/', '', (string)($cpf ?? ''));
    if (strlen($cpfNumericoBenef) === 11): ?>
        <div id="containerBtnCriancas" class="my-3" style="display:none;">
            <button type="button" class="btn btn-outline-info btn-sm"
                    data-bs-toggle="modal" data-bs-target="#modalCriancasVinculadas">
                <i class="fa-solid fa-children"></i> Ver crianças vinculadas
            </button>
        </div>

        <div class="modal fade" id="modalCriancasVinculadas" tabindex="-1"
             aria-labelledby="modalCriancasLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCriancasLabel">
                            <i class="fa-solid fa-children"></i>
                            Crianças vinculadas ao CPF do responsável
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>IdAluno</th>
                                        <th>Nome</th>
                                        <th>Nascimento</th>
                                        <th>CPF</th>
                                        <th>Responsável</th>
                                        <th>Parentesco</th>
                                        <th>WhatsApp Resp.</th>
                                        <th>Telefone Resp.</th>
                                        <th>Colaborador</th>
                                        <th>Data/Hora Cadastro</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="corpoTabelaCriancas">
                                    <tr>
                                        <td colspan="11" class="text-center text-muted">Carregando...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            (function () {
                const cpfResponsavel = '<?= htmlspecialchars($cpfNumericoBenef, ENT_QUOTES, 'UTF-8') ?>';
                const endpointCriancas = '<?= rtrim((string)$BASE_para_URL, '/') ?>/beneficiarios/criancas-responsavel';

                if (cpfResponsavel.length !== 11 || typeof $ === 'undefined') return;

                $.getJSON(endpointCriancas, { cpf: cpfResponsavel }, function (data) {
                    if (!data || !data.success || data.total === 0) return;

                    $('#containerBtnCriancas').show();

                    const tbody = $('#corpoTabelaCriancas');
                    tbody.empty();

                    $.each(data.criancas, function (i, c) {
                        const cpfExibir = (c.CPF && String(c.CPF).trim() !== '') ? $('<span>').text(c.CPF).html() : '<em class="text-muted">Sem CPF</em>';
                        const nascimento = c.Nascimento ? $('<span>').text(c.Nascimento).html() : '-';
                        const timeEntrada = c.TimeEntrada
                            ? $('<span>').text(String(c.TimeEntrada).replace('T', ' ').substring(0, 16)).html()
                            : '-';
                        const urlCadastro = $('<span>').text(c.UrlCadastro || '#').html();
                        const btnAbrir = '<a href="' + urlCadastro + '" target="_blank" rel="noopener noreferrer"'
                            + ' class="btn btn-sm btn-primary">Abrir cadastro</a>';

                        tbody.append(
                            '<tr>'
                            + '<td>' + $('<span>').text(c.IdUsuario || '').html() + '</td>'
                            + '<td>' + $('<span>').text(c.Nome || '-').html() + '</td>'
                            + '<td>' + nascimento + '</td>'
                            + '<td>' + cpfExibir + '</td>'
                            + '<td>' + $('<span>').text(c.NomeResp1 || '-').html() + '</td>'
                            + '<td>' + $('<span>').text(c.Parentesco || '-').html() + '</td>'
                            + '<td>' + $('<span>').text(c.WhatsAppResp1 || '-').html() + '</td>'
                            + '<td>' + $('<span>').text(c.TelefoneResp1 || '-').html() + '</td>'
                            + '<td>' + $('<span>').text(c.NomeColaborador || '-').html() + '</td>'
                            + '<td>' + timeEntrada + '</td>'
                            + '<td>' + btnAbrir + '</td>'
                            + '</tr>'
                        );
                    });
                });
            }());
        </script>
    <?php endif; ?>
</form>

</div>
</main>

<footer class="footer">
    <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
</footer>
</div>
</div>

</body>

</html>
<!-- Importação do jQuery Mask -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.12/jquery.mask.min.js"></script>
<script>
    const uploadImage = document.getElementById('uploadImage');
    const preview = document.getElementById('preview');
    const previewContainer = document.getElementById('previewContainer');

    uploadImage.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file && file.type.startsWith('image/')) {
            new Compressor(file, {
                quality: 0.6,
                maxWidth: 800,
                maxHeight: 800,
                success(result) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block'; // <-- Adicionado
                        previewContainer.style.display = 'block';
                    };
                    reader.readAsDataURL(result);

                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(new File([result], file.name, {
                        type: result.type
                    }));
                    uploadImage.files = dataTransfer.files;
                },
                error(err) {
                    console.error(err);
                    alert('Erro ao processar a imagem: ' + err.message);
                },
            });
        } else {
            alert("Por favor, envie uma imagem válida.");
            uploadImage.value = '';
        }
    });
</script>

<script>
    function buscarCepBrasilApi() {
        var numCep = $("#CEP").val().replace(/\D/g, '');
        if (numCep.length !== 8) {
            return;
        }
        var url = "https://brasilapi.com.br/api/cep/v2/" + numCep;
        $("#cepSpinner").show();
        $.ajax({
            url: url,
            type: "get",
            dataType: "json",
            success: function(dados) {
                if (!dados) return;
                if (dados.state) $("#UF").val(dados.state);
                if (dados.city) $("#Cidade").val(dados.city);
                if (dados.street) $("#Endereco").val(dados.street);
                if (dados.neighborhood) $("#Bairro").val(dados.neighborhood);
            },
            error: function(xhr) {
                console.error("Erro ao buscar CEP:", xhr.responseText || xhr.statusText);
            },
            complete: function() {
                $("#cepSpinner").hide();
            }
        });
    }

    $(document).ready(function() {
        $("#CEP").on("blur", buscarCepBrasilApi);
    });
</script>
<script type="text/javascript">
    $("#Telefone, #TelefoneResp1, #WhatsApp, #WhatsAppResp1").mask("(00) 0.0000-0000");
    $('.time').mask('00:00:00');
    $('.date_time').mask('00/00/0000 00:00:00');
    $('.cep').mask('00000-000');
    $('.phone').mask('0000-0000');
    $('.phone_with_ddd').mask('(00) 0000-0000');
    $('.phone_us').mask('(000) 000-0000');
    $('.mixed').mask('AAA 000-S0S');
    $('#CPF, #CpfResp1, #cpfInput').mask('000.000.000-00', {
        reverse: true
    });
    // $('.cnpj').mask('00.000.000/0000-00', {
    //     reverse: true
    // });
    $('.money').mask('000.000.000.000.000,00', {
        reverse: true
    });
    $('.money2').mask("#.##0,00", {
        reverse: true
    });
    $('.ip_address').mask('0ZZ.0ZZ.0ZZ.0ZZ', {
        translation: {
            'Z': {
                pattern: /[0-9]/,
                optional: true
            }
        }
    });
    $('.ip_address').mask('099.099.099.099');
    $('.percent').mask('##0,00%', {
        reverse: true
    });
    $('.clear-if-not-match').mask("00/00/0000", {
        clearIfNotMatch: true
    });
    $('.placeholder').mask("00/00/0000", {
        placeholder: "__/__/____"
    });
    $('.fallback').mask("00r00r0000", {
        translation: {
            'r': {
                pattern: /[\/]/,
                fallback: '/'
            },
            placeholder: "__/__/____"
        }
    });
    $('.selectonfocus').mask("00/00/0000", {
        selectOnFocus: true
    });
</script>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        const tabInput = document.getElementById('tabDestino');
        const fecharInput = document.getElementById('fecharCadastro');
        const tabButtons = document.querySelectorAll('#benefTabs button[data-bs-toggle="tab"]');

        function setTabValue(tab) {
            if (tabInput) {
                tabInput.value = tab || '';
            }
        }

        const activeTab = document.querySelector('#benefTabs .nav-link.active');
        if (activeTab) {
            setTabValue(activeTab.getAttribute('data-tab'));
        }

        tabButtons.forEach(btn => {
            btn.addEventListener('shown.bs.tab', function(e) {
                setTabValue(e.target.getAttribute('data-tab'));
            });
        });

        document.querySelectorAll('button[type="submit"]').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!fecharInput) return;
                const acao = this.getAttribute('data-acao');
                if (acao === 'fechar') {
                    fecharInput.value = '1';
                    return;
                }

                fecharInput.value = '';
                if (acao === 'continuar') {
                    const next = this.getAttribute('data-next-tab');
                    if (next) setTabValue(next);
                }
            });
        });
    });
</script>

<script type="text/javascript">
    $(document).ready(function() {
        const deveConsultarCpf = <?= $cpfNaoEncontrado ? 'true' : 'false' ?>;
        if (!deveConsultarCpf) return;

        const cpfCampo = $('#CPF');
        const nomeCampo = $('#Nome');
        const nascimentoCampo = $('#Nascimento');
        const sexoCampo = $('#SexoBio');
        const modoEdicao = <?= $modoEdicao ? 'true' : 'false' ?>;

        const cpf = (cpfCampo.val() || '').replace(/\D/g, '');
        if (cpf.length !== 11) return;

        $.ajax({
            url: '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/beneficiarios/consulta-cpf/',
            type: 'GET',
            dataType: 'json',
            data: {
                cpf: cpf
            },
            success: function(resp) {
                if (!resp || resp.code !== 200 || !resp.data) return;

                const data = resp.data;

                if (data.nome && !nomeCampo.val()) {
                    nomeCampo.val(data.nome);
                }

                if (data.genero && !modoEdicao) {
                    if (data.genero === 'M') {
                        sexoCampo.val('Masculino');
                    } else if (data.genero === 'F') {
                        sexoCampo.val('Feminino');
                    }
                    sexoCampo.trigger('change');
                }

                if (data.data_nascimento && !nascimentoCampo.val()) {
                    const partes = data.data_nascimento.split('-');
                    if (partes.length === 3) {
                        const dataFormatada = `${partes[2]}/${partes[1]}/${partes[0]}`;
                        nascimentoCampo.val(dataFormatada);
                    }
                }

                if (typeof toastr !== 'undefined') {
                    toastr.info('Dados do CPF preenchidos automaticamente.');
                }
            },
            error: function(xhr) {
                console.error('Erro ao consultar CPF:', xhr.responseText || xhr.statusText);
            }
        });
    });
</script>

<script type="text/javascript">
    $(document).ready(function() {
        $("#Nascimento").mask('00/00/0000');

        $("#Nascimento").on("input", function() {
            const dataNascimento = $(this).val();
            if (dataNascimento.length === 10) {
                const idade = calcularIdade(dataNascimento);
                if (!isNaN(idade)) {
                    let mensagem = "Adulto";
                    let bgColor = "#000";
                    let textColor = "#ffffff";
                    if (idade < 18) {
                        mensagem = "Menor de 18";
                        bgColor = "#5CBBF2";
                    } else if (idade >= 60) {
                        mensagem = "60+";
                        bgColor = "#A15CF2";
                    }
                    $(this).attr("data-bs-toggle", "tooltip")
                        .attr("data-bs-placement", "top")
                        .attr("title", mensagem)
                        .tooltip("show");

                    $(".tooltip-inner").css({
                        "background-color": bgColor,
                        "color": textColor
                    });

                    setTimeout(() => {
                        $(this).tooltip("dispose");
                    }, 3000);
                }
            }
        });
    });
</script>

<!-- <script type="text/javascript">
    $(document).ready(function() {
        $("#Nascimento").on("input", function() {
            const dataNascimento = $(this).val();
            if (dataNascimento.length === 10) {
                const idade = calcularIdade(dataNascimento);
                if (!isNaN(idade)) {
                    let mensagem = "Adulto";
                    let bgColor = "#000";
                    let textColor = "#ffffff";
                    if (idade < 18) {
                        mensagem = "Menor de 18";
                        bgColor = "#5CBBF2";
                    } else if (idade >= 60) {
                        mensagem = "60+";
                        bgColor = "#A15CF2";
                    }
                    $(this).attr("data-bs-toggle", "tooltip")
                        .attr("data-bs-placement", "top")
                        .attr("title", mensagem)
                        .tooltip("show");
                    $(".tooltip-inner").css({
                        "background-color": bgColor,
                        "color": textColor
                    });
                    $(".tooltip-arrow::before").css("background-color", bgColor);
                    setTimeout(() => {
                        $(this).tooltip("dispose");
                    }, 3000);
                }
            }
        });
    });
</script> -->

<!-- <script type="text/javascript">
    $(document).ready(function() {
        $("#Nascimento").on("input", function() {
            const dataNascimento = $(this).val();
            if (dataNascimento.length === 10) {
                const idade = calcularIdade(dataNascimento);
                if (!isNaN(idade)) {
                    let mensagem = "Adulto";
                    let bgColor = "#000";
                    let textColor = "#ffffff";
                    if (idade < 18) {
                        mensagem = "Menor de 18";
                        bgColor = "#5CBBF2";
                    } else if (idade >= 60) {
                        mensagem = "60+";
                        bgColor = "#A15CF2";
                    }
                    $(this).attr("data-bs-toggle", "tooltip")
                        .attr("data-bs-placement", "top")
                        .attr("title", mensagem)
                        .tooltip("show");
                    $(".tooltip-inner").css({
                        "background-color": bgColor,
                        "color": textColor
                    });
                    $(".tooltip-arrow::before").css("background-color", bgColor);
                    setTimeout(() => {
                        $(this).tooltip("dispose");
                    }, 3000);
                }
            }
        });
    });
</script> -->
<script type="text/javascript">
    $(document).ready(function() {

        function atualizarClassificacaoSocioeconomica() {
            // Pega valores dos inputs
            let rendaFamiliarStr = $('input[name="RendaFamiliar"]').val();
            let numPessoasStr = $('input[name="NumPessoasReside"]').val();

            // Troca vírgula por ponto (caso o usuário digite assim)
            rendaFamiliarStr = rendaFamiliarStr.replace(/\./g, '').replace(',', '.');

            const rendaFamiliar = parseFloat(rendaFamiliarStr);
            const numPessoas = parseInt(numPessoasStr);

            // Se estiver faltando dado ou inválido, limpa o campo
            if (isNaN(rendaFamiliar) || isNaN(numPessoas) || numPessoas <= 0) {
                $('input[name="Calculo"]').val('');
                return;
            }

            // Calcula renda per capita
            const rendaPerCapita = rendaFamiliar / numPessoas;

            let classificacao = '';

            if (rendaPerCapita <= 218) {
                classificacao = 'Extrema pobreza';
            } else if (rendaPerCapita <= 660) {
                classificacao = 'Pobreza';
            } else if (rendaPerCapita <= 1254) {
                classificacao = 'Baixa renda';
            } else if (rendaPerCapita <= 4085) {
                classificacao = 'Classe média';
            } else if (rendaPerCapita <= 9734) {
                classificacao = 'Classe média alta';
            } else {
                classificacao = 'Alta renda';
            }

            // Escreve no campo "Calculo"
            // Exemplo: "Baixa renda (R$ 523,45)"
            const texto = classificacao + ' (R$ ' + rendaPerCapita.toFixed(2).replace('.', ',') + ')';
            $('input[name="Calculo"]').val(texto);
        }

        // Dispara o calculo sempre que mudar RendaFamiliar ou NumPessoasReside
        $('input[name="RendaFamiliar"], input[name="NumPessoasReside"]').on('input change', function() {
            atualizarClassificacaoSocioeconomica();
        });

        // Se já vier valor do banco (modo edição), calcula na carga da página
        atualizarClassificacaoSocioeconomica();
    });
</script>


<!-- Calcula Idade completa -->
<script>
    function calcularIdadeDetalhada(dataStr) {
        const partes = dataStr.split("/");
        if (partes.length !== 3) return null;

        const dia = parseInt(partes[0]);
        const mes = parseInt(partes[1]) - 1;
        const ano = parseInt(partes[2]);

        const nascimento = new Date(ano, mes, dia);
        const hoje = new Date();

        if (isNaN(nascimento.getTime())) return null;

        let anos = hoje.getFullYear() - nascimento.getFullYear();
        let meses = hoje.getMonth() - nascimento.getMonth();
        let dias = hoje.getDate() - nascimento.getDate();

        if (dias < 0) {
            meses--;
            const ultimoDiaMesAnterior = new Date(hoje.getFullYear(), hoje.getMonth(), 0).getDate();
            dias += ultimoDiaMesAnterior;
        }

        if (meses < 0) {
            anos--;
            meses += 12;
        }

        return {
            anos,
            meses,
            dias
        };
    }

    $(document).ready(function() {

        function atualizarIdade() {
            const dataNascimento = $("#Nascimento").val();

            if (dataNascimento.length === 10) {
                const idade = calcularIdadeDetalhada(dataNascimento);

                if (idade) {
                    const texto = `${idade.anos} ano(s), ${idade.meses} mes(es) e ${idade.dias} dia(s)`;
                    $("#classificacaoIdade").text(`${texto}`);
                } else {
                    $("#classificacaoIdade").text("");
                }
            } else {
                $("#classificacaoIdade").text("");
            }
        }

        $("#Nascimento").on("input change", atualizarIdade);

        atualizarIdade(); // modo edição
    });
</script>

<!-- Contador overlay -->
<script>
    (function() {
        const form = document.getElementById('formulario');
        if (!form) return;

        let submitted = false;

        function validarNascimentoComAnoQuatroDigitos(valor) {
            const match = String(valor || '').trim().match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
            if (!match) return false;

            const dia = Number(match[1]);
            const mes = Number(match[2]);
            const ano = Number(match[3]);
            const data = new Date(ano, mes - 1, dia);

            return data.getFullYear() === ano && (data.getMonth() + 1) === mes && data.getDate() === dia;
        }
        function garantirAcaoNoPost(valor) {
            // garante que ação vá no POST mesmo com submit programático
            let hidden = form.querySelector('input[name="acao"][type="hidden"]');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'acao';
                form.appendChild(hidden);
            }
            hidden.value = valor;
        }

        function mostrarMensagemAvaliacao(mensagem) {
            const texto = mensagem || 'Erro ao gerar avaliação.';
            if (typeof mostrarOverlay === 'function') {
                mostrarOverlay({
                    mensagem: texto,
                    carregando: false
                });
                return;
            }
            if (typeof toastr !== 'undefined') {
                toastr.error(texto);
            }
        }

        form.addEventListener('submit', function handler(e) {
            const btn = e.submitter; // botao clicado (quando existe)
            const acao = (btn && btn.name === 'acao') ? btn.value : '';
            const nascimentoInput = form.querySelector('#Nascimento');
            const mensagemNascimento = 'Preencha o nascimento no formato dd/mm/aaaa, com ano de 4 dígitos.';

            if (nascimentoInput) {
                const nascimentoValido = validarNascimentoComAnoQuatroDigitos(nascimentoInput.value);
                nascimentoInput.setCustomValidity(nascimentoValido ? '' : mensagemNascimento);
                if (!nascimentoValido) {
                    e.preventDefault();
                    nascimentoInput.reportValidity();
                    nascimentoInput.focus();
                    return;
                }
            }
            // bloqueia duplo submit
            if (submitted) {
                e.preventDefault();
                return;
            }

            // ===== IA (avaliar vulnerabilidade) =====
            if (acao === 'avaliar_vulnerabilidade') {
                e.preventDefault();

                garantirAcaoNoPost('avaliar_vulnerabilidade');

                // Remove qualquer overlay para não bloquear a visualização da textarea
                if (typeof esconderOverlay === 'function') esconderOverlay();

                const textarea = form.querySelector('textarea[name="AvaliacaoVulnerabilidadeIA"]');
                if (textarea) textarea.value = '';

                const formData = new FormData(form);
                submitted = true;

                const statusMensagens = [
                    { text: 'Analisando pergunta', duration: 5000 },
                    { text: 'Buscando dados do banco para comparação', duration: 5000 },
                    { text: 'Fazendo análise de vulnerabilidades', duration: 5000 },
                    { text: 'Pensando', duration: 8000 },
                    { text: 'Criando resposta', duration: 5000 }
                ];
                let statusIndex = 0;
                let statusTimer = null;
                let animTimer = null;
                let animDots = 0;

                const pararStatus = () => {
                    if (statusTimer) clearTimeout(statusTimer);
                    statusTimer = null;
                    if (animTimer) clearInterval(animTimer);
                    animTimer = null;
                    animDots = 0;
                };

                const mostrarStatus = () => {
                    if (!textarea) return;
                    if (statusIndex >= statusMensagens.length) return;
                    const item = statusMensagens[statusIndex];
                    if (animTimer) clearInterval(animTimer);
                    animDots = 0;
                    animTimer = setInterval(() => {
                        animDots = (animDots + 1) % 4;
                        textarea.value = item.text + '.'.repeat(animDots);
                    }, 500);
                    statusIndex++;
                    statusTimer = setTimeout(mostrarStatus, item.duration);
                };

                mostrarStatus();

                fetch('<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/beneficiarios/avaliar-vulnerabilidade-stream/', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                }).then(async response => {
                    const contentType = response.headers.get('content-type') || '';
                    if (!response.ok || !response.body || !contentType.includes('text/event-stream')) {
                        const texto = await response.text().catch(() => '');
                        throw new Error(texto || 'Falha ao iniciar a avaliação.');
                    }

                    const reader = response.body.getReader();
                    const decoder = new TextDecoder('utf-8');
                    let buffer = '';
                    let resultado = '';
                    let iniciouResposta = false;
                    const statusLinhas = [];

                    const processarChunk = ({ value, done }) => {
                        if (done) {
                            return;
                        }

                        buffer += decoder.decode(value, { stream: true });
                        const partes = buffer.split('\n\n');
                        buffer = partes.pop() || '';

                        partes.forEach(parte => {
                            let evento = 'message';
                            let dados = [];
                            parte.split('\n').forEach(linha => {
                                if (linha.startsWith('event:')) {
                                    evento = linha.replace('event:', '').trim();
                                } else if (linha.startsWith('data:')) {
                                    dados.push(linha.replace('data:', ''));
                                }
                            });
                            const payload = dados.join('\n');
                            if (payload === '') return;

                            if (evento === 'delta') {
                                if (!iniciouResposta) {
                                    iniciouResposta = true;
                                    pararStatus();
                                    resultado = '';
                                }
                                resultado += payload;
                                if (textarea) textarea.value = resultado;
                            } else if (evento === 'info') {
                                // Ignorado: os status sao controlados no cliente
                            } else if (evento === 'error') {
                                pararStatus();
                                mostrarMensagemAvaliacao(payload.trim());
                            } else if (evento === 'done') {
                                pararStatus();
                                if (textarea) textarea.value = payload;
                            }
                        });

                        return reader.read().then(processarChunk);
                    };

                    return reader.read().then(processarChunk);
                }).catch(err => {
                    pararStatus();
                    mostrarMensagemAvaliacao(err.message || 'Erro ao gerar avaliação.');
                }).finally(() => {
                    submitted = false;
                });

                return;
            }

            // ===== Submit normal (salvar) =====
            submitted = true;

            if (typeof mostrarOverlay === 'function') {
                mostrarOverlay({
                    mensagem: 'Salvando dados, aguarde...',
                    carregando: true
                });
            }

            // desabilita outros botoes
            form.querySelectorAll('button[type="submit"]').forEach(b => {
                if (b !== btn) b.disabled = true;
            });
        });

        // quando volta do POST (bfcache), esconde overlay
        window.addEventListener('pageshow', function() {
            if (typeof esconderOverlay === 'function') esconderOverlay();
        });
    })();
</script>










