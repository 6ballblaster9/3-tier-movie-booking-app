param(
    [Parameter(Mandatory = $true)]
    [string]$BucketName
)

$ErrorActionPreference = 'Stop'
$workspaceRoot = Split-Path -Parent $PSScriptRoot
$deployRoot = Join-Path $env:TEMP ('cineverse-package-' + [guid]::NewGuid().ToString('N'))
$stage = Join-Path $deployRoot 'package'
$zipPath = Join-Path $deployRoot 'cineverse.zip'
New-Item -ItemType Directory -Path $stage -Force | Out-Null

Copy-Item (Join-Path $workspaceRoot 'index.html'), (Join-Path $workspaceRoot 'style.css'), (Join-Path $workspaceRoot 'script.js') -Destination $stage
Copy-Item (Join-Path $workspaceRoot 'api'), (Join-Path $workspaceRoot 'admin'), (Join-Path $workspaceRoot 'includes'), (Join-Path $workspaceRoot 'database'), (Join-Path $workspaceRoot 'config') -Destination $stage -Recurse
Remove-Item (Join-Path $stage 'config\config.php') -Force -ErrorAction SilentlyContinue

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$archive = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    Get-ChildItem -Path $stage -File -Recurse | ForEach-Object {
        $entryName = $_.FullName.Substring($stage.Length + 1).Replace('\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $_.FullName, $entryName, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
    }
}
finally {
    $archive.Dispose()
}

$archive = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
$entryNames = @($archive.Entries | ForEach-Object FullName)
$archive.Dispose()
if ($entryNames -contains 'config/config.php') {
    throw 'Local config.php was unexpectedly included in the source archive.'
}
if (-not ($entryNames -contains 'database/schema.sql') -or -not ($entryNames -contains 'api/db.php')) {
    throw 'Required application files are missing from the source archive.'
}

& aws s3 cp $zipPath "s3://$BucketName/cineverse.zip" --region us-east-1 --only-show-errors
if ($LASTEXITCODE -ne 0) {
    throw 'Could not upload the source archive.'
}

Write-Output "Uploaded verified source bundle ($($entryNames.Count) files, $((Get-Item $zipPath).Length) bytes)."