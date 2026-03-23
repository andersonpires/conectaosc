<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_PATH = $runtime['base_path'];
$BASE_URL = $runtime['base_url'];
$appJsVersion = @filemtime($BASE_PATH . '/app/assets/js/app.js') ?: time();

// Verifica se as vari?f?????T?f?????,????f???,?s?f??s?,?veis de sess?f?????T?f?????,????f???,?s?f??s?,?o BASE_PATH e BASE_URL est?f?????T?f?????,????f???,?s?f??s?,?o definidas
if (!isset($BASE_PATH) || !isset($BASE_URL)) {
    // Salva a URL atual para redirecionar o usu?f?????T?f?????,????f???,?s?f??s?,?rio ap?f?????T?f?????,????f???,?s?f??s?,?s o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endere?f?????T?f?????,????f???,?s?f??s?,?o atual
    header("Location: " . rtrim((string)$BASE_URL, '/') . "/login/?erro=Ocorreu%20um%20erro!%20Talvez%20voce%20tenha%20perdido%20sua%20ultima%20acao.%20Verifique.");
    exit(); // Garante que o c?f?????T?f?????,????f???,?s?f??s?,?digo abaixo n?f?????T?f?????,????f???,?s?f??s?,?o ser?f?????T?f?????,????f???,?s?f??s?,? executado
}
require_once $BASE_PATH . '/api/legacy/checa-token.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_PATH . '/app/template/header.php'; ?>
    <style>
        .tooltip .tooltip-inner {
            background-color: #007bff;
            color: #ffffff;
        }

        .img-cover {
            object-fit: cover;
            object-position: center;
        }

        .checkbox-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            align-items: center;
            margin: 20px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-item input {
            transform: scale(1.2);
        }

        table.dataTable {
            border-collapse: collapse;
            width: 100%;
        }

        table.dataTable th,
        table.dataTable td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        table.dataTable tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        table.dataTable tbody tr:nth-child(even) {
            background-color: #d4f4d4;
        }

        /* Alterar a cor do hover para um verde mais escuro */
        #tabelaInscritos tbody tr:hover {
            background-color: #006400 !important;
            /* Verde escuro */
            color: white;
            /* Cor do texto para maior contraste */
        }

        /* Alternativa: Se quiser azul no hover */
        #tabelaInscritos tbody tr:hover {
            background-color: #0056b3 !important;
            /* Azul escuro */
            color: white;
            /* Cor do texto para melhor leitura */
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

</head>

<body>
    <div class="wrapper">
        <?php require_once $BASE_PATH . '/app/template/menu.php'; ?>
        <div class="main">
            <?php require_once $BASE_PATH . '/app/template/topo.php'; ?>
            <script src="<?php echo $BASE_URL; ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>
            <main class="content">
                <div class="container-fluid p-0">
                    <?php
                    require_once $BASE_PATH . '/api/repositories/EventoRepository.php';
                    require_once $BASE_PATH . '/api/services/EventoService.php';

                    $inscritos = [];
                    try {
                        $eventoService = new \BackEnd\Services\EventoService(new \BackEnd\Repositories\EventoRepository());
                        $inscritos = $eventoService->listInscritos();
                    } catch (Throwable $e) {
                        die("Erro ao buscar dados: " . $e->getMessage());
                    }
                    ?>

                    <div class="container">
                        <h1>Listagem de Inscritos</h1>
                        <button class="btn btn-primary mb-3" onclick="exportToPDFr()">Exportar PDF retrato</button>
                        <button class="btn btn-primary mb-3" onclick="exportToPDFp()">Exportar PDF paisagem</button>
                        <button class="btn btn-success mb-3" onclick="exportToExcel()">Exportar para Excel</button>
                        <div class="card shadow-lg">
                            <div class="card-body">
                                <table id="tabelaInscritos" class="display table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>N.</th>
                                            <th>Nome</th>
                                            <th>Email</th>
                                            <th>OSC</th>
                                            <th>Telefone</th>
                                            <th>Tel2</th>
                                            <th>Dt Nasc.</th>
                                            <th>Motiva?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o</th>
                                            <th>Excluir</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $count = 1;
                                        foreach ($inscritos as $row) {
                                            $dataNascimento = date("d/m/Y", strtotime($row['DataNascimento']));
                                            $Osc = $row['OrganizacaoSocial'] ?: "N?f?????T?f?????,????f???,?s?f??s?,?o respondeu";
                                        ?>
                                            <tr>
                                                <td><?php echo $count++; ?></td>
                                                <td><?php echo htmlspecialchars($row['NomeCompleto']); ?></td>
                                                <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                                <td><?php echo htmlspecialchars($Osc); ?></td>
                                                <td><?php echo htmlspecialchars($row['Telefone']); ?></td>
                                                <td><?php echo htmlspecialchars($row['Telefone2']); ?></td>
                                                <td><?php echo $dataNascimento; ?></td>
                                                <td><?php echo htmlspecialchars($row['MotivacaoEvento']); ?></td>
                                                <td>
                                                    <?php
                                                    if ($_SESSION['Tipo'] == "Educador") {
                                                        // O usu?f?????T?f?????,????f???,?s?f??s?,?rio que ?f?????T?f?????,????f???,?s?f??s?,? Educador, bot?f?????T?f?????,????f???,?s?f??s?,?o desabilitado
                                                        echo '<button type="button" class="btn btn-primary btn-disabled" disabled>Desabilitado</button>';
                                                    } else {
                                                        // O usu?f?????T?f?????,????f???,?s?f??s?,?rio n?f?????T?f?????,????f???,?s?f??s?,?o ?f?????T?f?????,????f???,?s?f??s?,? Educador, exibir bot?f?????T?f?????,????f???,?s?f??s?,?es
                                                        echo '<div class="d-flex justify-content-center gap-2">';
                                                        echo '<form method="GET" action="' . rtrim((string)$BASE_URL, '/') . '/eventos/inscritos/editar/" style="display:inline;" onsubmit="return confirmExclusion();">
                                <input type="hidden" name="IdInscrito" value="' . htmlspecialchars($row['IdInscrito']) . '">
                                <button type="submit" class="btn btn-warning btn-sm">
                                    <i data-feather="edit-3"></i>
                                </button>
                            </form>';

                                                        echo '<form method="POST" action="' . rtrim((string)$BASE_URL, '/') . '/eventos/inscritos/excluir/" style="display:inline;" onsubmit="return confirmExclusion();">
                                <input type="hidden" name="IdInscrito" value="' . htmlspecialchars($row['IdInscrito']) . '">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i data-feather="trash-2"></i>
                                </button>
                            </form>';
                                                        echo '</div>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="footer">
                <?php require_once $BASE_PATH . '/app/template/footer.php'; ?>
            </footer>
        </div>
    </div>



    <script>
        function toTitleCase(str) {
            return str.replace(/\w\S*/g, function(word) {
                // Se a palavra tiver apenas uma letra, deixa em min?f?????T?f?????,????f???,?s?f??s?,?sculo, caso contr?f?????T?f?????,????f???,?s?f??s?,?rio coloca a primeira letra em mai?f?????T?f?????,????f???,?s?f??s?,?scula
                if (word.length > 1) {
                    return word.charAt(0).toUpperCase() + word.substr(1).toLowerCase();
                } else {
                    return word.toLowerCase();
                }
            });
        }

        function exportToPDFp() {
            const {
                jsPDF
            } = window.jspdf;
            let doc = new jsPDF('l', 'mm', 'a4'); // 'l' para paisagem (horizontal), 'mm' para mil?f?????T?f?????,????f???,?s?f??s?,?metros, 'a4' para formato A4
            let img = new Image();
            img.src = '<?php echo $BASE_URL; ?>/assets/img/banners/inscricao3osetor.jpg';
            img.onload = function() {
                let imgWidth = 180; // Largura desejada da imagem
                let pageWidth = doc.internal.pageSize.getWidth(); // Largura total da p?f?????T?f?????,????f???,?s?f??s?,?gina
                let imgX = (pageWidth - imgWidth) / 2; // Calcula a posi?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o X para centralizar
                let imgHeight = (img.height / img.width) * imgWidth; // Mant?f?????T?f?????,????f???,?s?f??s?,?m a propor?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o da altura
                doc.addImage(img, 'JPEG', imgX, 10, imgWidth, imgHeight);

                doc.setFontSize(14);
                doc.setFont("helvetica", "bold");
                doc.text("Lista de inscritos para o evento de Fortalecimento do 3o Setor", 145, imgHeight + 20, {
                    align: "center"
                });

                let rows = [];
                $("#tabelaInscritos tbody tr").each(function() {
                    let row = [];
                    $(this).find("td").each(function(index) {
                        if (index !== 5 && index !== 6) { // Remove colunas Dt Nasc. e Motiva?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o
                            row.push($(this).text());
                        }
                        if (index == 6) { // Remove colunas Dt Nasc. e Motiva?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o
                            row.push('');
                        }
                    });
                    row.push(''); // Agora, apenas adiciona um valor vazio (sem dados da coluna Motiva?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o)
                    rows.push(row);
                });

                // Adicionando a nova coluna de ASSINATURA na tabela
                doc.autoTable({
                    head: [
                        ["N.", "Nome", "Email", "OSC", "Telefone", "ASSINATURA"] // Cabe?f?????T?f?????,????f???,?s?f??s?,?alho com a nova coluna
                    ],
                    body: rows,
                    startY: imgHeight + 30,
                    columnStyles: {
                        0: {
                            cellWidth: 10,
                            halign: "center"
                        }, // N?f?????T?f?????,????f???,?s?f??s?,?mero centralizado e menor
                        1: {
                            cellWidth: 65
                        }, // Nome maior
                        2: {
                            cellWidth: 60
                        }, // Email menor
                        3: {
                            cellWidth: 40
                        }, // OSC menor
                        4: {
                            cellWidth: 35
                        }, // Telefone maior
                        5: {
                            cellWidth: 60, // Coluna ASSINATURA com largura suficiente
                            halign: "center"
                        } // Coluna de assinatura
                    },
                    didDrawPage: function(data) {
                        let pageNum = doc.internal.getNumberOfPages();
                        for (let i = 1; i <= pageNum; i++) {
                            doc.setPage(i);
                            doc.setFontSize(10);
                            doc.text(`P?f?????T?f?????,????f???,?s?f??s?,?g. ${i} de ${pageNum}`, 190, 285, {
                                align: "right"
                            });
                        }
                    }
                });

                doc.save("InscritosEvento.pdf");
            };
        }

        function exportToPDFr() {
            const {
                jsPDF
            } = window.jspdf;
            let doc = new jsPDF();
            let img = new Image();
            img.src = '<?php echo $BASE_URL; ?>/assets/img/banners/inscricao3osetor.jpg';
            img.onload = function() {
                let imgWidth = 180; // Largura desejada da imagem
                let pageWidth = doc.internal.pageSize.getWidth(); // Largura total da p?f?????T?f?????,????f???,?s?f??s?,?gina
                let imgX = (pageWidth - imgWidth) / 2; // Calcula a posi?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o X para centralizar
                let imgHeight = (img.height / img.width) * imgWidth; // Mant?f?????T?f?????,????f???,?s?f??s?,?m a propor?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o da altura
                doc.addImage(img, 'JPEG', imgX, 10, imgWidth, imgHeight);


                doc.setFontSize(14);
                doc.setFont("helvetica", "bold");
                doc.text("Lista de inscritos para o evento de Fortalecimento do 3o Setor", 105, imgHeight + 20, {
                    align: "center"
                });

                let rows = [];
                $("#tabelaInscritos tbody tr").each(function() {
                    let row = [];
                    $(this).find("td").each(function(index) {
                        if (index !== 5 && index !== 6) { // Remove colunas Dt Nasc. e Motiva?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o
                            row.push($(this).text());
                        }
                    });
                    rows.push(row);
                });

                doc.autoTable({
                    head: [
                        ["N.", "Nome", "Email", "OSC", "Telefone"]
                    ],
                    body: rows,
                    startY: imgHeight + 30,
                    columnStyles: {
                        0: {
                            cellWidth: 10,
                            halign: "center"
                        }, // N?f?????T?f?????,????f???,?s?f??s?,?mero centralizado e menor
                        1: {
                            cellWidth: 55
                        }, // Nome maior
                        2: {
                            cellWidth: 50
                        }, // Email menor
                        3: {
                            cellWidth: 40
                        }, // OSC menor
                        4: {
                            cellWidth: 30
                        } // Telefone maior
                    },
                    didDrawPage: function(data) {
                        let pageNum = doc.internal.getNumberOfPages();
                        for (let i = 1; i <= pageNum; i++) {
                            doc.setPage(i);
                            doc.setFontSize(10);
                            doc.text(`P?f?????T?f?????,????f???,?s?f??s?,?g. ${i} de ${pageNum}`, 190, 285, {
                                align: "right"
                            });
                        }
                    }
                });

                doc.save("InscritosEvento.pdf");
            };
        }

        function exportToExcel() {
            let wb = XLSX.utils.table_to_book(document.getElementById("tabelaInscritos"), {
                sheet: "Inscritos"
            });
            XLSX.writeFile(wb, "InscritosEvento.xlsx");
        }

        $(document).ready(function() {
            $('#tabelaInscritos').DataTable({
                "pageLength": 50,
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json"
                },
                columnDefs: [{
                        targets: [0, 4, 5],
                        className: "text-center",
                    }, // Centraliza a coluna N?f?????T?f?????,????f???,?s?f??s?,?mero
                    {
                        targets: 0,
                        width: "5%"
                    }, // Dt Nasc. com largura ajustada ao conte?f?????T?f?????,????f???,?s?f??s?,?do
                    {
                        targets: 1,
                        width: "17%"
                    }, // Dt Nasc. com largura ajustada ao conte?f?????T?f?????,????f???,?s?f??s?,?do
                    {
                        targets: 4,
                        width: "12%"
                    }, // Dt Nasc. com largura ajustada ao conte?f?????T?f?????,????f???,?s?f??s?,?do
                    {
                        targets: 5,
                        width: "12%"
                    }, // Telefone com largura ajustada ao conte?f?????T?f?????,????f???,?s?f??s?,?do

                    {
                        targets: 7,
                        width: "2%"
                    }, // Telefone com largura ajustada ao conte?f?????T?f?????,????f???,?s?f??s?,?do

                    {
                        targets: [2, 3, 6],
                        width: "16%"
                    } // Nome, Email, OSC e Motiva?f?????T?f?????,????f???,?s?f??s?,??f?????T?f?????,????f???,?s?f??s?,?o distribu?f?????T?f?????,????f???,?s?f??s?,?dos igualmente

                ]
            });
        });
    </script>

</body>

</html>







