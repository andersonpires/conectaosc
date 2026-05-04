<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\PerfilRepository;

final class PerfilService
{
    public function __construct(private readonly PerfilRepository $repository)
    {
    }

    public function getById(int $idColaborador): ?array
    {
        return $this->repository->getById($idColaborador);
    }

    public function update(array $dados, array $files, string $basePath): bool
    {
        $idColaborador = isset($dados['IdColaborador']) ? (int)$dados['IdColaborador'] : 0;
        if ($idColaborador <= 0) {
            return false;
        }

        $fotoString = (string)($dados['fotostring'] ?? '');
        $nomeArquivo = $fotoString;
        if (isset($files['foto']) && is_array($files['foto']) && ($files['foto']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $foto = $files['foto'];
            preg_match('/\.(png|jpg|jpeg){1}$/i', (string)$foto['name'], $ext);
            if ($ext) {
                $nomeArquivo = md5(uniqid((string)time(), true)) . "." . $ext[1];
                $dirFotos = $this->resolveFotosDir($basePath);
                if (!is_dir($dirFotos)) {
                    @mkdir($dirFotos, 0777, true);
                }
                $caminhoArquivo = rtrim($dirFotos, '/\\') . DIRECTORY_SEPARATOR . $nomeArquivo;
                move_uploaded_file((string)$foto['tmp_name'], $caminhoArquivo);
            }
        }

        $senha = (string)($dados['Senha'] ?? '');
        $hashSenha = $senha !== '' ? password_hash($senha, PASSWORD_DEFAULT) : null;
        $profissionalSaude = isset($dados['profissional_saude']) && (string)$dados['profissional_saude'] === '1' ? 1 : 0;
        $especialidadeId = $dados['especialidade_id'] ?? null;
        $nascimento = $this->normalizarNascimento($dados['Nascimento'] ?? null);

        return $this->repository->update(
            $idColaborador,
            $nomeArquivo,
            isset($dados['Nome']) ? (string)$dados['Nome'] : null,
            isset($dados['Sobrenome']) ? (string)$dados['Sobrenome'] : null,
            isset($dados['WhatsApp']) ? (string)$dados['WhatsApp'] : null,
            isset($dados['Email']) ? (string)$dados['Email'] : null,
            isset($dados['CidadeEstado']) ? (string)$dados['CidadeEstado'] : null,
            isset($dados['Cargo']) ? (string)$dados['Cargo'] : null,
            $nascimento,
            $profissionalSaude,
            $especialidadeId,
            $hashSenha
        );
    }

    private function normalizarNascimento(mixed $valor): ?string
    {
        $raw = trim((string)$valor);
        if ($raw === '') {
            return null;
        }

        $dt = \DateTime::createFromFormat('Y-m-d', $raw);
        if (!$dt || $dt->format('Y-m-d') !== $raw) {
            return null;
        }

        return $raw;
    }

    private function resolveFotosDir(string $basePath): string
    {
        $runtimeFile = dirname(__DIR__, 2) . '/bootstrap/runtime.php';
        if (is_file($runtimeFile)) {
            $runtime = require $runtimeFile;
            if (!empty($runtime['assets_img_path'])) {
                return rtrim((string)$runtime['assets_img_path'], '/\\') . DIRECTORY_SEPARATOR . 'fotos';
            }
        }

        return rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'fotos';
    }
}
