<?php
declare(strict_types=1);

namespace BackEnd\Middlewares;

use BackEnd\Core\Request;
use BackEnd\Core\Response;

final class SessionAuthMiddleware
{
    public function __invoke(Request $request): void
    {
        if (empty($_SESSION['Cod'])) {
            Response::json(
                [
                    'success' => false,
                    'message' => 'Unauthorized',
                    'data' => (object) [],
                    'errors' => ['Login required'],
                ],
                401
            );
        }
    }
}

