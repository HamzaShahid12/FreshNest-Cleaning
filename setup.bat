@echo off
if not exist .env copy .env.example .env
call composer install || goto :err
call npm install || goto :err
php artisan key:generate
echo Make sure the MySQL database "freshnest" exists, then press any key.
pause
php artisan migrate --seed || goto :err
php artisan storage:link
call npm run build || goto :err
echo Done. Run: php artisan serve
goto :eof
:err
echo Setup failed. See the message above.
exit /b 1
