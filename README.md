# Router-PHP

Un routeur PHP simple, léger et réutilisable pour vos applications web.

Il fonctionne avec deux types de projets :

- Projet PHP procédural MVC : routes vers des fonctions contrôleurs.
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

## Projet procédural MVC

Utilisez ce mode si votre projet PHP est organisé en MVC, mais sans classes.

Dans ce style :

- `controllers/` contient les fonctions qui traitent les routes.
- `models/` contient les fonctions liées aux données.
- `views/` contient l'affichage HTML.
- `routes.php` relie une URL à une fonction contrôleur.

### Étape 1 : créer `index.php`

```php
<?php
require 'vendor/autoload.php';
require 'controllers/PageController.php';
require 'controllers/ClientController.php';

use App\Router\Router;

$routes = require 'routes.php';
$middlewares = file_exists('middlewares.php') ? require 'middlewares.php' : [];

Router::resolve($routes, $middlewares);
```

Le fichier `middlewares.php` est optionnel. Si le fichier n'existe pas, le routeur continue avec un tableau vide.

### Étape 2 : créer les contrôleurs procéduraux

Un contrôleur procédural est un fichier qui contient des fonctions.

Exemple de fichier `controllers/PageController.php` :

```php
<?php
function accueil()
{
    require 'views/accueil.php';
}
```

Exemple de fichier `controllers/ClientController.php` :

```php
<?php
require_once 'models/ClientModel.php';

function afficherClient(array $params)
{
    $client = trouverClient($params['id']);

    require 'views/client.php';
}
```

Ici, la fonction contrôleur prépare les données, puis charge la vue.

### Étape 3 : créer le modèle

Un modèle procédural contient les fonctions liées aux données.

Exemple de fichier `models/ClientModel.php` :

```php
<?php
function trouverClient($id)
{
    return [
        'id' => $id,
        'nom' => 'Client exemple',
    ];
}
```

### Étape 4 : créer `routes.php`

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

`function` contient le nom de la fonction contrôleur à appeler.

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

### Étape 5 : créer les vues

Exemple de fichier `views/accueil.php` :

```php
<h1>Bienvenue sur la page d'accueil</h1>
```

Exemple de fichier `views/client.php` :

```php
<h1>Client <?= htmlspecialchars($client['id']) ?></h1>
<p>Nom : <?= htmlspecialchars($client['nom']) ?></p>
```

### Étape 6 : créer `middlewares.php` si nécessaire

Ce fichier est optionnel.

Créez-le seulement si vous utilisez des middlewares dans vos routes.

```php
<?php
function verifierAuth()
{
    if (!isset($_SESSION['user'])) {
        header('Location: /login');
        exit;
    }
}

return [
    'auth' => 'verifierAuth',
];
```

Ici, `auth` est le nom utilisé dans `routes.php`, et `verifierAuth` est la fonction appelée.

Structure possible :

```text
votre-projet/
├── vendor/
├── controllers/
│   ├── PageController.php
│   └── ClientController.php
├── models/
│   └── ClientModel.php
├── views/
│   ├── accueil.php
│   └── client.php
├── routes.php
├── middlewares.php
├── index.php
└── composer.json
```

### Variante : route directe vers un fichier PHP

Cette variante existe, mais pour garder une structure MVC propre, préférez les fonctions contrôleurs.

```php
<?php
return [
    '/' => [
        'file' => 'views/accueil.php',
        'methods' => ['GET'],
    ],
];
```

## Projet orienté objet

Utilisez ce mode si votre projet PHP fonctionne avec des classes contrôleurs.

### Étape 1 : configurer l'autoload de votre projet

Dans le `composer.json` de votre projet, ajoutez l'autoload de vos classes :

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "App/"
        }
    }
}
```

Puis lancez :

```bash
composer dump-autoload
```

Cette étape permet à PHP de trouver vos contrôleurs, par exemple `App\Controllers\UserController`.

### Étape 2 : créer `index.php`

```php
<?php
require 'vendor/autoload.php';

use App\Router\Router;

$routes = require 'routes.php';
$middlewares = file_exists('middlewares.php') ? require 'middlewares.php' : [];

Router::resolve($routes, $middlewares);
```

Le fichier `middlewares.php` est optionnel.

### Étape 3 : créer `routes.php`

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

### Étape 4 : créer le contrôleur

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

### Étape 5 : créer `middlewares.php` si nécessaire

Ce fichier est optionnel.

```php
<?php
return [
    'auth' => App\Middlewares\AuthMiddleware::class,
];
```

### Étape 6 : créer le middleware si nécessaire

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
