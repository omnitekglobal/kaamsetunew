@echo off
cd /d "%~dp0"
echo Starting PHP API at http://localhost:8000 (router enabled)
php -S localhost:8000 router.php
