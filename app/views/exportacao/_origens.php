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
        // Alem dos caminhos relativos a base, tentamos os absolutos conhecidos
        // do VPS — caso $BASE_para_PATH resolva para outro lugar.
        $absolutos = [
            'img' => ['/var/www/html/conectaosc/app/assets/img'],
            'storage' => ['/var/www/html/conectaosc/storage'],
        ];
        return [
            'img' => array_values(array_unique(array_merge([
                $base . '/app/assets/img',
                $base . '/assets/img',
            ], $absolutos['img']))),
            'storage' => array_values(array_unique(array_merge([
                $base . '/storage',
                $base . '/app/storage',
                dirname($base) . '/storage',
            ], $absolutos['storage']))),
        ];
    }
}

if (!function_exists('exportacaoInspecionar')) {
    /**
     * Radiografia de uma pasta: existe? da para ler? quantos arquivos tem (sem
     * aplicar exclusoes) e quais subpastas de 1o nivel, com contagem. Serve para
     * descobrir onde os arquivos realmente estao no servidor.
     */
    function exportacaoInspecionar(string $dir): array
    {
        $info = [
            'caminho' => $dir,
            'existe' => is_dir($dir),
            'legivel' => false,
            'link' => is_link($dir) ? (readlink($dir) ?: '?') : null,
            'real' => null,
            'arquivos' => 0,
            'sub' => [],
            'erro' => null,
        ];
        if (!$info['existe']) {
            return $info;
        }
        $info['legivel'] = is_readable($dir);
        $info['real'] = realpath($dir) ?: null;
        if (!$info['legivel']) {
            $info['erro'] = 'sem permissao de leitura para o usuario do PHP';
            return $info;
        }
        try {
            foreach (new DirectoryIterator($dir) as $item) {
                if ($item->isDot()) {
                    continue;
                }
                if ($item->isDir()) {
                    $n = 0;
                    try {
                        $it = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($item->getPathname(), FilesystemIterator::SKIP_DOTS),
                            RecursiveIteratorIterator::LEAVES_ONLY
                        );
                        foreach ($it as $f) {
                            if ($f->isFile()) {
                                $n++;
                            }
                        }
                    } catch (Throwable $e) {
                        $n = -1;
                    }
                    $info['sub'][$item->getFilename()] = $n;
                    if ($n > 0) {
                        $info['arquivos'] += $n;
                    }
                } elseif ($item->isFile()) {
                    $info['arquivos']++;
                }
            }
        } catch (Throwable $e) {
            $info['erro'] = $e->getMessage();
        }
        return $info;
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
