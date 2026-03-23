# Migração 004 - Especialidade do Colaborador

Para que o profissional possa atender consultas **Plantonistas** da sua especialidade, execute a migração 004:

**URL:** `/conectaosc/clinica/!Suporte/migrations/004_run_alter_tbuser_especialidade.php`

Após executar, configure no **formColaborador** (Colaboradores):
- Marque "Profissional de saúde" 
- Selecione a **Especialidade** correspondente

Assim, o colaborador poderá atender:
- Consultas **designadas a ele** diretamente
- Consultas **Plantonistas** da especialidade configurada
