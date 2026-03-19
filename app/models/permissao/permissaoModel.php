<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
require_once $BASE_para_PATH . '/api/repositories/PermissaoRepository.php';
require_once $BASE_para_PATH . '/api/services/PermissaoService.php';

use BackEnd\Repositories\PermissaoRepository;
use BackEnd\Services\PermissaoService;

class PermissaoModel
{
    private static function service(): PermissaoService
    {
        return new PermissaoService(new PermissaoRepository());
    }

    public static function listarPermissoes(): array
    {
        return self::service()->listarPermissoes();
    }

    public static function getPermissao(int $id): ?array
    {
        return self::service()->getPermissao($id);
    }

    public static function getPermissaoPaginas(int $id): array
    {
        return self::service()->getPermissaoPaginas($id);
    }

    public static function getPermissaoHorarios(int $id): array
    {
        return self::service()->getPermissaoHorarios($id);
    }

    public static function listarPaginas(): array
    {
        return self::service()->listarPaginas();
    }

    public static function salvarPermissao(string $nome, string $descricao, array $paginas, bool $todosHorariosDias, array $horariosSelecionados, ?int $id = null): int
    {
        return self::service()->salvarPermissao($nome, $descricao, $paginas, $todosHorariosDias, $horariosSelecionados, $id);
    }

    public static function excluirPermissao(int $id): bool
    {
        return self::service()->excluirPermissao($id);
    }
}




