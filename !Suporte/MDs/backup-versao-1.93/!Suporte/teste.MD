# Guia de Regras e Padrões de Desenvolvimento  
## Sistema de Registro de Ponto Eletrônico

Este documento define **todas as regras técnicas, arquiteturais e funcionais obrigatórias** do projeto **Sistema de Registro de Ponto Eletrônico**.

Sempre que uma IA gerar **código, SQL, arquitetura, lógica de negócio, endpoints, componentes de UI ou testes**, ela **DEVE obedecer integralmente** às regras descritas abaixo.

---

## 1. Arquitetura Geral

### Separação Obrigatória de Camadas

O sistema é composto por **duas aplicações independentes**:

### Back-end
- API RESTful
- PHP 8.1+
- Stateless
- Responsável por:
  - Persistência
  - Segurança
  - Autenticação
  - Autorização
  - Regras de negócio
  - Cálculos de ponto e banco de horas

### Front-end
- React + Vite + TypeScript
- Responsável por:
  - Interface do usuário
  - Experiência (UX)
  - Validação prévia de dados
  - Gerenciamento de estado
  - Consumo da API

**O front-end NUNCA acessa o banco de dados diretamente.**

---

## 2. Stack Tecnológica Oficial

### Back-end (API)
- PHP 8.1 ou superior
- PDO com Prepared Statements
- JWT (JSON Web Token)
- Bibliotecas externas permitidas apenas se:
  - Instaladas via Composer em desenvolvimento
  - Copiadas via pasta `vendor/` para produção
- Sem frameworks pesados (Laravel, Symfony, etc.)

### Front-end
- React 18+
- Vite
- TypeScript (obrigatório)
- Tailwind CSS 3.4
- Axios
- React Hook Form
- Zod
- Biblioteca UI gratuita (preferência: shadcn/ui)

---

## 3. Detecção Automática de Ambiente

O sistema detecta automaticamente se está em **produção** ou **desenvolvimento**:

### Back-end

- Verifica `HTTP_HOST` em `conectabd/conexao.php`
- **Produção**: quando o host NÃO é `localhost`, `127.0.0.1`, `::1` ou contém `.local`
- **Desenvolvimento**: quando o host é localhost ou contém `.local`
- Variáveis definidas:
  - `APP_ENV`: `'production'` ou `'development'`
  - `APP_DEBUG`: `true` em desenvolvimento, `false` em produção

### Front-end

- Usa `process.env.NODE_ENV` e `import.meta.env.DEV`
- **Produção**: `NODE_ENV === 'production'`
- **Desenvolvimento**: `NODE_ENV === 'development'` ou `import.meta.env.DEV === true`

### Regras Obrigatórias

- Em produção:
  - Não expor stack traces
  - Não expor detalhes de erros
  - Logs em arquivo
- Em desenvolvimento:
  - Mostrar erros detalhados
  - Logs no console e arquivo

---

## 4. Banco de Dados (Regra Crítica)

### Tipo
- MySQL remoto

### Conexão Oficial

Estas credenciais são **definitivas para o ambiente atual**:

- Banco: `mwtech63_matricula`
- Usuário: `mwtech63_admin_matricula`
- Porta: `3306`
- Senha: `Iteva@100`
- Host: `162.241.2.214`

### Regras Obrigatórias
- Usar **PDO**
- Charset `utf8mb4`
- Conexão única (singleton ou pool simples)
- Nunca versionar credenciais em código público

---

## 4. Convenções de Nomenclatura (Banco)

### Novas Tabelas
- Prefixo obrigatório: `tb_`
- Apenas letras minúsculas e `_`
- Exemplo:
  - `tb_usuarios`
  - `tb_pontos`
  - `tb_permissoes`

### Novas Views
- Prefixo obrigatório: `vw_`
- Exemplo:
  - `vw_horas_trabalhadas`
  - `vw_banco_horas`

**Nunca usar letras maiúsculas em nomes de tabelas ou views, exceto as que já existem.**

---

## 5. Fuso Horário

### Configuração Obrigatória

- Fuso horário padrão: `America/Fortaleza` (UTC-3)
- Configurado em `api/index.php` e `api/config/bootstrap.php` via `date_default_timezone_set('America/Fortaleza')`
- Todas as datas/horas do sistema DEVEM usar este fuso horário
- Nunca usar UTC ou outros fusos horários

---

## 6. Sistema de Permissões (Granular – Regra Central)

### Conceito

O sistema utiliza **permissões granulares por funcionalidade**, e NÃO apenas por perfil fixo.

### Estrutura Conceitual

- Permissão:
  - Possui um **nome**
  - Possui uma **descrição**
  - Possui um conjunto de **funcionalidades permitidas**

- Funcionalidade:
  - Representa uma ação ou acesso do sistema
  - Exemplos:
    - `ver_historico`
    - `registrar_ponto`
    - `gerenciar_usuarios`
    - `gerenciar_feriados`
    - `autorizar_autocadastro`

### Regra de Acesso

Para cada acesso a uma funcionalidade:

1. Verificar se o usuário possui a permissão
2. Caso não possua:
   - Verificar se o perfil é `superadministrador`
3. Caso contrário:
   - Negar acesso

Modelo lógico obrigatório:

```php
if ($usuario->temPermissao('nome_funcionalidade')) {
    // permitido
} elseif ($usuario->isSuperAdministrador()) {
    // permitido
} else {
    // acesso negado
}
Regras Obrigatórias

Todas as rotas protegidas devem verificar permissão

O front-end deve:

Ocultar menus sem permissão

Bloquear rotas privadas

O back-end é a autoridade final (front nunca substitui validação)

---

## 7. Autenticação e Segurança

### Autenticação

- JWT obrigatório
- Token identifica apenas o usuário
- Token nunca contém dados sensíveis
- **Armazenamento**: Cookies HttpOnly (não acessível via JavaScript)
- Cookie configurado pelo back-end no login
- Front-end envia automaticamente via `withCredentials: true` no Axios
- Logout limpa o cookie no back-end
- Front-end NUNCA gerencia token manualmente

### Senhas

- Sempre armazenadas com `password_hash`
- Nunca retornar hashes em responses

### Segurança Geral

- PDO Prepared Statements (nunca concatenar SQL)
- Validação rigorosa de payloads
- Tratamento global de erros

### CORS (Cross-Origin Resource Sharing)

- CORS configurável via `api/config/config.php` ou `.env`
- Variável: `allowed_origins` (array de URLs permitidas)
- Suporte a wildcards (`*`) nas origens
- Headers obrigatórios:
  - `Access-Control-Allow-Origin`
  - `Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS`
  - `Access-Control-Allow-Headers: Content-Type, Authorization`
  - `Access-Control-Allow-Credentials: true`

### Tratamento Global de Erros

- Handlers globais em `api/index.php`:
  - `set_exception_handler` para exceções não capturadas
  - `register_shutdown_function` para erros fatais
- Em produção:
  - Não expor stack trace
  - Não expor detalhes sensíveis
  - Sempre logar erros completos via `error_log()`
- Em desenvolvimento:
  - Incluir detalhes do erro (mensagem, arquivo, linha)
  - Logs no console e arquivo

### Configuração de Logs

- Em produção: logs em `api/logs/php_errors.log`
- Em desenvolvimento: logs no console e arquivo
- Sempre usar `error_log()` para registrar erros completos

### Envio de E-mails (Obrigatório)

- **Método obrigatório**: Usar `mail()` nativo do PHP
- **NÃO usar**: PHPMailer, SMTP externo ou outras bibliotecas de terceiros
- **Remetente padrão**: `no-reply@iteva.org.br`
- **Nome do remetente**: "Sistema de Ponto Eletrônico - ITEVA"

#### Formato Obrigatório

Todos os emails DEVEM seguir este padrão:

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

// Cabeçalhos obrigatórios
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

- Localização: `api/src/Services/EmailService.php`
- Métodos obrigatórios:
  - `enviarEmailResetSenha()` - Para "Esqueci minha senha"
  - `enviarConviteAutocadastro()` - Para convites de cadastro
  - `enviarEmailSenhaAlterada()` - Para confirmação de alteração
  - `enviarEmail()` - Método genérico

#### Regras Obrigatórias

1. **Sempre usar `mail()` do PHP** - Nunca usar PHPMailer ou SMTP
2. **Charset UTF-8** - Todos os emails devem usar `charset=UTF-8`
3. **HTML obrigatório** - Todos os emails devem ser enviados em HTML
4. **Headers obrigatórios** - Sempre incluir MIME-Version, Content-type, From e Reply-To
5. **Logs** - Sempre registrar tentativas de envio via `error_log()`
6. **Tratamento de erros** - Capturar e logar erros, mas não expor detalhes ao usuário

#### Configuração do Servidor

- **Windows/XAMPP**: Pode ser necessário configurar sendmail no `php.ini`
- **Linux**: Verificar se `sendmail` ou `postfix` está instalado
- **Produção**: Verificar se o servidor permite envio de emails via `mail()`

---

## 8. Padrão de Respostas da API

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

### Regras Obrigatórias

- Sempre usar `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES` no `json_encode`
- Códigos HTTP:
  - `200`: Sucesso
  - `400`: Erro do cliente
  - `401`: Não autorizado
  - `403`: Acesso proibido
  - `404`: Recurso não encontrado
  - `500`: Erro interno do servidor

### Content-Type Condicional

- Requisições normais: `Content-Type: application/json; charset=utf-8`
- Uploads de arquivo: não definir Content-Type (deixar o navegador definir)
- Requisições de imagem (`/foto` ou `/logo/`): não definir Content-Type como JSON

---

## 9. Base Path Dinâmico

### Configuração

- **Desenvolvimento**: base path `/` (raiz)
- **Produção**: base path `/ponto/` (subdiretório)

### Front-end

- Detecta automaticamente via `window.location.pathname`
- Função `getApiBasePath()` ajusta baseURL dinamicamente
- Em desenvolvimento: usa `/api` (proxy do Vite)
- Em produção: usa `/ponto/api` se estiver em `/ponto/`

### Back-end

- Router calcula `basePath` via `dirname($_SERVER['SCRIPT_NAME'])`
- Rotas definidas em `routes.php` NÃO devem incluir `/api` no path (já está no basePath)
- Exemplo: rota `/auth/login` (não `/api/auth/login`)

### Validação de Parâmetros de Rota

- Router suporta parâmetros dinâmicos: `{id}`, `{tipo}`, etc.
- Conversão automática entre `camelCase` (métodos) e `snake_case` (rotas)
- Uso de Reflection para mapear parâmetros corretamente
- **Regra crítica**: Rotas mais específicas DEVEM vir ANTES de rotas com parâmetros

---

## 10. Persistência Dinâmica (Obrigatória)

Todos os repositórios/models que realizam INSERT ou UPDATE DEVEM:

Identificar a tabela automaticamente

Buscar os campos reais da tabela (DESCRIBE)

Comparar com os dados recebidos (JSON)

Persistir apenas campos existentes

Ignorar campos inválidos

Diferenciar ações com switch/case

Premissa:

Os nomes dos campos do JSON DEVEM ser idênticos aos nomes das colunas da tabela.

---

## 11. Dados Brasileiros (Formatação)
Front-end

Telefone: (99) 9.9999-9999

CEP: 99.999-999

CPF: 999.999.999-99

Back-end / Banco

Salvar somente números

---

## 12. CPF – Validação

Validar CPF no front-end (tempo real)

Validar CPF no back-end

ÚNICA exceção aceita como inválida:

111.111.111-11 (apenas para testes)

---

## 13. Endereço via CEP

CEP consultado via:
https://viacep.com.br/ws/{cep}/json/

Preencher automaticamente:

Estado

Cidade

Bairro

Rua

Usuário informa número e complemento

---

## 14. Registro de Jornada e Banco de Horas

Cada colaborador possui:

Dias trabalhados

Horários de entrada e saída

Um ou mais intervalos

Cálculo de Banco de Horas

Trabalhou menos → horas negativas (vermelho)

Trabalhou igual → zerado (verde)

Trabalhou mais → horas positivas (azul)

Trabalhou em dia não previsto → horas extras

Trabalhou em feriado → horas extras

---

## 15. Faltas, Férias, Folgas e Atestados

Após 00:00:

Se deveria trabalhar e não trabalhou → FALTA

Se existir:

FÉRIAS

FOLGA

ATESTADO
→ Nunca marcar falta

---

## 16. Feriados

Feriados nacionais:

Buscar via:
https://brasilapi.com.br/api/feriados/v1/{ano}

Feriados locais:

Cadastrados manualmente

Considerados automaticamente nos cálculos

---

## 17. Histórico e Visibilidade

Colaborador:

Vê apenas seus próprios dados

Gestor / Admin / Superadmin:

Vê todos os colaboradores

---

## 18. Front-end (Regras Obrigatórias)

### Princípios Gerais

- Mobile first
- Tailwind CSS 3.4
- Dark mode persistente (sempre ativo no layout principal)
- Axios com interceptors
- Rotas protegidas
- Skeleton screens
- Code splitting

### Configuração do Vite

- **Base path**: 
  - Desenvolvimento: `/`
  - Produção: `/ponto/`
- **Proxy em desenvolvimento**: 
  - Target: `http://localhost/pontoeletronico`
  - Porta: `3000`
  - Path: `/api`
- **Alias de path**: `@` configurado para `./src`
  - Permite imports como `import { ... } from '@/components/...'`
  - Configurado em `vite.config.ts` e `tsconfig.json`
- **Otimização de dependências**: 
  - Dependências pesadas pré-carregadas: `xlsx`, `jspdf`, `jspdf-autotable`
  - Configurado em `vite.config.ts` via `optimizeDeps.include`

### Requisições HTTP

- **Timeout padrão**: 30 segundos para todas as requisições Axios
- Configurado em `frontend/src/lib/api.ts`
- Interceptors para tratamento de erros e ajuste de baseURL

### Hook de Loading Progress

- Hook `useLoadingProgress` para rastrear progresso de operações assíncronas
- Exibe spinner com percentual de download
- Simula progresso de 0% a 90% automaticamente
- Timeout de segurança: 45 segundos
- Métodos:
  - `startLoading(message)`: inicia loading com mensagem opcional
  - `finishLoading()`: finaliza loading (completa até 100%)
  - `setProgress(percent)`: atualiza progresso manualmente

### Layout Padrão (Obrigatório)

Todas as páginas autenticadas DEVEM usar o componente `SpotifyLayout`

O layout possui:

- Sidebar fixa à esquerda com navegação
- Logo customizável (suporte para light/dark mode)
- Botão hambúrguer para esconder/mostrar sidebar (mobile)
- Toda página que precisar carregar dados do servidor, enquanto os dados estão sendo carregados, não mostrar carregando apenas, deixar spinner rodando e percentagem de download
- Header com título da página e ações do usuário
- Conteúdo principal com gradiente de fundo

### Cores Padrão

- Fundo principal: `#090909`
- Fundo sidebar: `#000000` (preto)
- Gradiente conteúdo: `from-zinc-900 to-#121212`
- Cor primária (botões, destaques): `#1DB954` (verde Spotify)
- Texto: `zinc-100` (branco) / `zinc-400` (cinza claro)
- Cards: `bg-zinc-900/40 border-zinc-800`

### Responsividade

- Sidebar oculta em mobile por padrão
- Botão hambúrguer visível apenas em mobile
- Layout adapta-se automaticamente a diferentes tamanhos de tela

### Persistência de Estado do Layout

- Estado da sidebar (colapsada/expandida) salvo em `localStorage`
- Chave: `sidebarCollapsed`
- Restaurado automaticamente ao recarregar a página

---

## 19. Estrutura de Diretórios do Projeto

### Estrutura Obrigatória

```
projeto/
├── api/
│   ├── config/
│   │   ├── bootstrap.php
│   │   ├── config.php
│   │   └── .env (não versionado)
│   ├── src/
│   │   ├── Core/
│   │   ├── Controllers/
│   │   ├── Models/
│   │   ├── Middleware/
│   │   ├── Services/
│   │   └── Utils/
│   ├── uploads/
│   │   ├── fotos/
│   │   └── logos-sistema/
│   ├── index.php
│   ├── routes.php
│   └── .htaccess
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   ├── contexts/
│   │   ├── hooks/
│   │   ├── pages/
│   │   ├── services/
│   │   └── utils/
│   └── vite.config.ts
└── vendor/ (Composer)
```

### Estrutura de Pastas de Upload

- `api/uploads/fotos/` - Fotos de perfil dos usuários
- `api/uploads/logos-sistema/` - Logos do sistema (light/dark)
- Cada pasta DEVE conter um arquivo `index.php` ou `serve.php` para servir arquivos
- Arquivos antigos DEVEM ser removidos antes de salvar novos

---

## 20. Configuração do Servidor (.htaccess)

O arquivo `api/.htaccess` DEVE conter:

- `RewriteEngine On`
- Permissão de acesso direto a arquivos em `uploads/` (fotos e logos)
- Redirecionamento de todas as requisições não arquivos para `index.php`
- Suporte ao base path `/ponto/api/` em produção

### Exemplo de Configuração

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

# Redireciona apenas requisições que não são arquivos existentes
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

---

## 21. Testes Automatizados (Obrigatório)
Back-end

Testes unitários

Testes de endpoints

Front-end

Testes de formulários

Testes de rotas protegidas

---

## 22. Organização de Arquivos do Projeto (Obrigatória)

### Estrutura de Pastas para Documentação e Testes

O projeto DEVE manter uma organização rigorosa de arquivos:

### Pasta MDs (Documentação)

- **Localização**: `MDs/` (raiz do projeto)
- **Conteúdo**: Todos os arquivos de documentação em Markdown (`.md`)
- **Regras**:
  - TODOS os arquivos `.md` do projeto DEVEM estar nesta pasta
  - Exceção: `!Suporte/PROJECT_RULES.md` (mantido em sua localização original)
  - Arquivos `.md` de subdiretórios (`api/`, `database/`, `testes/`, etc.) DEVEM ser movidos para `MDs/`
  - Nomes de arquivos duplicados DEVEM ser renomeados (ex: `README_SEED_PERMISSOES.md` em vez de `README.md`)

### Pasta Testes

- **Localização**: `Testes/` (raiz do projeto)
- **Conteúdo**: Todos os arquivos de teste e diagnóstico
- **Regras**:
  - TODOS os arquivos de teste DEVEM estar nesta pasta
  - Inclui arquivos com prefixo `test-*.php`
  - Inclui arquivos de diagnóstico (ex: `verificar-api.php`, `test-diagnostico-api.php`)
  - Arquivos de teste em subdiretórios DEVEM ser movidos para `Testes/`
  - A pasta `testes/` antiga pode ser removida após migração completa

### Estrutura Esperada

```
projeto/
├── MDs/
│   ├── CONFIGURAR_MAIL.md
│   ├── EMAIL_SETUP.md
│   ├── README_SEED_PERMISSOES.md
│   ├── COMO_TESTAR.md
│   ├── test-completo.md
│   └── ... (todos os outros .md)
├── Testes/
│   ├── test-connection.php
│   ├── test-api-login.php
│   ├── test-diagnostico-api.php
│   ├── verificar-api.php
│   └── ... (todos os outros arquivos de teste)
├── !Suporte/
│   └── PROJECT_RULES.md (mantido aqui)
└── ...
```

### Regras Obrigatórias

1. **Nunca criar arquivos `.md` fora da pasta `MDs/`**
   - Exceção: `!Suporte/PROJECT_RULES.md`

2. **Nunca criar arquivos de teste fora da pasta `Testes/`**
   - Arquivos de teste são apenas para desenvolvimento/diagnóstico
   - Não devem ser versionados em produção

3. **Manutenção contínua**
   - Sempre que um novo arquivo `.md` ou de teste for criado, deve ser colocado na pasta correta
   - Revisar periodicamente a organização

4. **Referências em código**
   - Ao referenciar arquivos de documentação ou teste em código, usar caminhos relativos a partir das pastas `MDs/` e `Testes/`

---

## 23. Regra Final (Crítica)

Sempre que a IA:

Criar código

Criar SQL

Criar endpoints

Criar componentes React

Criar regras de negócio

Ela DEVE:

Seguir este documento integralmente

Seguir o prompt completo do projeto

Respeitar a separação front/back

Aplicar o sistema de permissões granulares

Priorizar segurança, clareza e escalabilidade

Este documento é obrigatório, permanente e imutável, salvo decisão explícita do responsável técnico do projeto.