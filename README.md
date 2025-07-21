# Router-PHP

Un routeur PHP simple, léger et réutilisable pour vos applications web.

## Installation

### Via Composer
```bash
composer require mrsems/router-php
```

##  Guide d'utilisation

### 1. Configuration des routes

Créez un fichier `routes.php` à la racine de votre projet :

```php
<?php
return [
  
       '/' => [
        'controller' => App\Controllers\SecurityController::class,
        'method' => 'login',
        'middlewares' => [],
        'methods' => ['GET', 'POST'],
    ],
    'client/dashboard' => [
        'controller' => App\Controllers\UserController::class,
        'method' => 'index',
        'middlewares' => ['auth'],
        'methods' => ['GET'],
    ],
    'client/{id}' => [
        'controller' => App\Controllers\UserController::class,
        'method' => 'show',
        'middlewares' => ['auth'],
        'methods' => ['GET'],
    ],

    
];
```

### 2. Configuration des middlewares

Créez un fichier `middlewares.php` :

```php
<?php
return [
    'auth' => \App\Middlewares\AuthMiddleware::class,
];
```

### 3. Création d'un middleware

Exemple de middleware d'authentification :

```php
<?php
namespace App\Middlewares;

class AuthMiddleware
{
    public function __invoke()
    {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
    }
}
```

### 4. Création d'un contrôleur

Exemple de contrôleur :

```php
<?php
namespace App\Controllers;

class HomeController
{
    public function index()
    {
        echo "Bienvenue sur la page d'accueil !";
    }
}
```

### 5. Initialisation du routeur

Dans votre fichier `index.php` :

```php
<?php
require 'vendor/autoload.php';

use App\Router\Router;

// Charger les configurations
$routes = require 'routes.php';
$middlewares = require 'middlewares.php';

// Résoudre la route
Router::resolve($routes, $middlewares);
```

##  Structure du projet

```
votre-projet/
├── vendor/
├── App/
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   └── DashboardController.php
│   └── Middlewares/
│       └── AuthMiddleware.php
├── routes.php
├── middlewares.php
├── index.php
└── composer.json
```

## Fonctionnalités

- ✅ Support des méthodes HTTP (GET, POST, etc.)
- ✅ Système de middlewares
- ✅ Contrôleurs organisés par classes
- ✅ Configuration simple via fichiers PHP


