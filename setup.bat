@echo off
setlocal EnableDelayedExpansion
chcp 65001 >nul 2>&1

:: ============================================================================
::  EventPro - Wedding & Event Management System
::  Windows Auto-Setup Script v1.0
::
::  REQUIREMENTS : Windows 10/11 with winget (App Installer from Microsoft Store)
::  RUN AS       : Administrator  (right-click -> "Run as Administrator")
::
::  What this installs and configures:
::    1. PHP 8.2+       via winget
::    2. PHP extensions  (pdo_mysql, gd, zip, bcmath, openssl, curl, intl ...)
::    3. Composer        via winget, fallback curl installer
::    4. Node.js LTS     via winget
::    5. MariaDB 10.11+  via winget  (MySQL-compatible, easier Windows setup)
::    6. Memurai/Redis   via winget  (optional - falls back to file/database)
::    7. .env            reconfigured for local Windows dev
::    8. composer install + php artisan key:generate
::    9. npm install + npm run build
::   10. php artisan migrate + db:seed + storage:link
::   11. start.bat / stop.bat helper scripts
:: ============================================================================

title EventPro - Windows Setup

cls
echo.
echo  ====================================================================
echo    EventPro - Wedding and Event Management System
echo    Windows Auto-Setup Script v1.0
echo  ====================================================================
echo.
echo  This will install PHP 8.2, Composer, Node.js, MariaDB, and Redis,
echo  then configure and launch the application automatically.
echo  Runtime: 10-20 min on first run (downloading packages).
echo.
echo  ====================================================================
echo.

:: ─── Administrator check ────────────────────────────────────────────────────
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo  [ERROR] Administrator privileges required.
    echo.
    echo          Right-click "setup.bat" and choose "Run as Administrator".
    echo.
    pause
    exit /b 1
)
echo  [OK] Running as Administrator
echo.

:: ─── Variables ──────────────────────────────────────────────────────────────
set "PROJECT_DIR=%~dp0"
if "%PROJECT_DIR:~-1%"=="\" set "PROJECT_DIR=%PROJECT_DIR:~0,-1%"
cd /d "%PROJECT_DIR%"

set "DB_NAME=eventpro"
set "DB_HOST=127.0.0.1"
set "DB_PORT=3306"
set "DB_USER=root"
set "DB_PASS="
set "APP_PORT=8000"
set "USE_REDIS=0"
set "LOGFILE=%PROJECT_DIR%\setup.log"

echo EventPro Setup Log  >>  "%LOGFILE%" 2>nul
echo Started: %DATE% %TIME% >> "%LOGFILE%" 2>nul

echo  Project : %PROJECT_DIR%
echo  Database: %DB_NAME%  at  %DB_HOST%:%DB_PORT%
echo  App URL : http://localhost:%APP_PORT%
echo  Log     : %LOGFILE%
echo.

:: ============================================================================
:: [1/9] Windows Package Manager (winget)
:: ============================================================================
echo  ── [1/9] Windows Package Manager ──────────────────────────────────────────
winget --version >nul 2>&1
if %errorlevel% neq 0 (
    echo  [WARN] winget not found. Registering App Installer via PowerShell...
    powershell -NoProfile -Command "Add-AppxPackage -RegisterByFamilyName -MainPackage Microsoft.WindowsAppRuntime.1.4_8wekyb3d8bbwe" >nul 2>&1
    winget --version >nul 2>&1
    if %errorlevel% neq 0 (
        echo.
        echo  [ERROR] winget unavailable.
        echo          Install "App Installer" from the Microsoft Store, restart, then re-run.
        echo.
        pause
        exit /b 1
    )
)
for /f "delims=" %%v in ('winget --version 2^>nul') do echo  [OK] winget %%v
echo.

call :RefreshPath

:: ============================================================================
:: [2/9] PHP 8.2+
:: ============================================================================
echo  ── [2/9] PHP 8.2+ ──────────────────────────────────────────────────────────
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo  [INFO] PHP not found. Installing PHP 8.2 via winget...
    winget install --id PHP.PHP.8.2 -e --silent --accept-package-agreements --accept-source-agreements >> "%LOGFILE%" 2>&1
    call :RefreshPath
    timeout /t 5 /nobreak >nul
    call :RefreshPath
    php -v >nul 2>&1
    if %errorlevel% neq 0 (
        echo  [WARN] winget returned non-zero. Retrying interactively...
        winget install --id PHP.PHP.8.2 -e --accept-package-agreements --accept-source-agreements
        call :RefreshPath
        timeout /t 3 /nobreak >nul
        call :RefreshPath
    )
)

php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo  [ERROR] PHP not in PATH after install.
    echo          Manual fix: Download PHP 8.2 Thread Safe x64 from https://windows.php.net/download/
    echo          Extract to C:\PHP and add C:\PHP to your System PATH, then re-run.
    echo.
    pause
    exit /b 1
)
for /f "tokens=1-3" %%a in ('php -v 2^>nul ^| findstr /i "^PHP "') do (
    if /i "%%a"=="PHP" echo  [OK] PHP %%b
)
echo.

:: ============================================================================
:: [3/9] PHP Extension Configuration
:: ============================================================================
echo  ── [3/9] PHP Extension Configuration ──────────────────────────────────────
call :ConfigurePHP
echo.

:: ============================================================================
:: [4/9] Composer
:: ============================================================================
echo  ── [4/9] Composer ──────────────────────────────────────────────────────────
composer --version >nul 2>&1
if %errorlevel% neq 0 (
    echo  [INFO] Installing Composer via winget...
    winget install --id Composer.Composer -e --silent --accept-package-agreements --accept-source-agreements >> "%LOGFILE%" 2>&1
    call :RefreshPath
    timeout /t 3 /nobreak >nul
    call :RefreshPath
    composer --version >nul 2>&1
    if %errorlevel% neq 0 (
        echo  [INFO] Fallback: downloading Composer installer via curl...
        curl -fsSL "https://getcomposer.org/installer" -o "%TEMP%\composer-setup.php"
        if exist "%TEMP%\composer-setup.php" (
            php "%TEMP%\composer-setup.php" --install-dir="%WINDIR%" --filename=composer.phar >> "%LOGFILE%" 2>&1
            echo @php "%WINDIR%\composer.phar" %%* > "%WINDIR%\composer.bat"
            call :RefreshPath
        )
    )
)
composer --version >nul 2>&1
if %errorlevel% neq 0 (
    echo  [ERROR] Composer unavailable. Install from https://getcomposer.org/download/
    pause
    exit /b 1
)
for /f "tokens=1-3" %%a in ('composer --version 2^>nul') do echo  [OK] %%a %%b %%c
echo.

:: ============================================================================
:: [5/9] Node.js LTS
:: ============================================================================
echo  ── [5/9] Node.js LTS ───────────────────────────────────────────────────────
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo  [INFO] Installing Node.js LTS via winget...
    winget install --id OpenJS.NodeJS.LTS -e --silent --accept-package-agreements --accept-source-agreements >> "%LOGFILE%" 2>&1
    call :RefreshPath
    timeout /t 5 /nobreak >nul
    call :RefreshPath
)
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo  [ERROR] Node.js unavailable. Install from https://nodejs.org/
    pause
    exit /b 1
)
for /f "delims=" %%v in ('node --version 2^>nul') do echo  [OK] Node.js %%v
for /f "delims=" %%v in ('npm --version 2^>nul') do echo  [OK] npm v%%v
echo.

:: ============================================================================
:: [6/9] MariaDB (MySQL-compatible database)
:: ============================================================================
echo  ── [6/9] MariaDB (MySQL-compatible) ───────────────────────────────────────
mysql --version >nul 2>&1
if %errorlevel% neq 0 (
    echo  [INFO] Installing MariaDB via winget...
    winget install --id MariaDB.Server -e --silent --accept-package-agreements --accept-source-agreements >> "%LOGFILE%" 2>&1
    if %errorlevel% neq 0 (
        echo  [INFO] Fallback: trying Oracle MySQL...
        winget install --id Oracle.MySQL -e --accept-package-agreements --accept-source-agreements >> "%LOGFILE%" 2>&1
    )
    call :RefreshPath
    timeout /t 8 /nobreak >nul
    call :RefreshPath
    echo  [OK] Database server installed
) else (
    for /f "tokens=1-5" %%a in ('mysql --version 2^>nul') do echo  [OK] %%a %%b %%c %%d %%e
)

net start MariaDB >nul 2>&1
net start MySQL80 >nul 2>&1
net start MySQL   >nul 2>&1
call :SetupDatabase
echo.

:: ============================================================================
:: [7/9] Redis / Memurai (optional)
:: ============================================================================
echo  ── [7/9] Redis Cache (Memurai) ─────────────────────────────────────────────
redis-cli ping >nul 2>&1
if %errorlevel% equ 0 (
    echo  [OK] Redis already running on 127.0.0.1:6379
    set "USE_REDIS=1"
) else (
    echo  [INFO] Installing Memurai (Redis for Windows) via winget...
    winget install --id Memurai.Memurai -e --silent --accept-package-agreements --accept-source-agreements >> "%LOGFILE%" 2>&1
    call :RefreshPath
    net start Memurai >nul 2>&1
    timeout /t 3 /nobreak >nul
    redis-cli ping >nul 2>&1
    if %errorlevel% equ 0 (
        echo  [OK] Memurai running on 127.0.0.1:6379
        set "USE_REDIS=1"
    ) else (
        echo  [INFO] Redis unavailable — using file/database fallback drivers
        echo         (App works normally; sessions/cache use filesystem)
        set "USE_REDIS=0"
    )
)
echo.

:: ============================================================================
:: [8/9] Configure Environment + Install Project Dependencies
:: ============================================================================
echo  ── [8/9] Project Configuration ────────────────────────────────────────────
cd /d "%PROJECT_DIR%"
echo.

:: 8.1 — .env file
echo  [8.1] Configuring .env...
if not exist ".env" (
    if not exist ".env.example" (
        echo  [ERROR] .env.example not found in: %PROJECT_DIR%
        pause
        exit /b 1
    )
    copy ".env.example" ".env" >nul
    echo  [OK] Created .env from .env.example
) else (
    echo  [INFO] .env already exists — updating values
)

:: Write a temp PS1 to patch .env (avoids cmd special-char escaping)
set "PS_ENV=%TEMP%\_ep_env.ps1"
echo $e = [System.IO.Path]::GetFullPath('%PROJECT_DIR%\.env')                            > "%PS_ENV%"
echo $c = [System.IO.File]::ReadAllText($e)                                              >> "%PS_ENV%"
echo $c = $c -replace 'DB_HOST=mysql',     'DB_HOST=127.0.0.1'                          >> "%PS_ENV%"
echo $c = $c -replace 'DB_HOST=localhost', 'DB_HOST=127.0.0.1'                          >> "%PS_ENV%"
echo $c = $c -replace 'REDIS_HOST=redis',  'REDIS_HOST=127.0.0.1'                       >> "%PS_ENV%"
echo $c = $c -replace 'MAIL_HOST=mailhog', 'MAIL_HOST=127.0.0.1'                        >> "%PS_ENV%"
echo $c = $c -replace 'DB_DATABASE=.*', 'DB_DATABASE=%DB_NAME%'                         >> "%PS_ENV%"
echo $c = $c -replace 'DB_USERNAME=.*', 'DB_USERNAME=%DB_USER%'                         >> "%PS_ENV%"
echo $c = $c -replace 'DB_PASSWORD=.*', 'DB_PASSWORD=%DB_PASS%'                         >> "%PS_ENV%"
echo if ('%USE_REDIS%' -eq '0') {                                                        >> "%PS_ENV%"
echo     $c = $c -replace 'SESSION_DRIVER=redis',   'SESSION_DRIVER=file'               >> "%PS_ENV%"
echo     $c = $c -replace 'CACHE_STORE=redis',      'CACHE_STORE=file'                  >> "%PS_ENV%"
echo     $c = $c -replace 'QUEUE_CONNECTION=redis', 'QUEUE_CONNECTION=database'         >> "%PS_ENV%"
echo }                                                                                    >> "%PS_ENV%"
echo [System.IO.File]::WriteAllText($e, $c)                                              >> "%PS_ENV%"
echo Write-Host '  [OK] .env configured for local Windows development'                   >> "%PS_ENV%"
powershell -NoProfile -ExecutionPolicy Bypass -File "%PS_ENV%"
del "%PS_ENV%" >nul 2>&1
echo.

:: 8.2 — PHP dependencies
echo  [8.2] Installing PHP dependencies via Composer...
echo        (first run may take several minutes — downloading packages)
composer install --no-interaction --prefer-dist --optimize-autoloader >> "%LOGFILE%" 2>&1
if %errorlevel% neq 0 (
    echo  [WARN] Retrying without --optimize-autoloader flag...
    composer install --no-interaction --prefer-dist >> "%LOGFILE%" 2>&1
    if %errorlevel% neq 0 (
        echo.
        echo  [ERROR] Composer install failed. Check: %LOGFILE%
        echo          Common causes:
        echo            - PHP extensions missing (pdo_mysql, gd, zip)
        echo            - Network/firewall blocking Packagist
        echo.
        pause
        exit /b 1
    )
)
echo  [OK] PHP dependencies installed
echo.

:: 8.3 — Laravel application key
echo  [8.3] Generating Laravel application key...
php artisan key:generate --force --ansi >> "%LOGFILE%" 2>&1
echo  [OK] Application key generated
echo.

:: 8.4 — Node.js dependencies
echo  [8.4] Installing Node.js dependencies via npm...
call npm install >> "%LOGFILE%" 2>&1
if %errorlevel% neq 0 (
    echo.
    echo  [ERROR] npm install failed. Check: %LOGFILE%
    pause
    exit /b 1
)
echo  [OK] Node.js dependencies installed
echo.

:: 8.5 — Build frontend assets
echo  [8.5] Building frontend assets (Vue 3 + Vite)...
call npm run build >> "%LOGFILE%" 2>&1
if %errorlevel% neq 0 (
    echo.
    echo  [ERROR] npm build failed. Check: %LOGFILE%
    pause
    exit /b 1
)
echo  [OK] Frontend assets compiled to public/build/
echo.

:: 8.6 — Database migrations
echo  [8.6] Running database migrations...
php artisan migrate --force --ansi >> "%LOGFILE%" 2>&1
if %errorlevel% neq 0 (
    echo  [WARN] Migrations had errors. Verify .env DB settings, then run:
    echo           php artisan migrate --force
) else (
    echo  [OK] Migrations complete
)
echo.

:: 8.7 — Database seeding
echo  [8.7] Seeding database (plans, roles, demo data)...
php artisan db:seed --force --ansi >> "%LOGFILE%" 2>&1
if %errorlevel% neq 0 (
    echo  [WARN] Seeding had issues. Check: %LOGFILE%
) else (
    echo  [OK] Database seeded
)
echo.

:: 8.8 — Storage symlink
echo  [8.8] Creating public/storage symlink...
php artisan storage:link --force >> "%LOGFILE%" 2>&1
echo  [OK] Storage symlink created
echo.

:: 8.9 — Directory permissions
echo  [8.9] Setting storage and cache write permissions...
icacls "%PROJECT_DIR%\storage"         /grant *S-1-1-0:(OI)(CI)F /T /Q >nul 2>&1
icacls "%PROJECT_DIR%\bootstrap\cache" /grant *S-1-1-0:(OI)(CI)F /T /Q >nul 2>&1
echo  [OK] Write permissions granted on storage/ and bootstrap/cache/
echo.

:: 8.10 — Clear all caches
echo  [8.10] Clearing application caches...
php artisan config:clear >> "%LOGFILE%" 2>&1
php artisan cache:clear  >> "%LOGFILE%" 2>&1
php artisan route:clear  >> "%LOGFILE%" 2>&1
php artisan view:clear   >> "%LOGFILE%" 2>&1
echo  [OK] All caches cleared
echo.

:: ============================================================================
:: [9/9] Create helper scripts (start.bat / stop.bat) via PowerShell
:: ============================================================================
echo  ── [9/9] Creating Helper Scripts ───────────────────────────────────────────

set "PS_SCRIPTS=%TEMP%\_ep_mkscripts.ps1"

echo $proj = '%PROJECT_DIR%'                                                              > "%PS_SCRIPTS%"
echo $port = '%APP_PORT%'                                                                 >> "%PS_SCRIPTS%"
echo.                                                                                     >> "%PS_SCRIPTS%"
echo # ── start.bat ──────────────────────────────────────────────────                   >> "%PS_SCRIPTS%"
echo $s = New-Object System.Collections.Generic.List[string]                             >> "%PS_SCRIPTS%"
echo $s.Add('@echo off')                                                                  >> "%PS_SCRIPTS%"
echo $s.Add('title EventPro - Dev Server')                                               >> "%PS_SCRIPTS%"
echo $s.Add('cls')                                                                        >> "%PS_SCRIPTS%"
echo $s.Add('echo.')                                                                      >> "%PS_SCRIPTS%"
echo $s.Add('echo  ================================================================')    >> "%PS_SCRIPTS%"
echo $s.Add('echo    EventPro - Wedding and Event Management System')                    >> "%PS_SCRIPTS%"
echo $s.Add('echo    Local Development Server')                                          >> "%PS_SCRIPTS%"
echo $s.Add('echo  ================================================================')    >> "%PS_SCRIPTS%"
echo $s.Add('echo.')                                                                      >> "%PS_SCRIPTS%"
echo $s.Add('echo  Starting services...')                                                 >> "%PS_SCRIPTS%"
echo $s.Add('net start MariaDB ^>nul 2^>^&1')                                            >> "%PS_SCRIPTS%"
echo $s.Add('net start MySQL80 ^>nul 2^>^&1')                                           >> "%PS_SCRIPTS%"
echo $s.Add('net start MySQL   ^>nul 2^>^&1')                                           >> "%PS_SCRIPTS%"
echo $s.Add('net start Memurai ^>nul 2^>^&1')                                           >> "%PS_SCRIPTS%"
echo $s.Add(('cd /d "' + $proj + '"'))                                                   >> "%PS_SCRIPTS%"
echo $s.Add(('start "EventPro Vite" /D "' + $proj + '" cmd /k npm run dev'))            >> "%PS_SCRIPTS%"
echo $s.Add('timeout /t 2 /nobreak ^>nul')                                               >> "%PS_SCRIPTS%"
echo $s.Add('echo  [OK] Vite dev server open in a new window')                          >> "%PS_SCRIPTS%"
echo $s.Add(('echo  [OK] Laravel: http://localhost:' + $port))                          >> "%PS_SCRIPTS%"
echo $s.Add('echo.')                                                                      >> "%PS_SCRIPTS%"
echo $s.Add('echo  Press Ctrl+C to stop the Laravel server')                            >> "%PS_SCRIPTS%"
echo $s.Add('echo.')                                                                      >> "%PS_SCRIPTS%"
echo $s.Add(('php artisan serve --host=127.0.0.1 --port=' + $port))                     >> "%PS_SCRIPTS%"
echo $s.Add('pause')                                                                      >> "%PS_SCRIPTS%"
echo $s | Set-Content (Join-Path $proj 'start.bat') -Encoding ASCII                     >> "%PS_SCRIPTS%"
echo Write-Host '  [OK] start.bat created'                                               >> "%PS_SCRIPTS%"
echo.                                                                                     >> "%PS_SCRIPTS%"
echo # ── stop.bat ───────────────────────────────────────────────────                   >> "%PS_SCRIPTS%"
echo $t = New-Object System.Collections.Generic.List[string]                             >> "%PS_SCRIPTS%"
echo $t.Add('@echo off')                                                                  >> "%PS_SCRIPTS%"
echo $t.Add('title EventPro - Stop')                                                     >> "%PS_SCRIPTS%"
echo $t.Add('echo.')                                                                      >> "%PS_SCRIPTS%"
echo $t.Add('echo  Stopping EventPro processes...')                                      >> "%PS_SCRIPTS%"
echo $t.Add('taskkill /f /im php.exe  ^>nul 2^>^&1')                                    >> "%PS_SCRIPTS%"
echo $t.Add('echo  [OK] php.exe stopped')                                                >> "%PS_SCRIPTS%"
echo $t.Add('taskkill /f /im node.exe ^>nul 2^>^&1')                                   >> "%PS_SCRIPTS%"
echo $t.Add('echo  [OK] node.exe stopped')                                               >> "%PS_SCRIPTS%"
echo $t.Add('echo.')                                                                      >> "%PS_SCRIPTS%"
echo $t.Add('echo  Done. Run start.bat to restart.')                                     >> "%PS_SCRIPTS%"
echo $t.Add('pause')                                                                      >> "%PS_SCRIPTS%"
echo $t | Set-Content (Join-Path $proj 'stop.bat') -Encoding ASCII                      >> "%PS_SCRIPTS%"
echo Write-Host '  [OK] stop.bat created'                                                >> "%PS_SCRIPTS%"

powershell -NoProfile -ExecutionPolicy Bypass -File "%PS_SCRIPTS%"
del "%PS_SCRIPTS%" >nul 2>&1
echo.

:: ============================================================================
:: DONE
:: ============================================================================
echo.
echo  ====================================================================
echo    SETUP COMPLETE!
echo  ====================================================================
echo.
echo    Database  :  %DB_NAME%  at  %DB_HOST%:%DB_PORT%  (user: %DB_USER%)
if "%USE_REDIS%"=="1" (
    echo    Redis     :  127.0.0.1:6379  via Memurai
) else (
    echo    Sessions  :  file    Cache: file    Queue: database
)
echo    App URL   :  http://localhost:%APP_PORT%
echo.
echo    ─── TO START ────────────────────────────────────────────────
echo     Double-click  start.bat         launches everything
echo     Or manually:
echo       php artisan serve
echo       npm run dev   (separate terminal)
echo.
echo    ─── TO STOP  ────────────────────────────────────────────────
echo     Double-click  stop.bat          kills php + node
echo.
echo    Setup log: %LOGFILE%
echo  ====================================================================
echo.
pause
exit /b 0

:: ============================================================================
:: SUBROUTINES
:: ============================================================================

:RefreshPath
:: Use PowerShell to read current Machine+User PATH from registry
powershell -NoProfile -Command ^
    "Write-Output ([Environment]::GetEnvironmentVariable('PATH','Machine') + ';' + [Environment]::GetEnvironmentVariable('PATH','User'))" ^
    > "%TEMP%\_ep_path.txt" 2>nul
if exist "%TEMP%\_ep_path.txt" (
    set /p _NEWPATH=<"%TEMP%\_ep_path.txt"
    if defined _NEWPATH set "PATH=!_NEWPATH!;%PATH%"
    del "%TEMP%\_ep_path.txt" >nul 2>&1
)
exit /b 0

:: ─────────────────────────────────────────────────────────────────────────────
:ConfigurePHP
:: Locate PHP directory and enable required extensions in php.ini
set "_PHP_EXE="
set "_PHP_DIR="
for /f "delims=" %%p in ('where php 2^>nul') do (
    if not defined _PHP_EXE (
        set "_PHP_EXE=%%p"
        for %%f in ("%%p") do set "_PHP_DIR=%%~dpf"
    )
)
:: Try fallback locations if where php failed
if not defined _PHP_DIR (
    for %%d in (
        "C:\Program Files\PHP\v8.2" "C:\Program Files\PHP\v8.3"
        "C:\Program Files\PHP" "C:\PHP" "C:\php82" "C:\php"
    ) do (
        if exist "%%~d\php.exe" if not defined _PHP_DIR set "_PHP_DIR=%%~d\"
    )
)
if not defined _PHP_DIR (
    echo  [WARN] PHP directory not found — skipping php.ini configuration.
    echo         Ensure these extensions are enabled manually: pdo_mysql gd zip openssl
    exit /b 0
)
:: Remove trailing backslash
if "!_PHP_DIR:~-1!"=="\" set "_PHP_DIR=!_PHP_DIR:~0,-1!"
echo  [INFO] PHP directory: !_PHP_DIR!

set "_PHP_INI=!_PHP_DIR!\php.ini"
if not exist "!_PHP_INI!" (
    if exist "!_PHP_DIR!\php.ini-development" (
        copy /Y "!_PHP_DIR!\php.ini-development" "!_PHP_INI!" >nul
        echo  [OK] php.ini created from php.ini-development
    ) else (
        echo  [WARN] No php.ini found in !_PHP_DIR! — skipping extension config.
        exit /b 0
    )
)

:: Write and execute a PowerShell script that uncomments required extensions
set "_PS_PHP=%TEMP%\_ep_phpini.ps1"
echo $ini   = '!_PHP_INI:\=\\!'                                                                  > "%_PS_PHP%"
echo $lines = Get-Content $ini -Encoding UTF8                                                    >> "%_PS_PHP%"
echo $exts  = @('bcmath','curl','fileinfo','gd','intl','mbstring','openssl','pdo_mysql','sodium','xml','zip') >> "%_PS_PHP%"
echo for ($i = 0; $i -lt $lines.Count; $i++) {                                                  >> "%_PS_PHP%"
echo     $line = $lines[$i]                                                                      >> "%_PS_PHP%"
echo     foreach ($ext in $exts) {                                                               >> "%_PS_PHP%"
echo         if ($line -match "^;extension=$ext\s*$") { $lines[$i] = "extension=$ext"; break }  >> "%_PS_PHP%"
echo     }                                                                                        >> "%_PS_PHP%"
echo     if ($line -match "^;\s*extension_dir\s*=") {                                           >> "%_PS_PHP%"
echo         $lines[$i] = 'extension_dir = "ext"'                                               >> "%_PS_PHP%"
echo     }                                                                                        >> "%_PS_PHP%"
echo }                                                                                            >> "%_PS_PHP%"
echo Set-Content $ini $lines -Encoding UTF8                                                      >> "%_PS_PHP%"
echo Write-Host '  [OK] PHP extensions enabled: bcmath curl fileinfo gd intl mbstring openssl pdo_mysql xml zip' >> "%_PS_PHP%"

powershell -NoProfile -ExecutionPolicy Bypass -File "%_PS_PHP%"
del "%_PS_PHP%" >nul 2>&1
exit /b 0

:: ─────────────────────────────────────────────────────────────────────────────
:SetupDatabase
echo  [INFO] Ensuring database "%DB_NAME%" exists...
timeout /t 3 /nobreak >nul

:: Try root with empty password (MariaDB fresh-install default on Windows)
mysql -h%DB_HOST% -P%DB_PORT% -u%DB_USER% --connect-timeout=10 -e "SELECT 1;" >nul 2>&1
if %errorlevel% equ 0 (
    mysql -h%DB_HOST% -P%DB_PORT% -u%DB_USER% -e "CREATE DATABASE IF NOT EXISTS `%DB_NAME%` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >nul 2>&1
    if %errorlevel% equ 0 (
        echo  [OK] Database "%DB_NAME%" ready
    ) else (
        call :DbManualNote
    )
) else (
    echo  [WARN] Cannot connect to database with empty root password.
    call :DbManualNote
)
exit /b 0

:DbManualNote
echo.
echo  ── Manual database step needed ──────────────────────────────────
echo     Open MySQL Workbench or a terminal, connect, then run:
echo.
echo       CREATE DATABASE %DB_NAME%
echo         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
echo.
echo     Update .env:  DB_PASSWORD=^<your root password^>
echo     Then run:     php artisan migrate --force
echo  ─────────────────────────────────────────────────────────────────
echo.
exit /b 0
