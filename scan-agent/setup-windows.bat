@echo off
setlocal EnableDelayedExpansion

cd /d "%~dp0"

set "RUNTIME=%~dp0runtime\python"
set "PYTHON=%RUNTIME%\python.exe"
set "PYW=%RUNTIME%\pythonw.exe"
set "PY_VER=3.12.10"
set "PY_ZIP=python-%PY_VER%-embed-amd64.zip"
set "PY_URL=https://www.python.org/ftp/python/%PY_VER%/%PY_ZIP%"

if exist "%PYW%" goto :install_packages

if /i "%~1"=="offline" (
    echo.
    echo [ERROR] Offline package missing runtime\python
    echo Run PREPARE-PACKAGE.bat on IT admin PC first.
    pause
    exit /b 1
)

echo.
echo Downloading portable Python (IT admin / first prepare only)...
echo.

if not exist "%~dp0runtime" mkdir "%~dp0runtime"

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$ProgressPreference='SilentlyContinue'; Invoke-WebRequest -Uri '%PY_URL%' -OutFile '%~dp0runtime\%PY_ZIP%'"

if errorlevel 1 (
    echo [ERROR] Download failed. Check internet or Kaspersky on THIS admin PC.
    pause
    exit /b 1
)

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "Expand-Archive -Path '%~dp0runtime\%PY_ZIP%' -DestinationPath '%RUNTIME%' -Force"

del "%~dp0runtime\%PY_ZIP%" 2>nul

for %%f in ("%RUNTIME%\python*._pth") do (
    powershell -NoProfile -Command ^
      "(Get-Content '%%f') -replace '#import site','import site' | Set-Content '%%f'"
)

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$ProgressPreference='SilentlyContinue'; Invoke-WebRequest -Uri 'https://bootstrap.pypa.io/get-pip.py' -OutFile '%RUNTIME%\get-pip.py'"

"%PYTHON%" "%RUNTIME%\get-pip.py" --no-warn-script-location
del "%RUNTIME%\get-pip.py" 2>nul

:install_packages
"%PYTHON%" -c "import flask, win32com.client, PIL, img2pdf" >nul 2>&1
if not errorlevel 1 goto :write_env

if /i "%~1"=="offline" (
    echo [ERROR] Python libraries missing from offline ZIP.
    echo Ask IT to run PREPARE-PACKAGE.bat again.
    pause
    exit /b 1
)

echo Installing scan libraries...
"%PYTHON%" -m pip install -q --disable-pip-version-check -r "%~dp0requirements-windows.txt"

if errorlevel 1 (
    echo [ERROR] Failed to install libraries.
    pause
    exit /b 1
)

:write_env
if not exist "%~dp0agent.env" (
    if exist "%~dp0agent.env.example" (
        copy /Y "%~dp0agent.env.example" "%~dp0agent.env" >nul
    ) else (
        echo SCAN_ALLOWED_ORIGINS=http://192.168.5.17,https://arch.hqnet.ly> "%~dp0agent.env"
    )
)

echo.
echo Setup complete.
echo.
exit /b 0
