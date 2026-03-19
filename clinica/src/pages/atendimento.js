import {
  getPaciente,
  getConsulta,
  getConsultasParaAtendimento,
  postProntuarioStreamIa,
  postProntuario,
  postIniciarAtendimento,
  getAnamneseByConsulta,
  postAnamnese,
  putAnamnese
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
    .replace(/[\u0300-\u036f]/g, '');
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
  modal.querySelector('[data-action="continue"]')?.addEventListener('click', () => {
    cleanup();
    onContinue?.();
  });
  modal.querySelector('[data-action="close"]')?.addEventListener('click', () => {
    cleanup();
    onClose?.();
  });
  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      cleanup();
      onClose?.();
    }
  });

  document.body.appendChild(modal);
}

export async function renderAtendimento(container, opts = {}) {
  if (typeof tinymce !== 'undefined' && tinymce.get('prontuario-texto')) tinymce.get('prontuario-texto').remove();
  const { consultaId: preselectConsultaId, date: dataFiltro } = opts;
  const dataConsulta = dataFiltro || new Date().toISOString().slice(0, 10);
  container.innerHTML = getSpinnerHtml('Carregando atendimento...');

  const consultasParaAtendimento = await getConsultasParaAtendimento(dataConsulta).catch(() => []);

  let consultaSelecionada = preselectConsultaId
    ? consultasParaAtendimento.find((c) => Number(c.id) === Number(preselectConsultaId))
    : null;
  if (!consultaSelecionada && preselectConsultaId) {
    try {
      consultaSelecionada = await getConsulta(preselectConsultaId);
    } catch {
      consultaSelecionada = null;
    }
  }

  const consultasRender = consultaSelecionada && !consultasParaAtendimento.some((c) => Number(c.id) === Number(consultaSelecionada.id))
    ? [consultaSelecionada, ...consultasParaAtendimento]
    : consultasParaAtendimento;

  let anamneseExistente = null;
  if (consultaSelecionada) {
    try {
      anamneseExistente = await getAnamneseByConsulta(consultaSelecionada.id);
    } catch {
      anamneseExistente = null;
    }
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
          return `
          <button type="button" data-consulta-id="${c.id}" class="atend-consulta-card text-left bg-white rounded-xl shadow-monday p-4 hover:shadow-monday-lg hover:border-monday-blue border-2 border-transparent transition truncate overflow-hidden ${consultaSelecionada?.id === c.id ? 'ring-2 ring-monday-blue border-monday-blue' : ''}">
            <span class="font-medium text-gray-800 block truncate">${escapeHtml(c.paciente_nome || '')}</span>
            <span class="text-sm text-gray-500">${c.data_consulta} ${fmtTime(c.hora_inicio_prevista)} — ${escapeHtml(c.especialidade_nome || '')}</span>
            <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full ${isAtendimento ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700'}">${statusLabel(c.status)}</span>
          </button>
          `;
        }).join('')}
      </div>
    </div>
  `);

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
        <form id="form-anamnese">${buildAnamneseFormHtml(anamneseExistente ?? {})}</form>
        <input type="hidden" id="anamnese-id" value="${anamneseExistente?.id || ''}">
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
      <button type="button" id="btn-iniciar-atendimento" class="w-full min-h-touch py-3 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition" ${consultaSelecionada && !(consultaSelecionada.status || '').toLowerCase().includes('em_atendimento') ? '' : 'style="display:none"'}>
        Iniciar atendimento
      </button>
    </div>
  `;

  container.innerHTML = listHtml + formSection;

  container.querySelector('#atend-data-filtro')?.addEventListener('change', (e) => {
    const d = e.target.value;
    if (d) renderAtendimento(container, { date: d, consultaId: consultaSelecionada?.id });
  });

  container.querySelectorAll('.atend-consulta-card').forEach((btn) => {
    btn.onclick = () => {
      const id = parseInt(btn.dataset.consultaId, 10);
      renderAtendimento(container, { consultaId: id, date: dataConsulta });
    };
  });

  const consultaIdInput = container.querySelector('#atend-consulta-id');
  const btnIniciar = container.querySelector('#btn-iniciar-atendimento');
  const btnGerar = container.querySelector('#btn-gerar-ia');
  const resultadoDiv = container.querySelector('#resultado-ia');
  const prontTextarea = container.querySelector('#prontuario-texto');
  const formAnamnese = container.querySelector('#form-anamnese');
  const btnSalvarAnamnese = container.querySelector('#btn-salvar-anamnese');
  const anamneseIdInput = container.querySelector('#anamnese-id');

  if (consultaSelecionada) {
    consultaIdInput.value = consultaSelecionada.id;
  }

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
    } catch {
      readonlyDiv.classList.add('hidden');
    }
  }

  container.querySelectorAll('.tab-atend').forEach((btn) => {
    btn.onclick = () => {
      container.querySelectorAll('.tab-atend').forEach((b) => {
        b.classList.remove('text-monday-blue', 'border-monday-blue');
        b.classList.add('text-gray-500');
      });
      btn.classList.add('text-monday-blue', 'border-monday-blue');
      btn.classList.remove('text-gray-500');
      container.querySelectorAll('.tab-content').forEach((tc) => tc.classList.add('hidden'));
      const tab = btn.dataset.tab;
      container.querySelector(`#tab-${tab}`).classList.remove('hidden');
    };
  });

  btnSalvarAnamnese?.addEventListener('click', async () => {
    const consultaId = parseInt(consultaIdInput?.value, 10) || Number(consultaSelecionada?.id) || 0;
    const alunoId = Number(consultaSelecionada?.aluno_id) || 0;
    if (!consultaId || !alunoId) {
      alert('Selecione uma consulta válida.');
      return;
    }
    const data = collectAnamneseFormData(formAnamnese);
    data.consulta_id = consultaId;
    data.aluno_id = alunoId;
    btnSalvarAnamnese.disabled = true;
    btnSalvarAnamnese.innerHTML = getButtonSpinnerHtml() + ' Salvando...';
    try {
      const id = anamneseIdInput?.value ? parseInt(anamneseIdInput.value, 10) : 0;
      if (id > 0) {
        await putAnamnese(id, data);
      } else {
        const res = await postAnamnese(data);
        if (res.id) anamneseIdInput.value = res.id;
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

  if (btnIniciar && consultaSelecionada && !(consultaSelecionada.status || '').toLowerCase().includes('em_atendimento')) {
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
    if (!alunoId) {
      alert('Selecione uma consulta válida.');
      return;
    }
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
    const alunoId = Number(consultaSelecionada?.aluno_id) || 0;
    const consultaId = parseInt(consultaIdInput.value, 10) || 0;
    const conteudo = (typeof tinymce !== 'undefined' && tinymce.get('prontuario-texto'))
      ? tinymce.get('prontuario-texto').getContent().trim()
      : prontTextarea.value.trim();
    if (!alunoId || !conteudo) {
      alert('Gere o prontuário com IA antes de salvar.');
      return;
    }
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
