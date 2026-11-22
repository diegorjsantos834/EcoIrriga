# Servidor local mínimo para o projeto Ecolrriga (sem dependências)
# Uso: powershell -ExecutionPolicy Bypass -File serve.ps1 [-Port 8000]
param(
    [int]$Port = 8000,
    [string]$Root = $PSScriptRoot
)

$mime = @{
    ".html" = "text/html; charset=utf-8"
    ".css"  = "text/css; charset=utf-8"
    ".js"   = "text/javascript; charset=utf-8"
    ".mjs"  = "text/javascript; charset=utf-8"
    ".json" = "application/json"
    ".stl"  = "application/octet-stream"
    ".png"  = "image/png"
    ".jpg"  = "image/jpeg"
    ".svg"  = "image/svg+xml"
    ".ico"  = "image/x-icon"
}

$listener = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Loopback, $Port)
$listener.Start()
Write-Host "Ecolrriga servindo em http://localhost:$Port  (raiz: $Root)" -ForegroundColor Green
Write-Host "Ctrl+C para parar."

while ($true) {
    $client = $listener.AcceptTcpClient()
    try {
        $stream = $client.GetStream()
        $reader = [System.IO.StreamReader]::new($stream)
        $requestLine = $reader.ReadLine()
        while ($true) {
            $line = $reader.ReadLine()
            if ($null -eq $line -or $line -eq "") { break }
        }

        $ok = $false
        $bytes = $null
        $ctype = "text/plain"

        if ($requestLine) {
            $parts = $requestLine.Split(" ")
            if ($parts.Length -ge 2 -and $parts[0] -eq "GET") {
                $path = [System.Uri]::UnescapeDataString($parts[1].Split("?")[0])
                if ($path -eq "/") { $path = "/index.html" }
                $file = Join-Path $Root ($path.Replace("/", [System.IO.Path]::DirectorySeparatorChar))
                if ((Test-Path $file) -and -not (Get-Item $file).PSIsContainer) {
                    $bytes = [System.IO.File]::ReadAllBytes($file)
                    $ext = [System.IO.Path]::GetExtension($file).ToLower()
                    $ctype = $mime[$ext]; if (-not $ctype) { $ctype = "application/octet-stream" }
                    $ok = $true
                    Write-Host ("  200  {0}" -f $path)
                }
            }
        }

        if ($ok) {
            $header = "HTTP/1.1 200 OK`r`nContent-Type: $ctype`r`nContent-Length: $($bytes.Length)`r`nConnection: close`r`nCache-Control: no-cache`r`n`r`n"
        } else {
            $bytes = [System.Text.Encoding]::UTF8.GetBytes("404 Not Found")
            $header = "HTTP/1.1 404 Not Found`r`nContent-Type: text/plain`r`nContent-Length: $($bytes.Length)`r`nConnection: close`r`n`r`n"
            Write-Host ("  404  {0}" -f $requestLine)
        }

        $hb = [System.Text.Encoding]::ASCII.GetBytes($header)
        $stream.Write($hb, 0, $hb.Length)
        $stream.Write($bytes, 0, $bytes.Length)
        $stream.Flush()
    } catch {
        Write-Host ("  erro: " + $_.Exception.Message) -ForegroundColor Yellow
    } finally {
        $client.Close()
    }
}
