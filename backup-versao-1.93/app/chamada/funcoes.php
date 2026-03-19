<?php
function turma_foto($pdo, $data, $dataSelecionada, $NNomeCurso, $NNomeTurma)
{
    // Consulta para buscar os alunos, cursos e turmas
    $sql = "SELECT 
        tbMatricula.IdMatricula,
        tbMatricula.vData, 
        tbTurma.IdTurma, 
        tbCurso.IdCurso, 
        tbAluno.IdUsuario AS IdAluno, 
        tbAluno.Nome, 
        tbAluno.Apelido, 
        tbAluno.Foto,
        (SELECT COUNT(*) FROM tbChamada WHERE tbChamada.IdMatricula = tbMatricula.IdMatricula AND tbChamada.Falta = 1) AS totalFaltas
    FROM 
        tbMatricula
    INNER JOIN tbTurma ON tbMatricula.IdTurma = tbTurma.IdTurma
    INNER JOIN tbCurso ON tbMatricula.IdCurso = tbCurso.IdCurso
    INNER JOIN tbAluno ON tbMatricula.IdUsuario = tbAluno.IdUsuario
    WHERE 
        tbCurso.IdCurso = ? 
        AND tbTurma.IdTurma = ?
        AND tbMatricula.Habilitado = 1
        -- AND tbMatricula.vData <= ?
    ORDER BY tbAluno.Nome";

    // Prepara e executa a consulta usando PDO
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['NNomeCurso'], $_GET['NNomeTurma']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $TotalCards = 0;
    $output = "";

    if (count($rows) > 0) {
        foreach ($rows as $row) {
            $TotalCards++;
            $IdMatricula = $row['IdMatricula'];
            $DataMatricula = $row['vData'];
            $IdTurma = $row['IdTurma'];
            $IdCurso = $row['IdCurso'];
            $IdAluno = $row['IdAluno'];
            $Nome = $row['Nome'];
            $Apelido = $row['Apelido'];
            $totalFaltas = $row['totalFaltas'];

            if (isset($Apelido) && $Apelido !== "" && $Apelido !== null) {
                $Nome = "(" . $Apelido . ") " . $Nome;
            }

            // Obtendo a cor do alerta
            $sqlAlerta = "SELECT alertaFaltoso, corFaltoso, alertaExcluido, corExcluido FROM tbAlerta LIMIT 1";
            $stmtAlerta = $pdo->prepare($sqlAlerta);
            $stmtAlerta->execute();
            $alerta = $stmtAlerta->fetch(PDO::FETCH_ASSOC);

            $corCard = "#FFFFFF"; // Branco padrão
            if ($totalFaltas >= $alerta['alertaExcluido']) {
                $corCard = $alerta['corExcluido'];
            } elseif ($totalFaltas >= $alerta['alertaFaltoso']) {
                $corCard = $alerta['corFaltoso'];
            }

            $foto = $_SESSION['BASE_URL'] . "/assets/img/fotos/" . $row['Foto'];

            $output .= "<div class='card' 
                            id='card_{$IdMatricula}_{$IdTurma}_{$IdCurso}_{$IdAluno}'
                            data-bs-toggle='tooltip' 
                            data-bs-placement='top' 
                            title='Matrícula: {$IdMatricula} | Código aluno: {$IdAluno} | Curso: {$IdCurso} | Turma: {$IdTurma} | Dt Matr.: {$DataMatricula}' 
                            style='background-color: {$corCard};'>";
            $output .= "<img src='$foto' alt='Foto_de_$Nome'>";
            $link = "listTotalChamada.php?chamada=1&idAluno={$IdAluno}&dataSelecionada={$dataSelecionada}&NNomeCurso=" . urlencode($NNomeCurso) . "&NNomeTurma=" . urlencode($NNomeTurma);
            $output .= "<h4>$Nome <a href='$link' class='text-decoration-none'>({$totalFaltas} faltas)</a></h4>";

            $output .= renderizarBotoes($IdMatricula, $foto, $Nome, 'T00');
            $output .= "</div>";
        }
    } else {
        $output = "Nenhum registro encontrado.<br>";
    }
    return ['html' => $output, 'totalCards' => $TotalCards];
}

function renderizarBotoes($idMatricula, $fotoaluno, $membro, $Obs)
{
    return "<div class='button-container'>
                <button class='btn btnp' id='P-{$idMatricula}' onclick='alterarCor(this, \"verde\")'>P</button>
                <button class='btn btnp' id='F-{$idMatricula}' onclick='alterarCor(this, \"vermelho\")'>F</button>
                <button class='btn btnp' id='FJ-{$idMatricula}' onclick='alterarCor(this, \"amarelo\")'>FJ</button>
                <button type='button' class='btn' data-bs-toggle='modal' data-bs-target='#obsModal' data-idmatricula='{$idMatricula}' data-fotoaluno='{$fotoaluno}' data-nomealuno='{$membro}' data-observacoes='{$Obs}' id='Obs-{$idMatricula}'>Obs</button>
            </div>";
}

function renderizarBotoesPorData($idMatricula, $data, $fotoaluno, $membro, $Obs)
{
    $dataId = str_replace('-', '', $data);
    return "<div class='button-container'>
                <button class='btn btnp' id='P-{$idMatricula}-{$dataId}' data-date='{$data}'>P</button>
                <button class='btn btnp' id='F-{$idMatricula}-{$dataId}' data-date='{$data}'>F</button>
                <button class='btn btnp' id='FJ-{$idMatricula}-{$dataId}' data-date='{$data}'>FJ</button>
                <button type='button' class='btn obs-botao' id='Obs-{$idMatricula}-{$dataId}' data-date='{$data}' data-bs-toggle='tooltip' title='Observações do aluno' disabled>Obs</button>
            </div>";
}
