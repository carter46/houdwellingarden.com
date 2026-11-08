# Start MariaDB (if needed) + PHP site for local preview.
$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

& (Join-Path $PSScriptRoot "start-local-mariadb.ps1")
$port = if ($env:PORT) { $env:PORT } else { "8765" }
Write-Host ""
Write-Host "Open: http://127.0.0.1:$port/"
Write-Host "Admin: http://127.0.0.1:$port/admin/login.php (create account via admin/setup.php if needed)"
Write-Host ""
php -S "127.0.0.1:$port" -t . router.php
