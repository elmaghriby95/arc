@echo off
setlocal

cd /d "%~dp0"

where php >nul 2>&1
if errorlevel 1 (
    echo [ERROR] PHP not found in PATH. Install WAMP or add PHP to PATH.
    pause
    exit /b 1
)

php -r "exit(class_exists('COM') ? 0 : 1);" >nul 2>&1
if errorlevel 1 (
    echo [ERROR] PHP COM extension is disabled.
    echo Enable extension=com_dotnet in php.ini then restart.
    pause
    exit /b 1
)

if not exist "%~dp0agent.env" (
    if exist "%~dp0agent.env.example" (
        copy /Y "%~dp0agent.env.example" "%~dp0agent.env" >nul
        echo Created agent.env from agent.env.example
    ) else (
        echo [WARN] agent.env not found. Create it with SCAN_ALLOWED_ORIGINS=https://arc.fwit.ly
    )
)

echo.
echo ARC Scan Agent (Windows / WIA)
echo Listening on http://127.0.0.1:8765
echo CORS origins: see agent.env ^(SCAN_ALLOWED_ORIGINS^)
echo Press Ctrl+C to stop.
echo.

php -S 127.0.0.1:8765 "%~dp0windows\router.php"
