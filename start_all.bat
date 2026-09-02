@echo off
title Aegis and Anarchy - Full System
color 0E

echo ========================================
echo    AEGIS AND ANARCHY - FULL SYSTEM
echo ========================================
echo.

:: Check if backend exists
if not exist "backend\CODE_PHP" (
    echo [ERROR] Backend not found at: backend\CODE_PHP
    echo.
    pause
    exit /b 1
)

:: Check if frontend exists (package.json in current directory)
if exist "package.json" (
    set FRONTEND_DIR=.
    echo [INFO] Frontend found in current directory
) else if exist "frontend\package.json" (
    set FRONTEND_DIR=frontend
    echo [INFO] Frontend found at: frontend
) else if exist "FRONT_END\package.json" (
    set FRONTEND_DIR=FRONT_END
    echo [INFO] Frontend found at: FRONT_END
) else (
    echo [ERROR] Frontend package.json not found!
    echo.
    echo Please check these locations:
    echo   - .\package.json
    echo   - frontend\package.json
    echo   - FRONT_END\package.json
    echo.
    echo Current directory: %cd%
    echo.
    pause
    exit /b 1
)

echo [INFO] Starting Backend Server...
echo.
start "Aegis Backend" cmd /k "cd backend && start_backend.bat"

:: Wait 3 seconds for backend to start
timeout /t 3 /nobreak >nul

echo [INFO] Starting Frontend Server (Vite)...
echo.
start "Aegis Frontend" cmd /k "cd %FRONTEND_DIR% && start_frontend.bat"

echo ========================================
echo [SUCCESS] Both servers are starting!
echo ========================================
echo.
echo Backend:  http://localhost:8080
echo Frontend: http://localhost:5173
echo.
echo API Test: http://localhost:8080/api/combatants/1
echo.
echo The terminal windows will open separately.
echo Close each terminal to stop the servers.
echo ========================================
echo.

pause