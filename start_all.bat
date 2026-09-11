@echo off
title Aegis and Anarchy - Full System
color 0E

echo ========================================
echo    AEGIS AND ANARCHY - FULL SYSTEM
echo ========================================
echo.

:: Set project root
set PROJECT_ROOT=%~dp0
cd /d "%PROJECT_ROOT%"

:: Find backend folder
set BACKEND_DIR=
if exist "backend\CODE_PHP" set BACKEND_DIR=backend\CODE_PHP
if exist "BACKEND\CODE_PHP" set BACKEND_DIR=BACKEND\CODE_PHP
if exist "CODE_PHP" set BACKEND_DIR=CODE_PHP

:: Find frontend folder
set FRONTEND_DIR=
if exist "frontend\package.json" set FRONTEND_DIR=frontend
if exist "FRONT_END\package.json" set FRONTEND_DIR=FRONT_END
if exist "frontend\index.html" if "%FRONTEND_DIR%"=="" set FRONTEND_DIR=frontend
if exist "FRONT_END\index.html" if "%FRONTEND_DIR%"=="" set FRONTEND_DIR=FRONT_END

echo [INFO] Project Root: %PROJECT_ROOT%
echo [INFO] Backend Dir:  %BACKEND_DIR%
echo [INFO] Frontend Dir: %FRONTEND_DIR%
echo.

if "%BACKEND_DIR%"=="" (
    echo [ERROR] Backend not found!
    echo Looked in: backend\CODE_PHP, BACKEND\CODE_PHP, CODE_PHP
    pause
    exit /b 1
)

if "%FRONTEND_DIR%"=="" (
    echo [ERROR] Frontend not found!
    echo Looked in: frontend, FRONT_END
    pause
    exit /b 1
)

:: Start Backend
echo [INFO] Starting Backend Server...
start "Aegis Backend" cmd /k "cd /d %PROJECT_ROOT%%BACKEND_DIR% && php -S localhost:8080 -t public"

:: Wait for backend
timeout /t 3 /nobreak >nul

:: Start Frontend
echo [INFO] Starting Frontend Server...
start "Aegis Frontend" cmd /k "cd /d %PROJECT_ROOT%%FRONTEND_DIR% && npm run dev"

:: Wait for frontend
timeout /t 5 /nobreak >nul

echo ========================================
echo [SUCCESS] Both servers are starting!
echo ========================================
echo.
echo Backend:  http://localhost:8080
echo Frontend: http://localhost:5173
echo.
echo Opening browser...
timeout /t 2 /nobreak >nul
start http://localhost:5173

echo.
echo Close each terminal to stop the servers.
echo ========================================
echo.
pause