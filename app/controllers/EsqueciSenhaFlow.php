<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

use PDO;
use Throwable;

final class EsqueciSenhaFlow
{
    public function __construct(
        private readonly string $basePath,
        private readonly string $baseUrl
    ) {
    }

    public function handle(): void
    {
        require_once $this->basePath . '/api/conectabd/conexao.php';
        global $pdo;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $cpfDigitado = preg_replace('/\D/', '', (string) ($_POST['cpf'] ?? ''));

            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $cpfDigitado !== '') {
                $this->processarRedefinicao($pdo, $email, $cpfDigitado);
            }

            $this->redirectToLoginWithSuccess();
        }

        $BASE_para_PATH = $this->basePath;
        $BASE_para_URL = $this->baseUrl;
        $mensagemSucesso = '';
        $appJsVersion = @filemtime($this->basePath . '/app/assets/js/app.js') ?: time();
        $config = $pdo->query('SELECT * FROM tbConfig LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        $shortcutIcon = (string) ($config['ShortcutIcon'] ?? 'icon-48x48.png');
        $shortcutIcon = trim($shortcutIcon) === '' ? 'icon-48x48.png' : trim($shortcutIcon);
        $shortcutIcon = ltrim(str_replace('\\', '/', $shortcutIcon), '/');
        $iconFullPath = $BASE_para_PATH . '/app/assets/img/icons/' . $shortcutIcon;
        if (!is_file($iconFullPath)) {
            $shortcutIcon = basename($shortcutIcon);
            $iconFullPath = $BASE_para_PATH . '/app/assets/img/icons/' . $shortcutIcon;
        }
        if (!is_file($iconFullPath)) {
            $shortcutIcon = 'icon-48x48.png';
        }

        require $this->basePath . '/app/views/pages/esqueci-senha.php';
    }

    private function redirectToLoginWithSuccess(): void
    {
        $mensagem = 'Se os dados estiverem corretos, sua senha foi redefinida para senha padrao. Tente fazer login novamente.';
        header('Location: ' . rtrim($this->baseUrl, '/') . '/login/?logout=1&sucesso=' . urlencode($mensagem));
        exit;
    }

    private function processarRedefinicao(PDO $pdo, string $email, string $cpfDigitado): void
    {
        $sql = "
            SELECT IdColaborador, Nome, Sobrenome, Email, CPF
              FROM tbUser
             WHERE LOWER(TRIM(Email)) = ?
               AND Habilitado = 1
             LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            return;
        }

        $cpfBanco = preg_replace('/\D/', '', (string) ($usuario['CPF'] ?? ''));
        $cpfConfere = $cpfBanco === $cpfDigitado;
        $nomeCompleto = trim((string) (($usuario['Nome'] ?? '') . ' ' . ($usuario['Sobrenome'] ?? '')));
        if ($nomeCompleto === '') {
            $nomeCompleto = 'Usuario';
        }

        if (!$cpfConfere) {
            $this->enviarEmailSistema(
                (string) $usuario['Email'],
                'ConectaOSC - Tentativa de redefinicao de senha bloqueada',
                $this->montarEmailHtml(
                    $nomeCompleto,
                    'Houve uma tentativa de alteracao da sua senha. Nao se preocupe, a solicitacao foi bloqueada porque o CPF informado nao confere com o seu cadastro.',
                    [
                        'tipo' => 'alerta',
                        'titulo' => 'Tentativa bloqueada',
                        'acaoTexto' => 'Falar com suporte',
                        'acaoUrl' => 'mailto:iteva@iteva.org.br',
                    ]
                )
            );
            return;
        }

        $novaSenha = substr($cpfDigitado, 0, 6);
        $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

        $updateSql = "
            UPDATE tbUser
               SET Senha = ?, TimeAlterado = NOW()
             WHERE LOWER(TRIM(Email)) = ?
               AND REPLACE(REPLACE(REPLACE(TRIM(CPF), '.', ''), '-', ''), ' ', '') = ?
               AND Habilitado = 1
        ";
        $up = $pdo->prepare($updateSql);
        $up->execute([$senhaHash, $email, $cpfDigitado]);

        if ($up->rowCount() > 0) {
            $this->enviarEmailSistema(
                (string) $usuario['Email'],
                'ConectaOSC - Senha redefinida',
                $this->montarEmailHtml(
                    $nomeCompleto,
                    'Sua senha no sistema ConectaOSC foi redefinida para os 6 primeiros digitos do seu CPF. Por seguranca, acesse e altere sua senha apos o login.',
                    [
                        'tipo' => 'sucesso',
                        'titulo' => 'Senha redefinida com sucesso!',
                        'acaoTexto' => 'Acessar sistema',
                        'acaoUrl' => $this->buildPublicBaseUrl() . '/',
                    ]
                )
            );
        }
    }

    private function enviarEmailSistema(string $destino, string $assunto, string $mensagemHtml): void
    {
        if ($destino === '' || !filter_var($destino, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $from = 'no-reply@iteva.org.br';
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= 'From: ' . $from . "\r\n";
        $headers .= 'Reply-To: ' . $from . "\r\n";
        $headers .= 'X-Mailer: PHP/' . phpversion();

        try {
            @mail($destino, $assunto, $mensagemHtml, $headers);
        } catch (Throwable) {
        }
    }

    private function montarEmailHtml(string $nome, string $mensagem, array $opcoes = []): string
    {
        $tipo = (string) ($opcoes['tipo'] ?? 'sucesso');
        $titulo = (string) ($opcoes['titulo'] ?? 'Atualizacao de senha');
        $acaoTexto = (string) ($opcoes['acaoTexto'] ?? 'Acessar sistema');
        $acaoUrl = (string) ($opcoes['acaoUrl'] ?? ($this->buildPublicBaseUrl() . '/'));

        $isAlerta = $tipo === 'alerta';
        $iconeBg = $isAlerta ? '#FFF4E5' : '#E8F5EC';
        $iconeCor = $isAlerta ? '#F57C00' : '#24A148';
        $icone = $isAlerta ? '&#9888;' : '&#10003;';
        $mensagemInfo = $isAlerta
            ? '<strong>Nao foi voce?</strong> Se voce nao solicitou esta acao, entre em contato imediatamente para proteger sua conta.'
            : '<strong>Dica de seguranca:</strong> apos entrar no sistema, altere sua senha para uma combinacao exclusiva.';

        $logoUrl = $this->resolveSystemLogoAbsoluteUrl();

        return '
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ConectaOSC</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e7eb;">
          <tr>
            <td style="height:6px;background:#F57C00;line-height:6px;font-size:0;">&nbsp;</td>
          </tr>
          <tr>
            <td align="center" style="padding:28px 20px 12px;">
              <img src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="ITEVA" width="96" style="width:96px;max-width:96px;height:auto;display:block;">
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:10px 28px 0;">
              <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
                <tr>
                  <td align="center" style="width:64px;height:64px;border-radius:999px;background:' . $iconeBg . ';color:' . $iconeCor . ';font-size:32px;line-height:64px;font-weight:bold;">' . $icone . '</td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:20px 28px 0;">
              <h1 style="margin:0;font-size:30px;line-height:1.2;color:#0f172a;">' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 32px 0;text-align:center;font-size:17px;line-height:1.6;color:#475569;">
              Ola, <strong>' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '</strong>.<br>
              ' . htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') . '
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:24px 28px 0;">
              <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
                <tr>
                  <td align="center" bgcolor="#F57C00" style="background:#F57C00;border-radius:8px;">
                    <a href="' . htmlspecialchars($acaoUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#F57C00;border:1px solid #F57C00;color:#ffffff !important;text-decoration:none;font-weight:700;font-size:16px;line-height:1;padding:14px 28px;border-radius:8px;">' . htmlspecialchars($acaoTexto, ENT_QUOTES, 'UTF-8') . '</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:20px 28px 26px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
                <tr>
                  <td style="padding:14px 16px;font-size:14px;line-height:1.6;color:#64748b;">
                    ' . $mensagemInfo . '
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background:#001B2E;padding:26px 20px 28px;text-align:center;color:#cbd5e1;font-size:12px;line-height:1.8;">
              <div style="font-weight:700;color:#ffffff;">RUA D, 164 - RESIDENCIAL ARVOREDO - AQUIRAZ/CE</div>
              <div>CEP: 61.700.000 - CAIXA POSTAL 66</div>
              <div style="color:#ffffff;">iteva@iteva.org.br | +55 85 3362-3210</div>
              <div style="margin-top:10px;border-top:1px solid rgba(255,255,255,0.15);padding-top:10px;">
                <div>Copyright © 2024 ITEVA - Todos os direitos reservados.</div>
                <div>Instituto Tecnologico e Vocacional Avancado</div>
                <div>CNPJ: 03.502.169/0001-38</div>
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
    }

    private function resolveLegacyAbsoluteUrl(string $legacyPath): string
    {
        $legacyPath = '/' . ltrim($legacyPath, '/');

        if (preg_match('/^https?:\/\//i', $this->baseUrl)) {
            $parts = parse_url($this->baseUrl);
            $scheme = (string) ($parts['scheme'] ?? 'https');
            $host = (string) ($parts['host'] ?? '');
            $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
            if ($host !== '') {
                return $scheme . '://' . $host . $port . $legacyPath;
            }
        }

        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if ($host !== '') {
            $https = (string) ($_SERVER['HTTPS'] ?? '');
            $scheme = ($https !== '' && strtolower($https) !== 'off') ? 'https' : 'http';
            return $scheme . '://' . $host . $legacyPath;
        }

        return $legacyPath;
    }

    private function buildPublicBaseUrl(): string
    {
        $envPublicBaseUrl = trim((string)($_SESSION['BASE_PUBLIC_URL'] ?? (getenv('APP_PUBLIC_BASE_URL') ?: '')));
        if ($envPublicBaseUrl !== '') {
            return rtrim($envPublicBaseUrl, '/');
        }

        $baseUrl = trim($this->baseUrl);
        if (preg_match('/^https?:\/\//i', $baseUrl)) {
            return rtrim($baseUrl, '/');
        }

        $basePath = '/' . trim(str_replace('\\', '/', $baseUrl), '/');
        $basePath = rtrim($basePath, '/');

        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if ($host !== '') {
            $https = (string) ($_SERVER['HTTPS'] ?? '');
            $scheme = ($https !== '' && strtolower($https) !== 'off') ? 'https' : 'http';
            return $scheme . '://' . $host . $basePath;
        }

        return $basePath !== '' ? $basePath : '/conecta';
    }

    private function resolveSystemLogoAbsoluteUrl(): string
    {
        $assetsImgUrl = (string) ($_SESSION['BASE_ASSETS_IMG_URL'] ?? '');
        $logoPath = '/assets/img/sistema/logo-bgbranco.png';
        if ($assetsImgUrl !== '') {
            $logoPath = rtrim($assetsImgUrl, '/') . '/sistema/logo-bgbranco.png';
        }

        if (preg_match('/^https?:\/\//i', $logoPath)) {
            return $logoPath;
        }

        return $this->resolveLegacyAbsoluteUrl($logoPath);
    }
}
