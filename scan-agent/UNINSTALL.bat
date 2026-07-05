@echo off
setlocal

set "STARTUP=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup"
set "TASK_NAME=ARC Scan Agent"

echo Removing auto-start...
del "%STARTUP%\ARC-Scan-Agent.vbs" 2>nul
schtasks /Delete /TN "%TASK_NAME%" /F >nul 2>&1

taskkill /F /IM pythonw.exe >nul 2>&1
taskkill /F /IM wscript.exe /FI "WINDOWTITLE eq run-agent-service.vbs" >nul 2>&1

echo Done.
pause
