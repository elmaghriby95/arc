@echo off
setlocal

cd /d "%~dp0"

echo.
echo ========================================
echo   Prepare OFFLINE package (IT admin)
echo ========================================
echo.
echo This needs internet ONCE on your PC only.
echo After that, zip the folder and give to all employees.
echo Kaspersky will NOT need to download anything on their PCs.
echo.

call "%~dp0setup-windows.bat"
if errorlevel 1 (
    echo [ERROR] Prepare failed.
    pause
    exit /b 1
)

echo.
echo ========================================
echo   NEXT STEPS FOR IT
echo ========================================
echo.
echo 1. Add this folder to Kaspersky exclusions:
echo    %~dp0
echo.
echo 2. Zip the ENTIRE scan-agent folder including runtime\
echo    Name: ARC-Scan-Agent.zip
echo.
echo 3. Copy ZIP to USB and give to employees
echo.
echo 4. Employee: extract to C:\scan-agent
echo    Then double-click: INSTALL-OFFLINE.bat
echo.
echo 5. Employee does NOT need internet or Kaspersky changes
echo    if you prepared the package correctly.
echo.
pause
