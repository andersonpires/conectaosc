<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\JsonResponse;
use App\Middlewares\AuthMiddleware;

class ProfissionaisController
{
    public function index(): void
    {
        AuthMiddleware::requireAuth();
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT IdColaborador, Nome, Sobrenome
            FROM tbUser
            WHERE Habilitado = 1
            AND COALESCE(profissional_saude, 0) = 1
            ORDER BY Nome, Sobrenome
        ");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $list = array_map(function ($r) {
            return [
                'id' => (int) $r['IdColaborador'],
                'nome' => trim($r['Nome'] . ' ' . $r['Sobrenome']),
            ];
        }, $rows);
        JsonResponse::success(['profissionais' => $list]);
    }
}
