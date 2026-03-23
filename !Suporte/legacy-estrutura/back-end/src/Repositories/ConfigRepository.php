<?php
declare(strict_types=1);

namespace BackEnd\Repositories;

use PDO;
use RuntimeException;

class ConfigRepository
{
    public function findFirst(): array
    {
        $pdo = $this->pdo();
        $stmt = $pdo->query('SELECT * FROM tbConfig LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : [];
    }

    public function updateById(int $idConfig, array $data): void
    {
        $pdo = $this->pdo();

        $stmt = $pdo->prepare(
            'UPDATE tbConfig SET
                NomeSistema = :NomeSistema,
                CorPrimaria = :CorPrimaria,
                CorSecundaria = :CorSecundaria,
                LogoSistema = :LogoSistema,
                LogoImpressao = :LogoImpressao,
                ShortcutIcon = :ShortcutIcon,
                TituloPagina = :TituloPagina,
                MetaDescription = :MetaDescription,
                MetaAuthor = :MetaAuthor,
                MetaKeywords = :MetaKeywords,
                APIzap = :APIzap,
                HeaderEmail = :HeaderEmail,
                TermosUso = :TermosUso
             WHERE IdConfig = :IdConfig'
        );

        $stmt->execute([
            ':NomeSistema' => $data['NomeSistema'] ?? '',
            ':CorPrimaria' => $data['CorPrimaria'] ?? '',
            ':CorSecundaria' => $data['CorSecundaria'] ?? '',
            ':LogoSistema' => $data['LogoSistema'] ?? '',
            ':LogoImpressao' => $data['LogoImpressao'] ?? '',
            ':ShortcutIcon' => $data['ShortcutIcon'] ?? '',
            ':TituloPagina' => $data['TituloPagina'] ?? '',
            ':MetaDescription' => $data['MetaDescription'] ?? '',
            ':MetaAuthor' => $data['MetaAuthor'] ?? '',
            ':MetaKeywords' => $data['MetaKeywords'] ?? '',
            ':APIzap' => $data['APIzap'] ?? '',
            ':HeaderEmail' => $data['HeaderEmail'] ?? '',
            ':TermosUso' => $data['TermosUso'] ?? '',
            ':IdConfig' => $idConfig,
        ]);
    }

    private function pdo(): PDO
    {
        $root = dirname(__DIR__, 3);
        require $root . '/conectabd/conexao.php';

        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new RuntimeException('Database connection unavailable');
        }

        return $pdo;
    }
}
