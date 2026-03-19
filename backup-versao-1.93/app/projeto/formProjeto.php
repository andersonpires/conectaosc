<?php
require_once $_SESSION['BASE_PATH'] . '/checa-token.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $_SESSION['BASE_PATH'] . '/template/header.php'; ?>
    <script src="https://cdn.tiny.cloud/1/maa7p6ervwrty9h84gphxrsrfking1awabjrp7tak98iuwjh/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
</head>

<body>
    <div class="wrapper">
        <?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>
        <div class="main">
            <?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php'; ?>
            <main class="content">
                <div class="container-fluid p-0">
                    <?php
                    $msg = $_POST['msg'] ?? '';
                    $erro = $_POST['erro'] ?? '';
                    if ($msg) {
                        echo '<div class="alert alert-success" role="alert">' . htmlspecialchars($msg) . '</div>';
                    } elseif ($erro) {
                        echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($erro) . '</div>';
                    }
                    ?>

                    <h1 class="h3 mb-3">Cadastro de Projeto</h1>

                    <div class="card mb-4">
                        <div class="card-body">
                            <form action="projetoController.php" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="IdProjeto" value="<?php echo htmlspecialchars($_POST['IdProjeto'] ?? ''); ?>">
                                <input type="hidden" name="LogoAtual" value="<?php echo htmlspecialchars($_POST['LogoProjeto'] ?? ''); ?>">

                                <div class="mb-3">
                                    <label for="NomeProjeto" class="form-label">Nome do Projeto</label>
                                    <input type="text" class="form-control" id="NomeProjeto" name="NomeProjeto" value="<?php echo htmlspecialchars($_POST['NomeProjeto'] ?? ''); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="LogoProjeto" class="form-label">Logo do Projeto (JPG ate 500KB)</label>
                                    <input type="file" class="form-control" id="LogoProjeto" name="LogoProjeto" accept="image/jpeg">
                                    <?php if (!empty($_POST['LogoProjeto'])): ?>
                                        <div class="mt-2">
                                            <img src="<?php echo $_SESSION['BASE_URL'] . '/assets/img/logos/' . htmlspecialchars($_POST['LogoProjeto']); ?>" alt="Logo atual" style="max-height:80px;">
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="TermosContrato" class="form-label">Termos do Contrato</label>
                                    <textarea class="form-control" id="TermosContrato" name="TermosContrato" rows="20" style="min-height: 400px;"><?php echo htmlspecialchars($_POST['TermosContrato'] ?? ''); ?></textarea>
                                </div>

                                <button type="submit" name="acao" value="salvar" class="btn btn-primary">Salvar</button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-3">Projetos cadastrados</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Logo</th>
                                            <th>Acoes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($listaProjetos)): ?>
                                            <?php foreach ($listaProjetos as $proj): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($proj['NomeProjeto'] ?? ''); ?></td>
                                                    <td>
                                                        <?php if (!empty($proj['LogoProjeto'])): ?>
                                                            <img src="<?php echo $_SESSION['BASE_URL'] . '/assets/img/logos/' . htmlspecialchars($proj['LogoProjeto']); ?>" alt="Logo" style="max-height:40px;">
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a class="btn btn-sm btn-warning" href="projetoController.php?id=<?php echo intval($proj['IdProjeto']); ?>">Editar</a>
                                                        <form action="projetoController.php" method="post" style="display:inline-block;" onsubmit="return confirm('Excluir este projeto?');">
                                                            <input type="hidden" name="IdProjeto" value="<?php echo intval($proj['IdProjeto']); ?>">
                                                            <button type="submit" name="acao" value="excluir" class="btn btn-sm btn-danger">Excluir</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3">Nenhum projeto cadastrado.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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

    <script>
        tinymce.init({
            selector: '#TermosContrato',
            menubar: false,
            height: 600,
            plugins: 'image link lists code',
            toolbar: 'undo redo | styles | bold italic underline | bullist numlist | link image | code',
            language: 'pt_BR',
            branding: false,
            setup: function(editor) {
                editor.on('init', function() {
                    editor.getContainer().style.height = "600px";
                    editor.getContainer().style.minHeight = "600px";
                    editor.getContainer().style.maxHeight = "600px";
                    editor.getContainer().style.overflowY = "auto";
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
                        reject('Resposta invalida: ' + xhr.responseText);
                        return;
                    }

                    resolve(json.location);
                };

                xhr.onerror = () => {
                    reject('Erro de conexao. Codigo: ' + xhr.status);
                };

                const formData = new FormData();
                formData.append('action', 'uploadImagemNota');
                formData.append('file', blobInfo.blob(), blobInfo.filename());

                xhr.send(formData);
            })
        });
    </script>
    <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>
</body>

</html>
