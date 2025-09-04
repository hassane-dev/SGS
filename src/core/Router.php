<?php

class Router {
    protected $routes = [];

    public function register($route, $controller, $action) {
        $this->routes[$route] = ['controller' => $controller, 'action' => $action];
    }

    public function dispatch($uri) {
        if (array_key_exists($uri, $this->routes)) {
            $controller = $this->routes[$uri]['controller'];
            $action = $this->routes[$uri]['action'];

            // NOTE: This is a simplified example. A real router would be more complex.
            // For now, we assume controller files are in src/controllers/
            require_once __DIR__ . '/../controllers/' . $controller . '.php';

            $controllerInstance = new $controller();
            $controllerInstance->$action();

        } else {
            // Handle 404 Not Found
            http_response_code(404);
            echo "<h1>404 Not Found</h1>";
            echo "The page you are looking for could not be found.";
            exit();
        }
    }
}
?>
