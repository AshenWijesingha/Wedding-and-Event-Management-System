@echo off
REM Capture the CURRENT source as the known-good baseline (run once before the
REM evaluation, on a healthy checkout). Git-free. Wrapper over `dev baseline`.
call "%~dp0..\..\dev.cmd" baseline %*
echo.
pause
