@echo off
title Aegis & Anarchy - Backend Server
color 0A

echo ========================================
echo    AEGIS & ANARCHY BACKEND SERVER
echo ========================================
echo.

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
mysql -u root -e "USE aegis_db" >nul 2>nul
if %errorlevel% neq 0 (
    echo [WARNING] Database 'aegis_db' not found!
    echo [INFO] Creating database...
    mysql -u root -e "CREATE DATABASE aegis_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    echo [INFO] Importing schema...
    mysql -u root aegis_db < QUERY\schema_and_seed.sql
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