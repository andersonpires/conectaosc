<?php
declare(strict_types=1);

namespace BackEnd\Controllers;

use BackEnd\Core\Response;

final class HealthController
{
    public function index(): void
    {
        Response::json(
            [
                'success' => true,
                'message' => 'API is running',
                'data' => [
                    'app' => 'ConectaOSC3 API',
                    'version' => 'v1',
                    'php' => PHP_VERSION,
                    'timestamp' => date('c'),
                ],
                'errors' => [],
            ]
        );
    }
}

