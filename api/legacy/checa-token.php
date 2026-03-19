<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

require_once $BASE_para_PATH . '/api/legacy/funcoes.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    require $BASE_para_PATH . '/api/conectabd/conexao.php';
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    session_unset();
    session_destroy();
    header("Location: " . rtrim((string)$BASE_para_URL, '/') . "/login/?erro=" . urlencode('Falha de conexao. Faca login novamente.'));
    exit();
}

$token = $_SESSION['token'] ?? 'erro';
$mes_atual = substr(date('M'), 0, 3);

if (
    !isset($_SESSION['token']) ||
    !isset($_SESSION['IdPermissao']) ||
    substr((string)$token, -3) !== $mes_atual ||
    ((int)$token % 10) !== 0 ||
    !verificaHorarioPermissao($pdo, $_SESSION['IdPermissao'])
) {
    if (isset($_COOKIE['login_v43'])) {
        setcookie('login_v43', '', time() - 3600, '/');
        unset($_COOKIE['login_v43']);
    }

    session_unset();
    session_destroy();

    header("Location: " . rtrim((string)$BASE_para_URL, '/') . "/login/");
    exit();
}

$stmt = $pdo->prepare('SELECT NomePermissao, PaginasPermitidas FROM vwPermissaoUsuario WHERE IdColaborador = ?');
$stmt->execute([$_SESSION['Cod']]);
$dados = $stmt->fetch(PDO::FETCH_ASSOC);

$paginasPermitidasSessao = array_values(array_filter(array_map('trim', (array)($_SESSION['PaginasPermitidas'] ?? []))));
if ($dados) {
    $paginasAtuais = array_values(array_filter(array_map('trim', explode(',', (string)$dados['PaginasPermitidas']))));
    $tipoAtual = (string)$dados['NomePermissao'];

    $sessaoTipoDiferente = ($_SESSION['Tipo'] ?? '') !== $tipoAtual;
    $sessaoPaginasDiferente = json_encode($paginasPermitidasSessao) !== json_encode($paginasAtuais);

    if ($sessaoTipoDiferente || $sessaoPaginasDiferente) {
        $_SESSION['Tipo'] = $tipoAtual;
        $_SESSION['PaginasPermitidas'] = $paginasAtuais;
        $paginasPermitidasSessao = $paginasAtuais;
    }
}

$paginaAtual = basename($_SERVER['PHP_SELF'] ?? '');
$requestPath = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$basePathFromUrl = (string)(parse_url((string)$BASE_para_URL, PHP_URL_PATH) ?: '');
$basePathFromUrl = rtrim($basePathFromUrl, '/');
$relativePath = $requestPath;
if ($basePathFromUrl !== '' && str_starts_with($relativePath, $basePathFromUrl)) {
    $relativePath = substr($relativePath, strlen($basePathFromUrl));
}
$routePath = trim('/' . ltrim($relativePath, '/'), '/');
$routeSegment = $routePath === '' ? '' : basename($routePath);
$routeFirstSegment = $routePath === '' ? '' : explode('/', $routePath)[0];

$legacyRouteMap = [
    'dashboard' => ['index.php', 'index_legacy_dashboard.php'],
    'perfil' => ['formPerfil.php', 'perfilController.php'],
    'crm' => ['crm.php', 'crmController.php'],
    'atendimento' => ['index.php', 'crmController.php'],
    'chamada' => ['listChamada.php', 'listTipoChamada.php', 'listTotalChamada.php', 'savebanco.php'],
    'swot' => ['swotController.php', 'formSwot.php', 'avaliarSwot.php'],
    'feriados' => ['formFeriados.php', 'feriadosController.php'],
    'backup' => ['backup.php', 'configBackup.php'],
    'alertas' => ['alerta.php', 'cadAlerta.php'],
    'projetos' => ['formProjeto.php', 'projetoController.php'],
    'cursos' => ['formCurso.php', 'cadCurso.php', 'alteraCurso.php', 'excluirCurso.php', 'planejamentoCurso.php', 'planoCursos.php'],
    'plano-cursos' => ['planoCursos.php', 'formCurso.php', 'planejamentoCurso.php'],
    'turmas' => ['formTurma.php', 'cadTurma.php', 'alteraTurma.php', 'excluirTurma.php'],
    'matriculas' => ['formMatricula.php', 'cadMatricula.php', 'alteraMatricula.php', 'excluirMatricula.php', 'listagemMatriculas.php', 'listTurmasMat.php', 'resultadoMatriculas.php', 'gerarContrato.php', 'gerarContratosTurma.php', 'gerarContratosCurso.php'],
    'colaboradores' => ['formColaborador.php', 'colaboradorController.php', 'resetSenha.php'],
    'permissoes' => ['listPermissaoView.php', 'listPermissaoController.php', 'formPermissaoView.php', 'permissaoController.php', 'formPaginasView.php', 'formPaginas.php'],
    'beneficiarios' => ['formBeneficiario.php', 'beneficiarioController.php', 'listagemBeneficiarios.php', 'listagemSBenef.php', 'aniversariantes.php', 'aniversariantesController.php', 'consulta-cpf.php', 'avaliarVulnerabilidade.php', 'avaliarVulnerabilidadeStream.php'],
    'eventos' => ['formInscricao.php', 'cadInscrito.php', 'inscricaosucess.php', 'listagemInscritos.php', 'alteraInscrito.php', 'excluirInscrito.php', 'dados_inscritos.php', 'pdf_inscritos.php', 'verifica_email.php'],
    'relatorios' => ['relFrequencia.php', 'relCustomize.php', 'relatorioPresencaCursoTurma.php', 'relMatriculados.php', 'swotRel.php'],
    'perfil' => ['formPerfil.php', 'perfilController.php'],
    'configuracoes' => ['formConfig.php', 'salvarConfig.php'],
    'assinatura' => ['PDFupload.php', 'PDFpreview.php', 'PDFfinaliza.php', 'verPDF.php'],
];

$legacyRoutePathMap = [
    'beneficiarios/cadastro' => ['formBeneficiario.php', 'beneficiarioController.php'],
    'beneficiarios/lista' => ['listagemBeneficiarios.php'],
    'beneficiarios/dados' => ['listagemSBenef.php'],
    'beneficiarios/aniversariantes' => ['aniversariantes.php', 'aniversariantesController.php'],
    'beneficiarios/consulta-cpf' => ['consulta-cpf.php'],
    'cursos' => ['formCurso.php', 'planejamentoCurso.php'],
    'cursos/planejamento' => ['planejamentoCurso.php'],
    'plano-cursos' => ['planoCursos.php'],
    'turmas' => ['formTurma.php'],
    'matriculas' => ['listagemMatriculas.php'],
    'matriculas/realizar' => ['formMatricula.php', 'listTurmasMat.php', 'resultadoMatriculas.php'],
    'matriculas/contrato' => ['gerarContrato.php'],
    'matriculas/contratos/turma' => ['gerarContratosTurma.php'],
    'matriculas/contratos/curso' => ['gerarContratosCurso.php'],
    'eventos/inscricao' => ['formInscricao.php', 'cadInscrito.php'],
    'eventos/inscricao/sucesso' => ['inscricaosucess.php'],
    'eventos/inscritos' => ['listagemInscritos.php'],
    'eventos/inscritos/editar' => ['alteraInscrito.php'],
    'eventos/inscritos/excluir' => ['excluirInscrito.php'],
    'relatorios/frequencia' => ['relFrequencia.php'],
    'relatorios/personalizado' => ['relCustomize.php'],
    'relatorios/presenca-curso-turma' => ['relatorioPresencaCursoTurma.php'],
    'relatorios/matriculados' => ['relMatriculados.php'],
    'relatorios/swot' => ['swotRel.php'],
];

$candidatosPagina = array_values(array_unique(array_filter([
    $paginaAtual,
    $routePath !== '' ? $routePath : null,
    $routeSegment !== '' ? $routeSegment : null,
    $routeFirstSegment !== '' ? $routeFirstSegment : null,
    $routePath !== '' ? $routePath . '.php' : null,
    $routeSegment !== '' ? $routeSegment . '.php' : null,
    $routeFirstSegment !== '' ? $routeFirstSegment . '.php' : null,
])));
if ($routeSegment !== '' && isset($legacyRouteMap[$routeSegment])) {
    $candidatosPagina = array_values(array_unique(array_merge($candidatosPagina, $legacyRouteMap[$routeSegment])));
}
if ($routeFirstSegment !== '' && isset($legacyRouteMap[$routeFirstSegment])) {
    $candidatosPagina = array_values(array_unique(array_merge($candidatosPagina, $legacyRouteMap[$routeFirstSegment])));
}
if ($routePath !== '' && isset($legacyRoutePathMap[$routePath])) {
    $candidatosPagina = array_values(array_unique(array_merge($candidatosPagina, $legacyRoutePathMap[$routePath])));
}

$normalizarPagina = static function (string $value): array {
    $value = trim($value);
    if ($value === '') {
        return [];
    }
    $semQuery = (string)parse_url($value, PHP_URL_PATH);
    $semQuery = trim($semQuery);
    if ($semQuery === '') {
        return [];
    }
    $semQuery = str_replace('\\', '/', $semQuery);
    $semQuery = trim($semQuery, '/');
    $base = basename($semQuery);
    $baseNoExt = preg_replace('/\.php$/i', '', $base);
    $tokens = array_values(array_unique(array_filter([
        $semQuery,
        '/' . $semQuery,
        $base,
        $baseNoExt,
        $baseNoExt !== '' ? $baseNoExt . '.php' : null,
    ])));
    return $tokens;
};

$permissoesNormalizadas = [];
foreach ($paginasPermitidasSessao as $permitida) {
    foreach ($normalizarPagina((string)$permitida) as $token) {
        $permissoesNormalizadas[$token] = true;
    }
}

$temPermissao = false;
foreach ($candidatosPagina as $candidato) {
    foreach ($normalizarPagina((string)$candidato) as $token) {
        if (isset($permissoesNormalizadas[$token])) {
            $temPermissao = true;
            break 2;
        }
    }
}

if (!$temPermissao && (($_SESSION['Tipo'] ?? '') !== 'Superadministrador')) {
    echo "<script>alert('Suas credenciais nao dao acesso a essa funcionalidade.'); window.location.href = '" . rtrim((string)$BASE_para_URL, '/') . "/dashboard/';</script>";
    exit();
}

$FotoColaborador = $_SESSION['Foto'];
$NomeColaborador = $_SESSION['Nome'];
$DataUltimoAcesso = $_SESSION['ultimoAcessoData'];
