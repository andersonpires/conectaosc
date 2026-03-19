<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

use BackEnd\Repositories\EventoRepository;
use BackEnd\Services\EventoService;
use Throwable;

final class EventoCadastroFlow
{
    public function __construct(
        private readonly string $basePath,
        private readonly string $baseUrl
    ) {
    }

    public function handle(): void
    {
        require_once $this->basePath . '/api/repositories/EventoRepository.php';
        require_once $this->basePath . '/api/services/EventoService.php';

        $base = rtrim($this->baseUrl, '/');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $base . '/eventos/inscricao/?erro=' . urlencode('Método inválido'));
            exit;
        }

        try {
            $service = new EventoService(new EventoRepository());
            $service->create($_POST);
            header('Location: ' . $base . '/eventos/inscricao/sucesso/');
            exit;
        } catch (Throwable $e) {
            header('Location: ' . $base . '/eventos/inscricao/?erro=' . urlencode('Erro ao cadastrar: ' . $e->getMessage()));
            exit;
        }
    }
}

