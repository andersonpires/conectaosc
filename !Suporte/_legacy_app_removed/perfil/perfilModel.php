<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_path'];
$BASE_para_URL = $runtime['base_url'];
require_once $BASE_para_PATH . '/api/repositories/PerfilRepository.php';
require_once $BASE_para_PATH . '/api/services/PerfilService.php';

use BackEnd\Repositories\PerfilRepository;
use BackEnd\Services\PerfilService;

class PerfilModel
{
    private static function service(): PerfilService
    {
        return new PerfilService(new PerfilRepository());
    }

    public static function getById(int $idColaborador): ?array
    {
        return self::service()->getById($idColaborador);
    }

    public static function update(array $dados, array $files): bool
    {
        return self::service()->update($dados, $files, (string)($BASE_para_PATH ?? ''));
    }
}



