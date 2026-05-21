import {
  getPaciente,
  getConsulta,
  getConsultasParaAtendimento,
  postProntuarioStreamIa,
  postProntuario,
  postIniciarAtendimento,
  postConcluirAtendimento,
  getAnamneseByConsulta,
  postAnamnese,
  putAnamnese,
  getAnamneseRoteiroByConsulta,
  postAnamneseRoteiro,
  putAnamneseRoteiro
} from '../services/api.js';
import { getSpinnerHtml, getButtonSpinnerHtml } from '../utils/loading.js';
import { markdownToHtml } from '../utils/markdownToHtml.js';

function fmtTime(t) {
  if (!t) return '';
  const [h, m] = String(t).split(':');
  return `${h}:${m || '00'}`;
}

function escapeHtml(s) {
  const d = document.createElement('div');
  d.textContent = s ?? '';
  return d.innerHTML;
}

function statusLabel(status) {
  const s = (status || '').toLowerCase();
  if (s.includes('concluida') || s.includes('concluída')) return 'Concluída';
  if (s.includes('em_atendimento')) return 'Em atendimento';
  if (s.includes('confirmacao')) return 'Confirmação solicitada';
  return 'Agendada';
}

const filtrosAtendimento = {
  busca: '',
  status: 'todos'
};

function normalizarTexto(valor) {
  return String(valor || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '');
}

function filtrarConsultasParaAtendimento(consultas, filtros) {
  const termo = normalizarTexto(filtros.busca);
  const status = String(filtros.status || 'todos').toLowerCase();

  return (Array.isArray(consultas) ? consultas : []).filter((c) => {
    const statusConsulta = String(c.status || '').toLowerCase();
    if (status === 'em_atendimento' && !statusConsulta.includes('em_atendimento')) return false;
    if (status === 'agendada' && statusConsulta !== 'agendada') return false;
    if (status === 'confirmacao_solicitada' && !statusConsulta.includes('confirmacao')) return false;

    if (!termo) return true;
    const alvo = normalizarTexto([
      c.paciente_nome,
      c.especialidade_nome,
      c.profissional_nome,
      c.profissional_nome_livre,
      c.data_consulta,
      c.hora_inicio_prevista
    ].filter(Boolean).join(' '));
    return alvo.includes(termo);
  });
}

const OPCOES_COGNITIVAS = [
  { v: '', t: 'Selecione...' },
  { v: 'adequada', t: 'Respondeu adequadamente' },
  { v: 'inadequada', t: 'Não respondeu adequadamente' }
];

const OPCOES_SIM_NAO = [
  { v: '', t: 'Selecione...' },
  { v: '1', t: 'Sim' },
  { v: '0', t: 'Não' }
];

function selectCognitivo(name, value = '') {
  return `<select name="${name}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">${OPCOES_COGNITIVAS.map((o) => `<option value="${o.v}" ${value === o.v ? 'selected' : ''}>${o.t}</option>`).join('')}</select>`;
}

function selectSimNao(name, value = '') {
  return `<select name="${name}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">${OPCOES_SIM_NAO.map((o) => `<option value="${o.v}" ${String(value) === o.v ? 'selected' : ''}>${o.t}</option>`).join('')}</select>`;
}

function selectDificuldadesMemoria(value = '') {
  const ops = [
    { v: '', t: 'Selecione...' },
    { v: 'nao', t: 'Não' },
    { v: 'sim_raramente', t: 'Sim, raramente' },
    { v: 'sim_frequencia', t: 'Sim, com frequência' }
  ];
  return `<select name="dificuldades_memoria" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">${ops.map((o) => `<option value="${o.v}" ${value === o.v ? 'selected' : ''}>${o.t}</option>`).join('')}</select>`;
}

/* =========================================================
   FORMULÁRIO ANAMNESE ADULTO (Avaliação Psicológica)
   ========================================================= */

function buildAnamneseFormHtml(anamnese = {}) {
  const data = anamnese ?? {};
  const v = (k) => data[k] ?? '';
  return `
  <div class="space-y-4">
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <details class="group" open>
        <summary class="px-4 py-3 bg-gray-50 cursor-pointer font-medium text-gray-800 flex items-center justify-between">
          <span>3 - Contexto psicossocial e rede de apoio</span>
          <i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition"></i>
        </summary>
        <div class="p-4 space-y-3 border-t border-gray-200">
          <div><label class="block text-sm text-gray-600 mb-1">Possui alguma crença ou religião que te fortalece?</label><input type="text" name="crenca_religiao" value="${escapeHtml(v('crenca_religiao'))}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Qual ou Não"></div>
          <div><label class="block text-sm text-gray-600 mb-1">Essa crença te traz mais Conforto ou Preocupação?</label><select name="crenca_conforto_preocupacao" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"><option value="" ${!v('crenca_conforto_preocupacao') ? 'selected' : ''}>Selecione...</option><option value="conforto" ${v('crenca_conforto_preocupacao') === 'conforto' ? 'selected' : ''}>Conforto</option><option value="preocupacao" ${v('crenca_conforto_preocupacao') === 'preocupacao' ? 'selected' : ''}>Preocupação</option></select></div>
          <div><label class="block text-sm text-gray-600 mb-1">Quem mais te ajuda no dia a dia?</label><textarea name="quem_ajuda_dia_dia" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">${escapeHtml(v('quem_ajuda_dia_dia'))}</textarea></div>
          <div><label class="block text-sm text-gray-600 mb-1">Você é cuidador(a) de alguém?</label>${selectSimNao('cuidador_de_alguem', v('cuidador_de_alguem'))}</div>
          <div id="cuidador-impacto-wrap"><label class="block text-sm text-gray-600 mb-1">Se sim, como isso afeta sua rotina e saúde?</label><textarea name="cuidador_impacto_rotina" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">${escapeHtml(v('cuidador_impacto_rotina'))}</textarea></div>
        </div>
      </details>
    </div>
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <details class="group">
        <summary class="px-4 py-3 bg-gray-50 cursor-pointer font-medium text-gray-800 flex items-center justify-between">
          <span>4 - Aspectos clínicos e saúde mental</span>
          <i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition"></i>
        </summary>
        <div class="p-4 space-y-3 border-t border-gray-200">
          <div><label class="block text-sm text-gray-600 mb-1">Já realizou acompanhamento psicológico ou psiquiátrico?</label>${selectSimNao('acompanhamento_psicologico', v('acompanhamento_psicologico'))}</div>
          <div><label class="block text-sm text-gray-600 mb-1">Já utilizou medicações controladas (psicotrópicos)?</label>${selectSimNao('medicacoes_psicotropicos', v('medicacoes_psicotropicos'))}</div>
          <div><label class="block text-sm text-gray-600 mb-1">Já passou por internação psiquiátrica?</label>${selectSimNao('internacao_psiquiatrica', v('internacao_psiquiatrica'))}</div>
          <div><label class="block text-sm text-gray-600 mb-1">Nos últimos meses, você tem percebido: (ansiedade, humor deprimido, irritabilidade, labilidade emocional, ideação de morte, nada, outros)</label><textarea name="percebido_ultimos_meses" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">${escapeHtml(v('percebido_ultimos_meses'))}</textarea></div>
          <div><label class="block text-sm text-gray-600 mb-1">Como costuma lidar com situações difíceis ou estressantes?</label><textarea name="lidar_situacoes_dificeis" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">${escapeHtml(v('lidar_situacoes_dificeis'))}</textarea></div>
        </div>
      </details>
    </div>
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <details class="group">
        <summary class="px-4 py-3 bg-gray-50 cursor-pointer font-medium text-gray-800 flex items-center justify-between">
          <span>5 - Questões de saúde física e funcionalidade</span>
          <i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition"></i>
        </summary>
        <div class="p-4 space-y-3 border-t border-gray-200">
          <div><label class="block text-sm text-gray-600 mb-1">Como você avalia sua saúde geral atualmente?</label><select name="avaliacao_saude_geral" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"><option value="">Selecione...</option><option value="muito_boa" ${v('avaliacao_saude_geral') === 'muito_boa' ? 'selected' : ''}>Muito boa</option><option value="boa" ${v('avaliacao_saude_geral') === 'boa' ? 'selected' : ''}>Boa</option><option value="regular" ${v('avaliacao_saude_geral') === 'regular' ? 'selected' : ''}>Regular</option><option value="ruim" ${v('avaliacao_saude_geral') === 'ruim' ? 'selected' : ''}>Ruim</option></select></div>
          <div><label class="block text-sm text-gray-600 mb-1">Costuma sentir-se cansado(a), desanimado(a) ou sem energia com frequência?</label><select name="cansado_desanimado" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"><option value="">Selecione...</option><option value="nunca" ${v('cansado_desanimado') === 'nunca' ? 'selected' : ''}>Nunca</option><option value="raramente" ${v('cansado_desanimado') === 'raramente' ? 'selected' : ''}>Raramente</option><option value="as_vezes" ${v('cansado_desanimado') === 'as_vezes' ? 'selected' : ''}>Às vezes</option><option value="frequentemente" ${v('cansado_desanimado') === 'frequentemente' ? 'selected' : ''}>Frequentemente</option></select></div>
          <div><label class="block text-sm text-gray-600 mb-1">Como você avalia a qualidade do seu sono?</label><select name="qualidade_sono" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"><option value="">Selecione...</option><option value="muito_boa" ${v('qualidade_sono') === 'muito_boa' ? 'selected' : ''}>Muito boa</option><option value="boa" ${v('qualidade_sono') === 'boa' ? 'selected' : ''}>Boa</option><option value="regular" ${v('qualidade_sono') === 'regular' ? 'selected' : ''}>Regular</option><option value="ruim" ${v('qualidade_sono') === 'ruim' ? 'selected' : ''}>Ruim</option></select></div>
          <div><label class="block text-sm text-gray-600 mb-1">Tem facilidade para acessar serviços de saúde quando precisa?</label>${selectSimNao('acesso_servicos_saude', v('acesso_servicos_saude'))}</div>
          <div><label class="block text-sm text-gray-600 mb-1">Considera que sua renda é suficiente para suprir suas necessidades básicas?</label>${selectSimNao('renda_suficiente_necessidades', v('renda_suficiente_necessidades'))}</div>
        </div>
      </details>
    </div>
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <details class="group">
        <summary class="px-4 py-3 bg-gray-50 cursor-pointer font-medium text-gray-800 flex items-center justify-between">
          <span>6 - Funcionalidade cognitiva e executiva</span>
          <i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition"></i>
        </summary>
        <div class="p-4 space-y-3 border-t border-gray-200 text-sm">
          <p class="text-gray-600 italic">Resposta: Respondeu adequadamente / Não respondeu adequadamente</p>
          <div><label class="block text-gray-600 mb-1">Orientação: Qual o seu nome completo?</label>${selectCognitivo('ori_nome_completo', v('ori_nome_completo'))}</div>
          <div><label class="block text-gray-600 mb-1">Orientação: Onde estamos agora?</label>${selectCognitivo('ori_onde_estamos', v('ori_onde_estamos'))}</div>
          <div><label class="block text-gray-600 mb-1">Orientação: Que dia é hoje? Mês? Ano?</label>${selectCognitivo('ori_dia_mes_ano', v('ori_dia_mes_ano'))}</div>
          <div><label class="block text-gray-600 mb-1">Orientação: Quem está aqui com você?</label>${selectCognitivo('ori_quem_esta_aqui', v('ori_quem_esta_aqui'))}</div>
          <div><label class="block text-gray-600 mb-1">Memória imediata: Diga três palavras e peça para repetir. Após 3–5 minutos, peça para lembrar novamente.</label>${selectCognitivo('mem_tres_palavras', v('mem_tres_palavras'))}</div>
          <div><label class="block text-gray-600 mb-1">Memória imediata: O que você comeu hoje no café da manhã?</label>${selectCognitivo('mem_cafe_manha', v('mem_cafe_manha'))}</div>
          <div><label class="block text-gray-600 mb-1">Atenção: Conte de 20 até 0.</label>${selectCognitivo('ate_contar_20_0', v('ate_contar_20_0'))}</div>
          <div><label class="block text-gray-600 mb-1">Atenção: Quando eu disser "sim", bata palma. Quando eu disser "não", não faça nada.</label>${selectCognitivo('ate_sim_bata_palma', v('ate_sim_bata_palma'))}</div>
          <div><label class="block text-gray-600 mb-1">Atenção: "Maria foi comprar flores, banana e mamão". O que Maria foi comprar?</label>${selectCognitivo('ate_frase_maria', v('ate_frase_maria'))}</div>
          <div><label class="block text-gray-600 mb-1">Linguagem - Nomeação: "Que objeto é este?" (ex.: caneta, relógio)</label>${selectCognitivo('lin_nomeacao', v('lin_nomeacao'))}</div>
          <div><label class="block text-gray-600 mb-1">Linguagem - Compreensão: "Pegue esta caneta com a sua mão direita e coloque em cima da cabeça."</label>${selectCognitivo('lin_compreensao', v('lin_compreensao'))}</div>
          <div><label class="block text-gray-600 mb-1">Linguagem - Repetição: "Repita: O tempo está nublado hoje."</label>${selectCognitivo('lin_repeticao', v('lin_repeticao'))}</div>
          <div><label class="block text-gray-600 mb-1">Linguagem - Fluência: "Fale o maior número de animais que lembrar em 1 minuto."</label>${selectCognitivo('lin_fluencia', v('lin_fluencia'))}</div>
          <div><label class="block text-gray-600 mb-1">Funções executivas - Planejamento: "Como você faria para preparar um café?"</label>${selectCognitivo('fex_planejamento', v('fex_planejamento'))}</div>
          <div><label class="block text-gray-600 mb-1">Funções executivas - Sequência: "Como você se arruma para sair de casa?"</label>${selectCognitivo('fex_sequencia', v('fex_sequencia'))}</div>
          <div><label class="block text-gray-600 mb-1">Funções executivas - Flexibilidade: "Conte de 1 até 5 alternando números e letras"</label>${selectCognitivo('fex_flexibilidade', v('fex_flexibilidade'))}</div>
          <div><label class="block text-gray-600 mb-1">Funções executivas - Resolução de problemas: "Se você esquecer a chave dentro de casa, o que faria?"</label>${selectCognitivo('fex_resolucao_problemas', v('fex_resolucao_problemas'))}</div>
        </div>
      </details>
    </div>
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <details class="group">
        <summary class="px-4 py-3 bg-gray-50 cursor-pointer font-medium text-gray-800 flex items-center justify-between">
          <span>7 - Risco e proteção psicossocial</span>
          <i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition"></i>
        </summary>
        <div class="p-4 space-y-3 border-t border-gray-200">
          <div><label class="block text-sm text-gray-600 mb-1">Há histórico de uso de álcool ou outras substâncias?</label>${selectSimNao('uso_alcool_substancias', v('uso_alcool_substancias'))}</div>
          <div><label class="block text-sm text-gray-600 mb-1">Já pensou ou tentou se machucar alguma vez?</label>${selectSimNao('pensou_tentou_machucar', v('pensou_tentou_machucar'))}</div>
          <div><label class="block text-sm text-gray-600 mb-1">Costuma se sentir sozinho(a) ou isolado(a)?</label><select name="sente_sozinho_isolado" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"><option value="">Selecione...</option><option value="sim" ${v('sente_sozinho_isolado') === 'sim' ? 'selected' : ''}>Sim</option><option value="as_vezes" ${v('sente_sozinho_isolado') === 'as_vezes' ? 'selected' : ''}>Às vezes</option><option value="nao" ${v('sente_sozinho_isolado') === 'nao' ? 'selected' : ''}>Não</option></select></div>
          <div><label class="block text-sm text-gray-600 mb-1">O que mais te ajuda nos momentos difíceis?</label><input type="text" name="o_que_ajuda_momentos_dificeis" value="${escapeHtml(v('o_que_ajuda_momentos_dificeis'))}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Família, Amigos, Fé, Trabalho, Lazer..."></div>
          <div><label class="block text-sm text-gray-600 mb-1">Quais atividades te fazem bem ou te dão prazer?</label><textarea name="atividades_fazem_bem" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">${escapeHtml(v('atividades_fazem_bem'))}</textarea></div>
        </div>
      </details>
    </div>
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <details class="group">
        <summary class="px-4 py-3 bg-gray-50 cursor-pointer font-medium text-gray-800 flex items-center justify-between">
          <span>8 - Aspectos emocionais e de enfrentamento</span>
          <i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition"></i>
        </summary>
        <div class="p-4 space-y-3 border-t border-gray-200">
          <div><label class="block text-sm text-gray-600 mb-1">Nos últimos meses, você tem se sentido mais...</label><select name="sentimento_ultimos_meses" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"><option value="">Selecione...</option><option value="alegre_motivado" ${v('sentimento_ultimos_meses') === 'alegre_motivado' ? 'selected' : ''}>Alegre e motivado(a)</option><option value="tranquilo_sem_animo" ${v('sentimento_ultimos_meses') === 'tranquilo_sem_animo' ? 'selected' : ''}>Tranquilo(a), mas sem muito ânimo</option><option value="triste_preocupado" ${v('sentimento_ultimos_meses') === 'triste_preocupado' ? 'selected' : ''}>Triste ou preocupado(a)</option></select></div>
          <div><label class="block text-sm text-gray-600 mb-1">Quando enfrenta dificuldades, você consegue pedir ajuda?</label><select name="consegue_pedir_ajuda" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"><option value="">Selecione...</option><option value="sempre" ${v('consegue_pedir_ajuda') === 'sempre' ? 'selected' : ''}>Sim, sempre</option><option value="as_vezes" ${v('consegue_pedir_ajuda') === 'as_vezes' ? 'selected' : ''}>Às vezes</option><option value="nao" ${v('consegue_pedir_ajuda') === 'nao' ? 'selected' : ''}>Não</option></select></div>
          <div><label class="block text-sm text-gray-600 mb-1">Atualmente sente que tem objetivos que te motivam?</label><select name="tem_objetivos_motivam" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"><option value="">Selecione...</option><option value="sempre" ${v('tem_objetivos_motivam') === 'sempre' ? 'selected' : ''}>Sim, sempre</option><option value="as_vezes" ${v('tem_objetivos_motivam') === 'as_vezes' ? 'selected' : ''}>Às vezes</option><option value="nao" ${v('tem_objetivos_motivam') === 'nao' ? 'selected' : ''}>Não</option></select></div>
          <div><label class="block text-sm text-gray-600 mb-1">Quando está triste ou preocupado(a), o que costuma fazer para se sentir melhor?</label><textarea name="quando_triste_o_que_faz" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">${escapeHtml(v('quando_triste_o_que_faz'))}</textarea></div>
        </div>
      </details>
    </div>
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <details class="group">
        <summary class="px-4 py-3 bg-gray-50 cursor-pointer font-medium text-gray-800 flex items-center justify-between">
          <span>9 - Memória, cotidiano e expectativas</span>
          <i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition"></i>
        </summary>
        <div class="p-4 space-y-3 border-t border-gray-200">
          <div><label class="block text-sm text-gray-600 mb-1">Você percebe dificuldades de memória?</label>${selectDificuldadesMemoria(v('dificuldades_memoria'))}</div>
          <div><label class="block text-sm text-gray-600 mb-1">Consegue realizar sozinho suas atividades básicas (alimentação, higiene, deslocamentos curtos)?</label><select name="atividades_basicas_sozinho" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"><option value="">Selecione...</option><option value="sem_dificuldade" ${v('atividades_basicas_sozinho') === 'sem_dificuldade' ? 'selected' : ''}>Sim, sem dificuldade</option><option value="com_ajuda" ${v('atividades_basicas_sozinho') === 'com_ajuda' ? 'selected' : ''}>Sim, com alguma ajuda</option><option value="depende_ajuda" ${v('atividades_basicas_sozinho') === 'depende_ajuda' ? 'selected' : ''}>Não, dependo de ajuda</option></select></div>
          <div><label class="block text-sm text-gray-600 mb-1">O que mais espera conquistar participando deste projeto?</label><textarea name="o_que_espera_projeto" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">${escapeHtml(v('o_que_espera_projeto'))}</textarea></div>
          <div><label class="block text-sm text-gray-600 mb-1">Gostaria de fortalecer mais o que?</label><input type="text" name="fortalecer_mais" value="${escapeHtml(v('fortalecer_mais'))}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Aprendizados, Convivência, Saúde..."></div>
        </div>
      </details>
    </div>
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <details class="group">
        <summary class="px-4 py-3 bg-gray-50 cursor-pointer font-medium text-gray-800 flex items-center justify-between">
          <span>10 - Observações e impressões gerais do profissional</span>
          <i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition"></i>
        </summary>
        <div class="p-4 border-t border-gray-200">
          <textarea name="observacoes_gerais" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Impressões do atendimento...">${escapeHtml(v('observacoes_gerais'))}</textarea>
        </div>
      </details>
    </div>
  </div>
  `;
}

function collectAnamneseFormData(formEl) {
  const fd = new FormData(formEl);
  const data = {};
  const names = ['crenca_religiao','crenca_conforto_preocupacao','quem_ajuda_dia_dia','cuidador_de_alguem','cuidador_impacto_rotina','acompanhamento_psicologico','medicacoes_psicotropicos','internacao_psiquiatrica','percebido_ultimos_meses','lidar_situacoes_dificeis','avaliacao_saude_geral','cansado_desanimado','qualidade_sono','acesso_servicos_saude','renda_suficiente_necessidades','ori_nome_completo','ori_onde_estamos','ori_dia_mes_ano','ori_quem_esta_aqui','mem_tres_palavras','mem_cafe_manha','ate_contar_20_0','ate_sim_bata_palma','ate_frase_maria','lin_nomeacao','lin_compreensao','lin_repeticao','lin_fluencia','fex_planejamento','fex_sequencia','fex_flexibilidade','fex_resolucao_problemas','uso_alcool_substancias','pensou_tentou_machucar','sente_sozinho_isolado','o_que_ajuda_momentos_dificeis','atividades_fazem_bem','sentimento_ultimos_meses','consegue_pedir_ajuda','tem_objetivos_motivam','quando_triste_o_que_faz','dificuldades_memoria','atividades_basicas_sozinho','o_que_espera_projeto','fortalecer_mais','observacoes_gerais'];
  for (const n of names) {
    const v = fd.get(n);
    data[n] = (v === '' || v === null) ? null : String(v).trim();
  }
  return data;
}

/* =========================================================
   FORMULÁRIO ANAMNESE ROTEIRO PEDIÁTRICO/FAMILIAR
   ========================================================= */

function buildAnamneseRoteiroFormHtml(anamnese = {}) {
  const data = anamnese ?? {};
  const v = (k) => data[k] ?? '';
  const ck = (k) => (data[k] == 1 || data[k] === true) ? 'checked' : '';
  const rb = (k, val) => String(v(k)) === String(val) ? 'checked' : '';

  const inputCls = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm';
  const inputSmCls = 'w-full px-2 py-1.5 border border-gray-300 rounded text-sm';
  const labelCls = 'block text-sm text-gray-600 mb-1';
  const labelSmCls = 'text-xs text-gray-500 block mb-0.5';
  const taCls = `${inputCls} resize-y`;
  const summCls = 'px-4 py-3 bg-gray-50 cursor-pointer font-medium text-gray-800 flex items-center justify-between';
  const bodyPad = 'p-4 space-y-3 border-t border-gray-200';
  const chevron = '<i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition"></i>';

  function acomp(label, cbName, ondeName, quandoName) {
    return `
    <div class="rounded-lg border border-gray-200 p-3">
      <label class="flex items-center gap-2 text-sm font-medium text-gray-800 mb-2 cursor-pointer">
        <input type="checkbox" name="${cbName}" value="1" class="rounded w-4 h-4 accent-monday-blue" ${ck(cbName)}>
        ${label}
      </label>
      <div class="pl-6 grid grid-cols-2 gap-2">
        <div><label class="${labelSmCls}">Onde:</label><input type="text" name="${ondeName}" value="${escapeHtml(v(ondeName))}" class="${inputSmCls}"></div>
        <div><label class="${labelSmCls}">Quando:</label><input type="text" name="${quandoName}" value="${escapeHtml(v(quandoName))}" class="${inputSmCls}"></div>
      </div>
    </div>`;
  }

  return `
  <div class="space-y-4">

    <!-- Queixa principal -->
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <details class="group" open>
        <summary class="${summCls}">${escapeHtml('Por que procurou o atendimento?')} ${chevron}</summary>
        <div class="${bodyPad}">
          <div><label class="${labelCls}">Descreva o motivo do atendimento:</label>
          <textarea name="motivo_atendimento" rows="3" class="${taCls}">${escapeHtml(v('motivo_atendimento'))}</textarea></div>
        </div>
      </details>
    </div>

    <!-- Acompanhamentos anteriores -->
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <details class="group">
        <summary class="${summCls}">Acompanhamentos anteriores e atuais ${chevron}</summary>
        <div class="${bodyPad} space-y-3">
          ${acomp('Psicológico', 'acomp_psicologico', 'acomp_psi_onde', 'acomp_psi_quando')}
          ${acomp('Psicopedagógico', 'acomp_psicopedagogico', 'acomp_psicoped_onde', 'acomp_psicoped_quando')}
          ${acomp('Fonoaudiológico', 'acomp_fonoaudiologico', 'acomp_fono_onde', 'acomp_fono_quando')}
          ${acomp('Neurológico', 'acomp_neurologico', 'acomp_neuro_onde', 'acomp_neuro_quando')}
          ${acomp('Terapia Ocupacional', 'acomp_terapia_ocupacional', 'acomp_to_onde', 'acomp_to_quando')}
          ${acomp('Fisioterapia', 'acomp_fisioterapia', 'acomp_fisio_onde', 'acomp_fisio_quando')}
          <div><label class="${labelCls}">Outros:</label>
          <textarea name="acomp_outros" rows="2" class="${taCls}">${escapeHtml(v('acomp_outros'))}</textarea></div>
        </div>
      </details>
    </div>

    <!-- 01 a 06 - História e desenvolvimento -->
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <details class="group">
        <summary class="${summCls}">01 a 06 — História e Desenvolvimento ${chevron}</summary>
        <div class="${bodyPad} space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">01 — História Gestacional</label>
            <p class="text-xs text-gray-500 mb-1">Pré-natal, gestações prévias, abortamentos, planejamento/aceitação, intercorrências, parto/tipo, a termo/prematuro.</p>
            <textarea name="historia_gestacional" rows="3" class="${taCls}">${escapeHtml(v('historia_gestacional'))}</textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">02 — Antecedentes Mórbidos</label>
            <p class="text-xs text-gray-500 mb-1">Infecções de repetição, doenças prévias, alergias, hospitalizações, cirurgias, traumas, uso de medicações.</p>
            <textarea name="antecedentes_morbidos" rows="3" class="${taCls}">${escapeHtml(v('antecedentes_morbidos'))}</textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">03 — Psicomotor</label>
            <p class="text-xs text-gray-500 mb-1">Motor, senso perceptivo, postura, coordenação, controle de esfíncteres etc.</p>
            <textarea name="psicomotor" rows="3" class="${taCls}">${escapeHtml(v('psicomotor'))}</textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">04 — Linguagem</label>
            <p class="text-xs text-gray-500 mb-1">Aquisição, atrasos, dificuldades passadas e atuais.</p>
            <textarea name="linguagem" rows="3" class="${taCls}">${escapeHtml(v('linguagem'))}</textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">05 — Alimentação</label>
            <p class="text-xs text-gray-500 mb-1">Amamentação/tempo, desmame, dificuldades, introdução dos alimentos, hábitos atuais, seletividade, intolerâncias, alergias.</p>
            <textarea name="alimentacao" rows="3" class="${taCls}">${escapeHtml(v('alimentacao'))}</textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">06 — Sono</label>
            <p class="text-xs text-gray-500 mb-1">Insônia, hipersonia, pesadelos, sonambulismo, terror noturno, enurese noturna.</p>
            <textarea name="sono" rows="3" class="${taCls}">${escapeHtml(v('sono'))}</textarea>
          </div>
        </div>
      </details>
    </div>

    <!-- 07 - Escolaridade -->
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <details class="group">
        <summary class="${summCls}">07 — Escolaridade ${chevron}</summary>
        <div class="${bodyPad} space-y-3">
          <div>
            <label class="${labelCls}">Relato:</label>
            <p class="text-xs text-gray-500 mb-1">Entrada na escola, adaptação, desempenho, desenvolvimento intelectual, dificuldades, repetência.</p>
            <textarea name="escolaridade" rows="3" class="${taCls}">${escapeHtml(v('escolaridade'))}</textarea>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="${labelCls}">Escola:</label><input type="text" name="escola_nome" value="${escapeHtml(v('escola_nome'))}" class="${inputCls}"></div>
            <div><label class="${labelCls}">Telefone:</label><input type="text" name="escola_telefone" value="${escapeHtml(v('escola_telefone'))}" class="${inputCls}"></div>
          </div>
        </div>
      </details>
    </div>

    <!-- 08-10 Social -->
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <details class="group">
        <summary class="${summCls}">08 a 10 — Social, Sexualidade e Situação Socioeconômica ${chevron}</summary>
        <div class="${bodyPad} space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">08 — Sociabilidade</label>
            <p class="text-xs text-gray-500 mb-1">Afetuosidade, agressividade, introversão, extroversão.</p>
            <textarea name="sociabilidade" rows="2" class="${taCls}">${escapeHtml(v('sociabilidade'))}</textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">09 — Sexualidade</label>
            <p class="text-xs text-gray-500 mb-1">Interesse pelo tema, curiosidade, questionamentos, reações dos adultos.</p>
            <textarea name="sexualidade" rows="2" class="${taCls}">${escapeHtml(v('sexualidade'))}</textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">10 — Situação Socioeconômica</label>
            <p class="text-xs text-gray-500 mb-1">Provedores, carga-horária.</p>
            <textarea name="situacao_socioeconomica" rows="2" class="${taCls}">${escapeHtml(v('situacao_socioeconomica'))}</textarea>
          </div>
        </div>
      </details>
    </div>

    <!-- 11 - Aspectos sensoriais -->
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <details class="group">
        <summary class="${summCls}">11 — Aspectos Sensoriais ${chevron}</summary>
        <div class="${bodyPad} space-y-4">
          <div>
            <p class="text-sm text-gray-700 mb-1">Apresenta alguma dificuldade para enxergar? (aproxima objeto dos olhos, franze a testa)</p>
            <div class="flex gap-6 mt-1 text-sm">
              <label class="flex items-center gap-2"><input type="radio" name="dificuldade_visao" value="1" ${rb('dificuldade_visao', 1)}> Sim</label>
              <label class="flex items-center gap-2"><input type="radio" name="dificuldade_visao" value="0" ${rb('dificuldade_visao', 0)}> Não</label>
            </div>
            <div class="mt-2"><label class="${labelSmCls}">Se sim, especificar:</label>
            <input type="text" name="dificuldade_visao_desc" value="${escapeHtml(v('dificuldade_visao_desc'))}" class="${inputCls}"></div>
          </div>
          <div>
            <p class="text-sm text-gray-700 mb-1">Aparenta ter dificuldade para ouvir? (necessita de repetir uma explicação dada anteriormente)</p>
            <div class="flex gap-6 mt-1 text-sm">
              <label class="flex items-center gap-2"><input type="radio" name="dificuldade_audicao" value="1" ${rb('dificuldade_audicao', 1)}> Sim</label>
              <label class="flex items-center gap-2"><input type="radio" name="dificuldade_audicao" value="0" ${rb('dificuldade_audicao', 0)}> Não</label>
            </div>
          </div>
        </div>
      </details>
    </div>

    <!-- 12 - Antecedentes familiares + Constelação -->
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <details class="group">
        <summary class="${summCls}">12 — Antecedentes Familiares e Constelação ${chevron}</summary>
        <div class="${bodyPad} space-y-4">
          <div>
            <p class="text-sm font-medium text-gray-700 mb-2">Antecedentes familiares:</p>
            <div class="grid grid-cols-2 gap-2 text-sm">
              <label class="flex items-center gap-2"><input type="checkbox" name="antec_fam_doencas" value="1" class="rounded accent-monday-blue" ${ck('antec_fam_doencas')}> Doenças</label>
              <label class="flex items-center gap-2"><input type="checkbox" name="antec_fam_alcoolismo" value="1" class="rounded accent-monday-blue" ${ck('antec_fam_alcoolismo')}> Alcoolismo</label>
              <label class="flex items-center gap-2"><input type="checkbox" name="antec_fam_homicidio" value="1" class="rounded accent-monday-blue" ${ck('antec_fam_homicidio')}> Homicídio</label>
              <label class="flex items-center gap-2"><input type="checkbox" name="antec_fam_def_mental" value="1" class="rounded accent-monday-blue" ${ck('antec_fam_def_mental')}> Deficiências mentais</label>
              <label class="flex items-center gap-2"><input type="checkbox" name="antec_fam_suicidio" value="1" class="rounded accent-monday-blue" ${ck('antec_fam_suicidio')}> Suicídio</label>
              <label class="flex items-center gap-2"><input type="checkbox" name="antec_fam_drogadicao" value="1" class="rounded accent-monday-blue" ${ck('antec_fam_drogadicao')}> Drogadição</label>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2">
              <div><label class="${labelSmCls}">Quais doenças:</label><input type="text" name="antec_fam_doencas_quais" value="${escapeHtml(v('antec_fam_doencas_quais'))}" class="${inputSmCls}"></div>
              <div><label class="${labelSmCls}">Grau de parentesco:</label><input type="text" name="antec_fam_grau_parentesco" value="${escapeHtml(v('antec_fam_grau_parentesco'))}" class="${inputSmCls}"></div>
            </div>
            <div class="mt-2"><label class="${labelSmCls}">Outros:</label>
            <textarea name="antec_fam_outros" rows="2" class="${taCls}">${escapeHtml(v('antec_fam_outros'))}</textarea></div>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-700 mb-2">Constelação familiar:</p>
            <div class="grid grid-cols-2 gap-3">
              <div><label class="${labelCls}">Nº de irmãos/sexo/idades:</label><input type="text" name="num_irmaos" value="${escapeHtml(v('num_irmaos'))}" class="${inputCls}"></div>
              <div><label class="${labelCls}">Posição no bloco familiar:</label><input type="text" name="posicao_familiar" value="${escapeHtml(v('posicao_familiar'))}" class="${inputCls}"></div>
            </div>
            <div class="mt-2">
              <p class="text-sm text-gray-600 mb-1">Situação dos pais:</p>
              <div class="flex flex-wrap gap-4 text-sm">
                <label class="flex items-center gap-2"><input type="radio" name="situacao_pais" value="casados" ${rb('situacao_pais', 'casados')}> Casados</label>
                <label class="flex items-center gap-2"><input type="radio" name="situacao_pais" value="separados" ${rb('situacao_pais', 'separados')}> Separados</label>
                <label class="flex items-center gap-2"><input type="radio" name="situacao_pais" value="separados_nova_estrutura" ${rb('situacao_pais', 'separados_nova_estrutura')}> Separados com nova estrutura familiar</label>
              </div>
            </div>
          </div>
        </div>
      </details>
    </div>

    <!-- Dados de triagem -->
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <details class="group">
        <summary class="${summCls}">Dados de Triagem ${chevron}</summary>
        <div class="${bodyPad} space-y-3">
          <div class="grid grid-cols-3 gap-3">
            <div><label class="${labelCls}">Triado por:</label><input type="text" name="triagem_por" value="${escapeHtml(v('triagem_por'))}" class="${inputCls}"></div>
            <div><label class="${labelCls}">Início:</label><input type="text" name="triagem_inicio" value="${escapeHtml(v('triagem_inicio'))}" class="${inputCls}" placeholder="HH:MM" inputmode="numeric" maxlength="5" autocomplete="off"></div>
            <div><label class="${labelCls}">Término:</label><input type="text" name="triagem_termino" value="${escapeHtml(v('triagem_termino'))}" class="${inputCls}" placeholder="HH:MM" inputmode="numeric" maxlength="5" autocomplete="off"></div>
          </div>
          <div><label class="${labelCls}">Hipótese diagnóstica:</label>
          <textarea name="hipotese_diagnostica" rows="2" class="${taCls}">${escapeHtml(v('hipotese_diagnostica'))}</textarea></div>
          <div>
            <p class="text-sm text-gray-600 mb-1">Conclusão:</p>
            <div class="flex gap-6 text-sm">
              <label class="flex items-center gap-2"><input type="radio" name="conclusao" value="elegivel" ${rb('conclusao', 'elegivel')}> Elegível</label>
              <label class="flex items-center gap-2"><input type="radio" name="conclusao" value="inelegivel" ${rb('conclusao', 'inelegivel')}> Inelegível</label>
            </div>
          </div>
          <div><label class="${labelCls}">Indicação terapêutica:</label>
          <textarea name="indicacao_terapeutica" rows="2" class="${taCls}">${escapeHtml(v('indicacao_terapeutica'))}</textarea></div>
          <div>
            <p class="text-sm text-gray-600 mb-1">Necessidade de atendimento:</p>
            <div class="flex gap-6 text-sm">
              <label class="flex items-center gap-2"><input type="radio" name="necessidade_atendimento" value="urgente" ${rb('necessidade_atendimento', 'urgente')}> Urgente</label>
              <label class="flex items-center gap-2"><input type="radio" name="necessidade_atendimento" value="curto_prazo" ${rb('necessidade_atendimento', 'curto_prazo')}> Curto prazo</label>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="${labelCls}">Profissional:</label><input type="text" name="profissional_triagem" value="${escapeHtml(v('profissional_triagem'))}" class="${inputCls}"></div>
            <div><label class="${labelCls}">Data:</label><input type="date" name="data_triagem" value="${escapeHtml(v('data_triagem'))}" class="${inputCls}"></div>
          </div>
        </div>
      </details>
    </div>

    <!-- Impressões gerais e complementares -->
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <details class="group">
        <summary class="${summCls}">Impressões Gerais e Informações Complementares ${chevron}</summary>
        <div class="${bodyPad} space-y-4">
          <div>
            <p class="text-sm font-medium text-gray-700 mb-2">Impressões durante o atendimento:</p>
            <div class="grid grid-cols-2 gap-2 text-sm">
              <label class="flex items-center gap-2"><input type="checkbox" name="impressao_tranquilo" value="1" class="rounded accent-monday-blue" ${ck('impressao_tranquilo')}> Tranquilo</label>
              <label class="flex items-center gap-2"><input type="checkbox" name="impressao_ansioso" value="1" class="rounded accent-monday-blue" ${ck('impressao_ansioso')}> Ansioso</label>
              <label class="flex items-center gap-2"><input type="checkbox" name="impressao_seguro" value="1" class="rounded accent-monday-blue" ${ck('impressao_seguro')}> Seguro</label>
              <label class="flex items-center gap-2"><input type="checkbox" name="impressao_alegre" value="1" class="rounded accent-monday-blue" ${ck('impressao_alegre')}> Alegre</label>
              <label class="flex items-center gap-2"><input type="checkbox" name="impressao_queixoso" value="1" class="rounded accent-monday-blue" ${ck('impressao_queixoso')}> Queixoso</label>
              <label class="flex items-center gap-2"><input type="checkbox" name="impressao_intolerante" value="1" class="rounded accent-monday-blue" ${ck('impressao_intolerante')}> Intolerante</label>
              <label class="flex items-center gap-2"><input type="checkbox" name="impressao_atencao" value="1" class="rounded accent-monday-blue" ${ck('impressao_atencao')}> Atenção</label>
              <label class="flex items-center gap-2"><input type="checkbox" name="impressao_adequacao_respostas" value="1" class="rounded accent-monday-blue" ${ck('impressao_adequacao_respostas')}> Adequação das respostas</label>
            </div>
          </div>
          <div><label class="${labelCls}">Rotina do paciente:</label>
          <textarea name="rotina_paciente" rows="2" class="${taCls}">${escapeHtml(v('rotina_paciente'))}</textarea></div>
          <div><label class="${labelCls}">Perdas recentes ou mudanças bruscas:</label>
          <textarea name="perdas_recentes" rows="2" class="${taCls}">${escapeHtml(v('perdas_recentes'))}</textarea></div>
          <div><label class="${labelCls}">Outras informações relevantes:</label>
          <textarea name="outras_informacoes" rows="3" class="${taCls}">${escapeHtml(v('outras_informacoes'))}</textarea></div>
        </div>
      </details>
    </div>

  </div>`;
}

function collectAnamneseRoteiroFormData(formEl) {
  const fd = new FormData(formEl);
  const data = {};

  const textFields = [
    'nome_paciente', 'motivo_atendimento',
    'acomp_psi_onde', 'acomp_psi_quando',
    'acomp_psicoped_onde', 'acomp_psicoped_quando',
    'acomp_fono_onde', 'acomp_fono_quando',
    'acomp_neuro_onde', 'acomp_neuro_quando',
    'acomp_to_onde', 'acomp_to_quando',
    'acomp_fisio_onde', 'acomp_fisio_quando',
    'acomp_outros',
    'historia_gestacional', 'antecedentes_morbidos', 'psicomotor', 'linguagem',
    'alimentacao', 'sono', 'escolaridade', 'escola_nome', 'escola_telefone',
    'sociabilidade', 'sexualidade', 'situacao_socioeconomica',
    'dificuldade_visao_desc',
    'antec_fam_doencas_quais', 'antec_fam_outros', 'antec_fam_grau_parentesco',
    'num_irmaos', 'posicao_familiar', 'situacao_pais',
    'triagem_por', 'triagem_inicio', 'triagem_termino',
    'hipotese_diagnostica', 'conclusao',
    'indicacao_terapeutica', 'necessidade_atendimento',
    'profissional_triagem', 'data_triagem',
    'rotina_paciente', 'perdas_recentes', 'outras_informacoes'
  ];
  for (const n of textFields) {
    const val = fd.get(n);
    data[n] = (val === '' || val === null) ? null : String(val).trim();
  }

  // Checkboxes: ausente no FormData quando desmarcado
  const checkboxFields = [
    'acomp_psicologico', 'acomp_psicopedagogico', 'acomp_fonoaudiologico',
    'acomp_neurologico', 'acomp_terapia_ocupacional', 'acomp_fisioterapia',
    'antec_fam_doencas', 'antec_fam_alcoolismo', 'antec_fam_homicidio',
    'antec_fam_def_mental', 'antec_fam_suicidio', 'antec_fam_drogadicao',
    'impressao_tranquilo', 'impressao_ansioso', 'impressao_seguro',
    'impressao_alegre', 'impressao_queixoso', 'impressao_intolerante',
    'impressao_atencao', 'impressao_adequacao_respostas'
  ];
  for (const n of checkboxFields) {
    data[n] = (fd.has(n) && fd.get(n) === '1') ? 1 : 0;
  }

  // Radios mapeados para TINYINT (null quando nenhum selecionado)
  const radioIntFields = ['dificuldade_visao', 'dificuldade_audicao'];
  for (const n of radioIntFields) {
    const val = fd.get(n);
    data[n] = val !== null ? parseInt(val, 10) : null;
  }

  return data;
}

function aplicarMascaraHora(input) {
  if (!input) return;
  const formatar = () => {
    const numeros = String(input.value || '').replace(/\D/g, '').slice(0, 4);
    input.value = numeros.length > 2 ? `${numeros.slice(0, 2)}:${numeros.slice(2)}` : numeros;
  };
  input.addEventListener('input', formatar);
  input.addEventListener('paste', () => setTimeout(formatar, 0));
  formatar();
}

/* =========================================================
   MODAL DE SUCESSO
   ========================================================= */

function openAtendimentoSuccessModal({ title, message, onContinue, onClose }) {
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4';
  modal.innerHTML = `
    <div class="w-full max-w-md rounded-2xl bg-white shadow-monday-lg overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">${escapeHtml(title)}</h3>
      </div>
      <div class="px-5 py-4">
        <p class="text-sm text-gray-600">${escapeHtml(message)}</p>
      </div>
      <div class="px-5 py-4 bg-gray-50 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <button type="button" data-action="close" class="px-4 py-2 rounded-xl bg-red-600 text-white font-medium hover:bg-red-700 transition">Fechar</button>
        <button type="button" data-action="continue" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition">Continuar anamnese</button>
      </div>
    </div>
  `;

  const cleanup = () => modal.remove();
  modal.querySelector('[data-action="continue"]')?.addEventListener('click', () => { cleanup(); onContinue?.(); });
  modal.querySelector('[data-action="close"]')?.addEventListener('click', () => { cleanup(); onClose?.(); });
  modal.addEventListener('click', (event) => { if (event.target === modal) { cleanup(); onClose?.(); } });
  document.body.appendChild(modal);
}

function openAtendimentoConfirmModal({ title, message, confirmLabel = 'Confirmar', cancelLabel = 'Cancelar', onConfirm }) {
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4';
  modal.innerHTML = `
    <div class="w-full max-w-md rounded-2xl bg-white shadow-monday-lg overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">${escapeHtml(title)}</h3>
      </div>
      <div class="px-5 py-4">
        <p class="text-sm leading-6 text-gray-600">${escapeHtml(message)}</p>
      </div>
      <div class="px-5 py-4 bg-gray-50 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <button type="button" data-action="cancel" class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 font-medium hover:bg-gray-100 transition">${escapeHtml(cancelLabel)}</button>
        <button type="button" data-action="confirm" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition">${escapeHtml(confirmLabel)}</button>
      </div>
    </div>
  `;

  const cleanup = () => modal.remove();
  const btnConfirm = modal.querySelector('[data-action="confirm"]');
  const btnCancel = modal.querySelector('[data-action="cancel"]');
  btnCancel?.addEventListener('click', cleanup);
  btnConfirm?.addEventListener('click', async () => {
    if (btnConfirm) {
      btnConfirm.disabled = true;
      btnConfirm.innerHTML = `${getButtonSpinnerHtml()} Confirmando...`;
    }
    if (btnCancel) btnCancel.disabled = true;
    try {
      await onConfirm?.();
      cleanup();
    } catch (err) {
      if (btnConfirm) {
        btnConfirm.disabled = false;
        btnConfirm.textContent = confirmLabel;
      }
      if (btnCancel) btnCancel.disabled = false;
      alert(err.message || 'Erro ao confirmar a ação.');
    }
  });
  modal.addEventListener('click', (event) => { if (event.target === modal) cleanup(); });
  document.body.appendChild(modal);
}

/* =========================================================
   RENDER PRINCIPAL
   ========================================================= */

export async function renderAtendimento(container, opts = {}) {
  if (typeof tinymce !== 'undefined' && tinymce.get('prontuario-texto')) tinymce.get('prontuario-texto').remove();
  const { consultaId: preselectConsultaId, date: dataFiltro, anamneseTipo: anamneseTipoPrefill } = opts;
  const dataConsulta = dataFiltro || new Date().toISOString().slice(0, 10);
  container.innerHTML = getSpinnerHtml('Carregando atendimento...');

  const consultasParaAtendimento = await getConsultasParaAtendimento(dataConsulta).catch(() => []);

  let consultaSelecionada = preselectConsultaId
    ? consultasParaAtendimento.find((c) => Number(c.id) === Number(preselectConsultaId))
    : null;
  if (!consultaSelecionada && preselectConsultaId) {
    try { consultaSelecionada = await getConsulta(preselectConsultaId); } catch { consultaSelecionada = null; }
  }

  const consultasRender = consultaSelecionada && !consultasParaAtendimento.some((c) => Number(c.id) === Number(consultaSelecionada.id))
    ? [consultaSelecionada, ...consultasParaAtendimento]
    : consultasParaAtendimento;

  // Busca paralela das duas anamneses
  let anamneseExistente = null;
  let anamneseRoteiroExistente = null;
  if (consultaSelecionada) {
    const [resAdulto, resRoteiro] = await Promise.allSettled([
      getAnamneseByConsulta(consultaSelecionada.id),
      getAnamneseRoteiroByConsulta(consultaSelecionada.id),
    ]);
    anamneseExistente       = resAdulto.status  === 'fulfilled' ? resAdulto.value  : null;
    anamneseRoteiroExistente = resRoteiro.status === 'fulfilled' ? resRoteiro.value : null;
  }

  const listHtml = `
  <div class="mb-4 flex flex-wrap items-center gap-3">
    <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
      <span>Data:</span>
      <input type="date" id="atend-data-filtro" value="${dataConsulta}" class="px-3 py-2 border border-gray-300 rounded-lg">
    </label>
  </div>
  ` + (consultasRender.length === 0
    ? '<div class="bg-white rounded-xl shadow-monday p-8 text-center text-gray-500"><p>Nenhuma consulta disponível para atendimento.</p><p class="text-sm mt-2">Consultas designadas a você ou plantonistas da sua especialidade aparecem aqui.</p></div>'
    : `
    <div class="space-y-3 mb-6">
      <h2 class="font-semibold text-lg">Consultas para atendimento</h2>
      <p class="text-sm text-gray-500">Clique em uma consulta para iniciar o atendimento</p>
      <div class="grid gap-3 sm:grid-cols-2">
        ${consultasRender.map((c) => {
          const isAtendimento = (c.status || '').toLowerCase().includes('em_atendimento');
          const isConcluida = (c.status || '').toLowerCase().includes('concluida') || (c.status || '').toLowerCase().includes('concluída');
          return `
          <button type="button" data-consulta-id="${c.id}" class="atend-consulta-card text-left bg-white rounded-xl shadow-monday p-4 hover:shadow-monday-lg hover:border-monday-blue border-2 border-transparent transition truncate overflow-hidden ${consultaSelecionada?.id === c.id ? 'ring-2 ring-monday-blue border-monday-blue' : ''}">
            <span class="font-medium text-gray-800 block truncate">${escapeHtml(c.paciente_nome || '')}</span>
            <span class="text-sm text-gray-500">${c.data_consulta} ${fmtTime(c.hora_inicio_prevista)} — ${escapeHtml(c.especialidade_nome || '')}</span>
            <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full ${isConcluida ? 'bg-emerald-100 text-emerald-800' : (isAtendimento ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700')}">${statusLabel(c.status)}</span>
          </button>
          `;
        }).join('')}
      </div>
    </div>
  `);

  // Selector de tipo de anamnese
  const tipoSelectorHtml = `
    <div class="flex gap-1 mb-4 p-1 bg-gray-100 rounded-xl">
      <button type="button" data-tipo="adulto" class="tipo-anamnese-btn flex-1 py-2 px-3 rounded-lg text-sm font-medium transition flex items-center justify-center gap-1.5">
        <i data-lucide="user" class="w-4 h-4"></i> Avaliação Adulto
      </button>
      <button type="button" data-tipo="roteiro" class="tipo-anamnese-btn flex-1 py-2 px-3 rounded-lg text-sm font-medium transition flex items-center justify-center gap-1.5">
        <i data-lucide="baby" class="w-4 h-4"></i> Roteiro Infantojuvenil
      </button>
    </div>
  `;
  const statusConsultaSelecionada = String(consultaSelecionada?.status || '').toLowerCase();
  const isConsultaEmAtendimento = !!consultaSelecionada && statusConsultaSelecionada.includes('em_atendimento');
  const podeIniciarAtendimento = !!consultaSelecionada
    && !statusConsultaSelecionada.includes('em_atendimento')
    && !statusConsultaSelecionada.includes('concluida')
    && !statusConsultaSelecionada.includes('concluída');

  const formSection = `
    <div id="atend-form-section" class="bg-white rounded-xl shadow-monday p-4 space-y-4 ${!consultaSelecionada ? 'opacity-60 pointer-events-none' : ''}">
      <h2 class="font-semibold text-lg">Atendimento clínico</h2>
      ${consultaSelecionada ? `
      <div class="p-3 bg-gray-50 rounded-lg">
        <p class="font-medium">${escapeHtml(consultaSelecionada.paciente_nome || '')}</p>
        <p class="text-sm text-gray-500">${consultaSelecionada.data_consulta} ${fmtTime(consultaSelecionada.hora_inicio_prevista)} — ${escapeHtml(consultaSelecionada.especialidade_nome || '')}</p>
        <p class="text-xs text-gray-500 mt-1">${escapeHtml(consultaSelecionada.profissional_nome || 'Plantonista')}</p>
      </div>
      ` : '<p class="text-gray-500 text-sm">Selecione uma consulta acima para iniciar.</p>'}
      <div id="paciente-dados-readonly" class="hidden p-3 bg-gray-50 rounded-lg text-sm space-y-1"></div>
      <div class="flex gap-2 border-b border-gray-200">
        <button type="button" data-tab="anamnese" class="tab-atend px-4 py-2 font-medium text-monday-blue border-b-2 border-monday-blue -mb-px">Anamnese</button>
        <button type="button" data-tab="prontuario" class="tab-atend px-4 py-2 font-medium text-gray-500 hover:text-gray-700">Prontuário IA</button>
      </div>
      <div id="tab-anamnese" class="tab-content">
        ${tipoSelectorHtml}
        <div id="anamnese-adulto-section">
          <form id="form-anamnese">${buildAnamneseFormHtml(anamneseExistente ?? {})}</form>
          <input type="hidden" id="anamnese-id" value="${anamneseExistente?.id || ''}">
        </div>
        <div id="anamnese-roteiro-section" class="hidden">
          <form id="form-anamnese-roteiro">${buildAnamneseRoteiroFormHtml(anamneseRoteiroExistente ?? {})}</form>
          <input type="hidden" id="anamnese-roteiro-id" value="${anamneseRoteiroExistente?.id || ''}">
        </div>
        <input type="hidden" id="atend-consulta-id" value="${consultaSelecionada?.id || preselectConsultaId || 0}">
        <button type="button" id="btn-salvar-anamnese" class="mt-4 w-full py-3 bg-monday-blue text-white rounded-xl font-medium hover:bg-monday-blue/90">Salvar anamnese</button>
      </div>
      <div id="tab-prontuario" class="tab-content hidden">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Instruções adicionais (opcional)</label>
          <p class="text-xs text-gray-500 mb-1">O prontuário é gerado com base no cadastro do beneficiário e na anamnese. Use este campo para complementar com instruções.</p>
          <textarea id="atend-dados" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="Parâmetros, pedidos ou instruções para IA..." ${consultaSelecionada ? '' : 'disabled'}></textarea>
        </div>
        <button type="button" id="btn-gerar-ia" class="w-full min-h-touch py-3 bg-purple-accent text-white rounded-xl font-medium hover:bg-purple-accent-hover transition" ${consultaSelecionada ? '' : 'disabled'}>
          Gerar prontuário com IA
        </button>
        <div id="resultado-ia" class="hidden">
          <label class="block text-sm font-medium text-gray-700 mb-1">Prontuário gerado (edite se necessário)</label>
          <textarea id="prontuario-texto" rows="12" class="w-full px-4 py-3 border border-gray-300 rounded-lg font-mono text-sm" placeholder="O prontuário gerado pela IA aparecerá aqui."></textarea>
          <button type="button" id="btn-salvar-pront" class="mt-3 w-full py-3 bg-monday-blue text-white rounded-lg font-medium">Salvar prontuário</button>
        </div>
      </div>
      <button type="button" id="btn-concluir-atendimento" class="w-full min-h-touch py-3 bg-emerald-700 text-white rounded-xl font-medium hover:bg-emerald-800 transition" ${isConsultaEmAtendimento ? '' : 'style="display:none"'}>
        Concluir atendimento
      </button>
      <button type="button" id="btn-iniciar-atendimento" class="w-full min-h-touch py-3 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition" ${podeIniciarAtendimento ? '' : 'style="display:none"'}>
        Iniciar atendimento
      </button>
    </div>
  `;

  container.innerHTML = listHtml + formSection;

  // Filtro de data
  container.querySelector('#atend-data-filtro')?.addEventListener('change', (e) => {
    const d = e.target.value;
    if (d) renderAtendimento(container, { date: d, consultaId: consultaSelecionada?.id });
  });

  // Seleção de consulta
  container.querySelectorAll('.atend-consulta-card').forEach((btn) => {
    btn.onclick = () => {
      const id = parseInt(btn.dataset.consultaId, 10);
      renderAtendimento(container, { consultaId: id, date: dataConsulta });
    };
  });

  const consultaIdInput    = container.querySelector('#atend-consulta-id');
  const btnIniciar         = container.querySelector('#btn-iniciar-atendimento');
  const btnConcluir        = container.querySelector('#btn-concluir-atendimento');
  const btnGerar           = container.querySelector('#btn-gerar-ia');
  const resultadoDiv       = container.querySelector('#resultado-ia');
  const prontTextarea      = container.querySelector('#prontuario-texto');
  const formAnamnese       = container.querySelector('#form-anamnese');
  const btnSalvarAnamnese  = container.querySelector('#btn-salvar-anamnese');
  const anamneseIdInput    = container.querySelector('#anamnese-id');

  if (consultaSelecionada) {
    consultaIdInput.value = consultaSelecionada.id;
  }

  // Dados do paciente (readonly)
  if (consultaSelecionada?.aluno_id) {
    const readonlyDiv = container.querySelector('#paciente-dados-readonly');
    try {
      const p = await getPaciente(consultaSelecionada.aluno_id);
      readonlyDiv.innerHTML = `
        <p><strong>Dados do paciente (tbAluno)</strong></p>
        <p>Nome: ${escapeHtml(p.Nome || '')} | Nascimento: ${escapeHtml(p.Nascimento || '')}</p>
        <p>Telefone: ${escapeHtml(p.Telefone || p.WhatsApp || '—')} | Endereço: ${escapeHtml([p.Endereco, p.Numero, p.Bairro, p.Cidade].filter(Boolean).join(', ') || '—')}</p>
      `;
      readonlyDiv.classList.remove('hidden');
    } catch { readonlyDiv.classList.add('hidden'); }
  }

  // Tabs (Anamnese / Prontuário IA)
  container.querySelectorAll('.tab-atend').forEach((btn) => {
    btn.onclick = () => {
      container.querySelectorAll('.tab-atend').forEach((b) => {
        b.classList.remove('text-monday-blue', 'border-monday-blue');
        b.classList.add('text-gray-500');
      });
      btn.classList.add('text-monday-blue', 'border-monday-blue');
      btn.classList.remove('text-gray-500');
      container.querySelectorAll('.tab-content').forEach((tc) => tc.classList.add('hidden'));
      container.querySelector(`#tab-${btn.dataset.tab}`).classList.remove('hidden');
    };
  });

  // Seletor de tipo de anamnese
  let tipoAnamnese = anamneseTipoPrefill || ((anamneseRoteiroExistente && !anamneseExistente) ? 'roteiro' : 'adulto');

  function setTipoAnamnese(tipo) {
    tipoAnamnese = tipo;
    container.querySelectorAll('.tipo-anamnese-btn').forEach((b) => {
      const ativo = b.dataset.tipo === tipo;
      b.classList.toggle('bg-white', ativo);
      b.classList.toggle('shadow-sm', ativo);
      b.classList.toggle('text-monday-blue', ativo);
      b.classList.toggle('font-semibold', ativo);
      b.classList.toggle('text-gray-500', !ativo);
    });
    container.querySelector('#anamnese-adulto-section')?.classList.toggle('hidden', tipo !== 'adulto');
    container.querySelector('#anamnese-roteiro-section')?.classList.toggle('hidden', tipo !== 'roteiro');
  }

  setTipoAnamnese(tipoAnamnese);
  container.querySelectorAll('.tipo-anamnese-btn').forEach((btn) => {
    btn.onclick = () => setTipoAnamnese(btn.dataset.tipo);
  });
  aplicarMascaraHora(container.querySelector('input[name="triagem_inicio"]'));
  aplicarMascaraHora(container.querySelector('input[name="triagem_termino"]'));

  // Salvar anamnese (adulto ou roteiro)
  btnSalvarAnamnese?.addEventListener('click', async () => {
    const consultaId = parseInt(consultaIdInput?.value, 10) || Number(consultaSelecionada?.id) || 0;
    const alunoId    = Number(consultaSelecionada?.aluno_id) || 0;
    if (!consultaId || !alunoId) { alert('Selecione uma consulta válida.'); return; }

    btnSalvarAnamnese.disabled = true;
    btnSalvarAnamnese.innerHTML = getButtonSpinnerHtml() + ' Salvando...';
    try {
      if (tipoAnamnese === 'adulto') {
        const data = collectAnamneseFormData(formAnamnese);
        data.consulta_id = consultaId;
        data.aluno_id    = alunoId;
        const id = anamneseIdInput?.value ? parseInt(anamneseIdInput.value, 10) : 0;
        if (id > 0) {
          await putAnamnese(id, data);
        } else {
          const res = await postAnamnese(data);
          if (res.id && anamneseIdInput) anamneseIdInput.value = res.id;
        }
      } else {
        const formRoteiro    = container.querySelector('#form-anamnese-roteiro');
        const roteiroIdInput = container.querySelector('#anamnese-roteiro-id');
        const data = collectAnamneseRoteiroFormData(formRoteiro);
        data.consulta_id = consultaId;
        data.aluno_id    = alunoId;
        const id = roteiroIdInput?.value ? parseInt(roteiroIdInput.value, 10) : 0;
        if (id > 0) {
          await putAnamneseRoteiro(id, data);
        } else {
          const res = await postAnamneseRoteiro(data);
          if (res.id && roteiroIdInput) roteiroIdInput.value = res.id;
        }
      }
      openAtendimentoSuccessModal({
        title: 'Anamnese salva',
        message: 'A anamnese foi salva com sucesso. Você pode continuar editando ou fechar e recarregar o atendimento.',
        onContinue: () => {},
        onClose: () => window.location.reload()
      });
    } catch (err) {
      alert(err.message || 'Erro ao salvar.');
    } finally {
      btnSalvarAnamnese.disabled = false;
      btnSalvarAnamnese.innerHTML = 'Salvar anamnese';
    }
  });

  // Iniciar atendimento
  if (btnIniciar && podeIniciarAtendimento) {
    btnIniciar.onclick = async () => {
      btnIniciar.disabled = true;
      btnIniciar.innerHTML = getButtonSpinnerHtml() + ' Iniciando...';
      try {
        await postIniciarAtendimento(consultaSelecionada.id);
        await renderAtendimento(container, {
          consultaId: Number(consultaSelecionada.id),
          date: consultaSelecionada.data_consulta || dataConsulta
        });
      } catch (err) {
        alert(err.message);
      }
    };
  }

  // TinyMCE para prontuário
  if (btnConcluir && isConsultaEmAtendimento) {
    btnConcluir.onclick = () => {
      openAtendimentoConfirmModal({
        title: 'Concluir atendimento',
        message: 'Confirme a conclusão deste atendimento. O sistema exigirá ao menos uma anamnese, evolução ou prontuário vinculado a esta consulta.',
        confirmLabel: 'Concluir atendimento',
        cancelLabel: 'Cancelar',
        onConfirm: async () => {
          await postConcluirAtendimento(consultaSelecionada.id);
          await renderAtendimento(container, {
            consultaId: Number(consultaSelecionada.id),
            date: consultaSelecionada.data_consulta || dataConsulta
          });
        }
      });
    };
  }

  const initTinyMCEAtend = () => {
    if (typeof tinymce === 'undefined' || tinymce.get('prontuario-texto')) return;
    tinymce.init({
      selector: '#prontuario-texto',
      menubar: false,
      height: 320,
      plugins: 'lists code',
      toolbar: 'undo redo | bold italic underline | bullist numlist | code',
      language: 'pt_BR',
      branding: false
    });
  };
  const removeTinyMCEAtend = () => {
    if (typeof tinymce !== 'undefined' && tinymce.get('prontuario-texto')) tinymce.get('prontuario-texto').remove();
  };

  btnGerar.onclick = async () => {
    const alunoId = Number(consultaSelecionada?.aluno_id) || 0;
    const dados = container.querySelector('#atend-dados').value.trim();
    if (!alunoId) { alert('Selecione uma consulta válida.'); return; }
    removeTinyMCEAtend();
    btnGerar.disabled = true;
    btnGerar.innerHTML = getButtonSpinnerHtml() + ' Gerando...';
    prontTextarea.value = '';
    resultadoDiv.classList.remove('hidden');
    try {
      const consultaId = parseInt(consultaIdInput.value, 10) || 0;
      await postProntuarioStreamIa(
        { consulta_id: consultaId, aluno_id: alunoId, observacoes_adicionais: dados },
        {
          onDelta: (chunk, fullText) => { prontTextarea.value = fullText; },
          onDone: (fullText) => {
            const conteudo = fullText || prontTextarea.value;
            prontTextarea.value = markdownToHtml(conteudo);
            initTinyMCEAtend();
          },
          onError: (msg) => { alert(msg); }
        }
      );
    } catch (err) {
      alert(err.message);
    } finally {
      btnGerar.disabled = false;
      btnGerar.innerHTML = 'Gerar prontuário com IA';
    }
  };

  container.querySelector('#btn-salvar-pront').onclick = async () => {
    const alunoId    = Number(consultaSelecionada?.aluno_id) || 0;
    const consultaId = parseInt(consultaIdInput.value, 10) || 0;
    const conteudo   = (typeof tinymce !== 'undefined' && tinymce.get('prontuario-texto'))
      ? tinymce.get('prontuario-texto').getContent().trim()
      : prontTextarea.value.trim();
    if (!alunoId || !conteudo) { alert('Gere o prontuário com IA antes de salvar.'); return; }
    const btnSalvar = container.querySelector('#btn-salvar-pront');
    const orig = btnSalvar.innerHTML;
    btnSalvar.disabled = true;
    btnSalvar.innerHTML = getButtonSpinnerHtml() + ' Salvando...';
    try {
      await postProntuario({ consulta_id: consultaId, aluno_id: alunoId, conteudo_ia: conteudo, conteudo_editado: conteudo, status: 'finalizado' });
      alert('Prontuário salvo.');
      removeTinyMCEAtend();
      prontTextarea.value = '';
      resultadoDiv.classList.add('hidden');
      await renderAtendimento(container, { consultaId: consultaSelecionada?.id });
    } catch (err) {
      alert(err.message);
    } finally {
      btnSalvar.disabled = false;
      btnSalvar.innerHTML = orig;
    }
  };

  if (typeof lucide !== 'undefined') lucide.createIcons();
}
