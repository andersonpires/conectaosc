<?php
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url");
    exit();
}

require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
require_once 'funcoes.php';
require_once 'colaboradorModel.php';
require_once 'especialidadeProfissionalModel.php';

function tratarUploadFoto($arquivo, $fotoAtual = null)
{
    if (!isset($arquivo) || !isset($arquivo['error'])) {
        return $fotoAtual ?: 'padrao.jfif';
    }

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        return $fotoAtual ?: 'padrao.jfif';
    }

    preg_match("/\.(png|jpg|jpeg){1}$/i", $arquivo["name"], $ext);
    if (!$ext) {
        return $fotoAtual ?: 'padrao.jfif';
    }

    $nomeArquivo = md5(uniqid(time())) . "." . $ext[1];
    $caminhoArquivo = $_SESSION['BASE_PATH'] . "/assets/img/fotos/" . $nomeArquivo;

    if (move_uploaded_file($arquivo['tmp_name'], $caminhoArquivo)) {
        if ($fotoAtual && $fotoAtual !== 'padrao.jfif') {
            $caminhoAntigo = $_SESSION['BASE_PATH'] . "/assets/img/fotos/" . $fotoAtual;
            if (file_exists($caminhoAntigo)) {
                unlink($caminhoAntigo);
            }
        }
        return $nomeArquivo;
    }

    return $fotoAtual ?: 'padrao.jfif';
}

$acao = $_POST['acao'] ?? null;
$idColaboradorAlt = isset($_SESSION['Cod']) ? intval($_SESSION['Cod']) : null;
$timeAlterado = date('Y-m-d H:i:s');

function normalizarEspecialidadesInput($input): array
{
    if (!is_array($input)) {
        return [];
    }

    $itens = [];
    foreach ($input as $item) {
        if (!is_array($item)) {
            continue;
        }

        $especialidadeId = isset($item['especialidade_id']) && $item['especialidade_id'] !== '' ? (int) $item['especialidade_id'] : null;
        $conselho = trim((string) ($item['conselho'] ?? ''));
        $uf = strtoupper(trim((string) ($item['uf'] ?? '')));
        $registro = trim((string) ($item['registro'] ?? ''));

        if ($especialidadeId === null && $conselho === '' && $uf === '' && $registro === '') {
            continue;
        }

        $itens[] = [
            'especialidade_id' => $especialidadeId,
            'Conselho' => $conselho !== '' ? $conselho : null,
            'UF' => $uf !== '' ? $uf : null,
            'NumeroRegistro' => $registro !== '' ? $registro : null
        ];
    }

    return $itens;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($acao === 'excluir') {
        $id = intval($_POST['IdColaborador'] ?? 0);
        if ($id > 0 && ColaboradorModel::inativar($id, $idColaboradorAlt, $timeAlterado)) {
            header("Location: colaboradorController.php?msg=" . urlencode("Usuario inativado com sucesso."));
            exit;
        }

        header("Location: colaboradorController.php?erro=" . urlencode("Erro ao inativar usuario."));
        exit;
    }

    if ($acao === 'reativar') {
        $id = intval($_POST['IdColaborador'] ?? 0);
        if ($id > 0 && ColaboradorModel::reativar($id, $idColaboradorAlt, $timeAlterado)) {
            header("Location: colaboradorController.php?status=inativos&msg=" . urlencode("Usuario reativado com sucesso."));
            exit;
        }

        header("Location: colaboradorController.php?status=inativos&erro=" . urlencode("Erro ao reativar usuario."));
        exit;
    }

    if ($acao === 'salvar') {
        $id = intval($_POST['IdColaborador'] ?? 0);
        $nome = trim((string) ($_POST['Nome'] ?? ''));
        $sobrenome = trim((string) ($_POST['Sobrenome'] ?? ''));
        $cpf = trim((string) ($_POST['CPF'] ?? ''));
        $idPermissao = intval($_POST['Permissao'] ?? 0);
        $whatsApp = trim((string) ($_POST['WhatsApp'] ?? ''));
        $email = trim((string) ($_POST['Email'] ?? ''));
        $senha = (string) ($_POST['Senha'] ?? '');

        if ($nome === '' || $sobrenome === '' || $email === '' || $idPermissao <= 0) {
            header("Location: colaboradorController.php?erro=" . urlencode("Preencha os campos obrigatorios."));
            exit;
        }

        $tipo = ColaboradorModel::getTipoPermissao($idPermissao);
        if (!$tipo) {
            header("Location: colaboradorController.php?erro=" . urlencode("Permissao invalida."));
            exit;
        }

        if ($id > 0) {
            $registro = ColaboradorModel::getById($id);
            if (!$registro) {
                header("Location: colaboradorController.php?erro=" . urlencode("Usuario nao encontrado para edicao."));
                exit;
            }

            $fotoAtual = $registro['Foto'] ?? 'padrao.jfif';
            $foto = tratarUploadFoto($_FILES['foto'] ?? null, $fotoAtual);
            $senhaAtualizar = null;

            if ($senha !== '' && $senha !== 'Apenas o usuario pode alterar a senha') {
                $senhaAtualizar = password_hash($senha, PASSWORD_DEFAULT);
            }

            $profissionalSaude = isset($_POST['profissional_saude']) && $_POST['profissional_saude'] === '1' ? 1 : 0;
            $especialidadesInput = normalizarEspecialidadesInput($_POST['especialidades'] ?? []);
            if ($profissionalSaude !== 1) {
                $especialidadesInput = [];
            }
            $especialidadeId = $especialidadesInput[0]['especialidade_id'] ?? null;
            $dados = [
                'IdColaborador' => $id,
                'Foto' => $foto,
                'Nome' => $nome,
                'Sobrenome' => $sobrenome,
                'IdPermissao' => $idPermissao,
                'WhatsApp' => $whatsApp,
                'Email' => $email,
                'profissional_saude' => $profissionalSaude,
                'especialidade_id' => $especialidadeId,
                'Senha' => $senhaAtualizar,
                'Tipo' => $tipo,
                'IdColaboradorAlt' => $idColaboradorAlt,
                'TimeAlterado' => $timeAlterado
            ];

            if (ColaboradorModel::update($dados)) {
                if (!EspecialidadeProfissionalModel::replaceForColaborador($id, $especialidadesInput)) {
                    header("Location: colaboradorController.php?erro=" . urlencode("Registro salvo, mas houve erro ao salvar especialidades."));
                    exit;
                }
                $mensagem = "
                    <!DOCTYPE html>
                    <html lang='pt-br'>
                    <head>
                        <meta charset='UTF-8'>
                        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                        <title>Cadastro no Sistema ITEVA de Gestao de OSCs</title>
                        <style>
                            body { font-family: Arial, sans-serif; color: #333; }
                            .header { color: #fff; background-color: #18385c; padding: 10px; text-align: center; }
                            .content { margin-top: 20px; padding: 20px; font-family: 'ember regular'; font-size: 16px; }
                            .footer { margin-top: 20px; padding: 10px; background-color: #18385c; color:#fff; text-align: center; font-weight: bold; }
                            .chamada { font-family: 'ember bold'; font-size: 22px; font-weight: bold; }
                        </style>
                    </head>
                    <body>
                        <div class='header'>
                            <img src='https://iteva.com.br/matricula/imagens/logo.png' id=ITEVALogo alt=ITEVA class='align-middle' width='110px'>
                        </div>
                        <div class='content'>
                            <p class='chamada'>Ola, {$nome}.</p>
                            <p>Seu perfil acabou de ser alterado no <strong>Sistema Iteva de Gestao de OSCs</strong>.</p>
                            <p>Caso nao tenha sido voce, verifique ou troque sua senha.</p>
                        </div>
                        <div class='footer'>
                            ITEVA - Sistema de Gestao de OSCs - Todos os direitos reservados.
                        </div>
                    </body>
                    </html>
                ";

                enviarEmail($nome, $email, $mensagem);
                header("Location: colaboradorController.php?msg=" . urlencode("Registro alterado com sucesso."));
                exit;
            }

            header("Location: colaboradorController.php?erro=" . urlencode("Nao foi possivel alterar o registro."));
            exit;
        }

        if ($cpf === '' || $senha === '') {
            header("Location: colaboradorController.php?erro=" . urlencode("CPF e senha sao obrigatorios."));
            exit;
        }

        $foto = tratarUploadFoto($_FILES['foto'] ?? null, 'padrao.jfif');
        $hashSenha = password_hash($senha, PASSWORD_DEFAULT);
        $profissionalSaude = isset($_POST['profissional_saude']) && $_POST['profissional_saude'] === '1' ? 1 : 0;
        $especialidadesInput = normalizarEspecialidadesInput($_POST['especialidades'] ?? []);
        if ($profissionalSaude !== 1) {
            $especialidadesInput = [];
        }
        $especialidadeId = $especialidadesInput[0]['especialidade_id'] ?? null;
        $dados = [
            'Foto' => $foto,
            'Nome' => $nome,
            'Sobrenome' => $sobrenome,
            'CPF' => $cpf,
            'IdPermissao' => $idPermissao,
            'WhatsApp' => $whatsApp,
            'Email' => $email,
            'profissional_saude' => $profissionalSaude,
            'especialidade_id' => $especialidadeId,
            'Senha' => $hashSenha,
            'Habilitado' => 1,
            'Email_confere' => 0,
            'Tipo' => $tipo,
            'IdColaboradorAlt' => $idColaboradorAlt,
            'TimeAlterado' => $timeAlterado
        ];

        $novoId = ColaboradorModel::create($dados);
        if ($novoId) {
            if (!EspecialidadeProfissionalModel::replaceForColaborador((int) $novoId, $especialidadesInput)) {
                header("Location: colaboradorController.php?erro=" . urlencode("Registro salvo, mas houve erro ao salvar especialidades."));
                exit;
            }
            $confirma = base64_encode(json_encode([
                'Email' => $email,
                'token' => $senha
            ]));
            $baseUrl = rtrim((string) ($_SESSION['BASE_URL'] ?? ''), '/');
            $url = $baseUrl !== '' ? $baseUrl . "/login.php?confirma=" . urlencode($confirma) : "login.php?confirma=" . urlencode($confirma);

            $mensagem = "
                <!DOCTYPE html>
                <html lang='en'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Cadastro no Sistema ITEVA de Gestao de OSCs</title>
                    <style>
                        body { font-family: Arial, sans-serif; color: #333; }
                        .header { color: #fff; background-color: #18385c; padding: 10px; text-align: center; }
                        .content { margin-top: 20px; padding: 20px; font-family: 'ember regular'; font-size: 16px; }
                        .footer { margin-top: 20px; padding: 10px; background-color: #18385c; color:#fff; text-align: center; font-weight: bold; }
                        .chamada { font-family: 'ember bold'; font-size: 22px; font-weight: bold; }
                    </style>
                </head>
                <body>
                    <div class='header'>
                        <img src='https://iteva.com.br/conectaosc/assets/img/sistema/logo.png' id=ITEVALogo alt=ITEVA class='align-middle' width='110px'>
                    </div>
                    <div class='content'>
                        <p class='chamada'>Ola, {$nome}.</p>
                        <p>Voce acabou de ser cadastrado no <strong>Sistema Iteva de Gestao de OSCs</strong>.</p>
                        <p>Para confirmar seu cadastro clique em <a href='{$url}' target='_blank'><strong>{$url}</strong></a> e faca seu login, utilizando seu e-mail e senha. A <strong>sua senha padrao sao os 6 primeiros digitos do seu CPF</strong>. Substitua a sua senha posteriormente.</p>
                        <p>Caso nao tenha sido voce, desconsidere este e-mail.</p>
                    </div>
                    <div class='footer'>
                        ITEVA - Sistema de Gestao de OSCs - Todos os direitos reservados.
                    </div>
                </body>
                </html>
            ";

            enviarEmail($nome, $email, $mensagem);
            header("Location: colaboradorController.php?msg=" . urlencode("Colaborador cadastrado com sucesso."));
            exit;
        }

        header("Location: colaboradorController.php?erro=" . urlencode("Erro ao cadastrar registro."));
        exit;
    }
}

if (isset($_GET['msg'])) $_POST['msg'] = urldecode($_GET['msg']);
if (isset($_GET['erro'])) $_POST['erro'] = urldecode($_GET['erro']);

$idEdicao = 0;
if (isset($_POST['IdColaborador'])) {
    $idEdicao = intval($_POST['IdColaborador']);
} elseif (isset($_GET['id'])) {
    $idEdicao = intval($_GET['id']);
}

$colaboradorEdicao = null;
if ($idEdicao > 0) {
    $colaboradorEdicao = ColaboradorModel::getById($idEdicao);
}

$statusFiltro = $_GET['status'] ?? 'ativos';
if ($statusFiltro === 'inativos') {
    $listaColaboradores = ColaboradorModel::listByStatus(0);
} else {
    $statusFiltro = 'ativos';
    $listaColaboradores = ColaboradorModel::listByStatus(1);
}
$permissoes = ColaboradorModel::listPermissoes();

$especialidades = [];
try {
    $stmtEsp = $pdo->query("SELECT id, nome FROM tb_especialidade WHERE ativo = 1 ORDER BY nome");
    if ($stmtEsp) $especialidades = $stmtEsp->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // tb_especialidade pode não existir ainda
}

$especialidadesProfissionais = [];
if ($colaboradorEdicao && !empty($colaboradorEdicao['IdColaborador'])) {
    try {
        $especialidadesProfissionais = EspecialidadeProfissionalModel::listByColaborador((int) $colaboradorEdicao['IdColaborador']);
    } catch (Throwable $e) {
        $especialidadesProfissionais = [];
    }
}

require 'formColaborador.php';
exit;
