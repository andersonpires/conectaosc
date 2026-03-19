<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_PATH = $runtime['base_path'];
$BASE_URL = $runtime['base_url'];
$appJsVersion = @filemtime($BASE_PATH . '/app/assets/js/app.js') ?: time();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php
    require_once $BASE_PATH . '/app/template/header.php';
    $gp = "graficoProfessor.php?v=" . time();
    ?>
    <style>
        p {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .success-container {
            text-align: center;
            background: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .success-container h1 {
            font-size: 3rem;
            color: #4caf50;
        }

        .success-container p {
            font-size: 1.5rem;
            color: #555;
        }

        .success-icon {
            font-size: 4rem;
            color: #4caf50;
            margin-bottom: 20px;
        }
    </style>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

</head>

<body>
    <div class="wrapper">
        <?php include __DIR__ . '/menuprofessor.php'; ?>

        <div class="main">
            <?php
            include __DIR__ . '/topoinscrito.php';
            ?>
            <script src="<?php echo rtrim((string)$BASE_URL, '/'); ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>

            <main class="content">
                <div class="success-container">
                    <i class="fas fa-check-circle success-icon"></i>
                    <h1>Parab?f?????T?f?????,????f???,?s?f??s?,?ns!</h1>
                    <p>Suas respostas foram recebidas com sucesso!</p>
                    <p><strong>Obrigado.</strong></p>
                </div>
            </main>

            <footer class="footer">
                <?php require_once $BASE_PATH . '/app/template/footer.php'; ?>
            </footer>
        </div>
    </div>

</body>

</html>



