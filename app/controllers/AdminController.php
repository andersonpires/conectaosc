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
        $controller = new ColaboradorController($this->basePath);
        $controller->index();
    }

    public function colaboradoresResetSenha(): void
    {
        $controller = new ColaboradorController($this->basePath);
        $controller->resetSenha();
    }

    public function permissoes(): void
    {
        $controller = new PermissaoController($this->basePath);
        $controller->index();
    }

    public function permissaoPaginas(): void
    {
        $controller = new PermissaoController($this->basePath);
        $controller->paginas();
    }

    public function perfil(): void
    {
        $controller = new PerfilController($this->basePath);
        $controller->index();
    }

    public function crm(): void
    {
        $controller = new CrmController($this->basePath);
        $controller->index();
    }

    public function crmSalvar(): void
    {
        $controller = new CrmController($this->basePath);
        $controller->salvar();
    }

    public function atendimento(): void
    {
        $controller = new AtendimentoController($this->basePath);
        $controller->index();
    }

    public function atendimentoSalvar(): void
    {
        $controller = new AtendimentoController($this->basePath);
        $controller->salvar();
    }

    public function chamada(): void
    {
        $controller = new ChamadaController($this->basePath);
        $controller->tipo();
    }

    public function chamadaLista(): void
    {
        $controller = new ChamadaController($this->basePath);
        $controller->lista();
    }

    public function chamadaFaltas(): void
    {
        $controller = new ChamadaController($this->basePath);
        $controller->faltas();
    }

    public function chamadaSalvar(): void
    {
        $controller = new ChamadaController($this->basePath);
        $controller->salvar();
    }

    public function swot(): void
    {
        $controller = new SwotController($this->basePath);
        $controller->index();
    }

    public function feriados(): void
    {
        $controller = new FeriadoController($this->basePath);
        $controller->index();
    }

    public function assinaturaPdf(): void
    {
        $controller = new AssinaturaController($this->basePath);
        $controller->upload();
    }

    public function assinaturaPdfPreview(): void
    {
        $controller = new AssinaturaController($this->basePath);
        $controller->preview();
    }

    public function assinaturaPdfFinalizar(): void
    {
        $controller = new AssinaturaController($this->basePath);
        $controller->finalizar();
    }

    public function assinaturaPdfValidar(): void
    {
        $controller = new AssinaturaController($this->basePath);
        $controller->validar();
    }

    public function backup(): void
    {
        $controller = new BackupController($this->basePath);
        $controller->configuracao();
    }

    public function backupDados(): void
    {
        $controller = new BackupController($this->basePath);
        $controller->dados();
    }

    public function alerta(): void
    {
        $controller = new AlertaController($this->basePath);
        $controller->index();
    }

    public function alertaSalvar(): void
    {
        $controller = new AlertaController($this->basePath);
        $controller->salvar();
    }

    public function projetos(): void
    {
        $controller = new ProjetoController($this->basePath);
        $controller->index();
    }

    public function matriculasTurma(): void
    {
        $controller = new MatriculaController($this->basePath);
        $controller->turma();
    }

    public function matriculasResultado(): void
    {
        $controller = new MatriculaController($this->basePath);
        $controller->resultado();
    }

    public function matriculasCadastrar(): void
    {
        $controller = new MatriculaController($this->basePath);
        $controller->cadastrar();
    }

    public function matriculasAlterar(): void
    {
        $controller = new MatriculaController($this->basePath);
        $controller->alterar();
    }

    public function matriculasExcluir(): void
    {
        $controller = new MatriculaController($this->basePath);
        $controller->excluir();
    }

    public function matriculasContrato(): void
    {
        $controller = new MatriculaController($this->basePath);
        $controller->contrato();
    }

    public function matriculasContratosTurma(): void
    {
        $controller = new MatriculaController($this->basePath);
        $controller->contratosTurma();
    }

    public function matriculasContratosCurso(): void
    {
        $controller = new MatriculaController($this->basePath);
        $controller->contratosCurso();
    }

    public function configuracoes(): void
    {
        $controller = new ConfiguracaoController($this->basePath);
        $controller->index();
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
        header('Location: ' . rtrim($this->baseUrl, '/') . $path);
        exit;
    }
}


