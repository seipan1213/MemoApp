![image](https://user-images.githubusercontent.com/38822155/183289935-02466684-03bd-4dcd-9db3-33e5163d9aeb.png)

# setup
```
create DB (memo-app)

-----
sh setup.sh
-----
OR
-----
cp .env.example .env
composer update
npm install
npm run build
php artisan migrate
php artisan storage:link
-----
```

# Launch
```
php artisan serve
```
