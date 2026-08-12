# Router-PHP

Un routeur PHP simple, léger et réutilisable pour vos applications web.

Il peut être utilisé dans deux styles de projet :

- Projet PHP procédural : routes vers des fonctions ou des fichiers PHP.
- Projet PHP orienté objet : routes vers des classes contrôleurs et leurs méthodes.

## Installation

```bash
composer require mrsems/router-php
```

Cette commande installe automatiquement la dernière version stable compatible.

Pour cibler explicitement la version `1.2` :

```bash
composer require mrsems/router-php:^1.2
```

Sur Packagist, `dev-main` correspond à la branche de développement. Pour un projet stable, utilisez une version stable ou la contrainte `^1.2`.

## Initialisation

Dans votre fichier `index.php` :

```php
<?php
require 'vendor/autoload.php';

use App\Router\Router;

$routes = require 'routes.php';
$middlewares = file_exists('middlewares.php') ? require 'middlewares.php' : [];

Router::resolve($routes, $middlewares);
```

## Utilisation dans un projet procédural

Ce mode est adapté aux projets PHP sans classes contrôleurs.

### Routes vers des fonctions

Exemple de fichier `functions.php` :

```php
<?php
function accueil()
{
    echo "Bienvenue sur la page d'accueil !";
}

function afficherClient(array $params)
{
    echo "Client : " . $params['id'];
}
```

Chargez vos fonctions avant de lancer le routeur :

```php
<?php
require 'vendor/autoload.php';
require 'functions.php';

use App\Router\Router;

$routes = require 'routes.php';
$middlewares = require 'middlewares.php';

Router::resolve($routes, $middlewares);
```

Exemple de fichier `routes.php` :

```php
<?php
return [
    '/' => [
        'handler' => 'accueil',
        'methods' => ['GET'],
    ],
    'client/{id}' => [
        'handler' => 'afficherClient',
        'middlewares' => ['auth'],
        'methods' => ['GET'],
    ],
];
```

### Routes vers des fichiers PHP

Vous pouvez aussi rediriger une route vers un fichier PHP existant.

```php
<?php
return [
    '/' => [
        'file' => 'pages/accueil.php',
        'methods' => ['GET'],
    ],
    'client/{id}' => [
        'file' => 'pages/client.php',
        'middlewares' => ['auth'],
        'methods' => ['GET'],
    ],
];
```

Dans le fichier inclus, les paramètres de route sont disponibles dans `$params`.

Exemple de `pages/client.php` :

```php
<?php
echo "Client : " . $params['id'];
```

### Middlewares procéduraux

Exemple de fichier `middlewares.php` :

```php
<?php
return [
    'auth' => 'verifierAuth',
];
```

Exemple de fonction middleware :

```php
<?php
function verifierAuth()
{
    if (!isset($_SESSION['user'])) {
        header('Location: /login');
        exit;
    }
}
```

## Utilisation dans un projet orienté objet

Ce mode est adapté aux projets structurés avec des contrôleurs, middlewares et services.

### Routes vers des contrôleurs

Exemple de fichier `routes.php` :

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

Exemple de contrôleur :

```php
<?php
namespace App\Controllers;

class UserController
{
    public function index()
    {
        echo "Tableau de bord client";
    }

    public function show(array $params)
    {
        echo "Client : " . $params['id'];
    }
}
```

### Middlewares orientés objet

Exemple de fichier `middlewares.php` :

```php
<?php
return [
    'auth' => App\Middlewares\AuthMiddleware::class,
];
```

Exemple de middleware :

```php
<?php
namespace App\Middlewares;

class AuthMiddleware
{
    public function __invoke()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
    }
}
```

## Injection de dépendances

Le routeur peut instancier automatiquement les dépendances des contrôleurs grâce à la réflexion.

Exemple :

```php
<?php
namespace App\Controllers;

use App\Repositories\UserRepository;

class DashboardController
{
    private UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function index()
    {
        // ...
    }
}
```

Si un constructeur attend une interface, définissez le mapping interface vers classe concrète avant `Router::resolve()`.

```php
<?php
use App\Router\Router;

Router::setDependencyMap([
    App\Contracts\UserRepositoryInterface::class => App\Repositories\UserRepository::class,
]);
```

## Structure conseillée

### Projet procédural

```text
votre-projet/
├── vendor/
├── pages/
│   ├── accueil.php
│   └── client.php
├── functions.php
├── routes.php
├── middlewares.php
├── index.php
└── composer.json
```

### Projet orienté objet

```text
votre-projet/
├── vendor/
├── App/
│   ├── Controllers/
│   │   ├── SecurityController.php
│   │   └── UserController.php
│   ├── Middlewares/
│   │   └── AuthMiddleware.php
│   └── Repositories/
│       └── UserRepository.php
├── routes.php
├── middlewares.php
├── index.php
└── composer.json
```

## Format des routes

Chaque route peut utiliser l'un de ces formats.

### Fonction procédurale

```php
[
    'handler' => 'nomDeLaFonction',
    'methods' => ['GET'],
]
```

### Fichier PHP

```php
[
    'file' => 'pages/accueil.php',
    'methods' => ['GET'],
]
```

### Contrôleur

```php
[
    'controller' => App\Controllers\HomeController::class,
    'method' => 'index',
    'methods' => ['GET'],
]
```

## Fonctionnalités

- Support des méthodes HTTP : `GET`, `POST`, `PUT`, `DELETE`, etc.
- Routes avec paramètres : `client/{id}`.
- Middlewares par route.
- Support des fonctions procédurales.
- Support des fichiers PHP procéduraux.
- Support des contrôleurs orientés objet.
- Injection automatique de dépendances pour les contrôleurs.
- Mapping interface vers classe concrète.
