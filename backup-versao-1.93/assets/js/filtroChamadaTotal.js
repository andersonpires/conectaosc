// filtroChamadaTotal.js

function updateTabelaChamada() {
    const presencaAtiva = document.getElementById('btn-presenca').style.backgroundColor !== 'gray';
    const faltaAtiva = document.getElementById('btn-falta').style.backgroundColor !== 'gray';
    const faltaJustAtiva = document.getElementById('btn-falta-justificada').style.backgroundColor !== 'gray';
    const nenhumAtivo = document.getElementById('btn-nenhum').classList.contains('ativo');

    const diasSelecionados = Array.from(document.querySelectorAll('.dia-checkbox:checked')).map(cb => cb.value);

    document.querySelectorAll('#tabelaPresencas tbody tr').forEach(tr => {
        const diaSemana = tr.querySelector('td:nth-child(2)').innerText.trim();
        const buttons = tr.querySelectorAll('button');
        let chamadaTipo = '';

        buttons.forEach(btn => {
            if (btn.classList.contains('selecionado')) {
                if (btn.id.includes('P-')) chamadaTipo = 'P';
                else if (btn.id.includes('FJ-')) chamadaTipo = 'FJ';
                else if (btn.id.includes('F-')) chamadaTipo = 'F';
            }
        });

        const obs = tr.querySelector('textarea')?.value.trim();

        const diaOk = diasSelecionados.includes(diaSemana);

        const chamadaOk =
            (presencaAtiva && chamadaTipo === 'P') ||
            (faltaAtiva && chamadaTipo === 'F') ||
            (faltaJustAtiva && chamadaTipo === 'FJ') ||
            (nenhumAtivo && chamadaTipo === '' && !obs);

        tr.style.display = (diaOk && chamadaOk) ? '' : 'none';
    });

    updateButtonCounts();
}

function updateButtonCounts() {
    let countPresenca = 0;
    let countFalta = 0;
    let countFaltaJust = 0;
    let countNenhum = 0;

    document.querySelectorAll("#tabelaPresencas tbody tr").forEach(row => {
        if (row.style.display === 'none') return;

        const botoes = row.querySelectorAll("button");
        let marcado = false;
        botoes.forEach(btn => {
            if (btn.classList.contains("selecionado")) {
                marcado = true;
                if (btn.innerText.includes("P")) countPresenca++;
                else if (btn.innerText.includes("FJ")) countFaltaJust++;
                else if (btn.innerText.includes("F")) countFalta++;
            }
        });

        if (!marcado) countNenhum++;
    });

    document.getElementById("btn-presenca").textContent = `Presença: ${countPresenca}`;
    document.getElementById("btn-falta").textContent = `Falta: ${countFalta}`;
    document.getElementById("btn-falta-justificada").textContent = `Falta Just.: ${countFaltaJust}`;
    document.getElementById("btn-nenhum").innerHTML = `<i class="bi bi-lightbulb"></i> Nenhum: ${countNenhum}`;
}

document.addEventListener('DOMContentLoaded', () => {
    // Ações de clique para os botões de filtro
    ['btn-presenca', 'btn-falta', 'btn-falta-justificada', 'btn-nenhum'].forEach(id => {
        const btn = document.getElementById(id);
        const originalColor = getComputedStyle(btn).backgroundColor;

        btn.addEventListener('click', () => {
            btn.classList.toggle('ativo');
            btn.style.backgroundColor = (btn.style.backgroundColor === 'gray') ? originalColor : 'gray';
            updateTabelaChamada();
        });

    });

    document.querySelectorAll('.dia-checkbox').forEach(cb => {
        cb.addEventListener('change', updateTabelaChamada);
    });

    updateTabelaChamada(); // Atualiza ao carregar

    // === CLIQUE EM BOTÕES DE CHAMADA (P, F, FJ) ===
    document.querySelectorAll('#tabelaPresencas button').forEach(btn => {
        btn.addEventListener('click', function () {
            const botao = this;
            const id = botao.id; // Exemplo: P-241-20250109
            const partes = id.split('-');
            const tipo = partes[0]; // P, F, FJ
            const idMatricula = partes[1];
            const data = partes[2];
            const linha = botao.closest('tr');
            const obs = linha.querySelector('textarea.obs')?.value || '';

            // remove seleção de outros botões da mesma linha
            linha.querySelectorAll('button').forEach(b => {
                b.classList.remove('selecionado');
                b.style.backgroundColor = '#a3a3a3'; // reseta cor para cinza
            });

            // aplica estilo e classe no botão clicado
            botao.classList.add('selecionado');

            if (tipo === 'P') botao.style.backgroundColor = '#008000';
            else if (tipo === 'FJ') botao.style.backgroundColor = '#CC9900';
            else if (tipo === 'F') botao.style.backgroundColor = '#FF0000';


            // chamada AJAX para inserir no banco
            $.post('savebanco.php', {
                action: 'insertData',
                id__Matricula: idMatricula,
                idAluno: DADOS_GLOBAIS.idAluno,
                idTurma: DADOS_GLOBAIS.idTurma,
                idCurso: DADOS_GLOBAIS.idCurso,
                idColaborador: DADOS_GLOBAIS.idColaborador,
                dataSelecionada: `${data.slice(6, 8)}/${data.slice(4, 6)}/${data.slice(0, 4)}`,
                selectedAction: tipo
            }, function (response) {
                console.log('Salvo:', response);

                $.post('savebanco.php', {
                    action: 'getFaltas',
                    id__Matricula: idMatricula
                }, function (resp) {
                    console.log('Contador atualizado:', resp);
                });

                toastr.success("Salvo!");
            });

            updateButtonCounts(); // Atualiza contadores no topo
        });
    });

    // Salva observações ao perder o foco do textarea
    document.querySelectorAll('textarea.obs').forEach(obsInput => {
        obsInput.addEventListener('blur', function () {
            const valor = this.value.trim();
            const data = this.getAttribute('data-date');
            const linha = this.closest('tr');

            // Atualiza visual do botão Obs
            const botaoObs = linha.querySelector(`button[id^="Obs-"]`);
            if (botaoObs) {
                if (valor !== '') {
                    botaoObs.style.backgroundColor = 'darkblue';
                    botaoObs.classList.add('selecionado');
                } else {
                    botaoObs.style.backgroundColor = '#a3a3a3';
                    botaoObs.classList.remove('selecionado');
                }
            }

            // AJAX: salvar no banco
            const idMatricula = botaoObs?.id.split('-')[1];
            const dataFormatada = `${data.slice(8, 10)}/${data.slice(5, 7)}/${data.slice(0, 4)}`;

            $.post('savebanco.php', {
                action: 'salvaObs',
                idCurso: '<?php echo $nomeCurso; ?>',
                idTurma: '<?php echo $nomeTurma; ?>',
                idMatricula: idMatricula,
                dataSelecionada: dataFormatada,
                observacoes: valor
            }, function (response) {
                console.log('Obs salva:', response);
                toastr.success("Observação salva!");
            });
        });
    });

    // Impede clique no botão Obs
    document.querySelectorAll('button[id^="Obs-"]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
        });
    });

});
