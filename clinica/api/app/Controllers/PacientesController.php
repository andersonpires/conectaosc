<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\JsonResponse;
use App\Middlewares\AuthMiddleware;
use App\Services\ConectaOsc3ApiClient;

class PacientesController
{
    public function index(): void
    {
        AuthMiddleware::requireAuth();
        $search = trim($_GET['search'] ?? '');
        $limit = max(1, min(20, (int) ($_GET['limit'] ?? 4)));

        if ($search === '') {
            JsonResponse::success(['pacientes' => []]);
            return;
        }

        $rows = $this->loadPacientesViaApi($search, $limit);
        if ($rows === null) {
            $rows = $this->loadPacientesViaBanco($search, $limit);
        }

        JsonResponse::success(['pacientes' => $rows]);
    }

    public function show(string $id): void
    {
        AuthMiddleware::requireAuth();
        $id = (int) $id;
        if ($id <= 0) {
            JsonResponse::error('ID invalido', [], 400);
        }

        $row = $this->loadPacienteDetalheViaApi($id);
        if ($row === null) {
            $row = $this->loadPacienteDetalheViaBanco($id);
        }

        if (!$row) {
            JsonResponse::error('Paciente nao encontrado', [], 404);
        }

        JsonResponse::success(['paciente' => $row]);
    }

    private function loadPacientesViaApi(string $search, int $limit): ?array
    {
        try {
            $client = new ConectaOsc3ApiClient();
            $payload = $client->get('/beneficiarios/busca', [
                'search' => $search,
                'page' => 1,
                'per_page' => $limit,
                'habilitado' => 1,
            ]);

            $data = $payload['data'] ?? [];
            $items = $data['items'] ?? null;
            if (!is_array($items)) {
                return null;
            }

            return array_map(static function (array $item): array {
                $foto = trim((string) ($item['Foto'] ?? ''));
                return [
                    'IdUsuario' => (int)($item['IdUsuario'] ?? 0),
                    'Nome' => (string)($item['Nome'] ?? ''),
                    'Apelido' => (string)($item['Apelido'] ?? ''),
                    'CPF' => (string)($item['CPF'] ?? ''),
                    'Telefone' => (string)($item['Telefone'] ?? ''),
                    'WhatsApp' => (string)($item['WhatsApp'] ?? ''),
                    'Email' => (string)($item['Email'] ?? ''),
                    'Nascimento' => (string)($item['Nascimento'] ?? ''),
                    'Foto' => $foto,
                    'FotoUrl' => self::buildFotoUrl($foto),
                ];
            }, $items);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function loadPacienteDetalheViaApi(int $id): ?array
    {
        try {
            $client = new ConectaOsc3ApiClient();
            $payload = $client->get('/beneficiarios/' . $id);
            $item = $payload['data'] ?? null;
            if (!is_array($item)) {
                return null;
            }

            return [
                'IdUsuario' => (int)($item['IdUsuario'] ?? 0),
                'Nome' => (string)($item['Nome'] ?? ''),
                'Apelido' => (string)($item['Apelido'] ?? ''),
                'SexoBio' => (string)($item['SexoBio'] ?? ''),
                'Nascimento' => (string)($item['Nascimento'] ?? ''),
                'CPF' => (string)($item['CPF'] ?? ''),
                'Identidade' => (string)($item['Identidade'] ?? ''),
                'NomeResp1' => (string)($item['NomeResp1'] ?? ''),
                'Parentesco' => (string)($item['Parentesco'] ?? ''),
                'CpfResp1' => (string)($item['CpfResp1'] ?? ''),
                'TelefoneResp1' => (string)($item['TelefoneResp1'] ?? ''),
                'WhatsAppResp1' => (string)($item['WhatsAppResp1'] ?? ''),
                'CEP' => (string)($item['CEP'] ?? ''),
                'Endereco' => (string)($item['Endereco'] ?? ''),
                'Numero' => (string)($item['Numero'] ?? ''),
                'Complemento' => (string)($item['Complemento'] ?? ''),
                'Bairro' => (string)($item['Bairro'] ?? ''),
                'Cidade' => (string)($item['Cidade'] ?? ''),
                'UF' => (string)($item['UF'] ?? ''),
                'Telefone' => (string)($item['Telefone'] ?? ''),
                'WhatsApp' => (string)($item['WhatsApp'] ?? ''),
                'Email' => (string)($item['Email'] ?? ''),
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function loadPacientesViaBanco(string $search, int $limit): array
    {
        $pdo = Database::getConnection();

        $sql = "SELECT IdUsuario, Nome, Apelido, CPF, Telefone, WhatsApp, Email, Nascimento, Foto
                FROM tbAluno WHERE Habilitado = 1";
        $params = [];

        if ($search !== '') {
            $term = '%' . $search . '%';
            $sql .= " AND (Nome LIKE ? OR Apelido LIKE ? OR CPF LIKE ? OR Telefone LIKE ? OR WhatsApp LIKE ? OR Email LIKE ?)";
            $params = array_fill(0, 6, $term);
        }

        $sql .= " ORDER BY Nome LIMIT " . $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(static function (array $row): array {
            $foto = trim((string) ($row['Foto'] ?? ''));
            $row['Foto'] = $foto;
            $row['FotoUrl'] = self::buildFotoUrl($foto);
            return $row;
        }, $rows);
    }

    private function loadPacienteDetalheViaBanco(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT IdUsuario, Nome, Apelido, SexoBio, Nascimento, CPF, Identidade,
                   NomeResp1, Parentesco, CpfResp1, TelefoneResp1, WhatsAppResp1,
                   CEP, Endereco, Numero, Complemento, Bairro, Cidade, UF,
                   Telefone, WhatsApp, Email
            FROM tbAluno WHERE IdUsuario = ? AND Habilitado = 1
        ");
        $stmt->execute([$id]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    private static function buildFotoUrl(string $foto): string
    {
        $foto = $foto !== '' ? basename($foto) : 'padrao.jpg';

        if (
            !empty($_SESSION['BASE_ASSETS_IMG_URL']) &&
            is_string($_SESSION['BASE_ASSETS_IMG_URL'])
        ) {
            return rtrim((string) $_SESSION['BASE_ASSETS_IMG_URL'], '/') . '/fotos/' . rawurlencode($foto);
        }

        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $projectBasePath = preg_match('#^(.*?)/clinica(?:/|$)#', $scriptName, $matches)
            ? rtrim((string) ($matches[1] ?? ''), '/')
            : '';

        return ($projectBasePath !== '' ? $projectBasePath : '') . '/assets/img/fotos/' . rawurlencode($foto);
    }
}
