# Guia de Regras e Padr�es de Desenvolvimento  
## Sistema de Registro de Ponto Eletr�nico

Este documento define **todas as regras t�cnicas, arquiteturais e funcionais obrigat�rias** do projeto **Sistema de Registro de Ponto Eletr�nico**.

Sempre que uma IA gerar **c�digo, SQL, arquitetura, l�gica de neg�cio, endpoints, componentes de UI ou testes**, ela **DEVE obedecer integralmente** �s regras descritas abaixo.

---

## 1. Arquitetura Geral

### Separa��o Obrigat�ria de Camadas

O sistema � composto por **duas aplica��es independentes**:

### Back-end
- API RESTful
- PHP 8.1+
- Stateless
- Respons�vel por:
  - Persist�ncia
  - Seguran�a
  - Autentica��o
  - Autoriza��o
  - Regras de neg�cio
  - C�lculos de ponto e banco de horas

### Front-end
- React + Vite + TypeScript
- Respons�vel por:
  - Interface do usu�rio
  - Experi�ncia (UX)
  - Valida��o pr�via de dados
  - Gerenciamento de estado
  - Consumo da API

**O front-end NUNCA acessa o banco de dados diretamente.**

---

## 2. Stack Tecnol�gica Oficial

### Back-end (API)
- PHP 8.1 ou superior
- PDO com Prepared Statements
- JWT (JSON Web Token)
- Bibliotecas externas permitidas apenas se:
  - Instaladas via Composer em desenvolvimento
  - Copiadas via pasta `vendor/` para produ��o
- Sem frameworks pesados (Laravel, Symfony, etc.)

### Front-end
- React 18+
- Vite
- TypeScript (obrigat�rio)
- Tailwind CSS 3.4
- Axios
- React Hook Form
- Zod
- Biblioteca UI gratuita (prefer�ncia: shadcn/ui)

---

## 3. Detec��o Autom�tica de Ambiente

O sistema detecta automaticamente se est� em **produ��o** ou **desenvolvimento**:

### Back-end

- Verifica `HTTP_HOST` em `conectabd/conexao.php`
- **Produ��o**: quando o host N�O � `localhost`, `127.0.0.1`, `::1` ou cont�m `.local`
- **Desenvolvimento**: quando o host � localhost ou cont�m `.local`
- Vari�veis definidas:
  - `APP_ENV`: `'production'` ou `'development'`
  - `APP_DEBUG`: `true` em desenvolvimento, `false` em produ��o

### Front-end

- Usa `process.env.NODE_ENV` e `import.meta.env.DEV`
- **Produ��o**: `NODE_ENV === 'production'`
- **Desenvolvimento**: `NODE_ENV === 'development'` ou `import.meta.env.DEV === true`

### Regras Obrigat�rias

- Em produ��o:
  - N�o expor stack traces
  - N�o expor detalhes de erros
  - Logs em arquivo
- Em desenvolvimento:
  - Mostrar erros detalhados
  - Logs no console e arquivo

---

## 4. Banco de Dados (Regra Cr�tica)

### Tipo
- MySQL remoto

### Conex�o Oficial

Estas credenciais s�o **definitivas para o ambiente atual**:

- Banco: `mwtech63_matricula`
- Usu�rio: `mwtech63_admin_matricula`
- Porta: `3306`
- Senha: `Iteva@100`
- Host: `162.241.2.214`

### Regras Obrigat�rias
- Usar **PDO**
- Charset `utf8mb4`
- Conex�o �nica (singleton ou pool simples)
- Nunca versionar credenciais em c�digo p�blico

---

## 4. Conven��es de Nomenclatura (Banco)

### Novas Tabelas
- Prefixo obrigat�rio: `tb_`
- Apenas letras min�sculas e `_`
- Exemplo:
  - `tb_usuarios`
  - `tb_pontos`
  - `tb_permissoes`

### Novas Views
- Prefixo obrigat�rio: `vw_`
- Exemplo:
  - `vw_horas_trabalhadas`
  - `vw_banco_horas`

**Nunca usar letras mai�sculas em nomes de tabelas ou views, exceto as que j� existem.**

---

## 5. Fuso Hor�rio

### Configura��o Obrigat�ria

- Fuso hor�rio padr�o: `America/Fortaleza` (UTC-3)
- Configurado em `api/index.php` e `api/config/bootstrap.php` via `date_default_timezone_set('America/Fortaleza')`
- Todas as datas/horas do sistema DEVEM usar este fuso hor�rio
- Nunca usar UTC ou outros fusos hor�rios

---

## 6. Sistema de Permiss�es (Granular  Regra Central)

### Conceito

O sistema utiliza **permiss�es granulares por funcionalidade**, e N�O apenas por perfil fixo.

### Estrutura Conceitual

- Permiss�o:
  - Possui um **nome**
  - Possui uma **descri��o**
  - Possui um conjunto de **funcionalidades permitidas**

- Funcionalidade:
  - Representa uma a��o ou acesso do sistema
  - Exemplos:
    - `ver_historico`
    - `registrar_ponto`
    - `gerenciar_usuarios`
    - `gerenciar_feriados`
    - `autorizar_autocadastro`

### Regra de Acesso

Para cada acesso a uma funcionalidade:

1. Verificar se o usu�rio possui a permiss�o
2. Caso n�o possua:
   - Verificar se o perfil � `superadministrador`
3. Caso contr�rio:
   - Negar acesso

Modelo l�gico obrigat�rio:

```php
if ($usuario->temPermissao('nome_funcionalidade')) {
    // permitido
} elseif ($usuario->isSuperAdministrador()) {
    // permitido
} else {
    // acesso negado
}
Regras Obrigat�rias

Todas as rotas protegidas devem verificar permiss�o

O front-end deve:

Ocultar menus sem permiss�o

Bloquear rotas privadas

O back-end � a autoridade final (front nunca substitui valida��o)

---

## 7. Autentica��o e Seguran�a

### Autentica��o

- JWT obrigat�rio
- Token identifica apenas o usu�rio
- Token nunca cont�m dados sens�veis
- **Armazenamento**: Cookies HttpOnly (n�o acess�vel via JavaScript)
- Cookie configurado pelo back-end no login
- Front-end envia automaticamente via `withCredentials: true` no Axios
- Logout limpa o cookie no back-end
- Front-end NUNCA gerencia token manualmente

### Senhas

- Sempre armazenadas com `password_hash`
- Nunca retornar hashes em responses

### Seguran�a Geral

- PDO Prepared Statements (nunca concatenar SQL)
- Valida��o rigorosa de payloads
- Tratamento global de erros

### CORS (Cross-Origin Resource Sharing)

- CORS configur�vel via `api/config/config.php` ou `.env`
- Vari�vel: `allowed_origins` (array de URLs permitidas)
- Suporte a wildcards (`*`) nas origens
- Headers obrigat�rios:
  - `Access-Control-Allow-Origin`
  - `Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS`
  - `Access-Control-Allow-Headers: Content-Type, Authorization`
  - `Access-Control-Allow-Credentials: true`

### Tratamento Global de Erros

- Handlers globais em `api/index.php`:
  - `set_exception_handler` para exce��es n�o capturadas
  - `register_shutdown_function` para erros fatais
- Em produ��o:
  - N�o expor stack trace
  - N�o expor detalhes sens�veis
  - Sempre logar erros completos via `error_log()`
- Em desenvolvimento:
  - Incluir detalhes do erro (mensagem, arquivo, linha)
  - Logs no console e arquivo

### Configura��o de Logs

- Em produ��o: logs em `api/logs/php_errors.log`
- Em desenvolvimento: logs no console e arquivo
- Sempre usar `error_log()` para registrar erros completos

### Envio de E-mails (Obrigat�rio)

- **M�todo obrigat�rio**: Usar `mail()` nativo do PHP
- **N�O usar**: PHPMailer, SMTP externo ou outras bibliotecas de terceiros
- **Remetente padr�o**: `no-reply@iteva.org.br`
- **Nome do remetente**: "Sistema de Ponto Eletr�nico - ITEVA"

#### Formato Obrigat�rio

Todos os emails DEVEM seguir este padr�o:

```php
$para = "destinatario@exemplo.com";
$assunto = "Assunto do Email";

// Mensagem em HTML
$mensagem = "
<html>
<head>
    <meta charset='UTF-8'>
    <title>Assunto do Email</title>
</head>
<body>
    <p>Sua mensagem aqui</p>
</body>
</html>
";

// Cabe�alhos obrigat�rios
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: no-reply@iteva.org.br\r\n";
$headers .= "Reply-To: no-reply@iteva.org.br\r\n";

// Envio do e-mail
if (mail($para, $assunto, $mensagem, $headers)) {
    // Sucesso
} else {
    // Falha
}
```

#### EmailService

- Localiza��o: `api/src/Services/EmailService.php`
- M�todos obrigat�rios:
  - `enviarEmailResetSenha()` - Para "Esqueci minha senha"
  - `enviarConviteAutocadastro()` - Para convites de cadastro
  - `enviarEmailSenhaAlterada()` - Para confirma��o de altera��o
  - `enviarEmail()` - M�todo gen�rico

#### Regras Obrigat�rias

1. **Sempre usar `mail()` do PHP** - Nunca usar PHPMailer ou SMTP
2. **Charset UTF-8** - Todos os emails devem usar `charset=UTF-8`
3. **HTML obrigat�rio** - Todos os emails devem ser enviados em HTML
4. **Headers obrigat�rios** - Sempre incluir MIME-Version, Content-type, From e Reply-To
5. **Logs** - Sempre registrar tentativas de envio via `error_log()`
6. **Tratamento de erros** - Capturar e logar erros, mas n�o expor detalhes ao usu�rio

#### Configura��o do Servidor

- **Windows/XAMPP**: Pode ser necess�rio configurar sendmail no `php.ini`
- **Linux**: Verificar se `sendmail` ou `postfix` est� instalado
- **Produ��o**: Verificar se o servidor permite envio de emails via `mail()`

---

## 8. Padr�o de Respostas da API

Todas as respostas da API DEVEM seguir o formato padronizado:

### Sucesso

```json
{
  "success": true,
  "message": "Mensagem de sucesso",
  "data": { ... }
}
```

### Erro

```json
{
  "success": false,
  "message": "Mensagem de erro",
  "errors": { ... } // opcional
}
```

### Regras Obrigat�rias

- Sempre usar `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES` no `json_encode`
- C�digos HTTP:
  - `200`: Sucesso
  - `400`: Erro do cliente
  - `401`: N�o autorizado
  - `403`: Acesso proibido
  - `404`: Recurso n�o encontrado
  - `500`: Erro interno do servidor

### Content-Type Condicional

- Requisi��es normais: `Content-Type: application/json; charset=utf-8`
- Uploads de arquivo: n�o definir Content-Type (deixar o navegador definir)
- Requisi��es de imagem (`/foto` ou `/logo/`): n�o definir Content-Type como JSON

---

## 9. Base Path Din�mico

### Configura��o

- **Desenvolvimento**: base path `/` (raiz)
- **Produ��o**: base path `/ponto/` (subdiret�rio)

### Front-end

- Detecta automaticamente via `window.location.pathname`
- Fun��o `getApiBasePath()` ajusta baseURL dinamicamente
- Em desenvolvimento: usa `/api` (proxy do Vite)
- Em produ��o: usa `/ponto/api` se estiver em `/ponto/`

### Back-end

- Router calcula `basePath` via `dirname($_SERVER['SCRIPT_NAME'])`
- Rotas definidas em `routes.php` N�O devem incluir `/api` no path (j� est� no basePath)
- Exemplo: rota `/auth/login` (n�o `/api/auth/login`)

### Valida��o de Par�metros de Rota

- Router suporta par�metros din�micos: `{id}`, `{tipo}`, etc.
- Convers�o autom�tica entre `camelCase` (m�todos) e `snake_case` (rotas)
- Uso de Reflection para mapear par�metros corretamente
- **Regra cr�tica**: Rotas mais espec�ficas DEVEM vir ANTES de rotas com par�metros

---

## 10. Persist�ncia Din�mica (Obrigat�ria)

Todos os reposit�rios/models que realizam INSERT ou UPDATE DEVEM:

Identificar a tabela automaticamente

Buscar os campos reais da tabela (DESCRIBE)

Comparar com os dados recebidos (JSON)

Persistir apenas campos existentes

Ignorar campos inv�lidos

Diferenciar a��es com switch/case

Premissa:

Os nomes dos campos do JSON DEVEM ser id�nticos aos nomes das colunas da tabela.

---

## 11. Dados Brasileiros (Formata��o)
Front-end

Telefone: (99) 9.9999-9999

CEP: 99.999-999

CPF: 999.999.999-99

Back-end / Banco

Salvar somente n�meros

---

## 12. CPF  Valida��o

Validar CPF no front-end (tempo real)

Validar CPF no back-end

�NICA exce��o aceita como inv�lida:

111.111.111-11 (apenas para testes)

---

## 13. Endere�o via CEP

CEP consultado via:
https://viacep.com.br/ws/{cep}/json/

Preencher automaticamente:

Estado

Cidade

Bairro

Rua

Usu�rio informa n�mero e complemento

---

## 14. Registro de Jornada e Banco de Horas

Cada colaborador possui:

Dias trabalhados

Hor�rios de entrada e sa�da

Um ou mais intervalos

C�lculo de Banco de Horas

Trabalhou menos � horas negativas (vermelho)

Trabalhou igual � zerado (verde)

Trabalhou mais � horas positivas (azul)

Trabalhou em dia n�o previsto � horas extras

Trabalhou em feriado � horas extras

---

## 15. Faltas, F�rias, Folgas e Atestados

Ap�s 00:00:

Se deveria trabalhar e n�o trabalhou � FALTA

Se existir:

F�RIAS

FOLGA

ATESTADO
� Nunca marcar falta

---

## 16. Feriados

Feriados nacionais:

Buscar via:
https://brasilapi.com.br/api/feriados/v1/{ano}

Feriados locais:

Cadastrados manualmente

Considerados automaticamente nos c�lculos

---

## 17. Hist�rico e Visibilidade

Colaborador:

V� apenas seus pr�prios dados

Gestor / Admin / Superadmin:

V� todos os colaboradores

---

## 18. Front-end (Regras Obrigat�rias)

### Princ�pios Gerais

- Mobile first
- Tailwind CSS 3.4
- Dark mode persistente (sempre ativo no layout principal)
- Axios com interceptors
- Rotas protegidas
- Skeleton screens
- Code splitting

### Configura��o do Vite

- **Base path**: 
  - Desenvolvimento: `/`
  - Produ��o: `/ponto/`
- **Proxy em desenvolvimento**: 
  - Target: `http://localhost/pontoeletronico`
  - Porta: `3000`
  - Path: `/api`
- **Alias de path**: `@` configurado para `./src`
  - Permite imports como `import { ... } from '@/components/...'`
  - Configurado em `vite.config.ts` e `tsconfig.json`
- **Otimiza��o de depend�ncias**: 
  - Depend�ncias pesadas pr�-carregadas: `xlsx`, `jspdf`, `jspdf-autotable`
  - Configurado em `vite.config.ts` via `optimizeDeps.include`

### Requisi��es HTTP

- **Timeout padr�o**: 30 segundos para todas as requisi��es Axios
- Configurado em `frontend/src/lib/api.ts`
- Interceptors para tratamento de erros e ajuste de baseURL

### Hook de Loading Progress

- Hook `useLoadingProgress` para rastrear progresso de opera��es ass�ncronas
- Exibe spinner com percentual de download
- Simula progresso de 0% a 90% automaticamente
- Timeout de seguran�a: 45 segundos
- M�todos:
  - `startLoading(message)`: inicia loading com mensagem opcional
  - `finishLoading()`: finaliza loading (completa at� 100%)
  - `setProgress(percent)`: atualiza progresso manualmente

### Layout Padr�o (Obrigat�rio)

Todas as p�ginas autenticadas DEVEM usar o componente `SpotifyLayout`

O layout possui:

- Sidebar fixa � esquerda com navega��o
- Logo customiz�vel (suporte para light/dark mode)
- Bot�o hamb�rguer para esconder/mostrar sidebar (mobile)
- Toda p�gina que precisar carregar dados do servidor, enquanto os dados est�o sendo carregados, n�o mostrar carregando apenas, deixar spinner rodando e percentagem de download
- Header com t�tulo da p�gina e a��es do usu�rio
- Conte�do principal com gradiente de fundo

### Cores Padr�o

- Fundo principal: `#090909`
- Fundo sidebar: `#000000` (preto)
- Gradiente conte�do: `from-zinc-900 to-#121212`
- Cor prim�ria (bot�es, destaques): `#1DB954` (verde Spotify)
- Texto: `zinc-100` (branco) / `zinc-400` (cinza claro)
- Cards: `bg-zinc-900/40 border-zinc-800`

### Responsividade

- Sidebar oculta em mobile por padr�o
- Bot�o hamb�rguer vis�vel apenas em mobile
- Layout adapta-se automaticamente a diferentes tamanhos de tela

### Persist�ncia de Estado do Layout

- Estado da sidebar (colapsada/expandida) salvo em `localStorage`
- Chave: `sidebarCollapsed`
- Restaurado automaticamente ao recarregar a p�gina

---

## 19. Estrutura de Diret�rios do Projeto

### Estrutura Obrigat�ria

```
projeto/
   api/
      config/
         bootstrap.php
         config.php
         .env (n�o versionado)
      src/
         Core/
         Controllers/
         Models/
         Middleware/
         Services/
         Utils/
      uploads/
         fotos/
         logos-sistema/
      index.php
      routes.php
      .htaccess
   frontend/
      src/
         components/
         contexts/
         hooks/
         pages/
         services/
         utils/
      vite.config.ts
   vendor/ (Composer)
```

### Estrutura de Pastas de Upload

- `api/uploads/fotos/` - Fotos de perfil dos usu�rios
- `api/uploads/logos-sistema/` - Logos do sistema (light/dark)
- Cada pasta DEVE conter um arquivo `index.php` ou `serve.php` para servir arquivos
- Arquivos antigos DEVEM ser removidos antes de salvar novos

---

## 20. Configura��o do Servidor (.htaccess)

O arquivo `api/.htaccess` DEVE conter:

- `RewriteEngine On`
- Permiss�o de acesso direto a arquivos em `uploads/` (fotos e logos)
- Redirecionamento de todas as requisi��es n�o arquivos para `index.php`
- Suporte ao base path `/ponto/api/` em produ��o

### Exemplo de Configura��o

```apache
RewriteEngine On

# Permite acesso direto a arquivos PHP na pasta uploads
RewriteCond %{REQUEST_URI} ^/ponto/api/uploads/ [NC]
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^uploads/(.*\.php)$ uploads/$1 [L]

# Permite acesso direto a arquivos de imagem na pasta uploads
RewriteCond %{REQUEST_URI} ^/ponto/api/uploads/ [NC]
RewriteCond %{REQUEST_FILENAME} -f
RewriteCond %{REQUEST_FILENAME} \.(jpg|jpeg|png|gif|webp)$ [NC]
RewriteRule ^uploads/(.*)$ uploads/$1 [L]

# Redireciona apenas requisi��es que n�o s�o arquivos existentes
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

---

## 21. Testes Automatizados (Obrigat�rio)
Back-end

Testes unit�rios

Testes de endpoints

Front-end

Testes de formul�rios

Testes de rotas protegidas

---

## 22. Organiza��o de Arquivos do Projeto (Obrigat�ria)

### Estrutura de Pastas para Documenta��o e Testes

O projeto DEVE manter uma organiza��o rigorosa de arquivos:

### Pasta MDs (Documenta��o)

- **Localiza��o**: `MDs/` (raiz do projeto)
- **Conte�do**: Todos os arquivos de documenta��o em Markdown (`.md`)
- **Regras**:
  - TODOS os arquivos `.md` do projeto DEVEM estar nesta pasta
  - Exce��o: `!Suporte/PROJECT_RULES.md` (mantido em sua localiza��o original)
  - Arquivos `.md` de subdiret�rios (`api/`, `database/`, `testes/`, etc.) DEVEM ser movidos para `MDs/`
  - Nomes de arquivos duplicados DEVEM ser renomeados (ex: `README_SEED_PERMISSOES.md` em vez de `README.md`)

### Pasta Testes

- **Localiza��o**: `Testes/` (raiz do projeto)
- **Conte�do**: Todos os arquivos de teste e diagn�stico
- **Regras**:
  - TODOS os arquivos de teste DEVEM estar nesta pasta
  - Inclui arquivos com prefixo `test-*.php`
  - Inclui arquivos de diagn�stico (ex: `verificar-api.php`, `test-diagnostico-api.php`)
  - Arquivos de teste em subdiret�rios DEVEM ser movidos para `Testes/`
  - A pasta `testes/` antiga pode ser removida ap�s migra��o completa

### Estrutura Esperada

```
projeto/
   MDs/
      CONFIGURAR_MAIL.md
      EMAIL_SETUP.md
      README_SEED_PERMISSOES.md
      COMO_TESTAR.md
      test-completo.md
      ... (todos os outros .md)
   Testes/
      test-connection.php
      test-api-login.php
      test-diagnostico-api.php
      verificar-api.php
      ... (todos os outros arquivos de teste)
   !Suporte/
      PROJECT_RULES.md (mantido aqui)
   ...
```

### Regras Obrigat�rias

1. **Nunca criar arquivos `.md` fora da pasta `MDs/`**
   - Exce��o: `!Suporte/PROJECT_RULES.md`

2. **Nunca criar arquivos de teste fora da pasta `Testes/`**
   - Arquivos de teste s�o apenas para desenvolvimento/diagn�stico
   - N�o devem ser versionados em produ��o

3. **Manuten��o cont�nua**
   - Sempre que um novo arquivo `.md` ou de teste for criado, deve ser colocado na pasta correta
   - Revisar periodicamente a organiza��o

4. **Refer�ncias em c�digo**
   - Ao referenciar arquivos de documenta��o ou teste em c�digo, usar caminhos relativos a partir das pastas `MDs/` e `Testes/`

---

## 23. Regra Final (Cr�tica)

Sempre que a IA:

Criar c�digo

Criar SQL

Criar endpoints

Criar componentes React

Criar regras de neg�cio

Ela DEVE:

Seguir este documento integralmente

Seguir o prompt completo do projeto

Respeitar a separa��o front/back

Aplicar o sistema de permiss�es granulares

Priorizar seguran�a, clareza e escalabilidade

Este documento � obrigat�rio, permanente e imut�vel, salvo decis�o expl�cita do respons�vel t�cnico do projeto.