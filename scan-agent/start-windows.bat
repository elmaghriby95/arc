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

echo.
echo ARC Scan Agent (Windows / WIA)
echo Listening on http://127.0.0.1:8765
echo Press Ctrl+C to stop.
echo.

php -S 127.0.0.1:8765 "%~dp0windows\router.php"
