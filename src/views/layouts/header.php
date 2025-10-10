<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?? _('School Management') ?></title>
    <!-- Bootstrap CSS -->
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* You can add custom styles here if needed */
        body {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <header class="pb-3 mb-4 border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h2"><?= APP_NAME ?? _('School Management') ?></h1>
                <p class="text-muted"><?= _('Admin Interface') ?></p>
            </div>
            <div class="text-sm">
                <a href="?lang=fr_FR">FR</a> |
                <a href="?lang=en_US">EN</a>
            </div>
        </div>
    </header>

    <main>
        <!-- Main content will be injected here -->
