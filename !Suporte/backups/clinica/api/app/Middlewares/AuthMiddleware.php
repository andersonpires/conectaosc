<?php
namespace App\Middlewares;

use App\Core\JsonResponse;

class AuthMiddleware
{
    public static function requireAuth(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['Cod']) || empty($_SESSION['BASE_para_PATH'])) {
            JsonResponse::error('Não autenticado', [], 401);
        }
    }

    public static function requireProfissionalSaude(): void
    {
        self::requireAuth();
        $profissional = (int)($_SESSION['profissional_saude'] ? 0);
        if ($profissional !== 1) {
            JsonResponse::error('Acesso restrito a profissionais de saúde', [], 403);
        }
    }

    public static function getUserId(): int
    {
        return (int)($_SESSION['Cod'] ? 0);
    }

    public static function isSuperAdmin(): bool
    {
        $permissao = $_SESSION['IdPermissao'] ? 0;
        $nome = $_SESSION['Tipo'] ? '';
        return ($permissao === 4 || $nome === 'Administrador' || $nome === 'Superadministrador');
    }
}
