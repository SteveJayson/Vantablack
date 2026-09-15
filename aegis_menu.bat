@echo off
title Vantablack Control Panel
color 0F

:MENU
cls
echo ========================================
echo         VANTABLACK
echo         CONTROL PANEL
echo ========================================
echo.
echo   [1] Start Backend Server
echo   [2] Start Frontend Server
echo   [3] Start Both Servers
echo   [4] Check Status
echo   [5] Import Database Schema
echo   [6] Exit
echo.
echo ========================================
set /p choice="Select an option (1-6): "

if "%choice%"=="1" goto BACKEND
if "%choice%"=="2" goto FRONTEND
if "%choice%"=="3" goto BOTH
if "%choice%"=="4" goto STATUS
if "%choice%"=="5" goto IMPORT
if "%choice%"=="6" goto EXIT

echo Invalid option!
pause
goto MENU

:BACKEND
cls
echo Starting Backend...
cd backend
call start_backend.bat
goto MENU

:FRONTEND
cls
echo Starting Frontend...
cd frontend
call start_frontend.bat
goto MENU

:BOTH
cls
echo Starting Both Servers...
cd backend
start start_backend.bat
cd ..
cd frontend
start start_frontend.bat
echo Both servers are starting!
pause
goto MENU

:STATUS
cls
call check_status.bat
pause
goto MENU

:IMPORT
cls
echo ========================================
echo    IMPORTING DATABASE SCHEMA
echo ========================================
echo.
mysql -u root -e "DROP DATABASE IF EXISTS aegis_db;"
mysql -u root -e "CREATE DATABASE aegis_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root aegis_db < backend\QUERY\schema_and_seed.sql
echo.
echo Database import complete!
pause
goto MENU

:EXIT
echo Goodbye!
exit