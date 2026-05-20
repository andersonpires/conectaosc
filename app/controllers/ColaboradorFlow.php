<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

use Throwable;

final class ColaboradorFlow
{
    public function __construct(
        private readonly string $basePath,
        private readonly string $baseUrl
    ) {
    }

    public function handle(): void
    {
        $this->bootstrapDependencies();

        $colaboradoresRoute = $this->buildInternalRoute('/colaboradores');
        $acao = $_POST['acao'] ?? null;
        $idColaboradorAlt = isset($_SESSION['Cod']) ? (int) $_SESSION['Cod'] : null;
        $timeAlterado = date('Y-m-d H:i:s');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->handlePost($acao, $colaboradoresRoute, $idColaboradorAlt, $timeAlterado);
            } catch (Throwable $e) {
                error_log('[colaboradores.handlePost] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
                $mensagemErro = 'Erro interno ao salvar colaborador.';
                if (\bootstrap_is_debug()) {
                    $mensagemErro = $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine();
                }
                $this->redirect("{$colaboradoresRoute}?erro=" . urlencode($mensagemErro));
            }
        }

        if (isset($_GET['msg'])) {
            $_POST['msg'] = urldecode((string) $_GET['msg']);
        }
        if (isset($_GET['erro'])) {
            $_POST['erro'] = urldecode((string) $_GET['erro']);
        }

        $idEdicao = 0;
        if (isset($_POST['IdColaborador'])) {
            $idEdicao = (int) $_POST['IdColaborador'];
        } elseif (isset($_GET['id'])) {
            $idEdicao = (int) $_GET['id'];
        }

        $colaboradorEdicao = null;
        if ($idEdicao > 0) {
            $colaboradorEdicao = \ColaboradorModel::getById($idEdicao);
        }

        $statusFiltro = $_GET['status'] ?? 'ativos';
        if ($statusFiltro === 'inativos') {
            $listaColaboradores = \ColaboradorModel::listByStatus(0);
        } else {
            $statusFiltro = 'ativos';
            $listaColaboradores = \ColaboradorModel::listByStatus(1);
        }
        $permissoes = \ColaboradorModel::listPermissoes();

        $especialidades = [];
        try {
            global $pdo;
            $stmtEsp = $pdo->query("SELECT id, nome FROM tb_especialidade WHERE ativo = 1 ORDER BY nome");
            if ($stmtEsp) {
                $especialidades = $stmtEsp->fetchAll(\PDO::FETCH_ASSOC);
            }
        } catch (Throwable) {
            $especialidades = [];
        }

        $especialidadesProfissionais = [];
        if ($colaboradorEdicao && !empty($colaboradorEdicao['IdColaborador'])) {
            try {
                $especialidadesProfissionais = \EspecialidadeProfissionalModel::listByColaborador((int) $colaboradorEdicao['IdColaborador']);
            } catch (Throwable) {
                $especialidadesProfissionais = [];
            }
        }

        require $this->basePath . '/app/views/colaborador/formColaborador.php';
        exit;
    }

    private function bootstrapDependencies(): void
    {
        require_once $this->basePath . '/api/conectabd/conexao.php';
        require_once $this->basePath . '/app/views/colaborador/funcoes.php';
        require_once $this->basePath . '/app/models/colaborador/colaboradorModel.php';
        require_once $this->basePath . '/app/models/colaborador/especialidadeProfissionalModel.php';
    }

    private function handlePost(?string $acao, string $colaboradoresRoute, ?int $idColaboradorAlt, string $timeAlterado): void
    {
        if ($acao === 'excluir') {
            $id = (int) ($_POST['IdColaborador'] ?? 0);
            if ($id > 0 && \ColaboradorModel::inativar($id, $idColaboradorAlt, $timeAlterado)) {
                $this->redirect("{$colaboradoresRoute}?msg=" . urlencode('Usuario inativado com sucesso.'));
            }
            $this->redirect("{$colaboradoresRoute}?erro=" . urlencode('Erro ao inativar usuario.'));
        }

        if ($acao === 'reativar') {
            $id = (int) ($_POST['IdColaborador'] ?? 0);
            if ($id > 0 && \ColaboradorModel::reativar($id, $idColaboradorAlt, $timeAlterado)) {
                $this->redirect("{$colaboradoresRoute}?status=inativos&msg=" . urlencode('Usuario reativado com sucesso.'));
            }
            $this->redirect("{$colaboradoresRoute}?status=inativos&erro=" . urlencode('Erro ao reativar usuario.'));
        }

        if ($acao !== 'salvar') {
            return;
        }

        $id = (int) ($_POST['IdColaborador'] ?? 0);
        $nome = trim((string) ($_POST['Nome'] ?? ''));
        $sobrenome = trim((string) ($_POST['Sobrenome'] ?? ''));
        $cpf = trim((string) ($_POST['CPF'] ?? ''));
        $idPermissao = (int) ($_POST['Permissao'] ?? 0);
        $whatsApp = trim((string) ($_POST['WhatsApp'] ?? ''));
        $nascimento = $this->normalizarNascimento($_POST['Nascimento'] ?? null);
        $cargo = trim((string) ($_POST['Cargo'] ?? ''));
        $email = trim((string) ($_POST['Email'] ?? ''));
        $senha = trim((string) ($_POST['Senha'] ?? ''));
        if ($nome === '' || $sobrenome === '' || $email === '' || $idPermissao <= 0) {
            $this->redirect("{$colaboradoresRoute}?erro=" . urlencode('Preencha os campos obrigatorios.'));
        }

        $tipo = \ColaboradorModel::getTipoPermissao($idPermissao);
        if (!$tipo) {
            $this->redirect("{$colaboradoresRoute}?erro=" . urlencode('Permissao invalida.'));
        }

        $podeDefinirClinica = $this->currentUserIsAdmin();

        if ($id > 0) {
            $this->atualizarColaborador(
                $id,
                $nome,
                $sobrenome,
                $idPermissao,
                $whatsApp,
                $nascimento,
                $cargo,
                $email,
                $tipo,
                $idColaboradorAlt,
                $timeAlterado,
                $colaboradoresRoute,
                $podeDefinirClinica
            );
            return;
        }

        $this->cadastrarColaborador(
            $nome,
            $sobrenome,
            $cpf,
            $idPermissao,
            $whatsApp,
            $nascimento,
            $cargo,
            $email,
            $senha,
            $tipo,
            $idColaboradorAlt,
            $timeAlterado,
            $colaboradoresRoute,
            $podeDefinirClinica
        );
    }

    private function atualizarColaborador(
        int $id,
        string $nome,
        string $sobrenome,
        int $idPermissao,
        string $whatsApp,
        ?string $nascimento,
        string $cargo,
        string $email,
        string $tipo,
        ?int $idColaboradorAlt,
        string $timeAlterado,
        string $colaboradoresRoute,
        bool $podeDefinirClinica
    ): void {
        $registro = \ColaboradorModel::getById($id);
        if (!$registro) {
            $this->redirect("{$colaboradoresRoute}?erro=" . urlencode('Usuario nao encontrado para edicao.'));
        }

        $fotoAtual = $registro['Foto'] ?? 'padrao.jfif';
        $foto = $this->tratarUploadFoto($_FILES['foto'] ?? null, $fotoAtual);
        [$profissionalSaude, $licencaAdministrativa] = $this->resolveClinicaFlags($_POST['habilitacao_clinica'] ?? null, $registro, $podeDefinirClinica);
        $especialidadesInput = $this->normalizarEspecialidadesInput($_POST['especialidades'] ?? []);
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
            'Nascimento' => $nascimento,
            'Cargo' => $cargo !== '' ? $cargo : null,
            'Email' => $email,
            'profissional_saude' => $profissionalSaude,
            'licenca_administrativa' => $licencaAdministrativa,
            'especialidade_id' => $especialidadeId,
            'Tipo' => $tipo,
            'IdColaboradorAlt' => $idColaboradorAlt,
            'TimeAlterado' => $timeAlterado,
        ];

        if (!\ColaboradorModel::update($dados)) {
            $this->redirect("{$colaboradoresRoute}?erro=" . urlencode('Nao foi possivel alterar o registro.'));
        }

        if (!\EspecialidadeProfissionalModel::replaceForColaborador($id, $especialidadesInput)) {
            $this->redirect("{$colaboradoresRoute}?erro=" . urlencode('Registro salvo, mas houve erro ao salvar especialidades.'));
        }

        if ($idColaboradorAlt !== null && $id === $idColaboradorAlt) {
            $_SESSION['profissional_saude'] = $profissionalSaude;
            $_SESSION['licenca_administrativa'] = $licencaAdministrativa;
        }

        $mensagem = "
            <!DOCTYPE html>
            <html lang='pt-br'>
            <head><meta charset='UTF-8'><title>Cadastro</title></head>
            <body>
                <p>Ola, {$nome}.</p>
                <p>Seu perfil acabou de ser alterado no <strong>Sistema Iteva de Gestao de OSCs</strong>.</p>
                <p>Caso nao tenha sido voce, verifique ou troque sua senha.</p>
            </body>
            </html>
        ";
        $this->safeEnviarEmail($nome, $email, $mensagem);
        $this->redirect("{$colaboradoresRoute}?msg=" . urlencode('Registro alterado com sucesso.'));
    }

    private function cadastrarColaborador(
        string $nome,
        string $sobrenome,
        string $cpf,
        int $idPermissao,
        string $whatsApp,
        ?string $nascimento,
        string $cargo,
        string $email,
        string $senha,
        string $tipo,
        ?int $idColaboradorAlt,
        string $timeAlterado,
        string $colaboradoresRoute,
        bool $podeDefinirClinica
    ): void {
        if ($cpf === '' || $senha === '') {
            $this->redirect("{$colaboradoresRoute}?erro=" . urlencode('CPF e senha sao obrigatorios.'));
        }

        $foto = $this->tratarUploadFoto($_FILES['foto'] ?? null, 'padrao.jfif');
        $hashSenha = password_hash($senha, PASSWORD_DEFAULT);
        [$profissionalSaude, $licencaAdministrativa] = $this->resolveClinicaFlags($_POST['habilitacao_clinica'] ?? null, null, $podeDefinirClinica);
        $especialidadesInput = $this->normalizarEspecialidadesInput($_POST['especialidades'] ?? []);
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
            'Nascimento' => $nascimento,
            'Cargo' => $cargo !== '' ? $cargo : null,
            'Email' => $email,
            'profissional_saude' => $profissionalSaude,
            'licenca_administrativa' => $licencaAdministrativa,
            'especialidade_id' => $especialidadeId,
            'Senha' => $hashSenha,
            'Habilitado' => 1,
            'Email_confere' => 0,
            'Tipo' => $tipo,
            'IdColaboradorAlt' => $idColaboradorAlt,
            'TimeAlterado' => $timeAlterado,
        ];

        $novoId = \ColaboradorModel::create($dados);
        if (!$novoId) {
            $this->redirect("{$colaboradoresRoute}?erro=" . urlencode('Erro ao cadastrar registro.'));
        }

        if (!\EspecialidadeProfissionalModel::replaceForColaborador((int) $novoId, $especialidadesInput)) {
            $this->redirect("{$colaboradoresRoute}?erro=" . urlencode('Registro salvo, mas houve erro ao salvar especialidades.'));
        }

        $confirma = base64_encode(json_encode([
            'Email' => $email,
            'token' => $senha,
        ]));
        $baseUrl = rtrim($this->baseUrl, '/');
        $url = $baseUrl . '/login/?confirma=' . urlencode($confirma);

        $mensagem = "
            <!DOCTYPE html>
            <html lang='pt-br'>
            <head><meta charset='UTF-8'><title>Cadastro</title></head>
            <body>
                <p>Ola, {$nome}.</p>
                <p>Voce acabou de ser cadastrado no <strong>Sistema Iteva de Gestao de OSCs</strong>.</p>
                <p>Para confirmar seu cadastro clique em <a href='{$url}' target='_blank'>{$url}</a>.</p>
            </body>
            </html>
        ";
        $this->safeEnviarEmail($nome, $email, $mensagem);

        $this->redirect("{$colaboradoresRoute}?msg=" . urlencode('Colaborador cadastrado com sucesso.'));
    }

    private function tratarUploadFoto(mixed $arquivo, ?string $fotoAtual = null): string
    {
        if (!is_array($arquivo) || !isset($arquivo['error'])) {
            return $fotoAtual ?: 'padrao.jfif';
        }
        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            return $fotoAtual ?: 'padrao.jfif';
        }
        preg_match("/\.(png|jpg|jpeg){1}$/i", (string) $arquivo['name'], $ext);
        if (!$ext) {
            return $fotoAtual ?: 'padrao.jfif';
        }

        $nomeArquivo = md5(uniqid((string) time(), true)) . '.' . $ext[1];
        $dirFotos = $this->resolveFotosDir();
        if (!is_dir($dirFotos)) {
            @mkdir($dirFotos, 0777, true);
        }
        $caminhoArquivo = $dirFotos . DIRECTORY_SEPARATOR . $nomeArquivo;
        if (move_uploaded_file((string) $arquivo['tmp_name'], $caminhoArquivo)) {
            if ($fotoAtual && $fotoAtual !== 'padrao.jfif') {
                $caminhoAntigo = $dirFotos . DIRECTORY_SEPARATOR . $fotoAtual;
                if (is_file($caminhoAntigo)) {
                    @unlink($caminhoAntigo);
                }
            }
            return $nomeArquivo;
        }
        return $fotoAtual ?: 'padrao.jfif';
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

        return rtrim($this->basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'fotos';
    }

    private function normalizarEspecialidadesInput(mixed $input): array
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
                'NumeroRegistro' => $registro !== '' ? $registro : null,
            ];
        }
        return $itens;
    }

    private function normalizarNascimento(mixed $valor): ?string
    {
        $raw = trim((string) $valor);
        if ($raw === '') {
            return null;
        }

        $dt = \DateTime::createFromFormat('Y-m-d', $raw);
        if (!$dt || $dt->format('Y-m-d') !== $raw) {
            return null;
        }

        return $raw;
    }

    private function currentUserIsAdmin(): bool
    {
        $tipo = (string) ($_SESSION['Tipo'] ?? '');
        $permissao = (int) ($_SESSION['IdPermissao'] ?? 0);
        return $permissao === 4 || in_array($tipo, ['Administrador', 'Superadministrador'], true);
    }

    private function resolveClinicaFlags(mixed $habilitacaoClinica, ?array $registroAtual, bool $podeDefinirClinica): array
    {
        if (!$podeDefinirClinica) {
            return [
                (int) ($registroAtual['profissional_saude'] ?? 0),
                (int) ($registroAtual['licenca_administrativa'] ?? 0),
            ];
        }

        $role = trim((string) $habilitacaoClinica);
        return match ($role) {
            'profissional_saude' => [1, 0],
            'licenca_administrativa' => [0, 1],
            default => [0, 0],
        };
    }

    private function buildInternalRoute(string $suffix): string
    {
        $baseUrl = trim($this->baseUrl);
        if ($baseUrl === '') {
            return $suffix;
        }

        $path = parse_url($baseUrl, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = $baseUrl;
        }

        return rtrim($path, '/') . $suffix;
    }

    private function safeEnviarEmail(string $nome, string $email, string $mensagem): void
    {
        try {
            \enviarEmail($nome, $email, $mensagem);
        } catch (Throwable $e) {
            error_log('[colaboradores.email] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    private function redirect(string $location): never
    {
        header('Location: ' . $location);
        exit;
    }
}
