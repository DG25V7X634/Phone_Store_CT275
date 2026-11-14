@echo off
chcp 65001 >nul
cd /d "%~dp0"
title PHONE STORE CT275 - KHOI DONG WEBSITE

echo ==================================================
echo          PHONE STORE CT275 - KHOI DONG
echo ==================================================
echo.
where php >nul 2>nul
if errorlevel 1 (
  echo KHONG TIM THAY PHP trong PATH.
  echo Hay cai dat PHP 8.2 tro len va them php.exe vao PATH.
  echo Neu ban da cai PHP tai D:\Servers\php, hay bao lai de minh huong dan.
  pause
  exit /b 1
)

if not exist ".env" (
  echo Lan dau chay: can nhap mat khau MySQL root de tao cau hinh.
  powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0TAO_CAU_HINH.ps1"
  if errorlevel 1 (
    echo Khong tao duoc file .env. Hay chay lai va nhap mat khau MySQL chinh xac.
    pause
    exit /b 1
  )
)

echo.
echo Dang kiem tra va bo sung bang order_items (khong xoa du lieu cu)...
php "database\setup_order_items.php"
if errorlevel 1 (
  echo.
  echo Chua the hoan tat cau hinh CSDL. Doc loi o tren va gui anh cho ChatGPT.
  pause
  exit /b 1
)

echo.
echo Mo website tai http://127.0.0.1:8000/
start "" "http://127.0.0.1:8000/"
echo Giu cua so nay mo trong luc su dung website.
echo Nhan Ctrl+C de dung may chu.
echo.
php -S 127.0.0.1:8000 -t public
pause
