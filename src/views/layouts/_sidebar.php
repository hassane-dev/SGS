<div class="sidebar-header">
    <?php
    // We assume the lycee info is fetched and available globally for logged-in users
    // This would typically be loaded into the session or a global context
    $current_lycee_logo = Auth::get('lycee_logo') ?? '/assets/images/default_logo.png';
    $current_lycee_name = Auth::get('lycee_name') ?? 'Mon École';
    ?>
    <img src="<?= htmlspecialchars($current_lycee_logo) ?>" alt="Logo du Lycée" class="img-fluid rounded-circle mb-2">
    <h5><?= htmlspecialchars($current_lycee_name) ?></h5>
</div>

<ul class="list-unstyled components">
    <p>Navigation</p>
    <li class="active">
        <a href="/dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    </li>

    <?php if (Auth::can('view_classes') || Auth::can('manage_frais')): ?>
    <li>
        <a href="#academicsSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
            <i class="fas fa-school"></i> Académique
        </a>
        <ul class="collapse list-unstyled" id="academicsSubmenu">
            <?php if (Auth::can('view_classes')): ?>
                <li><a href="/classes">Classes</a></li>
            <?php endif; ?>
            <?php if (Auth::can('manage_frais')): ?>
                <li><a href="/frais">Grille Tarifaire</a></li>
            <?php endif; ?>
        </ul>
    </li>
    <?php endif; ?>

    <?php if (Auth::can('view_eleves')): ?>
    <li>
        <a href="/eleves"><i class="fas fa-user-graduate"></i> Élèves</a>
    </li>
    <?php endif; ?>

    <?php if (Auth::can('manage_paiements')): ?>
    <li>
        <a href="/comptable/pending">
            <i class="fas fa-file-invoice-dollar"></i> Inscriptions en Attente
            <?php
                $unread_notifications = Notification::findUnreadByUser(Auth::get('id_user'));
                $unread_count = count($unread_notifications);
                if ($unread_count > 0) {
                    echo ' <span class="badge bg-danger">' . $unread_count . '</span>';
                }
            ?>
        </a>
    </li>
    <?php endif; ?>

    <?php if (Auth::can('manage_users')): ?>
    <li>
        <a href="/users"><i class="fas fa-users"></i> Utilisateurs</a>
    </li>
    <?php endif; ?>

    <?php if (Auth::can('manage_roles')): ?>
    <li>
        <a href="/roles"><i class="fas fa-user-shield"></i> Rôles & Permissions</a>
    </li>
    <?php endif; ?>
</ul>