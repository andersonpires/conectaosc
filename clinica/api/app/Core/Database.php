<?php
namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        // Reutilizar conexão do ConectaOSC (mesma que o login usa)
        $basePath = rtrim((string) ($_SESSION['BASE_para_PATH'] ?? dirname(dirname(dirname(dirname(__DIR__))))), '/\\');
        $conexaoPath = $basePath . '/api/conectabd/conexao.php';
        if (!is_file($conexaoPath)) {
            $conexaoPath = $basePath . '/conectabd/conexao.php';
        }
        if (file_exists($conexaoPath)) {
            require_once $conexaoPath;
            if (isset($pdo) && $pdo instanceof PDO) {
                self::$pdo = $pdo;
                return self::$pdo;
            }
        }

        require_once $basePath . '/bootstrap/runtime.php';
        $dbConfig = bootstrap_database_config($basePath);

        if ($dbConfig['name'] === '' || $dbConfig['user'] === '') {
            throw new RuntimeException('Configuração do banco de dados ausente.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $dbConfig['host'],
            $dbConfig['port'],
            $dbConfig['name']
        );

        try {
            self::$pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            error_log('[clinica.database] Falha ao conectar ao banco: ' . $e->getMessage());
            throw new RuntimeException('Falha ao conectar ao banco de dados.', 0, $e);
        }

        return self::$pdo;
    }
}
