<?php
session_set_cookie_params(['httponly' => true]);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

// Verifica se as variáveis de sessão BASE_PATH e BASE_URL estão definidas
if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    // Salva a URL atual para redirecionar o usuário após o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endereço atual
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?erro=Ocorreu%20um%20erro!%20Talvez%20você%20tenha%20perdido%20sua%20última%20ação.%20Verifique."); // Redireciona para o login com o endereço de volta via GET
    exit(); // Garante que o código abaixo não será executado
}
require_once $_SESSION['BASE_PATH'] . '/checa-token.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $_SESSION['BASE_PATH'] . '/template/header.php'; ?>
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
        <?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>
        <div class="main">
            <?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php'; ?>
            <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>
            <main class="content">
                <div class="container-fluid p-0">
                    <?php
                    require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

                    try {
                        $query = "SELECT * FROM InscritosEvento ORDER BY NomeCompleto ASC";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
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
                                            <th>Motivação</th>
                                            <th>Excluir</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query = "SELECT * FROM InscritosEvento ORDER BY NomeCompleto ASC";
                                        $stmt = $pdo->prepare($query);
                                        $stmt->execute();
                                        $inscritos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        $count = 1;
                                        foreach ($inscritos as $row) {
                                            $dataNascimento = date("d/m/Y", strtotime($row['DataNascimento']));
                                            $Osc = $row['OrganizacaoSocial'] ?: "Não respondeu";
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
                                                        // O usuário que é Educador, botão desabilitado
                                                        echo '<button type="button" class="btn btn-primary btn-disabled" disabled>Desabilitado</button>';
                                                    } else {
                                                        // O usuário não é Educador, exibir botões
                                                        echo '<div class="d-flex justify-content-center gap-2">';
                                                        echo '<form method="GET" action="alteraInscrito.php" style="display:inline;" onsubmit="return confirmExclusion();">
                                <input type="hidden" name="IdInscrito" value="' . htmlspecialchars($row['IdInscrito']) . '">
                                <button type="submit" class="btn btn-warning btn-sm">
                                    <i data-feather="edit-3"></i>
                                </button>
                            </form>';

                                                        echo '<form method="POST" action="excluirInscrito.php" style="display:inline;" onsubmit="return confirmExclusion();">
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
                <?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php'; ?>
            </footer>
        </div>
    </div>



    <script>
        function toTitleCase(str) {
            return str.replace(/\w\S*/g, function(word) {
                // Se a palavra tiver apenas uma letra, deixa em minúsculo, caso contrário coloca a primeira letra em maiúscula
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
            let doc = new jsPDF('l', 'mm', 'a4'); // 'l' para paisagem (horizontal), 'mm' para milímetros, 'a4' para formato A4
            let img = new Image();
            img.src = '<?php echo $_SESSION['BASE_URL']; ?>/assets/img/banners/inscricao3osetor.jpg';
            img.onload = function() {
                let imgWidth = 180; // Largura desejada da imagem
                let pageWidth = doc.internal.pageSize.getWidth(); // Largura total da página
                let imgX = (pageWidth - imgWidth) / 2; // Calcula a posição X para centralizar
                let imgHeight = (img.height / img.width) * imgWidth; // Mantém a proporção da altura
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
                        if (index !== 5 && index !== 6) { // Remove colunas Dt Nasc. e Motivação
                            row.push($(this).text());
                        }
                        if (index == 6) { // Remove colunas Dt Nasc. e Motivação
                            row.push('');
                        }
                    });
                    row.push(''); // Agora, apenas adiciona um valor vazio (sem dados da coluna Motivação)
                    rows.push(row);
                });

                // Adicionando a nova coluna de ASSINATURA na tabela
                doc.autoTable({
                    head: [
                        ["N.", "Nome", "Email", "OSC", "Telefone", "ASSINATURA"] // Cabeçalho com a nova coluna
                    ],
                    body: rows,
                    startY: imgHeight + 30,
                    columnStyles: {
                        0: {
                            cellWidth: 10,
                            halign: "center"
                        }, // Número centralizado e menor
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
                            doc.text(`Pág. ${i} de ${pageNum}`, 190, 285, {
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
            img.src = '<?php echo $_SESSION['BASE_URL']; ?>/assets/img/banners/inscricao3osetor.jpg';
            img.onload = function() {
                let imgWidth = 180; // Largura desejada da imagem
                let pageWidth = doc.internal.pageSize.getWidth(); // Largura total da página
                let imgX = (pageWidth - imgWidth) / 2; // Calcula a posição X para centralizar
                let imgHeight = (img.height / img.width) * imgWidth; // Mantém a proporção da altura
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
                        if (index !== 5 && index !== 6) { // Remove colunas Dt Nasc. e Motivação
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
                        }, // Número centralizado e menor
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
                            doc.text(`Pág. ${i} de ${pageNum}`, 190, 285, {
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
                    }, // Centraliza a coluna Número
                    {
                        targets: 0,
                        width: "5%"
                    }, // Dt Nasc. com largura ajustada ao conteúdo
                    {
                        targets: 1,
                        width: "17%"
                    }, // Dt Nasc. com largura ajustada ao conteúdo
                    {
                        targets: 4,
                        width: "12%"
                    }, // Dt Nasc. com largura ajustada ao conteúdo
                    {
                        targets: 5,
                        width: "12%"
                    }, // Telefone com largura ajustada ao conteúdo

                    {
                        targets: 7,
                        width: "2%"
                    }, // Telefone com largura ajustada ao conteúdo

                    {
                        targets: [2, 3, 6],
                        width: "16%"
                    } // Nome, Email, OSC e Motivação distribuídos igualmente

                ]
            });
        });
    </script>

</body>

</html>