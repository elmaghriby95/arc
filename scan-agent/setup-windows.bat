@echo off
setlocal EnableDelayedExpansion

cd /d "%~dp0"

set "RUNTIME=%~dp0runtime\python"
set "PYTHON=%RUNTIME%\python.exe"
set "PY_VER=3.12.10"
set "PY_ZIP=python-%PY_VER%-embed-amd64.zip"
set "PY_URL=https://www.python.org/ftp/python/%PY_VER%/%PY_ZIP%"

if exist "%PYTHON%" goto :install_packages

echo.
echo ==^> تثبيت Python المحمول (مرة واحدة، يحتاج انترنت)...
echo.

if not exist "%~dp0runtime" mkdir "%~dp0runtime"

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$ProgressPreference='SilentlyContinue'; Invoke-WebRequest -Uri '%PY_URL%' -OutFile '%~dp0runtime\%PY_ZIP%'"

if errorlevel 1 (
    echo [ERROR] فشل تحميل Python. تحقق من الاتصال بالانترنت.
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
echo ==^> تثبيت مكتبات المسح...
"%PYTHON%" -m pip install -q --disable-pip-version-check -r "%~dp0requirements-windows.txt"

if errorlevel 1 (
    echo [ERROR] فشل تثبيت المكتبات.
    pause
    exit /b 1
)

if not exist "%~dp0agent.env" (
    if exist "%~dp0agent.env.example" (
        copy /Y "%~dp0agent.env.example" "%~dp0agent.env" >nul
    ) else (
        echo SCAN_ALLOWED_ORIGINS=https://arc.fwit.ly> "%~dp0agent.env"
    )
)

echo.
echo تم التثبيت.
echo.
