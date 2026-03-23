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
                $caminhoArquivo = rtrim($basePath, '/\\') . "/assets/img/fotos/" . $nomeArquivo;
                move_uploaded_file((string)$foto['tmp_name'], $caminhoArquivo);
            }
        }

        $senha = (string)($dados['Senha'] ?? '');
        $hashSenha = $senha !== '' ? password_hash($senha, PASSWORD_DEFAULT) : null;
        $profissionalSaude = isset($dados['profissional_saude']) && (string)$dados['profissional_saude'] === '1' ? 1 : 0;
        $especialidadeId = $dados['especialidade_id'] ?? null;

        return $this->repository->update(
            $idColaborador,
            $nomeArquivo,
            isset($dados['Nome']) ? (string)$dados['Nome'] : null,
            isset($dados['Sobrenome']) ? (string)$dados['Sobrenome'] : null,
            isset($dados['WhatsApp']) ? (string)$dados['WhatsApp'] : null,
            isset($dados['Email']) ? (string)$dados['Email'] : null,
            isset($dados['CidadeEstado']) ? (string)$dados['CidadeEstado'] : null,
            isset($dados['Trabalho']) ? (string)$dados['Trabalho'] : null,
            $profissionalSaude,
            $especialidadeId,
            $hashSenha
        );
    }
}

