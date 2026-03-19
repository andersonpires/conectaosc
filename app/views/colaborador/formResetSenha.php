<!DOCTYPE html>
<html lang="pt-BR">
<?php $statusFiltro = ($_GET['status'] ?? 'ativos') === 'inativos' ? 'inativos' : 'ativos'; ?>

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <style>
        .img-cover {
            object-fit: cover;
            object-position: center;
        }

        /* Garantir que a tabela role horizontalmente em telas pequenas */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            /* Melhora a rolagem no iOS */
        }

        /* Garantir que as colunas se ajustem melhor */
        .table-layout-auto {
            table-layout: auto;
            width: 100%;
            white-space: nowrap;
            /* Comentário ajustado para UTF-8. */
        }

        /* Definir rolagem em telas menores */
        @media (max-width: 767px) {
            .table-responsive {
                display: block;
                width: 100%;
                overflow-x: auto;
            }

            .table-layout-auto th,
            .table-layout-auto td {
                white-space: nowrap;
                /* Evita que o texto das colunas quebre */
                text-align: left;
            }
        }
    </style>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/jquery.dataTables.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="<?php echo $BASE_para_URL; ?>/assets/css/overlayNotifica.css">
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/overlayNotifica.js"></script>

</head>

<script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>

            <main class="content">
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3">Usuários do Sistema</h1>
                    <?php
                    // Exibe mensagem vinda da URL
                    $msg = "";
                    $url = $_SERVER['REQUEST_URI'];
                    $urlDecoded = urldecode($url);
                    if (strpos($urlDecoded, '=') !== false) {
                        $params = explode('&', $urlDecoded);
                        $tipomsg = explode('?', $params[0])[1];
                        $tipomsg = explode('=', $tipomsg)[0]; // Pega o valor antes do '='
                        $msg = explode('=', $params[0])[1];
                    }
                    if ($msg != "") {
                        if ($tipomsg == "msg") {
                    ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $msg ?>
                            </div>
                        <?php
                        } elseif ($tipomsg == "erro") {
                        ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $msg ?>
                            </div>
                    <?php
                        }
                    }
                    ?>
                    <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex flex-wrap align-items-center gap-2">
                            <h5 class="mb-0">Colaboradores cadastrados</h5>
                            <div class="btn-group ms-auto" role="group" aria-label="Filtro de status">
                                <a href="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/colaboradores/reset-senha?status=ativos" class="btn btn-sm <?php echo $statusFiltro === 'ativos' ? 'btn-primary' : 'btn-outline-primary'; ?>">Ativos</a>
                                <a href="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/colaboradores/reset-senha?status=inativos" class="btn btn-sm <?php echo $statusFiltro === 'inativos' ? 'btn-primary' : 'btn-outline-primary'; ?>">Inativos</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="input-group input-group-sm mb-3" style="width: 55%; min-width: 220px; max-width: 520px;">
                                <span class="input-group-text">Procurar</span>
                                <input type="text" class="form-control" id="filtroProcurar" placeholder="Nome, e-mail, CPF...">
                                <button class="btn btn-outline-secondary" type="button" id="limparFiltro">Limpar</button>
                            </div>
                            <div class="table-responsive">
                                <table id="minhaTabela" class="table table-striped table-hover datatable-custom table-layout-auto">
                                    <thead>
                                        <tr>
                                            <th scope="col">Foto</th>
                                            <th scope="col">Nome</th>
                                            <th scope="col">Sobrenome</th>
                                            <th scope="col">Permissão</th>
                                            <th scope="col">CPF</th>
                                            <th scope="col">WhatsApp</th>
                                            <th scope="col">E-mail</th>
                                            <th style="text-align: center;" scope="col">Conferido</th>
                                            <th scope="col">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $habilitadoFiltro = $statusFiltro === 'inativos' ? 0 : 1;
                                        $sql = "SELECT u.*, p.NomePermissao 
                                        FROM tbUser u 
                                        LEFT JOIN tbPermissao p ON u.IdPermissao = p.IdPermissao 
                                        WHERE u.Habilitado = ? 
                                        ORDER BY u.Nome ASC";
                                        $busca = $pdo->prepare($sql);
                                        $busca->execute([$habilitadoFiltro]);
                                        $listaColaboradores = $busca->fetchAll(PDO::FETCH_ASSOC);
                                        if (!empty($listaColaboradores)) {
                                            foreach ($listaColaboradores as $dados) {
                                                $IdColaborador = $dados['IdColaborador'];
                                                $Foto = $dados['Foto'];
                                                $Nome = $dados['Nome'];
                                                $Sobrenome = $dados['Sobrenome'];
                                                $CPF = $dados['CPF'];
                                                $parts = explode('.', $CPF);
                                                $parts[1] = '***';
                                                $masked_CPF = implode('.', $parts);
                                                $Permissao = $dados['NomePermissao'];
                                                $WhatsApp = $dados['WhatsApp'];
                                                $Email = $dados['Email'];
                                                $Email_confere = $dados['Email_confere'];
                                                if ($Email_confere == 0) {
                                                    $Email_confere = "alert-triangle";
                                                } elseif ($Email_confere == 1) {
                                                    $Email_confere = "thumbs-up";
                                                }
                                        ?>
                                                <tr>
                                                    <td>
                                                        <img src="<?php echo htmlspecialchars(bootstrap_foto_url((string)$Foto), ENT_QUOTES, 'UTF-8'); ?>" class="rounded-circle img-cover" width="40px" height="40px">
                                                    </td>
                                                    <td><?php echo $Nome ?></td>
                                                    <td><?php echo $Sobrenome ?></td>
                                                    <td><?php echo $Permissao ?></td>
                                                    <td><?php echo $masked_CPF ?></td>
                                                    <td><?php echo $WhatsApp ?></td>
                                                    <td><?php echo $Email ?></td>
                                                    <td style="text-align: center;"><?php echo '<i class="align-middle" data-feather="' .  $Email_confere . '"></i>' ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-warning btn-sm" onclick="resetaSenha(<?php echo $IdColaborador; ?>)">
                                                            Resetar senha
                                                        </button>
                                                    </td>
                                                </tr>
                                        <?php }
                                        } else { ?>
                                            <tr>
                                                <td colspan="9">Nenhum colaborador cadastrado.</td>
                                            </tr>
                                        <?php }
                                        ?>
                                    </tbody>
                                </table>
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

</body>

</html>

<script>
    $(document).ready(function() {
        function normalizarTexto(valor) {
            return (valor || '')
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/ç/gi, 'c')
                .toLowerCase();
        }

        if ($.fn.dataTable && $.fn.dataTable.ext && $.fn.dataTable.ext.type) {
            $.fn.dataTable.ext.type.search.string = function(data) {
                return normalizarTexto(data);
            };
        }

        const tabela = $('#minhaTabela').DataTable({
            "pageLength": 50,
            "scrollX": true,
            "autoWidth": false,
            "responsive": true,
            "dom": 'rtip',
            "order": [
                [1, 'asc'],
                [2, 'asc']
            ],
            "language": {
                url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json"
            }
        });

        $('#filtroProcurar').on('input keyup', function() {
            tabela.search(normalizarTexto(this.value)).draw();
        });

        $('#limparFiltro').on('click', function() {
            $('#filtroProcurar').val('');
            tabela.search('').draw();
            $('#filtroProcurar').trigger('focus');
        });
    });
</script>


<!-- Comentário interno -->
<!-- Comentário interno -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.12/jquery.mask.min.js"></script>
<script type="text/javascript">
    $("#Telefone, #WhatsApp").mask("(00) 0.0000-0000"); //000 000 0000 eua
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
    const inputCPF = document.querySelector('#CPF');
    inputCPF.addEventListener('blur', function() {
        // Obter o valor do CPF
        const cpf = inputCPF.value;

        // Comentário ajustado para UTF-8.
        const primeirosDigitos = getPrimeiroDigitosCPF(cpf);

        // Obter o input Senha
        const inputSenha = document.querySelector('#Senha');

        // Preencher o input Senha se estiver em branco
        if (inputSenha.value === '') {
            inputSenha.value = primeirosDigitos;
        }
    });

    function getPrimeiroDigitosCPF(cpf) {
        return cpf.replace(/\./g, '').substring(0, 6);
    }
</script>



<script type="text/javascript">
    $("#CPF").keydown(function() {
        try {
            $("#cpfcnpj").unmask();
        } catch (e) {}

        var tamanho = $("#CPF").val().length;

        if (tamanho < 14) {
            $("#CPF").mask("999.999.999-99");
        }
        // ajustando foco
        var elem = this;
        setTimeout(function() {
            // Comentário ajustado para UTF-8.
            elem.selectionStart = elem.selectionEnd = 10000;
        }, 0);
        // reaplico o valor para mudar o foco
        var currentValue = $(this).val();
        $(this).val('');
        $(this).val(currentValue);
    });
</script>
<script>
    $("#cpf").keydown(function() {
        try {
            $("#cpfcnpj").unmask();
        } catch (e) {}

        var tamanho = $("#CPF").val().length;

        if (tamanho < 11) {
            $("#cpf").mask("999.999.999-99");
        }

        // ajustando foco
        var elem = this;
        setTimeout(function() {
            // Comentário ajustado para UTF-8.
            elem.selectionStart = elem.selectionEnd = 10000;
        }, 0);
        // reaplico o valor para mudar o foco
        var currentValue = $(this).val();
        $(this).val('');
        $(this).val(currentValue);
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(() => {
            const alertElement = document.querySelector(".alert");
            if (alertElement) alertElement.style.display = "none";
        }, 2000);
    });
</script>

<script>
    $(function() {
        $('#excluirModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            var id = button.data('id') // data-id
            var nome = button.data('nome')
            var sobrenome = button.data('sobrenome')
            var tipo = button.data('tipo')
            var cpf = button.data('cpf')
            var email = button.data('email')
            var foto = button.data('foto')

            var modal = $(this)
            //aplica valor ao id
            modal.find('.modal-title').text('Excluir dados')
            modal.find('#id').val(id)
            modal.find('#nome').val(nome)
            modal.find('#sobrenome').val(sobrenome)
            modal.find('#tipo').val(tipo)
            modal.find('#cpf').val(cpf)
            modal.find('#email').val(email)
            // modal.find('#foto').val(foto)
            modal.find('#fotostring').val(foto)

            // Para ler o valor do atributo src de todas as imagens com a classe 'imagens'
            var imagens = document.getElementsByClassName('fotografia');
            for (var i = 0; i < imagens.length; i++) {
                var srcValue = imagens[i].getAttribute('src');
            }

            // Para alterar o valor do atributo src de todas as imagens com a classe 'imagens'
            for (var i = 0; i < imagens.length; i++) {
                imagens[i].setAttribute('src', foto ? '<?php echo rtrim(bootstrap_assets_img_url(), '/'); ?>/fotos/' + foto : '<?php echo bootstrap_foto_url(''); ?>');
            }
        });
    });
</script>

<!-- Comentário interno -->
<script>
    function resetaSenha(id) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo $RESET_ROUTE; ?>';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'IdColaborador';
        input.value = id;

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
</script>

<!-- Script para ajustar foto -->
<script>
    const uploadImage = document.getElementById('uploadImage');
    const preview = document.getElementById('preview');
    const cropControls = document.getElementById('cropControls');
    const previewContainer = document.getElementById('previewContainer');
    let cropper;

    uploadImage.addEventListener('change', (event) => {
        const file = event.target.files[0];

        // Validar tipo de arquivo
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
                        // Comentário ajustado para UTF-8.
                        saveCroppedImage();
                    },
                    crop() {
                        // Comentário ajustado para UTF-8.
                        saveCroppedImage();
                    }
                });
            };
            reader.readAsDataURL(file);
        } else {
            alert("Por favor, envie apenas imagens no formato JPEG, JPG ou PNG.");
            uploadImage.value = ''; // Resetar campo de upload
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

                // Substituir o arquivo original no campo de upload
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                uploadImage.files = dataTransfer.files;

            }, "image/jpeg");
        }
    }
</script>

<!-- Mostrar preview da imagem apenas quando usuario selecionar algum arquivo -->
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







