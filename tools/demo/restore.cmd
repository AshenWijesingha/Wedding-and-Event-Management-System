@echo off
setlocal
REM ===========================================================================
REM  EventPro - restore.cmd
REM  Recovers source code from the known-good 'demo-baseline' tag.
REM    restore.cmd path\to\File.php   -> restore just that file
REM    restore.cmd                    -> restore ALL tracked source to baseline
REM
REM  NOTE: this restores files that GIT tracks (source code AND the seeded
REM  database\database.sqlite). Build artifacts that git ignores
REM  (vendor\, node_modules\, public\build\, .env) are NOT in git - if one of
REM  those is deleted, restore it from the backup .zip made by
REM  tools\demo\backup.cmd instead.
REM ===========================================================================
title EventPro - restore from baseline

set "ROOT=%~dp0..\.."
pushd "%ROOT%"

git rev-parse -q --verify refs/tags/demo-baseline >nul 2>&1
if errorlevel 1 (
    echo  [ERROR] The 'demo-baseline' tag does not exist. Nothing to restore from.
    popd & pause & exit /b 1
)

set "TARGET=%~1"
echo.
if "%TARGET%"=="" (
    echo  About to restore ALL tracked files to the 'demo-baseline' state.
    echo  Uncommitted edits to tracked files will be OVERWRITTEN.
    choice /m "  Proceed"
    if errorlevel 2 ( echo  Cancelled. & popd & pause & exit /b 0 )
    git checkout demo-baseline -- .
    echo.
    echo  [OK] All tracked files restored to baseline.
) else (
    if not "%TARGET:~0,1%"=="" (
        echo  Restoring "%TARGET%" from demo-baseline ...
        git checkout demo-baseline -- "%TARGET%"
        if errorlevel 1 (
            echo  [ERROR] Could not restore "%TARGET%". Check the path.
            popd & pause & exit /b 1
        )
        echo  [OK] Restored "%TARGET%".
    )
)
echo.
echo  Tip: run tools\demo\doctor.cmd to confirm the app is healthy again.
echo.
popd
pause
