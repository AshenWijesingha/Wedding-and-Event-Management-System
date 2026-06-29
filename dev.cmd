@echo off
setlocal enabledelayedexpansion
REM ===========================================================================
REM  dev.cmd - EventPro developer/evaluation helper (offline, git-free)
REM
REM    dev doctor              find changed/deleted code + verify all CRUD flows
REM    dev run doctor          (same - "run" is optional)
REM    dev doctor --no-tests   fast: integrity + offline checks only
REM    dev baseline            capture the current source as the known-good baseline
REM    dev restore <path>      restore one file from the baseline
REM    dev restore --all       restore everything that changed
REM
REM  Wraps the `dev:*` artisan commands. If the app is so broken it cannot boot,
REM  `dev doctor` falls back to the standalone integrity checker so you can still
REM  see exactly which files were deleted/modified.
REM ===========================================================================
set "ROOT=%~dp0"

REM --- locate PHP (PATH, then Herd-lite) ---
set "PHP=php"
where php >nul 2>&1
if errorlevel 1 set "PHP=%USERPROFILE%\.config\herd-lite\bin\php.exe"

REM --- parse: allow an optional "run" verb (dev run doctor == dev doctor) ---
set "CMD=%~1"
if /i "%CMD%"=="run" (
    set "CMD=%~2"
    set "FWD=%3 %4 %5 %6 %7 %8 %9"
) else (
    set "FWD=%2 %3 %4 %5 %6 %7 %8 %9"
)

if "%CMD%"=="" goto :help
if /i "%CMD%"=="help" goto :help
if /i "%CMD%"=="doctor"   goto :doctor
if /i "%CMD%"=="baseline" goto :baseline
if /i "%CMD%"=="snapshot" goto :baseline
if /i "%CMD%"=="restore"  goto :restore

echo Unknown command: %CMD%
goto :help

:doctor
"%PHP%" "%ROOT%artisan" --version >nul 2>&1
if errorlevel 1 (
    echo [warn] The app cannot boot - running the standalone integrity check instead.
    echo        ^(Functional tests skipped until the app boots again.^)
    "%PHP%" "%ROOT%tools\demo\integrity.php" --check
) else (
    "%PHP%" "%ROOT%artisan" dev:doctor %FWD%
)
goto :end

:baseline
"%PHP%" "%ROOT%artisan" --version >nul 2>&1
if errorlevel 1 (
    "%PHP%" "%ROOT%tools\demo\integrity.php" --snapshot
) else (
    "%PHP%" "%ROOT%artisan" dev:baseline %FWD%
)
goto :end

:restore
"%PHP%" "%ROOT%artisan" --version >nul 2>&1
if errorlevel 1 (
    echo [warn] App cannot boot - restoring via the standalone tool.
    if /i "%~2"=="--all" (
        "%PHP%" "%ROOT%tools\demo\integrity.php" --restore-all
    ) else (
        "%PHP%" "%ROOT%tools\demo\integrity.php" --restore %2
    )
) else (
    "%PHP%" "%ROOT%artisan" dev:restore %FWD%
)
goto :end

:help
echo.
echo  EventPro dev helper (offline, git-free)
echo  ---------------------------------------
echo    dev doctor            find changed/deleted code + verify all CRUD flows
echo    dev run doctor        same as above
echo    dev doctor --no-tests fast integrity + offline checks only
echo    dev baseline          capture current source as the known-good baseline
echo    dev restore ^<path^>    restore one file from the baseline
echo    dev restore --all     restore everything that changed
echo.
goto :end

:end
endlocal
