# Verificacao de Layout - Etapa 5

Referencias desta etapa:
- `clinica/!Suporte/layout_2/formulário_de_anamnese_clínica/code.html`
- `clinica/!Suporte/layout_2/prescription_and_certificate_issuer/code.html`

## Anamnese (`/anamnese`) - evolucao funcional
- Aderente: estrutura visual principal mantida (header sticky, bloco do paciente, progresso, cards por secao e CTA fixo).
- Aderente: linguagem visual mobile e barra inferior continuam no padrao do mock.
- Evolucao funcional: ampliacao de campos conectados ao backend sem descaracterizar layout:
  - `acompanhamento_psicologico`
  - `medicacoes_psicotropicos`
  - `internacao_psiquiatrica`
  - `sentimento_ultimos_meses`
  - `atividades_fazem_bem`
  - `dificuldades_memoria`
  - + campos ja existentes da etapa anterior.

## Prontuario (`/prontuario`) - fluxo integrado
- Aderente: layout base de emissao de documentos preservado (header, tabs, busca, card de paciente, assinatura, CTA principal).
- Evolucao funcional: adicionado CTA secundario "Abrir Anamnese" no bloco de acoes, mantendo hierarquia visual.
- Fluxo: ao clicar em "Abrir Anamnese", navega para `/anamnese?aluno_id=<id>` com paciente pre-selecionado.

## Integracao API desta etapa
- Anamnese (leitura/gravação) mantida em:
  - `GET /clinica/api/anamnese?aluno_id=...`
  - `POST /clinica/api/anamnese`
  - `PUT /clinica/api/anamnese/{id}`
- Tipagem e payload atualizados para novos campos em:
  - `src/app/core/api/api.types.ts`
  - `src/app/core/api/api.service.ts`

## Verificacao tecnica
- Build Angular: OK (`npm.cmd run build`).
- Front legado mantido sem alteracoes.
