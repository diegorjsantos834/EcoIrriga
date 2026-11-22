# ============================================================================
#  serve-php.ps1 — sobe o ambiente completo da loja Ecolrriga
# ----------------------------------------------------------------------------
#  1) Liga o MySQL do XAMPP (se a porta 3306 estiver fechada);
#  2) Cria o banco `ecolrriga` importando db/ecolrriga.sql (se não existir);
#  3) Inicia o servidor PHP embutido em http://localhost:8000
#
#  Uso:
#    powershell -ExecutionPolicy Bypass -File serve-php.ps1
#    powershell -ExecutionPolicy Bypass -File serve-php.ps1 -Port 8080
# ============================================================================

param(
    [int]$Port = 8000
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $MyInvocation.MyCommand.Path

# Caminhos do XAMPP
$phpExe   = "C:\xampp\php\php.exe"
$mysqlExe = "C:\xampp\mysql\bin\mysql.exe"
$mysqld   = "C:\xampp\mysql\bin\mysqld.exe"
$myIni    = "C:\xampp\mysql\bin\my.ini"

function Test-Port([int]$p) {
    $client = New-Object System.Net.Sockets.TcpClient
    try { $client.Connect("127.0.0.1", $p); return $true } catch { return $false } finally { $client.Close() }
}

# --- 1) MySQL ---------------------------------------------------------------
if (-not (Test-Port 3306)) {
    Write-Host "[1/3] Liguando o MySQL do XAMPP..." -ForegroundColor Cyan
    Start-Process -FilePath $mysqld -ArgumentList "--defaults-file=`"$myIni`"" -WindowStyle Hidden
    # aguarda a porta 3306 abrir (até 30s)
    for ($i = 0; $i -lt 30 -and -not (Test-Port 3306); $i++) { Start-Sleep -Seconds 1 }
    if (-not (Test-Port 3306)) {
        Write-Error "Nao foi possivel ligar o MySQL. Abra o XAMPP Control Panel e clique em Start no MySQL."
        exit 1
    }
} else {
    Write-Host "[1/3] MySQL ja esta ligado na porta 3306." -ForegroundColor Green
}

# --- 2) Banco de dados ------------------------------------------------------
Write-Host "[2/3] Verificando o banco 'ecolrriga'..." -ForegroundColor Cyan
$sqlFile = Join-Path $root "db\ecolrriga.sql"

# So importa na PRIMEIRA vez: se o banco ja existir, os dados sao preservados
# (o script db/ecolrriga.sql tem DROP TABLE e apagaria o que ja foi cadastrado)
$existe = cmd /c "`"$mysqlExe`" -u root -N -e `"SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='ecolrriga'`""
if ("$existe".Trim() -eq "ecolrriga") {
    Write-Host "       Banco 'ecolrriga' ja existe — dados preservados." -ForegroundColor Green
} else {
    Write-Host "       Importando db/ecolrriga.sql..." -ForegroundColor Cyan
    # Obs.: no PowerShell nao existe redirecionamento "<", por isso usamos o cmd.
    cmd /c "`"$mysqlExe`" -u root --default-character-set=utf8mb4 < `"$sqlFile`"" | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Falha ao importar db/ecolrriga.sql."
        exit 1
    }
}

# --- 3) Servidor PHP --------------------------------------------------------
Write-Host "[3/3] Servidor PHP em http://localhost:$Port" -ForegroundColor Green
Write-Host "  Loja  -> http://localhost:$Port/loja.php" -ForegroundColor Yellow
Write-Host "  Admin -> http://localhost:$Port/admin/index.php" -ForegroundColor Yellow
Write-Host "  Landing -> http://localhost:$Port/index.html" -ForegroundColor Yellow
Write-Host "Pressione Ctrl+C para parar." -ForegroundColor DarkGray

& $phpExe -S "localhost:$Port" -t $root
