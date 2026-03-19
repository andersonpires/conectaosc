<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

use BackEnd\Repositories\EventoRepository;
use BackEnd\Services\EventoService;
use Throwable;

final class EventoExcluirFlow
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['IdInscrito'])) {
            try {
                $service = new EventoService(new EventoRepository());
                $service->delete((int) $_POST['IdInscrito']);
                header('Location: ' . $base . '/eventos/inscritos/?msg=' . urlencode('Inscrição removida com sucesso'));
                exit;
            } catch (Throwable $e) {
                header('Location: ' . $base . '/eventos/inscritos/?erro=' . urlencode('Erro ao excluir: ' . $e->getMessage()));
                exit;
            }
        }

        header('Location: ' . $base . '/eventos/inscritos/?erro=' . urlencode('Requisição inválida'));
        exit;
    }
}

