<?php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Redefinição de senha">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="shortcut icon" href="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/assets/img/icons/<?php echo htmlspecialchars((string) $shortcutIcon, ENT_QUOTES, 'UTF-8'); ?>" />

    <title>Esqueci a senha | ITEVA - Gestão de OSCs</title>

    <link href="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/assets/css/app.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/assets/js/app.js?v=<?php echo (int) $appJsVersion; ?>"></script>
    <style>
        @keyframes fadeInTop {
            0% { opacity: 0; transform: translateY(-30px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInBottom {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .texto { opacity: 0; animation: fadeInTop .5s ease-in-out forwards; }
        .caixa { opacity: 0; animation: fadeInBottom .5s ease-in-out forwards; }

        .reset-alert-success {
            width: 100%;
            color: #0f5132;
            background-color: #d1e7dd;
            border: 1px solid #badbcc;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-top: 1rem;
            text-align: left !important;
        }

        .reset-alert-success .bi {
            fill: currentColor;
        }
    </style>
</head>
<body>
    <svg xmlns="http://www.w3.org/2000/svg" class="d-none">
        <symbol id="check-circle-fill" viewBox="0 0 16 16">
            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.97 11.03a.75.75 0 0 0 1.08.022l3.992-4.99a.75.75 0 1 0-1.17-.94L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06l2.646 2.647z"/>
        </symbol>
    </svg>
    <main class="d-flex w-100">
        <div class="container d-flex flex-column">
            <div class="row vh-100">
                <div class="col-sm-9 col-md-7 col-lg-5 mx-auto d-table h-100">
                    <div class="d-table-cell align-middle">
                        <div class="text-center mt-4">
                            <h1 class="h2 texto">Esqueceu a senha?</h1>
                            <p class="lead texto">Informe seu e-mail e CPF para redefinir o acesso</p>
                        </div>

                        <?php if (!empty($mensagemSucesso)) { ?>
                            <div class="alert alert-success reset-alert-success d-flex align-items-center caixa" role="alert">
                                <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Sucesso: " width="20" height="20">
                                    <use xlink:href="#check-circle-fill"></use>
                                </svg>
                                <div>
                                    <?php echo htmlspecialchars((string) $mensagemSucesso, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                        <?php } ?>

                        <div class="card caixa">
                            <div class="card-body caixa">
                                <div class="m-sm-4">
                                    <div class="text-center mb-5">
                                        <img src="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/assets/img/sistema/logo-bgbranco.png" alt="ITEVA LOGO" class="img-fluid" width="200" />
                                    </div>
                                    <form method="post" action="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/esqueci-senha/">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">E-mail</label>
                                            <input class="form-control form-control-lg" type="email" id="email" name="email" placeholder="Digite seu e-mail" autocomplete="on" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="cpf" class="form-label">CPF</label>
                                            <input class="form-control form-control-lg" type="text" id="cpf" name="cpf" placeholder="000.000.000-00" inputmode="numeric" maxlength="14" required>
                                        </div>
                                        <div class="text-center mt-3">
                                            <button type="submit" class="btn btn-lg btn-primary">Redefinir senha</button>
                                        </div>
                                    </form>
                                    <div class="text-center mt-3">
                                        <small><a href="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/login/">Voltar para o login</a></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer class="footer">
        <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
    </footer>

    <script>
        (function () {
            const cpfInput = document.getElementById('cpf');
            if (!cpfInput) {
                return;
            }

            cpfInput.addEventListener('input', function () {
                let digits = this.value.replace(/\D/g, '').slice(0, 11);
                if (digits.length > 9) {
                    digits = digits.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
                } else if (digits.length > 6) {
                    digits = digits.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
                } else if (digits.length > 3) {
                    digits = digits.replace(/(\d{3})(\d{1,3})/, '$1.$2');
                }
                this.value = digits;
            });
        })();
    </script>
</body>
</html>
