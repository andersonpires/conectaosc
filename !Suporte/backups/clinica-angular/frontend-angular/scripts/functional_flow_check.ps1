param(
  [string]$BaseUrl = "http://localhost/conectaosc3/clinica",
  [string]$PhpSessId = "",
  [switch]$RequireAuth
)

$ErrorActionPreference = 'Stop'

function Get-StatusCode {
  param(
    [string]$Url,
    [string]$Cookie = ""
  )

  $args = @('-s', '-I', '--max-time', '10')
  if ($Cookie -ne '') {
    $args += @('-H', "Cookie: PHPSESSID=$Cookie")
  }
  $args += $Url

  $headers = & curl.exe @args
  if (-not $headers) { return 0 }
  $statusLine = ($headers | Select-String -Pattern '^HTTP/').Line | Select-Object -Last 1
  if ($statusLine -match 'HTTP/\S+\s+(\d{3})') {
    return [int]$Matches[1]
  }
  return 0
}

Write-Output '=== Functional Flow Check ==='
Write-Output ("BaseUrl: {0}" -f $BaseUrl)

$failed = @()

$publicChecks = @(
  @{ Name = 'Angular Dashboard Route'; Url = "$BaseUrl/frontend-angular/dashboard"; Expect = 200 },
  @{ Name = 'Angular Pacientes Route'; Url = "$BaseUrl/frontend-angular/pacientes"; Expect = 200 },
  @{ Name = 'Angular Prontuario Route'; Url = "$BaseUrl/frontend-angular/prontuario"; Expect = 200 },
  @{ Name = 'Angular Anamnese Route'; Url = "$BaseUrl/frontend-angular/anamnese"; Expect = 200 }
)

foreach ($check in $publicChecks) {
  $status = Get-StatusCode -Url $check.Url
  if ($status -eq $check.Expect) {
    Write-Output ("[OK]   {0} -> {1}" -f $check.Name, $status)
  } else {
    Write-Output ("[FAIL] {0} -> {1} (esperado {2})" -f $check.Name, $status, $check.Expect)
    $failed += $check.Name
  }
}

$apiProbeUnauth = Get-StatusCode -Url "$BaseUrl/api/pacientes"
if ($apiProbeUnauth -in 401,404) {
  Write-Output ("[OK]   API sem sessao bloqueada -> {0}" -f $apiProbeUnauth)
} else {
  Write-Output ("[WARN] API sem sessao retornou status inesperado -> {0}" -f $apiProbeUnauth)
}

if ($PhpSessId -ne '') {
  Write-Output '--- Validacao com sessao informada ---'

  $authWebChecks = @(
    @{ Name = 'Clinica Root Auth'; Url = "$BaseUrl/"; Accept = @(200) },
    @{ Name = 'Clinica Legacy Auth'; Url = "$BaseUrl/?legacy=1"; Accept = @(200) },
    @{ Name = 'Clinica Dashboard Auth'; Url = "$BaseUrl/dashboard"; Accept = @(200) },
    @{ Name = 'Clinica Pacientes Auth'; Url = "$BaseUrl/pacientes"; Accept = @(200) },
    @{ Name = 'Clinica Anamnese Auth'; Url = "$BaseUrl/anamnese"; Accept = @(200) }
  )

  foreach ($check in $authWebChecks) {
    $status = Get-StatusCode -Url $check.Url -Cookie $PhpSessId
    if ($check.Accept -contains $status) {
      Write-Output ("[OK]   {0} -> {1}" -f $check.Name, $status)
    } else {
      Write-Output ("[FAIL] {0} -> {1} (esperado: {2})" -f $check.Name, $status, ($check.Accept -join '/'))
      $failed += $check.Name
    }
  }

  $authApiChecks = @(
    @{ Name = 'API Root Auth'; Url = "$BaseUrl/api/"; Accept = @(200) },
    @{ Name = 'API Pacientes Auth'; Url = "$BaseUrl/api/pacientes"; Accept = @(200) },
    @{ Name = 'API Agenda Auth'; Url = "$BaseUrl/api/agenda?view=day&date=2026-02-23"; Accept = @(200) },
    @{ Name = 'API Prontuarios Auth'; Url = "$BaseUrl/api/prontuarios"; Accept = @(200) },
    @{ Name = 'API Anamnese Auth'; Url = "$BaseUrl/api/anamnese"; Accept = @(200) }
  )

  foreach ($check in $authApiChecks) {
    $status = Get-StatusCode -Url $check.Url -Cookie $PhpSessId
    if ($check.Accept -contains $status) {
      Write-Output ("[OK]   {0} -> {1}" -f $check.Name, $status)
    } else {
      Write-Output ("[FAIL] {0} -> {1} (esperado: {2})" -f $check.Name, $status, ($check.Accept -join '/'))
      $failed += $check.Name
    }
  }
} else {
  if ($RequireAuth) {
    Write-Output '[FAIL] Validacao autenticada requerida, mas -PhpSessId nao foi informado.'
    $failed += 'Auth Session Missing'
  } else {
    Write-Output '[SKIP] Validacao autenticada nao executada (informe -PhpSessId).'
  }
}

if ($failed.Count -gt 0) {
  Write-Output '=== RESULTADO: FALHAS ==='
  $failed | ForEach-Object { Write-Output (" - " + $_) }
  exit 1
}

Write-Output '=== RESULTADO: OK ==='
exit 0
