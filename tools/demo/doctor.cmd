@echo off
REM Double-click entry point. Finds changed/deleted code (git-free) and verifies
REM every CRUD flow. Thin wrapper over `dev doctor` in the project root.
call "%~dp0..\..\dev.cmd" doctor %*
echo.
pause
