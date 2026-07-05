@echo off
setlocal

cd /d "%~dp0"

if exist "%~dp0runtime\python\python.exe" (
    call "%~dp0شغّل-الماسح.bat"
    exit /b %ERRORLEVEL%
)

where php >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Python not installed. Run setup-windows.bat first.
    echo         او شغّل: شغّل-الماسح.bat
    pause
    exit /b 1
)

php -r "exit(class_exists('COM') ? 0 : 1);" >nul 2>&1
if errorlevel 1 (
    echo [ERROR] PHP COM extension is disabled.
    echo Run setup-windows.bat or شغّل-الماسح.bat instead.
    pause
    exit /b 1
)

if not exist "%~dp0agent.env" (
    if exist "%~dp0agent.env.example" (
        copy /Y "%~dp0agent.env.example" "%~dp0agent.env" >nul
    ) else (
        echo [WARN] agent.env not found. Create it with SCAN_ALLOWED_ORIGINS=https://arc.fwit.ly
    )
)

echo.
echo ARC Scan Agent (Windows / PHP legacy)
echo Listening on http://127.0.0.1:8765
echo Press Ctrl+C to stop.
echo.

php -S 127.0.0.1:8765 "%~dp0windows\router.php"
