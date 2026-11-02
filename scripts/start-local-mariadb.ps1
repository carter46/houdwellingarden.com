$mysqld = "C:\Users\user pc\local-mariadb\mariadb-11.4.4-winx64\bin\mysqld.exe"
$myIni  = "C:\Users\user pc\local-mariadb\data\my.ini"
if (-not (Test-Path $mysqld)) {
    Write-Error "MariaDB not found at $mysqld"
}
if (Get-NetTCPConnection -LocalPort 3306 -State Listen -ErrorAction SilentlyContinue) {
    Write-Host "MariaDB already listening on 3306."
    exit 0
}
Start-Process -FilePath $mysqld -ArgumentList "--defaults-file=`"$myIni`"" -WindowStyle Hidden
Start-Sleep -Seconds 4
Write-Host "MariaDB started on port 3306."
