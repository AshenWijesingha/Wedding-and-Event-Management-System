@echo off
REM Build Start-EventPro.exe from EventProLauncher.cs using the .NET Framework
REM C# compiler that ships with Windows. Output lands in the project root.
setlocal

set "CSC=%WINDIR%\Microsoft.NET\Framework64\v4.0.30319\csc.exe"
if not exist "%CSC%" set "CSC=%WINDIR%\Microsoft.NET\Framework\v4.0.30319\csc.exe"
if not exist "%CSC%" (
  echo Could not find csc.exe ^(.NET Framework 4^). Cannot build.
  exit /b 1
)

set "SRC=%~dp0EventProLauncher.cs"
set "OUT=%~dp0..\Start-EventPro.exe"

echo Compiling Start-EventPro.exe ...
"%CSC%" /nologo /optimize+ /target:exe /platform:anycpu /out:"%OUT%" "%SRC%"
if errorlevel 1 (
  echo Build failed.
  exit /b 1
)

echo Done: %OUT%
endlocal
