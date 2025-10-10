<?php

class HomeController {
    public function index() {
        // The main dashboard view now handles the display of navigation links
        // based on the user's permissions.
        require_once __DIR__ . '/../views/home/index.php';
    }
}
?>