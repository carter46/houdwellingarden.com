# Create local MariaDB/MySQL database and import schema + property data.
$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot

function Find-MysqlExe {
    $candidates = @(
        "C:\Users\user pc\local-mariadb\mariadb-11.4.4-winx64\bin\mysql.exe",
        "C:\Users\user pc\local-dev\mariadb-11.4.4-winx64\bin\mysql.exe",
        "$env:ProgramFiles\MariaDB*\bin\mysql.exe",
        "${env:ProgramFiles(x86)}\MariaDB*\bin\mysql.exe",
        "$env:ProgramFiles\MySQL\MySQL Server*\bin\mysql.exe",
        "C:\xampp\mysql\bin\mysql.exe",
        "C:\laragon\bin\mysql\mysql-*\bin\mysql.exe"
    )
    foreach ($pattern in $candidates) {
        $hit = Get-Item $pattern -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($hit) { return $hit.FullName }
    }
    return $null
}

$mysql = Find-MysqlExe
if (-not $mysql) {
    Write-Host "mysql.exe not found. Install MariaDB (winget install MariaDB.Server) or XAMPP, then re-run this script."
    exit 1
}

Write-Host "Using: $mysql"
$dbName = "houdwell_local"
$args = @("-u", "root", "--default-character-set=utf8mb4")

& $mysql @args -e "CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if ($LASTEXITCODE -ne 0) {
    Write-Host "Could not connect as root with no password. Try: mysql -u root -p and set pass in config.local.php"
    exit 1
}

$files = @(
    "database\schema.sql",
    "database\seed.sql"
)

foreach ($rel in $files) {
    $path = Join-Path $Root $rel
    if (-not (Test-Path $path)) {
        Write-Host "Skip missing $rel"
        continue
    }
    Write-Host "Importing $rel ..."
    Get-Content -Raw -Encoding UTF8 $path | & $mysql @args $dbName
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Warning: $rel may have partial errors (some migrations are idempotent)."
    }
}

Write-Host "Done. Database: $dbName"
Write-Host "Run: php check.php"
Write-Host "Then: php -S 127.0.0.1:8765 -t ."
