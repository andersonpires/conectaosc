<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: " . rtrim((string)$BASE_para_URL, '/') . "/login/?erro=" . urlencode("Ocorreu um erro! Talvez você tenha perdido sua última ação. Verifique."));
    exit();
}

require_once $BASE_para_PATH . '/api/repositories/CursoRepository.php';
require_once $BASE_para_PATH . '/api/services/CursoService.php';

function cursoReturnQuery(): string
{
    $situacao = '';
    $porPagina = null;

    if (isset($_POST['_return_query'])) {
        $params = [];
        parse_str((string) $_POST['_return_query'], $params);
        if (isset($params['situacao'])) {
            $situacao = trim((string) $params['situacao']);
        }
        if (isset($params['porPagina']) && is_numeric($params['porPagina'])) {
            $valor = (int) $params['porPagina'];
            if ($valor > 0 || $valor === -1) {
                $porPagina = $valor;
            }
        }
    }

    if (($situacao === '' || $porPagina === null) && isset($_SERVER['HTTP_REFERER'])) {
        $query = parse_url((string) $_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
        if (is_string($query) && $query !== '') {
            $params = [];
            parse_str($query, $params);
            if ($situacao === '' && isset($params['situacao'])) {
                $situacao = trim((string) $params['situacao']);
            }
            if ($porPagina === null && isset($params['porPagina']) && is_numeric($params['porPagina'])) {
                $valor = (int) $params['porPagina'];
                if ($valor > 0 || $valor === -1) {
                    $porPagina = $valor;
                }
            }
        }
    }

    $queryParams = [];
    if (in_array($situacao, ['Ativo', 'Concluido', 'Todos'], true)) {
        $queryParams['situacao'] = $situacao;
    }
    if ($porPagina !== null) {
        $queryParams['porPagina'] = (string) $porPagina;
    }

    return http_build_query($queryParams);
}

try {
    $idCurso = (int) ($_POST['id'] ?? 0);
    $service = new \BackEnd\Services\CursoService(new \BackEnd\Repositories\CursoRepository());
    $updated = $service->update($idCurso, $_POST);
    $returnQuery = cursoReturnQuery();
    $sep = $returnQuery !== '' ? '&' : '';

    if ($updated) {
        $resultado = 'Registro%20alterado%20com%20sucesso';
        header("Location: " . rtrim((string)$BASE_para_URL, '/') . "/cursos/?" . $returnQuery . $sep . "msg=$resultado");
    } else {
        $resultado = 'Registro%20sem%20alteracao.';
        header("Location: " . rtrim((string)$BASE_para_URL, '/') . "/cursos/?" . $returnQuery . $sep . "erro=$resultado");
    }
    exit();
} catch (Throwable $e) {
    $resultado = urlencode('Erro ao alterar curso: ' . $e->getMessage());
    $returnQuery = cursoReturnQuery();
    $sep = $returnQuery !== '' ? '&' : '';
    header("Location: " . rtrim((string)$BASE_para_URL, '/') . "/cursos/?" . $returnQuery . $sep . "erro=$resultado");
    exit();
}
?>








