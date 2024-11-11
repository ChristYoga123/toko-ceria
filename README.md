# Init Laravel Filament Starter Kit

Personal Laravel Starter Kit, integrated with:

1. FilamentPHP, 
2. FilamentPHP EditProfile, 
3. FilamentPHP EditEnv, 
4. FilamentPHP LogManager
5. FilamentPHP SpatieMediaLibrary
6. Spatie Laravel-Permission
7. Spatie Laravel-Medialibrary
8. Laravel Debugbar

owner starter kit: [ChristYoga123](https://github.com/ChristYoga123)

### 1. Composer install

```
composer i
```

### 2. Modify .env

First, copy .env.example into .env

```
cp .env.example .env
```

Then, generate APP_KEY

```
php artisan key:generate
```

Last, modify env variables

```
APP_NAME="Your App Name"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://your-app-name

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Make Filament user

```
php artisan make:filament-user
```

Insert your initial user

### 4. Generate Initial Policies using Filament

```
php artisan shield:install
```

Choose `yes` only

### 5. Modify Initial Role

You can access the `role-management` using RoleSeeder.php

### 6. Enjoy Your Develop Process

### NB: You can check the details of your dependencies using composer.json