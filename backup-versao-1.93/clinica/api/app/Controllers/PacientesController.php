<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\JsonResponse;
use App\Middlewares\AuthMiddleware;

class PacientesController
{
    public function index(): void
    {
        AuthMiddleware::requireAuth();
        $search = trim($_GET['search'] ?? '');
        $pdo = Database::getConnection();

        $sql = "SELECT IdUsuario, Nome, Apelido, CPF, Telefone, WhatsApp, Email, Nascimento
                FROM tbAluno WHERE Habilitado = 1";
        $params = [];

        if ($search !== '') {
            $term = '%' . $search . '%';
            $sql .= " AND (Nome LIKE ? OR Apelido LIKE ? OR CPF LIKE ? OR Telefone LIKE ? OR WhatsApp LIKE ? OR Email LIKE ?)";
            $params = array_fill(0, 6, $term);
        }

        $sql .= " ORDER BY Nome";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        JsonResponse::success(['pacientes' => $rows]);
    }

    public function show(string $id): void
    {
        AuthMiddleware::requireAuth();
        $id = (int) $id;
        if ($id <= 0) {
            JsonResponse::error('ID inválido', [], 400);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT IdUsuario, Nome, Apelido, SexoBio, Nascimento, CPF, Identidade,
                   NomeResp1, Parentesco, CpfResp1, TelefoneResp1, WhatsAppResp1,
                   CEP, Endereco, Numero, Complemento, Bairro, Cidade, UF,
                   Telefone, WhatsApp, Email
            FROM tbAluno WHERE IdUsuario = ? AND Habilitado = 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            JsonResponse::error('Paciente não encontrado', [], 404);
        }

        JsonResponse::success(['paciente' => $row]);
    }
}
