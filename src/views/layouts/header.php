<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Gestion Scolaire' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <?php if (Auth::isLoggedIn()): ?>
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
            </ul>
            <ul class="navbar-nav">
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