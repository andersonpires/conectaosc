<style>
    .quick-access-global .quick-access-btn {
        padding-top: 0.45rem !important;
        padding-bottom: 0.45rem !important;
        line-height: 1.2;
        min-height: 44px;
    }
    .quick-access-global .dropdown-menu {
        margin-top: 0.35rem !important;
    }
    @media (max-width: 767.98px) {
        .quick-access-global {
            width: 100%;
            max-width: 400px;
            margin: 20px auto 0;
            padding: 0 !important;
        }
        .quick-access-global .card {
            width: 100%;
        }
        .quick-access-global .card-body {
            padding: 0.95rem;
        }
        .quick-access-global .quick-access-btn {
            font-size: 0.92rem;
        }
        .quick-access-global .dropdown-menu {
            position: static !important;
            transform: none !important;
            inset: auto !important;
            width: 100% !important;
            max-height: 60vh;
            overflow-y: auto;
        }
    }
</style>

<?php
$requestPathQuickAccess = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$isDashboardQuickAccess = (bool) preg_match('#/dashboard/?$#i', $requestPathQuickAccess);
$quickAccessVisibilityClass = $isDashboardQuickAccess ? '' : 'd-xl-none';
?>

<div class="container-fluid p-3 pb-0 <?php echo $quickAccessVisibilityClass; ?> quick-access-global">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1">Acessos r&aacute;pidos</h2>
                    <p class="text-muted mb-0">Atalhos para as &aacute;reas mais usadas no sistema.</p>
                </div>
            </div>
            <div class="row g-2">
                <div class="col-6 d-grid">
                    <a href="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/chamada" class="btn btn-primary text-start quick-access-btn">
                        <span class="d-block fw-bold">Chamada</span>
                    </a>
                </div>
                <div class="col-6 d-grid">
                    <div class="dropdown w-100">
                        <button class="btn btn-success dropdown-toggle w-100 text-start quick-access-btn" type="button" id="dropdownRelatorios" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                            <span class="d-block fw-bold">Relat&oacute;rios</span>
                        </button>
                        <ul class="dropdown-menu w-100 mt-1" aria-labelledby="dropdownRelatorios">
                            <li><a class="dropdown-item" href="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/relatorios/frequencia">Frequ&ecirc;ncia</a></li>
                            <li><a class="dropdown-item" href="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/relatorios/personalizado">Customizar</a></li>
                            <li><a class="dropdown-item" href="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/relatorios/presenca-curso-turma">Presen&ccedil;as</a></li>
                            <li><a class="dropdown-item" href="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/relatorios/matriculados">Matriculados</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-6 d-grid">
                    <div class="dropdown w-100">
                        <button class="btn btn-warning dropdown-toggle w-100 text-start quick-access-btn" type="button" id="dropdownBeneficiarios" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                            <span class="d-block fw-bold">Benefici&aacute;rios</span>
                        </button>
                        <ul class="dropdown-menu w-100 mt-1" aria-labelledby="dropdownBeneficiarios">
                            <li><a class="dropdown-item" href="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/beneficiarios/cadastro">Cadastro</a></li>
                            <li><a class="dropdown-item" href="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/beneficiarios/lista">Listar</a></li>
                            <li><a class="dropdown-item" href="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/beneficiarios/dados">Todos os dados</a></li>
                            <li><a class="dropdown-item" href="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/beneficiarios/lista?msg=%27Selecione+as+pessoas+e+clique+em+Matricular+Aluno%27&matricula=1">Incluir matr&iacute;cula</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-6 d-grid">
                    <div class="dropdown w-100">
                        <button class="btn btn-info dropdown-toggle w-100 text-start quick-access-btn" type="button" id="dropdownAcademico" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                            <span class="d-block fw-bold">Cursos e turmas</span>
                        </button>
                        <ul class="dropdown-menu w-100 mt-1" aria-labelledby="dropdownAcademico">
                            <li><a class="dropdown-item" href="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/cursos">Cursos</a></li>
                            <li><a class="dropdown-item" href="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/turmas">Turmas</a></li>
                            <li><a class="dropdown-item" href="<?php echo rtrim((string) $BASE_para_URL, '/'); ?>/matriculas">Matr&iacute;culas</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
