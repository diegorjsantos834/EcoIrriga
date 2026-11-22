# Gera silhuetas ASCII (vista de frente/tras/topo/etc) de um STL binario
# Ajuda a localizar buracos/entradas de cada peca.
param(
    [string]$File,
    [int]$Cols = 56,
    [int]$Rows = 36
)

$bytes = [System.IO.File]::ReadAllBytes($File)
$triCount = [BitConverter]::ToUInt32($bytes, 80)
$tris = New-Object System.Collections.Generic.List[object]
$off = 84
for ($i = 0; $i -lt $triCount; $i++) {
    $off += 12 # normal
    $v = @()
    for ($k = 0; $k -lt 3; $k++) {
        $x = [BitConverter]::ToSingle($bytes, $off)
        $y = [BitConverter]::ToSingle($bytes, $off + 4)
        $z = [BitConverter]::ToSingle($bytes, $off + 8)
        $off += 12
        $v += ,@($x, $y, $z)
    }
    $off += 2
    $tris.Add($v)
}

# extents
$min = @([double]::MaxValue, [double]::MaxValue, [double]::MaxValue)
$max = @([double]::MinValue, [double]::MinValue, [double]::MinValue)
foreach ($t in $tris) {
    foreach ($p in $t) {
        for ($a = 0; $a -lt 3; $a++) {
            if ($p[$a] -lt $min[$a]) { $min[$a] = $p[$a] }
            if ($p[$a] -gt $max[$a]) { $max[$a] = $p[$a] }
        }
    }
}
Write-Host ("{0}  tris={1}" -f (Split-Path $File -Leaf), $triCount)
Write-Host ("  X [{0:N2} .. {1:N2}]   Y [{2:N2} .. {3:N2}]   Z [{4:N2} .. {5:N2}]" -f $min[0],$max[0],$min[1],$max[1],$min[2],$max[2])

# projeta: funcao retorna @(u,v) para um ponto, conforme a vista
$views = @(
    @{ Name = "FRENTE  (olhando de +Z para -Z, v = cima)"; U = { param($p) $p[0] };  V = { param($p) $p[1] } },
    @{ Name = "TRAS    (olhando de -Z para +Z)";           U = { param($p) -$p[0] }; V = { param($p) $p[1] } },
    @{ Name = "DIREITA (olhando de +X para -X)";           U = { param($p) -$p[2] }; V = { param($p) $p[1] } },
    @{ Name = "ESQ     (olhando de -X para +X)";           U = { param($p) $p[2] };  V = { param($p) $p[1] } },
    @{ Name = "TOPO    (olhando de +Y para -Y; dir=+X, v=-Z)"; U = { param($p) $p[0] }; V = { param($p) -$p[2] } },
    @{ Name = "BAIXO   (olhando de -Y para +Y; dir=+X, v=+Z)"; U = { param($p) $p[0] }; V = { param($p) $p[2] } }
)

function Test-PointInTri($px, $py, $ax, $ay, $bx, $by, $cx, $cy) {
    $d1 = ($px - $bx) * ($ay - $by) - ($ax - $bx) * ($py - $by)
    $d2 = ($px - $cx) * ($by - $cy) - ($bx - $cx) * ($py - $cy)
    $d3 = ($px - $ax) * ($cy - $ay) - ($cx - $ax) * ($py - $ay)
    $neg = ($d1 -lt 0) -or ($d2 -lt 0) -or ($d3 -lt 0)
    $pos = ($d1 -gt 0) -or ($d2 -gt 0) -or ($d3 -gt 0)
    return -not ($neg -and $pos)
}

foreach ($view in $views) {
    # projeta os triangulos
    $pts = @()
    foreach ($t in $tris) {
        $a = & $view.U $t[0]; $av = & $view.V $t[0]
        $b = & $view.U $t[1]; $bv = & $view.V $t[1]
        $c = & $view.U $t[2]; $cv = & $view.V $t[2]
        $pts += ,@($a,$av,$b,$bv,$c,$cv)
    }
    $us = $pts | ForEach-Object { $_[0]; $_[2]; $_[4] }
    $vs = $pts | ForEach-Object { $_[1]; $_[3]; $_[5] }
    $uMin = ($us | Measure-Object -Minimum).Minimum
    $uMax = ($us | Measure-Object -Maximum).Maximum
    $vMin = ($vs | Measure-Object -Minimum).Minimum
    $vMax = ($vs | Measure-Object -Maximum).Maximum
    $du = [Math]::Max($uMax - $uMin, 0.0001)
    $dv = [Math]::Max($vMax - $vMin, 0.0001)
    $pad = 0.02
    $uMin -= $du * $pad; $uMax += $du * $pad
    $vMin -= $dv * $pad; $vMax += $dv * $pad
    $du = $uMax - $uMin; $dv = $vMax - $vMin

    $grid = New-Object 'bool[,]' $Cols, $Rows
    foreach ($t in $pts) {
        $cu = @($t[0], $t[2], $t[4])
        $cv = @($t[1], $t[3], $t[5])
        $c0 = [int][Math]::Floor((($cu | Measure-Object -Minimum).Minimum - $uMin) / $du * ($Cols - 1))
        $c1 = [int][Math]::Floor((($cu | Measure-Object -Maximum).Maximum - $uMin) / $du * ($Cols - 1))
        $r0 = [int][Math]::Floor((($cv | Measure-Object -Maximum).Maximum - $vMin) / $dv * ($Rows - 1))
        $r1 = [int][Math]::Floor((($cv | Measure-Object -Minimum).Minimum - $vMin) / $dv * ($Rows - 1))
        $r0 = [Math]::Max(0, [Math]::Min($Rows - 1, $r0))
        $r1 = [Math]::Max(0, [Math]::Min($Rows - 1, $r1))
        $c0 = [Math]::Max(0, [Math]::Min($Cols - 1, $c0))
        $c1 = [Math]::Max(0, [Math]::Min($Cols - 1, $c1))
        if ($r0 -gt $r1) { $tmp = $r0; $r0 = $r1; $r1 = $tmp }
        if ($c0 -gt $c1) { $tmp = $c0; $c0 = $c1; $c1 = $tmp }
        for ($r = $r0; $r -le $r1; $r++) {
            # centro da celula: linha 0 = topo = vMax
            $py = $vMax - ($r / ($Rows - 1)) * $dv
            for ($c = $c0; $c -le $c1; $c++) {
                $px = $uMin + ($c / ($Cols - 1)) * $du
                if (Test-PointInTri $px $py $cu[0] $cv[0] $cu[1] $cv[1] $cu[2] $cv[2]) {
                    $grid[$c, $r] = $true
                }
            }
        }
    }

    Write-Host ""
    Write-Host ("--- {0} ---" -f $view.Name)
    for ($r = 0; $r -lt $Rows; $r++) {
        $line = New-Object System.Text.StringBuilder
        for ($c = 0; $c -lt $Cols; $c++) {
            if ($grid[$c, $r]) { [void]$line.Append('#') } else { [void]$line.Append('.') }
        }
        Write-Host $line.ToString()
    }
}
