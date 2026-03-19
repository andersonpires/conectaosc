<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
require_once $BASE_para_PATH . '/api/repositories/PaginaRepository.php';
require_once $BASE_para_PATH . '/api/services/PaginaService.php';

use BackEnd\Repositories\PaginaRepository;
use BackEnd\Services\PaginaService;

class PaginaModel
{
    private static function service(): PaginaService
    {
        return new PaginaService(new PaginaRepository());
    }

    public static function listarPaginas(): array
    {
        return self::service()->listarPaginas();
    }

    public static function buscarPagina(int $id): ?array
    {
        return self::service()->buscarPagina($id);
    }

    public static function criarPagina(string $nome, string $descricao, string $tipoApp, string $arquivo): int
    {
        return self::service()->criarPagina($nome, $descricao, $tipoApp, $arquivo);
    }

    public static function atualizarPagina(int $id, string $nome, string $descricao, string $tipoApp, string $arquivo): bool
    {
        return self::service()->atualizarPagina($id, $nome, $descricao, $tipoApp, $arquivo);
    }

    public static function excluirPagina(int $id): bool
    {
        return self::service()->excluirPagina($id);
    }
}




