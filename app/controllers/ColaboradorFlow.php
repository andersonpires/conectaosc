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

        $colaboradoresRoute = rtrim($this->baseUrl, '/') . '/colaboradores';
        $acao = $_POST['acao'] ?? null;
        $idColaboradorAlt = isset($_SESSION['Cod']) ? (int) $_SESSION['Cod'] : null;
        $timeAlterado = date('Y-m-d H:i:s');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost($acao, $colaboradoresRoute, $idColaboradorAlt, $timeAlterado);
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
                $this->redirect("{$colaboradoresRoute}/?msg=" . urlencode('Usuario inativado com sucesso.'));
            }
            $this->redirect("{$colaboradoresRoute}/?erro=" . urlencode('Erro ao inativar usuario.'));
        }

        if ($acao === 'reativar') {
            $id = (int) ($_POST['IdColaborador'] ?? 0);
            if ($id > 0 && \ColaboradorModel::reativar($id, $idColaboradorAlt, $timeAlterado)) {
                $this->redirect("{$colaboradoresRoute}/?status=inativos&msg=" . urlencode('Usuario reativado com sucesso.'));
            }
            $this->redirect("{$colaboradoresRoute}/?status=inativos&erro=" . urlencode('Erro ao reativar usuario.'));
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
        $email = trim((string) ($_POST['Email'] ?? ''));
        $senha = (string) ($_POST['Senha'] ?? '');

        if ($nome === '' || $sobrenome === '' || $email === '' || $idPermissao <= 0) {
            $this->redirect("{$colaboradoresRoute}/?erro=" . urlencode('Preencha os campos obrigatorios.'));
        }

        $tipo = \ColaboradorModel::getTipoPermissao($idPermissao);
        if (!$tipo) {
            $this->redirect("{$colaboradoresRoute}/?erro=" . urlencode('Permissao invalida.'));
        }

        if ($id > 0) {
            $this->atualizarColaborador(
                $id,
                $nome,
                $sobrenome,
                $idPermissao,
                $whatsApp,
                $email,
                $senha,
                $tipo,
                $idColaboradorAlt,
                $timeAlterado,
                $colaboradoresRoute
            );
            return;
        }

        $this->cadastrarColaborador(
            $nome,
            $sobrenome,
            $cpf,
            $idPermissao,
            $whatsApp,
            $email,
            $senha,
            $tipo,
            $idColaboradorAlt,
            $timeAlterado,
            $colaboradoresRoute
        );
    }

    private function atualizarColaborador(
        int $id,
        string $nome,
        string $sobrenome,
        int $idPermissao,
        string $whatsApp,
        string $email,
        string $senha,
        string $tipo,
        ?int $idColaboradorAlt,
        string $timeAlterado,
        string $colaboradoresRoute
    ): void {
        $registro = \ColaboradorModel::getById($id);
        if (!$registro) {
            $this->redirect("{$colaboradoresRoute}/?erro=" . urlencode('Usuario nao encontrado para edicao.'));
        }

        $fotoAtual = $registro['Foto'] ?? 'padrao.jfif';
        $foto = $this->tratarUploadFoto($_FILES['foto'] ?? null, $fotoAtual);
        $senhaAtualizar = null;
        if ($senha !== '' && $senha !== 'Apenas o usuario pode alterar a senha') {
            $senhaAtualizar = password_hash($senha, PASSWORD_DEFAULT);
        }

        $profissionalSaude = isset($_POST['profissional_saude']) && $_POST['profissional_saude'] === '1' ? 1 : 0;
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
            'Email' => $email,
            'profissional_saude' => $profissionalSaude,
            'especialidade_id' => $especialidadeId,
            'Senha' => $senhaAtualizar,
            'Tipo' => $tipo,
            'IdColaboradorAlt' => $idColaboradorAlt,
            'TimeAlterado' => $timeAlterado,
        ];

        if (!\ColaboradorModel::update($dados)) {
            $this->redirect("{$colaboradoresRoute}/?erro=" . urlencode('Nao foi possivel alterar o registro.'));
        }

        if (!\EspecialidadeProfissionalModel::replaceForColaborador($id, $especialidadesInput)) {
            $this->redirect("{$colaboradoresRoute}/?erro=" . urlencode('Registro salvo, mas houve erro ao salvar especialidades.'));
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
        \enviarEmail($nome, $email, $mensagem);
        $this->redirect("{$colaboradoresRoute}/?msg=" . urlencode('Registro alterado com sucesso.'));
    }

    private function cadastrarColaborador(
        string $nome,
        string $sobrenome,
        string $cpf,
        int $idPermissao,
        string $whatsApp,
        string $email,
        string $senha,
        string $tipo,
        ?int $idColaboradorAlt,
        string $timeAlterado,
        string $colaboradoresRoute
    ): void {
        if ($cpf === '' || $senha === '') {
            $this->redirect("{$colaboradoresRoute}/?erro=" . urlencode('CPF e senha sao obrigatorios.'));
        }

        $foto = $this->tratarUploadFoto($_FILES['foto'] ?? null, 'padrao.jfif');
        $hashSenha = password_hash($senha, PASSWORD_DEFAULT);
        $profissionalSaude = isset($_POST['profissional_saude']) && $_POST['profissional_saude'] === '1' ? 1 : 0;
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
            'Email' => $email,
            'profissional_saude' => $profissionalSaude,
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
            $this->redirect("{$colaboradoresRoute}/?erro=" . urlencode('Erro ao cadastrar registro.'));
        }

        if (!\EspecialidadeProfissionalModel::replaceForColaborador((int) $novoId, $especialidadesInput)) {
            $this->redirect("{$colaboradoresRoute}/?erro=" . urlencode('Registro salvo, mas houve erro ao salvar especialidades.'));
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
        \enviarEmail($nome, $email, $mensagem);

        $this->redirect("{$colaboradoresRoute}/?msg=" . urlencode('Colaborador cadastrado com sucesso.'));
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

    private function redirect(string $location): never
    {
        header('Location: ' . $location);
        exit;
    }
}
