<?php 
namespace App\Router;

class Router
{
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
        if (isset($routes[$uri])) {
            $route = $routes[$uri];
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
            $controller = new $controllerClass();
            if (!method_exists($controller, $action)) {
                http_response_code(500);
                echo "Méthode {$action} introuvable.";
                return;
            }
            $controller->$action();
        } else {
            http_response_code(404);
            echo "Page non trouvée.";
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
}