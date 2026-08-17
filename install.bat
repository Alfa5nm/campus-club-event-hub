@echo off
setlocal EnableExtensions EnableDelayedExpansion
title Campus Club Hub - XAMPP Installer
color 0A

set "APP_NAME=campus-club-event-hub"
set "DB_NAME=campus_club_hub"
set "SOURCE_DIR=%~dp0"
set "XAMPP_DIR="
set "MYSQL_PASSWORD="

echo.
echo  ============================================================
echo       Campus Club ^& Event Hub - Local Setup for Windows
echo  ============================================================
echo.

call :find_xampp
if not defined XAMPP_DIR call :xampp_missing
if not defined XAMPP_DIR goto :failed

echo [OK] XAMPP found at: %XAMPP_DIR%
if not exist "%XAMPP_DIR%\php\php.exe" (
    echo [ERROR] PHP is missing from this XAMPP installation.
    goto :failed
)
if not exist "%XAMPP_DIR%\mysql\bin\mysql.exe" (
    echo [ERROR] MySQL or MariaDB is missing from this XAMPP installation.
    goto :failed
)
if not exist "%XAMPP_DIR%\phpMyAdmin\index.php" (
    echo [ERROR] phpMyAdmin is missing. Reinstall the complete XAMPP package.
    goto :failed
)

set "DEST_DIR=%XAMPP_DIR%\htdocs\%APP_NAME%"
call :install_files
if errorlevel 1 goto :failed

call :start_services
if errorlevel 1 goto :failed

call :wait_for_mysql
if errorlevel 1 goto :failed

call :configure_database
if errorlevel 1 goto :failed

echo.
echo  ============================================================
echo   Installation completed successfully.
echo  ============================================================
echo.
echo   Application: http://localhost/%APP_NAME%/
echo   phpMyAdmin:  http://localhost/phpmyadmin/
echo.
echo   Demo executive: amina@student.edu / Password123!
echo   Demo student:   nafis@student.edu / Password123!
echo   Demo admin:     admin@campus.edu / Admin123!
echo.
choice /C YN /N /M "Open the application and phpMyAdmin now? [Y/N]: "
if errorlevel 2 goto :success
start "" "http://localhost/%APP_NAME%/"
start "" "http://localhost/phpmyadmin/"

:success
echo Setup is complete. You may close this window.
pause
exit /b 0

:find_xampp
if defined XAMPP_HOME if exist "%XAMPP_HOME%\xampp-control.exe" set "XAMPP_DIR=%XAMPP_HOME%"
if defined XAMPP_DIR exit /b 0

for %%D in (C D E F) do (
    if not defined XAMPP_DIR if exist "%%D:\xampp\xampp-control.exe" set "XAMPP_DIR=%%D:\xampp"
)
if defined XAMPP_DIR exit /b 0

for /f "delims=" %%I in ('where xampp-control.exe 2^>nul') do (
    if not defined XAMPP_DIR set "XAMPP_DIR=%%~dpI"
)
if defined XAMPP_DIR if "!XAMPP_DIR:~-1!"=="\" set "XAMPP_DIR=!XAMPP_DIR:~0,-1!"
exit /b 0

:xampp_missing
color 0E
echo [NOTICE] XAMPP was not found on drives C: through F:.
echo.
echo XAMPP provides every required service in one package:
echo Apache, PHP, MariaDB/MySQL, and phpMyAdmin.
echo.
choice /C YN /N /M "Open the official XAMPP download page? [Y/N]: "
if errorlevel 2 exit /b 0
start "" "https://www.apachefriends.org/download.html"
echo.
echo Install XAMPP, keep Apache, MySQL, PHP, and phpMyAdmin selected,
echo then run this installer again.
pause
exit /b 0

:install_files
echo.
echo [1/4] Installing project files...
if /I "%SOURCE_DIR:~0,-1%"=="%DEST_DIR%" (
    echo [OK] The project is already inside XAMPP htdocs.
    exit /b 0
)

if exist "%DEST_DIR%\" (
    echo An existing installation was found at:
    echo %DEST_DIR%
    choice /C YN /N /M "Update its application files? Existing uploads are preserved. [Y/N]: "
    if errorlevel 2 (
        echo [ERROR] File installation was cancelled.
        exit /b 1
    )
) else (
    mkdir "%DEST_DIR%" 2>nul
)

robocopy "%SOURCE_DIR%" "%DEST_DIR%" /E /R:2 /W:1 /XD ".git" "backups" /XF "*.log" >nul
set "ROBO_RESULT=!ERRORLEVEL!"
if !ROBO_RESULT! GEQ 8 (
    echo [ERROR] Files could not be copied. Robocopy code: !ROBO_RESULT!
    exit /b 1
)
echo [OK] Project installed at %DEST_DIR%
exit /b 0

:start_services
echo.
echo [2/4] Starting Apache and MySQL...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | find /I "mysqld.exe" >nul
if errorlevel 1 start "CampusHub MySQL" /MIN "%XAMPP_DIR%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP_DIR%\mysql\bin\my.ini" --standalone

tasklist /FI "IMAGENAME eq httpd.exe" 2>nul | find /I "httpd.exe" >nul
if errorlevel 1 start "CampusHub Apache" /MIN "%XAMPP_DIR%\apache\bin\httpd.exe"

timeout /t 3 /nobreak >nul
tasklist /FI "IMAGENAME eq httpd.exe" 2>nul | find /I "httpd.exe" >nul
if errorlevel 1 (
    echo [ERROR] Apache did not start. Another program may be using port 80.
    echo Open the XAMPP Control Panel to inspect the Apache log.
    exit /b 1
)
echo [OK] Apache is running.
exit /b 0

:wait_for_mysql
echo.
echo [3/4] Connecting to MySQL...
set /a ATTEMPT=0
:mysql_wait_loop
"%XAMPP_DIR%\mysql\bin\mysqladmin.exe" -u root ping --silent >nul 2>&1
if not errorlevel 1 (
    echo [OK] MySQL is running.
    exit /b 0
)
set /a ATTEMPT+=1
if !ATTEMPT! GEQ 12 (
    echo [ERROR] MySQL did not become ready.
    echo This installer expects the standard XAMPP root account with no password.
    echo Open the XAMPP Control Panel and verify the MySQL configuration.
    exit /b 1
)
timeout /t 1 /nobreak >nul
goto :mysql_wait_loop

:configure_database
echo.
echo [4/4] Preparing database and mock data...
set "MYSQL=%XAMPP_DIR%\mysql\bin\mysql.exe"
set "MYSQLDUMP=%XAMPP_DIR%\mysql\bin\mysqldump.exe"
set "SCHEMA=%DEST_DIR%\database\schema.sql"
set "SEED=%DEST_DIR%\database\seed.sql"

"%MYSQL%" -u root -N -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='%DB_NAME%'" 2>nul | find /I "%DB_NAME%" >nul
if not errorlevel 1 (
    echo An existing %DB_NAME% database was found.
    choice /C RSK /N /M "Reset with seed data [R], skip database import [S], or cancel [K]? "
    if errorlevel 3 exit /b 1
    if errorlevel 2 (
        echo [OK] Existing database preserved.
        exit /b 0
    )
    if not exist "%DEST_DIR%\backups" mkdir "%DEST_DIR%\backups"
    for /f %%T in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd-HHmmss"') do set "STAMP=%%T"
    "%MYSQLDUMP%" -u root --single-transaction "%DB_NAME%" > "%DEST_DIR%\backups\%DB_NAME%-!STAMP!.sql"
    if errorlevel 1 (
        echo [ERROR] Existing database could not be backed up. Reset cancelled.
        exit /b 1
    )
    echo [OK] Backup created in %DEST_DIR%\backups
)

"%MYSQL%" -u root -e "source %SCHEMA:\=/%"
if errorlevel 1 (
    echo [ERROR] Database schema import failed.
    exit /b 1
)
"%MYSQL%" -u root -e "source %SEED:\=/%"
if errorlevel 1 (
    echo [ERROR] Mock seed data import failed.
    exit /b 1
)
echo [OK] Database and mock data are ready.
exit /b 0

:failed
color 0C
echo.
echo Installation did not complete. No existing database was reset without
echo confirmation. Review the message above, then run install.bat again.
pause
exit /b 1
