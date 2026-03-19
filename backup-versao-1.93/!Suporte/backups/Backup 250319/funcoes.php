<?php
function turma_foto($pdo, $data)
{
    // Implementação específica desta função
    $sql = "SELECT 
        tbMatricula.IdMatricula, 
        tbTurma.IdTurma, 
        tbCurso.IdCurso, 
        tbAluno.IdUsuario AS IdAluno, 
        tbAluno.Nome, 
        tbAluno.Apelido, 
        tbAluno.Foto
    FROM 
        tbMatricula
    INNER JOIN tbTurma ON tbMatricula.IdTurma = tbTurma.IdTurma
    INNER JOIN tbCurso ON tbMatricula.IdCurso = tbCurso.IdCurso
    INNER JOIN tbAluno ON tbMatricula.IdUsuario = tbAluno.IdUsuario
    WHERE 
        tbCurso.IdCurso = ? 
        AND tbTurma.IdTurma = ?
    ORDER BY tbAluno.Nome";

    // Prepara e executa a consulta usando PDO
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['NNomeCurso'], $_GET['NNomeTurma']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $TotalCards = 0;
    $output = ""; // Inicializa uma variável para armazenar a saída HTML

    if (count($rows) > 0) {
        foreach ($rows as $row) {
            $TotalCards++;
            $IdMatricula = $row['IdMatricula'];
            $IdTurma = $row['IdTurma'];
            $IdCurso = $row['IdCurso'];
            $IdAluno = $row['IdAluno'];
            $Nome = $row['Nome'];
            $Apelido = $row['Apelido'];
            if (isset($Apelido) && $Apelido !== "" && $Apelido !== null) {
                $Nome = "(" . $Apelido . ") " . $Nome;
            }
            $foto = $_SESSION['BASE_URL'] . "/assets/img/fotos/" . $row['Foto'];

            $output .= "<div class='card' id='card_{$IdMatricula}_{$IdTurma}_{$IdCurso}_{$IdAluno}'>";
            $output .= "<img src='$foto' alt='Foto_de_$Nome'>";
            $output .= "<h4>$Nome</h4>";
            $output .= renderizarBotoes($IdMatricula, $foto, $Nome, 'T00');
            $output .= "</div>";
        }
    } else {
        $output = "Nenhum registro encontrado.<br>";
    }
    // Retorna um array contendo a saída HTML e o número total de cards
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
?>
