<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\JsonResponse;
use App\Middlewares\AuthMiddleware;
use App\Services\ConectaOsc3ApiClient;

class ProfissionaisController
{
    public function index(): void
    {
        AuthMiddleware::requireAuth();

        $list = $this->loadProfissionaisViaApi();
        if ($list === null) {
            $list = $this->loadProfissionaisViaBanco();
        }

        JsonResponse::success(['profissionais' => $list]);
    }

    private function loadProfissionaisViaApi(): ?array
    {
        try {
            $client = new ConectaOsc3ApiClient();
            $payload = $client->get('/colaboradores/profissionais-saude', [
                'habilitado' => 1,
            ]);
            $items = $payload['data'] ?? null;
            if (!is_array($items)) {
                return null;
            }

            return array_map(static function (array $row): array {
                return [
                    'id' => (int)($row['id'] ?? 0),
                    'nome' => (string)($row['nome_completo'] ?? trim(((string)($row['nome'] ?? '')) . ' ' . ((string)($row['sobrenome'] ?? '')))),
                ];
            }, $items);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function loadProfissionaisViaBanco(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT IdColaborador, Nome, Sobrenome
            FROM tbUser
            WHERE Habilitado = 1
            AND COALESCE(profissional_saude, 0) = 1
            ORDER BY Nome, Sobrenome
        ");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(function ($r) {
            return [
                'id' => (int) $r['IdColaborador'],
                'nome' => trim($r['Nome'] . ' ' . $r['Sobrenome']),
            ];
        }, $rows);
    }
}
