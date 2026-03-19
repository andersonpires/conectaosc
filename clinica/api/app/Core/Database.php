<?php
namespace App\Core;

use PDO;
use PDOException;

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

        if (!isset($_ENV['DB_HOST'])) $_ENV['DB_HOST'] = '162.241.2.214';
        if (!isset($_ENV['DB_NAME'])) $_ENV['DB_NAME'] = 'mwtech63_matricula';
        if (!isset($_ENV['DB_USER'])) $_ENV['DB_USER'] = 'mwtech63_admin_matricula';
        if (!isset($_ENV['DB_PASS'])) $_ENV['DB_PASS'] = 'Iteva@100';
        if (!isset($_ENV['DB_PORT'])) $_ENV['DB_PORT'] = '3306';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'],
            $_ENV['DB_NAME']
        );

        self::$pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return self::$pdo;
    }
}
