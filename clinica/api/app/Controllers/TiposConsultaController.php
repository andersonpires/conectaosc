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
        $isAdmin = AuthMiddleware::isSuperAdmin();
        $sql = $isAdmin
            ? "SELECT id, nome, ativo FROM tb_tipo_consulta ORDER BY nome"
            : "SELECT id, nome, ativo FROM tb_tipo_consulta WHERE ativo = 1 ORDER BY nome";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        JsonResponse::success(['tipos_consulta' => $rows]);
    }

    public function store(): void
    {
        AuthMiddleware::requireAuth();
        if (!AuthMiddleware::isSuperAdmin()) {
            JsonResponse::error('Acesso restrito a administradores', [], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $nome = trim((string) ($input['nome'] ?? ''));
        if ($nome === '') {
            JsonResponse::error('Nome obrigatorio', [], 422);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO tb_tipo_consulta (nome, ativo) VALUES (?, 1)');
        $stmt->execute([$nome]);

        JsonResponse::success(['id' => (int) $pdo->lastInsertId()], 'Tipo de consulta criado', 201);
    }

    public function update(string $id): void
    {
        AuthMiddleware::requireAuth();
        if (!AuthMiddleware::isSuperAdmin()) {
            JsonResponse::error('Acesso restrito a administradores', [], 403);
        }

        $tipoId = (int) $id;
        if ($tipoId <= 0) {
            JsonResponse::error('ID invalido', [], 400);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $nome = trim((string) ($input['nome'] ?? ''));
        if ($nome === '') {
            JsonResponse::error('Nome obrigatorio', [], 422);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE tb_tipo_consulta SET nome = ? WHERE id = ?');
        $stmt->execute([$nome, $tipoId]);
        if ($stmt->rowCount() === 0) {
            JsonResponse::error('Tipo de consulta nao encontrado', [], 404);
        }

        JsonResponse::success(['id' => $tipoId], 'Tipo de consulta atualizado');
    }

    public function toggle(string $id): void
    {
        AuthMiddleware::requireAuth();
        if (!AuthMiddleware::isSuperAdmin()) {
            JsonResponse::error('Acesso restrito a administradores', [], 403);
        }

        $tipoId = (int) $id;
        if ($tipoId <= 0) {
            JsonResponse::error('ID invalido', [], 400);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT ativo FROM tb_tipo_consulta WHERE id = ? LIMIT 1');
        $stmt->execute([$tipoId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            JsonResponse::error('Tipo de consulta nao encontrado', [], 404);
        }

        $novoAtivo = ((int) ($row['ativo'] ?? 0) === 1) ? 0 : 1;
        $update = $pdo->prepare('UPDATE tb_tipo_consulta SET ativo = ? WHERE id = ?');
        $update->execute([$novoAtivo, $tipoId]);

        JsonResponse::success(['id' => $tipoId, 'ativo' => $novoAtivo], $novoAtivo === 1 ? 'Tipo ativado' : 'Tipo inativado');
    }
}
