<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

// CONEXÃO COM O BANCO — necessária para validação
require_once $_SERVER['DOCUMENT_ROOT'] . "/conectaosc/conectabd/conexao.php";

// ==========================================================
//  SE NÃO RECEBER CODE -> MOSTRA TELA PÚBLICA DE CONSULTA
// ==========================================================
if (!isset($_GET['code']) || empty($_GET['code'])) {
?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8">
        <title>Validação de Documento - ITEVA</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body {
                background: #eef2f7;
                font-family: Arial, sans-serif;
                padding: 30px;
                text-align: center;
            }

            .card {
                background: white;
                padding: 30px 25px;
                max-width: 420px;
                margin: 0 auto;
                border-radius: 16px;
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
                animation: fadeIn .5s ease;
            }

            .icon-cert {
                font-size: 60px;
                color: #007bff;
                margin-bottom: 10px;
            }

            h2 {
                margin: 0;
                font-weight: 700;
                color: #333;
            }

            p {
                color: #444;
                font-size: 15px;
                margin-top: 8px;
            }

            input {
                width: 100%;
                padding: 12px;
                margin-top: 15px;
                border-radius: 8px;
                border: 1px solid #ccc;
                font-size: 16px;
                box-sizing: border-box;
            }

            button {
                width: 100%;
                padding: 12px;
                margin-top: 15px;
                border-radius: 8px;
                border: none;
                background: #007bff;
                color: white;
                font-size: 17px;
                cursor: pointer;
                transition: .2s;
            }

            button:hover {
                background: #0069d9;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>
    </head>

    <body>

        <div class="card">
            <div class="icon-cert">🔒</div>
            <h2>Validação de Documento</h2>
            <p>Digite o código de validação para verificar a autenticidade.</p>

            <form method="GET" action="">
                <input type="text" name="code" placeholder="Insira o código de validação" required>
                <button type="submit">Validar</button>
            </form>
        </div>

    </body>

    </html>

<?php
    exit;
}

// ==========================================================
//  DAQUI PARA BAIXO: VALIDAR DOCUMENTO
// ==========================================================

$code = $_GET['code'];

$sql = $pdo->prepare("SELECT * FROM tbpdf_assinado WHERE AssinaturaBase64 = ?");
$sql->execute([$code]);
$dados = $sql->fetch(PDO::FETCH_ASSOC);

// Se não achou:
if (!$dados) {
?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8">
        <title>Documento Não Encontrado</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <style>
            body {
                background: #eef2f7;
                font-family: Arial, sans-serif;
                padding: 30px;
                text-align: center;
            }

            .card {
                background: white;
                padding: 30px 25px;
                max-width: 420px;
                margin: 0 auto;
                border-radius: 16px;
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
                animation: fadeIn .5s ease;
            }

            .icon-error {
                font-size: 60px;
                color: #dc3545;
                margin-bottom: 10px;
            }

            h2 {
                margin-top: 0;
                font-weight: 700;
                color: #333;
            }

            p {
                color: #444;
                font-size: 15px;
                margin-top: 8px;
            }

            .btn {
                display: block;
                width: 100%;
                padding: 12px;
                border-radius: 8px;
                border: none;
                background: #007bff;
                color: white;
                font-size: 17px;
                cursor: pointer;
                margin-top: 15px;
                text-decoration: none;
                box-sizing: border-box;
                margin-left: 0;
                margin-right: 0;
            }

            .btn:hover {
                background: #0069d9;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>

    </head>

    <body>

        <div class="card">
            <div class="icon-error">❌</div>

            <h2>Documento Não Encontrado</h2>
            <p>Nenhum documento foi localizado com o código informado:</p>

            <p><b><?= htmlspecialchars($code); ?></b></p>

            <a href="verPDF.php" class="btn">Tentar Novamente</a>
        </div>

    </body>

    </html>
<?php
    exit;
}


// ==========================================================
//  SE ACHOU O DOCUMENTO -> MOSTRA TELA DE VALIDAÇÃO
// ==========================================================

$nomeArquivo = $dados['NomeArquivo'];
$NomeDocumento = $dados['NomeDocumento'];
$nomeOriginal = $dados['NomeOriginal'];
$idColab = $dados['IdColaborador'];

$pathPDF = $_SERVER['DOCUMENT_ROOT'] . "/conectaosc/app/assinatura/assinados/" . $nomeArquivo;
$urlPDF  = "/conectaosc/app/assinatura/assinados/" . $nomeArquivo;

// Buscar usuário
$sqlU = $pdo->prepare("SELECT Nome, Sobrenome FROM tbUser WHERE IdColaborador = ?");
$sqlU->execute([$idColab]);
$user = $sqlU->fetch(PDO::FETCH_ASSOC);

$nomeColab = $user ? ($user['Nome'] . " " . $user['Sobrenome']) : "Desconhecido";

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Documento Validado - ITEVA</title>

    <style>
        body {
            background: #eef2f7;
            font-family: Arial, sans-serif;
            padding: 30px;
            text-align: center;
        }

        .card {
            background: white;
            padding: 30px 25px;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            animation: fadeIn .5s ease;
            text-align: left;
        }

        .icon-ok {
            font-size: 60px;
            color: #28a745;
            text-align: center;
            margin-bottom: 10px;
        }

        h2 {
            margin-top: 0;
            text-align: center;
            font-weight: 700;
        }

        p {
            color: #444;
            font-size: 15px;
            line-height: 1.5;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: none;
            background: #007bff;
            color: white;
            font-size: 17px;
            cursor: pointer;
            margin-top: 15px;
            text-decoration: none;
            text-align: center;
            box-sizing: border-box;
            margin-left: 0;
            margin-right: 0;
        }

        .btn:hover {
            background: #0069d9;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

</head>

<body>

    <div class="card">

        <div class="icon-ok">📄✒️</div>

        <h2>Documento Validado</h2>

        <p><b>Nome do documento:</b><br><?= htmlspecialchars($NomeDocumento); ?></p>
        <p><b>Assinado por:</b><br><?= htmlspecialchars($nomeColab); ?></p>
        <p><b>Data/Hora da assinatura:</b><br><?= date("d/m/Y H:i:s", $dados['TimestampAssinatura']); ?></p>

        <p><b>Código de validação:</b><br>
            <span style="font-family:monospace; font-size:16px;">
                <?= htmlspecialchars($code); ?>
            </span>
        </p>

        <a class="btn" href="<?= $urlPDF ?>" target="_blank">📄 Abrir Documento Assinado</a>
        <a class="btn" href="verPDF.php">Validar Outro Documento</a>

    </div>

</body>

</html>