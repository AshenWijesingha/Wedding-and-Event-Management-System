@echo off
setlocal enabledelayedexpansion
REM ===========================================================================
REM  EventPro - backup.cmd
REM  Creates a COMPLETE offline restore point of the whole project, including the
REM  git-ignored artifacts that the 'demo-baseline' tag does NOT contain:
REM  vendor\, node_modules\, public\build\, .env  (plus .git and the whole tree).
REM
REM  Output: %USERPROFILE%\EventPro-Backup\eventpro-demo-<timestamp>.zip
REM  Copy that .zip to a USB stick as well - it is your ultimate safety net.
REM ===========================================================================
title EventPro - full backup

set "ROOT=%~dp0..\.."
pushd "%ROOT%"
set "ROOT=%CD%"

for %%I in ("%ROOT%") do set "PROJ=%%~nxI"

set "DEST=%USERPROFILE%\EventPro-Backup"
if not exist "%DEST%" mkdir "%DEST%"

REM timestamp via PowerShell (locale-independent)
for /f %%t in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd-HHmmss"') do set "TS=%%t"
set "ZIP=%DEST%\eventpro-demo-%TS%.zip"

echo.
echo  ===================================================
echo    EventPro full backup
echo  ===================================================
echo    Source : %ROOT%
echo    Output : %ZIP%
echo.
echo    Archiving the entire project (vendor + node_modules included).
echo    This can take a minute or two - please wait...
echo.

REM Archive the project folder from its parent so paths are relative.
pushd "%ROOT%\.."
tar -c -f "%ZIP%" --format=zip "%PROJ%"
set "RC=%ERRORLEVEL%"
popd

if not "%RC%"=="0" (
    echo  [ERROR] Backup failed (tar exit %RC%).
    popd & pause & exit /b 1
)

for %%S in ("%ZIP%") do set "BYTES=%%~zS"
set /a MB=%BYTES% / 1048576
echo  [OK] Backup created (%MB% MB):
echo       %ZIP%
echo.
echo  To restore everything later: extract the .zip over a fresh folder, or
echo  copy the needed folder (e.g. vendor\) out of it.
echo.
popd
pause
