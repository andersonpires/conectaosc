<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

use DateTime;
use Exception;

final class FeriadoFlow
{
    public function __construct(
        private readonly string $basePath,
        private readonly string $baseUrl
    ) {
    }

    public function handle(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.gc_maxlifetime', '86400');
        }

        require_once $this->basePath . '/app/models/feriados/feriadosModel.php';
        require_once $this->basePath . '/app/models/feriados/BrasilApiService.php';

        $feriadosRoute = rtrim($this->baseUrl, '/') . '/feriados';
        $acao = $_POST['acao'] ?? $_GET['acao'] ?? null;
        $idColaborador = (int) ($_SESSION['Cod'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'verificar') {
            header('Content-Type: application/json; charset=utf-8');
            $this->verificarFeriados();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($acao === 'excluir') {
                $id = (int) ($_POST['IdFeriado'] ?? 0);
                $registro = $id ? \FeriadosModel::getById($id) : null;

                if ($id && $registro && (int) ($registro['IdColaborador'] ?? 0) === $idColaborador && \FeriadosModel::delete($id)) {
                    header("Location: {$feriadosRoute}/?msg=" . urlencode('Feriado excluído com sucesso!'));
                    exit;
                }

                header("Location: {$feriadosRoute}/?erro=" . urlencode('Erro ao excluir feriado.'));
                exit;
            }

            if ($acao === 'salvar') {
                $id = (int) ($_POST['IdFeriado'] ?? 0);
                $nome = trim((string) ($_POST['Nome'] ?? ''));
                $dataFeriado = $this->normalizarDataFeriado($_POST['DataFeriado'] ?? '');

                if ($nome === '') {
                    header("Location: {$feriadosRoute}/?erro=" . urlencode('Informe o nome do feriado.'));
                    exit;
                }
                if (!$dataFeriado) {
                    header("Location: {$feriadosRoute}/?erro=" . urlencode('Informe a data no formato dd/mm.'));
                    exit;
                }
                if ($idColaborador <= 0) {
                    header("Location: {$feriadosRoute}/?erro=" . urlencode('Usuário inválido.'));
                    exit;
                }
                if (\FeriadosModel::existsByNome($nome, $id)) {
                    header("Location: {$feriadosRoute}/?erro=" . urlencode('Já existe um feriado com este nome.'));
                    exit;
                }

                $dados = [
                    'IdFeriado' => $id,
                    'Nome' => $nome,
                    'DataFeriado' => $dataFeriado,
                    'IdColaborador' => $idColaborador,
                ];

                if ($id > 0) {
                    $registro = \FeriadosModel::getById($id);
                    if (!$registro || (int) ($registro['IdColaborador'] ?? 0) !== $idColaborador) {
                        header("Location: {$feriadosRoute}/?erro=" . urlencode('Feriado não encontrado para edição.'));
                        exit;
                    }

                    if (\FeriadosModel::update($dados)) {
                        header("Location: {$feriadosRoute}/?msg=" . urlencode('Feriado atualizado com sucesso!'));
                        exit;
                    }
                    header("Location: {$feriadosRoute}/?erro=" . urlencode('Erro ao atualizar feriado.'));
                    exit;
                }

                if (\FeriadosModel::create($dados)) {
                    header("Location: {$feriadosRoute}/?msg=" . urlencode('Feriado cadastrado com sucesso!'));
                    exit;
                }
                header("Location: {$feriadosRoute}/?erro=" . urlencode('Erro ao cadastrar feriado.'));
                exit;
            }
        }

        if (isset($_GET['msg'])) {
            $_POST['msg'] = urldecode((string) $_GET['msg']);
        }
        if (isset($_GET['erro'])) {
            $_POST['erro'] = urldecode((string) $_GET['erro']);
        }

        $idEdicao = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($idEdicao > 0) {
            $registro = \FeriadosModel::getById($idEdicao);
            if ($registro && (int) ($registro['IdColaborador'] ?? 0) === $idColaborador) {
                foreach ($registro as $key => $value) {
                    $_POST[$key] = $value;
                }
            }
        }

        $listaFeriados = \FeriadosModel::listAll();
        $BASE_para_PATH = $this->basePath;
        $BASE_para_URL = $this->baseUrl;
        require $this->basePath . '/app/views/feriados/formFeriados.php';
        exit;
    }

    private function normalizarDataFeriado(mixed $valor): ?string
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return null;
        }
        if (preg_match('/^\d{2}\/\d{2}$/', $valor)) {
            return $valor;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            $data = DateTime::createFromFormat('Y-m-d', $valor);
            return $data ? $data->format('d/m') : null;
        }
        return null;
    }

    private function verificarFeriados(): void
    {
        $datas = $_POST['datas'] ?? [];
        if (is_string($datas)) {
            $decoded = json_decode($datas, true);
            if (is_array($decoded)) {
                $datas = $decoded;
            }
        }
        if (!is_array($datas)) {
            $datas = [];
        }
        $datas = array_values(array_unique(array_filter($datas, static function ($data): bool {
            return is_string($data) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) === 1;
        })));

        $diasMes = [];
        $anos = [];
        foreach ($datas as $data) {
            $dataObj = DateTime::createFromFormat('Y-m-d', $data);
            if ($dataObj) {
                $diasMes[] = $dataObj->format('d/m');
                $anos[$dataObj->format('Y')] = true;
            }
        }

        $feriadosLocais = \FeriadosModel::getByDiasMes(array_values(array_unique($diasMes)));
        $mapLocal = [];
        foreach ($feriadosLocais as $feriado) {
            $diaMes = $feriado['DataFeriado'] ?? '';
            if ($diaMes !== '') {
                $mapLocal[$diaMes] = $feriado['Nome'] ?? 'Feriado';
            }
        }

        $mapApi = [];
        foreach (array_keys($anos) as $ano) {
            try {
                $feriadosApi = \BrasilApiService::buscarFeriadosNacionais((int) $ano);
            } catch (Exception) {
                $feriadosApi = [];
            }
            foreach ($feriadosApi as $feriado) {
                $dataApi = $feriado['data'] ?? null;
                if ($dataApi && in_array($dataApi, $datas, true)) {
                    $mapApi[$dataApi] = $feriado['nome'] ?? 'Feriado';
                }
            }
        }

        $resultado = [];
        foreach ($datas as $data) {
            $dataObj = DateTime::createFromFormat('Y-m-d', $data);
            $diaMes = $dataObj ? $dataObj->format('d/m') : '';
            if ($diaMes !== '' && isset($mapLocal[$diaMes])) {
                $resultado[$data] = ['feriado' => true, 'nome' => $mapLocal[$diaMes], 'fonte' => 'local'];
            } elseif (isset($mapApi[$data])) {
                $resultado[$data] = ['feriado' => true, 'nome' => $mapApi[$data], 'fonte' => 'api'];
            } else {
                $resultado[$data] = ['feriado' => false, 'nome' => null, 'fonte' => null];
            }
        }

        echo json_encode(['success' => true, 'data' => $resultado], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
