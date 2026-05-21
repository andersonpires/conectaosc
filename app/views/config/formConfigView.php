<?php
$BASE_para_PATH = $BASE_para_PATH ?? '';
$BASE_para_URL = $BASE_para_URL ?? '';
$configCurrent = [];
$colaboradoresAtivos = [];

if ($BASE_para_PATH !== '') {
    require_once $BASE_para_PATH . '/api/repositories/ConfigRepository.php';
    require_once $BASE_para_PATH . '/api/services/ConfigService.php';
    try {
        $configService = new \BackEnd\Services\ConfigService(new \BackEnd\Repositories\ConfigRepository());
        $configCurrent = $configService->getConfig();
        $colaboradoresAtivos = $configService->getActiveCollaborators();
    } catch (Throwable $e) {
        $configCurrent = [];
        $colaboradoresAtivos = [];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <link rel="stylesheet" href="<?php echo $BASE_para_URL; ?>/assets/css/overlayNotifica.css">
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/overlayNotifica.js"></script>
    <script src="https://cdn.tiny.cloud/1/maa7p6ervwrty9h84gphxrsrfking1awabjrp7tak98iuwjh/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://unpkg.com/feather-icons"></script>

    <script>
        tinymce.init({
            selector: '#TermosUso',
            menubar: false,
            height: 250,
            max_height: 400,
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
                    editor.getContainer().style.maxHeight = '400px';
                    editor.getContainer().style.overflowY = 'auto';
                });
            },
            images_upload_handler: (blobInfo) => new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/crm/salvar/');

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
<script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>
        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
            <main class="content">
                <div class="container-fluid p-0">
                    <?php
                    $msg = '';
                    $url = $_SERVER['REQUEST_URI'];
                    $urlDecoded = urldecode($url);
                    if (strpos($urlDecoded, '=') !== false) {
                        $params = explode('&', $urlDecoded);
                        $tipomsg = explode('?', $params[0])[1];
                        $tipomsg = explode('=', $tipomsg)[0];
                        $msg = explode('=', $params[0])[1];
                    }
                    if ($msg !== '') {
                        if ($tipomsg === 'msg') {
                    ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $msg; ?>
                            </div>
                        <?php
                        } elseif ($tipomsg === 'erro') {
                        ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $msg; ?>
                            </div>
                    <?php
                        }
                    }
                    ?>

                    <h1 class="h3 mb-3">Configurações do Sistema</h1>

                    <div class="card mb-4">
                        <div class="card-body">
                            <form id="configForm" method="post" action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/configuracoes">
                                <div class="mb-3">
                                    <label for="NomeSistema" class="form-label">Nome do Sistema</label>
                                    <input type="text" class="form-control" id="NomeSistema" name="NomeSistema" value="<?php echo htmlspecialchars((string)($configCurrent['NomeSistema'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="TituloPagina" class="form-label">Título da página</label>
                                    <input type="text" class="form-control" id="TituloPagina" name="TituloPagina" value="<?php echo htmlspecialchars((string)($configCurrent['TituloPagina'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="MetaDescription" class="form-label">Meta Description</label>
                                    <input type="text" class="form-control" id="MetaDescription" name="MetaDescription" value="<?php echo htmlspecialchars((string)($configCurrent['MetaDescription'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="MetaAuthor" class="form-label">Meta Author</label>
                                    <input type="text" class="form-control" id="MetaAuthor" name="MetaAuthor" value="<?php echo htmlspecialchars((string)($configCurrent['MetaAuthor'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="IdColaboradorDirigente" class="form-label">Dirigente da organização</label>
                                    <select class="form-select" id="IdColaboradorDirigente" name="IdColaboradorDirigente">
                                        <option value="">Selecione</option>
                                        <?php
                                        $dirigenteSelecionado = (int)($configCurrent['IdColaboradorDirigente'] ?? 0);
                                        foreach ($colaboradoresAtivos as $colaborador) {
                                            $idColab = (int)($colaborador['IdColaborador'] ?? 0);
                                            if ($idColab <= 0) {
                                                continue;
                                            }
                                            $nome = trim((string)($colaborador['Nome'] ?? ''));
                                            $sobrenome = trim((string)($colaborador['Sobrenome'] ?? ''));
                                            $nomeCompleto = trim($nome . ' ' . $sobrenome);
                                            if ($nomeCompleto === '') {
                                                $nomeCompleto = 'Colaborador #' . $idColab;
                                            }
                                            $selected = $idColab === $dirigenteSelecionado ? ' selected' : '';
                                            echo '<option value="' . $idColab . '"' . $selected . '>' . htmlspecialchars($nomeCompleto, ENT_QUOTES, 'UTF-8') . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="CargoDirigente" class="form-label">Cargo</label>
                                    <input type="text" class="form-control" id="CargoDirigente" name="CargoDirigente" value="<?php echo htmlspecialchars((string)($configCurrent['CargoDirigente'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="MetaKeywords" class="form-label">Meta Keywords</label>
                                    <input type="text" class="form-control" id="MetaKeywords" name="MetaKeywords" value="<?php echo htmlspecialchars((string)($configCurrent['MetaKeywords'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="CorPrimaria" class="form-label">Cor Primária</label>
                                    <input type="color" class="form-control form-control-color" id="CorPrimaria" name="CorPrimaria" value="<?php echo htmlspecialchars((string)($configCurrent['CorPrimaria'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="CorSecundaria" class="form-label">Cor Secundária</label>
                                    <input type="color" class="form-control form-control-color" id="CorSecundaria" name="CorSecundaria" value="<?php echo htmlspecialchars((string)($configCurrent['CorSecundaria'] ?? '#6c757d'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="LogoSistema" class="form-label">Logo do Sistema</label>
                                    <input type="text" class="form-control" id="LogoSistema" name="LogoSistema" value="<?php echo htmlspecialchars((string)($configCurrent['LogoSistema'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="LogoImpressao" class="form-label">Logo para impressão</label>
                                    <input type="text" class="form-control" id="LogoImpressao" name="LogoImpressao" value="<?php echo htmlspecialchars((string)($configCurrent['LogoImpressao'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="ShortcutIcon" class="form-label">Shortcut Icon</label>
                                    <input type="text" class="form-control" id="ShortcutIcon" name="ShortcutIcon" value="<?php echo htmlspecialchars((string)($configCurrent['ShortcutIcon'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="APIzap" class="form-label">API WhatsApp</label>
                                    <textarea class="form-control" id="APIzap" name="APIzap" rows="2"><?php echo htmlspecialchars((string)($configCurrent['APIzap'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="HeaderEmail" class="form-label">Cabeçalho de E-mail</label>
                                    <textarea class="form-control" id="HeaderEmail" name="HeaderEmail" rows="2"><?php echo htmlspecialchars((string)($configCurrent['HeaderEmail'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="TermosUso" class="form-label">Termos de Uso</label>
                                    <textarea class="form-control" id="TermosUso" name="TermosUso" rows="10"><?php echo htmlspecialchars((string)($configCurrent['TermosUso'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
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
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>

    <script>
        if (window.feather && typeof window.feather.replace === 'function') {
            window.feather.replace();
        }

        function resolveApiBase() {
            const basePath = "<?php echo rtrim((string)$BASE_para_URL, '/'); ?>";
            if (/^https?:\/\//i.test(basePath)) {
                return `${basePath}/api/v1`;
            }
            return `${window.location.origin}${basePath}/api/v1`;
        }

        function showAlert(type, message) {
            const container = document.querySelector('.container-fluid.p-0');
            const oldAlert = document.getElementById('configApiAlert');
            if (oldAlert) oldAlert.remove();

            const alert = document.createElement('div');
            alert.id = 'configApiAlert';
            alert.className = `alert ${type === 'success' ? 'alert-success' : 'alert-danger'}`;
            alert.setAttribute('role', 'alert');
            alert.textContent = message;
            container.prepend(alert);
        }

        function fillForm(data) {
            const fields = [
                'NomeSistema', 'TituloPagina', 'MetaDescription', 'MetaAuthor', 'MetaKeywords',
                'CorPrimaria', 'CorSecundaria', 'LogoSistema', 'LogoImpressao', 'ShortcutIcon',
                'APIzap', 'HeaderEmail', 'TermosUso', 'IdColaboradorDirigente', 'CargoDirigente'
            ];

            fields.forEach((name) => {
                const el = document.getElementById(name);
                if (!el) return;
                el.value = data[name] ?? '';
            });

            if (window.tinymce && tinymce.get('TermosUso')) {
                tinymce.get('TermosUso').setContent(data.TermosUso ? '');
            }
        }

        async function loadConfig() {
            const apiUrl = `${resolveApiBase()}/config`;
            const response = await fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Falha ao carregar Configurações');
            }

            fillForm(result.data || {});
        }

        async function submitConfig(event) {
            event.preventDefault();
            if (
                typeof window.mostrarOverlay === 'function' &&
                document.getElementById('overlayLoading') &&
                document.getElementById('overlaySpinner') &&
                document.getElementById('overlayText') &&
                document.getElementById('overlayBtnOk')
            ) {
                window.mostrarOverlay({
                    mensagem: 'Salvando Configurações...',
                    carregando: true
                });
            }

            const form = document.getElementById('configForm');
            const formData = new FormData(form);
            const payload = Object.fromEntries(formData.entries());

            if (window.tinymce && tinymce.get('TermosUso')) {
                payload.TermosUso = tinymce.get('TermosUso').getContent();
            }

            try {
                const apiUrl = `${resolveApiBase()}/config`;
                const response = await fetch(apiUrl, {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.errors?.[0] || result.message || 'Falha ao salvar');
                }

                fillForm(result.data || {});
                showAlert('success', result.message || 'Configurações atualizadas com sucesso.');
            } catch (error) {
                showAlert('error', error.message || 'Erro ao atualizar configurações.');
                form.submit();
            } finally {
                if (typeof esconderOverlay === 'function') {
                    esconderOverlay();
                }
            }
        }

        document.getElementById('configForm').addEventListener('submit', submitConfig);
        loadConfig().catch((error) => {
            showAlert('error', error.message || 'Erro ao carregar configurações.');
        });
    </script>
</body>

</html>





