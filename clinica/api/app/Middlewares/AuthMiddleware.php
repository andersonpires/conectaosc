<?php
namespace App\Middlewares;

use App\Core\JsonResponse;

require_once dirname(__DIR__, 4) . '/bootstrap/runtime.php';

class AuthMiddleware
{
    public static function requireAuth(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('PHPSESSID3');
            session_start();
        }

        $basePath = $_SESSION['BASE_para_PATH'] ?? null;
        if (is_string($basePath) && $basePath !== '') {
            bootstrap_validate_auth_session_cookie_name($basePath);
        }

        if (empty($_SESSION['Cod']) || empty($basePath)) {
            JsonResponse::error('Nao autenticado', [], 401);
        }
    }

    public static function requireProfissionalSaude(): void
    {
        self::requireAuth();
        $profissional = (int)($_SESSION['profissional_saude'] ?? 0);
        if ($profissional !== 1) {
            JsonResponse::error('Acesso restrito a profissionais de saude', [], 403);
        }
    }

    public static function requireAcessoClinica(): void
    {
        self::requireAuth();
        $profissional = (int)($_SESSION['profissional_saude'] ?? 0);
        $licencaAdministrativa = (int)($_SESSION['licenca_administrativa'] ?? 0);
        if ($profissional !== 1 && $licencaAdministrativa !== 1) {
            JsonResponse::error('Acesso restrito ao App Clinica', [], 403);
        }
    }

    public static function getUserId(): int
    {
        return (int)($_SESSION['Cod'] ?? 0);
    }

    public static function isSuperAdmin(): bool
    {
        $permissao = $_SESSION['IdPermissao'] ?? 0;
        $nome = $_SESSION['Tipo'] ?? '';
        return ($permissao === 4 || $nome === 'Administrador' || $nome === 'Superadministrador');
    }

    public static function isLicencaAdministrativa(): bool
    {
        return (int)($_SESSION['licenca_administrativa'] ?? 0) === 1
            && (int)($_SESSION['profissional_saude'] ?? 0) !== 1;
    }
}
