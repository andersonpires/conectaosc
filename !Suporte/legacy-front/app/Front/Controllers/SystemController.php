<?php
declare(strict_types=1);

namespace Front\Controllers;

final class SystemController
{
    public function health(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            [
                'success' => true,
                'message' => 'Front router is ready',
                'data' => ['timestamp' => date('c')],
                'errors' => [],
            ],
            JSON_UNESCAPED_UNICODE
        );
    }
}

