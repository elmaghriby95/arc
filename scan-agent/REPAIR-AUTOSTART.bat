@echo off
REM Re-register auto-start only (run after moving folder or reboot issues)
setlocal

cd /d "%~dp0"
set "AGENT_DIR=%~dp0"
set "AGENT_DIR=%AGENT_DIR:~0,-1%"
set "PYW=%~dp0runtime\python\pythonw.exe"
set "AGENT=%~dp0agent_windows.py"
set "TASK_NAME=ARC Scan Agent"
set "STARTUP=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup"

echo.
echo ARC Scan Agent - Repair auto-start
echo.

if not exist "%PYW%" (
    echo [ERROR] runtime\python not found. Run INSTALL-OFFLINE.bat first.
    pause
    exit /b 1
)

set "RUN_CMD=\"%PYW%\" \"%AGENT%\""

reg add "HKCU\Software\Microsoft\Windows\CurrentVersion\Run" /v "ARCScanAgent" /t REG_SZ /d "%RUN_CMD%" /f

schtasks /Delete /TN "%TASK_NAME%" /F >nul 2>&1
schtasks /Create /TN "%TASK_NAME%" /TR %RUN_CMD% /SC ONLOGON /DELAY 000030 /RU "%USERNAME%" /RL LIMITED /F

if errorlevel 1 (
    echo [WARN] Task Scheduler failed - Registry auto-start is still set.
) else (
    echo Task Scheduler: OK
)

echo Registry Run key: OK
echo.
echo Starting agent now...
start "" "%PYW%" "%AGENT%"

timeout /t 4 /nobreak >nul
curl -fsS http://127.0.0.1:8765/health >nul 2>&1
if errorlevel 1 (
    echo Agent not responding yet - wait 30 sec after next login.
) else (
    echo Agent is running.
)

echo.
pause
