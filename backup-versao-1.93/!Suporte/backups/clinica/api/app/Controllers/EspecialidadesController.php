<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\JsonResponse;
use App\Middlewares\AuthMiddleware;

class EspecialidadesController
{
    public function index(): void
    {
        AuthMiddleware::requireAuth();
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, nome FROM tb_especialidade WHERE ativo = 1 ORDER BY nome");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        JsonResponse::success(['especialidades' => $rows]);
    }
}
