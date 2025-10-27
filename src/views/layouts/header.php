<!DOCTYPE html>
<?php
global $lang, $supported_languages;
// Determine language code and direction from the language setting
$lang_code = explode('_', $lang)[0]; // 'fr_FR' becomes 'fr'
$direction = $supported_languages[$lang]['dir'];
?>
<html lang="<?= $lang_code ?>" dir="<?= $direction ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Gestion Scolaire' ?></title>
    <?php if ($direction === 'rtl'): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="/"><?= APP_NAME ?? 'School Management' ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php if (Auth::check()): ?>
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="elevesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Élèves
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="elevesDropdown">
                        <?php if (Auth::can('inscrire', 'eleve')): ?>
                            <li><a class="dropdown-item" href="/eleves/create">Inscrire un nouvel élève</a></li>
                        <?php endif; ?>
                        <?php if (Auth::can('reinscrire', 'eleve')): ?>
                            <li><a class="dropdown-item" href="/reinscription">Réinscrire un ancien élève</a></li>
                        <?php endif; ?>
                        <?php if (Auth::can('view_all', 'eleve')): ?>
                            <li><a class="dropdown-item" href="/eleves">Voir la liste des élèves</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php if (Auth::can('note:create_own')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/evaluations/select_class">Saisir les Notes</a>
                </li>
                <?php endif; ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="pedagogieDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Pédagogie
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="pedagogieDropdown">
                        <?php if (Auth::can('class:view')): ?>
                            <li><a class="dropdown-item" href="/classes">Gestion des Classes</a></li>
                        <?php endif; ?>
                        <?php if (Auth::can('matiere:view')): ?>
                            <li><a class="dropdown-item" href="/matieres">Gestion des Matières</a></li>
                        <?php endif; ?>
                        <?php if (Auth::can('sequence:manage')): ?>
                            <li><a class="dropdown-item" href="/sequences">Gestion des Séquences</a></li>
                        <?php endif; ?>
                        <?php if (Auth::can('bulletin:generate')): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/bulletins">Générer les Bulletins</a></li>
                        <?php endif; ?>
                        <?php if (Auth::can('bulletin_template:manage')): ?>
                            <li><a class="dropdown-item" href="/modele-bulletin/edit">Personnaliser le Bulletin</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php if (Auth::can('manage_users')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/users">Utilisateurs</a>
                </li>
                <?php endif; ?>
                <?php if (Auth::can('manage_frais')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/frais">Grille Tarifaire</a>
                </li>
                <?php endif; ?>
                <?php if (Auth::can('manage_paiements')):
                    // Fetch notifications for the accountant
                    $unread_notifications = Notification::findUnreadByUser(Auth::get('id_user'));
                    $unread_count = count($unread_notifications);
                ?>
                <li class="nav-item">
                    <a class="nav-link" href="/comptable/pending">
                        Inscriptions en Attente
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-danger"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="paramsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cogs"></i> Paramètres
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="paramsDropdown">
                        <?php if (Auth::can('param_lycee:edit')): ?>
                            <li><a class="dropdown-item" href="/param-lycee/edit">Paramètres du Lycée</a></li>
                        <?php endif; ?>
                        <?php if (Auth::can('param_general:edit')): ?>
                            <li><a class="dropdown-item" href="/param-general/edit">Paramètres Généraux</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <?php if (Auth::can('param_devoir:edit')): ?>
                            <li><a class="dropdown-item" href="/param-devoir/edit">Paramètres des Devoirs</a></li>
                        <?php endif; ?>
                        <?php if (Auth::can('param_composition:edit')): ?>
                            <li><a class="dropdown-item" href="/param-composition/edit">Paramètres des Compositions</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-globe"></i> <?= $supported_languages[$lang]['name'] ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                        <?php foreach ($supported_languages as $code => $properties): ?>
                            <?php if ($code !== $lang): ?>
                                <li><a class="dropdown-item" href="?lang=<?= $code ?>"><?= $properties['name'] ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?= Auth::get('email') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="/logout">Déconnexion</a></li>
                    </ul>
                </li>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container mt-4">
    <?= $content ?? '' ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>