@echo off
title Vantablack - Full System
color 0E

echo ========================================
echo    VANTABLACK - FULL SYSTEM
echo ========================================
echo.

:: ============================================
:: SET YOUR PATHS HERE
:: ============================================
set "BACKEND_PATH=%~dp0backend\CODE_PHP"
set "FRONTEND_PATH=%~dp0FRONT_END"

:: ============================================
:: CHECK IF PATHS EXIST
:: ============================================
echo [CHECK] Verifying directories...
echo.

if not exist "%BACKEND_PATH%" (
    echo [ERROR] Backend not found at:
    echo   %BACKEND_PATH%
    echo.
    echo Please check your BACKEND path.
    pause
    exit /b 1
)
echo [OK] Backend path found
echo      %BACKEND_PATH%

if not exist "%FRONTEND_PATH%" (
    echo [ERROR] Frontend not found at:
    echo   %FRONTEND_PATH%
    echo.
    echo Please check your FRONT_END path.
    pause
    exit /b 1
)
echo [OK] Frontend path found
echo      %FRONTEND_PATH%
echo.

:: ============================================
:: CHECK PACKAGE.JSON
:: ============================================
if not exist "%FRONTEND_PATH%\package.json" (
    echo [WARNING] package.json not found in FRONT_END
    echo [INFO] Frontend might need: npm install
    echo.
)

:: ============================================
:: START BACKEND
:: ============================================
echo ========================================
echo [STARTING] Backend Server
echo ========================================
echo.
echo URL: http://localhost:8080
echo API: http://localhost:8080/api
echo.

start "Vantablack Backend" cmd /k "cd /d "%BACKEND_PATH%" && echo Starting Backend... && php -S localhost:8080 -t public"

:: Wait for backend to start
echo [WAIT] Starting backend...
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

:: Wait for frontend to start
echo [WAIT] Starting frontend...
timeout /t 4 /nobreak >nul

:: ============================================
:: OPEN BROWSER
:: ============================================
echo.
echo [INFO] Opening browser...
start http://localhost:5173

:: ============================================
:: SUCCESS MESSAGE
:: ============================================
echo.
echo ========================================
echo    [SUCCESS] BOTH SERVERS RUNNING!
echo ========================================
echo.
echo Backend:  http://localhost:8080
echo Frontend: http://localhost:5173
echo.
echo API Test: http://localhost:8080/api/health
echo.
echo ========================================
echo To stop servers:
echo   Close the two terminal windows
echo ========================================
echo.
echo Press any key to close this window...
pause >nul