<?php include 'checa-token.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php include 'header.php'; ?>
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
        <?php include 'menu.php'; ?>
        <div class="main">
            <?php include 'topo.php'; ?>
            <script src="js/app.js"></script>
            <main class="content">
                <div class="container-fluid p-0">
                    <?php
                    include 'conexao_grava.php';

                    $query = "SELECT * FROM InscritosEvento ORDER BY NomeCompleto ASC";
                    $stmt = $conexao_grava->prepare($query);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    ?>

                    <div class="container">
                        <h1>Listagem de Inscritos</h1>
                        <button class="btn btn-primary mb-3" onclick="exportToPDF()">Exportar para PDF</button>
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
                                            <th>Dt Nasc.</th>
                                            <th>Motivação</th>
                                            <th>Excluir</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $count = 1;
                                        while ($row = $result->fetch_assoc()) {
                                            $dataNascimento = date("d/m/Y", strtotime($row['DataNascimento']));
                                            $Osc = $row['OrganizacaoSocial'] ?: "Não respondeu";
                                        ?>
                                            <tr>
                                                <td><?php echo $count++; ?></td>
                                                <td><?php echo $row['NomeCompleto']; ?></td>
                                                <td><?php echo $row['Email']; ?></td>
                                                <td><?php echo $Osc; ?></td>
                                                <td><?php echo $row['Telefone']; ?></td>
                                                <td><?php echo $dataNascimento; ?></td>
                                                <td><?php echo $row['MotivacaoEvento']; ?></td>
                                                <td>
                                                    <?php
                                                    if ($_SESSION['Tipo'] == "Educador") {
                                                        // Desativado: antes era para O usuário que é Educador, desativar botões
                                                        echo '<button type="button" class="btn btn-primary btn-disabled" disabled>Desabilitado</button>';
                                                    } else {
                                                        // O usuário não é Educador, exibir botões
                                                        echo '<div class="d-flex justify-content-center gap-2">';

                                                        echo '<form method="POST" action="excluirInscrito.php" style="display:inline;" onsubmit="return confirmExclusion();">
                                                                    <input type="hidden" name="IdInscrito" value="' . $row['IdInscrito'] . '">
                                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                                        <i data-feather="trash-2"></i>
                                                                    </button>
                                                                </form>';
                                                        echo '</div>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php }
                                        $stmt->close();
                                        $conexao_grava->close(); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="footer">
                <?php include 'footer.php' ?>
            </footer>
        </div>
    </div>

    <script>
        function exportToPDF() {
            const {
                jsPDF
            } = window.jspdf;
            let doc = new jsPDF();
            let img = new Image();
            img.src = 'img/banners/inscricao3osetor.jpg';
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