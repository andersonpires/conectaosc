<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class ProjetoFlow
{
    public function __construct(
        private readonly string $basePath,
        private readonly string $baseUrl
    ) {
    }

    public function handle(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.gc_maxlifetime', '86400');
        }

        require_once $this->basePath . '/app/models/projeto/projetoModel.php';

        $projetosRoute = rtrim($this->baseUrl, '/') . '/projetos';
        $acao = $_POST['acao'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($acao === 'excluir') {
                $id = (int) ($_POST['IdProjeto'] ?? 0);
                $projeto = $id ? \ProjetoModel::getById($id) : null;

                if ($id && $projeto && \ProjetoModel::delete($id)) {
                    if (!empty($projeto['LogoProjeto'])) {
                        $this->apagarLogoSeExistir((string) $projeto['LogoProjeto']);
                    }
                    header("Location: {$projetosRoute}/?msg=" . urlencode('Projeto excluÃ­do com sucesso!'));
                    exit;
                }

                header("Location: {$projetosRoute}/?erro=" . urlencode('Erro ao excluir projeto.'));
                exit;
            }

            if ($acao === 'salvar') {
                $id = (int) ($_POST['IdProjeto'] ?? 0);
                $logoAtual = (string) ($_POST['LogoAtual'] ?? '');
                $upload = $_FILES['LogoProjeto'] ?? null;

                $resultadoLogo = $this->processarUploadLogo($upload, $logoAtual);
                if (is_array($resultadoLogo) && isset($resultadoLogo['erro'])) {
                    header("Location: {$projetosRoute}/?erro=" . urlencode((string) $resultadoLogo['erro']));
                    exit;
                }

                $dados = [
                    'IdProjeto' => $id,
                    'NomeProjeto' => $_POST['NomeProjeto'] ?? '',
                    'LogoProjeto' => $resultadoLogo,
                    'TermosContrato' => $_POST['TermosContrato'] ?? '',
                ];

                if ($id > 0) {
                    if (\ProjetoModel::update($dados)) {
                        header("Location: {$projetosRoute}/?id={$id}&msg=" . urlencode('Projeto atualizado com sucesso!'));
                        exit;
                    }
                    header("Location: {$projetosRoute}/?id={$id}&erro=" . urlencode('Erro ao atualizar projeto.'));
                    exit;
                }

                if (\ProjetoModel::create($dados)) {
                    header("Location: {$projetosRoute}/?msg=" . urlencode('Projeto cadastrado com sucesso!'));
                    exit;
                }

                header("Location: {$projetosRoute}/?erro=" . urlencode('Erro ao cadastrar projeto.'));
                exit;
            }
        }

        if (isset($_GET['msg'])) {
            $_POST['msg'] = urldecode((string) $_GET['msg']);
        }
        if (isset($_GET['erro'])) {
            $_POST['erro'] = urldecode((string) $_GET['erro']);
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $projeto = $id ? \ProjetoModel::getById($id) : null;
        if ($projeto) {
            foreach ($projeto as $key => $value) {
                $_POST[$key] = $value;
            }
        }

        $listaProjetos = \ProjetoModel::listAll();

        $BASE_para_PATH = $this->basePath;
        $BASE_para_URL = $this->baseUrl;
        $ASSETS_IMG_URL = $this->resolveAssetsImgUrl();
        require $this->basePath . '/app/views/projeto/formProjeto.php';
        exit;
    }

    private function gerarNomeLogoJpg(): string
    {
        $milis = (int) ((microtime(true) - floor(microtime(true))) * 1000);
        return sprintf('%s%03d.jpg', date('YmdHis'), $milis);
    }

    private function apagarLogoSeExistir(string $logo): void
    {
        $logo = trim($logo);
        if ($logo === '') {
            return;
        }

        $caminho = $this->resolveLogosDir() . DIRECTORY_SEPARATOR . basename($logo);
        if (is_file($caminho)) {
            @unlink($caminho);
        }
    }

    private function processarUploadLogo(mixed $arquivo, string $logoAtual = ''): string|array
    {
        if (!is_array($arquivo) || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($arquivo['tmp_name'])) {
            return $logoAtual;
        }

        if (($arquivo['size'] ?? 0) > 500 * 1024) {
            return ['erro' => 'A logo deve ter no mÃ¡ximo 500KB.'];
        }

        $ext = strtolower(pathinfo((string) ($arquivo['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext !== 'jpg' && $ext !== 'jpeg') {
            return ['erro' => 'A logo deve estar em formato JPG.'];
        }

        $info = @getimagesize((string) $arquivo['tmp_name']);
        if (!$info || ($info[2] ?? null) !== IMAGETYPE_JPEG) {
            return ['erro' => 'Arquivo JPG invÃ¡lido.'];
        }

        $destinoDir = $this->resolveLogosDir();
        if (!is_dir($destinoDir)) {
            @mkdir($destinoDir, 0777, true);
        }

        $novoNome = $this->gerarNomeLogoJpg();
        $destino = rtrim($destinoDir, '/\\') . DIRECTORY_SEPARATOR . $novoNome;
        if (!move_uploaded_file((string) $arquivo['tmp_name'], $destino)) {
            return ['erro' => 'Falha ao salvar a logo enviada.'];
        }

        if ($logoAtual !== '' && $logoAtual !== $novoNome) {
            $this->apagarLogoSeExistir($logoAtual);
        }

        return $novoNome;
    }

    private function resolveLogosDir(): string
    {
        $runtimeFile = $this->basePath . '/bootstrap/runtime.php';
        if (is_file($runtimeFile)) {
            $runtime = require $runtimeFile;
            if (!empty($runtime['assets_img_path'])) {
                return rtrim((string) $runtime['assets_img_path'], '/\\') . DIRECTORY_SEPARATOR . 'logos';
            }
        }

        return rtrim($this->basePath, '/\\') . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'logos';
    }

    private function resolveAssetsImgUrl(): string
    {
        $runtimeFile = $this->basePath . '/bootstrap/runtime.php';
        if (is_file($runtimeFile)) {
            $runtime = require $runtimeFile;
            if (!empty($runtime['assets_img_url'])) {
                return rtrim((string) $runtime['assets_img_url'], '/');
            }
        }

        return rtrim($this->baseUrl, '/') . '/assets/img';
    }
}

