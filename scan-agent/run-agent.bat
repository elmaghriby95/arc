@echo off
setlocal

cd /d "%~dp0"
if errorlevel 1 (
    echo [ERROR] Cannot open folder: %~dp0
    pause
    exit /b 1
)

if not exist "%~dp0agent_windows.py" (
    echo [ERROR] agent_windows.py not found.
    echo Copy the FULL scan-agent folder, not only START.bat
    pause
    exit /b 1
)

if not exist "%~dp0runtime\python\python.exe" (
    echo.
    echo First run: installing portable Python...
    echo Needs internet. Takes about 5 minutes.
    echo.
    call "%~dp0setup-windows.bat"
    if errorlevel 1 (
        echo.
        echo [ERROR] Setup failed. See messages above.
        pause
        exit /b 1
    )
)

if not exist "%~dp0runtime\python\python.exe" (
    echo [ERROR] Python not found after setup.
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
echo ========================================
echo   ARC Scan Agent
echo   http://127.0.0.1:8765
echo ========================================
echo.
echo Keep this window OPEN.
echo Then open: https://arc.fwit.ly
echo Press Ctrl+C to stop.
echo.

"%~dp0runtime\python\python.exe" "%~dp0agent_windows.py"
if errorlevel 1 (
    echo.
    echo [ERROR] Agent stopped with an error.
)

echo.
pause
