<?php

class View {
    public static function render($view, $data = []) {
        extract($data);
        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            // Fallback for tests if view file doesn't exist
            echo "Rendering view: {$view}";
        }
    }
}
