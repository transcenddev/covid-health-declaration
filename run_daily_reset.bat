@echo off
REM COVID-19 Health Declaration System - Daily Reset Batch File
REM This batch file runs the daily reset script for guest usage limits

echo Starting COVID-19 Daily Reset Script...
echo Timestamp: %date% %time%
echo.

REM Change to the project directory
cd /d "C:\xampp\htdocs\covid-health-declaration"

REM Run the PHP script
"C:\xampp\php\php.exe" reset_daily_limits.php

REM Capture the exit code
set EXITCODE=%ERRORLEVEL%

echo.
echo Script completed with exit code: %EXITCODE%

REM Exit codes:
REM 0 = Success
REM 1 = Some operations failed
REM 2 = Fatal error

if %EXITCODE%==0 (
    echo Status: All operations completed successfully
) else if %EXITCODE%==1 (
    echo Status: Some operations failed - check logs for details
) else (
    echo Status: Fatal error occurred - check logs immediately
)

echo.
echo Check logs at: C:\xampp\htdocs\covid-health-declaration\logs\daily_reset.log
echo.

REM Keep window open for 5 seconds if run manually
timeout /t 5 /nobreak >nul

exit /b %EXITCODE%