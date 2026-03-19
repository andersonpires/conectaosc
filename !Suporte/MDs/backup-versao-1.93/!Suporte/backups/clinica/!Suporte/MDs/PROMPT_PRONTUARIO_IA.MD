# Prompt para Geração de Prontuário com IA

Referência completa do prompt usado no `ProntuariosController::gerarIa()` e `streamIa()`.

## Contexto
- Psicólogo hospitalar (enfermaria, UTI, ambulatório)
- Conforme Resolução CFP nº 06/2019
- Referência técnica: Psicopatologia-Paulo-Dalgalarrondo.pdf (Parte II, pág. 90-593)

## Regras principais
1. **Objetividade e tecnicidade** - Termos técnicos hospitalares (afasia, disartria, déficit em funções executivas, humor ansioso/deprimido, orientado tempo/espaço/pessoa)
2. **Evitar** descrições subjetivas ou narrativas longas
3. **Nunca incluir renda** do paciente no prontuário

## Estrutura obrigatória
- Uma linha em branco antes de cada título
- CONDUTA: um item por linha

### Tópicos
- **DADOS DO PACIENTE**: Nome, idade, estado civil, filhos, profissão, nível educacional. Receptivo? Ciclo sono-vigília?
- **HISTÓRICO**: Apenas se houver prontuários anteriores (baseado neles)
- **ESTADO MENTAL**: Orientação, memória, atenção, linguagem, agitação psicomotora, senso-percepção, pensamento, linguagem (repete/nomeia/compreende), histórico psiquiátrico, funções executivas
- **DO HUMOR**: Ansioso, deprimido, eutímico, disfórico, lábil, afetividade congruente
- **DO SOCIAL**: Rede de apoio funcional?
- **IMPRESSÃO**: Síntese técnica, recursos adaptativos, insight, negação, sobrecarga, risco
- **MANEJO**: Técnicas usadas (anamnese, escuta ativa, psicoeducação, etc.)
- **CONDUTA**: Encaminhamentos, continuidade, intervenções (um por linha)

## Estilo
- Breve, claro, frases curtas
- Focar no observado
- Hipóteses entre parênteses (negação? crise focal?)
