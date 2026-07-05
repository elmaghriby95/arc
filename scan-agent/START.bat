@echo off
REM Opens a window that stays open even if setup fails.
if /i not "%~1"=="run" (
    start "ARC Scan Agent" cmd /k "%~f0" run
    exit /b 0
)

cd /d "%~dp0"
if errorlevel 1 (
    echo [ERROR] Cannot open folder: %~dp0
    goto :done
)

echo ARC Scan Agent v2 - no PHP required
echo.

call "%~dp0run-agent.bat"

:done
echo.
pause
