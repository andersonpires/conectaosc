<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
$permissoes = $permissoes ?? [];
$listaColaboradores = $listaColaboradores ?? [];
$colaboradorEdicao = $colaboradorEdicao ?? null;
$statusFiltro = $statusFiltro ?? 'ativos';
$especialidadesProfissionais = $especialidadesProfissionais ?? [];
$ufs = [
    'AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <style>
        .img-cover {
            object-fit: cover;
            object-position: center;
        }

        /* Garantir que a tabela role horizontalmente em telas pequenas */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            /* Melhora a rolagem no iOS */
        }

        /* Garantir que as colunas se ajustem melhor */
        .table-layout-auto {
            table-layout: auto;
            width: 100%;
            white-space: nowrap;
            /* Comentário ajustado para UTF-8. */
        }

        /* Definir rolagem em telas menores */
        @media (max-width: 767px) {
            .table-responsive {
                display: block;
                width: 100%;
                overflow-x: auto;
            }

            .table-layout-auto th,
            .table-layout-auto td {
                white-space: nowrap;
                /* Evita que o texto das colunas quebre */
                text-align: left;
            }
        }
    </style>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/jquery.dataTables.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="<?php echo $BASE_para_URL; ?>/assets/css/overlayNotifica.css">
    <script src="<?php echo $BASE_para_URL; ?>/assets/js/overlayNotifica.js"></script>

</head>

<script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>


<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>

            <main class="content">
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3">Usuários do Sistema</h1>
                    
                    <?php
                    $msg = $_POST['msg'] ?? '';
                    $erro = $_POST['erro'] ?? '';
                    if ($msg) {
                        echo '<div class="alert alert-success" role="alert">' . htmlspecialchars($msg) . '</div>';
                    } elseif ($erro) {
                        echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($erro) . '</div>';
                    }

                    $colaborador = $colaboradorEdicao ?? null;
                    if ($colaborador) {
                    ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/colaboradores/" enctype="multipart/form-data" method="post" onsubmit="mostrarOverlay({ mensagem: 'Alterando dados...', carregando: true })">
                                        <input type="hidden" name="IdColaborador" value="<?php echo htmlspecialchars($colaborador['IdColaborador']); ?>">
                                        <div class="row mb-3">
                                            <div class="col-5">
                                                <label for="Nome" class="form-label">Nome</label>
                                                <input type="text" class="form-control" id="Nome" name="Nome" value="<?php echo htmlspecialchars($colaborador['Nome']); ?>" required autocomplete="off">
                                            </div>
                                            <div class="col-7">
                                                <label for="Sobrenome" class="form-label">Sobrenome</label>
                                                <input type="text" class="form-control" id="Sobrenome" name="Sobrenome" value="<?php echo htmlspecialchars($colaborador['Sobrenome']); ?>" required autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-7">
                                                <label for="CPF" class="form-label">CPF</label>
                                                <input type="text" class="form-control" id="CPF" name="CPF" value="<?php echo htmlspecialchars($colaborador['CPF']); ?>" readonly autocomplete="off">
                                            </div>
                                            <div class="col-5">
                                                <label for="Permissao" class="form-label">Perfil</label>
                                                <select class="form-select" aria-label="Selecione" id="Permissao" name="Permissao" required>
                                                    <option value="">Selecione...</option>
                                                    <?php foreach ($permissoes as $p): ?>
                                                        <?php $selected = ($colaborador['IdPermissao'] == $p['IdPermissao']) ? 'selected' : ''; ?>
                                                        <option value="<?php echo $p['IdPermissao']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($p['NomePermissao']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="WhatsApp" class="form-label">WhatsApp</label>
                                            <input type="text" class="form-control" id="WhatsApp" name="WhatsApp" value="<?php echo htmlspecialchars($colaborador['WhatsApp']); ?>" autocomplete="off">
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="profissional_saude" name="profissional_saude" value="1" <?php echo !empty($colaborador['profissional_saude']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="profissional_saude">Profissional de saúde</label>
                                            </div>
                                        </div>
                                        <div id="profissionalSaudeCampos" class="border rounded p-3 mb-3" style="display: none;">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <label class="form-label mb-0">Especialidades</label>
                                                <button type="button" class="btn btn-outline-primary btn-sm" id="addEspecialidade">+</button>
                                            </div>
                                            <div id="especialidadesContainer">
                                                <?php
                                                $itensEspecialidade = $especialidadesProfissionais;
                                                if (empty($itensEspecialidade)) {
                                                    $itensEspecialidade = [[]];
                                                }
                                                ?>
                                                <?php foreach ($itensEspecialidade as $idx => $item): ?>
                                                <div class="row g-2 align-items-end especialidade-item" data-index="<?php echo (int)$idx; ?>">
                                                    <div class="col-md-4">
                                                        <label class="form-label">Especialidade</label>
                                                        <select class="form-select" data-name="especialidade_id" name="especialidades[<?php echo (int)$idx; ?>][especialidade_id]">
                                                            <option value="">Nenhuma</option>
                                                            <?php foreach ($especialidades as $e): ?>
                                                            <option value="<?php echo (int)$e['id']; ?>" <?php echo (isset($item['especialidade_id']) && (int)$item['especialidade_id'] === (int)$e['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($e['nome']); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Conselho</label>
                                                        <input type="text" class="form-control" data-name="conselho" name="especialidades[<?php echo (int)$idx; ?>][conselho]" value="<?php echo htmlspecialchars($item['Conselho'] ?? ''); ?>">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">UF</label>
                                                        <select class="form-select" data-name="uf" name="especialidades[<?php echo (int)$idx; ?>][uf]">
                                                            <option value="">UF...</option>
                                                            <?php foreach ($ufs as $uf): ?>
                                                            <option value="<?php echo $uf; ?>" <?php echo (isset($item['UF']) && $item['UF'] === $uf) ? 'selected' : ''; ?>><?php echo $uf; ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">No registro</label>
                                                        <input type="text" class="form-control" data-name="registro" name="especialidades[<?php echo (int)$idx; ?>][registro]" value="<?php echo htmlspecialchars($item['NumeroRegistro'] ?? ''); ?>">
                                                    </div>
                                                    <div class="col-md-1 d-grid">
                                                        <?php if (count($itensEspecialidade) > 1): ?>
                                                        <button type="button" class="btn btn-danger btn-sm remove-especialidade" title="Remover especialidade">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <small class="text-muted">Defina as especialidades e dados do conselho para o colaborador.</small>
                                        </div>
<?php if (false): ?>
                                        <div class="mb-3">
                                            <label for="especialidade_id" class="form-label">Especialidade (App Clinica)</label>
                                            <select class="form-select" id="especialidade_id" name="especialidade_id">
                                                <option value="">Nenhuma</option>
                                                <?php foreach ($especialidades as $e): ?>
                                                <option value="<?php echo (int)$e['id']; ?>" <?php echo (isset($colaborador['especialidade_id']) && (int)$colaborador['especialidade_id'] === (int)$e['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($e['nome']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted">Define em quais atendimentos plantonistas o colaborador pode atuar.</small>
                                        </div>
                                        <?php endif; ?>
                                        <div class="row mb-3">
                                            <div class="col-7">
                                                <label for="Email" class="form-label">E-mail</label>
                                                <input type="email" class="form-control" id="Email" name="Email" value="<?php echo htmlspecialchars($colaborador['Email']); ?>" required autocomplete="off">
                                            </div>
                                            <div class="col-5">
                                                <label for="Senha" class="form-label">Senha</label>
                                                <input type="text" class="form-control" id="Senha" name="Senha" value="Apenas o usuário pode alterar a senha" readonly autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="fotoedit" class="form-label">Foto colaborador</label>
                                            <br>
                                            <img src="<?php echo htmlspecialchars(bootstrap_foto_url((string)($colaborador['Foto'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" id="fotoedit" class="rounded-circle img-cover fotografia" width="100px" height="100px">
                                            <p></p>
                                            <input class="form-control mb-3" type="file" name="foto" id="uploadImage" accept=".jpeg, .jpg, .png">
                                            <input type="hidden" name="fotostring" id="fotostring" value="<?php echo htmlspecialchars($colaborador['Foto']); ?>">
                                            <div id="previewContainer" style="display: none; margin-top: 10px;">
                                                <img id="preview" style="width: 300px; height: 300px; margin-top: 10px;">
                                                <div id="cropControls" style="display: none; margin-top: 10px;">
                                                    <button type="button" id="rotateLeft">Girar Esquerda</button>
                                                    <button type="button" id="rotateRight">Girar Direita</button>
                                                    <button type="button" id="flipHorizontal">Inverter Horizontal</button>
                                                    <button type="button" id="flipVertical">Inverter Vertical</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <button type="submit" name="acao" value="salvar" class="btn btn-primary">Alterar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/colaboradores/" enctype="multipart/form-data" method="post" onsubmit="mostrarOverlay({ mensagem: 'Salvando dados...', carregando: true })">
                                        <div class="row mb-3">
                                            <div class="col-5">
                                                <label for="Nome" class="form-label">Nome</label>
                                                <input type="text" class="form-control" id="Nome" name="Nome" required autocomplete="off">
                                            </div>
                                            <div class="col-7">
                                                <label for="Sobrenome" class="form-label">Sobrenome</label>
                                                <input type="text" class="form-control" id="Sobrenome" name="Sobrenome" required autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-7">
                                                <label for="CPF" class="form-label">CPF</label>
                                                <input type="text" class="form-control" id="CPF" name="CPF" required autocomplete="off">
                                            </div>
                                            <div class="col-5">
                                                <label for="Permissao" class="form-label">Perfil</label>
                                                <select class="form-select" aria-label="Selecione" name="Permissao" required>
                                                    <option value="">Selecione...</option>
                                                    <?php foreach ($permissoes as $p): ?>
                                                        <option value="<?php echo $p['IdPermissao']; ?>"><?php echo htmlspecialchars($p['NomePermissao']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                        </div>
                                        <div class="mb-3">
                                            <label for="WhatsApp" class="form-label">WhatsApp</label>
                                            <input type="text" class="form-control" id="WhatsApp" name="WhatsApp" autocomplete="off">
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="profissional_saude" name="profissional_saude" value="1">
                                                <label class="form-check-label" for="profissional_saude">Profissional de saúde</label>
                                            </div>
                                        </div>
<?php if (false): ?>
                                        <div class="mb-3">
                                            <label for="especialidade_id" class="form-label">Especialidade (App Clinica)</label>
                                            <select class="form-select" id="especialidade_id" name="especialidade_id">
                                                <option value="">Nenhuma</option>
                                                <?php foreach ($especialidades as $e): ?>
                                                <option value="<?php echo (int)$e['id']; ?>"><?php echo htmlspecialchars($e['nome']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted">Define em quais atendimentos plantonistas o colaborador pode atuar.</small>
                                        </div>
                                        <?php endif; ?>
                                        <div class="row mb-3">
                                            <div class="col-7">
                                                <label for="Email" class="form-label">E-mail</label>
                                                <input type="email" class="form-control" id="Email" name="Email" required autocomplete="off">
                                            </div>
                                            <div class="col-5">
                                                <label for="Senha" class="form-label">Senha</label>
                                                <input type="text" class="form-control" id="Senha" name="Senha" autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="uploadImage" class="form-label">Foto colaborador</label>
                                            <input class="form-control mb-3" type="file" name="foto" id="uploadImage" accept=".jpeg, .jpg, .png">
                                            <div id="previewContainer" style="display: none; margin-top: 10px;">
                                                <img id="preview" style="width: 300px; height: 300px; margin-top: 10px;">
                                                <div id="cropControls" style="display: none; margin-top: 10px;">
                                                    <button type="button" id="rotateLeft">Girar Esquerda</button>
                                                    <button type="button" id="rotateRight">Girar Direita</button>
                                                    <button type="button" id="flipHorizontal">Inverter Horizontal</button>
                                                    <button type="button" id="flipVertical">Inverter Vertical</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <button type="submit" name="acao" value="salvar" class="btn btn-primary">Cadastrar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </main>

            <main class="content">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex flex-wrap align-items-center gap-2">
                            <h5 class="mb-0">Colaboradores cadastrados</h5>
                            <div class="btn-group ms-auto" role="group" aria-label="Filtro de status">
                                <a href="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/colaboradores/?status=ativos" class="btn btn-sm <?php echo ($statusFiltro ?? 'ativos') === 'ativos' ? 'btn-primary' : 'btn-outline-primary'; ?>">Ativos</a>
                                <a href="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/colaboradores/?status=inativos" class="btn btn-sm <?php echo ($statusFiltro ?? 'ativos') === 'inativos' ? 'btn-primary' : 'btn-outline-primary'; ?>">Inativos</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="input-group input-group-sm mb-3" style="width: 55%; min-width: 220px; max-width: 520px;">
                                <span class="input-group-text">Procurar</span>
                                <input type="text" class="form-control" id="filtroProcurar" placeholder="Nome, email, CPF...">
                                <button class="btn btn-outline-secondary" type="button" id="limparFiltro">Limpar</button>
                            </div>
                            <div class="table-responsive">
                                <table id="minhaTabela" class="table table-striped table-hover datatable-custom table-layout-auto">
                                    <thead>
                                        <tr>
                                            <th scope="col">Foto</th>
                                            <th scope="col">Nome</th>
                                            <th scope="col">Sobrenome</th>
                                            <th scope="col">Permissão</th>
                                            <th scope="col">CPF</th>
                                            <th scope="col">WhatsApp</th>
                                            <th scope="col">E-mail</th>
                                            <th style="text-align: center;" scope="col">Conferido</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                                                                <?php if (!empty($listaColaboradores)): ?>
                                            <?php foreach ($listaColaboradores as $dados): ?>
                                                <?php
                                                $IdColaborador = $dados['IdColaborador'] ?? 0;
                                                $Foto = $dados['Foto'] ?? 'padrao.jfif';
                                                $Nome = $dados['Nome'] ?? '';
                                                $Sobrenome = $dados['Sobrenome'] ?? '';
                                                $CPF = $dados['CPF'] ?? '';
                                                $parts = explode('.', $CPF);
                                                if (count($parts) > 1) {
                                                    $parts[1] = '***';
                                                    $masked_CPF = implode('.', $parts);
                                                } else {
                                                    $masked_CPF = $CPF;
                                                }
                                                $Permissao = $dados['NomePermissao'] ?? '';
                                                $WhatsApp = $dados['WhatsApp'] ?? '';
                                                $Email = $dados['Email'] ?? '';
                                                $Email_confere = $dados['Email_confere'] ?? 0;
                                                if ($Email_confere == 0) {
                                                    $Email_confere = "alert-triangle";
                                                } elseif ($Email_confere == 1) {
                                                    $Email_confere = "thumbs-up";
                                                }

                                                $habilitado = (int) ($dados['Habilitado'] ?? 1);
                                                $statusLabel = $habilitado === 1 ? 'Ativo' : 'Inativo';
                                                $statusClass = $habilitado === 1 ? 'bg-success' : 'bg-secondary';
                                                ?>
                                                <tr>
                                                    <td>
                                                        <img src="<?php echo htmlspecialchars(bootstrap_foto_url((string)$Foto), ENT_QUOTES, 'UTF-8'); ?>" class="rounded-circle img-cover" width="40px" height="40px">
                                                    </td>
                                                    <td><?php echo htmlspecialchars($Nome); ?></td>
                                                    <td><?php echo htmlspecialchars($Sobrenome); ?></td>
                                                    <td><?php echo htmlspecialchars($Permissao); ?></td>
                                                    <td><?php echo htmlspecialchars($masked_CPF); ?></td>
                                                    <td><?php echo htmlspecialchars($WhatsApp); ?></td>
                                                    <td><?php echo htmlspecialchars($Email); ?></td>
                                                    <td style="text-align: center;"><?php echo '<i class="align-middle" data-feather="' .  $Email_confere . '"></i>'; ?></td>
                                                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span></td>
                                                    <td>
                                                        <?php if ($habilitado === 1): ?>
                                                            <button type="button" class="btn btn-warning btn-sm" onclick="alterarColaborador(<?php echo (int) $IdColaborador; ?>)">
                                                                <i class="fa-solid fa-pen-to-square" data-feather="edit-3"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#excluirModal" data-id="<?php echo (int) $IdColaborador; ?>" data-nome="<?php echo htmlspecialchars($Nome); ?>" data-sobrenome="<?php echo htmlspecialchars($Sobrenome); ?>" data-cpf="<?php echo htmlspecialchars($CPF); ?>" data-email="<?php echo htmlspecialchars($Email); ?>" data-foto="<?php echo htmlspecialchars($Foto); ?>" data-tipo="<?php echo htmlspecialchars($Permissao); ?>">
                                                                <i class="fa-solid fa-trash" data-feather="trash-2"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <form action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/colaboradores/" method="post" style="display:inline-block;">
                                                                <input type="hidden" name="acao" value="reativar">
                                                                <input type="hidden" name="IdColaborador" value="<?php echo (int) $IdColaborador; ?>">
                                                                <button type="submit" class="btn btn-success btn-sm">Reativar</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="10">Nenhum colaborador cadastrado.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="modal fade" id="excluirModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="exampleModalLabel">Inativar usuário</h1>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/colaboradores/" method="post" onsubmit="mostrarOverlay({ mensagem: 'Inativando usuário...', carregando: true })">
                                                <input type="hidden" name="acao" value="excluir">
                                                <div class="mb-3">
                                                    <div class="mb-3">
                                                        <label for="nome" class="form-label">Nome</label>
                                                        <input type="text" class="form-control" id="nome" name="nome" readonly autocomplete="off">
                                                        <input type="hidden" name="IdColaborador" id="IdColaborador">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="sobrenome" class="form-label">Sobrenome</label>
                                                        <input type="text" class="form-control" id="sobrenome" name="sobrenome" readonly autocomplete="off">
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="mb-3">
                                                        <label for="tipo" class="form-label">Perfil</label>
                                                        <select disabled class="form-select" aria-label="Selecione" id="tipo" name="tipo">
                                                            <option value="Educador">Educador</option>
                                                            <option value="Geral">Geral</option>
                                                            <option value="Administrador">Administrador</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="WhatsApp" class="form-label">WhatsApp</label>
                                                    <input type="text" class="form-control" id="WhatsApp" name="WhatsApp" autocomplete="off">
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="mb-3">
                                                        <label for="email" class="form-label">E-mail</label>
                                                        <input type="email" class="form-control" id="email" name="email" readonly autocomplete="off">
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div style="text-align: center;" class="mb-3">
                                                        <label for="foto" class="form-label">Foto usuário</label>
                                                        <br>
                                                        <img src="<?php echo htmlspecialchars(bootstrap_foto_url((string)$Foto), ENT_QUOTES, 'UTF-8'); ?>" id="foto" class="rounded-circle img-cover fotografia" width="100px" height="100px">
                                                        <br>
                                                    </div>
                                                </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-danger">Inativar</button>
                                        </div>
                                        </form>
                                    </div>
                                </div>
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
    <div id="overlayLoading" style="display: none;">
        <div class="overlay-bg"></div>
        <div class="overlay-content" id="overlayContent">
            <div class="spinner-border text-primary" role="status" id="overlaySpinner">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-2" id="overlayText">Salvando dados...</p>
            <button id="overlayBtnOk" class="btn btn-primary mt-3" style="display: none;">OK</button>
        </div>
    </div>

</body>

</html>

<script>
    $(document).ready(function() {
        function normalizarTexto(valor) {
            return (valor || '')
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase();
        }

        if ($.fn.dataTable && $.fn.dataTable.ext && $.fn.dataTable.ext.type) {
            $.fn.dataTable.ext.type.search.string = function(data) {
                return normalizarTexto(data);
            };
        }

        const tabela = $('#minhaTabela').DataTable({
            "pageLength": 50,
            "scrollX": true,
            "autoWidth": false, // Impede que a largura da tabela fique fixa e cause cortes
            "responsive": true, // Permite melhor responsividade no DataTables
            "order": [
                [1, "asc"],
                [2, "asc"]
            ],
            "dom": "lrtip",
            "language": {
                url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json"
            }
        });

        $('#filtroProcurar').on('input keyup', function() {
            tabela.search(normalizarTexto(this.value.trim())).draw();
        });

        $('#limparFiltro').on('click', function() {
            $('#filtroProcurar').val('');
            tabela.search('').draw();
            $('#filtroProcurar').trigger('focus');
        });
    });
</script>


<!-- Comentário interno -->
<!-- Comentário interno -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.12/jquery.mask.min.js"></script>
<script type="text/javascript">
    $("#Telefone, #WhatsApp").mask("(00) 0.0000-0000"); //000 000 0000 eua
    $("#Nascimento").mask('00/00/0000');
    $('.time').mask('00:00:00');
    $('.date_time').mask('00/00/0000 00:00:00');
    $('.cep').mask('00000-000');
    $('.phone').mask('0000-0000');
    $('.phone_with_ddd').mask('(00) 0000-0000');
    $('.phone_us').mask('(000) 000-0000');
    $('.mixed').mask('AAA 000-S0S');
    $('#CPF').mask('000.000.000-00', {
        reverse: true
    });
    $('.money').mask('000.000.000.000.000,00', {
        reverse: true
    });
    $('#Valor').mask('000.000.000.000.000,00', {
        reverse: true
    });
    $('#valor').mask('000.000.000.000.000,00', {
        reverse: true
    });
    $('.money2').mask("#.##0,00", {
        reverse: true
    });
    $('.ip_address').mask('0ZZ.0ZZ.0ZZ.0ZZ', {
        translation: {
            'Z': {
                pattern: /[0-9]/,
                optional: true
            }
        }
    });
    $('.ip_address').mask('099.099.099.099');
    $('.percent').mask('##0,00%', {
        reverse: true
    });
    $('.clear-if-not-match').mask("00/00/0000", {
        clearIfNotMatch: true
    });
    $('.placeholder').mask("00/00/0000", {
        placeholder: "__/__/____"
    });
    $('.fallback').mask("00r00r0000", {
        translation: {
            'r': {
                pattern: /[\/]/,
                fallback: '/'
            },
            placeholder: "__/__/____"
        }
    });
    $('.selectonfocus').mask("00/00/0000", {
        selectOnFocus: true
    });
</script>

<script>
    const inputCPF = document.querySelector('#CPF');
    inputCPF.addEventListener('blur', function() {
        // Obter o valor do CPF
        const cpf = inputCPF.value;

        // Comentário ajustado para UTF-8.
        const primeirosDigitos = getPrimeiroDigitosCPF(cpf);

        // Obter o input Senha
        const inputSenha = document.querySelector('#Senha');

        // Preencher o input Senha se estiver em branco
        if (inputSenha.value === '') {
            inputSenha.value = primeirosDigitos;
        }
    });

    function getPrimeiroDigitosCPF(cpf) {
        return cpf.replace(/\./g, '').substring(0, 6);
    }
</script>



<script type="text/javascript">
    $("#CPF").keydown(function() {
        try {
            $("#cpfcnpj").unmask();
        } catch (e) {}

        var tamanho = $("#CPF").val().length;

        if (tamanho < 14) {
            $("#CPF").mask("999.999.999-99");
        }
        // ajustando foco
        var elem = this;
        setTimeout(function() {
            // Comentário ajustado para UTF-8.
            elem.selectionStart = elem.selectionEnd = 10000;
        }, 0);
        // reaplico o valor para mudar o foco
        var currentValue = $(this).val();
        $(this).val('');
        $(this).val(currentValue);
    });
</script>
<script>
    $("#cpf").keydown(function() {
        try {
            $("#cpfcnpj").unmask();
        } catch (e) {}

        var tamanho = $("#CPF").val().length;

        if (tamanho < 11) {
            $("#cpf").mask("999.999.999-99");
        }

        // ajustando foco
        var elem = this;
        setTimeout(function() {
            // Comentário ajustado para UTF-8.
            elem.selectionStart = elem.selectionEnd = 10000;
        }, 0);
        // reaplico o valor para mudar o foco
        var currentValue = $(this).val();
        $(this).val('');
        $(this).val(currentValue);
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(() => {
            const alertElement = document.querySelector(".alert");
            if (alertElement) alertElement.style.display = "none";
        }, 2000);
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const toggle = document.getElementById('profissional_saude');
        const campos = document.getElementById('profissionalSaudeCampos');
        const container = document.getElementById('especialidadesContainer');
        const addBtn = document.getElementById('addEspecialidade');

        function atualizarIndices() {
            if (!container) return;
            const items = container.querySelectorAll('.especialidade-item');
            items.forEach((item, idx) => {
                item.dataset.index = idx;
                item.querySelectorAll('[data-name]').forEach((el) => {
                    const base = el.getAttribute('data-name');
                    if (base) {
                        el.name = `especialidades[${idx}][${base}]`;
                    }
                });
            });
        }

        function limparValores(item) {
            item.querySelectorAll('input').forEach((el) => {
                el.value = '';
            });
            item.querySelectorAll('select').forEach((el) => {
                el.selectedIndex = 0;
            });
        }

        function atualizarRemover() {
            if (!container) return;
            const items = container.querySelectorAll('.especialidade-item');
            items.forEach((item) => {
                const btn = item.querySelector('.remove-especialidade');
                if (!btn) return;
                btn.style.display = items.length > 1 ? '' : 'none';
            });
        }

        function setEnabled(enabled) {
            if (!campos) return;
            campos.style.display = enabled ? '' : 'none';
            campos.querySelectorAll('input, select, button.remove-especialidade').forEach((el) => {
                el.disabled = !enabled;
            });
            if (addBtn) addBtn.disabled = !enabled;
        }

        if (addBtn && container) {
            addBtn.addEventListener('click', () => {
                const first = container.querySelector('.especialidade-item');
                if (!first) return;
                const clone = first.cloneNode(true);
                limparValores(clone);
                container.appendChild(clone);
                atualizarIndices();
                atualizarRemover();
            });
        }

        if (container) {
            container.addEventListener('click', (event) => {
                const btn = event.target.closest('.remove-especialidade');
                if (!btn) return;
                const items = container.querySelectorAll('.especialidade-item');
                const item = btn.closest('.especialidade-item');
                if (!item) return;
                if (items.length <= 1) {
                    limparValores(item);
                    return;
                }
                item.remove();
                atualizarIndices();
                atualizarRemover();
            });
        }

        if (toggle) {
            toggle.addEventListener('change', () => setEnabled(toggle.checked));
            setEnabled(toggle.checked);
        }
        atualizarRemover();
    });
</script>

<script>
    $(function() {
        $('#excluirModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            var id = button.data('id') // data-id
            var nome = button.data('nome')
            var sobrenome = button.data('sobrenome')
            var tipo = button.data('tipo')
            var cpf = button.data('cpf')
            var email = button.data('email')
            var foto = button.data('foto')

            var modal = $(this)
            //aplica valor ao id
            modal.find('.modal-title').text('Inativar usuário')
            modal.find('#IdColaborador').val(id)
            modal.find('#nome').val(nome)
            modal.find('#sobrenome').val(sobrenome)
            modal.find('#tipo').val(tipo)
            modal.find('#cpf').val(cpf)
            modal.find('#email').val(email)
            // modal.find('#foto').val(foto)

            // Para ler o valor do atributo src de todas as imagens com a classe 'imagens'
            var imagens = document.getElementsByClassName('fotografia');
            for (var i = 0; i < imagens.length; i++) {
                var srcValue = imagens[i].getAttribute('src');
            }

            // Para alterar o valor do atributo src de todas as imagens com a classe 'imagens'
            for (var i = 0; i < imagens.length; i++) {
                imagens[i].setAttribute('src', foto ? '<?php echo rtrim(bootstrap_assets_img_url(), '/'); ?>/fotos/' + foto : '<?php echo bootstrap_foto_url(''); ?>');
            }
        });
    });
</script>

<!-- Comentário interno -->
<script>
    function alterarColaborador(id) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/colaboradores/';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'IdColaborador';
        input.value = id;

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
</script>

<!-- Script para ajustar foto -->
<script>
    const uploadImage = document.getElementById('uploadImage');
    const preview = document.getElementById('preview');
    const cropControls = document.getElementById('cropControls');
    const previewContainer = document.getElementById('previewContainer');
    let cropper;

    uploadImage.addEventListener('change', (event) => {
        const file = event.target.files[0];

        // Validar tipo de arquivo
        if (file && ['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.src = e.target.result;
                preview.style.display = 'block';
                previewContainer.style.display = 'block';
                cropControls.style.display = 'block';

                if (cropper) cropper.destroy();
                cropper = new Cropper(preview, {
                    aspectRatio: 1,
                    viewMode: 1,
                    ready() {
                        // Comentário ajustado para UTF-8.
                        saveCroppedImage();
                    },
                    crop() {
                        // Comentário ajustado para UTF-8.
                        saveCroppedImage();
                    }
                });
            };
            reader.readAsDataURL(file);
        } else {
            alert("Por favor, envie apenas imagens no formato JPEG, JPG ou PNG.");
            uploadImage.value = ''; // Resetar campo de upload
        }
    });

    document.getElementById('rotateLeft').addEventListener('click', () => {
        if (cropper) cropper.rotate(-90);
    });

    document.getElementById('rotateRight').addEventListener('click', () => {
        if (cropper) cropper.rotate(90);
    });

    document.getElementById('flipHorizontal').addEventListener('click', () => {
        if (cropper) cropper.scaleX(cropper.getData().scaleX === 1 ? -1 : 1);
    });

    document.getElementById('flipVertical').addEventListener('click', () => {
        if (cropper) cropper.scaleY(cropper.getData().scaleY === 1 ? -1 : 1);
    });

    function saveCroppedImage() {
        if (cropper) {
            cropper.getCroppedCanvas().toBlob((blob) => {
                const file = new File([blob], "foto-ajustada.jpg", {
                    type: "image/jpeg"
                });

                // Substituir o arquivo original no campo de upload
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                uploadImage.files = dataTransfer.files;

            }, "image/jpeg");
        }
    }
</script>

<!-- Mostrar preview da imagem apenas quando usuário selecionar algum arquivo -->
<script>
    function previewImage(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewContainer = document.getElementById('imagePreviewContainer');
                const previewImage = document.getElementById('imagePreview');
                previewImage.src = e.target.result;
                previewContainer.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }
</script>

















