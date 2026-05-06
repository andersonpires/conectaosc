<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

use PDO;
use Throwable;

final class BeneficiarioController
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $basePath
    ) {
    }

    public function cadastro(): void
    {
        $flow = new BeneficiarioCadastroFlow($this->basePath, $this->baseUrl);
        $flow->handle();
    }

    public function lista(): void
    {
        $this->render('/app/views/beneficiario/listagemSBenef.php');
    }

    public function dados(): void
    {
        $this->render('/app/views/beneficiario/listagemBeneficiarios.php');
    }

    public function aniversariantes(): void
    {
        $this->render('/app/views/aniversariantes/aniversariantes.php');
    }

    public function aniversariantesDados(): void
    {
        $flow = new AniversariantesDataFlow($this->basePath, $this->baseUrl);
        $flow->handle();
    }

    public function consultaCpf(): void
    {
        $this->render('/app/views/beneficiario/consulta-cpf.php');
    }

    public function fotos(): void
    {
        try {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                @session_start();
            }

            require_once $this->basePath . '/api/legacy/checa-token.php';
            require_once $this->basePath . '/api/conectabd/conexao.php';
            $runtime = require $this->basePath . '/bootstrap/runtime.php';
            header('Content-Type: application/json; charset=utf-8');

            $assetsImgUrl  = rtrim((string)($runtime['assets_img_url']  ?? (rtrim($this->baseUrl, '/') . '/assets/img')), '/');
            $assetsImgPath = rtrim((string)($runtime['assets_img_path'] ?? ''), '/\\');
            $fotoPadraoUrl = $assetsImgUrl . '/fotos/padrao.jfif';
            $idsRaw = (string)($_GET['ids'] ?? '');

            if ($idsRaw === '') {
                echo json_encode(['fotos' => []], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                return;
            }

            $ids = preg_split('/[,\s]+/', $idsRaw) ?: [];
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0)));
            if ($ids === []) {
                echo json_encode(['fotos' => []], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                return;
            }

            $ids = array_slice($ids, 0, 200);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            /** @var PDO $pdo */
            $stmt = $pdo->prepare("SELECT IdUsuario, Foto FROM tbAluno WHERE Habilitado = 1 AND IdUsuario IN ($placeholders)");
            foreach ($ids as $index => $id) {
                $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
            }
            $stmt->execute();

            $fotos = [];
            foreach ($ids as $id) {
                $fotos[(string)$id] = $fotoPadraoUrl;
            }

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $id = (string)($row['IdUsuario'] ?? '');
                if ($id === '') {
                    continue;
                }

                $fotoRaw = trim((string)($row['Foto'] ?? ''));
                if ($fotoRaw === '') {
                    $fotos[$id] = $fotoPadraoUrl;
                    continue;
                }

                if (preg_match('/^https?:\/\//i', $fotoRaw)) {
                    $fotos[$id] = $fotoRaw;
                    continue;
                }

                $filename = basename($fotoRaw);
                $filePath = $assetsImgPath !== ''
                    ? $assetsImgPath . DIRECTORY_SEPARATOR . 'fotos' . DIRECTORY_SEPARATOR . $filename
                    : '';
                if ($filePath === '' || is_file($filePath)) {
                    $fotos[$id] = $assetsImgUrl . '/fotos/' . rawurlencode($filename);
                }
                // se o arquivo não existe no disco, mantém $fotoPadraoUrl já definido
            }

            echo json_encode(['fotos' => $fotos], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['fotos' => [], 'erro' => 'Falha ao buscar fotos.'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
    }

    public function avaliarVulnerabilidadeStream(): void
    {
        $this->render('/app/views/beneficiario/avaliarVulnerabilidadeStream.php');
    }

    private function render(string $legacyPath): void
    {
        $candidate = $this->basePath . $legacyPath;
        if (is_file($candidate)) {
            require $candidate;
            return;
        }
        http_response_code(404);
        echo 'Página não encontrada';
    }

    private function redirect(string $path): void
    {
        $location = rtrim($this->baseUrl, '/') . $path;
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
        if ($query !== '') {
            $location .= str_contains($location, '?') ? '&' . $query : '?' . $query;
        }
        header('Location: ' . $location);
        exit;
    }
}
