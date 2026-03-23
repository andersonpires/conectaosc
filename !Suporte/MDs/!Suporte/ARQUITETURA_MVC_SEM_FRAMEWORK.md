# Arquitetura MVC sem Framework - ConectaOSC3

## Estrutura alvo

```text
conectaosc3/
  front-end/
    config/
      app.php
    public/
      index.php
    routes/
      web.php
    src/
      Controllers/
      Core/
      Views/

  back-end/
    config/
      app.php
    public/
      index.php
    routes/
      api.php
    src/
      Controllers/
      Core/
      Middlewares/
      Models/
      Repositories/   (legado compativel)
      Services/

  public/
    index.php         (ponte para front-end/public/index.php)

  clinica/            (sem reorganizacao estrutural)
```

## Decisoes aplicadas

- Front MVC ativado por `front-end/public/index.php`.
- `public/index.php` virou ponte para o novo front-end.
- `.htaccess` agora roteia:
  - `api/v1/*` para `back-end/public/index.php`
  - rotas front (nao-arquivo/nao-diretorio) para `public/index.php`
  - `clinica/*` preservado.
- Back-end ganhou camada `Models` para padrao MVC.
- `back-end/routes/api.php` passou a instanciar Services com Models.
- `Repositories` foram mantidos para compatibilidade durante a transicao.

## Compatibilidade

- Rotas antigas com arquivo fisico continuam funcionando (regra `-f/-d` no `.htaccess`).
- Clinica permanece com organizacao propria, apenas consumindo URLs dinamicas do ConectaOSC.

## Proximos passos de consolidacao

1. Migrar gradualmente telas legadas para rotas amigaveis em `front-end/routes/web.php`.
2. Mover views novas para `front-end/src/Views` e reduzir includes diretos de scripts legados.
3. Encerrar uso direto de `Repositories` quando toda camada antiga for removida.
4. Desativar endpoints legados de `get/*.php` apos validacao completa das rotas API.
