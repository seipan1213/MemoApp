#!bin/sh
composer update
npm install
npm run build
php artisan migrate
php artisan storage:link