# Solução para 404 nas rotas da API

## Sintoma
- POST para `/consultas/{id}/iniciar-atendimento` retorna 404
- Outras rotas da API (pacientes, agenda, etc.) podem também retornar 404

## Alterações feitas

1. **`.htaccess` da API** passa a rota como query string `__route__`:
   ```
   RewriteRule ^(.*)$ index.php?__route__=/$1 [QSA,L]
   ```

2. **Router** usa `$_GET['__route__']` ou `PATH_INFO` quando disponíveis, como fallback ao `REQUEST_URI`.

## Se ainda houver 404

### 1. Verificar mod_rewrite
No Apache (httpd.conf ou apache2.conf):
```
LoadModule rewrite_module modules/mod_rewrite.so
```
Deve estar descomentado.

### 2. Verificar AllowOverride
Na configuração do VirtualHost ou Directory:
```
AllowOverride All
```
(ou pelo menos `AllowOverride FileInfo`)

### 3. Testar rotas manualmente
Acesse no navegador:
- `http://localhost/conectaosc/clinica/api/` – deve retornar JSON
- `http://localhost/conectaosc/clinica/api/index.php?__route__=/pacientes` – deve listar pacientes

### 4. Servidor PHP embutido (alternativa)
Para desenvolvimento sem mod_rewrite:
```
cd conectaosc/clinica/api
php -S localhost:8080 router.php
```
(E seria necessário criar um router.php que encaminha para index.php)

## Favicon 404
O 404 em `/favicon.ico` é normal – o navegador busca o ícone do site. Pode ser ignorado ou adicionar um `favicon.ico` na raiz do projeto.
