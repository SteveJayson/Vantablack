@echo off
title Vantablack - Backend Server
color 0A

echo ========================================
echo    VANTABLACK BACKEND SERVER
echo ========================================
echo.

set "MYSQL_BIN="
if exist "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" (
    set "MYSQL_BIN=C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
) else (
    where mysql >nul 2>nul
    if not errorlevel 1 set "MYSQL_BIN=mysql"
)

:: Check if PHP is installed
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] PHP is not installed or not in PATH!
    echo.
    echo Please install PHP or add it to your system PATH.
    pause
    exit /b 1
)

:: Check if composer dependencies are installed
if not exist "CODE_PHP\vendor" (
    echo [INFO] Composer dependencies not found.
    echo [INFO] Running composer install...
    echo.
    cd CODE_PHP
    composer install
    cd ..
    echo.
)

:: Check if .env exists
if not exist "CODE_PHP\.env" (
    echo [INFO] Creating .env file...
    echo DB_HOST=localhost > CODE_PHP\.env
    echo DB_NAME=aegis_db >> CODE_PHP\.env
    echo DB_USER=root >> CODE_PHP\.env
    echo DB_PASS= >> CODE_PHP\.env
    echo.
)

:: Check if database exists
echo [INFO] Checking database connection...
if "%MYSQL_BIN%"=="" (
    echo [ERROR] MySQL client not found. Please start Laragon or add MySQL to PATH.
    pause
    exit /b 1
)

"%MYSQL_BIN%" -u root -e "USE aegis_db" >nul 2>nul
if %errorlevel% neq 0 (
    echo [WARNING] Database 'aegis_db' not found!
    echo [INFO] Creating database...
    "%MYSQL_BIN%" -u root -e "CREATE DATABASE aegis_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    echo [INFO] Importing schema...
    "%MYSQL_BIN%" -u root aegis_db < QUERY\schema_and_seed.sql
    echo.
)

echo ========================================
echo [SUCCESS] Starting backend server...
echo ========================================
echo.
echo Server URL: http://localhost:8080
echo API URL:    http://localhost:8080/api
echo.
echo Press Ctrl+C to stop the server
echo ========================================
echo.

cd CODE_PHP
php -S localhost:8080 -t public

pause