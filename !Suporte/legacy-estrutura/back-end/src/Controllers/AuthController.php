<?php
declare(strict_types=1);

namespace BackEnd\Controllers;

use BackEnd\Core\Response;

final class AuthController
{
    public function me(): void
    {
        Response::json(
            [
                'success' => true,
                'message' => 'Authenticated user',
                'data' => [
                    'id' => $_SESSION['Cod'] ?? null,
                    'name' => $_SESSION['Nome'] ?? null,
                    'email' => $_SESSION['Email'] ?? null,
                    'perfil' => $_SESSION['Perfil'] ?? null,
                ],
                'errors' => [],
            ]
        );
    }
}

