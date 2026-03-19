<?php
session_set_cookie_params(['httponly' => true]);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?erro=Ocorreu%20um%20erro!%20Talvez%20voce%20tenha%20perdido%20sua%20ultima%20acao.%20Verifique.");
    exit();
}

require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

$beneficiarios = isset($_POST['codAluno']) && is_array($_POST['codAluno']) ? $_POST['codAluno'] : [];
$cursoTurmasPost = isset($_POST['CursoTurma']) && is_array($_POST['CursoTurma']) ? $_POST['CursoTurma'] : [];
$dataMatricula = isset($_POST['dataMatricula']) ? $_POST['dataMatricula'] : date('Y-m-d');

if (empty($beneficiarios) || empty($cursoTurmasPost)) {
    header("Location: " . $_SESSION['BASE_URL'] . "/app/beneficiario/listagemSBenef.php?erro=" . urlencode("Selecione beneficiarios, cursos e turmas para continuar.") . "&matricula=1");
    exit();
}

$dataTimestamp = strtotime($dataMatricula);
$dataConvertida = $dataTimestamp ? date('d/m/Y', $dataTimestamp) : date('d/m/Y');

function parseNascimento($dataStr)
{
    if (!$dataStr) {
        return null;
    }

    $dataStr = trim($dataStr);
    $data = DateTime::createFromFormat('d/m/Y', $dataStr);
    if ($data instanceof DateTime) {
        return $data;
    }

    $data = DateTime::createFromFormat('Y-m-d', $dataStr);
    if ($data instanceof DateTime) {
        return $data;
    }

    return null;
}

$beneficiariosIds = array_values(array_unique(array_filter(array_map('intval', $beneficiarios), function ($id) {
    return $id > 0;
})));

if (empty($beneficiariosIds)) {
    header("Location: " . $_SESSION['BASE_URL'] . "/app/beneficiario/listagemSBenef.php?erro=" . urlencode("Nenhum beneficiario valido foi informado.") . "&matricula=1");
    exit();
}

$selecoesSolicitadas = [];
$turmaIdsSolicitados = [];

foreach ($cursoTurmasPost as $item) {
    $idCurso = isset($item['IdCurso']) ? (int)$item['IdCurso'] : 0;
    $idTurma = isset($item['IdTurma']) ? (int)$item['IdTurma'] : 0;

    if ($idCurso <= 0 || $idTurma <= 0) {
        continue;
    }

    $chave = $idCurso . '-' . $idTurma;
    if (!isset($selecoesSolicitadas[$chave])) {
        $selecoesSolicitadas[$chave] = [
            'IdCurso' => $idCurso,
            'IdTurma' => $idTurma,
        ];
        $turmaIdsSolicitados[] = $idTurma;
    }
}

if (empty($selecoesSolicitadas)) {
    header("Location: " . $_SESSION['BASE_URL'] . "/app/beneficiario/listagemSBenef.php?erro=" . urlencode("Nenhum par curso/turma valido foi informado.") . "&matricula=1");
    exit();
}

$turmaIdsSolicitados = array_values(array_unique($turmaIdsSolicitados));
$placeholdersTurma = implode(',', array_fill(0, count($turmaIdsSolicitados), '?'));

$stmtTurmas = $pdo->prepare("SELECT t.IdTurma, t.IdCurso, t.NomeTurma, t.MaxMatriculas, c.NomeCurso, c.IdadeMin, c.IdadeMax
                            FROM tbTurma t
                            INNER JOIN tbCurso c ON c.IdCurso = t.IdCurso
                            WHERE t.IdTurma IN ($placeholdersTurma) AND t.Habilitado = 1 AND c.Habilitado = 1");
$stmtTurmas->execute($turmaIdsSolicitados);

$turmasValidas = [];
while ($row = $stmtTurmas->fetch(PDO::FETCH_ASSOC)) {
    $idTurma = (int)$row['IdTurma'];
    $turmasValidas[$idTurma] = [
        'IdTurma' => $idTurma,
        'IdCurso' => (int)$row['IdCurso'],
        'NomeTurma' => $row['NomeTurma'],
        'NomeCurso' => $row['NomeCurso'],
        'IdadeMin' => $row['IdadeMin'] !== null ? (int)$row['IdadeMin'] : null,
        'IdadeMax' => $row['IdadeMax'] !== null ? (int)$row['IdadeMax'] : null,
        'MaxMatriculas' => $row['MaxMatriculas'] !== null ? (int)$row['MaxMatriculas'] : null,
    ];
}

$selecoes = [];
$cursoIdsSelecionados = [];
$turmaIdsSelecionados = [];

foreach ($selecoesSolicitadas as $item) {
    $idCurso = $item['IdCurso'];
    $idTurma = $item['IdTurma'];

    if (!isset($turmasValidas[$idTurma])) {
        continue;
    }

    if ($turmasValidas[$idTurma]['IdCurso'] !== $idCurso) {
        continue;
    }

    $dadosTurma = $turmasValidas[$idTurma];
    $selecoes[] = [
        'IdCurso' => $idCurso,
        'IdTurma' => $idTurma,
        'NomeCurso' => $dadosTurma['NomeCurso'],
        'NomeTurma' => $dadosTurma['NomeTurma'],
        'IdadeMin' => $dadosTurma['IdadeMin'],
        'IdadeMax' => $dadosTurma['IdadeMax'],
    ];

    $cursoIdsSelecionados[] = $idCurso;
    $turmaIdsSelecionados[] = $idTurma;
}

if (empty($selecoes)) {
    header("Location: " . $_SESSION['BASE_URL'] . "/app/beneficiario/listagemSBenef.php?erro=" . urlencode("Nao foi possivel validar os pares curso/turma selecionados.") . "&matricula=1");
    exit();
}

$cursoIdsSelecionados = array_values(array_unique($cursoIdsSelecionados));
$turmaIdsSelecionados = array_values(array_unique($turmaIdsSelecionados));

$placeholdersAluno = implode(',', array_fill(0, count($beneficiariosIds), '?'));
$stmtAlunos = $pdo->prepare("SELECT IdUsuario, Nome, Nascimento, Habilitado FROM tbAluno WHERE IdUsuario IN ($placeholdersAluno)");
$stmtAlunos->execute($beneficiariosIds);

$alunos = [];
while ($row = $stmtAlunos->fetch(PDO::FETCH_ASSOC)) {
    $alunos[(int)$row['IdUsuario']] = [
        'Nome' => $row['Nome'],
        'Nascimento' => $row['Nascimento'],
        'Habilitado' => (int)$row['Habilitado'],
    ];
}

$placeholdersTurmaCount = implode(',', array_fill(0, count($turmaIdsSelecionados), '?'));
$stmtCount = $pdo->prepare("SELECT IdTurma, COUNT(*) AS TotalAtivos
                           FROM tbMatricula
                           WHERE IdTurma IN ($placeholdersTurmaCount) AND Habilitado = 1
                           GROUP BY IdTurma");
$stmtCount->execute($turmaIdsSelecionados);

$totaisAtivos = [];
while ($row = $stmtCount->fetch(PDO::FETCH_ASSOC)) {
    $totaisAtivos[(int)$row['IdTurma']] = (int)$row['TotalAtivos'];
}

$restantesTurma = [];
foreach ($turmaIdsSelecionados as $idTurma) {
    $maxMatriculas = $turmasValidas[$idTurma]['MaxMatriculas'];
    if ($maxMatriculas === null) {
        $restantesTurma[$idTurma] = null;
    } else {
        $restantesTurma[$idTurma] = max(0, $maxMatriculas - ($totaisAtivos[$idTurma] ?? 0));
    }
}

$placeholdersCurso = implode(',', array_fill(0, count($cursoIdsSelecionados), '?'));
$paramsExistentes = array_merge($beneficiariosIds, $cursoIdsSelecionados);
$phExistAluno = implode(',', array_fill(0, count($beneficiariosIds), '?'));
$phExistCurso = implode(',', array_fill(0, count($cursoIdsSelecionados), '?'));
$stmtExistentes = $pdo->prepare("SELECT IdUsuario, IdCurso
                                FROM tbMatricula
                                WHERE IdUsuario IN ($phExistAluno)
                                  AND IdCurso IN ($phExistCurso)
                                  AND Habilitado = 1");
$stmtExistentes->execute($paramsExistentes);

$matriculasAtivas = [];
while ($row = $stmtExistentes->fetch(PDO::FETCH_ASSOC)) {
    $matriculasAtivas[(int)$row['IdUsuario'] . '-' . (int)$row['IdCurso']] = true;
}

$stmtInsert = $pdo->prepare("INSERT INTO tbMatricula (IdCurso, IdTurma, IdUsuario, vData, Habilitado)
                            VALUES (?, ?, ?, ?, 1)");

$resultadoLinhas = [];
$agora = new DateTime('today');
$mensagemPadraoObs = "Por favor, verifique se o(a) beneficiario(a) ja se encontra matriculado(a) no(s) referido(s) curso(s); se a idade do(a) beneficiario(a) atende as regras do projeto; e se o limite maximo de pessoas nao foi atingido.";

foreach ($beneficiariosIds as $idUsuario) {
    $dadosAluno = $alunos[$idUsuario] ?? null;
    $nomeAluno = $dadosAluno ? $dadosAluno['Nome'] : ('ID ' . $idUsuario);
    $nascimento = $dadosAluno ? $dadosAluno['Nascimento'] : null;
    $dataNascimento = parseNascimento($nascimento);
    $idade = $dataNascimento ? $dataNascimento->diff($agora)->y : null;

    $matriculadoEm = [];
    $observacoes = [];

    foreach ($selecoes as $selecao) {
        $idCurso = $selecao['IdCurso'];
        $idTurma = $selecao['IdTurma'];
        $rotulo = $selecao['NomeCurso'] . ' - ' . $selecao['NomeTurma'];

        if (!$dadosAluno || $dadosAluno['Habilitado'] !== 1) {
            $observacoes[] = $rotulo . ' (beneficiario inativo ou nao encontrado)';
            continue;
        }

        $chaveMatricula = $idUsuario . '-' . $idCurso;
        if (isset($matriculasAtivas[$chaveMatricula])) {
            $observacoes[] = $rotulo . ' (ja matriculado no curso)';
            continue;
        }

        $idadeMin = $selecao['IdadeMin'];
        $idadeMax = $selecao['IdadeMax'];
        if (($idadeMin !== null || $idadeMax !== null) && $idade === null) {
            $observacoes[] = $rotulo . ' (sem data de nascimento valida para validar idade)';
            continue;
        }

        if (($idadeMin !== null && $idade < $idadeMin) || ($idadeMax !== null && $idade > $idadeMax)) {
            $observacoes[] = $rotulo . ' (fora da faixa etaria do curso)';
            continue;
        }

        $restante = $restantesTurma[$idTurma] ?? null;
        if ($restante !== null && $restante <= 0) {
            $observacoes[] = $rotulo . ' (limite maximo da turma atingido)';
            continue;
        }

        try {
            $stmtInsert->execute([$idCurso, $idTurma, $idUsuario, $dataConvertida]);
            $matriculadoEm[] = $rotulo;
            $matriculasAtivas[$chaveMatricula] = true;

            if ($restante !== null) {
                $restantesTurma[$idTurma] = $restante - 1;
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $observacoes[] = $rotulo . ' (ja matriculado no curso)';
            } else {
                $observacoes[] = $rotulo . ' (erro ao efetuar matricula)';
            }
        }
    }

    $resultadoLinhas[] = [
        'aluno' => $nomeAluno,
        'matriculas' => !empty($matriculadoEm) ? implode('; ', $matriculadoEm) : 'Nenhuma matricula efetivada',
        'observacoes' => !empty($observacoes) ? (implode('; ', $observacoes) . '. ' . $mensagemPadraoObs) : 'Sem impedimentos.',
    ];
}

$_SESSION['resultado_matriculas_lote'] = [
    'linhas' => $resultadoLinhas,
    'data_execucao' => date('d/m/Y H:i:s'),
];

$pdo = null;
header("Location: " . $_SESSION['BASE_URL'] . "/app/matricula/resultadoMatriculas.php");
exit();
?>