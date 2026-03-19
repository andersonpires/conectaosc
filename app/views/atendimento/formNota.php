<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
if (!isset($_SESSION)) {
    session_name('PHPSESSID3');
    session_start();
}
?>
<div id="formNovaNota" class="mt-4" style="display: none;">
    <h6>Nova Nota</h6>
    <form id="novaNotaForm" class="mb-4">
        <input type="hidden" name="idUsuario" id="notaIdUsuario">
        <input type="hidden" name="action" value="salvarNota">

        <div class="mb-2 text-muted fst-italic small">
            Escreva qual foi sua interação com o aluno e o resultado, ex: Entramos em contato pelo zap e celular e a aluna não atendeu.
        </div>

        <textarea name="textoNota" id="textoNota"></textarea>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-floppy-disk"></i> Salvar Nota
            </button>
        </div>
    </form>

    <div id="mensagemNota"></div>
</div>
<div id="formNovaTarefa" style="display: none;" class="mt-3">
    <h6>Nova Tarefa</h6>
    <form id="novaTarefaForm">
        <input type="hidden" name="idUsuario" id="tarefaIdUsuario">
        <input type="hidden" name="action" value="salvarTarefa">

        <div class="mb-2">
            <label for="descricaoTarefa" class="form-label">Descrição da tarefa:</label>
            <textarea name="descricaoTarefa" id="descricaoTarefa" class="form-control" rows="3" required></textarea>
        </div>

        <div class="mb-2">
            <label for="dataExecucao" class="form-label">Data de execução:</label>
            <input type="datetime-local" name="dataExecucao" id="dataExecucao" class="form-control" required value="<?php echo date('Y-m-d') . 'T09:00'; ?>">
        </div>

        <button type="submit" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-save"></i> Salvar Tarefa
        </button>
    </form>
    <div id="mensagemTarefa" class="mt-2"></div>
</div>





