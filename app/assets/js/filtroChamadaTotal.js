// filtroChamadaTotal.js

function showToastr(type, message, title = '') {
    if (typeof toastr !== 'undefined' && typeof toastr[type] === 'function') {
        toastr[type](message, title || undefined);
        return;
    }

    if (type === 'error') {
        console.error(message);
        return;
    }

    console.log(message);
}

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

    const diasSelecionados = Array.from(document.querySelectorAll('.dia-checkbox:checked')).map((cb) => cb.value);

    document.querySelectorAll('#tabelaPresencas tbody tr').forEach((tr) => {
        const diaSemana = tr.querySelector('td:nth-child(2)')?.innerText.trim() || '';
        const buttons = tr.querySelectorAll('.button-container .btnp');
        let chamadaTipo = '';

        buttons.forEach((btn) => {
            if (!btn.classList.contains('selecionado')) {
                return;
            }

            if (btn.id.includes('P-')) {
                chamadaTipo = 'P';
            } else if (btn.id.includes('FJ-')) {
                chamadaTipo = 'FJ';
            } else if (btn.id.includes('F-')) {
                chamadaTipo = 'F';
            }
        });

        const obs = tr.querySelector('textarea.obs')?.value.trim();
        const diaOk = diasSelecionados.includes(diaSemana);
        const chamadaOk =
            (presencaAtiva && chamadaTipo === 'P') ||
            (faltaAtiva && chamadaTipo === 'F') ||
            (faltaJustAtiva && chamadaTipo === 'FJ') ||
            (nenhumAtivo && chamadaTipo === '' && !obs);

        tr.style.display = diaOk && chamadaOk ? '' : 'none';
    });

    updateButtonCounts();
}

function updateButtonCounts() {
    const btnPresenca = document.getElementById('btn-presenca');
    const btnFalta = document.getElementById('btn-falta');
    const btnFaltaJust = document.getElementById('btn-falta-justificada');
    const btnNenhum = document.getElementById('btn-nenhum');
    const resumoFrequencia = document.getElementById('resumo-frequencia-aluno');
    if (!btnPresenca || !btnFalta || !btnFaltaJust || !btnNenhum) {
        return;
    }

    let countPresenca = 0;
    let countFalta = 0;
    let countFaltaJust = 0;
    let countNenhum = 0;

    document.querySelectorAll('#tabelaPresencas tbody tr').forEach((row) => {
        if (row.style.display === 'none') {
            return;
        }

        const botoes = row.querySelectorAll('.button-container .btnp');
        let marcado = false;

        botoes.forEach((btn) => {
            if (!btn.classList.contains('selecionado')) {
                return;
            }

            marcado = true;
            if (btn.innerText.includes('FJ')) {
                countFaltaJust += 1;
            } else if (btn.innerText.includes('F')) {
                countFalta += 1;
            } else if (btn.innerText.includes('P')) {
                countPresenca += 1;
            }
        });

        if (!marcado) {
            countNenhum += 1;
        }
    });

    btnPresenca.textContent = `Presença: ${countPresenca}`;
    btnFalta.textContent = `Falta: ${countFalta}`;
    btnFaltaJust.textContent = `Falta Just.: ${countFaltaJust}`;
    btnNenhum.innerHTML = `<i class="bi bi-lightbulb"></i> Nenhum: ${countNenhum}`;

    if (resumoFrequencia) {
        const totalAulas = countPresenca + countFalta + countFaltaJust;
        const frequencia = totalAulas > 0 ? (countPresenca / totalAulas) * 100 : 0;
        const percentualFalta = totalAulas > 0 ? (countFalta / totalAulas) * 100 : 0;
        const percentualFaltaJust = totalAulas > 0 ? (countFaltaJust / totalAulas) * 100 : 0;
        const formatarPercentual = (valor) => valor.toLocaleString('pt-BR', {
            minimumFractionDigits: 1,
            maximumFractionDigits: 1
        });

        resumoFrequencia.textContent =
            `O aluno teve ${totalAulas} aulas, com ${formatarPercentual(frequencia)}% de frequência, ${formatarPercentual(percentualFalta)}% de falta e ${formatarPercentual(percentualFaltaJust)}% de falta justificada.`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    ['btn-presenca', 'btn-falta', 'btn-falta-justificada', 'btn-nenhum'].forEach((id) => {
        const btn = document.getElementById(id);
        if (!btn) {
            return;
        }

        const originalColor = getComputedStyle(btn).backgroundColor;
        btn.addEventListener('click', () => {
            btn.classList.toggle('ativo');
            btn.style.backgroundColor = btn.style.backgroundColor === 'gray' ? originalColor : 'gray';
            updateTabelaChamada();
        });
    });

    document.querySelectorAll('.dia-checkbox').forEach((cb) => {
        cb.addEventListener('change', updateTabelaChamada);
    });

    document.querySelectorAll('#tabelaPresencas tbody .button-container .btnp').forEach((btn) => {
        btn.addEventListener('click', function (event) {
            event.preventDefault();

            const botao = this;
            const partes = botao.id.split('-');
            const tipo = partes[0];
            const idMatricula = partes[1];
            const data = partes[2];
            const linha = botao.closest('tr');

            if (!linha || !idMatricula || !data) {
                showToastr('error', 'Não foi possível identificar a chamada selecionada.');
                return;
            }

            linha.querySelectorAll('.button-container .btnp').forEach((item) => {
                item.classList.remove('selecionado');
                item.style.backgroundColor = '#a3a3a3';
            });

            botao.classList.add('selecionado');
            if (tipo === 'P') {
                botao.style.backgroundColor = '#008000';
            } else if (tipo === 'FJ') {
                botao.style.backgroundColor = '#CC9900';
            } else if (tipo === 'F') {
                botao.style.backgroundColor = '#FF0000';
            }

            $.ajax({
                url: DADOS_GLOBAIS.saveUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'insertData',
                    id__Matricula: idMatricula,
                    idAluno: DADOS_GLOBAIS.idAluno,
                    idTurma: DADOS_GLOBAIS.idTurma,
                    idCurso: DADOS_GLOBAIS.idCurso,
                    idColaborador: DADOS_GLOBAIS.idColaborador,
                    dataSelecionada: `${data.slice(6, 8)}/${data.slice(4, 6)}/${data.slice(0, 4)}`,
                    selectedAction: tipo
                }
            }).done((response) => {
                if (!response || response.status !== 'success') {
                    showToastr('error', response?.message || 'Erro ao salvar a chamada.');
                    return;
                }

                $.ajax({
                    url: DADOS_GLOBAIS.saveUrl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'getFaltas',
                        id__Matricula: idMatricula
                    }
                }).fail(() => {
                    console.error('Falha ao atualizar contador de faltas.');
                });

                showToastr('success', 'Chamada salva com sucesso.');
            }).fail(() => {
                showToastr('error', 'Erro ao salvar a chamada.');
            });

            updateButtonCounts();
        });
    });

    document.querySelectorAll('textarea.obs').forEach((obsInput) => {
        obsInput.addEventListener('blur', function () {
            const valor = this.value.trim();
            const data = this.getAttribute('data-date');
            const linha = this.closest('tr');
            const botaoObs = linha?.querySelector('button[id^="Obs-"]');

            if (!botaoObs || !data) {
                return;
            }

            if (valor !== '') {
                botaoObs.style.backgroundColor = 'darkblue';
                botaoObs.classList.add('selecionado');
            } else {
                botaoObs.style.backgroundColor = '#a3a3a3';
                botaoObs.classList.remove('selecionado');
            }

            const idMatricula = botaoObs.id.split('-')[1];
            const dataFormatada = `${data.slice(8, 10)}/${data.slice(5, 7)}/${data.slice(0, 4)}`;

            $.ajax({
                url: DADOS_GLOBAIS.saveUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'salvaObs',
                    idCurso: DADOS_GLOBAIS.idCurso,
                    idTurma: DADOS_GLOBAIS.idTurma,
                    idMatricula: idMatricula,
                    dataSelecionada: dataFormatada,
                    observacoes: valor
                }
            }).done((response) => {
                if (!response || response.success !== true) {
                    showToastr('error', response?.message || 'Erro ao salvar a observação.');
                    return;
                }

                showToastr('success', 'Observação salva com sucesso.');
            }).fail(() => {
                showToastr('error', 'Erro ao salvar a observação.');
            });
        });
    });

    document.querySelectorAll('button[id^="Obs-"]').forEach((btn) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
        });
    });

    updateTabelaChamada();
});
