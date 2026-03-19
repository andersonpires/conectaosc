<?php
session_set_cookie_params(['httponly' => true]);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url");
    exit();
}

require_once $_SESSION['BASE_PATH'] . '/checa-token.php';
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

$config = $pdo->query("SELECT * FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $_SESSION['BASE_PATH'] . '/template/header.php'; ?>
    <link rel="stylesheet" href="<?php echo $_SESSION['BASE_URL']; ?>/assets/css/overlayNotifica.css">
    <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/overlayNotifica.js"></script>
    <script src="https://cdn.tiny.cloud/1/maa7p6ervwrty9h84gphxrsrfking1awabjrp7tak98iuwjh/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://unpkg.com/feather-icons"></script>

    <script>
        // tinymce.init({
            selector: '#TermosUso',
            menubar: false,
            height: 250, // altura inicial
            max_height: 400, // altura máxima configurada para a caixa
            autoresize_overflow_padding: 10,
            autoresize_bottom_margin: 10,
            autoresize_min_height: 250,
            autoresize_max_height: 400,
            autoresize_on_init: true,
            plugins: 'autoresize image link lists code',
            toolbar: 'undo redo | styles | bold italic underline | bullist numlist | link image | code',
            language: 'pt_BR',
            branding: false,
            setup: function(editor) {
                editor.on('init', function() {
                    editor.getContainer().style.maxHeight = "400px"; // aqui força o máximo da div externa
                    editor.getContainer().style.overflowY = "auto"; // barra de rolagem se ultrapassar
                });
            },
            images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'crmController.php');

                xhr.onload = () => {
                    if (xhr.status < 200 || xhr.status >= 300) {
                        reject('HTTP Error: ' + xhr.status);
                        return;
                    }

                    let json;
                    try {
                        json = JSON.parse(xhr.responseText);
                    } catch (e) {
                        reject('Erro ao processar JSON: ' + xhr.responseText);
                        return;
                    }

                    if (!json || typeof json.location !== 'string') {
                        reject('Resposta inválida: ' + xhr.responseText);
                        return;
                    }

                    resolve(json.location);
                };

                xhr.onerror = () => {
                    reject('Erro de conexão. Código: ' + xhr.status);
                };

                const formData = new FormData();
                formData.append('action', 'uploadImagemNota');
                formData.append('file', blobInfo.blob(), blobInfo.filename());

                xhr.send(formData);
            })
        });
    </script>

</head>
<script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>

<body>
    <div class="wrapper">
        <?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>
        <div class="main">
            <?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php'; ?>
            <main class="content">
                <div class="container-fluid p-0">
                    <?php
                    // Exibe mensagem vinda da URL
                    $msg = "";
                    $url = $_SERVER['REQUEST_URI'];
                    $urlDecoded = urldecode($url);
                    if (strpos($urlDecoded, '=') !== false) {
                        $params = explode('&', $urlDecoded);
                        $tipomsg = explode('?', $params[0])[1];
                        $tipomsg = explode('=', $tipomsg)[0];
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
                    <h1 class="h3 mb-3">Configurações do Sistema</h1>

                    <div class="card mb-4">
                        <div class="card-body">
                            <form action="salvarConfig.php" method="post" onsubmit="mostrarOverlay({mensagem: 'Salvando configurações...', carregando: true})">
                                <div class="mb-3">
                                    <label for="NomeSistema" class="form-label">Nome do Sistema</label>
                                    <input type="text" class="form-control" id="NomeSistema" name="NomeSistema" value="<?php echo htmlspecialchars($config['NomeSistema'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="TituloPagina" class="form-label">Título da Página</label>
                                    <input type="text" class="form-control" id="TituloPagina" name="TituloPagina" value="<?php echo htmlspecialchars($config['TituloPagina'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="MetaDescription" class="form-label">Meta Description</label>
                                    <input type="text" class="form-control" id="MetaDescription" name="MetaDescription" value="<?php echo htmlspecialchars($config['MetaDescription'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="MetaAuthor" class="form-label">Meta Author</label>
                                    <input type="text" class="form-control" id="MetaAuthor" name="MetaAuthor" value="<?php echo htmlspecialchars($config['MetaAuthor'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="MetaKeywords" class="form-label">Meta Keywords</label>
                                    <input type="text" class="form-control" id="MetaKeywords" name="MetaKeywords" value="<?php echo htmlspecialchars($config['MetaKeywords'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="CorPrimaria" class="form-label">Cor Primária</label>
                                    <input type="color" class="form-control form-control-color" id="CorPrimaria" name="CorPrimaria" value="<?php echo htmlspecialchars($config['CorPrimaria'] ?? '#0d6efd'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="CorSecundaria" class="form-label">Cor Secundária</label>
                                    <input type="color" class="form-control form-control-color" id="CorSecundaria" name="CorSecundaria" value="<?php echo htmlspecialchars($config['CorSecundaria'] ?? '#6c757d'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="LogoSistema" class="form-label">Logo do Sistema</label>
                                    <input type="text" class="form-control" id="LogoSistema" name="LogoSistema" value="<?php echo htmlspecialchars($config['LogoSistema'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="LogoImpressao" class="form-label">Logo para impressao</label>
                                    <input type="text" class="form-control" id="LogoImpressao" name="LogoImpressao" value="<?php echo htmlspecialchars($config['LogoImpressao'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="ShortcutIcon" class="form-label">Shortcut Icon</label>
                                    <input type="text" class="form-control" id="ShortcutIcon" name="ShortcutIcon" value="<?php echo htmlspecialchars($config['ShortcutIcon'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="APIzap" class="form-label">API WhatsApp</label>
                                    <textarea class="form-control" id="APIzap" name="APIzap" rows="2"><?php echo htmlspecialchars($config['APIzap'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="HeaderEmail" class="form-label">Cabeçalho de E-mail</label>
                                    <textarea class="form-control" id="HeaderEmail" name="HeaderEmail" rows="2"><?php echo htmlspecialchars($config['HeaderEmail'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="TermosUso" class="form-label">Termos de Uso</label>
                                    <textarea class="form-control" id="TermosUso" name="TermosUso" rows="10"><?php echo htmlspecialchars($config['TermosUso'] ?? ''); ?></textarea>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">Salvar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="footer">
                <?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php'; ?>
            </footer>
        </div>
    </div>
    <script>
        feather.replace();
    </script>

</body>

</html>
