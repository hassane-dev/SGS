<?php

class View {
    public static function render(string $view, array $data = []): void {
        extract($data);
        $viewPath = __DIR__ . '/../views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            echo "Erreur : La vue '$view' est introuvable.";
            return;
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // Le layout principal enveloppe maintenant le contenu
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public static function renderCentered(string $view, array $data = []): void {
        extract($data);
        $viewPath = __DIR__ . '/../views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            echo "Erreur : La vue '$view' est introuvable.";
            return;
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // Le layout centré est un fichier autonome
        require_once __DIR__ . '/../views/layouts/header_centered.php';
    }
}