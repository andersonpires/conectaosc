<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_PATH = $runtime['base_path'];
$BASE_URL = $runtime['base_url'];
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}

// Verifica se as vari?f?????T?f??s?,?veis de sess?f?????T?f??s?,?o BASE_PATH e BASE_URL est?f?????T?f??s?,?o definidas
if (!isset($BASE_PATH) || !isset($BASE_URL)) {
    // Salva a URL atual para redirecionar o usu?f?????T?f??s?,?rio ap?s o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endere?f?????T?f??s?,?o atual
    header("Location: " . rtrim((string) ($BASE_URL ?? ''), '/') . "/login/?redirect=$redirect_url");
    exit(); // Garante que o c?f?????T?f??s?,?digo abaixo n?f?????T?f??s?,?o ser? executado
}

require_once $BASE_PATH . '/api/legacy/checa-token.php';
require_once __DIR__ . '/beneficiarioModel.php';
$beneficiariosListaUrl = rtrim((string) $BASE_URL, '/') . '/beneficiarios/lista';
$beneficiariosCadastroUrl = rtrim((string) $BASE_URL, '/') . '/beneficiarios/cadastro';

$acao = $_POST['acao'] ?? null;

/*
-------------------------------------------------------
 Normaliza novos campos (PCD em casa)
-------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Select Sim/N?f?????T?f??s?,?o (1 ou 0)
    $_POST['PCDEmCasa'] = isset($_POST['PCDEmCasa']) && $_POST['PCDEmCasa'] !== ''
        ? intval($_POST['PCDEmCasa'])
        : null;

    // Texto livre
    $_POST['DeficienciasCasa'] = $_POST['DeficienciasCasa'] ?? null;
}

/*
-------------------------------------------------------
 Trata requisi?f?????T?f??s?,??f?????T?f??s?,?o de DELETE (via POST)
-------------------------------------------------------
*/
if (isset($_POST['delete'])) {

    $id = intval($_POST['delete']);

    if ($id > 0) {
        require_once $BASE_PATH . '/api/repositories/BeneficiarioRepository.php';
        require_once $BASE_PATH . '/api/services/BeneficiarioService.php';
        $service = new \BackEnd\Services\BeneficiarioService(new \BackEnd\Repositories\BeneficiarioRepository());

        if ($service->softDelete($id)) {
            header("Location: {$beneficiariosListaUrl}?msg=" . urlencode("Benefici?f?????T?f??s?,?rio exclu?f?????T?f??s?,?do com sucesso!"));
            exit;
        } else {
            header("Location: {$beneficiariosListaUrl}?erro=" . urlencode("Erro ao excluir benefici?f?????T?f??s?,?rio."));
            exit;
        }
    } else {
        header("Location: {$beneficiariosListaUrl}?erro=" . urlencode("ID inv?f?????T?f??s?,?lido para exclus?f?????T?f??s?,?o."));
        exit;
    }
}


/*
-------------------------------------------------------
 Avaliar vulnerabilidade
-------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'avaliar_vulnerabilidade') {
    require_once __DIR__ . '/avaliarVulnerabilidade.php';
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
            header("Location: {$beneficiariosCadastroUrl}?erro=" . urlencode("Sem permiss?f?????T?f??s?,?o para salvar com Versatilis."));
            exit;
        }

        $foto = $_FILES['foto'] ?? null;
        $fotoAtual = $_POST['fotoAtual'] ?? 'padrao.jfif';
        $nome_arquivo = $fotoAtual;

        if ($foto && $foto['error'] === UPLOAD_ERR_OK && !empty($foto['tmp_name'])) {
            preg_match("/\.(png|jpg|jpeg)$/i", $foto["name"], $ext);
            if (!empty($ext)) {
                $nome_arquivo = md5(uniqid(time(), true)) . "." . $ext[1];
                $destino = $BASE_PATH . "/assets/img/fotos/" . $nome_arquivo;
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
                header("Location: {$beneficiariosListaUrl}?msg=" . urlencode($resultado['mensagem'] ?? 'Registro salvo com Versatilis.'));
                exit;
            }

            if ($tab) {
                $query['tab'] = $tab;
            }
            $query['msg'] = $resultado['mensagem'] ?? 'Registro salvo com Versatilis.';
            header("Location: {$beneficiariosCadastroUrl}?" . http_build_query($query));
            exit;
        }

        if ($tab) {
            $query['tab'] = $tab;
        }
        $query['erro'] = $resultado['mensagem'] ?? 'Erro ao salvar com Versatilis.';
        header("Location: {$beneficiariosCadastroUrl}?" . http_build_query($query));
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
                $destino = $BASE_PATH . "/assets/img/fotos/" . $nome_arquivo;
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
                    header("Location: {$beneficiariosListaUrl}?msg=" . urlencode("Registro atualizado com sucesso!"));
                    exit;
                }

                $query = ['cpf' => $cpf, 'msg' => "Registro atualizado com sucesso!"];
                if ($tab) {
                    $query['tab'] = $tab;
                }
                header("Location: {$beneficiariosCadastroUrl}?" . http_build_query($query));
                exit;
            }

            $query = ['id' => intval($_POST['IdUsuario']), 'erro' => "Erro ao atualizar registro."];
            if ($tab) {
                $query['tab'] = $tab;
            }
            header("Location: {$beneficiariosCadastroUrl}?" . http_build_query($query));
            exit;
        }

        // CREATE
        $idUsuario = BeneficiarioModel::create($_POST);
        if ($idUsuario) {
            $idsProjetosSelecionados = $_POST['projetos'] ?? [];
            BeneficiarioModel::salvarInteresses($idUsuario, $idsProjetosSelecionados);

            $cpf = preg_replace('/[^0-9]/', '', $_POST['CPF'] ?? '');
            if ($fecharCadastro) {
                header("Location: {$beneficiariosListaUrl}?msg=" . urlencode("Cadastro realizado com sucesso!"));
                exit;
            }

            $query = ['cpf' => $cpf, 'msg' => "Cadastro realizado com sucesso!"];
            if ($tab) {
                $query['tab'] = $tab;
            }
            header("Location: {$beneficiariosCadastroUrl}?" . http_build_query($query));
            exit;
        }

        $query = ['erro' => "Erro ao cadastrar registro."];
        if ($tab) {
            $query['tab'] = $tab;
        }
        header("Location: {$beneficiariosCadastroUrl}?" . http_build_query($query));
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
 Recupera par?metros CPF e ID
-------------------------------------------------------
*/
$cpf = isset($_GET['cpf']) ? preg_replace('/[^0-9]/', '', $_GET['cpf']) : null;
$id  = isset($_GET['id'])  ? intval($_GET['id']) : null;

/*
-------------------------------------------------------
 CASO N?f?????T?f?????,???O TENHA CPF NEM ID ? apenas abre o form vazio
-------------------------------------------------------
*/
if (!$cpf && !$id) {
    require __DIR__ . '/formBeneficiario.php';
    exit;
}

/*
-------------------------------------------------------
 Se veio CPF, mas inv?f?????T?f??s?,?lido, tenta usar ID
-------------------------------------------------------
*/
if ($cpf && strlen($cpf) !== 11) {

    // Se N?f?????T?f?????,???O tiver ID ? erro no CPF
    if (!$id) {
        header("Location: {$beneficiariosCadastroUrl}?erro=CPF inv?f?????T?f??s?,?lido");
        exit;
    }

    // CPF inv?f?????T?f??s?,?lido, mas ID existe ? ignora CPF e busca pelo ID
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
    // Modo edi??o
    foreach ($dados as $key => $value) {
        $_POST[$key] = $value;
    }
} else {
    // Modo cadastro (pr?-preenche CPF se informado)
    if ($cpf) {
        $_POST['CPF'] = $cpf;
        $cpfNaoEncontrado = true;
    }
}

/*
-------------------------------------------------------
 Chama o formul?rio (HTML est?f?????T?f??s?,? todo no form)
-------------------------------------------------------
*/
require __DIR__ . '/formBeneficiario.php';
exit;







