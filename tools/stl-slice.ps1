# Fatia um STL binario em planos e desenha o corte em ASCII
# Ex.: stl-slice.ps1 -File "models\Caixa Case SE.stl" -Axis Y -Values 24,30,36,42
param(
    [string]$File,
    [ValidateSet("X","Y","Z")] [string]$Axis = "Y",
    [string]$Values = "0",
    [int]$Cols = 56,
    [int]$Rows = 36
)

$bytes = [System.IO.File]::ReadAllBytes($File)
$triCount = [BitConverter]::ToUInt32($bytes, 80)
$tris = New-Object System.Collections.Generic.List[object]
$off = 84
for ($i = 0; $i -lt $triCount; $i++) {
    $off += 12
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

$ai = switch ($Axis) { "X" { 0 } "Y" { 1 } "Z" { 2 } }
$ui = switch ($Axis) { "X" { 1 } "Y" { 0 } "Z" { 0 } }  # primeiro eixo de tela
$vi = switch ($Axis) { "X" { 2 } "Y" { 2 } "Z" { 1 } }  # segundo eixo de tela (vertical)

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

$uMin = $min[$ui]; $uMax = $max[$ui]
$vMin = $min[$vi]; $vMax = $max[$vi]
$du = $uMax - $uMin; $dv = $vMax - $vMin
$pad = 0.03
$uMin -= $du * $pad; $uMax += $du * $pad
$du = $uMax - $uMin
$vMin -= $dv * $pad; $vMax += $dv * $pad
$dv = $vMax - $vMin

function CellOf($u, $v) {
    $c = [int][Math]::Round(($u - $uMin) / $du * ($Cols - 1))
    $r = [int][Math]::Round(($vMax - $v) / $dv * ($Rows - 1))
    return @($c, $r)
}

function DrawLine($grid, $u1, $v1, $u2, $v2) {
    $steps = [Math]::Max([Math]::Abs($u2 - $u1) / ($du / $Cols), [Math]::Abs($v2 - $v1) / ($dv / $Rows))
    $steps = [Math]::Max(2, [Math]::Ceiling($steps * 1.5))
    for ($s = 0; $s -le $steps; $s++) {
        $u = $u1 + ($u2 - $u1) * $s / $steps
        $v = $v1 + ($v2 - $v1) * $s / $steps
        $cell = CellOf $u $v
        $c = $cell[0]; $r = $cell[1]
        if ($c -ge 0 -and $c -lt $Cols -and $r -ge 0 -and $r -lt $Rows) {
            $grid[$c, $r] = $true
        }
    }
}

foreach ($valRaw in $Values.Split(",")) {
    $val = [double]$valRaw
    $grid = New-Object 'bool[,]' $Cols, $Rows
    $segCount = 0
    foreach ($t in $tris) {
        # interseccao do triangulo com o plano ai = val
        $pts = @()
        for ($k = 0; $k -lt 3; $k++) {
            $p1 = $t[$k]; $p2 = $t[($k + 1) % 3]
            $d1 = $p1[$ai] - $val; $d2 = $p2[$ai] - $val
            if (($d1 -le 0 -and $d2 -ge 0) -or ($d1 -ge 0 -and $d2 -le 0)) {
                if ([Math]::Abs($d1 - $d2) -gt 1e-9) {
                    $tt = $d1 / ($d1 - $d2)
                    $pu = $p1[$ui] + ($p2[$ui] - $p1[$ui]) * $tt
                    $pv = $p1[$vi] + ($p2[$vi] - $p1[$vi]) * $tt
                    $pts += ,@($pu, $pv)
                } elseif ([Math]::Abs($d1) -le 1e-9) {
                    $pts += ,@($p1[$ui], $p1[$vi])
                }
            }
        }
        if ($pts.Count -ge 2) {
            DrawLine $grid $pts[0][0] $pts[0][1] $pts[1][0] $pts[1][1]
            if ($pts.Count -ge 3) {
                DrawLine $grid $pts[1][0] $pts[1][1] $pts[2][0] $pts[2][1]
            }
            $segCount++
        }
    }
    Write-Host ""
    Write-Host ("--- {0} = {1}   ({2} segmentos) ---" -f $Axis, $valRaw, $segCount)
    for ($r = 0; $r -lt $Rows; $r++) {
        $line = New-Object System.Text.StringBuilder
        for ($c = 0; $c -lt $Cols; $c++) {
            if ($grid[$c, $r]) { [void]$line.Append('#') } else { [void]$line.Append('.') }
        }
        Write-Host $line.ToString()
    }
}
