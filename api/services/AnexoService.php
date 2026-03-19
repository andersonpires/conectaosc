<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\AnexoRepository;
use InvalidArgumentException;

final class AnexoService
{
    private const MAX_FILE_SIZE = 26214400; // 25 MB

    /**
     * @var array<string, string[]>
     */
    private const ALLOWED_MIME_BY_EXT = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'ppt' => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'txt' => ['text/plain'],
        'csv' => ['text/csv', 'application/vnd.ms-excel', 'text/plain'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        'odt' => ['application/vnd.oasis.opendocument.text'],
        'ods' => ['application/vnd.oasis.opendocument.spreadsheet'],
        'odp' => ['application/vnd.oasis.opendocument.presentation'],
    ];

    public function __construct(private readonly AnexoRepository $repository)
    {
    }

    public function uploadPlanoAula(int $idPlanoCursoAula, array $files, int $idColaborador, array $payload = []): array
    {
        if (!$this->repository->existsPlanoAula($idPlanoCursoAula)) {
            throw new InvalidArgumentException('Aula do plano não encontrada');
        }
        return $this->uploadMany($files, 'plano_curso', function (array $meta) use ($idPlanoCursoAula, $idColaborador): int {
            return $this->repository->insertPlanoAulaAnexo([
                'IdPlanoCursoAula' => $idPlanoCursoAula,
                'NomeOriginal' => $meta['nome_original'],
                'NomeArquivo' => $meta['nome_arquivo'],
                'Descricao' => $meta['descricao'],
                'NomeFisico' => $meta['nome_fisico'],
                'MimeType' => $meta['mime_type'],
                'Extensao' => $meta['extensao'],
                'TamanhoBytes' => $meta['tamanho_bytes'],
                'HashArquivo' => $meta['hash_arquivo'],
                'CaminhoRelativo' => $meta['caminho_relativo'],
                'EnviadoPor' => $idColaborador,
            ]);
        }, [
            'id_plano_curso_aula' => $idPlanoCursoAula,
            'id_colaborador' => $idColaborador,
            'nome_arquivo' => $payload['NomeArquivo'] ?? '',
            'descricao' => $payload['Descricao'] ?? '',
        ]);
    }

    public function uploadCronogramaAula(int $idCronogramaAula, array $files, int $idColaborador): array
    {
        throw new InvalidArgumentException('Upload permitido somente no plano de curso');
    }

    public function listPlanoAula(int $idPlanoCursoAula): array
    {
        return $this->repository->listPlanoAulaAnexos($idPlanoCursoAula);
    }

    public function listCronogramaAula(int $idCronogramaAula): array
    {
        return $this->repository->listCronogramaAulaAnexos($idCronogramaAula);
    }

    /**
     * @param callable(array<string,mixed>): int $persist
     * @return array<string,mixed>
     */
    private function uploadMany(array $files, string $subdir, callable $persist, array $context = []): array
    {
        $normalized = $this->normalizeFiles($files);
        if ($normalized === []) {
            throw new InvalidArgumentException('Nenhum arquivo enviado');
        }

        $dir = $this->resolveStorageDir($subdir);
        $saved = [];
        foreach ($normalized as $index => $file) {
            $meta = $this->validateAndMove($file, $dir, $subdir);
            $meta['nome_arquivo'] = $this->resolveNomeArquivo($context, $meta['nome_original'], (int) $index);
            $meta['descricao'] = $this->resolveDescricao($context, (int) $index);
            $id = $persist($meta);
            if (
                $subdir === 'plano_curso'
                && isset($context['id_plano_curso_aula'], $context['id_colaborador'])
            ) {
                $this->finalizePlanoCursoFile(
                    (int) $id,
                    (int) $context['id_plano_curso_aula'],
                    (int) $context['id_colaborador'],
                    $meta,
                    $dir
                );
            }
            $saved[] = [
                'id' => $id,
                'nome_original' => $meta['nome_original'],
                'nome_arquivo' => $meta['nome_arquivo'],
                'descricao' => $meta['descricao'],
                'nome_fisico' => $meta['nome_fisico'],
                'mime_type' => $meta['mime_type'],
                'extensao' => $meta['extensao'],
                'tamanho_bytes' => $meta['tamanho_bytes'],
                'caminho_relativo' => $meta['caminho_relativo'],
            ];
        }

        return [
            'total' => count($saved),
            'arquivos' => $saved,
        ];
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    private function normalizeFiles(array $files): array
    {
        $file = $files['arquivo'] ?? null;
        if (!is_array($file)) {
            return [];
        }

        $result = [];
        $isMultiple = isset($file['name']) && is_array($file['name']);
        if (!$isMultiple) {
            $result[] = $file;
            return $result;
        }

        $count = count($file['name']);
        for ($i = 0; $i < $count; $i++) {
            $result[] = [
                'name' => $file['name'][$i] ?? '',
                'type' => $file['type'][$i] ?? '',
                'tmp_name' => $file['tmp_name'][$i] ?? '',
                'error' => $file['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $file['size'][$i] ?? 0,
            ];
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $file
     * @return array<string,mixed>
     */
    private function validateAndMove(array $file, string $targetDir, string $subdir): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Erro no upload do arquivo');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new InvalidArgumentException('Arquivo inválido para upload');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            throw new InvalidArgumentException('Arquivo vazio');
        }
        if ($size > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException('Arquivo excede o limite de 25MB');
        }

        $originalName = trim((string) ($file['name'] ?? 'arquivo'));
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === '' || !isset(self::ALLOWED_MIME_BY_EXT[$ext])) {
            throw new InvalidArgumentException('Extensão de arquivo não permitida');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = strtolower((string) $finfo->file($tmpName));
        $allowedMimes = self::ALLOWED_MIME_BY_EXT[$ext];
        if (!in_array($mimeType, $allowedMimes, true)) {
            throw new InvalidArgumentException('MIME do arquivo não corresponde à extensão permitida');
        }

        $randomName = bin2hex(random_bytes(20)) . '.' . $ext;
        $absolutePath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $randomName;
        if (!move_uploaded_file($tmpName, $absolutePath)) {
            throw new InvalidArgumentException('Falha ao armazenar arquivo');
        }

        $hash = hash_file('sha256', $absolutePath);
        $relativePath = 'assets/files/' . $subdir . '/' . $randomName;

        return [
            'nome_original' => $this->truncate($originalName, 255),
            'nome_fisico' => $randomName,
            'mime_type' => $this->truncate($mimeType, 120),
            'extensao' => $this->truncate($ext, 20),
            'tamanho_bytes' => $size,
            'hash_arquivo' => $hash !== false ? $hash : '',
            'caminho_relativo' => $this->truncate($relativePath, 255),
            'caminho_absoluto' => $absolutePath,
        ];
    }

    private function resolveStorageDir(string $subdir): string
    {
        $root = dirname(__DIR__, 2);
        $runtimeFile = $root . '/bootstrap/runtime.php';
        $baseAssetsPath = $root . '/app/assets';
        if (is_file($runtimeFile)) {
            $runtime = require $runtimeFile;
            if (!empty($runtime['assets_path'])) {
                $baseAssetsPath = rtrim((string) $runtime['assets_path'], '/\\');
            }
        }

        $dir = $baseAssetsPath . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $subdir;
        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new InvalidArgumentException('Não foi possível criar o diretório de upload');
        }
        return $dir;
    }

    /**
     * @param array<string,mixed> $context
     * @param array<string,mixed> $meta
     */
    private function finalizePlanoCursoFile(int $idPlanoCursoAnexo, int $idPlanoCursoAula, int $idColaborador, array &$meta, string $dir): void
    {
        $ext = (string) ($meta['extensao'] ?? '');
        $finalName = sprintf('%s_%d_%d_%d.%s', date('y-m-d'), $idPlanoCursoAnexo, $idPlanoCursoAula, $idColaborador, $ext);
        $finalAbsolutePath = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $finalName;

        if (!@rename((string) $meta['caminho_absoluto'], $finalAbsolutePath)) {
            $this->repository->deletePlanoAulaAnexo($idPlanoCursoAnexo);
            @unlink((string) $meta['caminho_absoluto']);
            throw new InvalidArgumentException('Falha ao aplicar nome final do arquivo');
        }

        $relativePath = 'assets/files/plano_curso/' . $finalName;
        $this->repository->updatePlanoAulaAnexoStorage($idPlanoCursoAnexo, $finalName, $relativePath);
        $meta['nome_fisico'] = $finalName;
        $meta['caminho_relativo'] = $relativePath;
        $meta['caminho_absoluto'] = $finalAbsolutePath;
    }

    /**
     * @param array<string,mixed> $context
     */
    private function resolveNomeArquivo(array $context, string $fallback, int $index): string
    {
        $value = $context['nome_arquivo'] ?? '';
        if (is_array($value)) {
            $value = $value[$index] ?? '';
        }
        $value = trim((string) $value);
        if ($value === '') {
            $value = $fallback;
        }
        return $this->truncate($value, 255);
    }

    /**
     * @param array<string,mixed> $context
     */
    private function resolveDescricao(array $context, int $index): ?string
    {
        $value = $context['descricao'] ?? '';
        if (is_array($value)) {
            $value = $value[$index] ?? '';
        }
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        return $this->truncate($value, 5000);
    }

    private function truncate(string $value, int $max): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max, 'UTF-8');
        }
        return substr($value, 0, $max);
    }
}

