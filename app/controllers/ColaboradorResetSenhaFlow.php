<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class ColaboradorResetSenhaFlow
{
    public function __construct(
        private readonly string $basePath,
        private readonly string $baseUrl
    ) {
    }

    public function handle(): void
    {
        $resetRoute = rtrim($this->baseUrl, '/') . '/colaboradores/reset-senha';

        require_once $this->basePath . '/api/legacy/checa-token.php';
        require_once $this->basePath . '/api/conectabd/conexao.php';

        if (isset($_POST['IdColaborador']) && $_POST['IdColaborador'] !== '') {
            $idColaborador = (int) $_POST['IdColaborador'];
            $idColaboradorAlt = isset($_SESSION['Cod']) ? (int) $_SESSION['Cod'] : null;
            $timeAlterado = date('Y-m-d H:i:s');

            global $pdo;
            $stmt = $pdo->prepare('SELECT CPF FROM tbUser WHERE IdColaborador = ?');
            $stmt->execute([$idColaborador]);
            $colaborador = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$colaborador) {
                $this->redirect("{$resetRoute}/?erro=" . urlencode('Colaborador não encontrado.'));
            }

            $cpfLimpo = preg_replace('/\D/', '', (string) $colaborador['CPF']);
            $novaSenha = substr((string) $cpfLimpo, 0, 6);
            $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

            $update = $pdo->prepare('UPDATE tbUser SET Senha = ?, IdColaboradorAlt = ?, TimeAlterado = ? WHERE IdColaborador = ?');
            if ($update->execute([$senhaHash, $idColaboradorAlt, $timeAlterado, $idColaborador])) {
                $this->redirect("{$resetRoute}/?msg=" . urlencode('Senha redefinida com sucesso!'));
            }

            $this->redirect("{$resetRoute}/?erro=" . urlencode('Erro ao atualizar a senha.'));
        }

        $BASE_para_PATH = $this->basePath;
        $BASE_para_URL = $this->baseUrl;
        $RESET_ROUTE = $resetRoute;
        require $this->basePath . '/app/views/colaborador/formResetSenha.php';
        exit;
    }

    private function redirect(string $location): never
    {
        header('Location: ' . $location);
        exit;
    }
}

