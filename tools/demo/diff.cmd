@echo off
REM Fast view of exactly what changed/was deleted vs the baseline (no functional
REM tests). Git-free. Thin wrapper over `dev doctor --no-tests`.
call "%~dp0..\..\dev.cmd" doctor --no-tests %*
echo.
pause
