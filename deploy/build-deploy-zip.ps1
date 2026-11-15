# Builds DERMAS.zip for cPanel upload with Unix permission bits embedded
# (so extract usually does NOT need fix-permissions URL).
$ErrorActionPreference = "Stop"
$root = Split-Path $PSScriptRoot -Parent
if (-not (Test-Path "$root\artisan")) {
    throw "Could not find Laravel project root (artisan)."
}

$stamp = Get-Date -Format "yyyyMMdd-HHmm"
$zipPath = Join-Path (Split-Path $root -Parent) "DERMAS.zip"
$staging = Join-Path $env:TEMP "DERMAS-staging-$stamp"
$pyZip = Join-Path $PSScriptRoot "make-unix-zip.py"

Write-Host "Project: $root"
Write-Host "Staging: $staging"
Write-Host "Output:  $zipPath"

if (Test-Path $staging) { Remove-Item $staging -Recurse -Force }
New-Item -ItemType Directory -Path $staging | Out-Null

$excludeDirs = @(
    ".git", "node_modules", ".idea", ".vscode", ".nova", ".fleet", ".zed",
    ".phpunit.cache", "tests"
)
$excludeFiles = @(
    ".env", ".env.backup", ".env.production", ".env.zip",
    ".phpunit.result.cache", "Thumbs.db", ".DS_Store"
)

$rcArgs = @($root, $staging, "/E", "/NFL", "/NDL", "/NJH", "/NJS", "/NC", "/NS", "/NP")
foreach ($d in $excludeDirs) { $rcArgs += "/XD"; $rcArgs += $d }
foreach ($f in $excludeFiles) { $rcArgs += "/XF"; $rcArgs += $f }
$rcArgs += "/XF"; $rcArgs += "DERMAS.zip"
$rcArgs += "/XF"; $rcArgs += "dermas-deploy-*.zip"

& robocopy @rcArgs | Out-Null
# Robocopy exit codes 0-7 are success-ish
if ($LASTEXITCODE -gt 7) { throw "robocopy failed with exit code $LASTEXITCODE" }

Copy-Item (Join-Path $PSScriptRoot "fix-permissions.sh") (Join-Path $staging "fix-permissions.sh") -Force

@"
DERMAS — cPanel deploy package
==============================

1. Upload this zip to your hosting account.
2. Extract into your site folder (document root should point to public/).
3. Copy .env from your backup (not included in this zip).
   MySQL on cPanel — use DB_HOST=localhost (NOT 127.0.0.1).
   User/database names must include the cPanel prefix (e.g. dermasap_er22).
4. Permissions are already set INSIDE this zip (755/644, storage 775).
   After extract you normally do NOT need fix-permissions URL.
   Only if write errors appear, open:
     https://YOUR-DOMAIN/fix-permissions.php?key=YOUR_KEY
5. Test DB: https://YOUR-DOMAIN/deploy/YOUR_KEY/db-test
6. If needed:
     php artisan config:clear
     php artisan cache:clear
     php artisan view:clear
     php artisan migrate --force

Deploy URL utilities (set DEPLOY_KEY in .env):
  /deploy/YOUR_KEY/db-test
  /deploy/YOUR_KEY/migrate
  /deploy/YOUR_KEY/seed-defaults
  /deploy/YOUR_KEY/cache-clear

"@ | Set-Content (Join-Path $staging "DEPLOY-README.txt") -Encoding UTF8

if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

Write-Host "Packing with Unix permission attributes..."
& python $pyZip $staging $zipPath
if ($LASTEXITCODE -ne 0) { throw "make-unix-zip.py failed with exit code $LASTEXITCODE" }

Remove-Item $staging -Recurse -Force

$sizeMb = [math]::Round((Get-Item $zipPath).Length / 1MB, 1)
Write-Host ""
Write-Host "Created: $zipPath ($sizeMb MB)"
Write-Host "Upload + extract - permission URL normally NOT needed."
