@echo off
title Aegis & Anarchy - Frontend Server
color 0B

echo ========================================
echo    AEGIS & ANARCHY FRONTEND SERVER
echo ========================================
echo.

:: Check if PHP is installed (for built-in server)
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] PHP is not installed or not in PATH!
    echo.
    echo Please install PHP or add it to your system PATH.
    pause
    exit /b 1
)

:: Check if index.html exists
if not exist "index.html" (
    echo [ERROR] index.html not found!
    echo.
    echo Please make sure you're in the correct directory.
    pause
    exit /b 1
)

:: Check if frontend dependencies are installed (if using Node.js)
if exist "package.json" (
    echo [INFO] Checking Node.js dependencies...
    if not exist "node_modules" (
        echo [INFO] Installing Node.js dependencies...
        call npm install
        echo.
    )
)

echo ========================================
echo [SUCCESS] Starting frontend server...
echo ========================================
echo.
echo Server URL: http://localhost:3000
echo.
echo Press Ctrl+C to stop the server
echo ========================================
echo.

:: If Node.js is available and package.json exists, use npm
if exist "package.json" (
    call npm run dev
) else (
    :: Otherwise use PHP built-in server
    php -S localhost:3000
)

pause