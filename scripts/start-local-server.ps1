$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root
$port = if ($env:PORT) { $env:PORT } else { "8765" }
Write-Host "Starting PHP at http://127.0.0.1:$port/index.php"
Write-Host "Press Ctrl+C to stop."
php -S "127.0.0.1:$port" router.php
