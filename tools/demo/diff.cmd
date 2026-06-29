@echo off
setlocal
REM ===========================================================================
REM  EventPro - diff.cmd
REM  Shows EXACTLY what changed (or was deleted) versus the known-good
REM  'demo-baseline' tag. Run this after doctor.cmd reports a problem, to see
REM  precisely which code the evaluator removed so you can re-implement it.
REM ===========================================================================
title EventPro - diff vs baseline

set "ROOT=%~dp0..\.."
pushd "%ROOT%"

git rev-parse -q --verify refs/tags/demo-baseline >nul 2>&1
if errorlevel 1 (
    echo.
    echo  [ERROR] The 'demo-baseline' tag does not exist.
    echo          Create it once on a known-good checkout:
    echo              git tag -a demo-baseline -m "known-good demo state"
    echo.
    popd & pause & exit /b 1
)

echo.
echo  ===================================================
echo    Working tree vs demo-baseline
echo  ===================================================
echo.
echo  -- git status (uncommitted changes) ---------------------------
git status --short
echo.
echo  -- files changed/deleted vs baseline (summary) ----------------
git --no-pager diff --stat demo-baseline
echo.
echo  -- full diff vs baseline (deleted lines start with '-') -------
echo     (q to quit if it pages; full patch follows)
echo.
git --no-pager diff demo-baseline
echo.
echo  ===================================================
echo    To recover:  tools\demo\restore.cmd ^<path^>     (one file)
echo                 tools\demo\restore.cmd            (everything)
echo  ===================================================
echo.
popd
pause
