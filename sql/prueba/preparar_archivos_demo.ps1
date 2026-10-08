# Copia la hoja PDF de prueba a las rutas que utiliza la plataforma.
# Ejecutar antes de importar demostracion_completa.sql.
[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '../..')).Path
$sourcePdf = Join-Path $PSScriptRoot 'Evidencia de prueba.pdf'
$sqlPath = Join-Path $PSScriptRoot 'demostracion_completa.sql'
$destinationDir = Join-Path $projectRoot 'uploads/files'

if (-not (Test-Path -LiteralPath $sourcePdf -PathType Leaf)) {
    throw "No se encuentra el PDF de origen: $sourcePdf"
}
$sqlText = Get-Content -LiteralPath $sqlPath -Raw -Encoding UTF8
$names = @([regex]::Matches($sqlText, "'(?<file>demo_ge_[0-9]{2}_[0-9]{2}(?:_anexo)?\.pdf)'") |
    ForEach-Object { $_.Groups['file'].Value } | Sort-Object -Unique)
if ($names.Count -ne 90) {
    throw "Se esperaban 90 nombres PDF (45 evidencias y 45 anexos); se encontraron $($names.Count)."
}

$sourceHash = (Get-FileHash -LiteralPath $sourcePdf -Algorithm SHA256).Hash
New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
# Revisar todos los destinos antes de copiar. No sobrescribir archivos diferentes.
foreach ($name in $names) {
    $target = Join-Path $destinationDir $name
    if (Test-Path -LiteralPath $target) {
        if (-not (Test-Path -LiteralPath $target -PathType Leaf)) {
            throw "El destino no es un archivo: $target"
        }
        if ((Get-FileHash -LiteralPath $target -Algorithm SHA256).Hash -ne $sourceHash) {
            throw "Ya existe un archivo con contenido diferente; se conserva: $target"
        }
    }
}
$copied = 0
foreach ($name in $names) {
    $target = Join-Path $destinationDir $name
    if (-not (Test-Path -LiteralPath $target)) {
        Copy-Item -LiteralPath $sourcePdf -Destination $target
        $copied++
    }
    if ((Get-FileHash -LiteralPath $target -Algorithm SHA256).Hash -ne $sourceHash) {
        throw "Fallo la verificacion de la copia: $target"
    }
}
Write-Host "Listo: $($names.Count) PDF verificados en $destinationDir; $copied copiados ahora."
Write-Host 'Ya puedes importar sql/prueba/demostracion_completa.sql en tu base existente.'
