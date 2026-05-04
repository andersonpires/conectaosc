<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

if (!isset($BASE_para_PATH)) {
    echo '<script>alert("Sessão inválida."); window.close();</script>';
    exit;
}

require_once $BASE_para_PATH . '/api/repositories/RelatorioRepository.php';
require_once $BASE_para_PATH . '/api/services/RelatorioService.php';

use BackEnd\Repositories\RelatorioRepository;
use BackEnd\Services\RelatorioService;

$curso = $_GET['curso'] ?? '';
$turma = $_GET['turma'] ?? '';
$mesAno = $_GET['mesAno'] ?? '';
$habilitado = $_GET['habilitado'] ?? 0;

if (strpos($mesAno, '-') === false) {
    echo '<script>alert("Mês/Ano inválido."); window.close();</script>';
    exit;
}

[$ano, $mes] = explode('-', $mesAno, 2);
if (!ctype_digit($ano) || !ctype_digit($mes)) {
    echo '<script>alert("Mês/Ano inválido."); window.close();</script>';
    exit;
}

$service = new RelatorioService(new RelatorioRepository());
$data = $service->frequenciaMensal($_GET);

if (empty($data)) {
    echo '<script>alert("Nenhum dado encontrado para os critérios selecionados."); window.close();</script>';
    exit;
}

$tabela = [];
$alunos = [];
$dias = [];
$cursoNome = (string) ($data[0]['NomeCurso'] ?? '');
$turmaNome = (string) ($data[0]['NomeTurma'] ?? '');

foreach ($data as $row) {
    $aluno = (string) ($row['Aluno'] ?? '');
    $dia = (int) ($row['Dia'] ?? 0);
    $status = ((int) ($row['presenca'] ?? 0) === 1)
        ? 'P'
        : (((int) ($row['falta'] ?? 0) === 1)
            ? 'F'
            : (((int) ($row['faltajust'] ?? 0) === 1) ? 'FJ' : ''));

    if (!isset($tabela[$aluno])) {
        $tabela[$aluno] = [];
        $alunos[] = $aluno;
    }

    if (!in_array($dia, $dias, true)) {
        $dias[] = $dia;
    }

    $tabela[$aluno][$dia] = $status;
}

sort($dias);

$jsonAlunos = json_encode($alunos, JSON_UNESCAPED_UNICODE);
$jsonDias = json_encode($dias, JSON_UNESCAPED_UNICODE);
$jsonTabela = json_encode($tabela, JSON_UNESCAPED_UNICODE);
$jsonCursoNome = json_encode($cursoNome, JSON_UNESCAPED_UNICODE);
$jsonTurmaNome = json_encode($turmaNome, JSON_UNESCAPED_UNICODE);
$jsonMesAno = json_encode(sprintf('%02d/%04d', (int) $mes, (int) $ano), JSON_UNESCAPED_UNICODE);

$baseWidth = 210;
$margin = 20;
$usableWidth = $baseWidth - $margin;
$numWidth = 15;
$alunoWidth = 70;
$diasWidth = $usableWidth - $numWidth - $alunoWidth;
$diaWidth = count($dias) > 0 ? ($diasWidth / count($dias)) : 0;
$dataEmissao = date('d/m/Y');

echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js'></script>";

echo "<script>
const numWidth = $numWidth;
const alunoWidth = $alunoWidth;
const diaWidth = $diaWidth;

function renderPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    const alunos = $jsonAlunos;
    const dias = $jsonDias;
    const tabela = $jsonTabela;
    const cursoNome = $jsonCursoNome;
    const turmaNome = $jsonTurmaNome;
    const mesAno = $jsonMesAno;

    const pageHeight = 297;
    const marginTop = 20;
    const marginBottom = 20;
    const contentHeight = pageHeight - marginTop - marginBottom;
    const cellHeight = 10;
    let currentY = marginTop;

    const pageHeader = () => {
        doc.setFontSize(12);
        doc.text(`Curso: ${cursoNome}`, 10, currentY);
        doc.text(`Turma: ${turmaNome}`, 10, currentY + 7);
        doc.text(`Mês/Ano: ${mesAno}`, 10, currentY + 14);
        currentY += 20;
    };

    const header = () => {
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(10);
        doc.setLineWidth(0.2);

        doc.rect(10, currentY, numWidth, cellHeight * 2, 'S');
        doc.text('Nº', 10 + numWidth / 2, currentY + cellHeight, { align: 'center', baseline: 'middle' });

        doc.rect(10 + numWidth, currentY, alunoWidth, cellHeight * 2, 'S');
        doc.text('NOME DOS ALUNOS', 10 + numWidth + alunoWidth / 2, currentY + cellHeight, { align: 'center', baseline: 'middle' });

        const diasTotalWidth = dias.length * diaWidth;
        doc.rect(10 + numWidth + alunoWidth, currentY, diasTotalWidth, cellHeight, 'S');
        doc.text('DATAS', 10 + numWidth + alunoWidth + diasTotalWidth / 2, currentY + cellHeight / 2, { align: 'center', baseline: 'middle' });

        doc.setFont('helvetica', 'normal');
        dias.forEach((dia, index) => {
            const x = 10 + numWidth + alunoWidth + index * diaWidth;
            doc.rect(x, currentY + cellHeight, diaWidth, cellHeight, 'S');
            doc.text(String(dia), x + diaWidth / 2, currentY + cellHeight + cellHeight / 2, { align: 'center', baseline: 'middle' });
        });

        currentY += cellHeight * 2;
    };

    const footer = (pageNum, totalPages) => {
        doc.setFontSize(10);
        doc.text('Data de emissão: $dataEmissao', 10, pageHeight - marginBottom);
        doc.text('Página ' + pageNum + '/' + totalPages, 190, pageHeight - marginBottom, { align: 'right' });
    };

    const drawTable = () => {
        let pageNum = 1;
        const totalPages = Math.max(1, Math.ceil((alunos.length * cellHeight) / contentHeight));

        doc.setFont('helvetica');
        doc.setFontSize(10);

        alunos.forEach((aluno, index) => {
            if (currentY + cellHeight > contentHeight) {
                footer(pageNum, totalPages);
                doc.addPage();
                pageNum += 1;
                currentY = marginTop;
                pageHeader();
                header();
            }

            doc.rect(10, currentY, numWidth, cellHeight, 'S');
            doc.text(String(index + 1), 10 + numWidth / 2, currentY + 7, { align: 'center' });

            doc.rect(10 + numWidth, currentY, alunoWidth, cellHeight, 'S');
            doc.text(String(aluno), 10 + numWidth + 2, currentY + 7);

            dias.forEach((dia, idx) => {
                const x = 10 + numWidth + alunoWidth + idx * diaWidth;
                const status = (tabela[aluno] && tabela[aluno][dia]) ? tabela[aluno][dia] : '';
                doc.rect(x, currentY, diaWidth, cellHeight, 'S');
                doc.text(status, x + diaWidth / 2, currentY + 7, { align: 'center' });
            });

            currentY += cellHeight;
        });

        footer(pageNum, totalPages);
    };

    pageHeader();
    header();
    drawTable();

    doc.output('dataurlnewwindow');
    setTimeout(() => window.close(), 3000);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderPDF);
} else {
    renderPDF();
}
</script>";
