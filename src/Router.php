<?php 
namespace App\Router;

class Router
{
    private static array $dependencyMap = [];

    /**
     * Résout la route en fonction de la méthode HTTP et de l'URI.
     *
     * @param array $routes Les routes définies pour chaque méthode HTTP.
     * @param array $middlewares Les middlewares disponibles.
     * @return void
     */
    public static function resolve(array $routes, array $middlewares = []): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = self::normalizeUri($_SERVER['REQUEST_URI'] ?? '/');

        $routes = self::prepareRoutes($routes);
        
        // Chercher d'abord une correspondance exacte
        if (isset($routes[$uri])) {
            self::executeRoute($routes[$uri], $method, [], $middlewares);
            return;
        }

        // Chercher une correspondance avec des paramètres
        foreach ($routes as $pattern => $route) {
            $params = self::matchRoute($pattern, $uri);
            if ($params !== false) {
                self::executeRoute($route, $method, $params, $middlewares);
                return;
            }
        }

        // Aucune route trouvée
        http_response_code(404);
        echo "Page non trouvée.";
    }

    /**
     * Vérifie si une URI correspond à un pattern de route et extrait les paramètres.
     *
     * @param string $pattern Le pattern de la route (ex: "citoyens/{id}")
     * @param string $uri L'URI à vérifier
     * @return array|false Les paramètres extraits ou false si pas de correspondance
     */
    private static function matchRoute(string $pattern, string $uri)
    {
        // Convertir le pattern en regex
        $regex = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            array_shift($matches); // Supprimer la correspondance complète
            
            // Extraire les noms des paramètres du pattern
            preg_match_all('/\{([^}]+)\}/', $pattern, $paramNames);
            $paramNames = $paramNames[1];
            
            // Associer les noms aux valeurs
            $params = [];
            foreach ($paramNames as $index => $name) {
                $params[$name] = $matches[$index] ?? null;
            }
            
            return $params;
        }

        return false;
    }

    /**
     * Exécute une route avec les paramètres donnés.
     *
     * @param array $route La configuration de la route
     * @param string $method La méthode HTTP
     * @param array $params Les paramètres extraits de l'URI
     * @param array $middlewares Les middlewares disponibles
     * @return void
     */
    private static function executeRoute(array $route, string $method, array $params, array $middlewares): void
    {
        $allowedMethods = $route['methods'] ?? ['GET'];
        if (!in_array($method, $allowedMethods)) {
            http_response_code(405);
            echo "Méthode non autorisée.";
            return;
        }

        if (!empty($route['middlewares'])) {
            self::runMiddlewares($route['middlewares'], $middlewares);
        }

        $controllerClass = $route['controller'];
        $action = $route['method'];

        if (!class_exists($controllerClass)) {
            http_response_code(500);
            echo "Contrôleur {$controllerClass} introuvable.";
            return;
        }

        $controller = self::instantiateWithDependencies($controllerClass);
        if (!method_exists($controller, $action)) {
            http_response_code(500);
            echo "Méthode {$action} introuvable.";
            return;
        }

        // Passer les paramètres à la méthode du contrôleur
        if (!empty($params)) {
            $controller->$action($params);
        } else {
            $controller->$action();
        }
    }

    /**
     * Normalise l'URI en supprimant les barres obliques de début et de fin.
     *
     * @param string $uri L'URI à normaliser.
     * @return string L'URI normalisée.
     */
    private static function normalizeUri(string $uri): string
    {
        return trim(parse_url($uri, PHP_URL_PATH), '/');
    }

    /**
     * Prépare les routes en nettoyant les chemins.
     *
     * @param array $routes Les routes à préparer.
     * @return array Les routes préparées.
     */
    private static function prepareRoutes(array $routes): array
    {
        $cleanRoutes = [];
        foreach ($routes as $path => $route) {
            $cleanRoutes[trim($path, '/')] = $route;
        }
        return $cleanRoutes;
    }

    /**
     * Exécute les middlewares associés à la route.
     *
     * @param array $routeMiddlewares Les middlewares définis pour la route.
     * @param array $availableMiddlewares Les middlewares disponibles.
     * @return void
     */
    private static function runMiddlewares(array $routeMiddlewares, array $availableMiddlewares): void
    {
        foreach ($routeMiddlewares as $middlewareName) {
            if (isset($availableMiddlewares[$middlewareName])) {
                $class = $availableMiddlewares[$middlewareName];
                if (class_exists($class)) {
                    $middleware = new $class();
                    if (is_callable($middleware)) {
                        $middleware();
                    }
                }
            }
        }
    }

    /**
     * Instancie une classe avec ses dépendances en utilisant la réflexion.
     *
     * @param string $class Le nom de la classe à instancier.
     * @return object Une instance de la classe.
     */
    private static function instantiateWithDependencies(string $class)
    {
        // Si c'est une interface, on cherche dans le mapping utilisateur
        if (interface_exists($class)) {
            if (isset(self::$dependencyMap[$class])) {
                $class = self::$dependencyMap[$class];
            } else {
                // Sinon, tente la convention de nommage (optionnel)
                $concrete = preg_replace('/\\\I([A-Z])/', '\\\$1', $class);
                if (class_exists($concrete)) {
                    $class = $concrete;
                } else {
                    throw new \Exception("Impossible de résoudre l'implémentation concrète pour l'interface $class");
                }
            }
        }

        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if (!$constructor || $constructor->getNumberOfParameters() === 0) {
            return new $class();
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                $depClass = $type->getName();
                $dependencies[] = self::instantiateWithDependencies($depClass);
            } else {
                $dependencies[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            }
        }
        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Permet à l'utilisateur de définir ses propres mappings interface => classe concrète
     */
    public static function setDependencyMap(array $map): void
    {
        self::$dependencyMap = $map;
    }
}