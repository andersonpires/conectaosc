<?php
if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    require_once __DIR__ . '/../../../bootstrap/runtime.php';
    $runtime = bootstrap_runtime();
    $BASE_para_PATH = $runtime['base_para_path'];
    $BASE_para_URL = $runtime['base_para_url'];
}

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';

$temas = [
    ['valor' => 'INOVAÇÃO EM PROJETOS EXISTENTES', 'cor' => 'primary'],
    ['valor' => 'NOVOS PROJETOS PARA INVESTIDORES', 'cor' => 'info'],
    ['valor' => 'NOVOS SERVIÇOS', 'cor' => 'danger'],
    ['valor' => 'NOVOS PRODUTOS', 'cor' => 'warning']
];

$temaSelecionado = $_POST['Tema'] ?? $temas[0]['valor'];
$swotRoute = rtrim((string)($SWOT_ROUTE ?? (rtrim((string)$BASE_para_URL, '/') . '/swot')), '/');
$appJsVersion = @filemtime($BASE_para_PATH . '/app/assets/js/app.js') ?: time();

function corTemaSwot($tema, $temas)
{
    foreach ($temas as $item) {
        if ($item['valor'] === $tema) {
            return $item['cor'];
        }
    }
    return 'secondary';
}

function previewTextoSwot($texto, $limite = 200)
{
    $texto = html_entity_decode((string) $texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $texto = trim(preg_replace('/\s+/', ' ', strip_tags($texto)));
    if ($texto === '') {
        return '-';
    }
    $preview = mb_substr($texto, 0, $limite, 'UTF-8');
    if (mb_strlen($texto, 'UTF-8') > $limite) {
        $preview .= '...';
    }
    return $preview;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <style>
        .swot-theme-btn {
            min-width: 220px;
            margin-bottom: 8px;
        }
        .swot-theme-btn.btn-outline-secondary {
            opacity: 0.6;
        }
        .swot-preview-text {
            max-width: 520px;
            white-space: normal;
            line-height: 1.4;
        }

        /* Post-it style */
        .swot-postit-wrap {
            position: relative;
            display: block;
            margin: 6px 0 18px;
            padding: 18px 20px 22px;
            background: linear-gradient(180deg, #fff1a8 0%, #ffe36f 100%);
            border: 1px solid #f0d25a;
            border-radius: 10px;
            box-shadow: 0 10px 18px rgba(0, 0, 0, 0.12);
        }
        .swot-postit-wrap::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 14px;
            width: 70px;
            height: 18px;
            background: rgba(255, 255, 255, 0.55);
            border-radius: 2px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
            transform: rotate(-6deg);
            transform-origin: center;
        }
        .swot-postit-wrap::after {
            content: '';
            position: absolute;
            top: 10px;
            right: 14px;
            width: 70px;
            height: 18px;
            background: rgba(255, 255, 255, 0.55);
            border-radius: 2px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
            transform: rotate(6deg);
            transform-origin: center;
        }
        .swot-postit-pin {
            position: absolute;
            top: 6px;
            left: 50%;
            width: 18px;
            height: 18px;
            background: radial-gradient(circle at 35% 35%, #ffffff 0%, #f3f3f3 40%, #d66b6b 65%, #a63b3b 100%);
            border: 1px solid rgba(140, 40, 40, 0.7);
            border-radius: 50%;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.18);
            transform: translateX(-50%);
        }
        .swot-postit-pin::after {
            content: '';
            position: absolute;
            top: 7px;
            left: 50%;
            width: 2px;
            height: 16px;
            background: rgba(120, 120, 120, 0.8);
            transform: translateX(-50%);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
        }
        .swot-postit {
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
            font-family: "Segoe Print", "Bradley Hand", "Comic Sans MS", cursive;
            font-size: 16px;
            line-height: 1.6;
            color: #6f6f6f;
            margin-top: 20px;
        }
        .swot-postit::placeholder {
            color: #8b8b8b;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>
        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
            <main class="content">
                <div class="container-fluid p-0">
                    <?php
                    $msg = $_POST['msg'] ?? '';
                    $erro = $_POST['erro'] ?? '';
                    if ($msg) {
                        echo '<div class="alert alert-success" role="alert">' . htmlspecialchars($msg) . '</div>';
                    } elseif ($erro) {
                        echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($erro) . '</div>';
                    }
                    ?>

                    <h1 class="h3 mb-3">SWOT - Insights</h1>

                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="mb-3 d-flex flex-wrap gap-2">
                                <?php foreach ($temas as $tema): ?>
                                    <?php
                                    $isSelected = $temaSelecionado === $tema['valor'];
                                    $btnClass = $isSelected ? 'btn btn-' . $tema['cor'] : 'btn btn-outline-secondary';
                                    $extraClass = ($tema['cor'] === 'warning' && $isSelected) ? ' text-dark' : '';
                                    ?>
                                    <button type="button"
                                            class="swot-theme-btn <?= $btnClass . $extraClass ?>"
                                            data-tema="<?= htmlspecialchars($tema['valor']) ?>"
                                            data-cor="<?= htmlspecialchars($tema['cor']) ?>">
                                        <?= htmlspecialchars($tema['valor']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <form action="<?= htmlspecialchars($swotRoute) ?>/" method="post">
                                <input type="hidden" name="IdSwot" id="IdSwot" value="<?= htmlspecialchars($_POST['IdSwot'] ?? '') ?>">
                                <input type="hidden" name="Tema" id="TemaSwot" value="<?= htmlspecialchars($temaSelecionado) ?>">

                                <div class="mb-3 swot-postit-wrap">
                                    <span class="swot-postit-pin" aria-hidden="true"></span>
                                    <textarea class="form-control swot-postit" id="TextoSwot" name="Texto" rows="10" style="min-height: 320px;" placeholder="Digite sua mensagem aqui..."><?= htmlspecialchars($_POST['Texto'] ?? '') ?></textarea>
                                </div>

                                <button type="submit" name="acao" value="salvar" class="btn btn-primary">Enviar</button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-3">Insights cadastrados</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Insight</th>
                                            <th>Tema</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($listaSwot)): ?>
                                            <?php foreach ($listaSwot as $item): ?>
                                                <?php
                                                $temaItem = $item['Tema'] ?? '';
                                                $corItem = corTemaSwot($temaItem, $temas);
                                                $badgeClass = 'badge bg-' . $corItem;
                                                if ($corItem === 'warning') {
                                                    $badgeClass .= ' text-dark';
                                                }
        $textoJson = htmlspecialchars(json_encode(html_entity_decode((string) ($item['Texto'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                                ?>
                                                <tr>
                                                    <td class="swot-preview-text"><?= htmlspecialchars(previewTextoSwot($item['Texto'] ?? '')) ?></td>
                                                    <td><span class="<?= $badgeClass ?>"><?= htmlspecialchars($temaItem) ?></span></td>
                                                    <td>
                                                        <button type="button"
                                                                class="btn btn-sm btn-warning btn-editar-swot"
                                                                data-id="<?= intval($item['IdSwot'] ?? 0) ?>"
                                                                data-tema="<?= htmlspecialchars($temaItem) ?>"
                                                                data-texto="<?= $textoJson ?>">
                                                            Editar
                                                        </button>
                                                        <form action="<?= htmlspecialchars($swotRoute) ?>/" method="post" style="display:inline-block;" onsubmit="return confirm('Excluir este insight?');">
                                                            <input type="hidden" name="IdSwot" value="<?= intval($item['IdSwot'] ?? 0) ?>">
                                                            <button type="submit" name="acao" value="excluir" class="btn btn-sm btn-danger">Excluir</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3">Nenhum insight cadastrado.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>

    <script>
        function atualizarBotoesTema(temaAtual) {
            document.querySelectorAll('.swot-theme-btn').forEach((btn) => {
                const tema = btn.getAttribute('data-tema');
                const cor = btn.getAttribute('data-cor');

                btn.classList.remove('btn-primary', 'btn-info', 'btn-danger', 'btn-warning', 'text-dark');
                if (tema === temaAtual) {
                    btn.classList.remove('btn-outline-secondary');
                    btn.classList.add('btn-' + cor);
                    if (cor === 'warning') {
                        btn.classList.add('text-dark');
                    }
                } else {
                    btn.classList.remove('btn-' + cor);
                    btn.classList.add('btn-outline-secondary');
                }
            });
        }

        document.querySelectorAll('.swot-theme-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const temaSelecionado = btn.getAttribute('data-tema');
                document.getElementById('TemaSwot').value = temaSelecionado;
                atualizarBotoesTema(temaSelecionado);
            });
        });

        document.querySelectorAll('.btn-editar-swot').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                const tema = btn.getAttribute('data-tema');
                const textoJson = btn.getAttribute('data-texto');
                let texto = '';

                try {
                    texto = JSON.parse(textoJson);
                } catch (e) {
                    texto = '';
                }

                document.getElementById('IdSwot').value = id;
                document.getElementById('TemaSwot').value = tema;
                atualizarBotoesTema(tema);

                if (window.tinymce && tinymce.get('TextoSwot')) {
                    tinymce.get('TextoSwot').setContent(texto || '');
                } else {
                    document.getElementById('TextoSwot').value = texto || '';
                }

                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });

        atualizarBotoesTema('<?= htmlspecialchars($temaSelecionado) ?>');
    </script>

    <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>
</body>

</html>






