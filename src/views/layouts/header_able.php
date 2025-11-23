<?php
// Ensure user is authenticated before proceeding
if (!Auth::check()) {
    // Redirect to login or show an error, but for a layout, we assume user is logged in.
    // This is mainly a safeguard.
    return;
}

// --- Data Loading for Dynamic Header ---

// Load necessary models
require_once __DIR__ . '/../../models/Lycee.php';
require_once __DIR__ . '/../../models/AnneeAcademique.php';
require_once __DIR__ . '/../../models/ParamLycee.php';
require_once __DIR__ . '/../../models/Notification.php';

// Get current user info
$current_user = Auth::user();
$user_role = $current_user['role_name'] ?? 'N/A';
$user_avatar = $current_user['photo'] ?? '/assets/img/default-avatar.png';
$user_name = $current_user['prenom'] . ' ' . $current_user['nom'];

// --- Active Lycee & Availability Logic ---

$active_lycee_id = Auth::getLyceeId();
$active_lycee = null;
$available_lycees = [];

// Determine all lycees available to the current user
if (Auth::can('view_all_lycees', 'lycee')) {
    $available_lycees = Lycee::findAll();
} elseif ($active_lycee_id) {
    // A regular user is tied to one lycee
    $potential_lycee = Lycee::findById($active_lycee_id);
    if ($potential_lycee) {
        $available_lycees[] = $potential_lycee;
    }
}

// Determine the active lycee for the current request
if ($active_lycee_id) {
    $active_lycee = Lycee::findById($active_lycee_id);
}

// If no lycee is active (e.g., super admin first login), default to the first available one
if (!$active_lycee && !empty($available_lycees)) {
    $active_lycee = $available_lycees[0];
    $active_lycee_id = $active_lycee['id_lycee'];
}

// Fetch params for the now-determined active lycee
$lycee_params = $active_lycee_id ? ParamLycee::findByLyceeId($active_lycee_id) : [];
$is_multilycee = count($available_lycees) > 1;

// Get active academic year
$active_year = AnneeAcademique::findActive();
$all_years = AnneeAcademique::findAll();

// Get active sequence for the current lycee
$active_sequence = $lycee_params['sequence_annuelle'] ?? _('Non définie');

// Get unread notifications
$unread_notifications = Notification::findUnreadByUser($current_user['id']);
$notification_count = count($unread_notifications);

// --- End Data Loading ---
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Gestion Scolaire' ?></title>
    <link rel="icon" href="<?= htmlspecialchars($lycee_params['logo'] ?? '/assets/img/favicon.svg') ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" id="main-font-link" />
    <link rel="stylesheet" href="/assets/css/style.css" id="main-style-link" >
    <link rel="stylesheet" href="/assets/css/style-preset.css" >
    <style>
        .pc-sidebar .navbar-content {
            height: calc(100vh - 70px); /* Adjust 70px to match the header's height */
            overflow-y: auto;
            padding-bottom: 20px;
        }
    </style>
</head>
<body>
<!-- [ Pre-loader ] start -->
<div class="loader-bg">
    <div class="loader-track">
        <div class="loader-fill"></div>
    </div>
</div>
<!-- [ Pre-loader ] End -->

<?php require_once __DIR__ . '/sidebar_able.php'; ?>

<header class="pc-header">
    <div class="header-wrapper">
        <!-- [Mobile Media Block] start -->
        <div class="me-auto pc-mob-drp">
            <ul class="list-unstyled">
                <li class="pc-h-item pc-sidebar-collapse">
                    <a href="#" class="pc-head-link ms-0" id="sidebar-hide">
                        <i class="ph-duotone ph-list"></i>
                    </a>
                </li>
                <li class="pc-h-item pc-sidebar-popup">
                    <a href="#" class="pc-head-link ms-0" id="mobile-collapse">
                        <i class="ph-duotone ph-list"></i>
                    </a>
                </li>
            </ul>
        </div>
        <!-- [Mobile Media Block] end -->

        <!-- Left Section: School Info -->
        <div class="header-logo me-auto">
             <?php if ($is_multilycee): ?>
                <div class="dropdown">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <img src="<?= htmlspecialchars($lycee_params['logo'] ?? '/assets/img/favicon.svg') ?>" alt="logo" class="logo-lg" style="height: 30px;">
                        <span class="ms-2"><?= htmlspecialchars($active_lycee['nom_lycee'] ?? _('Aucun lycée configuré')) ?></span>
                    </a>
                    <div class="dropdown-menu">
                        <?php foreach ($available_lycees as $lycee): ?>
                            <a href="/settings/change-lycee?id=<?= $lycee['id_lycee'] ?>" class="dropdown-item"><?= htmlspecialchars($lycee['nom_lycee']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <img src="<?= htmlspecialchars($lycee_params['logo'] ?? '/assets/img/favicon.svg') ?>" alt="logo" class="logo-lg" style="height: 30px;">
                <span class="ms-2"><?= htmlspecialchars($active_lycee['nom_lycee'] ?? _('Aucun lycée configuré')) ?></span>
            <?php endif; ?>
        </div>

        <!-- Right Section: Actions & Profile -->
        <div class="ms-auto">
            <ul class="list-unstyled">
                <!-- Academic Year Selector -->
                <li class="dropdown pc-h-item">
                     <a class="pc-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <i class="ph-duotone ph-calendar-blank"></i>
                        <span><?= _('Année') ?>: <?= htmlspecialchars($active_year['libelle'] ?? 'N/A') ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
                        <?php foreach ($all_years as $year): ?>
                            <a href="/settings/change-year?id=<?= $year['id'] ?>" class="dropdown-item <?= $year['id'] == $active_year['id'] ? 'active' : '' ?>"><?= htmlspecialchars($year['libelle']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </li>

                <!-- Sequence Indicator -->
                <li class="pc-h-item">
                    <span class="pc-head-link me-0">
                        <i class="ph-duotone ph-flag"></i>
                        <span><?= _('Séquence') ?>: <?= htmlspecialchars($active_sequence) ?></span>
                    </span>
                </li>

                <!-- Language Selector -->
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <i class="ph-duotone ph-translate"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
                        <a href="/settings/change-language?lang=fr" class="dropdown-item"><span>Français</span></a>
                        <a href="/settings/change-language?lang=en" class="dropdown-item"><span>English</span></a>
                        <a href="/settings/change-language?lang=ar" class="dropdown-item"><span>العربية</span></a>
                    </div>
                </li>

                <!-- Notifications -->
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <i class="ph-duotone ph-bell"></i>
                        <?php if ($notification_count > 0): ?>
                            <span class="badge bg-danger pc-h-badge"><?= $notification_count ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
                        <div class="dropdown-header">
                            <a href="/notifications/mark-all-as-read" class="text-muted float-end"><?= _('Tout marquer comme lu') ?></a>
                            <h5 class="m-0"><?= _('Notifications') ?></h5>
                        </div>
                        <div class="dropdown-divider"></div>
                        <?php if ($notification_count > 0): ?>
                            <?php foreach ($unread_notifications as $notif): ?>
                                <a href="/notifications/mark-as-read?id=<?= $notif['id'] ?>&redirect_to=<?= urlencode($notif['link']) ?>" class="dropdown-item">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <i class="ph-duotone ph-warning-circle"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <p class="mb-0"><?= htmlspecialchars($notif['message']) ?></p>
                                            <small class="text-muted"><?= htmlspecialchars($notif['created_at']) ?></small>
                                        </div>
                                    </div>
                                </a>
                                <div class="dropdown-divider"></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="py-3 text-center">
                                <p><?= _('Aucune nouvelle notification') ?></p>
                            </div>
                        <?php endif; ?>
                        <div class="text-center py-2">
                            <a href="/notifications/all" class="link-primary"><?= _('Voir toutes les notifications') ?></a>
                        </div>
                    </div>
                </li>

                <!-- User Profile -->
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <img src="<?= htmlspecialchars($user_avatar) ?>" alt="user-image" class="user-avtar" />
                        <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
                        <span class="badge bg-primary ms-2"><?= htmlspecialchars($user_role) ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
                        <a href="/profile" class="dropdown-item">
                            <i class="ph-duotone ph-user"></i>
                            <span><?= _('Mon Profil') ?></span>
                        </a>
                        <a href="/logout" class="dropdown-item">
                            <i class="ph-duotone ph-power"></i>
                            <span><?= _('Déconnexion') ?></span>
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</header>
</body>
</html>
