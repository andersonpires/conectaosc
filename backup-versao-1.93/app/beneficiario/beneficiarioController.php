<?php 
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

// Verifica se as variáveis de sessão BASE_PATH e BASE_URL estão definidas
if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    // Salva a URL atual para redirecionar o usuário após o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endereço atual
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url"); // Redireciona para o login com o endereço de volta via GET
    exit(); // Garante que o código abaixo não será executado
}

require_once $_SESSION['BASE_PATH'] . '/checa-token.php';
require_once 'beneficiarioModel.php';

$acao = $_POST['acao'] ?? null;

/*
-------------------------------------------------------
 Normaliza novos campos (PCD em casa)
-------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Select Sim/Não (1 ou 0)
    $_POST['PCDEmCasa'] = isset($_POST['PCDEmCasa']) && $_POST['PCDEmCasa'] !== ''
        ? intval($_POST['PCDEmCasa'])
        : null;

    // Texto livre
    $_POST['DeficienciasCasa'] = $_POST['DeficienciasCasa'] ?? null;
}

/*
-------------------------------------------------------
 Trata requisição de DELETE (via POST)
-------------------------------------------------------
*/
if (isset($_POST['delete'])) {

    $id = intval($_POST['delete']);

    if ($id > 0) {
        if (BeneficiarioModel::delete($id)) {
            header("Location: listagemSBenef.php?msg=" . urlencode("Beneficiário excluído com sucesso!"));
            exit;
        } else {
            header("Location: listagemSBenef.php?erro=" . urlencode("Erro ao excluir beneficiário."));
            exit;
        }
    } else {
        header("Location: listagemSBenef.php?erro=" . urlencode("ID inválido para exclusão."));
        exit;
    }
}


/*
-------------------------------------------------------
 Avaliar vulnerabilidade
-------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'avaliar_vulnerabilidade') {
    require_once 'avaliarVulnerabilidade.php';
    exit;
}

/*
-------------------------------------------------------
 TRATA O SALVAR
-------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab = $_POST['tab'] ?? '';
    $tabsValidos = ['inscricao', 'socio', 'medico', 'ipai', 'outros'];
    $tab = in_array($tab, $tabsValidos, true) ? $tab : null;
    $fecharCadastro = !empty($_POST['fechar']);

    if ($acao === 'salvar_versatilis') {
        $tipoPermissao = $_SESSION['Tipo'] ?? '';
        $podeUsarVersatilis = in_array($tipoPermissao, ['Versatilis', 'Geral', 'Administrador', 'Superadministrador'], true);
        if (!$podeUsarVersatilis) {
            header("Location: beneficiarioController.php?erro=" . urlencode("Sem permissão para salvar com Versatilis."));
            exit;
        }

        $foto = $_FILES['foto'] ?? null;
        $fotoAtual = $_POST['fotoAtual'] ?? 'padrao.jfif';
        $nome_arquivo = $fotoAtual;

        if ($foto && $foto['error'] === UPLOAD_ERR_OK && !empty($foto['tmp_name'])) {
            preg_match("/\.(png|jpg|jpeg)$/i", $foto["name"], $ext);
            if (!empty($ext)) {
                $nome_arquivo = md5(uniqid(time(), true)) . "." . $ext[1];
                $destino = $_SESSION['BASE_PATH'] . "/assets/img/fotos/" . $nome_arquivo;
                move_uploaded_file($foto['tmp_name'], $destino);
            }
        }

        $_POST['Foto'] = $nome_arquivo;

        $resultado = BeneficiarioModel::salvarComVersatilis($_POST);

        $cpf = preg_replace('/[^0-9]/', '', $_POST['CPF'] ?? '');
        $query = [];

        if (!empty($_POST['IdUsuario'])) {
            $query['id'] = intval($_POST['IdUsuario']);
        } elseif (!empty($cpf)) {
            $query['cpf'] = $cpf;
        }

        if (!empty($resultado['ok'])) {
            $idsProjetosSelecionados = $_POST['projetos'] ?? [];
            if (!empty($resultado['idUsuario'])) {
                BeneficiarioModel::salvarInteresses($resultado['idUsuario'], $idsProjetosSelecionados);
            }

            if ($fecharCadastro) {
                header("Location: listagemSBenef.php?msg=" . urlencode($resultado['mensagem'] ?? 'Registro salvo com Versatilis.'));
                exit;
            }

            if ($tab) {
                $query['tab'] = $tab;
            }
            $query['msg'] = $resultado['mensagem'] ?? 'Registro salvo com Versatilis.';
            header("Location: beneficiarioController.php?" . http_build_query($query));
            exit;
        }

        if ($tab) {
            $query['tab'] = $tab;
        }
        $query['erro'] = $resultado['mensagem'] ?? 'Erro ao salvar com Versatilis.';
        header("Location: beneficiarioController.php?" . http_build_query($query));
        exit;
    }

    if ($acao === 'salvar') {

        // Tratamento de upload da foto (MOVIDO do form antigo)
        $foto = $_FILES['foto'] ?? null;
        $fotoAtual = $_POST['fotoAtual'] ?? 'padrao.jfif';
        $nome_arquivo = $fotoAtual;

        if ($foto && $foto['error'] === UPLOAD_ERR_OK && !empty($foto['tmp_name'])) {
            preg_match("/\.(png|jpg|jpeg)$/i", $foto["name"], $ext);
            if (!empty($ext)) {
                $nome_arquivo = md5(uniqid(time(), true)) . "." . $ext[1];
                $destino = $_SESSION['BASE_PATH'] . "/assets/img/fotos/" . $nome_arquivo;
                move_uploaded_file($foto['tmp_name'], $destino);
            }
        }

        $_POST['Foto'] = $nome_arquivo;


        // UPDATE
        if (!empty($_POST['IdUsuario'])) {
            $sucesso = BeneficiarioModel::update($_POST);

            if ($sucesso) {
                $idsProjetosSelecionados = $_POST['projetos'] ?? [];
                BeneficiarioModel::salvarInteresses($_POST['IdUsuario'], $idsProjetosSelecionados);

                $cpf = preg_replace('/[^0-9]/', '', $_POST['CPF'] ?? '');
                if ($fecharCadastro) {
                    header("Location: listagemSBenef.php?msg=" . urlencode("Registro atualizado com sucesso!"));
                    exit;
                }

                $query = ['cpf' => $cpf, 'msg' => "Registro atualizado com sucesso!"];
                if ($tab) {
                    $query['tab'] = $tab;
                }
                header("Location: beneficiarioController.php?" . http_build_query($query));
                exit;
            }

            $query = ['id' => intval($_POST['IdUsuario']), 'erro' => "Erro ao atualizar registro."];
            if ($tab) {
                $query['tab'] = $tab;
            }
            header("Location: beneficiarioController.php?" . http_build_query($query));
            exit;
        }

        // CREATE
        $idUsuario = BeneficiarioModel::create($_POST);
        if ($idUsuario) {
            $idsProjetosSelecionados = $_POST['projetos'] ?? [];
            BeneficiarioModel::salvarInteresses($idUsuario, $idsProjetosSelecionados);

            $cpf = preg_replace('/[^0-9]/', '', $_POST['CPF'] ?? '');
            if ($fecharCadastro) {
                header("Location: listagemSBenef.php?msg=" . urlencode("Cadastro realizado com sucesso!"));
                exit;
            }

            $query = ['cpf' => $cpf, 'msg' => "Cadastro realizado com sucesso!"];
            if ($tab) {
                $query['tab'] = $tab;
            }
            header("Location: beneficiarioController.php?" . http_build_query($query));
            exit;
        }

        $query = ['erro' => "Erro ao cadastrar registro."];
        if ($tab) {
            $query['tab'] = $tab;
        }
        header("Location: beneficiarioController.php?" . http_build_query($query));
        exit;
    }
}

/*
-------------------------------------------------------
 Trata mensagens vindas via GET
-------------------------------------------------------
*/
if (isset($_GET['msg']))     $_POST['msg']  = urldecode($_GET['msg']);
if (isset($_GET['erro']))    $_POST['erro'] = urldecode($_GET['erro']);

/*
-------------------------------------------------------
 Recupera parâmetros CPF e ID
-------------------------------------------------------
*/
$cpf = isset($_GET['cpf']) ? preg_replace('/[^0-9]/', '', $_GET['cpf']) : null;
$id  = isset($_GET['id'])  ? intval($_GET['id']) : null;

/*
-------------------------------------------------------
 CASO NÃO TENHA CPF NEM ID → apenas abre o form vazio
-------------------------------------------------------
*/
if (!$cpf && !$id) {
    require 'formBeneficiario.php';
    exit;
}

/*
-------------------------------------------------------
 Se veio CPF, mas inválido, tenta usar ID
-------------------------------------------------------
*/
if ($cpf && strlen($cpf) !== 11) {

    // Se NÃO tiver ID → erro no CPF
    if (!$id) {
        header("Location: beneficiarioController.php?erro=CPF inválido");
        exit;
    }

    // CPF inválido, mas ID existe → ignora CPF e busca pelo ID
    $cpf = null;
}

/*
-------------------------------------------------------
 Busca aluno no banco (CPF tem prioridade)
-------------------------------------------------------
*/
$dados = BeneficiarioModel::getRead($cpf, $id);
$cpfNaoEncontrado = false;

/*
-------------------------------------------------------
 Preenche dados para o form
-------------------------------------------------------
*/
if ($dados) {
    // Modo edição
    foreach ($dados as $key => $value) {
        $_POST[$key] = $value;
    }
} else {
    // Modo cadastro (pré-preenche CPF se informado)
    if ($cpf) {
        $_POST['CPF'] = $cpf;
        $cpfNaoEncontrado = true;
    }
}

/*
-------------------------------------------------------
 Chama o formulário (HTML está todo no form)
-------------------------------------------------------
*/
require 'formBeneficiario.php';
exit;
