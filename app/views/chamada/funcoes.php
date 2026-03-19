<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

function chamadaCriarDataBase(?string $data): ?DateTimeImmutable
{
    $valor = trim((string)$data);
    if ($valor === '') {
        return null;
    }

    foreach (['d/m/Y', 'Y-m-d'] as $formato) {
        $date = DateTimeImmutable::createFromFormat($formato, $valor);
        if ($date instanceof DateTimeImmutable) {
            return $date;
        }
    }

    try {
        return new DateTimeImmutable($valor);
    } catch (Throwable $e) {
        return null;
    }
}

function chamadaAniversarioProximo(?string $nascimento, ?string $dataSelecionada): ?array
{
    $dataBase = chamadaCriarDataBase($dataSelecionada);
    $dataNascimento = chamadaCriarDataBase($nascimento);
    if (!$dataBase || !$dataNascimento) {
        return null;
    }

    $mesDiaNascimento = $dataNascimento->format('m-d');
    $anoBase = (int)$dataBase->format('Y');
    $menorDiferenca = null;
    $dataAniversario = null;

    foreach ([$anoBase - 1, $anoBase, $anoBase + 1] as $ano) {
        $aniversario = DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%04d-%s', $ano, $mesDiaNascimento));
        if (!($aniversario instanceof DateTimeImmutable)) {
            continue;
        }

        $diferencaDias = (int)$dataBase->diff($aniversario)->format('%r%a');
        if ($menorDiferenca === null || abs($diferencaDias) < abs($menorDiferenca)) {
            $menorDiferenca = $diferencaDias;
            $dataAniversario = $aniversario;
        }
    }

    if ($menorDiferenca === null || !$dataAniversario || abs($menorDiferenca) > 3) {
        return null;
    }

    if ($menorDiferenca === 0) {
        $mensagem = 'Aniversaria hoje';
        $acaoIdade = 'completa';
    } elseif ($menorDiferenca < 0) {
        $dias = abs($menorDiferenca);
        $mensagem = 'Aniversariou ha ' . $dias . ' dia' . ($dias > 1 ? 's' : '');
        $acaoIdade = 'completou';
    } else {
        $mensagem = 'Aniversaria em ' . $menorDiferenca . ' dia' . ($menorDiferenca > 1 ? 's' : '');
        $acaoIdade = 'completa';
    }

    return [
        'mensagem' => $mensagem,
        'data' => $dataAniversario,
        'idade' => (int)$dataNascimento->diff($dataAniversario)->y,
        'acao_idade' => $acaoIdade,
    ];
}

function turma_foto($pdo, $data, $dataSelecionada, $NNomeCurso, $NNomeTurma, $baseUrl = '')
{
    $sql = "SELECT
        tbMatricula.IdMatricula,
        tbMatricula.vData,
        tbTurma.IdTurma,
        tbCurso.IdCurso,
        tbAluno.IdUsuario AS IdAluno,
        tbAluno.Nome,
        tbAluno.Apelido,
        tbAluno.Foto,
        tbAluno.Nascimento,
        (SELECT COUNT(*) FROM tbChamada WHERE tbChamada.IdMatricula = tbMatricula.IdMatricula AND tbChamada.Falta = 1) AS totalFaltas
    FROM tbMatricula
    INNER JOIN tbTurma ON tbMatricula.IdTurma = tbTurma.IdTurma
    INNER JOIN tbCurso ON tbMatricula.IdCurso = tbCurso.IdCurso
    INNER JOIN tbAluno ON tbMatricula.IdUsuario = tbAluno.IdUsuario
    WHERE tbCurso.IdCurso = ?
      AND tbTurma.IdTurma = ?
      AND tbMatricula.Habilitado = 1
    ORDER BY tbAluno.Nome";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['NNomeCurso'], $_GET['NNomeTurma']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalCards = 0;
    $output = '';

    if (count($rows) > 0) {
        foreach ($rows as $row) {
            $totalCards++;
            $idMatricula = $row['IdMatricula'];
            $dataMatricula = $row['vData'];
            $idTurma = $row['IdTurma'];
            $idCurso = $row['IdCurso'];
            $idAluno = $row['IdAluno'];
            $nome = (string)$row['Nome'];
            $apelido = $row['Apelido'];
            $nascimento = $row['Nascimento'] ?? '';
            $totalFaltas = (int)$row['totalFaltas'];

            if (isset($apelido) && $apelido !== '' && $apelido !== null) {
                $nome = '(' . $apelido . ') ' . $nome;
            }

            $sqlAlerta = 'SELECT alertaFaltoso, corFaltoso, alertaExcluido, corExcluido FROM tbAlerta LIMIT 1';
            $stmtAlerta = $pdo->prepare($sqlAlerta);
            $stmtAlerta->execute();
            $alerta = $stmtAlerta->fetch(PDO::FETCH_ASSOC);

            $corCard = '#FFFFFF';
            if ($totalFaltas >= (int)$alerta['alertaExcluido']) {
                $corCard = $alerta['corExcluido'];
            } elseif ($totalFaltas >= (int)$alerta['alertaFaltoso']) {
                $corCard = $alerta['corFaltoso'];
            }

            $foto = bootstrap_foto_url((string)($row['Foto'] ?? ''));
            $aniversario = chamadaAniversarioProximo($nascimento, $dataSelecionada);
            $badgeAniversario = '';
            $infoAniversario = '';
            $classeAniversario = '';
            if ($aniversario !== null) {
                $tituloAniversario = htmlspecialchars(
                    $aniversario['mensagem'] . ' - ' . $aniversario['data']->format('d/m'),
                    ENT_QUOTES,
                    'UTF-8'
                );
                $classeAniversario = ' card-birthday';
                $badgeAniversario = "<span class='birthday-badge' title='{$tituloAniversario}' aria-label='{$tituloAniversario}'>&#129395;</span>";
                $diaMes = htmlspecialchars($aniversario['data']->format('d/m'), ENT_QUOTES, 'UTF-8');
                $idade = (int)$aniversario['idade'];
                $acaoIdade = htmlspecialchars((string)$aniversario['acao_idade'], ENT_QUOTES, 'UTF-8');
                $infoAniversario = "<p class='birthday-info'>Aniversario: {$diaMes} - {$acaoIdade} {$idade} anos</p>";
            }

            $output .= "<div class='card{$classeAniversario}'
                            id='card_{$idMatricula}_{$idTurma}_{$idCurso}_{$idAluno}'
                            data-bs-toggle='tooltip'
                            data-bs-placement='top'
                            title='Matricula: {$idMatricula} | Codigo aluno: {$idAluno} | Curso: {$idCurso} | Turma: {$idTurma} | Dt Mat.: {$dataMatricula}'
                            style='background-color: {$corCard};'>";
            $output .= $badgeAniversario;
            $output .= "<img src='{$foto}' alt='Foto_de_" . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . "'>";
            $link = rtrim((string)$baseUrl, '/') . "/chamada/faltas?chamada=1&idAluno={$idAluno}&dataSelecionada=" . urlencode((string)$dataSelecionada) . '&NNomeCurso=' . urlencode((string)$NNomeCurso) . '&NNomeTurma=' . urlencode((string)$NNomeTurma);
            $output .= '<h4>' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . " <a href='{$link}' class='text-decoration-none'>({$totalFaltas} faltas)</a></h4>";
            $output .= $infoAniversario;
            $output .= renderizarBotoes($idMatricula, $foto, $nome, 'T00');
            $output .= '</div>';
        }
    } else {
        $output = 'Nenhum registro encontrado.<br>';
    }

    return ['html' => $output, 'totalCards' => $totalCards];
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
                <button type='button' class='btn obs-botao' id='Obs-{$idMatricula}-{$dataId}' data-date='{$data}' data-bs-toggle='tooltip' title='Observacoes do aluno' disabled>Obs</button>
            </div>";
}
