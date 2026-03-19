param(
  [string]$BaseUrl = "http://localhost/conectaosc3/clinica"
)

$distPath = "dist/frontend-angular/browser"
$mainFile = (Get-ChildItem -Path $distPath -Filter 'main-*.js' -ErrorAction SilentlyContinue | Select-Object -First 1).Name
$styleFile = (Get-ChildItem -Path $distPath -Filter 'styles-*.css' -ErrorAction SilentlyContinue | Select-Object -First 1).Name

if (-not $mainFile -or -not $styleFile) {
  Write-Output "[FAIL] Build Angular nao encontrado em $distPath"
  exit 1
}

$checks = @(
  @{ Name = "Clinica Root"; Url = "$BaseUrl/"; Expect = 302 },
  @{ Name = "Legacy Fallback"; Url = "$BaseUrl/?legacy=1"; Expect = 302 },
  @{ Name = "Angular Index"; Url = "$BaseUrl/frontend-angular/"; Expect = 200 },
  @{ Name = "Angular Dashboard"; Url = "$BaseUrl/frontend-angular/dashboard"; Expect = 200 },
  @{ Name = "Angular Pacientes"; Url = "$BaseUrl/frontend-angular/pacientes"; Expect = 200 },
  @{ Name = "Angular Anamnese"; Url = "$BaseUrl/frontend-angular/anamnese"; Expect = 200 },
  @{ Name = "Angular Main JS"; Url = "$BaseUrl/frontend-angular/$mainFile"; Expect = 200 },
  @{ Name = "Angular Styles CSS"; Url = "$BaseUrl/frontend-angular/$styleFile"; Expect = 200 },
  @{ Name = "Clinica API"; Url = "$BaseUrl/api/pacientes"; Expect = 401 }
)

Write-Output "=== Smoke Test Clinica Front ==="
$failed = @()

foreach ($check in $checks) {
  $headers = curl.exe -s -I --max-time 8 $check.Url
  if (-not $headers) {
    Write-Output ("[FAIL] {0} -> sem resposta" -f $check.Name)
    $failed += $check.Name
    continue
  }

  $statusLine = ($headers | Select-String -Pattern '^HTTP/').Line | Select-Object -Last 1
  $statusCode = 0
  if ($statusLine -match 'HTTP/\S+\s+(\d{3})') {
    $statusCode = [int]$Matches[1]
  }

  $ok = $false
  if ($check.Expect -eq 401) {
    $ok = ($statusCode -eq 401 -or $statusCode -eq 404)
  } else {
    $ok = ($statusCode -eq $check.Expect)
  }

  if ($ok) {
    Write-Output ("[OK]   {0} -> {1}" -f $check.Name, $statusCode)
  } else {
    Write-Output ("[FAIL] {0} -> {1} (esperado {2})" -f $check.Name, $statusCode, $check.Expect)
    $failed += $check.Name
  }
}

if ($failed.Count -gt 0) {
  Write-Output "=== RESULTADO: FALHAS ==="
  $failed | ForEach-Object { Write-Output (" - " + $_) }
  exit 1
}

Write-Output "=== RESULTADO: OK ==="
exit 0

