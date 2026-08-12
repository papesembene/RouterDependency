# Router-PHP

Un routeur PHP simple, léger et réutilisable pour vos applications web.

Il fonctionne avec deux types de projets :

- Projet PHP procédural : routes vers des fonctions ou des fichiers PHP.
- Projet PHP orienté objet : routes vers des classes contrôleurs.

## Installation

```bash
composer require mrsems/router-php
```

Cette commande installe automatiquement la dernière version stable compatible.

Vous pouvez aussi cibler la version `1.3` :

```bash
composer require mrsems/router-php:^1.3
```

Sur Packagist, `dev-main` correspond à la branche de développement. Pour un projet normal, utilisez une version stable.

## Fichiers utilisés

Le routeur utilise généralement ces fichiers :

- `index.php` : point d'entrée de votre application.
- `routes.php` : liste des routes de votre application.
- `middlewares.php` : liste des middlewares, optionnel.
- `vendor/autoload.php` : fichier généré par Composer.

La ligne suivante charge Composer :

```php
require 'vendor/autoload.php';
```

Elle permet à PHP de trouver automatiquement le routeur installé dans le dossier `vendor/`.

## Projet procédural

Utilisez ce mode si votre projet PHP fonctionne avec des fonctions ou des fichiers PHP simples.

### Étape 1 : créer `index.php`

```php
<?php
require 'vendor/autoload.php';
require 'functions.php';

use App\Router\Router;

$routes = require 'routes.php';
$middlewares = file_exists('middlewares.php') ? require 'middlewares.php' : [];

Router::resolve($routes, $middlewares);
```

Le fichier `middlewares.php` est optionnel. Si vous n'utilisez pas de middleware, vous pouvez ne pas le créer.

Le fichier `functions.php` est nécessaire seulement si vos routes appellent des fonctions. Si vos routes utilisent uniquement `file`, vous pouvez le retirer.

### Étape 2 : créer `functions.php`

Mettez ici vos fonctions PHP.

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

function verifierAuth()
{
    if (!isset($_SESSION['user'])) {
        header('Location: /login');
        exit;
    }
}
```

La fonction `verifierAuth()` sert seulement si vous utilisez un middleware.

### Étape 3 : créer `routes.php`

Mettez ici les routes de votre application.

```php
<?php
return [
    '/' => [
        'function' => 'accueil',
        'methods' => ['GET'],
    ],
    'client/{id}' => [
        'function' => 'afficherClient',
        'middlewares' => ['auth'],
        'methods' => ['GET'],
    ],
];
```

`function` contient le nom de la fonction à appeler.

La clé `handler` reste acceptée pour compatibilité, mais `function` est plus simple pour un projet procédural.

La clé `middlewares` est optionnelle. Ajoutez-la seulement si la route doit passer par un middleware.

Pour une route simple en `GET`, vous pouvez aussi écrire directement le nom de la fonction :

```php
<?php
return [
    '/' => 'accueil',
    'client/{id}' => 'afficherClient',
];
```

### Étape 4 : créer `middlewares.php` si nécessaire

Ce fichier est optionnel.

Créez-le seulement si vous utilisez des middlewares dans vos routes.

```php
<?php
return [
    'auth' => 'verifierAuth',
];
```

Ici, `auth` est le nom utilisé dans `routes.php`, et `verifierAuth` est la fonction appelée.

### Variante : route vers un fichier PHP

Au lieu d'appeler une fonction, une route peut inclure un fichier PHP.

```php
<?php
return [
    '/' => [
        'file' => 'pages/accueil.php',
        'methods' => ['GET'],
    ],
    'client/{id}' => [
        'file' => 'pages/client.php',
        'methods' => ['GET'],
    ],
];
```

Dans `pages/client.php`, les paramètres de route sont disponibles dans `$params`.

```php
<?php
echo "Client : " . $params['id'];
```

Structure possible :

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

## Projet orienté objet

Utilisez ce mode si votre projet PHP fonctionne avec des classes contrôleurs.

### Étape 1 : créer `index.php`

```php
<?php
require 'vendor/autoload.php';

use App\Router\Router;

$routes = require 'routes.php';
$middlewares = file_exists('middlewares.php') ? require 'middlewares.php' : [];

Router::resolve($routes, $middlewares);
```

Le fichier `middlewares.php` est optionnel.

### Étape 2 : créer `routes.php`

```php
<?php
return [
    '/' => [
        'controller' => App\Controllers\SecurityController::class,
        'method' => 'login',
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

`controller` contient la classe à utiliser.

`method` contient la méthode à appeler dans le contrôleur.

La clé `middlewares` est optionnelle.

### Étape 3 : créer le contrôleur

Exemple de fichier `App/Controllers/UserController.php` :

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

### Étape 4 : créer `middlewares.php` si nécessaire

Ce fichier est optionnel.

```php
<?php
return [
    'auth' => App\Middlewares\AuthMiddleware::class,
];
```

### Étape 5 : créer le middleware si nécessaire

Exemple de fichier `App/Middlewares/AuthMiddleware.php` :

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

Structure possible :

```text
votre-projet/
├── vendor/
├── App/
│   ├── Controllers/
│   │   ├── SecurityController.php
│   │   └── UserController.php
│   └── Middlewares/
│       └── AuthMiddleware.php
├── routes.php
├── middlewares.php
├── index.php
└── composer.json
```

## Injection de dépendances

Le routeur peut instancier automatiquement les dépendances des contrôleurs.

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

Si un constructeur attend une interface, définissez le mapping avant `Router::resolve()`.

```php
<?php
use App\Router\Router;

Router::setDependencyMap([
    App\Contracts\UserRepositoryInterface::class => App\Repositories\UserRepository::class,
]);
```

## Récapitulatif des routes

Route vers une fonction :

```php
[
    'function' => 'nomDeLaFonction',
    'methods' => ['GET'],
]
```

Route simple vers une fonction :

```php
'/' => 'nomDeLaFonction'
```

Route vers un fichier :

```php
[
    'file' => 'pages/accueil.php',
    'methods' => ['GET'],
]
```

Route vers un contrôleur :

```php
[
    'controller' => App\Controllers\HomeController::class,
    'method' => 'index',
    'methods' => ['GET'],
]
```

Route avec middleware :

```php
[
    'function' => 'accueil',
    'middlewares' => ['auth'],
    'methods' => ['GET'],
]
```

## Fonctionnalités

- Support des méthodes HTTP : `GET`, `POST`, `PUT`, `DELETE`, etc.
- Routes avec paramètres : `client/{id}`.
- Middlewares optionnels par route.
- Support des fonctions procédurales.
- Support des fichiers PHP procéduraux.
- Support des contrôleurs orientés objet.
- Injection automatique de dépendances pour les contrôleurs.
- Mapping interface vers classe concrète.
