# Prompt para Gera��o de Prontu�rio com IA

Refer�ncia completa do prompt usado no `ProntuariosController::gerarIa()` e `streamIa()`.

## Contexto
- Psic�logo hospitalar (enfermaria, UTI, ambulat�rio)
- Conforme Resolu��o CFP n� 06/2019
- Refer�ncia t�cnica: Psicopatologia-Paulo-Dalgalarrondo.pdf (Parte II, p�g. 90-593)

## Regras principais
1. **Objetividade e tecnicidade** - Termos t�cnicos hospitalares (afasia, disartria, d�ficit em fun��es executivas, humor ansioso/deprimido, orientado tempo/espa�o/pessoa)
2. **Evitar** descri��es subjetivas ou narrativas longas
3. **Nunca incluir renda** do paciente no prontu�rio

## Estrutura obrigat�ria
- Uma linha em branco antes de cada t�tulo
- CONDUTA: um item por linha

### T�picos
- **DADOS DO PACIENTE**: Nome, idade, estado civil, filhos, profiss�o, n�vel educacional. Receptivo? Ciclo sono-vig�lia?
- **HIST�RICO**: Apenas se houver prontu�rios anteriores (baseado neles)
- **ESTADO MENTAL**: Orienta��o, mem�ria, aten��o, linguagem, agita��o psicomotora, senso-percep��o, pensamento, linguagem (repete/nomeia/compreende), hist�rico psiqui�trico, fun��es executivas
- **DO HUMOR**: Ansioso, deprimido, eut�mico, disf�rico, l�bil, afetividade congruente
- **DO SOCIAL**: Rede de apoio funcional?
- **IMPRESS�O**: S�ntese t�cnica, recursos adaptativos, insight, nega��o, sobrecarga, risco
- **MANEJO**: T�cnicas usadas (anamnese, escuta ativa, psicoeduca��o, etc.)
- **CONDUTA**: Encaminhamentos, continuidade, interven��es (um por linha)

## Estilo
- Breve, claro, frases curtas
- Focar no observado
- Hip�teses entre par�nteses (nega��o? crise focal?)
