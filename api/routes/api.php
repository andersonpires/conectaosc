<?php
declare(strict_types=1);

use BackEnd\Controllers\AuthController;
use BackEnd\Controllers\AnexoController;
use BackEnd\Controllers\BeneficiarioController;
use BackEnd\Controllers\ColaboradorController;
use BackEnd\Controllers\ConfigController;
use BackEnd\Controllers\CronogramaAulaController;
use BackEnd\Controllers\CursoController;
use BackEnd\Controllers\EventoController;
use BackEnd\Controllers\HealthController;
use BackEnd\Controllers\MatriculaController;
use BackEnd\Controllers\PlanoCursoAulaTodoController;
use BackEnd\Controllers\PlanoCursoController;
use BackEnd\Controllers\RelatorioController;
use BackEnd\Controllers\TurmaController;
use BackEnd\Controllers\TurmaPlanoCursoController;
use BackEnd\Middlewares\SessionAuthMiddleware;
use BackEnd\Models\BeneficiarioModel;
use BackEnd\Models\AnexoModel;
use BackEnd\Models\ColaboradorModel;
use BackEnd\Models\ConfigModel;
use BackEnd\Models\CronogramaAulaModel;
use BackEnd\Models\CursoModel;
use BackEnd\Models\EventoModel;
use BackEnd\Models\MatriculaModel;
use BackEnd\Models\PlanoCursoAulaTodoModel;
use BackEnd\Models\PlanoCursoModel;
use BackEnd\Models\RelatorioModel;
use BackEnd\Models\TurmaModel;
use BackEnd\Models\TurmaPlanoCursoModel;
use BackEnd\Services\BeneficiarioService;
use BackEnd\Services\AnexoService;
use BackEnd\Services\ColaboradorService;
use BackEnd\Services\ConfigService;
use BackEnd\Services\CronogramaAulaService;
use BackEnd\Services\CursoService;
use BackEnd\Services\EventoService;
use BackEnd\Services\MatriculaService;
use BackEnd\Services\PlanoCursoAulaTodoService;
use BackEnd\Services\PlanoCursoService;
use BackEnd\Services\RelatorioService;
use BackEnd\Services\TurmaService;
use BackEnd\Services\TurmaPlanoCursoService;

$healthController = new HealthController();
$authController = new AuthController();
$anexoController = new AnexoController(
    new AnexoService(new AnexoModel())
);
$configController = new ConfigController(
    new ConfigService(new ConfigModel())
);
$cronogramaAulaController = new CronogramaAulaController(
    new CronogramaAulaService(new CronogramaAulaModel())
);
$beneficiarioController = new BeneficiarioController(
    new BeneficiarioService(new BeneficiarioModel())
);
$colaboradorController = new ColaboradorController(
    new ColaboradorService(new ColaboradorModel())
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
$planoCursoController = new PlanoCursoController(
    new PlanoCursoService(new PlanoCursoModel())
);
$planoCursoAulaTodoController = new PlanoCursoAulaTodoController(
    new PlanoCursoAulaTodoService(new PlanoCursoAulaTodoModel())
);
$turmaPlanoCursoController = new TurmaPlanoCursoController(
    new TurmaPlanoCursoService(new TurmaPlanoCursoModel())
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
$router->get('/beneficiarios/busca', [$beneficiarioController, 'search'], [$sessionAuth]);
$router->get('/beneficiarios/{id}', [$beneficiarioController, 'show'], [$sessionAuth]);
$router->delete('/beneficiarios/{id}', [$beneficiarioController, 'destroy'], [$sessionAuth]);
$router->get('/colaboradores-publicos/{id}/nome', [$colaboradorController, 'publicName']);
$router->get('/colaboradores/profissionais-saude', [$colaboradorController, 'profissionaisSaude'], [$sessionAuth]);
$router->get('/colaboradores/{id}', [$colaboradorController, 'show'], [$sessionAuth]);
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
$router->get('/planos-curso', [$planoCursoController, 'index'], [$sessionAuth]);
$router->get('/plano-cursos/resumo', [$planoCursoController, 'resumoCursos'], [$sessionAuth]);
$router->get('/planos-curso/{id}/pdf', [$planoCursoController, 'exportPdf'], [$sessionAuth]);
$router->get('/planos-curso/{id}', [$planoCursoController, 'show'], [$sessionAuth]);
$router->get('/planos-curso/{id}/impacto-exclusao', [$planoCursoController, 'deleteImpact'], [$sessionAuth]);
$router->post('/planos-curso', [$planoCursoController, 'store'], [$sessionAuth]);
$router->put('/planos-curso/{id}', [$planoCursoController, 'update'], [$sessionAuth]);
$router->delete('/planos-curso/{id}', [$planoCursoController, 'destroy'], [$sessionAuth]);
$router->post('/planos-curso/{id}/duplicar', [$planoCursoController, 'duplicate'], [$sessionAuth]);
$router->get('/planos-curso/{id}/aulas', [$planoCursoController, 'listAulas'], [$sessionAuth]);
$router->post('/planos-curso/{id}/aulas', [$planoCursoController, 'storeAula'], [$sessionAuth]);
$router->post('/planos-curso/{id}/aulas/reordenar', [$planoCursoController, 'reorderAulas'], [$sessionAuth]);
$router->put('/planos-curso/aulas/{id}', [$planoCursoController, 'updateAula'], [$sessionAuth]);
$router->delete('/planos-curso/aulas/{id}', [$planoCursoController, 'destroyAula'], [$sessionAuth]);
$router->get('/planos-curso/aulas/{id}/todos', [$planoCursoAulaTodoController, 'listByAula'], [$sessionAuth]);
$router->post('/planos-curso/aulas/{id}/todos', [$planoCursoAulaTodoController, 'store'], [$sessionAuth]);
$router->post('/planos-curso/aulas/{id}/todos/reordenar', [$planoCursoAulaTodoController, 'reorder'], [$sessionAuth]);
$router->put('/planos-curso/aulas/todos/{id}', [$planoCursoAulaTodoController, 'update'], [$sessionAuth]);
$router->put('/planos-curso/aulas/todos/{id}/status', [$planoCursoAulaTodoController, 'updateStatus'], [$sessionAuth]);
$router->delete('/planos-curso/aulas/todos/{id}', [$planoCursoAulaTodoController, 'destroy'], [$sessionAuth]);
$router->get('/planos-curso/aulas/{id}/anexos', [$anexoController, 'listPlanoAula'], [$sessionAuth]);
$router->post('/planos-curso/aulas/{id}/anexos', [$anexoController, 'uploadPlanoAula'], [$sessionAuth]);
$router->get('/turmas/{id}/plano-curso', [$turmaPlanoCursoController, 'show'], [$sessionAuth]);
$router->post('/turmas/{id}/plano-curso/vincular', [$turmaPlanoCursoController, 'vincular'], [$sessionAuth]);
$router->post('/turmas/{id}/plano-curso/desvincular', [$turmaPlanoCursoController, 'desvincular'], [$sessionAuth]);
$router->get('/turmas/{id}/plano-curso/cronograma', [$turmaPlanoCursoController, 'cronograma'], [$sessionAuth]);
$router->get('/cursos/{id}/turmas-com-plano', [$turmaPlanoCursoController, 'listByCurso'], [$sessionAuth]);
$router->get('/cronograma-aulas/{id}/anexos', [$anexoController, 'listCronogramaAula'], [$sessionAuth]);
$router->post('/cronograma-aulas/{id}/anexos', [$anexoController, 'uploadCronogramaAula'], [$sessionAuth]);
$router->put('/cronograma-aulas/{id}/agendar', [$cronogramaAulaController, 'agendar'], [$sessionAuth]);
$router->put('/cronograma-aulas/{id}/status', [$cronogramaAulaController, 'status'], [$sessionAuth]);
$router->get('/cronograma-aulas/{id}/comentarios', [$cronogramaAulaController, 'comentarios'], [$sessionAuth]);
$router->post('/cronograma-aulas/{id}/comentarios', [$cronogramaAulaController, 'comentar'], [$sessionAuth]);
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
