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
        echo SCAN_ALLOWED_ORIGINS=http://192.168.5.17,https://arch.hqnet.ly> "%~dp0agent.env"
    )
)

echo [2/3] Registering auto-start (Registry + Task Scheduler)...

set "RUN_CMD=\"%PYW%\" \"%AGENT%\""

reg add "HKCU\Software\Microsoft\Windows\CurrentVersion\Run" /v "ARCScanAgent" /t REG_SZ /d "%RUN_CMD%" /f
if errorlevel 1 (
    echo [ERROR] Failed to set Registry auto-start.
    pause
    exit /b 1
)

del "%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\ARC-Scan-Agent.vbs" 2>nul
del "%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\ARC-Scan-Agent.bat" 2>nul

schtasks /Delete /TN "%TASK_NAME%" /F >nul 2>&1
schtasks /Create /TN "%TASK_NAME%" /TR %RUN_CMD% /SC ONLOGON /DELAY 000030 /RU "%USERNAME%" /RL LIMITED /F

if errorlevel 1 (
    echo [WARN] Task Scheduler failed - Registry auto-start is active.
    echo        Run REPAIR-AUTOSTART.bat as Administrator if needed.
) else (
    echo Task Scheduler: OK
)

echo Registry auto-start: OK

echo [3/3] Starting agent now...
start "" "%PYW%" "%AGENT%"

timeout /t 5 /nobreak >nul

curl -fsS http://127.0.0.1:8765/health >nul 2>&1
if errorlevel 1 (
    echo.
    echo Installed. Agent may need 30 sec after login to start.
) else (
    echo.
    echo SUCCESS - Agent is running.
)

echo.
echo After reboot, agent starts automatically (wait ~30 sec).
echo If not: run REPAIR-AUTOSTART.bat
echo Log: %AGENT_DIR%\agent.log
echo.
pause
