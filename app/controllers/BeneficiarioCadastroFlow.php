<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class BeneficiarioCadastroFlow
{
    public function __construct(
        private readonly string $basePath,
        private readonly string $baseUrl
    ) {
    }

    private ?array $pendingFotoUpload = null;

    public function handle(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.gc_maxlifetime', '86400');
        }

        require_once $this->basePath . '/api/legacy/checa-token.php';
        require_once $this->basePath . '/app/models/BeneficiarioModel.php';

        $beneficiariosListaUrl = rtrim($this->baseUrl, '/') . '/beneficiarios/lista';
        $beneficiariosCadastroUrl = rtrim($this->baseUrl, '/') . '/beneficiarios/cadastro';
        $acao = $_POST['acao'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST['PCDEmCasa'] = isset($_POST['PCDEmCasa']) && $_POST['PCDEmCasa'] !== ''
                ? (int) $_POST['PCDEmCasa']
                : null;
            $_POST['DeficienciasCasa'] = $_POST['DeficienciasCasa'] ?? null;
        }

        if (isset($_POST['delete'])) {
            $id = (int) $_POST['delete'];
            if ($id > 0) {
                require_once $this->basePath . '/api/repositories/BeneficiarioRepository.php';
                require_once $this->basePath . '/api/services/BeneficiarioService.php';
                $service = new \BackEnd\Services\BeneficiarioService(new \BackEnd\Repositories\BeneficiarioRepository());
                if ($service->softDelete($id)) {
                    header("Location: {$beneficiariosListaUrl}?msg=" . urlencode('Beneficiário excluído com sucesso!'));
                    exit;
                }
                header("Location: {$beneficiariosListaUrl}?erro=" . urlencode('Erro ao excluir beneficiário.'));
                exit;
            }
            header("Location: {$beneficiariosListaUrl}?erro=" . urlencode('ID inválido para exclusão.'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'avaliar_vulnerabilidade') {
            require $this->basePath . '/app/views/beneficiario/avaliarVulnerabilidade.php';
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tab = $_POST['tab'] ?? '';
            $tabsValidos = ['inscricao', 'socio', 'vulnerabilidade', 'medico', 'ipai', 'outros'];
            $tab = in_array($tab, $tabsValidos, true) ? $tab : null;
            $fecharCadastro = !empty($_POST['fechar']);

            if ($acao === 'salvar_versatilis') {
                $tipoPermissao = $_SESSION['Tipo'] ?? '';
                $podeUsarVersatilis = in_array($tipoPermissao, ['Versatilis', 'Geral', 'Administrador', 'Superadministrador'], true);
                if (!$podeUsarVersatilis) {
                    header("Location: {$beneficiariosCadastroUrl}?erro=" . urlencode('Sem permissão para salvar com Versatilis.'));
                    exit;
                }

                $this->processUploadFoto();
                $resultado = \BeneficiarioModel::salvarComVersatilis($_POST);
                if (!empty($resultado['ok']) && !empty($resultado['idUsuario']) && (($resultado['tipo'] ?? '') === 'create')) {
                    $this->finalizePendingFotoUpload((int) $resultado['idUsuario']);
                }
                $cpf = preg_replace('/[^0-9]/', '', $_POST['CPF'] ?? '');
                $query = [];
                if (!empty($_POST['IdUsuario'])) {
                    $query['id'] = (int) $_POST['IdUsuario'];
                } elseif (!empty($cpf)) {
                    $query['cpf'] = $cpf;
                }

                if (!empty($resultado['ok'])) {
                    $idsProjetosSelecionados = $_POST['projetos'] ?? [];
                    if (!empty($resultado['idUsuario'])) {
                        \BeneficiarioModel::salvarInteresses($resultado['idUsuario'], $idsProjetosSelecionados);
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
                $this->processUploadFoto();

                $erroCriancaSemCpf = $this->validarCriancaSemCpf($_POST);
                if ($erroCriancaSemCpf !== null) {
                    $_POST['erro'] = $erroCriancaSemCpf;
                    $_POST['modo'] = 'crianca';
                    if ($tab) {
                        $_GET['tab'] = $tab;
                    }
                    require $this->basePath . '/app/views/beneficiario/formBeneficiario.php';
                    exit;
                }

                $this->prepararDadosCriancaSemCpf($_POST);

                if (!empty($_POST['IdUsuario'])) {
                    $sucesso = \BeneficiarioModel::update($_POST);
                    if ($sucesso) {
                        $idsProjetosSelecionados = $_POST['projetos'] ?? [];
                        \BeneficiarioModel::salvarInteresses($_POST['IdUsuario'], $idsProjetosSelecionados);
                        $cpf = $this->cpfSomenteDigitos($_POST['CPF'] ?? '');

                        if ($fecharCadastro) {
                            header("Location: {$beneficiariosListaUrl}?msg=" . urlencode('Registro atualizado com sucesso!'));
                            exit;
                        }

                        $query = $cpf !== ''
                            ? ['cpf' => $cpf]
                            : ['id' => (int)$_POST['IdUsuario'], 'modo' => 'crianca'];
                        $query['msg'] = 'Registro atualizado com sucesso!';
                        if ($tab) {
                            $query['tab'] = $tab;
                        }
                        header("Location: {$beneficiariosCadastroUrl}?" . http_build_query($query));
                        exit;
                    }

                    $query = ['id' => (int) $_POST['IdUsuario'], 'erro' => 'Erro ao atualizar registro.'];
                    if ($this->postIndicaCriancaSemCpf($_POST)) {
                        $query['modo'] = 'crianca';
                    }
                    if ($tab) {
                        $query['tab'] = $tab;
                    }
                    header("Location: {$beneficiariosCadastroUrl}?" . http_build_query($query));
                    exit;
                }

                $idUsuario = \BeneficiarioModel::create($_POST);
                if ($idUsuario) {
                    if ($this->pendingFotoUpload !== null) {
                        $this->finalizePendingFotoUpload($idUsuario);
                    }
                    $idsProjetosSelecionados = $_POST['projetos'] ?? [];
                    \BeneficiarioModel::salvarInteresses($idUsuario, $idsProjetosSelecionados);
                    $cpf = $this->cpfSomenteDigitos($_POST['CPF'] ?? '');

                    if ($fecharCadastro) {
                        header("Location: {$beneficiariosListaUrl}?msg=" . urlencode('Cadastro realizado com sucesso!'));
                        exit;
                    }

                    $query = $cpf !== ''
                        ? ['cpf' => $cpf]
                        : ['id' => $idUsuario, 'modo' => 'crianca'];
                    $query['msg'] = 'Cadastro realizado com sucesso!';
                    if ($tab) {
                        $query['tab'] = $tab;
                    }
                    header("Location: {$beneficiariosCadastroUrl}?" . http_build_query($query));
                    exit;
                }

                $query = ['erro' => 'Erro ao cadastrar registro.'];
                if ($this->postIndicaCriancaSemCpf($_POST)) {
                    $query['modo'] = 'crianca';
                }
                if ($tab) {
                    $query['tab'] = $tab;
                }
                header("Location: {$beneficiariosCadastroUrl}?" . http_build_query($query));
                exit;
            }
        }

        if (isset($_GET['msg'])) {
            $_POST['msg'] = urldecode((string) $_GET['msg']);
        }
        if (isset($_GET['erro'])) {
            $_POST['erro'] = urldecode((string) $_GET['erro']);
        }

        $cpf = isset($_GET['cpf']) ? preg_replace('/[^0-9]/', '', (string) $_GET['cpf']) : null;
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

        if (!$cpf && !$id) {
            if (($_GET['modo'] ?? '') === 'crianca') {
                $_POST['modo'] = 'crianca';
            }
            require $this->basePath . '/app/views/beneficiario/formBeneficiario.php';
            exit;
        }

        if ($cpf && strlen($cpf) !== 11) {
            if (!$id) {
                header("Location: {$beneficiariosCadastroUrl}?erro=CPF inválido");
                exit;
            }
            $cpf = null;
        }

        $dados = \BeneficiarioModel::getRead($cpf, $id);
        $cpfNaoEncontrado = false;

        if ($dados) {
            $cpfBanco = $this->cpfSomenteDigitos($dados['CPF'] ?? '');
            if ($cpfBanco === '') {
                $_POST['modo'] = 'crianca';
            }

            foreach ($dados as $key => $value) {
                $_POST[$key] = $value;
            }
        } elseif ($cpf) {
            $_POST['CPF'] = $cpf;
            $cpfNaoEncontrado = true;
        }

        require $this->basePath . '/app/views/beneficiario/formBeneficiario.php';
        exit;
    }

    private function cpfSomenteDigitos(?string $valor): string
    {
        return preg_replace('/\D/', '', (string)$valor);
    }

    private function postIndicaCriancaSemCpf(array $dados): bool
    {
        $modo = (string)($dados['modo'] ?? '');
        $cpf = $this->cpfSomenteDigitos($dados['CPF'] ?? '');
        return $modo === 'crianca' || $cpf === '';
    }

    private function prepararDadosCriancaSemCpf(array &$dados): void
    {
        if (!$this->postIndicaCriancaSemCpf($dados)) {
            return;
        }

        $dados['modo'] = 'crianca';
        $dados['CPF'] = null;
    }

    private function validarCriancaSemCpf(array $dados): ?string
    {
        if (!$this->postIndicaCriancaSemCpf($dados)) {
            return null;
        }

        $nomeResp = trim((string)($dados['NomeResp1'] ?? ''));
        $parentesco = trim((string)($dados['Parentesco'] ?? ''));
        $cpfResp = $this->cpfSomenteDigitos($dados['CpfResp1'] ?? '');
        $whatsappResp = preg_replace('/\D/', '', (string)($dados['WhatsAppResp1'] ?? ''));

        if ($nomeResp === '') {
            return 'Informe o nome do responsável.';
        }
        if ($parentesco === '') {
            return 'Informe o parentesco do responsável.';
        }
        if (strlen($cpfResp) !== 11) {
            return 'Informe um CPF válido para o responsável.';
        }
        if ($whatsappResp === '') {
            return 'Informe o WhatsApp do responsável.';
        }

        return null;
    }

    private function processUploadFoto(): void
    {
        $foto = $_FILES['foto'] ?? null;
        $fotoAtual = $_POST['fotoAtual'] ?? 'padrao.jfif';
        $nomeArquivo = $fotoAtual;

        if ($foto && $foto['error'] === UPLOAD_ERR_OK && !empty($foto['tmp_name'])) {
            preg_match('/\.(png|jpg|jpeg)$/i', (string) $foto['name'], $ext);
            if (!empty($ext)) {
                $extension = strtolower($ext[1]);
                if (!empty($_POST['IdUsuario'])) {
                    $idUsuario = (int) $_POST['IdUsuario'];
                    $nomeArquivo = $this->buildFotoFileName('aluno', $idUsuario, $extension);
                    $destinoDir = $this->resolveFotosDir();
                    if (!is_dir($destinoDir)) {
                        @mkdir($destinoDir, 0777, true);
                    }
                    $destino = $destinoDir . DIRECTORY_SEPARATOR . $nomeArquivo;
                    move_uploaded_file((string) $foto['tmp_name'], $destino);
                    $this->deleteOldFoto($fotoAtual, $nomeArquivo, $destinoDir);
                } else {
                    $this->pendingFotoUpload = [
                        'tmp_name' => (string) $foto['tmp_name'],
                        'ext' => $extension,
                        'fotoAtual' => $fotoAtual,
                    ];
                }
            }
        }

        $_POST['Foto'] = $nomeArquivo;
    }

    private function finalizePendingFotoUpload(int $idUsuario): void
    {
        if ($this->pendingFotoUpload === null) {
            return;
        }

        $extension = $this->pendingFotoUpload['ext'];
        $fotoAtual = $this->pendingFotoUpload['fotoAtual'];
        $nomeArquivo = $this->buildFotoFileName('aluno', $idUsuario, $extension);
        $destinoDir = $this->resolveFotosDir();
        if (!is_dir($destinoDir)) {
            @mkdir($destinoDir, 0777, true);
        }
        $destino = $destinoDir . DIRECTORY_SEPARATOR . $nomeArquivo;
        move_uploaded_file($this->pendingFotoUpload['tmp_name'], $destino);
        $this->deleteOldFoto($fotoAtual, $nomeArquivo, $destinoDir);
        $_POST['Foto'] = $nomeArquivo;
        \BeneficiarioModel::update(['IdUsuario' => $idUsuario, 'Foto' => $nomeArquivo]);
        $this->pendingFotoUpload = null;
    }

    private function buildFotoFileName(string $prefix, int $id, string $ext): string
    {
        return sprintf('%s_%d.%s', $prefix, $id, $ext);
    }

    private function deleteOldFoto(string $oldFoto, string $newFoto, string $dirFotos): void
    {
        $oldFoto = trim((string) $oldFoto);
        if ($oldFoto === '' || $oldFoto === 'padrao.jfif' || $oldFoto === $newFoto) {
            return;
        }

        $oldFile = $dirFotos . DIRECTORY_SEPARATOR . basename($oldFoto);
        if (is_file($oldFile)) {
            @unlink($oldFile);
        }
    }

    private function resolveFotosDir(): string
    {
        $runtimeFile = $this->basePath . '/bootstrap/runtime.php';
        if (is_file($runtimeFile)) {
            $runtime = require $runtimeFile;
            if (!empty($runtime['assets_img_path'])) {
                return rtrim((string) $runtime['assets_img_path'], '/\\') . DIRECTORY_SEPARATOR . 'fotos';
            }
        }

        return rtrim($this->basePath, '/\\') . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'fotos';
    }
}
