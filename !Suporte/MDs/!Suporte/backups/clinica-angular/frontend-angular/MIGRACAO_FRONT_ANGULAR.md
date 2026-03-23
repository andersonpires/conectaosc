# Plano de Reestruturacao Front-end Clinica (Angular 18+)

## Objetivo
Migrar o front-end atual de `/clinica` para Angular, preservando o backend existente em `/clinica/api` e realizando a troca de forma progressiva.

## Estrategia de migracao
1. Executar em paralelo:
- Front atual permanece funcionando em `/clinica/`.
- Novo front Angular evolui em `/clinica/frontend-angular/`.

2. Manter contratos da API:
- Reuso direto dos endpoints atuais (`/pacientes`, `/agenda`, `/prontuarios`, `/profissionais`).
- Requests com cookie de sessao (`withCredentials`).

3. Migracao por telas prioritarias:
- Dashboard inicial.
- Pagina do paciente.
- Pagina de configuracoes.
- Layout mobile-first com adaptacao desktop.

4. Corte controlado (quando aprovado):
- Build do Angular.
- Publicacao em pasta servida pelo Apache.
- Atualizacao do ponto de entrada de `/clinica` para o bundle Angular.

## Entregavel desta etapa
- Projeto Angular criado e configurado (`clinica/frontend-angular`).
- Shell com roteamento e navegacao.
- 3 telas baseadas nos layouts de `!Suporte/layout_2`.
- Integracao inicial com API real sem alterar backend.

## Proximos passos recomendados
1. Portar tela de agenda completa com filtros e status.
2. Portar fluxo de atendimento/prontuario com acoes (`iniciar`, `concluir`, `gerar ia`).
3. Adicionar guard de autenticacao e fallback de login.
4. Definir estrategia final de deploy (subpasta dedicada ou substituicao de `/clinica/index.php`).
