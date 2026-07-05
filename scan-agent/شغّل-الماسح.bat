@echo off
setlocal

cd /d "%~dp0"

if not exist "%~dp0runtime\python\python.exe" (
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
echo ARC Scan Agent (Windows)
echo Listening on http://127.0.0.1:8765
echo اترك هذه النافذة مفتوحة ثم افتح https://arc.fwit.ly
echo.
echo اضغط Ctrl+C للايقاف.
echo.

"%~dp0runtime\python\python.exe" "%~dp0agent_windows.py"

pause
