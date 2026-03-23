# Verificacao de Layout - Etapa 4

Referencias desta etapa:
- `clinica/!Suporte/layout_2/patient_directory_list/code.html`
- `clinica/!Suporte/layout_2/formulário_de_anamnese_clínica/code.html`

## Lista de Pacientes (`/pacientes`)
- Aderente: status bar superior, header com titulo, campo de busca com icone, filtros em pills e cards de pacientes.
- Aderente: FAB e barra inferior com 5 itens no mesmo padrao mobile do modelo.
- Aderente: tipografia e iconografia Material nos mesmos pontos de destaque.
- Parcial: avatar/estado online do mock foram convertidos para dados reais (iniciais e contato da API).

## Anamnese (`/anamnese`)
- Aderente: header sticky com bloco do paciente, barra de progresso, seções em cards colapsaveis visuais e CTA fixo "Salvar Anamnese".
- Aderente: navegação inferior no padrao do mock.
- Aderente: paleta base (`primary` azul e CTA verde-clinico) conforme referência.
- Parcial: campos completos do mock foram reduzidos para um conjunto funcional ligado ao backend (`crenca_religiao`, `qualidade_sono`, `uso_alcool_substancias`, `observacoes_gerais`).

## Integracao API desta etapa
- Lista de pacientes: `GET /clinica/api/pacientes`
- Anamnese:
  - `GET /clinica/api/anamnese?aluno_id=...`
  - `POST /clinica/api/anamnese`
  - `PUT /clinica/api/anamnese/{id}`

## Verificacao tecnica
- Build Angular: OK (`npm.cmd run build`).
- Rotas adicionadas e funcionais:
  - `/pacientes`
  - `/anamnese`
- Front legado mantido sem alteracoes.
