# Plano de Execucao do Planejamento de Curso

## Etapa 1. Base estrutural
- Adicionar botao de acesso ao planejamento na listagem de cursos.
- Criar rota e pagina inicial do modulo em `/cursos/planejamento?id={IdCurso}`.
- Criar migrations para plano mestre, arquivos, agenda por turma/data e historico de alteracoes.

## Etapa 2. Plano mestre das aulas
- Implementar CRUD de `tbPlanoCurso`.
- Ao criar um novo registro, sugerir automaticamente `Aula 1`, `Aula 2`, `Aula 3` e assim por diante com base em `NumeroAula`.
- Integrar `Detalhamento` com TinyMCE no mesmo padrao usado em contrato/configuracoes.
- Permitir reordenacao de aulas sem perder o historico.

## Etapa 3. Upload e armazenamento de materiais
- Implementar upload multiplo por aula com validacao de extensao, MIME type e tamanho.
- Tipos previstos: `.doc`, `.docx`, `.pdf`, `.xls`, `.xlsx`, `.jpg`, `.jpeg`, `.zip`, `.ppt`, `.pptx`, `.txt`.
- Salvar em `storage/curso/{IdCurso}/planejamento/{token-unico}/`.
- Substituir o nome original por nome unico mantendo a extensao.
- Registrar em `tbPlanoCursoArquivo` o nome original, nome salvo, descricao basica, caminho, tamanho, MIME type e colaborador responsavel.

## Etapa 4. Historico e auditoria
- Toda inclusao, alteracao e exclusao logica deve gerar registro em `tbPlanoCursoHistorico`.
- Registrar entidade afetada, id do registro, acao, campo alterado, valor anterior, valor novo, colaborador e data/hora.
- Exibir historico por aula, por arquivo e por agenda para dar rastreabilidade completa.

## Etapa 5. Agenda por turma e data
- Implementar associacao entre `tbPlanoCurso` e `tbTurma` em `tbPlanoCursoAgenda`.
- Permitir que a mesma aula seja usada por turmas diferentes em datas distintas.
- Criar visualizacao de calendario com badge da turma e indicador visual de status.
- Planejada: badge ou bolinha amarela.
- Planejada e executada: badge ou bolinha verde.

## Etapa 6. Confirmacao de execucao
- Permitir marcar se a aula ocorreu e se ocorreu conforme o planejado.
- Guardar observacoes da execucao e quem confirmou a realizacao.
- Preparar o gancho para presenca/falta dos alunos no mesmo dia.

## Etapa 7. UX mobile first
- Manter formulario em blocos verticais no mobile.
- Em telas maiores, distribuir em colunas sem comprometer legibilidade.
- Evitar tabelas largas para cadastro; usar cards e accordions para aulas.
- Deixar tabela e calendario como visualizacao secundaria no desktop.

## Etapa 8. Integracao com chamada
- Ao confirmar uma aula como executada, habilitar o fluxo de presenca ou falta da turma naquela data.
- Avaliar se a chamada deve nascer da agenda planejada ou apenas se vincular a ela.

## Observacoes de modelagem
- `tbPlanoCurso` guarda o conteudo-base da aula e nao a data.
- `tbPlanoCursoAgenda` guarda o vinculo com turma e data.
- `tbPlanoCursoArquivo` guarda os anexos de cada aula.
- `tbPlanoCursoHistorico` guarda a trilha de auditoria.

## Riscos a tratar antes do CRUD completo
- Limite de tamanho por arquivo e por lote.
- Politica de exclusao logica x fisica de anexos.
- Permissoes: quem pode criar, editar, excluir, agendar e confirmar execucao.
- Compatibilidade entre calendario, chamada e relatorios futuros.
