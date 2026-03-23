<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class SystemController
{
    public function health(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => 'Front MVC router is ready',
            'data' => ['timestamp' => date('c')],
            'errors' => [],
        ], JSON_UNESCAPED_UNICODE);
    }
}
