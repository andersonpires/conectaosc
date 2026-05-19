<?php
declare(strict_types=1);

namespace BackEnd\Controllers;

use BackEnd\Core\Response;

final class AuthController
{
    public function me(): void
    {
        $profile = $this->resolveProfileData();

        Response::json(
            [
                'success' => true,
                'message' => 'Authenticated user',
                'data' => [
                    'id' => $_SESSION['Cod'] ?? null,
                    'name' => $_SESSION['Nome'] ?? null,
                    'email' => $_SESSION['Email'] ?? null,
                    'perfil' => $_SESSION['Perfil'] ?? null,
                    'nome_completo' => $profile['nome_completo'] ?? trim((string)(($_SESSION['Nome'] ?? '') . ' ' . ($_SESSION['Sobrenome'] ?? ''))),
                    'profissional_saude' => $profile['profissional_saude'] ?? null,
                    'licenca_administrativa' => $profile['licenca_administrativa'] ?? null,
                    'especialidade_id' => $profile['especialidade_id'] ?? null,
                ],
                'errors' => [],
            ]
        );
    }

    private function resolveProfileData(): array
    {
        $idColaborador = (int)($_SESSION['Cod'] ?? 0);
        if ($idColaborador <= 0) {
            return [];
        }

        try {
            $root = dirname(__DIR__, 2);
            require $root . '/api/conectabd/conexao.php';
            if (!isset($pdo) || !$pdo instanceof \PDO) {
                return [];
            }

            $stmt = $pdo->prepare(
                'SELECT Nome, Sobrenome, COALESCE(profissional_saude, 0) AS profissional_saude,
                        COALESCE(licenca_administrativa, 0) AS licenca_administrativa,
                        especialidade_id
                   FROM tbUser
                  WHERE IdColaborador = ?'
            );
            $stmt->execute([$idColaborador]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return [];
            }

            return [
                'nome_completo' => trim((string)($row['Nome'] ?? '') . ' ' . (string)($row['Sobrenome'] ?? '')),
                'profissional_saude' => (int)($row['profissional_saude'] ?? 0),
                'licenca_administrativa' => (int)($row['licenca_administrativa'] ?? 0),
                'especialidade_id' => isset($row['especialidade_id']) && $row['especialidade_id'] !== null ? (int)$row['especialidade_id'] : null,
            ];
        } catch (\Throwable) {
            return [];
        }
    }
}
