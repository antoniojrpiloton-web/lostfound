@echo off
cd /d "%~dp0"
echo Starting Boracay Lost & Found Laravel Scheduler (blf:run-matching, blf:flag-disposal-eligible, blf:backup-db) ...
php artisan schedule:work
pause
