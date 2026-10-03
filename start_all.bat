@echo off
title Vantablack - Full System
color 0E
setlocal enabledelayedexpansion

echo ========================================
echo    VANTABLACK - FULL SYSTEM
echo ========================================
echo.

:: ============================================
:: SET YOUR PATHS HERE
:: ============================================
set "BACKEND_PATH=%~dp0backend\CODE_PHP"
set "FRONTEND_PATH=%~dp0FRONT_END"
set "MYSQL_BIN="
if exist "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" (
    set "MYSQL_BIN=C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
) else (
    where mysql >nul 2>nul
    if not errorlevel 1 set "MYSQL_BIN=mysql"
)

:: ============================================
:: CHECK 1: MYSQL IS RUNNING
:: ============================================
echo [1/4] Checking MySQL connection...
if "%MYSQL_BIN%"=="" (
    echo.
    echo   [WARNING] MySQL client not found in PATH
    echo   [INFO] Make sure MySQL is running in Laragon
    echo.
    goto :CHECK_PATHS
)

"%MYSQL_BIN%" -u root -e "SELECT 1" >nul 2>nul
if %errorlevel% neq 0 (
    echo.
    echo   [ERROR] Cannot connect to MySQL!
    echo   [INFO] Please start MySQL in Laragon first
    echo.
    set /p "continue=Continue anyway? (y/n): "
    if /i "!continue!" neq "y" exit /b 1
) else (
    echo   [OK] MySQL is running
)

:: ============================================
:: CHECK 2: DATABASE EXISTS
:: ============================================
echo.
echo [2/4] Checking database 'aegis_db'...
"%MYSQL_BIN%" -u root -e "USE aegis_db" >nul 2>nul
    if %errorlevel% neq 0 (
        echo.
        echo   [WARNING] Database 'aegis_db' not found!
        echo   [INFO] Attempting to create and import schema...
        echo.
    
        "%MYSQL_BIN%" -u root -e "CREATE DATABASE IF NOT EXISTS aegis_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
        if exist "%~dp0backend\QUERY\schema_and_seed.sql" (
            echo   [INFO] Importing schema_and_seed.sql...
            "%MYSQL_BIN%" -u root aegis_db < "%~dp0backend\QUERY\schema_and_seed.sql"
        if !errorlevel! equ 0 (
            echo   [OK] Database created and imported
        ) else (
            echo   [ERROR] Import failed. Please import manually.
        )
    ) else (
        echo   [ERROR] schema_and_seed.sql not found!
        echo   [INFO] Expected at: %~dp0backend\QUERY\schema_and_seed.sql
    )
) else (
    echo   [OK] Database 'aegis_db' exists
)

:: ============================================
:: CHECK 3: BACKEND AND FRONTEND PATHS
:: ============================================
:CHECK_PATHS
echo.
echo [3/4] Verifying directories...
echo.

if not exist "%BACKEND_PATH%" (
    echo   [ERROR] Backend not found at:
    echo   %BACKEND_PATH%
    pause
    exit /b 1
)
echo   [OK] Backend path found

if not exist "%FRONTEND_PATH%" (
    echo   [ERROR] Frontend not found at:
    echo   %FRONTEND_PATH%
    pause
    exit /b 1
)
echo   [OK] Frontend path found

if not exist "%FRONTEND_PATH%\package.json" (
    echo   [WARNING] package.json not found in FRONT_END
    echo   [INFO] Run: npm install
)

:: ============================================
:: CHECK 4: NODE_MODULES
:: ============================================
if not exist "%FRONTEND_PATH%\node_modules" (
    echo.
    echo   [WARNING] node_modules not found
    echo   [INFO] Running npm install...
    cd /d "%FRONTEND_PATH%"
    call npm install
    cd /d "%~dp0"
)

:: ============================================
:: START BACKEND
:: ============================================
echo.
echo [4/4] Starting servers...
echo.
echo ========================================
echo [STARTING] Backend Server
echo ========================================
echo.
echo URL: http://localhost:8080
echo API: http://localhost:8080/api
echo.

start "Vantablack Backend" cmd /k "cd /d "%BACKEND_PATH%" && echo Starting Backend... && php -S localhost:8080 -t public"

echo [WAIT] Backend starting...
timeout /t 4 /nobreak >nul

:: ============================================
:: START FRONTEND
:: ============================================
echo.
echo ========================================
echo [STARTING] Frontend Server (Vite)
echo ========================================
echo.
echo URL: http://localhost:5173
echo.

start "Vantablack Frontend" cmd /k "cd /d "%FRONTEND_PATH%" && echo Starting Vite... && npm run dev"

echo [WAIT] Frontend starting...
timeout /t 5 /nobreak >nul

:: ============================================
:: VERIFY SERVERS
:: ============================================
echo.
echo ========================================
echo [VERIFY] Checking servers...
echo ========================================
echo.

:: Test backend
curl -s -o nul -w "%%{http_code}" http://localhost:8080/api/health > temp_status.txt 2>nul
set /p backend_status=<temp_status.txt
del temp_status.txt 2>nul

if "!backend_status!"=="200" (
    echo   [OK] Backend is running on port 8080
) else (
    echo   [WARNING] Backend may not be responding
)

:: Open browser
echo.
echo [INFO] Opening browser...
start http://localhost:5173

:: ============================================
:: SUCCESS MESSAGE
:: ============================================
echo.
echo ========================================
echo    [SUCCESS] SYSTEM READY!
echo ========================================
echo.
echo Backend:  http://localhost:8080
echo Frontend: http://localhost:5173
echo.
echo API Test: http://localhost:8080/api/health
echo.
echo Demo Accounts:
echo   juan       / password123   (Civilian)
echo   vantablack / password123   (Hero)
echo   shadow     / password123   (Villain)
echo   admin      / admin123      (Admin)
echo.
echo ========================================
echo To stop servers:
echo   Close the two terminal windows
echo ========================================
echo.
echo Press any key to close this window...
pause >nul