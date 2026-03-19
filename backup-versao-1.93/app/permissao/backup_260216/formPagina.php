<?php
// Início da página: inicialização de session e configuração de timezone
session_set_cookie_params(['httponly' => true]);
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
?>
<?php //require_once $_SESSION['BASE_PATH'] . '/checa-token.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $_SESSION['BASE_PATH'] . '/template/header.php'; ?>
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
            /* Impede quebra de linha nas células */
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="<?php echo $_SESSION['BASE_URL']; ?>/assets/css/overlayNotifica.css">
    <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/overlayNotifica.js"></script>

</head>

<script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>
<script src="//code.jquery.com/jquery-3.2.1.min.js"></script>

<body>
    <div class="wrapper">
        <?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>

        <div class="main">
            <?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php'; ?>

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
                        $tipomsg = explode('?', $params[0])[1]; // Pega o valor após o '?'
                        $tipomsg = explode('=', $tipomsg)[0]; // Pega o valor antes do '='
                        $msg = explode('=', $params[0])[1]; // Pega o valor após o '='
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

                    // Conexão com o banco utilizando PDO
                    require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

                    // Se existir POST com IdColaborador, exibe formulário para alteração
                    if (isset($_POST['IdColaborador']) && !empty($_POST['IdColaborador'])) {
                        $IdColaborador = $_POST['IdColaborador'];
                        $stmt = $pdo->prepare("SELECT u.*, p.NomePermissao FROM tbUser u LEFT JOIN tbPermissao p ON u.IdPermissao = p.IdPermissao WHERE u.IdColaborador = ?");
                        $stmt->execute([$IdColaborador]);
                        $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($colaborador) {
                        ?>
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <form action="alteraColaborador.php" enctype="multipart/form-data" method="post" onsubmit="mostrarOverlay({ mensagem: 'Alterando dados...', carregando: true })">
                                            <input type="hidden" name="IdColaborador" value="<?php echo htmlspecialchars($colaborador['IdColaborador']); ?>">
                                            <div class="row mb-3">
                                                <div class="col-5">
                                                    <label for="Nome" class="form-label">Nome</label>
                                                    <input type="text" class="form-control" id="Nome" name="Nome" value="<?php echo htmlspecialchars($colaborador['Nome']); ?>" required autocomplete="off">
                                                </div>
                                                <div class="col-7">
                                                    <label for="Sobrenome" class="form-label">Sobrenome</label>
                                                    <input type="text" class="form-control" id="Sobrenome" name="Sobrenome" value="<?php echo htmlspecialchars($colaborador['Sobrenome']); ?>" required autocomplete="off">
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-7">
                                                    <label for="CPF" class="form-label">CPF</label>
                                                    <input type="text" class="form-control" id="CPF" name="CPF" value="<?php echo htmlspecialchars($colaborador['CPF']); ?>" readonly autocomplete="off">
                                                </div>
                                                <div class="col-5">
                                                    <label for="Permissao" class="form-label">Perfil</label>
                                                    <select class="form-select" aria-label="Selecione" id="Permissao" name="Permissao" required>
                                                        <option value="">Selecione...</option>
                                                        <?php
                                                        $permissoes = $pdo->query("SELECT IdPermissao, NomePermissao FROM tbPermissao ORDER BY NomePermissao")->fetchAll(PDO::FETCH_ASSOC);
                                                        foreach ($permissoes as $p) {
                                                            $selected = ($colaborador['IdPermissao'] == $p['IdPermissao']) ? 'selected' : '';
                                                            echo "<option value='{$p['IdPermissao']}' $selected>{$p['NomePermissao']}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="WhatsApp" class="form-label">WhatsApp</label>
                                                <input type="text" class="form-control" id="WhatsApp" name="WhatsApp" value="<?php echo htmlspecialchars($colaborador['WhatsApp']); ?>" autocomplete="off">
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-7">
                                                    <label for="Email" class="form-label">E-mail</label>
                                                    <input type="email" class="form-control" id="Email" name="Email" value="<?php echo htmlspecialchars($colaborador['Email']); ?>" required autocomplete="off">
                                                </div>
                                                <div class="col-5">
                                                    <label for="Senha" class="form-label">Senha</label>
                                                    <input type="text" class="form-control" id="Senha" name="Senha" value="Apenas o usuário pode alterar a senha" readonly autocomplete="off">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="fotoedit" class="form-label">Foto colaborador</label>
                                                <br>
                                                <!-- Atualizado o caminho da imagem -->
                                                <img src="<?php echo $_SESSION['BASE_URL']; ?>/assets/img/fotos/<?php echo $colaborador['Foto']; ?>" id="fotoedit" class="rounded-circle img-cover fotografia" width="100px" height="100px">
                                                <p></p>
                                                <input class="form-control mb-3" type="file" name="foto" id="uploadImage" accept=".jpeg, .jpg, .png">
                                                <input type="hidden" name="fotostring" id="fotostring" value="<?php echo htmlspecialchars($colaborador['Foto']); ?>">
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
                                            <div style="text-align: right;">
                                                <button type="submit" class="btn btn-primary">Alterar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php
                        }
                        $stmt = null;
                    } else {
                        ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form action="cadColaborador.php" enctype="multipart/form-data" method="post" onsubmit="mostrarOverlay({ mensagem: 'Salvando dados...', carregando: true })">
                                        <div class="row mb-3">
                                            <div class="col-5">
                                                <label for="Nome" class="form-label">Nome</label>
                                                <input type="text" class="form-control" id="Nome" name="Nome" required autocomplete="off">
                                            </div>
                                            <div class="col-7">
                                                <label for="Sobrenome" class="form-label">Sobrenome</label>
                                                <input type="text" class="form-control" id="Sobrenome" name="Sobrenome" required autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-7">
                                                <label for="CPF" class="form-label">CPF</label>
                                                <input type="text" class="form-control" id="CPF" name="CPF" required autocomplete="off">
                                            </div>
                                            <div class="col-5">
                                                <label for="Permissao" class="form-label">Perfil</label>
                                                <select class="form-select" aria-label="Selecione" name="Permissao" required>
                                                    <option value="">Selecione...</option>
                                                    <?php
                                                    require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
                                                    $permissoes = $pdo->query("SELECT IdPermissao, NomePermissao FROM tbPermissao ORDER BY NomePermissao")->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach ($permissoes as $p) {
                                                        echo "<option value='{$p['IdPermissao']}'>{$p['NomePermissao']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                        </div>
                                        <div class="mb-3">
                                            <label for="WhatsApp" class="form-label">WhatsApp</label>
                                            <input type="text" class="form-control" id="WhatsApp" name="WhatsApp" autocomplete="off">
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-7">
                                                <label for="Email" class="form-label">E-mail</label>
                                                <input type="email" class="form-control" id="Email" name="Email" required autocomplete="off">
                                            </div>
                                            <div class="col-5">
                                                <label for="Senha" class="form-label">Senha</label>
                                                <input type="text" class="form-control" id="Senha" name="Senha" autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="uploadImage" class="form-label">Foto colaborador</label>
                                            <input class="form-control mb-3" type="file" name="foto" id="uploadImage" accept=".jpeg, .jpg, .png">
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
                                        <div style="text-align: right;">
                                            <button type="submit" class="btn btn-primary">Cadastrar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </main>

            <main class="content">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Colaboradores cadastrados</h5>
                        </div>
                        <div class="card-body">
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
                                        $sql = "SELECT u.*, p.NomePermissao 
                                        FROM tbUser u 
                                        LEFT JOIN tbPermissao p ON u.IdPermissao = p.IdPermissao 
                                        WHERE u.Habilitado = 1 
                                        ORDER BY u.Nome ASC";

                                        $busca = $pdo->query($sql);
                                        if ($busca->rowCount() <= 1) {
                                            $resultado = "Sem registros para exibir";
                                        } else {
                                            while ($dados = $busca->fetch(PDO::FETCH_ASSOC)) {
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
                                                        <img src="<?php echo $_SESSION['BASE_URL']; ?>/assets/img/fotos/<?php echo $Foto ?>" class="rounded-circle img-cover" width="40px" height="40px">
                                                    </td>
                                                    <td><?php echo $Nome ?></td>
                                                    <td><?php echo $Sobrenome ?></td>
                                                    <td><?php echo $Permissao ?></td>
                                                    <td><?php echo $masked_CPF ?></td>
                                                    <td><?php echo $WhatsApp ?></td>
                                                    <td><?php echo $Email ?></td>
                                                    <td style="text-align: center;"><?php echo '<i class="align-middle" data-feather="' .  $Email_confere . '"></i>' ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-warning btn-sm" onclick="alterarColaborador(<?php echo $IdColaborador; ?>)">
                                                            <i class="fa-solid fa-pen-to-square" data-feather="edit-3"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#excluirModal" data-id="<?php echo $IdColaborador ?>" data-nome="<?php echo $Nome ?>" data-tipo="<?php echo $Permissao ?>" data-sobrenome="<?php echo $Sobrenome ?>" data-cpf="<?php echo $CPF ?>" data-email="<?php echo $Email ?>" data-foto="<?php echo $Foto ?>">
                                                            <i class="fa-solid fa-trash" data-feather="trash-2"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                        <?php }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="modal fade" id="excluirModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="exampleModalLabel">Excluir usuários</h1>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="excluirColaborador.php" method="post" onsubmit="mostrarOverlay({ mensagem: 'Excluindo dados...', carregando: true })">
                                                <div class="mb-3">
                                                    <div class="mb-3">
                                                        <label for="nome" class="form-label">Nome</label>
                                                        <input type="text" class="form-control" id="nome" name="nome" readonly autocomplete="off">
                                                        <input type="hidden" name="id" id="id">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="sobrenome" class="form-label">Sobrenome</label>
                                                        <input type="text" class="form-control" id="sobrenome" name="sobrenome" readonly autocomplete="off">
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="mb-3">
                                                        <label for="tipo" class="form-label">Perfil</label>
                                                        <select disabled class="form-select" aria-label="Selecione" id="tipo" name="tipo">
                                                            <option value="Educador">Educador</option>
                                                            <option value="Geral">Geral</option>
                                                            <option value="Administrador">Administrador</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="WhatsApp" class="form-label">WhatsApp</label>
                                                    <input type="text" class="form-control" id="WhatsApp" name="WhatsApp" autocomplete="off">
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="mb-3">
                                                        <label for="email" class="form-label">E-mail</label>
                                                        <input type="email" class="form-control" id="email" name="email" readonly autocomplete="off">
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div style="text-align: center;" class="mb-3">
                                                        <label for="foto" class="form-label">Foto usuário</label>
                                                        <br>
                                                        <img src="<?php echo $_SESSION['BASE_URL']; ?>/assets/img/fotos/<?php echo $Foto ?>" id="foto" class="rounded-circle img-cover fotografia" width="100px" height="100px">
                                                        <br>
                                                    </div>
                                                </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-danger">Excluir</button>
                                        </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="footer">
                <?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php'; ?>
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
        $('#minhaTabela').DataTable({
            "pageLength": 50,
            "scrollX": true, // Adiciona barra de rolagem lateral se necessário
            "autoWidth": false, // Impede que a largura da tabela fique fixa e cause cortes
            "responsive": true, // Permite melhor responsividade no DataTables
            "language": {
                url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json"
            }
        });
    });
</script>


<!-- Importação do Jquery Mask -->
<!-- include da importação da mascara dos inputs -->
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

        // Obter os 6 primeiros dígitos
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
            // mudo a posição do seletor
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
            // mudo a posição do seletor
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
                imagens[i].setAttribute('src', '<?php echo $_SESSION['BASE_URL'] . '/assets/img/fotos/' ?>' + foto);
            }
        });
    });
</script>

<!-- Cria o <form> para o botão Alterar -->
<script>
    function alterarColaborador(id) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'formColaborador.php'; // Envia para o próprio arquivo

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
                    aspectRatio: 1, // Proporção ajustável
                    viewMode: 1,
                    ready() {
                        // Salvar automaticamente após inicialização
                        saveCroppedImage();
                    },
                    crop() {
                        // Salvar automaticamente em cada modificação
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