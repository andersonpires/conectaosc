<!-- SERVI?O DE INSCRI??O EVENTO 3O SETOR - INFORMA QUE A INSCRI??O FOI FEITA COM SUCESSO -->
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php
    include 'header.php';
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
        <?php include 'menuinscrito.php'; ?>

        <div class="main">
            <?php
            include 'topoinscrito.php';
            ?>
            <script src="js/app.js"></script>

            <main class="content">
                <div class="success-container">
                    <i class="fas fa-check-circle success-icon"></i>
                    <h1>Parab?ns!</h1>
                    <p>Seu cadastro foi feito com sucesso!</p>
                    <p><strong>Te aguardamos no dia 30/01, ?s 8h30.</strong></p>
                </div>
            </main>

            <footer class="footer">
                <?php include 'footer.php' ?>
            </footer>
        </div>
    </div>

</body>

</html>

