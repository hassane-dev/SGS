<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?? 'Gestion des Lycées' ?></title>
    <!-- Link to local Tailwind CSS -->
    <link href="/assets/css/tailwind.css" rel="stylesheet">
    <style>
        /* You can add custom styles here if needed */
        body {
            background-color: #f4f7f6;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
    </style>
</head>
<body class="font-sans">

<div class="container">
    <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800"><?= APP_NAME ?? 'Gestion des Lycées' ?></h1>
        <p class="text-gray-600">Interface d'administration</p>
    </header>

    <main>
        <!-- Main content will be injected here -->
