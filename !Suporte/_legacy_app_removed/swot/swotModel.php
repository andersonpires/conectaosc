<?php
require_once __DIR__ . '/../../bootstrap/runtime.php';
$runtime = bootstrap_runtime();
$BASE_para_PATH = $runtime['base_path'];

require_once $BASE_para_PATH . '/api/repositories/SwotRepository.php';
require_once $BASE_para_PATH . '/api/services/SwotService.php';

use BackEnd\Repositories\SwotRepository;
use BackEnd\Services\SwotService;

class SwotModel
{
    private static function service(): SwotService
    {
        return new SwotService(new SwotRepository());
    }

    public static function listUsuariosAtivos(): array
    {
        return self::service()->listUsuariosAtivos();
    }

    public static function listByUser($idColaborador): array
    {
        return self::service()->listByUser((int)$idColaborador);
    }

    public static function listForReport($tema = null, $idColaborador = null): array
    {
        return self::service()->listForReport($tema, $idColaborador);
    }

    public static function getById($id): ?array
    {
        return self::service()->getById((int)$id);
    }

    public static function create($dados): bool
    {
        return self::service()->create((array)$dados);
    }

    public static function update($dados): bool
    {
        return self::service()->update((array)$dados);
    }

    public static function delete($id): bool
    {
        return self::service()->delete((int)$id);
    }
}


