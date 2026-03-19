<?php
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400); // 24 horas
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

require_once $_SESSION['BASE_PATH'] . '/checa-token.php';
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

// Buscar os dados da tabela tbAlerta
$stmt = $pdo->query("SELECT * FROM tbAlerta LIMIT 1");
$alerta = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <?php require_once $_SESSION['BASE_PATH'] . '/template/header.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.1/spectrum.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.1/spectrum.min.css">

    <style>
        .form-container {
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            font-weight: bold;
            margin-top: 10px;
        }

        input[type="number"],
        input[type="text"] {
            width: 120px;
            padding: 6px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            display: inline-block;
        }

        .btn-container {
            margin-top: 20px;
            text-align: center;
        }
    </style>

    <script>
        $(document).ready(function() {
            function syncColorInputs(colorPicker, textInput) {
                $(colorPicker).spectrum({
                    showPalette: true,
                    palette: [
                        ['#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF', '#00FFFF', '#000000', '#FFFFFF'],
                        ['#808080', '#C0C0C0', '#800000', '#808000', '#008000', '#800080', '#008080', '#000080']
                    ],
                    showInput: true,
                    preferredFormat: "hex",
                    allowEmpty: false,
                    chooseText: "Escolher",
                    cancelText: "Cancelar",
                    togglePaletteMoreText: "Mais cores",
                    togglePaletteLessText: "Menos cores",
                    noColorSelectedText: "Nenhuma cor selecionada",
                    clearText: "Limpar seleção",
                    change: function(color) {
                        $(textInput).val(color.toHexString());
                    }
                });

                $(textInput).on('input', function() {
                    $(colorPicker).spectrum("set", $(this).val());
                });
            }

            syncColorInputs('#colorFaltosoPicker', '#colorFaltosoText');
            syncColorInputs('#colorExcluidoPicker', '#colorExcluidoText');

            $('#formAlerta').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'cadAlerta.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        alert('Alterações salvas com sucesso!');
                        location.reload();
                    },
                    error: function() {
                        alert('Erro ao salvar alterações.');
                    }
                });
            });
        });
    </script>
</head>

<body>
    <div class="wrapper">
        <?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>

        <div class="main">
            <?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php'; ?>

            <main class="content">
                <div class="form-container">
                    <h2>Configurações de Alerta</h2>
                    <form id="formAlerta">
                        <label>Nº faltas para Alerta (>=):</label>
                        <input type="number" name="alertaFaltoso" value="<?= $alerta['alertaFaltoso'] ?>" required>

                        <label>Cor do alerta:</label>
                        <input type="text" id="colorFaltosoText" name="corFaltoso" value="<?= $alerta['corFaltoso'] ?>" required>
                        <input type="text" id="colorFaltosoPicker" class="color-picker" name="corFaltoso" value="<?= $alerta['corFaltoso'] ?>" required>

                        <label>Nº faltas para Exclusão (>=):</label>
                        <input type="number" name="alertaExcluido" value="<?= $alerta['alertaExcluido'] ?>" required>

                        <label>Cor do alerta:</label>
                        <input type="text" id="colorExcluidoText" name="corExcluido" value="<?= $alerta['corExcluido'] ?>" required>
                        <input type="text" id="colorExcluidoPicker" class="color-picker" name="corExcluido" value="<?= $alerta['corExcluido'] ?>" required>

                        <div class="btn-container">
                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </main>


            <footer class="footer">
                <?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php'; ?>
            </footer>
        </div>
    </div>
    <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>
</body>

</html>