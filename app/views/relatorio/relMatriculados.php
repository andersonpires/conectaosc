<?php
require_once __DIR__ . '/../../../bootstrap/runtime.php';
$runtime = bootstrap_runtime();
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$appJsVersion = @filemtime($BASE_para_PATH . '/app/assets/js/app.js') ?: time();

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}
session_regenerate_id(true);

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/repositories/RelatorioRepository.php';
require_once $BASE_para_PATH . '/api/services/RelatorioService.php';

$relatorioService = new \BackEnd\Services\RelatorioService(new \BackEnd\Repositories\RelatorioRepository());
$cursos = array_values(array_filter(
    $relatorioService->cursosOptions(0),
    static fn(array $row): bool => is_numeric((string) ($row['value'] ?? ''))
));
$assetsFotosBaseUrl = rtrim(bootstrap_assets_img_url(), '/') . '/fotos';
$avatarPadraoUrl = bootstrap_foto_url('');
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            border: 1px solid #000 !important;
        }

        thead {
            text-align: center;
            color: white;
        }

        th,
        td {
            border: 1px solid #000 !important;
            padding: 10px;
            text-align: center !important;
            vertical-align: middle;
        }

        th {
            background-color: rgb(83, 37, 126) !important;
        }

        tr:nth-child(even) {
            background-color: rgb(205, 241, 213);
        }

        tr:nth-child(odd) {
            background-color: #fff;
        }

        .foto-matriculado {
            display: block;
            max-width: 30px;
            max-height: 30px;
            width: auto !important;
            height: auto !important;
            margin: 0 auto;
            border-radius: 4px;
        }

        #relatorio {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 767.98px) {
            .content .card-body {
                padding: 1rem .75rem;
            }

            .col-12.col-md-6 {
                width: 100%;
                max-width: 100%;
            }

            #filterForm .btn {
                width: 100%;
                margin-bottom: .5rem;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>

            <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>
            <main class="content">
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3">Gerar Relatório de Matriculados</h1>
                    <div class="col-12 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <form id="filterForm">
                                    <div class="mb-3">
                                        <label for="curso" class="form-label">Curso:</label>
                                        <select id="curso" name="curso" class="form-select">
                                            <option value="">Selecione o curso</option>
                                            <?php foreach ($cursos as $curso): ?>
                                                <option value="<?= htmlspecialchars((string) $curso['value'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $curso['label'], ENT_QUOTES, 'UTF-8') ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="turma" class="form-label">Turma:</label>
                                        <select id="turma" name="turma" class="form-select">
                                            <option value="">Selecione um curso primeiro</option>
                                        </select>
                                    </div>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" role="switch" id="mostrarFoto" name="mostrarFoto">
                                        <label class="form-check-label" for="mostrarFoto">Exibir foto do aluno</label>
                                    </div>

                                    <button type="button" id="gerarRelatorio" class="btn btn-primary">Gerar relatório</button>
                                    <button type="button" id="salvarPDF" style="display: none;" class="btn btn-primary">Salvar PDF</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="relatorio"></div>
            </main>
            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>

            <script>
                const fotosBaseUrl = <?php echo json_encode($assetsFotosBaseUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
                const avatarPadraoUrl = <?php echo json_encode($avatarPadraoUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

                function escapeHtml(value) {
                    return String(value ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function fotoUrl(foto) {
                    const nomeArquivo = String(foto ?? '').trim();
                    if (!nomeArquivo) return avatarPadraoUrl;
                    if (/^https?:\/\//i.test(nomeArquivo) || nomeArquivo.startsWith('data:')) return nomeArquivo;
                    const partes = nomeArquivo.split(/[\\/]/);
                    const nomeNormalizado = partes[partes.length - 1];
                    return `${fotosBaseUrl}/${encodeURIComponent(nomeNormalizado)}`;
                }

                function initCursoTomSelect() {
                    const element = document.getElementById('curso');
                    if (!element || typeof TomSelect === 'undefined') {
                        return;
                    }

                    if (element.tomselect) {
                        element.tomselect.destroy();
                    }

                    new TomSelect(element, {
                        create: false,
                        allowEmptyOption: true,
                        searchField: ['text'],
                        placeholder: 'Selecione o curso',
                    });
                }

                $('#gerarRelatorio').click(function(e) {
                    e.preventDefault();

                    const curso = $('#curso').val();
                    const turma = $('#turma').val();
                    const mostrarFoto = $('#mostrarFoto').is(':checked');
                    const getMatricula = "<?php echo $BASE_para_URL; ?>/get/getMatricula.php";

                    $.post(getMatricula, {
                        curso,
                        turma
                    }, function(data) {
                        $('#relatorio').empty();

                        let table = `
                <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            ${mostrarFoto ? '<th>Foto</th>' : ''}
                            <th>Matriculados</th>
                            <th>Nascimento</th>
                            <th>Endereço</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

                        data.forEach((row, index) => {
                            const fotoCell = mostrarFoto
                                ? `<td><img src="${escapeHtml(fotoUrl(row.Foto))}" class="foto-matriculado" alt="${escapeHtml(row.Nome)}"></td>`
                                : '';
                            const endereco = [row.Endereco, row.Bairro, row.Cidade, row.UF].filter(Boolean).join(' - ');

                            table += `
                            <tr>
                                <td>${index + 1}</td>
                                ${fotoCell}
                                <td>${escapeHtml(row.Nome)}</td>
                                <td>${escapeHtml(row.Nascimento)}</td>
                                <td>${escapeHtml(endereco)}</td>
                            </tr>
                        `;
                        });

                        table += `
                    </tbody>
                </table>
            `;

                        $('#relatorio').html(table);
                        $('#salvarPDF').show();
                    }, 'json').fail(function() {
                        alert('Erro ao carregar os dados.');
                    });
                });

                $('#salvarPDF').click(function(e) {
                    e.preventDefault();

                    const curso = $('#curso').val();
                    const turma = $('#turma').val();
                    const mostrarFoto = $('#mostrarFoto').is(':checked') ? 1 : 0;
                    const getPDF = "<?php echo $BASE_para_URL; ?>/get/";
                    const url = getPDF + `getMatriculaPDF.php?curso=${curso}&turma=${turma}&mostrarFoto=${mostrarFoto}`;
                    window.open(url, '_blank');
                });

                $(document).ready(function() {
                    initCursoTomSelect();
                });
            </script>
        </div>
    </div>
</body>
<script>
    async function carregarTurmas(idCurso) {
        const turmaSelect = document.getElementById('turma');
        turmaSelect.innerHTML = '<option value="">Carregando turmas...</option>';

        if (!idCurso) {
            turmaSelect.innerHTML = '<option value="">Selecione um curso primeiro</option>';
            return;
        }

        try {
            const formData = new FormData();
            formData.append('IdCurso', idCurso);
            const getTurmas = "<?php echo $BASE_para_URL; ?>/get/getTurmas.php";
            const response = await fetch(getTurmas, {
                method: 'POST',
                body: formData,
            });

            const turmasHtml = await response.text();
            turmaSelect.innerHTML = turmasHtml;
        } catch (error) {
            turmaSelect.innerHTML = '<option value="">Erro ao carregar turmas</option>';
            console.error('Erro ao carregar turmas:', error);
        }
    }
</script>
<script>
    $('#curso').change(function() {
        const idCurso = $(this).val();
        const turmaSelect = $('#turma');
        turmaSelect.html('<option value="">Carregando turmas...</option>');

        if (!idCurso) {
            turmaSelect.html('<option value="">Selecione um curso primeiro</option>');
            return;
        }

        const getTurmas = "<?php echo $BASE_para_URL; ?>/get/getTurmas.php";
        $.post(getTurmas, {
            IdCurso: idCurso
        }, function(data) {
            turmaSelect.html(data);
        }).fail(function() {
            turmaSelect.html('<option value="">Erro ao carregar turmas</option>');
        });
    });
</script>

</html>
