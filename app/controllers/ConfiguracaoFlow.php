<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class ConfiguracaoFlow
{
    public function __construct(
        private readonly string $basePath,
        private readonly string $baseUrl
    ) {
    }

    public function handle(): void
    {
        $BASE_para_PATH = $this->basePath;
        $BASE_para_URL = $this->baseUrl;

        if ($BASE_para_PATH === '' || $BASE_para_URL === '') {
            $redirectUrl = urlencode((string) ($_SERVER['REQUEST_URI'] ?? '/'));
            header('Location: ' . rtrim($BASE_para_URL, '/') . '/login/?redirect=' . $redirectUrl);
            exit;
        }

        require_once $this->basePath . '/api/legacy/checa-token.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once $this->basePath . '/api/repositories/ConfigRepository.php';
            require_once $this->basePath . '/api/services/ConfigService.php';

            try {
                $service = new \BackEnd\Services\ConfigService(new \BackEnd\Repositories\ConfigRepository());
                $service->updateConfig($_POST);
                header('Location: ' . rtrim($BASE_para_URL, '/') . '/configuracoes?msg=' . urlencode('Configuracoes atualizadas com sucesso!'));
                exit;
            } catch (\Throwable $e) {
                header('Location: ' . rtrim($BASE_para_URL, '/') . '/configuracoes?erro=' . urlencode('Erro ao atualizar configuracoes: ' . $e->getMessage()));
                exit;
            }
        }

        require $this->basePath . '/app/views/config/formConfigView.php';
        exit;
    }
}
