# Frontend MVC Structure

This folder is the frontend root using SSR in PHP and consuming the REST API from `/api`.

Main entrypoint:
- `index.php` (project root) -> `app/index.php`

Frontend routing and MVC:
- Routes: `app/routes/web.php`
- Controllers: `app/controllers`
- Models (API consumers): `app/models`
- Views: `app/views`
- Reusable partials: `app/views/partials`
- Static files: `app/assets`

Notes:
- Arquivos legados de compatibilidade foram removidos de `app/`.
- Durante a transição, o conteúdo antigo foi movido para `_legacy_app_removed/` na raiz do projeto.
