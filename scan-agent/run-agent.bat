@echo off
setlocal

cd /d "%~dp0"

if not exist "%~dp0runtime\python\python.exe" (
    echo.
    echo First run: installing portable Python (needs internet, ~5 min)...
    echo.
    call "%~dp0setup-windows.bat"
    if errorlevel 1 exit /b 1
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

echo.
pause
