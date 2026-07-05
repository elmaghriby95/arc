@echo off
setlocal

set "TASK_NAME=ARC Scan Agent"

echo Removing auto-start...
reg delete "HKCU\Software\Microsoft\Windows\CurrentVersion\Run" /v "ARCScanAgent" /f >nul 2>&1
del "%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\ARC-Scan-Agent.vbs" 2>nul
del "%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\ARC-Scan-Agent.bat" 2>nul
schtasks /Delete /TN "%TASK_NAME%" /F >nul 2>&1

taskkill /F /IM pythonw.exe >nul 2>&1

echo Done.
pause
