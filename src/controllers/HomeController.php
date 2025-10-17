<?php
require_once __DIR__ . '/../core/View.php';

class HomeController {
    public function index() {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }

        View::render('home/index', ['title' => 'Tableau de bord']);
    }
}