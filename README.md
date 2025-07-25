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

## Injection de dépendances et mapping d’interfaces

Le routeur instancie automatiquement vos contrôleurs et leurs dépendances grâce à la réflexion.  
Si un constructeur de contrôleur attend une interface, vous pouvez définir le mapping interface => classe concrète :

```php
use App\Router\Router;

Router::setDependencyMap([
    App\Contracts\IUserRepository::class => App\Repositories\UserRepository::class,
    // Ajoutez d'autres mappings ici
]);
```

**Exemple d’utilisation dans une route** :

```php
return [
    '/dashboard' => [
        'controller' => App\Controllers\DashboardController::class,
        'method' => 'index',
        'middlewares' => ['auth'],
        'methods' => ['GET'],
    ],
];
```

Votre contrôleur peut alors recevoir des dépendances dans son constructeur :

```php
namespace App\Controllers;

use App\Contracts\IUserRepository;

class DashboardController
{
    public function __construct(IUserRepository $userRepo) {
        $this->userRepo = $userRepo;
    }

    public function index() {
        // ...
    }
}
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


