<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

use Throwable;

final class PerfilFlow
{
    public function __construct(
        private readonly string $basePath,
        private readonly string $baseUrl
    ) {
    }

    public function handle(): void
    {
        $perfilRoute = rtrim($this->baseUrl, '/') . '/perfil';

        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.gc_maxlifetime', '86400');
        }

        require_once $this->basePath . '/api/legacy/checa-token.php';
        require_once $this->basePath . '/api/conectabd/conexao.php';
        require_once $this->basePath . '/app/models/colaborador/especialidadeProfissionalModel.php';
        require_once $this->basePath . '/app/models/perfil/perfilModel.php';

        $authCookieName = bootstrap_auth_cookie_name($this->basePath);

        $authSessionKeys = [
            'token',
            'Cod',
            'Foto',
            'Nome',
            'Sobrenome',
            'Tipo',
            'IdPermissao',
            'PaginasPermitidas',
            'ultimoAcessoData',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idColaborador = isset($_POST['IdColaborador']) ? (int) $_POST['IdColaborador'] : 0;
            $profissionalSaude = isset($_POST['profissional_saude']) && $_POST['profissional_saude'] === '1' ? 1 : 0;
            $especialidadesInput = $this->normalizarEspecialidadesInput($_POST['especialidades'] ?? []);
            if ($profissionalSaude !== 1) {
                $especialidadesInput = [];
            }
            $_POST['especialidade_id'] = $especialidadesInput[0]['especialidade_id'] ?? null;

            $ok = \PerfilModel::update($_POST, $_FILES);
            $okEspecialidades = true;
            if ($idColaborador > 0) {
                $okEspecialidades = \EspecialidadeProfissionalModel::replaceForColaborador($idColaborador, $especialidadesInput);
            }

            if ($ok && $okEspecialidades) {
                $resultado = 'Registro%20alterado%20com%20sucesso';
                $loginUrl = rtrim($this->baseUrl, '/') . "/login/?msg={$resultado}";
                foreach ($authSessionKeys as $key) {
                    unset($_SESSION[$key]);
                }
                setcookie($authCookieName, '', time() - 3600, '/');
                setcookie($authCookieName, '', time() - 3600, '/', '', false, false);
                header("Location: {$loginUrl}");
                exit;
            }

            $resultado = 'N%C3%A3o%20foi%20poss%C3%ADvel%20alterar%20o%20registro!';
            header("Location: {$perfilRoute}/?erro={$resultado}");
            exit;
        }

        if (isset($_GET['msg'])) {
            $_POST['msg'] = urldecode((string) $_GET['msg']);
        }
        if (isset($_GET['erro'])) {
            $_POST['erro'] = urldecode((string) $_GET['erro']);
        }

        $IdColaborador = (int) ($_SESSION['Cod'] ?? 0);
        $colaborador = \PerfilModel::getById($IdColaborador);

        $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

        $especialidades = [];
        try {
            global $pdo;
            $stmtEsp = $pdo->query('SELECT id, nome FROM tb_especialidade WHERE ativo = 1 ORDER BY nome');
            if ($stmtEsp) {
                $especialidades = $stmtEsp->fetchAll(\PDO::FETCH_ASSOC);
            }
        } catch (Throwable) {
            $especialidades = [];
        }

        $especialidadesProfissionais = [];
        try {
            $especialidadesProfissionais = \EspecialidadeProfissionalModel::listByColaborador($IdColaborador);
        } catch (Throwable) {
            $especialidadesProfissionais = [];
        }

        $BASE_para_PATH = $this->basePath;
        $BASE_para_URL = $this->baseUrl;
        require $this->basePath . '/app/views/perfil/formPerfil.php';
        exit;
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
}
