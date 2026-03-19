<?php
require 'conexao_grava.php';

header('Content-Type: text/html; charset=UTF-8');
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerar PDF - Inscritos</title>

    <!-- Importação das bibliotecas necessárias -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
</head>

<body onload="gerarPDF()">
    <script>
        async function gerarPDF() {
            const {
                jsPDF
            } = window.jspdf;
            let doc = new jsPDF();

            // Centraliza a imagem do banner
            let img = new Image();
            img.src = 'img/banners/inscricao3osetor.jpg';
            img.onload = async function() {
                let imgWidth = 180;
                let pageWidth = doc.internal.pageSize.getWidth();
                let imgX = (pageWidth - imgWidth) / 2; // Centraliza a imagem
                let imgHeight = (img.height / img.width) * imgWidth; // Mantém a proporção
                doc.addImage(img, 'JPEG', imgX, 10, imgWidth, imgHeight);

                doc.setFontSize(14);
                doc.setFont("helvetica", "bold");
                doc.text("Lista de Inscritos para o Evento de Fortalecimento do 3º Setor", pageWidth / 2, imgHeight + 20, {
                    align: "center"
                });

                // Requisição para obter os dados do banco de dados via PHP
                let response = await fetch('dados_inscritos.php?v=' + Date.now());
                let inscritos = await response.json();

                let rows = inscritos.map((row, index) => [
                    index + 1, row.NomeCompleto, row.Email, row.OrganizacaoSocial || "Não respondeu", row.Telefone
                ]);

                // Geração da tabela
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
                        let agora = new Date(Date.now());
                        let horaFormatada = agora.toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit'
                        });
                        for (let i = 1; i <= pageNum; i++) {
                            doc.setPage(i);
                            doc.setFontSize(10);
                            doc.text(`Relatório emitido às ${horaFormatada}                       Pág. ${i} de ${pageNum}`, 190, 285, {
                                align: "right"
                            });
                        }
                    }
                });

                // Abre o PDF diretamente no navegador
                window.open(doc.output('bloburl'), '_blank');
            };
        }
    </script>
</body>

</html>