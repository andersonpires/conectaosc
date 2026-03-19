<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

use BackEnd\Repositories\EventoRepository;
use BackEnd\Services\EventoService;
use Throwable;

final class EventoVerificaEmailFlow
{
    public function __construct(
        private readonly string $basePath
    ) {
    }

    public function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($this->basePath === '') {
            http_response_code(401);
            echo json_encode(['exists' => false], JSON_UNESCAPED_UNICODE);
            exit;
        }

        require_once $this->basePath . '/api/repositories/EventoRepository.php';
        require_once $this->basePath . '/api/services/EventoService.php';

        $email = (string) ($_GET['email'] ?? '');
        $response = ['exists' => false];

        try {
            $service = new EventoService(new EventoRepository());
            $response['exists'] = $service->emailExists($email);
        } catch (Throwable $e) {
            $response['error'] = 'Erro ao verificar e-mail: ' . $e->getMessage();
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

