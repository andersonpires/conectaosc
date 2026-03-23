$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$repoRoot = Split-Path -Parent (Split-Path -Parent $root)
$layoutRoot = Join-Path $repoRoot 'clinica\!Suporte\layout_2'

function ResolveLayoutReference([string]$folderPattern) {
  $folder = Get-ChildItem -Path $layoutRoot -Directory | Where-Object { $_.Name -like $folderPattern } | Select-Object -First 1
  if ($null -eq $folder) {
    return Join-Path $layoutRoot (Join-Path $folderPattern 'code.html')
  }
  return Join-Path $folder.FullName 'code.html'
}

$shellTarget = Join-Path $root 'src\app\layout\shell\shell.component.html'
$checks = @(
  @{
    Name = 'Dashboard';
    Reference = ResolveLayoutReference('doctor_dashboard_summary');
    Target = Join-Path $root 'src\app\features\dashboard\pages\dashboard-page.component.html';
    Required = @('Bem-vindo de volta', 'Resumo', 'Atendimentos de hoje', 'material-symbols-outlined');
  },
  @{
    Name = 'Agenda';
    Reference = ResolveLayoutReference('daily_appointment_schedule');
    Target = Join-Path $root 'src\app\features\agenda\pages\agenda-page.component.html';
    Required = @('week-strip', 'current-line', 'material-symbols-outlined');
  },
  @{
    Name = 'Pacientes Lista';
    Reference = ResolveLayoutReference('patient_directory_list');
    Target = Join-Path $root 'src\app\features\patients-list\pages\patients-list-page.component.html';
    Required = @('Lista de Pacientes', 'Clinica Medica', 'Buscar por nome ou ID', 'material-symbols-outlined');
  },
  @{
    Name = 'Paciente Prontuario';
    Reference = ResolveLayoutReference('patient_medical_records');
    Target = Join-Path $root 'src\app\features\patient\pages\patient-page.component.html';
    Required = @('Prontuario do Paciente', 'Consultas Recentes', 'Historico', 'material-icons-round');
  },
  @{
    Name = 'Prontuario Emissao';
    Reference = ResolveLayoutReference('prescription_and_certificate_issuer');
    Target = Join-Path $root 'src\app\features\prontuario\pages\prontuario-page.component.html';
    Required = @('Emitir Documento', 'BUSCAR PACIENTE', 'ASSINATURA DO PROFISSIONAL', 'Emitir e Imprimir');
  },
  @{
    Name = 'Anamnese';
    Reference = ResolveLayoutReference('*anamnese*');
    Target = Join-Path $root 'src\app\features\anamnese\pages\anamnese-page.component.html';
    Required = @('Anamnese do Paciente', 'Progresso do Preenchimento', 'Salvar Anamnese', 'card-head');
  },
  @{
    Name = 'Configuracoes';
    Reference = ResolveLayoutReference('*config*');
    Target = Join-Path $root 'src\app\features\settings\pages\settings-page.component.html';
    Required = @('Configuracoes', 'Sair da Conta', 'profile-card', 'material-symbols-outlined');
  }
)

$globalBottomNavTokens = @(
  'global-bottom-nav',
  'routerLink="/dashboard"',
  'routerLink="/agenda"',
  'routerLink="/pacientes"',
  'routerLink="/prontuario"',
  'routerLink="/atendimento"'
)

$forbiddenPageTokens = @('status-row', 'bottom-nav')

Write-Output '=== Layout Conformance Check ==='
$failed = @()

if (!(Test-Path $shellTarget)) {
  Write-Output '[FAIL] Shell: alvo ausente'
  $failed += 'Shell'
} else {
  $shellContent = Get-Content -Raw $shellTarget
  $missingGlobal = @()
  foreach ($token in $globalBottomNavTokens) {
    if ($shellContent -notmatch [regex]::Escape($token)) {
      $missingGlobal += $token
    }
  }

  if ($missingGlobal.Count -eq 0) {
    Write-Output '[OK]   Shell (menu global fixo)'
  } else {
    Write-Output ("[FAIL] Shell: faltando => {0}" -f ($missingGlobal -join ', '))
    $failed += 'Shell'
  }
}

foreach ($item in $checks) {
  if (!(Test-Path $item.Reference)) {
    Write-Output ("[FAIL] {0}: referencia ausente" -f $item.Name)
    $failed += $item.Name
    continue
  }
  if (!(Test-Path $item.Target)) {
    Write-Output ("[FAIL] {0}: alvo ausente" -f $item.Name)
    $failed += $item.Name
    continue
  }

  $targetContent = Get-Content -Raw $item.Target
  $missing = @()
  foreach ($token in $item.Required) {
    if ($targetContent -notmatch [regex]::Escape($token)) {
      $missing += $token
    }
  }

  $forbiddenFound = @()
  foreach ($token in $forbiddenPageTokens) {
    if ($targetContent -match [regex]::Escape($token)) {
      $forbiddenFound += $token
    }
  }

  if ($missing.Count -eq 0 -and $forbiddenFound.Count -eq 0) {
    Write-Output ("[OK]   {0}" -f $item.Name)
  } else {
    if ($missing.Count -gt 0) {
      Write-Output ("[FAIL] {0}: faltando => {1}" -f $item.Name, ($missing -join ', '))
    }
    if ($forbiddenFound.Count -gt 0) {
      Write-Output ("[FAIL] {0}: token legado presente => {1}" -f $item.Name, ($forbiddenFound -join ', '))
    }
    $failed += $item.Name
  }
}

if ($failed.Count -gt 0) {
  Write-Output '=== RESULTADO: FALHAS ==='
  ($failed | Select-Object -Unique) | ForEach-Object { Write-Output (" - " + $_) }
  exit 1
}

Write-Output '=== RESULTADO: OK ==='
exit 0
