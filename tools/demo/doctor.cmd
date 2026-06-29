@echo off
setlocal enabledelayedexpansion
REM ===========================================================================
REM  EventPro - doctor.cmd
REM  Diagnoses whether the project is in a runnable state. Run this FIRST when an
REM  evaluator has changed/deleted code: it tells you what is broken and where.
REM ===========================================================================
title EventPro - doctor

REM --- locate project root (this script lives in tools\demo\) ---------------
set "ROOT=%~dp0..\.."
pushd "%ROOT%"
set "ROOT=%CD%"

REM --- locate PHP (PATH, then Herd-lite fallback) ---------------------------
set "PHP=php"
where php >nul 2>&1
if errorlevel 1 set "PHP=%USERPROFILE%\.config\herd-lite\bin\php.exe"
if not exist "%PHP%" if "%PHP%"=="%USERPROFILE%\.config\herd-lite\bin\php.exe" (
    echo [FATAL] PHP not found on PATH or in Herd-lite. Install PHP first.
    popd & pause & exit /b 1
)

echo.
echo  ===================================================
echo    EventPro doctor - health check
echo  ===================================================
echo    Project: %ROOT%
echo    PHP    : %PHP%
echo.

set "FAIL=0"

echo  -- Critical files / folders -----------------------------------
call :check "artisan"                                   FILE
call :check ".env"                                      FILE
call :check "vendor\autoload.php"                       FILE
call :check "public\build\manifest.json"                FILE
call :check "database\database.sqlite"                  FILE
call :check "routes\web.php"                            FILE
call :check "app\Http\Middleware\SecurityHeaders.php"   FILE
call :check "resources\css\fonts.css"                   FILE
echo.

echo  -- No internet dependency leaked back in ----------------------
findstr /s /i /m "fonts.bunny.net" "resources\views\*.blade.php" >nul 2>&1
if errorlevel 1 (
    echo   [OK]      no fonts.bunny.net link in blade views
) else (
    echo   [FAIL]    a fonts.bunny.net link is back in a blade view ^(breaks offline^)
    set "FAIL=1"
)
echo.

echo  -- Route table ------------------------------------------------
set "ROUTES=0"
"%PHP%" artisan route:list >"%TEMP%\ep_routes.txt" 2>nul
for /f %%c in ('find /v /c "" ^< "%TEMP%\ep_routes.txt"') do set "ROUTES=%%c"
del "%TEMP%\ep_routes.txt" >nul 2>&1
if !ROUTES! GTR 20 (
    echo   [OK]      route:list returned !ROUTES! lines
) else (
    echo   [FAIL]    route:list returned only !ROUTES! lines ^(app may not boot^)
    set "FAIL=1"
)
echo.

echo  -- Demo readiness smoke test ^(22 flow checks^) ----------------
"%PHP%" artisan test tests\Feature\DemoReadinessTest.php
if errorlevel 1 (
    echo   [FAIL]    DemoReadinessTest reported failures - see output above
    set "FAIL=1"
) else (
    echo   [OK]      all demo-readiness flows pass
)
echo.

echo  ===================================================
if "!FAIL!"=="0" (
    echo    RESULT: HEALTHY - the app is ready to demo.
) else (
    echo    RESULT: PROBLEMS FOUND - see [FAIL] lines above.
    echo    Next:  tools\demo\diff.cmd     ^(see exactly what changed^)
    echo           tools\demo\restore.cmd  ^(recover from baseline^)
)
echo  ===================================================
echo.
popd
pause
exit /b !FAIL!

:check
REM %1 = relative path, %2 = FILE
if exist "%~1" (
    echo   [OK]      %~1
) else (
    echo   [MISSING] %~1
    set "FAIL=1"
)
exit /b 0
