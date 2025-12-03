<?php

class Router {
    protected $routes = [];

    public function register($route, $controller, $action) {
        // Convertit la route en une expression régulière pour capturer les paramètres
        // Par exemple: /paiements/show/{eleveId} devient #^/paiements/show/(\w+)$#
        $pattern = preg_replace('/\{(\w+)\}/', '(\w+)', $route);
        $pattern = '#^' . $pattern . '$#';
        $this->routes[$pattern] = ['controller' => $controller, 'action' => $action];
    }

    public function dispatch($uri) {
        foreach ($this->routes as $pattern => $route) {
            if (preg_match($pattern, $uri, $matches)) {
                // Supprime la correspondance complète ($matches[0]) pour ne garder que les paramètres
                array_shift($matches);
                $params = $matches;

                $controller = $route['controller'];
                $action = $route['action'];

                require_once __DIR__ . '/../controllers/' . $controller . '.php';

                $controllerInstance = new $controller();

                // Appelle la méthode du contrôleur avec les paramètres extraits
                call_user_func_array([$controllerInstance, $action], $params);
                return;
            }
        }

        // Handle 404 Not Found
        http_response_code(404);
        require_once __DIR__ . '/../core/View.php';
        View::render('errors/404');
        exit();
    }
}
?>
