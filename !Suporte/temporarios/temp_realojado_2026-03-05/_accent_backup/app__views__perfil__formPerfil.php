<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_PATH = $runtime['base_path'];
$BASE_URL = $runtime['base_url'];
$appJsVersion = @filemtime($BASE_PATH . '/app/assets/js/app.js') ?: time();
// Arquivo de view (form)
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_PATH . '/app/template/header.php'; ?>
    <style>
        .img-cover {
            object-fit: cover;
            object-position: center;
        }

        .btn-especialidade {
            width: 34px;
            height: 34px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/assets/css/overlayNotifica.css">
    <script src="<?php echo $BASE_URL; ?>/assets/js/overlayNotifica.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
</head>
<script src="//code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="<?php echo $BASE_URL; ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>
<script>
    feather.replace();
</script>

</body>

</html>

<body>
    <div class="wrapper">
        <?php require_once $BASE_PATH . '/app/template/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_PATH . '/app/template/topo.php'; ?>

            <main class="content">
                <div class="container-fluid p-0">

                    <div class="mb-3">
                        <h1 class="h3 d-inline align-middle">Perfil</h1>
                    </div>
                    <div class="row">
                        <div class="col-md-4 col-xl-3">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Detalhes do perfil</h5>
                                </div>
                                <div class="card-body text-center">
                                    <img src="<?php echo $BASE_URL; ?>/assets/img/fotos/<?php echo $colaborador['Foto']; ?>" alt="<?php echo $NomeColaborador; ?>" class="rounded-circle img-cover mb-2" width="128" height="128" />
                                    <h5 class="card-title mb-0"><?php echo $NomeColaborador; ?></h5>
                                    <div class="text-muted mb-2"><?php echo htmlspecialchars($colaborador['Trabalho']); ?></div>
                                </div>
                                <hr class="my-0" />
                                <div class="card-body">
                                    <h5 class="h6 card-title">Compet?f?????T?f?????,????f???,?s?f??s?,?ncias</h5>
                                    <a href="#" class="badge bg-primary me-1 my-1">Projetos</a>
                                    <a href="#" class="badge bg-primary me-1 my-1">Comunica?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o</a>
                                    <a href="#" class="badge bg-primary me-1 my-1">Eventos</a>
                                    <a href="#" class="badge bg-primary me-1 my-1">Atendimento ao p?f?????T?f?????,????f???,?s?f??s?,?blico</a>
                                    <a href="#" class="badge bg-primary me-1 my-1">Cadastro</a>
                                    <a href="#" class="badge bg-primary me-1 my-1">Gest?f?????T?f?????,????f???,?s?f??s?,?o</a>
                                </div>
                                <hr class="my-0" />
                                <div class="card-body">
                                    <h5 class="h6 card-title">Sobre</h5>
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-1"><span data-feather="home" class="feather-sm me-1"></span> Mora em <a href="#"><?php echo htmlspecialchars($colaborador['CidadeEstado']); ?></a></li>
                                        <li class="mb-1"><span data-feather="briefcase" class="feather-sm me-1"></span> Trabalha como <a href="#"><?php echo htmlspecialchars($colaborador['Trabalho']); ?></a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8 col-xl-9">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Dados do cadastro</h5>
                                </div>
                                <div class="card-body h-100">
                                    <div class="col-12">
                                        <div class="col-12">
                                            <div class="card">
                                                <div class="card-body">
                                                    <form action="<?php echo rtrim((string)$BASE_URL, '/'); ?>/perfil/" enctype='multipart/form-data' method='post' onsubmit="return avisoSalvarPerfil();">
                                                        <input type="hidden" name="IdColaborador" value="<?php echo htmlspecialchars($colaborador['IdColaborador']); ?>">
                                                        <div class="row mb-3">
                                                            <div class="col-5">
                                                                <label for="Nome" class="form-label">Nome</label>
                                                                <input type="text" class="form-control" id="Nome" name='Nome' value="<?php echo htmlspecialchars($colaborador['Nome']); ?>" autocomplete="off">
                                                            </div>
                                                            <div class="col-7">
                                                                <label for="Sobrenome" class="form-label">Sobrenome</label>
                                                                <input type="text" class="form-control" id="Sobrenome" name='Sobrenome' value="<?php echo htmlspecialchars($colaborador['Sobrenome']); ?>" autocomplete="off">
                                                            </div>
                                                        </div>

                                                        <div class="row mb-3">
                                                            <div class="col-7">
                                                                <label for="CPF" class="form-label">CPF</label>
                                                                <input type="text" class="form-control" id="CPF" name='CPF' value="<?php echo htmlspecialchars($colaborador['CPF']); ?>" readonly autocomplete="off">
                                                            </div>
                                                            <div class="col-5">
                                                                <label for="Tipo" class="form-label">Perfil</label>
                                                                <select class="form-select" aria-label="Selecione" id='Tipo' name='Tipo' disabled>
                                                                    <option value="Educador" <?php echo $colaborador['Tipo'] == 'Educador' ? 'selected' : ''; ?>>Educador</option>
                                                                    <option value="Geral" <?php echo $colaborador['Tipo'] == 'Geral' ? 'selected' : ''; ?>>Geral</option>
                                                                    <option value="Administrador" <?php echo $colaborador['Tipo'] == 'Administrador' ? 'selected' : ''; ?>>Administrador</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <div class="col-7">
                                                                <label for="Email" class="form-label">E-mail</label>
                                                                <input type="email" class="form-control" id="Email" name='Email' value="<?php echo htmlspecialchars($colaborador['Email']); ?>" required autocomplete="off">
                                                            </div>
                                                            <div class="col-5">
                                                                <label for="Senha" class="form-label">Senha</label>
                                                                <input type="text" class="form-control" id="Senha" name='Senha' autocomplete="off">
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <div class="col-5">
                                                                <label for="WhatsApp" class="form-label">WhatsApp</label>
                                                                <input type="text" class="form-control" id="WhatsApp" name='WhatsApp' value="<?php echo htmlspecialchars($colaborador['WhatsApp']); ?>" autocomplete="off">
                                                            </div>
                                                            <div class="col-4">
                                                                <label for="CidadeEstado" class="form-label">Mora em</label>
                                                                <input type="text" class="form-control" id="CidadeEstado" name='CidadeEstado' value="<?php echo htmlspecialchars($colaborador['CidadeEstado']); ?>" autocomplete="off">
                                                            </div>
                                                            <div class="col-3">
                                                                <label for="Trabalho" class="form-label">Trabalha como</label>
                                                                <input type="text" class="form-control" id="Trabalho" name='Trabalho' value="<?php echo htmlspecialchars($colaborador['Trabalho']); ?>" autocomplete="off">
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" role="switch" id="profissional_saude" name="profissional_saude" value="1" <?php echo !empty($colaborador['profissional_saude']) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="profissional_saude">Profissional de sa?f?????T?f?????,????f???,?s?f??s?,?de</label>
                                                            </div>
                                                        </div>
                                                        <div id="profissionalSaudeCampos" class="border rounded p-3 mb-3" style="display: none;">
                                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                                <label class="form-label mb-0">Especialidades</label>
                                                                <button type="button" class="btn btn-outline-primary btn-sm btn-especialidade" id="addEspecialidade">+</button>
                                                            </div>
                                                            <div id="especialidadesContainer">
                                                                <?php
                                                                $itensEspecialidade = $especialidadesProfissionais;
                                                                if (empty($itensEspecialidade)) {
                                                                    $itensEspecialidade = [[]];
                                                                }
                                                                ?>
                                                                <?php foreach ($itensEspecialidade as $idx => $item): ?>
                                                                <div class="row g-2 align-items-end especialidade-item" data-index="<?php echo (int)$idx; ?>">
                                                                    <div class="col-md-4">
                                                                        <label class="form-label">Especialidade</label>
                                                                        <select class="form-select" data-name="especialidade_id" name="especialidades[<?php echo (int)$idx; ?>][especialidade_id]">
                                                                            <option value="">Nenhuma</option>
                                                                            <?php foreach ($especialidades as $e): ?>
                                                                            <option value="<?php echo (int)$e['id']; ?>" <?php echo (isset($item['especialidade_id']) && (int)$item['especialidade_id'] === (int)$e['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($e['nome']); ?></option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <label class="form-label">Conselho</label>
                                                                        <input type="text" class="form-control" data-name="conselho" name="especialidades[<?php echo (int)$idx; ?>][conselho]" value="<?php echo htmlspecialchars($item['Conselho'] ?? ''); ?>">
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <label class="form-label">UF</label>
                                                                        <select class="form-select" data-name="uf" name="especialidades[<?php echo (int)$idx; ?>][uf]">
                                                                            <option value="">UF...</option>
                                                                            <?php foreach ($ufs as $uf): ?>
                                                                            <option value="<?php echo $uf; ?>" <?php echo (isset($item['UF']) && $item['UF'] === $uf) ? 'selected' : ''; ?>><?php echo $uf; ?></option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <label class="form-label">No registro</label>
                                                                        <input type="text" class="form-control" data-name="registro" name="especialidades[<?php echo (int)$idx; ?>][registro]" value="<?php echo htmlspecialchars($item['NumeroRegistro'] ?? ''); ?>">
                                                                    </div>
                                                                    <div class="col-md-1 d-grid">
                                                                        <?php if (count($itensEspecialidade) > 1): ?>
                                                                        <button type="button" class="btn btn-danger btn-sm btn-especialidade remove-especialidade" title="Remover especialidade">
                                                                            <i class="fa-solid fa-trash"></i>
                                                                        </button>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                            <small class="text-muted">Defina as especialidades e dados do conselho para o colaborador.</small>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="uploadImage" class="form-label">Foto colaborador</label>
                                                            <input class="form-control mb-3" type="file" name="foto" id="uploadImage" accept=".jpeg, .jpg, .png">
                                                            <input type="hidden" name="fotostring" id="fotostring" value="<?php echo htmlspecialchars($colaborador['Foto']); ?>">

                                                            <div id="previewContainer" style="display: none; margin-top: 10px;">
                                                                <img id="preview" style="width: 300px; height: 300px; margin-top: 10px;">
                                                                <div id="cropControls" style="display: none; margin-top: 10px;">
                                                                    <button id="rotateLeft">Girar Esquerda</button>
                                                                    <button id="rotateRight">Girar Direita</button>
                                                                    <button id="flipHorizontal">Inverter Horizontal</button>
                                                                    <button id="flipVertical">Inverter Vertical</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                </div>
                                                <div style="text-align: right;">
                                                    <button type="submit" class="btn btn-primary">Salvar altera?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?es</button>
                                                </div>
                                                </form>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
            </main>

            <footer class="footer">
                <?php require_once $BASE_PATH . '/app/template/footer.php' ?>
            </footer>
        </div>
    </div>
    <script src="<?php echo $BASE_URL; ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>
    <script>
        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            const foco = urlParams.get('m');

            if (foco === 'mail') {
                $('#Email').focus();
            } else if (foco === 'ws') {
                $('#WhatsApp').focus();
            }
        });
    </script>

<div id="overlayLoading" style="display: none;">
    <div class="overlay-bg"></div>
    <div class="overlay-content" id="overlayContent">
        <div class="spinner-border text-primary" role="status" id="overlaySpinner">
            <span class="visually-hidden">Carregando...</span>
        </div>
        <p class="mt-2" id="overlayText">Salvando dados...</p>
        <button id="overlayBtnOk" class="btn btn-primary mt-3" style="display: none;">OK</button>
    </div>
</div>

<script>
    const uploadImage = document.getElementById('uploadImage');
    const preview = document.getElementById('preview');
    const cropControls = document.getElementById('cropControls');
    const previewContainer = document.getElementById('previewContainer');
    let cropper;

    uploadImage.addEventListener('change', (event) => {
        const file = event.target.files[0];

        if (file && ['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.src = e.target.result;
                preview.style.display = 'block';
                previewContainer.style.display = 'block';
                cropControls.style.display = 'block';

                if (cropper) cropper.destroy();
                cropper = new Cropper(preview, {
                    aspectRatio: 1,
                    viewMode: 1,
                    ready() {
                        saveCroppedImage();
                    },
                    crop() {
                        saveCroppedImage();
                    }
                });
            };
            reader.readAsDataURL(file);
        } else {
            alert("Por favor, envie apenas imagens no formato JPEG, JPG ou PNG.");
            uploadImage.value = '';
        }
    });

    document.getElementById('rotateLeft').addEventListener('click', () => {
        if (cropper) cropper.rotate(-90);
    });

    document.getElementById('rotateRight').addEventListener('click', () => {
        if (cropper) cropper.rotate(90);
    });

    document.getElementById('flipHorizontal').addEventListener('click', () => {
        if (cropper) cropper.scaleX(cropper.getData().scaleX === 1 ? -1 : 1);
    });

    document.getElementById('flipVertical').addEventListener('click', () => {
        if (cropper) cropper.scaleY(cropper.getData().scaleY === 1 ? -1 : 1);
    });

    function saveCroppedImage() {
        if (cropper) {
            cropper.getCroppedCanvas().toBlob((blob) => {
                const file = new File([blob], "foto-ajustada.jpg", {
                    type: "image/jpeg"
                });

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                uploadImage.files = dataTransfer.files;

            }, "image/jpeg");
        }
    }
</script>

<script>
    function previewImage(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewContainer = document.getElementById('imagePreviewContainer');
                const previewImage = document.getElementById('imagePreview');
                previewImage.src = e.target.result;
                previewContainer.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }
</script>
<script>
    function avisoSalvarPerfil() {
        if (typeof mostrarOverlay === 'function') {
            mostrarOverlay({
                mensagem: 'Ap?f?????T?f?????,????f???,?s?f??s?,?s salvar as altera?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?es, voc?f?????T?f?????,????f???,?s?f??s?,? precisar?f?????T?f?????,????f???,?s?f??s?,? logar-se novamente!',
                carregando: true
            });
        }
        return true;
    }
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.12/jquery.mask.min.js"></script>
<script type="text/javascript">
    $("#Telefone, #WhatsApp").mask("(00) 0.0000-0000");
    $("#Nascimento").mask('00/00/0000');
    $('.time').mask('00:00:00');
    $('.date_time').mask('00/00/0000 00:00:00');
    $('.cep').mask('00000-000');
    $('.phone').mask('0000-0000');
    $('.phone_with_ddd').mask('(00) 0000-0000');
    $('.phone_us').mask('(000) 000-0000');
    $('.mixed').mask('AAA 000-S0S');
    $('#CPF').mask('000.000.000-00', {
        reverse: true
    });
    $('.money').mask('000.000.000.000.000,00', {
        reverse: true
    });
    $('#Valor').mask('000.000.000.000.000,00', {
        reverse: true
    });
    $('#valor').mask('000.000.000.000.000,00', {
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

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const toggle = document.getElementById('profissional_saude');
        const campos = document.getElementById('profissionalSaudeCampos');
        const container = document.getElementById('especialidadesContainer');
        const addBtn = document.getElementById('addEspecialidade');

        function atualizarIndices() {
            if (!container) return;
            const items = container.querySelectorAll('.especialidade-item');
            items.forEach((item, idx) => {
                item.dataset.index = idx;
                item.querySelectorAll('[data-name]').forEach((el) => {
                    const base = el.getAttribute('data-name');
                    if (base) {
                        el.name = `especialidades[${idx}][${base}]`;
                    }
                });
            });
        }

        function limparValores(item) {
            item.querySelectorAll('input').forEach((el) => {
                el.value = '';
            });
            item.querySelectorAll('select').forEach((el) => {
                el.selectedIndex = 0;
            });
        }

        function atualizarRemover() {
            if (!container) return;
            const items = container.querySelectorAll('.especialidade-item');
            items.forEach((item) => {
                const btn = item.querySelector('.remove-especialidade');
                if (!btn) return;
                btn.style.display = items.length > 1 ? '' : 'none';
            });
        }

        function setEnabled(enabled) {
            if (!campos) return;
            campos.style.display = enabled ? '' : 'none';
            campos.querySelectorAll('input, select, button.remove-especialidade').forEach((el) => {
                el.disabled = !enabled;
            });
            if (addBtn) addBtn.disabled = !enabled;
        }

        if (addBtn && container) {
            addBtn.addEventListener('click', () => {
                const first = container.querySelector('.especialidade-item');
                if (!first) return;
                const clone = first.cloneNode(true);
                limparValores(clone);
                container.appendChild(clone);
                atualizarIndices();
                atualizarRemover();
            });
        }

        if (container) {
            container.addEventListener('click', (event) => {
                const btn = event.target.closest('.remove-especialidade');
                if (!btn) return;
                const items = container.querySelectorAll('.especialidade-item');
                const item = btn.closest('.especialidade-item');
                if (!item) return;
                if (items.length <= 1) {
                    limparValores(item);
                    return;
                }
                item.remove();
                atualizarIndices();
                atualizarRemover();
            });
        }

        if (toggle) {
            toggle.addEventListener('change', () => setEnabled(toggle.checked));
            setEnabled(toggle.checked);
        }
        atualizarRemover();
    });
</script>




