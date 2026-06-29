@echo off
REM Restore source from the git-free baseline snapshot.
REM   restore.cmd app\Http\Controllers\Admin\VenueController.php   (one file)
REM   restore.cmd --all                                           (everything changed)
call "%~dp0..\..\dev.cmd" restore %*
echo.
pause
