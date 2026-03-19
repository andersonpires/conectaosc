<?php
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
require_once $_SESSION['BASE_PATH'] . '/funcoes.php';

// Token recebido da sessão, se não estiver definido, definir como vazio
$token = isset($_SESSION['token']) ? $_SESSION['token'] : 'erro';

// Obter as 3 primeiras letras do mês atual
$mes_atual = substr(date("M"), 0, 3);

// Verificação de validade do token + horário permitido
if (
    !isset($_SESSION['token']) ||
    substr($token, -3) !== $mes_atual ||
    (int)$token % 10 !== 0 ||
    !verificaHorarioPermissao($pdo, $_SESSION['IdPermissao'])
) {
    if (isset($_COOKIE['login_v3'])) {
        setcookie('login_v3', '', time() - 3600, '/');
        unset($_COOKIE['login_v3']);
    }

    session_unset();
    session_destroy();

    header("Location: /conectaosc/login.php");
    exit();
}


// Checa se o usuário tem acesso à página atual
$paginaAtual = basename($_SERVER['PHP_SELF']);

$stmt = $pdo->prepare("SELECT NomePermissao, PaginasPermitidas FROM vwPermissaoUsuario WHERE IdColaborador = ?");
$stmt->execute([$_SESSION['Cod']]);
$dados = $stmt->fetch(PDO::FETCH_ASSOC);

if ($dados) {
    $paginasAtuais = explode(',', $dados['PaginasPermitidas']);
    $tipoAtual = $dados['NomePermissao'];

    $sessaoTipoDiferente = $_SESSION['Tipo'] !== $tipoAtual;
    $sessaoPaginasDiferente = json_encode($_SESSION['PaginasPermitidas']) !== json_encode($paginasAtuais);

    if ($sessaoTipoDiferente || $sessaoPaginasDiferente) {
        $_SESSION['Tipo'] = $tipoAtual;
        $_SESSION['PaginasPermitidas'] = $paginasAtuais;
    }
}

// Redireciona se a página atual não estiver permitida (exceto Superadmin que pode ver todas)
if (!in_array($paginaAtual, $_SESSION['PaginasPermitidas']) && $_SESSION['Tipo'] !== "Superadministrador") {
    echo "<script>alert('Suas credenciais não dão acesso à essa funcionalidade.'); window.location.href = '/conectaosc/index.php';</script>";
    exit();
}

// Dados úteis para exibir na interface
$FotoColaborador = $_SESSION['Foto'];
$NomeColaborador = $_SESSION['Nome'];
$DataUltimoAcesso = $_SESSION['ultimoAcessoData'];
