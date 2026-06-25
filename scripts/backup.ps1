# ============================================================
# Neilos Partner Portal — Database & Files Backup Script
# Usage: powershell .\scripts\backup.ps1
# ============================================================
param(
    [string]$BackupDir = "C:\Backups\Neilos",
    [string]$DBUser = "root",
    [string]$DBPass = "",
    [string]$DBName = "neilos_portal",
    [string]$ProjectDir = "C:\xampp\htdocs\Neilos"
)

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$dbFile    = "$BackupDir\db_$timestamp.sql"
$zipFile   = "$BackupDir\files_$timestamp.zip"

if (!(Test-Path $BackupDir)) { New-Item -ItemType Directory -Path $BackupDir -Force }

Write-Host "[Neilos Backup] Starting backup at $(Get-Date)..."

# Database dump
Write-Host "[Neilos Backup] Dumping database..."
if ($DBPass) {
    $env:MYSQL_PWD = $DBPass
}
$conn = "mysql -u $DBUser --host=127.0.0.1 $DBName"
if ($DBPass) {
    & "C:\xampp\mysql\bin\mysqldump" --user="$DBUser" --password="$DBPass" --host=127.0.0.1 --single-transaction --routines --triggers "$DBName" | Out-File -FilePath $dbFile -Encoding utf8
} else {
    & "C:\xampp\mysql\bin\mysqldump" --user="$DBUser" --host=127.0.0.1 --single-transaction --routines --triggers "$DBName" | Out-File -FilePath $dbFile -Encoding utf8
}

if ($LASTEXITCODE -eq 0) {
    Write-Host "[Neilos Backup] Database dumped: $dbFile ($((Get-Item $dbFile).Length / 1KB) KB)"
} else {
    Write-Host "[Neilos Backup] ERROR: Database dump failed!"
    exit 1
}

# File archive (uploads, config, etc.)
Write-Host "[Neilos Backup] Archiving project files..."
$compress = @{
    Path         = @(
        "$ProjectDir\public\uploads",
        "$ProjectDir\app",
        "$ProjectDir\database",
        "$ProjectDir\.env.example"
    )
    CompressionLevel = "Optimal"
    DestinationPath  = $zipFile
}
Compress-Archive @compress -Force

if (Test-Path $zipFile) {
    Write-Host "[Neilos Backup] Files archived: $zipFile ($((Get-Item $zipFile).Length / 1KB) KB)"
} else {
    Write-Host "[Neilos Backup] ERROR: File archive failed!"
    exit 1
}

# Retain only last 7 backups
$oldDbs = Get-ChildItem "$BackupDir\db_*.sql" | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-7) }
$oldZips = Get-ChildItem "$BackupDir\files_*.zip" | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-7) }
$oldDbs | ForEach-Object { Remove-Item $_.FullName -Force; Write-Host "[Neilos Backup] Removed old backup: $($_.Name)" }
$oldZips | ForEach-Object { Remove-Item $_.FullName -Force; Write-Host "[Neilos Backup] Removed old backup: $($_.Name)" }

Write-Host "[Neilos Backup] Completed successfully at $(Get-Date)."
