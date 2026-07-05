@echo off
setlocal

cd /d "%~dp0"
set "AGENT_DIR=%~dp0"
set "AGENT_DIR=%AGENT_DIR:~0,-1%"
set "STARTUP=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup"
set "TASK_NAME=ARC Scan Agent"

echo.
echo ========================================
echo   ARC Scan Agent - Install Once
echo ========================================
echo.

if not exist "%~dp0agent_windows.py" (
    echo [ERROR] agent_windows.py not found. Copy the full scan-agent folder.
    pause
    exit /b 1
)

echo [1/4] Installing Python and libraries...
call "%~dp0setup-windows.bat"
if errorlevel 1 (
    echo [ERROR] Setup failed.
    pause
    exit /b 1
)

if not exist "%~dp0runtime\python\pythonw.exe" (
    echo [ERROR] pythonw.exe not found after setup.
    pause
    exit /b 1
)

echo [2/4] Configuring auto-start at Windows login...

if not exist "%STARTUP%" mkdir "%STARTUP%"

(
echo Set shell = CreateObject^("WScript.Shell"^)
echo shell.Run "wscript.exe ""%AGENT_DIR%\run-agent-service.vbs""", 0, False
) > "%STARTUP%\ARC-Scan-Agent.vbs"

schtasks /Delete /TN "%TASK_NAME%" /F >nul 2>&1
schtasks /Create /TN "%TASK_NAME%" /TR "wscript.exe \"%AGENT_DIR%\run-agent-service.vbs\"" /SC ONLOGON /DELAY 000045 /F >nul 2>&1

echo [3/4] Starting agent now...
start "" wscript.exe "%~dp0run-agent-service.vbs"

echo [4/4] Waiting for agent...
timeout /t 6 /nobreak >nul

curl -fsS http://127.0.0.1:8765/health >nul 2>&1
if errorlevel 1 (
    echo.
    echo Installed, but health check failed yet.
    echo Wait 30 seconds after reboot, or connect scanner USB first.
) else (
    echo.
    echo SUCCESS - Agent is running.
)

echo.
echo ========================================
echo   Done. No need to run START.bat again.
echo   Agent starts automatically with Windows.
echo   Log file: %AGENT_DIR%\agent.log
echo ========================================
echo.
pause
