// filtroChamadaTotal.js

function updateTabelaChamada() {
    const btnPresenca = document.getElementById('btn-presenca');
    const btnFalta = document.getElementById('btn-falta');
    const btnFaltaJust = document.getElementById('btn-falta-justificada');
    const btnNenhum = document.getElementById('btn-nenhum');
    if (!btnPresenca || !btnFalta || !btnFaltaJust || !btnNenhum) {
        return;
    }

    const presencaAtiva = btnPresenca.style.backgroundColor !== 'gray';
    const faltaAtiva = btnFalta.style.backgroundColor !== 'gray';
    const faltaJustAtiva = btnFaltaJust.style.backgroundColor !== 'gray';
    const nenhumAtivo = btnNenhum.classList.contains('ativo');

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
    const btnPresenca = document.getElementById('btn-presenca');
    const btnFalta = document.getElementById('btn-falta');
    const btnFaltaJust = document.getElementById('btn-falta-justificada');
    const btnNenhum = document.getElementById('btn-nenhum');
    if (!btnPresenca || !btnFalta || !btnFaltaJust || !btnNenhum) {
        return;
    }

    let countPresenca = 0;
    let countFalta = 0;
    let countFaltaJust = 0;
    let countNenhum = 0;

    document.querySelectorAll('#tabelaPresencas tbody tr').forEach(row => {
        if (row.style.display === 'none') return;

        const botoes = row.querySelectorAll('button');
        let marcado = false;
        botoes.forEach(btn => {
            if (btn.classList.contains('selecionado')) {
                marcado = true;
                if (btn.innerText.includes('P')) countPresenca++;
                else if (btn.innerText.includes('FJ')) countFaltaJust++;
                else if (btn.innerText.includes('F')) countFalta++;
            }
        });

        if (!marcado) countNenhum++;
    });

    btnPresenca.textContent = `Presenca: ${countPresenca}`;
    btnFalta.textContent = `Falta: ${countFalta}`;
    btnFaltaJust.textContent = `Falta Just.: ${countFaltaJust}`;
    btnNenhum.innerHTML = `<i class="bi bi-lightbulb"></i> Nenhum: ${countNenhum}`;
}

document.addEventListener('DOMContentLoaded', () => {
    ['btn-presenca', 'btn-falta', 'btn-falta-justificada', 'btn-nenhum'].forEach(id => {
        const btn = document.getElementById(id);
        if (!btn) {
            return;
        }
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

    updateTabelaChamada();

    document.querySelectorAll('#tabelaPresencas button').forEach(btn => {
        btn.addEventListener('click', function () {
            const botao = this;
            const id = botao.id;
            const partes = id.split('-');
            const tipo = partes[0];
            const idMatricula = partes[1];
            const data = partes[2];
            const linha = botao.closest('tr');
            const obs = linha.querySelector('textarea.obs')?.value || '';

            linha.querySelectorAll('button').forEach(b => {
                b.classList.remove('selecionado');
                b.style.backgroundColor = '#a3a3a3';
            });

            botao.classList.add('selecionado');

            if (tipo === 'P') botao.style.backgroundColor = '#008000';
            else if (tipo === 'FJ') botao.style.backgroundColor = '#CC9900';
            else if (tipo === 'F') botao.style.backgroundColor = '#FF0000';

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

                toastr.success('Salvo!');
            });

            updateButtonCounts();
        });
    });

    document.querySelectorAll('textarea.obs').forEach(obsInput => {
        obsInput.addEventListener('blur', function () {
            const valor = this.value.trim();
            const data = this.getAttribute('data-date');
            const linha = this.closest('tr');

            const botaoObs = linha.querySelector('button[id^="Obs-"]');
            if (botaoObs) {
                if (valor !== '') {
                    botaoObs.style.backgroundColor = 'darkblue';
                    botaoObs.classList.add('selecionado');
                } else {
                    botaoObs.style.backgroundColor = '#a3a3a3';
                    botaoObs.classList.remove('selecionado');
                }
            }

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
                toastr.success('Observacao salva!');
            });
        });
    });

    document.querySelectorAll('button[id^="Obs-"]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
        });
    });
});
