<?php
namespace App\Services;

use App\Core\Database;

class ColaboradorResolver
{
    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    public function hydrateProfissionalNome(array $rows, string $idField = 'profissional_id', string $nameField = 'profissional_nome'): array
    {
        $cache = [];

        foreach ($rows as &$row) {
            $id = (int)($row[$idField] ?? 0);
            if ($id <= 0) {
                if (empty($row[$nameField]) && isset($row['profissional_nome_livre'])) {
                    $row[$nameField] = (string)$row['profissional_nome_livre'];
                }
                $row['profissional_foto'] = '';
                continue;
            }

            if (!array_key_exists($id, $cache)) {
                $cache[$id] = $this->findProfissionalMeta($id);
            }

            if (($cache[$id]['nome'] ?? '') !== '') {
                $row[$nameField] = $cache[$id]['nome'];
            }
            $row['profissional_foto'] = (string)($cache[$id]['foto'] ?? '');
        }
        unset($row);

        return $rows;
    }

    /**
     * @return array{nome:string,foto:string}
     */
    private function findProfissionalMeta(int $idColaborador): array
    {
        $nome = $this->findNomeCompleto($idColaborador) ?? '';
        $foto = $this->findFotoViaBanco($idColaborador) ?? '';
        return ['nome' => $nome, 'foto' => $foto];
    }

    public function findNomeCompleto(int $idColaborador): ?string
    {
        $nomeApi = $this->findNomeCompletoViaApi($idColaborador);
        if ($nomeApi !== null && $nomeApi !== '') {
            return $nomeApi;
        }

        return $this->findNomeCompletoViaBanco($idColaborador);
    }

    private function findNomeCompletoViaApi(int $idColaborador): ?string
    {
        try {
            $client = new ConectaOsc3ApiClient();
            $payload = $client->get('/colaboradores/' . $idColaborador);
            $item = $payload['data'] ?? null;
            if (!is_array($item)) {
                return null;
            }

            $nome = trim((string)($item['nome_completo'] ?? ''));
            return $nome !== '' ? $nome : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function findNomeCompletoViaBanco(int $idColaborador): ?string
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT Nome, Sobrenome FROM tbUser WHERE IdColaborador = ?');
        $stmt->execute([$idColaborador]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        $nome = trim(((string)($row['Nome'] ?? '')) . ' ' . ((string)($row['Sobrenome'] ?? '')));
        return $nome !== '' ? $nome : null;
    }

    private function findFotoViaBanco(int $idColaborador): ?string
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT Foto FROM tbUser WHERE IdColaborador = ?');
        $stmt->execute([$idColaborador]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        $foto = trim((string)($row['Foto'] ?? ''));
        return $foto !== '' ? $foto : null;
    }
}
