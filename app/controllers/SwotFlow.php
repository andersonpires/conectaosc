<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class SwotFlow
{
    public function __construct(
        private readonly string $basePath,
        private readonly string $baseUrl
    ) {
    }

    public function handle(): void
    {
        require_once $this->basePath . '/app/models/swot/swotModel.php';

        $swotRoute = rtrim($this->baseUrl, '/') . '/swot';
        $temasDisponiveis = [
            'INOVAÇÃO EM PROJETOS EXISTENTES',
            'NOVOS PROJETOS PARA INVESTIDORES',
            'NOVOS SERVIÇOS',
            'NOVOS PRODUTOS',
        ];

        $temaPadrao = $temasDisponiveis[0];
        $acao = $_POST['acao'] ?? null;
        $idColaborador = (int) ($_SESSION['Cod'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($acao === 'excluir') {
                $id = (int) ($_POST['IdSwot'] ?? 0);
                $registro = $id ? \SwotModel::getById($id) : null;

                if ($id && $registro && (int) ($registro['IdColaborador'] ?? 0) === $idColaborador && \SwotModel::delete($id)) {
                    header("Location: {$swotRoute}/?msg=" . urlencode('Insight excluído com sucesso!'));
                    exit;
                }

                header("Location: {$swotRoute}/?erro=" . urlencode('Erro ao excluir insight.'));
                exit;
            }

            if ($acao === 'salvar') {
                $id = (int) ($_POST['IdSwot'] ?? 0);
                $tema = trim((string) ($_POST['Tema'] ?? $temaPadrao));
                if (!in_array($tema, $temasDisponiveis, true)) {
                    $tema = $temaPadrao;
                }
                $texto = trim((string) ($_POST['Texto'] ?? ''));

                if ($texto === '') {
                    header("Location: {$swotRoute}/?tema=" . urlencode($tema) . '&erro=' . urlencode('Informe o insight antes de enviar.'));
                    exit;
                }
                if ($idColaborador <= 0) {
                    header("Location: {$swotRoute}/?tema=" . urlencode($tema) . '&erro=' . urlencode('Usuário inválido.'));
                    exit;
                }

                $dados = [
                    'IdSwot' => $id,
                    'Tema' => $tema,
                    'Texto' => $texto,
                    'IdColaborador' => $idColaborador,
                ];

                if ($id > 0) {
                    $registro = \SwotModel::getById($id);
                    if (!$registro || (int) ($registro['IdColaborador'] ?? 0) !== $idColaborador) {
                        header("Location: {$swotRoute}/?tema=" . urlencode($tema) . '&erro=' . urlencode('Insight não encontrado para edição.'));
                        exit;
                    }

                    if (\SwotModel::update($dados)) {
                        header("Location: {$swotRoute}/?tema=" . urlencode($tema) . '&msg=' . urlencode('Insight atualizado com sucesso!'));
                        exit;
                    }

                    header("Location: {$swotRoute}/?tema=" . urlencode($tema) . '&erro=' . urlencode('Erro ao atualizar insight.'));
                    exit;
                }

                if (\SwotModel::create($dados)) {
                    header("Location: {$swotRoute}/?tema=" . urlencode($tema) . '&msg=' . urlencode('Insight cadastrado com sucesso!'));
                    exit;
                }

                header("Location: {$swotRoute}/?tema=" . urlencode($tema) . '&erro=' . urlencode('Erro ao cadastrar insight.'));
                exit;
            }
        }

        if (isset($_GET['msg'])) {
            $_POST['msg'] = urldecode((string) $_GET['msg']);
        }
        if (isset($_GET['erro'])) {
            $_POST['erro'] = urldecode((string) $_GET['erro']);
        }
        if (isset($_GET['tema'])) {
            $_POST['Tema'] = urldecode((string) $_GET['tema']);
        }

        $idEdicao = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($idEdicao > 0) {
            $registro = \SwotModel::getById($idEdicao);
            if ($registro && (int) ($registro['IdColaborador'] ?? 0) === $idColaborador) {
                foreach ($registro as $key => $value) {
                    $_POST[$key] = $value;
                }
            }
        }

        $listaSwot = \SwotModel::listByUser($idColaborador);
        if (empty($_POST['Tema'])) {
            $_POST['Tema'] = $temaPadrao;
        }

        $BASE_para_PATH = $this->basePath;
        $BASE_para_URL = $this->baseUrl;
        require $this->basePath . '/app/views/swot/formSwot.php';
        exit;
    }
}
