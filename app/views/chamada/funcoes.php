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
        $mensagem = 'Aniversariou há ' . $dias . ' dia' . ($dias > 1 ? 's' : '');
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

function chamadaDataBrParaIso(?string $data): ?string
{
    $date = chamadaCriarDataBase($data);
    return $date ? $date->format('Y-m-d') : null;
}

function chamadaSomenteDigitos(?string $valor): string
{
    return preg_replace('/\D+/', '', (string)$valor) ?? '';
}

function chamadaFormatarCpf(?string $cpf): string
{
    $digitos = chamadaSomenteDigitos($cpf);
    if (strlen($digitos) !== 11) {
        return trim((string)$cpf);
    }

    return substr($digitos, 0, 3) . '.' . substr($digitos, 3, 3) . '.' . substr($digitos, 6, 3) . '-' . substr($digitos, 9, 2);
}

function chamadaUsuarioPodeRedefinir(?array $sessao = null): bool
{
    $sessao = $sessao ?? $_SESSION;
    $tipo = (string)($sessao['Tipo'] ?? '');
    $idPermissao = (int)($sessao['IdPermissao'] ?? 0);

    return $idPermissao === 4 || in_array($tipo, ['Geral', 'Administrador', 'Superadministrador'], true);
}

function chamadaRemoverAcentos(string $valor): string
{
    $valor = strtr($valor, [
        'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'Ç' => 'C', 'ç' => 'c', 'Ñ' => 'N', 'ñ' => 'n',
    ]);

    if (class_exists('Transliterator')) {
        $transliterador = Transliterator::create('Any-Latin; Latin-ASCII;');
        if ($transliterador instanceof Transliterator) {
            $valor = $transliterador->transliterate($valor);
        }
    } else {
        $convertido = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);
        if ($convertido !== false) {
            $valor = $convertido;
        }
    }

    return $valor;
}

function chamadaNomeFotoParaZip(string $nomeAluno): string
{
    $nome = chamadaRemoverAcentos(trim($nomeAluno));
    $nome = preg_replace('/[^A-Za-z0-9\s]/', ' ', $nome) ?? '';
    $partes = preg_split('/\s+/', trim($nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];

    if ($partes === []) {
        return 'Aluno.jpg';
    }

    $nomeBase = implode(' ', $partes);
    if (strlen($nomeBase) > 15 && count($partes) > 3) {
        $preposicoes = ['da', 'das', 'de', 'do', 'dos', 'e'];
        $quantidadePrimeiras = in_array(strtolower($partes[1] ?? ''), $preposicoes, true) && count($partes) > 4 ? 3 : 2;
        $primeiras = array_slice($partes, 0, $quantidadePrimeiras);
        $ultima = $partes[count($partes) - 1];
        $intermediarias = array_slice($partes, $quantidadePrimeiras, -1);
        $iniciais = [];

        foreach ($intermediarias as $parte) {
            if (in_array(strtolower($parte), $preposicoes, true)) {
                continue;
            }
            $iniciais[] = strtoupper(substr($parte, 0, 1));
        }

        $partes = array_merge($primeiras, $iniciais, [$ultima]);
    }

    $arquivo = implode('_', $partes);
    $arquivo = preg_replace('/_+/', '_', $arquivo) ?? 'Aluno';
    $arquivo = trim($arquivo, '_');

    return ($arquivo !== '' ? $arquivo : 'Aluno') . '.jpg';
}

function chamadaResolverFotoAlunoPath(?string $foto): string
{
    $foto = trim((string)$foto);
    if ($foto !== '' && !preg_match('/^https?:\/\//i', $foto)) {
        $resolvida = bootstrap_resolve_assets_img_file($foto);
        if ($resolvida !== '') {
            return $resolvida;
        }
    }

    foreach (['padrao.jfif', 'padrao.jpg', 'padrao.png'] as $arquivoPadrao) {
        $padrao = bootstrap_resolve_assets_img_file($arquivoPadrao);
        if ($padrao !== '') {
            return $padrao;
        }
    }

    return '';
}

function buscarAlunosChamada($pdo, $dataSelecionada, $NNomeCurso, $NNomeTurma): array
{
    $dataIso = chamadaDataBrParaIso($dataSelecionada);
    $sql = "SELECT
        tbMatricula.IdMatricula,
        tbMatricula.vData,
        tbTurma.IdTurma,
        tbCurso.IdCurso,
        tbAluno.IdUsuario AS IdAluno,
        tbAluno.Nome,
        tbAluno.Apelido,
        tbAluno.CPF,
        tbAluno.Foto,
        tbAluno.Nascimento,
        tbCurso.NomeCurso,
        tbTurma.NomeTurma,
        (SELECT COUNT(*) FROM tbChamada WHERE tbChamada.IdMatricula = tbMatricula.IdMatricula AND tbChamada.falta = 1) AS totalFaltas,
        (SELECT COUNT(*) FROM tbChamada WHERE tbChamada.IdMatricula = tbMatricula.IdMatricula AND tbChamada.faltajust = 1) AS totalFaltasJustificadas,
        ch.IdChamada,
        ch.IdColaborador,
        CONCAT_WS(' ', u.Nome, u.Sobrenome) AS NomeColaboradorChamada
    FROM tbMatricula
    INNER JOIN tbTurma ON tbMatricula.IdTurma = tbTurma.IdTurma
    INNER JOIN tbCurso ON tbMatricula.IdCurso = tbCurso.IdCurso
    INNER JOIN tbAluno ON tbMatricula.IdUsuario = tbAluno.IdUsuario
    LEFT JOIN tbChamada ch
        ON ch.IdChamada = (
            SELECT ch2.IdChamada
            FROM tbChamada ch2
            WHERE ch2.IdMatricula = tbMatricula.IdMatricula
              AND ch2.IdCurso = tbCurso.IdCurso
              AND ch2.IdTurma = tbTurma.IdTurma
              AND ch2.Data = ?
            ORDER BY ch2.IdChamada DESC
            LIMIT 1
        )
    LEFT JOIN tbUser u ON u.IdColaborador = ch.IdColaborador
    WHERE tbCurso.IdCurso = ?
      AND tbTurma.IdTurma = ?
      AND tbMatricula.Habilitado = 1
    ORDER BY tbAluno.Nome";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dataIso, $NNomeCurso, $NNomeTurma]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function chamadaAlertaCores($pdo): array
{
    $sqlAlerta = 'SELECT alertaFaltoso, corFaltoso, alertaExcluido, corExcluido FROM tbAlerta LIMIT 1';
    $stmtAlerta = $pdo->prepare($sqlAlerta);
    $stmtAlerta->execute();
    return $stmtAlerta->fetch(PDO::FETCH_ASSOC) ?: [
        'alertaFaltoso' => 999999,
        'corFaltoso' => '#FFFFFF',
        'alertaExcluido' => 999999,
        'corExcluido' => '#FFFFFF',
    ];
}

function chamadaPrepararAluno(array $row, ?string $dataSelecionada, array $alerta, string $baseUrl, $NNomeCurso, $NNomeTurma): array
{
    $idMatricula = (int)$row['IdMatricula'];
    $dataMatricula = (string)($row['vData'] ?? '');
    $idTurma = (int)$row['IdTurma'];
    $idCurso = (int)$row['IdCurso'];
    $idAluno = (int)$row['IdAluno'];
    $nome = (string)$row['Nome'];
    $apelido = $row['Apelido'] ?? null;
    $nascimento = (string)($row['Nascimento'] ?? '');
    $totalFaltas = (int)$row['totalFaltas'];
    $totalFaltasJustificadas = (int)($row['totalFaltasJustificadas'] ?? 0);
    $cpf = (string)($row['CPF'] ?? '');
    $cpfDigitos = chamadaSomenteDigitos($cpf);
    $cpfFormatado = chamadaFormatarCpf($cpf);
    $nomeCurso = (string)($row['NomeCurso'] ?? '');
    $nomeTurma = (string)($row['NomeTurma'] ?? '');

    if (isset($apelido) && $apelido !== '' && $apelido !== null) {
        $nome = '(' . $apelido . ') ' . $nome;
    }

    $corCard = '#FFFFFF';
    if ($totalFaltas >= (int)$alerta['alertaExcluido']) {
        $corCard = (string)$alerta['corExcluido'];
    } elseif ($totalFaltas >= (int)$alerta['alertaFaltoso']) {
        $corCard = (string)$alerta['corFaltoso'];
    }

    $foto = bootstrap_foto_url((string)($row['Foto'] ?? ''));
    $aniversario = chamadaAniversarioProximo($nascimento, $dataSelecionada);
    $aniversarioTexto = '';
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
        $aniversarioTexto = "Aniversário: {$diaMes} - {$acaoIdade} {$idade} anos";
        $infoAniversario = "<p class='birthday-info'>{$aniversarioTexto}</p>";
    }

    $baseUrl = rtrim((string)$baseUrl, '/');
    $linkFrequencia = $baseUrl . "/chamada/faltas?chamada=1&idAluno={$idAluno}&dataSelecionada=" . urlencode((string)$dataSelecionada) . '&NNomeCurso=' . urlencode((string)$NNomeCurso) . '&NNomeTurma=' . urlencode((string)$NNomeTurma);
    $linkCadastro = $baseUrl . '/beneficiarios/cadastro';
    $cadastroParams = [];
    if (strlen($cpfDigitos) === 11) {
        $cadastroParams['cpf'] = $cpfDigitos;
    }
    if ($idAluno > 0) {
        $cadastroParams['id'] = (string)$idAluno;
    }
    if ($cadastroParams !== []) {
        $linkCadastro .= '?' . http_build_query($cadastroParams);
    }
    $linkTurma = $baseUrl . '/matriculas/turma?turma=' . urlencode((string)$idTurma);
    $textoFaltas = "{$totalFaltas} F e {$totalFaltasJustificadas} FJ";
    $modalPayload = rawurlencode(json_encode([
        'nome' => $nome,
        'cpfFormatado' => $cpfFormatado !== '' ? $cpfFormatado : 'Não informado',
        'cursoNome' => $nomeCurso,
        'turmaNome' => $nomeTurma,
        'dataSelecionada' => (string)$dataSelecionada,
        'urlCadastro' => $linkCadastro,
        'urlFrequencia' => $linkFrequencia,
        'urlTurma' => $linkTurma,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');

    return [
        'idMatricula' => $idMatricula,
        'dataMatricula' => $dataMatricula,
        'idTurma' => $idTurma,
        'idCurso' => $idCurso,
        'idAluno' => $idAluno,
        'nome' => $nome,
        'foto' => $foto,
        'totalFaltas' => $totalFaltas,
        'totalFaltasJustificadas' => $totalFaltasJustificadas,
        'textoFaltas' => $textoFaltas,
        'corCard' => $corCard,
        'classeAniversario' => $classeAniversario,
        'badgeAniversario' => $badgeAniversario,
        'infoAniversario' => $infoAniversario,
        'aniversarioTexto' => $aniversarioTexto,
        'linkFaltas' => $linkFrequencia,
        'modalPayload' => $modalPayload,
        'nomeColaboradorChamada' => trim((string)($row['NomeColaboradorChamada'] ?? '')),
    ];
}

function renderizarGridChamada(array $rows, $pdo, $dataSelecionada, $NNomeCurso, $NNomeTurma, $baseUrl = ''): array
{
    $alerta = chamadaAlertaCores($pdo);
    $totalCards = 0;
    $output = '';

    if (count($rows) > 0) {
        foreach ($rows as $row) {
            $totalCards++;
            $aluno = chamadaPrepararAluno($row, $dataSelecionada, $alerta, (string)$baseUrl, $NNomeCurso, $NNomeTurma);
            $nomeEsc = htmlspecialchars($aluno['nome'], ENT_QUOTES, 'UTF-8');
            $search = htmlspecialchars($aluno['nome'] . ' ' . $aluno['nomeColaboradorChamada'], ENT_QUOTES, 'UTF-8');

            $output .= "<div class='card chamada-student-item{$aluno['classeAniversario']}'
                            id='card_{$aluno['idMatricula']}_{$aluno['idTurma']}_{$aluno['idCurso']}_{$aluno['idAluno']}'
                            data-search='{$search}'
                            data-bs-toggle='tooltip'
                            data-bs-placement='top'
                            title='Matrícula: {$aluno['idMatricula']} | Código do aluno: {$aluno['idAluno']} | Curso: {$aluno['idCurso']} | Turma: {$aluno['idTurma']} | Dt. Mat.: " . htmlspecialchars($aluno['dataMatricula'], ENT_QUOTES, 'UTF-8') . "'
                            style='background-color: " . htmlspecialchars($aluno['corCard'], ENT_QUOTES, 'UTF-8') . ";'>";
            $output .= $aluno['badgeAniversario'];
            $output .= "<img src='" . htmlspecialchars($aluno['foto'], ENT_QUOTES, 'UTF-8') . "' alt='Foto_de_{$nomeEsc}'>";
            $output .= '<h4>' . $nomeEsc . " <a href='" . htmlspecialchars($aluno['linkFaltas'], ENT_QUOTES, 'UTF-8') . "' class='text-decoration-none js-faltas-link js-beneficiario-chamada-modal' data-aluno='" . htmlspecialchars($aluno['modalPayload'], ENT_QUOTES, 'UTF-8') . "'>({$aluno['textoFaltas']})</a></h4>";
            $output .= $aluno['infoAniversario'];
            $output .= renderizarBotoes($aluno['idMatricula'], $aluno['foto'], $aluno['nome'], 'T00');
            $output .= '</div>';
        }
    } else {
        $output = 'Nenhum registro encontrado.<br>';
    }

    return ['html' => $output, 'totalCards' => $totalCards];
}

function renderizarListaChamada(array $rows, $pdo, $dataSelecionada, $NNomeCurso, $NNomeTurma, $baseUrl = ''): array
{
    $alerta = chamadaAlertaCores($pdo);
    $totalCards = 0;
    $body = '';

    if (count($rows) > 0) {
        foreach ($rows as $row) {
            $totalCards++;
            $aluno = chamadaPrepararAluno($row, $dataSelecionada, $alerta, (string)$baseUrl, $NNomeCurso, $NNomeTurma);
            $nomeEsc = htmlspecialchars($aluno['nome'], ENT_QUOTES, 'UTF-8');
            $fotoEsc = htmlspecialchars($aluno['foto'], ENT_QUOTES, 'UTF-8');
            $search = htmlspecialchars($aluno['nome'] . ' ' . $aluno['nomeColaboradorChamada'], ENT_QUOTES, 'UTF-8');
            $aniversario = htmlspecialchars($aluno['aniversarioTexto'], ENT_QUOTES, 'UTF-8');
            $colaborador = htmlspecialchars($aluno['nomeColaboradorChamada'], ENT_QUOTES, 'UTF-8');
            $link = htmlspecialchars($aluno['linkFaltas'], ENT_QUOTES, 'UTF-8');
            $payload = htmlspecialchars($aluno['modalPayload'], ENT_QUOTES, 'UTF-8');

            $body .= "<tr class='chamada-student-item'
                            id='card_{$aluno['idMatricula']}_{$aluno['idTurma']}_{$aluno['idCurso']}_{$aluno['idAluno']}'
                            data-search='{$search}'>";
            $body .= "<td><img src='{$fotoEsc}' alt='Foto_de_{$nomeEsc}' class='chamada-list-foto'></td>";
            $body .= "<td class='chamada-list-nome'>{$nomeEsc}</td>";
            $body .= '<td>' . renderizarBotoes($aluno['idMatricula'], $aluno['foto'], $aluno['nome'], 'T00') . '</td>';
            $body .= "<td><a href='{$link}' class='text-decoration-none js-faltas-link js-beneficiario-chamada-modal' data-aluno='{$payload}'>({$aluno['textoFaltas']})</a></td>";
            $body .= "<td>{$aniversario}</td>";
            $body .= "<td class='js-colaborador-chamada'>{$colaborador}</td>";
            $body .= '</tr>';
        }
    } else {
        $body = "<tr><td colspan='6'>Nenhum registro encontrado.</td></tr>";
    }

    $output = "<div class='chamada-list-wrapper'>
        <table class='table table-sm table-striped align-middle chamada-list-table'>
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nome</th>
                    <th>Chamada</th>
                    <th>Faltas</th>
                    <th>Aniversário</th>
                    <th>Colaborador</th>
                </tr>
            </thead>
            <tbody>{$body}</tbody>
        </table>
    </div>";

    return ['html' => $output, 'totalCards' => $totalCards];
}

function turma_foto($pdo, $data, $dataSelecionada, $NNomeCurso, $NNomeTurma, $baseUrl = '', string $view = 'grid')
{
    $rows = buscarAlunosChamada($pdo, $dataSelecionada, $NNomeCurso, $NNomeTurma);
    if ($view === 'list') {
        return renderizarListaChamada($rows, $pdo, $dataSelecionada, $NNomeCurso, $NNomeTurma, $baseUrl);
    }
    return renderizarGridChamada($rows, $pdo, $dataSelecionada, $NNomeCurso, $NNomeTurma, $baseUrl);
}

function renderizarBotoes($idMatricula, $fotoaluno, $membro, $Obs)
{
    $fotoalunoEsc = htmlspecialchars((string)$fotoaluno, ENT_QUOTES, 'UTF-8');
    $membroEsc = htmlspecialchars((string)$membro, ENT_QUOTES, 'UTF-8');
    $obsEsc = htmlspecialchars((string)$Obs, ENT_QUOTES, 'UTF-8');

    return "<div class='button-container'>
                <button class='btn btnp' id='P-{$idMatricula}' onclick='alterarCor(this, \"verde\")'>P</button>
                <button class='btn btnp' id='F-{$idMatricula}' onclick='alterarCor(this, \"vermelho\")'>F</button>
                <button class='btn btnp' id='FJ-{$idMatricula}' onclick='alterarCor(this, \"amarelo\")'>FJ</button>
                <button type='button' class='btn' data-bs-toggle='modal' data-bs-target='#obsModal' data-idmatricula='{$idMatricula}' data-fotoaluno='{$fotoalunoEsc}' data-nomealuno='{$membroEsc}' data-observacoes='{$obsEsc}' id='Obs-{$idMatricula}'>Obs</button>
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
