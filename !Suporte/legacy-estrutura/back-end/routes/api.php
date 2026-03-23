<?php
declare(strict_types=1);

use BackEnd\Controllers\AuthController;
use BackEnd\Controllers\BeneficiarioController;
use BackEnd\Controllers\ConfigController;
use BackEnd\Controllers\CursoController;
use BackEnd\Controllers\EventoController;
use BackEnd\Controllers\HealthController;
use BackEnd\Controllers\MatriculaController;
use BackEnd\Controllers\RelatorioController;
use BackEnd\Controllers\TurmaController;
use BackEnd\Middlewares\SessionAuthMiddleware;
use BackEnd\Models\ConfigModel;
use BackEnd\Models\CursoModel;
use BackEnd\Models\EventoModel;
use BackEnd\Models\BeneficiarioModel;
use BackEnd\Models\MatriculaModel;
use BackEnd\Models\RelatorioModel;
use BackEnd\Models\TurmaModel;
use BackEnd\Services\ConfigService;
use BackEnd\Services\CursoService;
use BackEnd\Services\EventoService;
use BackEnd\Services\BeneficiarioService;
use BackEnd\Services\MatriculaService;
use BackEnd\Services\RelatorioService;
use BackEnd\Services\TurmaService;

$healthController = new HealthController();
$authController = new AuthController();
$configController = new ConfigController(
    new ConfigService(new ConfigModel())
);
$beneficiarioController = new BeneficiarioController(
    new BeneficiarioService(new BeneficiarioModel())
);
$cursoController = new CursoController(
    new CursoService(new CursoModel())
);
$turmaController = new TurmaController(
    new TurmaService(new TurmaModel())
);
$matriculaController = new MatriculaController(
    new MatriculaService(new MatriculaModel())
);
$relatorioController = new RelatorioController(
    new RelatorioService(new RelatorioModel())
);
$eventoController = new EventoController(
    new EventoService(new EventoModel())
);
$sessionAuth = new SessionAuthMiddleware();

$router->get('/', [$healthController, 'index']);
$router->get('/health', [$healthController, 'index']);
$router->get('/me', [$authController, 'me'], [$sessionAuth]);
$router->get('/config', [$configController, 'index'], [$sessionAuth]);
$router->put('/config', [$configController, 'update'], [$sessionAuth]);
$router->get('/beneficiarios/resumo-ativos', [$beneficiarioController, 'resumoAtivos'], [$sessionAuth]);
$router->get('/beneficiarios/detalhado-ativos', [$beneficiarioController, 'detalhadoAtivos'], [$sessionAuth]);
$router->delete('/beneficiarios/{id}', [$beneficiarioController, 'destroy'], [$sessionAuth]);
$router->get('/cursos', [$cursoController, 'index'], [$sessionAuth]);
$router->get('/cursos/ativos', [$cursoController, 'ativos'], [$sessionAuth]);
$router->post('/cursos', [$cursoController, 'store'], [$sessionAuth]);
$router->put('/cursos/{id}', [$cursoController, 'update'], [$sessionAuth]);
$router->delete('/cursos/{id}', [$cursoController, 'destroy'], [$sessionAuth]);
$router->get('/projetos', [$cursoController, 'projetos'], [$sessionAuth]);
$router->get('/turmas', [$turmaController, 'index'], [$sessionAuth]);
$router->get('/turmas/por-curso', [$turmaController, 'porCurso'], [$sessionAuth]);
$router->post('/turmas', [$turmaController, 'store'], [$sessionAuth]);
$router->put('/turmas/{id}', [$turmaController, 'update'], [$sessionAuth]);
$router->delete('/turmas/{id}', [$turmaController, 'destroy'], [$sessionAuth]);
$router->post('/matriculas/lote', [$matriculaController, 'storeLote'], [$sessionAuth]);
$router->get('/matriculas/por-turma', [$matriculaController, 'porTurma'], [$sessionAuth]);
$router->post('/matriculas/{id}/ativar', [$matriculaController, 'ativar'], [$sessionAuth]);
$router->put('/matriculas/{id}', [$matriculaController, 'update'], [$sessionAuth]);
$router->delete('/matriculas/{id}', [$matriculaController, 'destroy'], [$sessionAuth]);
$router->post('/relatorios/cursos/options', [$relatorioController, 'cursosOptions'], [$sessionAuth]);
$router->post('/relatorios/turmas/options', [$relatorioController, 'turmasOptions'], [$sessionAuth]);
$router->get('/relatorios/projetos', [$relatorioController, 'projetos'], [$sessionAuth]);
$router->post('/relatorios/custom-alunos', [$relatorioController, 'customAlunos'], [$sessionAuth]);
$router->post('/relatorios/custom-alunos/html', [$relatorioController, 'customAlunosHtml'], [$sessionAuth]);
$router->post('/relatorios/presenca-curso-turma', [$relatorioController, 'presencaCursoTurma'], [$sessionAuth]);
$router->post('/relatorios/presenca-curso-turma/html', [$relatorioController, 'presencaCursoTurmaHtml'], [$sessionAuth]);
$router->post('/relatorios/matriculados', [$relatorioController, 'matriculados'], [$sessionAuth]);
$router->post('/relatorios/frequencia/mensal', [$relatorioController, 'frequenciaMensal'], [$sessionAuth]);
$router->post('/relatorios/frequencia/intervalo', [$relatorioController, 'frequenciaIntervalo'], [$sessionAuth]);
$router->get('/relatorios/frequencia/mensal', [$relatorioController, 'frequenciaMensal'], [$sessionAuth]);
$router->get('/relatorios/frequencia/intervalo', [$relatorioController, 'frequenciaIntervalo'], [$sessionAuth]);
$router->get('/eventos/inscritos', [$eventoController, 'index'], [$sessionAuth]);
$router->get('/eventos/inscritos/{id}', [$eventoController, 'show'], [$sessionAuth]);
$router->get('/eventos/inscritos/email-exists', [$eventoController, 'emailExists']);
$router->post('/eventos/inscritos', [$eventoController, 'store']);
$router->put('/eventos/inscritos/{id}', [$eventoController, 'update'], [$sessionAuth]);
$router->delete('/eventos/inscritos/{id}', [$eventoController, 'destroy'], [$sessionAuth]);
