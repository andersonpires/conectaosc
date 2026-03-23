<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\JsonResponse;
use App\Middlewares\AuthMiddleware;
use App\Services\BrasilApiFeriadosService;

/**
 * Verifica se datas são feriados (tb_feriados + Brasil API).
 * Mesmo modelo de conectaosc/app/chamada/listTotalChamada.php
 */
class FeriadosController
{
    public function verificar(): void
    {
        AuthMiddleware::requireAuth();

        $datasParam = $_GET['datas'] ?? '';
        if (!is_string($datasParam) || trim($datasParam) === '') {
            JsonResponse::success(['feriados' => []]);
        }

        $datas = array_values(array_unique(array_filter(
            array_map('trim', explode(',', $datasParam)),
            fn($d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)
        )));

        if (empty($datas)) {
            JsonResponse::success(['feriados' => []]);
        }

        $resultado = [];
        $anos = array_unique(array_map(fn($d) => substr($d, 0, 4), $datas));

        // 1. Buscar em tb_feriados (DataFeriado formato dd/mm)
        $mapLocal = [];
        $dataToDdmm = [];
        foreach ($datas as $data) {
            $dt = \DateTime::createFromFormat('Y-m-d', $data);
            if ($dt) {
                $dataToDdmm[$data] = $dt->format('d/m');
            }
        }
        $diasMesUnicos = array_values(array_unique($dataToDdmm));
        if (!empty($diasMesUnicos)) {
            try {
                $pdo = Database::getConnection();
                $placeholders = implode(',', array_fill(0, count($diasMesUnicos), '?'));
                $stmt = $pdo->prepare("SELECT DataFeriado, Nome FROM tb_feriados WHERE DataFeriado IN ($placeholders)");
                $stmt->execute($diasMesUnicos);
                $feriadosLocais = [];
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $feriadosLocais[$row['DataFeriado']] = $row['Nome'] ?? 'Feriado';
                }
                foreach ($dataToDdmm as $dataStr => $ddmm) {
                    if (isset($feriadosLocais[$ddmm])) {
                        $mapLocal[$dataStr] = $feriadosLocais[$ddmm];
                    }
                }
            } catch (\Throwable $e) {
                // Tabela pode não existir
            }
        }

        // 2. Buscar na Brasil API (datas YYYY-MM-DD)
        $mapApi = [];
        foreach ($anos as $ano) {
            try {
                $feriados = BrasilApiFeriadosService::buscarFeriadosNacionais((int)$ano);
                foreach ($feriados as $dataApi => $nome) {
                    if (in_array($dataApi, $datas, true)) {
                        $mapApi[$dataApi] = $nome;
                    }
                }
            } catch (\Throwable $e) {
                // Ignorar falha da API externa
            }
        }

        // 3. Montar resultado (local tem prioridade sobre API)
        foreach ($datas as $data) {
            if (isset($mapLocal[$data])) {
                $resultado[$data] = ['feriado' => true, 'nome' => $mapLocal[$data], 'fonte' => 'local'];
            } elseif (isset($mapApi[$data])) {
                $resultado[$data] = ['feriado' => true, 'nome' => $mapApi[$data], 'fonte' => 'api'];
            } else {
                $resultado[$data] = ['feriado' => false, 'nome' => null, 'fonte' => null];
            }
        }

        JsonResponse::success(['feriados' => $resultado]);
    }
}
