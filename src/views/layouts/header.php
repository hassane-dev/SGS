<header class="main-header navbar navbar-expand-lg navbar-light bg-light border-bottom">
    <div class="container-fluid">
        <!-- Bouton pour afficher/cacher la sidebar sur mobile -->
        <button type="button" id="sidebarCollapse" class="btn btn-dark d-md-none me-3">
            <i class="fas fa-align-left"></i>
        </button>

        <div class="d-flex align-items-center">
            <img src="<?= htmlspecialchars($_SESSION['lycee_logo'] ?? '/img/default_logo.png') ?>" alt="Logo" style="height: 40px; margin-right: 10px;">
            <span class="navbar-brand mb-0 h1 d-none d-sm-block"><?= htmlspecialchars($_SESSION['lycee_name'] ?? 'Mon École') ?></span>
        </div>

        <div class="mx-auto">
            <!-- Boutons du milieu (Dashboard, etc.) -->
            <a href="/dashboard" class="btn btn-outline-primary"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
        </div>

        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-cog"></i>
                    <span class="d-none d-sm-inline ms-1"><?= Auth::get('email') ?? 'Utilisateur' ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="/profile">Mon profil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/logout">Déconnexion</a></li>
                </ul>
            </li>
        </ul>
    </div>
</header>