<?php

/**
 * Origens dos arquivos fisicos do legado.
 *
 * Cada "prefixo" do ZIP pode vir de mais de uma pasta, porque a instalacao
 * varia: em alguns servidores o volume de storage esta montado na RAIZ
 * (<base>/storage) e em outros dentro de app (<base>/app/storage). Aqui a gente
 * procura em todos os candidatos e junta o que existir sob o mesmo prefixo.
 */

if (!function_exists('exportacaoCandidatos')) {
    /** @return array<string, string[]> prefixo => lista de caminhos candidatos */
    function exportacaoCandidatos(string $base): array
    {
        $base = rtrim(str_replace('\\', '/', $base), '/');
        return [
            'img' => [
                $base . '/app/assets/img',
                $base . '/assets/img',
            ],
            'storage' => [
                $base . '/storage',
                $base . '/app/storage',
            ],
        ];
    }
}

if (!function_exists('exportacaoOrigens')) {
    /**
     * Só os candidatos que existem de fato (e sem repetir a mesma pasta).
     *
     * @return array<string, string[]>
     */
    function exportacaoOrigens(string $base): array
    {
        $saida = [];
        foreach (exportacaoCandidatos($base) as $prefixo => $candidatos) {
            $vistos = [];
            foreach ($candidatos as $dir) {
                if (!is_dir($dir)) {
                    continue;
                }
                $real = realpath($dir) ?: $dir;
                if (isset($vistos[$real])) {
                    continue; // mesma pasta por outro caminho (ex.: symlink)
                }
                $vistos[$real] = true;
                $saida[$prefixo][] = $dir;
            }
            $saida[$prefixo] ??= [];
        }
        return $saida;
    }
}

if (!function_exists('exportacaoIgnorar')) {
    /**
     * O que nao vai no pacote: temporarios da assinatura e, no storage,
     * backups e logs (nao servem para a migracao e sao enormes).
     */
    function exportacaoIgnorar(string $prefixo, string $relativo): bool
    {
        $rel = trim(str_replace('\\', '/', $relativo), '/');
        if (str_starts_with($rel, 'assinatura/tmp/')) {
            return true;
        }
        if ($prefixo === 'storage') {
            if (str_starts_with($rel, 'backups/') || str_starts_with($rel, 'logs/')) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('exportacaoVarrer')) {
    /**
     * Percorre as origens de um prefixo chamando $callback($caminhoAbsoluto,
     * $caminhoNoZip, $bytes). Evita repetir o mesmo caminho relativo quando a
     * mesma pasta aparece em mais de uma origem.
     */
    function exportacaoVarrer(string $prefixo, array $origens, callable $callback): void
    {
        $jaVistos = [];
        foreach ($origens as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($it as $arq) {
                if (!$arq->isFile()) {
                    continue;
                }
                $caminho = $arq->getPathname();
                $rel = str_replace('\\', '/', substr($caminho, strlen($dir) + 1));
                if (exportacaoIgnorar($prefixo, $rel)) {
                    continue;
                }
                $noZip = $prefixo . '/' . $rel;
                if (isset($jaVistos[$noZip])) {
                    continue;
                }
                $jaVistos[$noZip] = true;
                $callback($caminho, $noZip, (int) $arq->getSize());
            }
        }
    }
}
