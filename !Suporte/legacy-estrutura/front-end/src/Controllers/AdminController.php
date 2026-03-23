<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class AdminController
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $basePath
    )
    {
    }

    public function colaboradores(): void
    {
        $this->render('/app/colaborador/colaboradorController.php');
    }

    public function colaboradoresResetSenha(): void
    {
        $this->render('/app/colaborador/resetSenha.php');
    }

    public function permissoes(): void
    {
        $_GET['view'] = $_GET['view'] ?? 'list';
        $this->render('/app/permissao/permissaoController.php');
    }

    public function permissaoPaginas(): void
    {
        $this->render('/app/permissao/formPaginas.php');
    }

    public function perfil(): void
    {
        $this->render('/app/perfil/perfilController.php');
    }

    public function crm(): void
    {
        $this->render('/app/crm/crm.php');
    }

    public function crmSalvar(): void
    {
        $this->render('/app/crm/crmController.php');
    }

    public function atendimento(): void
    {
        $this->render('/app/atendimento/index.php');
    }

    public function atendimentoSalvar(): void
    {
        $this->render('/app/atendimento/crmController.php');
    }

    public function chamada(): void
    {
        $this->render('/app/chamada/listTipoChamada.php');
    }

    public function chamadaLista(): void
    {
        $this->render('/app/chamada/listChamada.php');
    }

    public function chamadaFaltas(): void
    {
        $this->render('/app/chamada/listTotalChamada.php');
    }

    public function chamadaSalvar(): void
    {
        $this->render('/app/chamada/savebanco.php');
    }

    public function swot(): void
    {
        $this->render('/app/swot/swotController.php');
    }

    public function feriados(): void
    {
        $this->render('/app/feriados/feriadosController.php');
    }

    public function assinaturaPdf(): void
    {
        $this->render('/app/assinatura/PDFupload.php');
    }

    public function assinaturaPdfPreview(): void
    {
        $this->render('/app/assinatura/PDFpreview.php');
    }

    public function assinaturaPdfFinalizar(): void
    {
        $this->render('/app/assinatura/PDFfinaliza.php');
    }

    public function assinaturaPdfValidar(): void
    {
        $this->render('/app/assinatura/verPDF.php');
    }

    public function backup(): void
    {
        $this->render('/app/backup/configBackup.php');
    }

    public function backupDados(): void
    {
        $this->render('/app/backup/backup.php');
    }

    public function alerta(): void
    {
        $this->render('/app/alerta/alerta.php');
    }

    public function alertaSalvar(): void
    {
        $this->render('/app/alerta/cadAlerta.php');
    }

    public function projetos(): void
    {
        $this->render('/app/projeto/cadastro_projeto.php');
    }

    public function matriculasTurma(): void
    {
        $this->render('/app/matricula/listTurmasMat.php');
    }

    public function matriculasResultado(): void
    {
        $this->render('/app/matricula/resultadoMatriculas.php');
    }

    public function matriculasCadastrar(): void
    {
        $this->render('/app/matricula/cadMatricula.php');
    }

    public function matriculasAlterar(): void
    {
        $this->render('/app/matricula/alteraMatricula.php');
    }

    public function matriculasExcluir(): void
    {
        $this->render('/app/matricula/excluirMatricula.php');
    }

    public function matriculasContrato(): void
    {
        $this->render('/app/matricula/gerarContrato.php');
    }

    public function matriculasContratosTurma(): void
    {
        $this->render('/app/matricula/gerarContratosTurma.php');
    }

    public function matriculasContratosCurso(): void
    {
        $this->render('/app/matricula/gerarContratosCurso.php');
    }

    public function configuracoes(): void
    {
        $this->render('/app/config/formConfig.php');
    }

    private function render(string $legacyPath): void
    {
        $candidate = $this->basePath . $legacyPath;
        if (is_file($candidate)) {
            require $candidate;
            return;
        }
        http_response_code(404);
        echo 'Pagina nao encontrada';
    }

    private function redirect(string $path): void
    {
        header('Location: ' . rtrim($this->baseUrl, '/') . $path);
        exit;
    }
}
