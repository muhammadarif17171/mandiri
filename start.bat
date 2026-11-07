@echo off
setlocal
cd /d "%~dp0"
set "MANDIRI_PHP=php"
where php >nul 2>nul
if errorlevel 1 (
  if exist "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" (
    set "MANDIRI_PHP=C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe"
  ) else if exist "C:\xampp\php\php.exe" (
    set "MANDIRI_PHP=C:\xampp\php\php.exe"
  ) else (
    echo PHP tidak ditemukan. Tambahkan PHP 8.2 atau lebih baru ke PATH.
    pause
    exit /b 1
  )
)
if not exist "vendor\autoload.php" (
  echo Jalankan composer install terlebih dahulu. Lihat README.md.
  pause
  exit /b 1
)
if not exist ".env" (
  echo Buat .env sesuai README.md lalu jalankan migrasi database.
  pause
  exit /b 1
)
echo Bina Mandiri PS - http://127.0.0.1:8000
echo Pastikan MySQL aktif dan konfigurasi .env sudah sesuai.
echo Tekan Ctrl+C untuk menghentikan aplikasi.
"%MANDIRI_PHP%" artisan serve --host=127.0.0.1 --port=8000
endlocal
