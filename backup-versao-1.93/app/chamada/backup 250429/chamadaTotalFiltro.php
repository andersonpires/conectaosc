<?php
// components/chamadaTotalFiltro.php
// Este arquivo deve ser incluído dentro de listTotalChamada.php, após as variáveis $aluno, $nomeCurso, etc. estarem definidas
?>
<div class="card mb-4">
    <div class="card-body d-flex align-items-center">
        <img src="<?php echo $_SESSION['BASE_URL']; ?>/assets/img/fotos/<?php echo $aluno['Foto'] ?: 'padrao.jpg'; ?>" class="rounded-circle me-3" width="80" height="80">
        <div>
            <h5><?php echo $aluno['Apelido'] ? "({$aluno['Apelido']}) " : ''; ?><?php echo $aluno['Nome']; ?></h5>
            <p class="mb-0">Curso: <?php echo '<strong>' . $nomeExtCurso . '</strong>'; ?> - Turma: <?php echo '<strong>' . $nomeExtTurma . '</strong>'; ?></p>
            <small><?php echo $aluno['Endereco'] . ', ' . $aluno['Bairro'] . ', ' . $aluno['Cidade'] . '-' . $aluno['UF']; ?></small>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form class="row g-2">
            <input type="hidden" name="idAluno" value="<?php echo $idAluno; ?>">
            <input type="hidden" name="dataSelecionada" value="<?= htmlspecialchars($dataSelecionada) ?>">
            <input type="hidden" name="NNomeCurso" value="<?php echo $nomeCurso; ?>">
            <input type="hidden" name="NNomeTurma" value="<?php echo $nomeTurma; ?>">
            <div class="col">
                <label>Data Início</label>
                <input type="date" name="dataInicio" class="form-control" value="<?php echo $dataInicio; ?>">
            </div>
            <div class="col">
                <label>Data Fim</label>
                <input type="date" name="dataFim" class="form-control" value="<?php echo $dataFim; ?>">
            </div>
            <div class="col d-flex align-items-end">
                <button class="btn btn-primary w-100">Pesquisar</button>
            </div>
        </form>
    </div>
    <div class="card-body table-responsive">
        <div class="row mb-3">
            <div class="col">
                <div id="filter-buttons" class="d-flex gap-2 flex-wrap">
                    <button id="btn-presenca" class="btn btn-success">Presença</button>
                    <button id="btn-falta" class="btn btn-danger">Falta</button>
                    <button id="btn-falta-justificada" class="btn btn-warning">Falta Just.</button>
                    <button id="btn-nenhum" class="btn btn-secondary ativo"><i class="bi bi-lightbulb"></i> Nenhum</button>
                </div>
            </div>
            <div class="col">
                <?php
                $diasSemana = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sabado', 'Domingo'];
                foreach ($diasSemana as $dia) {
                    echo '<div class="form-check form-check-inline">';
                    echo '<label class="form-check-label">';
                    echo "<input type='checkbox' class='form-check-input dia-checkbox' value='{$dia}' checked> " . substr($dia, 0, 3);
                    echo '</label></div>';
                }
                ?>
            </div>
        </div>

        <table class="table table-hover" id="tabelaPresencas">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Dia da Semana</th>
                    <th>Excluir</th>
                    <th>Chamada</th>
                    <th>Registrada por</th>
                    <th>Observação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (diasEntreDatas($dataInicio, $dataFim) as $dia):
                    $dataStr = $dia->format('Y-m-d');
                    // $sqlChamada = $pdo->prepare("SELECT Presenca, Obs FROM tbChamada WHERE IdAluno = ? AND IdCurso = ? AND Data = ? LIMIT 1");
                    $sqlChamada = $pdo->prepare("
                                                    SELECT c.Presenca, c.Obs, u.Nome AS NomeColaborador
                                                    FROM tbChamada c
                                                    LEFT JOIN tbUser u ON c.IdColaborador = u.IdColaborador
                                                    WHERE c.IdAluno = ? AND c.IdCurso = ? AND c.Data = ?
                                                    LIMIT 1
                                                ");
                    $sqlChamada->execute([$idAluno, $nomeCurso, $dataStr]);
                    $chamada = $sqlChamada->fetch(PDO::FETCH_ASSOC);

                    $presenca = $chamada['Presenca'] ?? '';
                    $obs = $chamada['Obs'] ?? '';
                    $semana = traduzirDia($dataStr);
                ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($dataStr)); ?></td>
                        <td><?php echo $semana; ?></td>
                        <td>
                            <a href="#"
                                class="btn btn-sm btn-outline-danger excluir-registro"
                                data-idmatricula="<?php echo $idMatricula; ?>"
                                data-idturma="<?php echo $nomeTurma; ?>"
                                data-idcurso="<?php echo $nomeCurso; ?>"
                                data-data="<?php echo $dataStr; ?>">
                                <i class="bi bi-trash"></i> Excluir
                            </a>
                        </td>

                        <td>
                            <?php echo renderizarBotoesPorData($idMatricula, $dataStr, '', $aluno['Nome'], $obs); ?>
                        </td>
                        <td><?php echo $chamada['NomeColaborador'] ?? '-'; ?></td>
                        <td><textarea class="form-control form-control-sm obs" rows="1" data-date="<?php echo $dataStr; ?>"><?php echo $obs; ?></textarea></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>