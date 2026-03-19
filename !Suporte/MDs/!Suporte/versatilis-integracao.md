# Integracao Versatilis (beneficiario)

## O que foi feito
- Criado o servico de integracao com Versatilis em `app/beneficiario/versatilis.php` (token, consulta por CPF, cadastro e logs).
- Adicionado `temp/apikey-versatilis.php` para configurar credenciais e base URL.
- Implementado fluxo "Salvar + Versatilis" no `app/beneficiario/beneficiarioController.php` via `BeneficiarioModel::salvarComVersatilis`.
- Adicionado `BeneficiarioModel::salvarComVersatilis` em `app/beneficiario/beneficiarioModel.php`.
- Botao "Salvar + Versatilis" agora submete com `acao=salvar_versatilis` em `app/beneficiario/formBeneficiario.php`.
- Log das chamadas Versatilis gravado em `temp/versatilis.log`.
- Script para criar coluna Versatilis em `!Suporte/add_versatilis_coluna.php`.

## Configuracao
Edite `temp/apikey-versatilis.php` e preencha:
- `$versatilisUsername`
- `$versatilisPassword`
- `$versatilisBaseUrl` (se necessario)
- `$versatilisDefaultPassword` (senha padrao enviada ao Versatilis)

## Reversao (passo a passo)
1. Remover o fluxo do controller:
   - apagar o bloco `if ($acao === 'salvar_versatilis') { ... }` em `app/beneficiario/beneficiarioController.php`.
2. Remover o metodo do model:
   - apagar `public static function salvarComVersatilis(...)` e o `require_once __DIR__ . '/versatilis.php';` em `app/beneficiario/beneficiarioModel.php`.
3. Remover arquivos de integracao:
   - `app/beneficiario/versatilis.php`
   - `temp/apikey-versatilis.php`
   - `temp/versatilis.log` (opcional)
4. Reverter botoes:
   - alterar os botoes `btnSalvarTopoVersatilis` e `btnSalvarBaixoVersatilis` para `type="button"` e remover `name="acao"`/`value="salvar_versatilis"` em `app/beneficiario/formBeneficiario.php`.
5. Remover a coluna do banco (opcional):
   - `ALTER TABLE tbAluno DROP COLUMN Versatilis;`

## Observacoes
- O fluxo do botao "Salvar + Versatilis" nao salva localmente se o usuario ja existir no Versatilis ou se ocorrer erro de API.
- As mensagens exibidas ao usuario usam o mesmo mecanismo de alerta do formulario (`msg`/`erro` via GET).
