@echo off
setlocal

cd /d "%~dp0"
set "AGENT_DIR=%~dp0"
set "AGENT_DIR=%AGENT_DIR:~0,-1%"
set "PYW=%~dp0runtime\python\pythonw.exe"
set "AGENT=%~dp0agent_windows.py"
set "TASK_NAME=ARC Scan Agent"

echo.
echo ========================================
echo   ARC Scan Agent - Offline Install
echo   (no internet, no download)
echo ========================================
echo.

if not exist "%AGENT%" (
    echo [ERROR] agent_windows.py not found.
    pause
    exit /b 1
)

echo [1/3] Checking bundled Python...
call "%~dp0setup-windows.bat" offline
if errorlevel 1 exit /b 1

if not exist "%~dp0agent.env" (
    if exist "%~dp0agent.env.example" (
        copy /Y "%~dp0agent.env.example" "%~dp0agent.env" >nul
    ) else (
        echo SCAN_ALLOWED_ORIGINS=https://arc.fwit.ly> "%~dp0agent.env"
    )
)

echo [2/3] Registering auto-start...
schtasks /Delete /TN "%TASK_NAME%" /F >nul 2>&1
schtasks /Create /TN "%TASK_NAME%" /TR "\"%PYW%\" \"%AGENT%\"" /SC ONLOGON /DELAY 000045 /F >nul 2>&1

echo [3/3] Starting agent...
start "" "%PYW%" "%AGENT%"

timeout /t 5 /nobreak >nul

echo.
echo SUCCESS - Installed offline.
echo Agent runs in background. Open https://arc.fwit.ly
echo Log: %AGENT_DIR%\agent.log
echo.
pause
