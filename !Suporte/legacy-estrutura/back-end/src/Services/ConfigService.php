<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\ConfigRepository;
use InvalidArgumentException;

final class ConfigService
{
    public function __construct(private readonly ConfigRepository $repository)
    {
    }

    public function getConfig(): array
    {
        return $this->repository->findFirst();
    }

    public function updateConfig(array $payload): array
    {
        $config = $this->repository->findFirst();
        if ($config === []) {
            throw new InvalidArgumentException('Configuração não encontrada');
        }

        $idConfig = (int) ($config['IdConfig'] ?? 0);
        if ($idConfig <= 0) {
            throw new InvalidArgumentException('IdConfig inválido');
        }

        $sanitized = $this->sanitize($payload, $config);
        $this->repository->updateById($idConfig, $sanitized);

        return $this->repository->findFirst();
    }

    private function sanitize(array $payload, array $fallback): array
    {
        $fields = [
            'NomeSistema',
            'CorPrimaria',
            'CorSecundaria',
            'LogoSistema',
            'LogoImpressao',
            'ShortcutIcon',
            'TituloPagina',
            'MetaDescription',
            'MetaAuthor',
            'MetaKeywords',
            'APIzap',
            'HeaderEmail',
            'TermosUso',
        ];

        $result = [];
        foreach ($fields as $field) {
            $value = $payload[$field] ?? $fallback[$field] ?? '';
            $result[$field] = is_string($value) ? trim($value) : (string) $value;
        }

        return $result;
    }
}

