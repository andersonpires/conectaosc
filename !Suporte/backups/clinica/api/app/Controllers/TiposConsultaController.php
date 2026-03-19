<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\JsonResponse;
use App\Middlewares\AuthMiddleware;

class TiposConsultaController
{
    public function index(): void
    {
        AuthMiddleware::requireAuth();
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, nome FROM tb_tipo_consulta WHERE ativo = 1 ORDER BY nome");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        JsonResponse::success(['tipos_consulta' => $rows]);
    }
}
