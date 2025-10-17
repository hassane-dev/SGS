<aside class="sidebar bg-dark text-white p-3">
    <div class="sidebar-header text-center mb-4">
        <img src="<?= htmlspecialchars($_SESSION['lycee_logo'] ?? '/img/default_logo.png') ?>" alt="Logo Lycée" class="img-fluid rounded-circle mb-2" style="width: 80px; height: 80px; object-fit: cover;">
        <h5><?= htmlspecialchars($_SESSION['lycee_name'] ?? 'Mon École') ?></h5>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link text-white" href="/dashboard"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>

        <?php if (Auth::isLoggedIn()): ?>
            <hr class="text-secondary">

            <h6 class="text-secondary ps-3">Académique</h6>
            <a class="nav-link text-white" href="/eleves"><i class="fas fa-user-graduate"></i> Élèves</a>
            <a class="nav-link text-white" href="/classes"><i class="fas fa-chalkboard-teacher"></i> Classes</a>
            <a class="nav-link text-white" href="/matieres"><i class="fas fa-book"></i> Matières</a>
            <?php if (Auth::can('manage_classes')): ?>
                <a class="nav-link text-white" href="/emploi-du-temps"><i class="far fa-calendar-alt"></i> Emploi du Temps</a>
            <?php endif; ?>
             <?php if (Auth::get('role_name') === 'enseignant'): ?>
                <a class="nav-link text-white" href="/notes"><i class="fas fa-edit"></i> Saisir les Notes</a>
                <a class="nav-link text-white" href="/cahier-texte"><i class="fas fa-book-open"></i> Cahier de Texte</a>
            <?php endif; ?>

            <?php if (Auth::can('manage_paiements')): ?>
                <hr class="text-secondary">
                <h6 class="text-secondary ps-3">Finance</h6>
                <a class="nav-link text-white" href="/inscriptions"><i class="fas fa-file-invoice-dollar"></i> Inscriptions</a>
                <a class="nav-link text-white" href="/comptable/pending"><i class="fas fa-clock"></i> Inscriptions en Attente</a>
                <a class="nav-link text-white" href="/paiements"><i class="fas fa-cash-register"></i> Mensualités</a>
                <a class="nav-link text-white" href="/salaires"><i class="fas fa-hand-holding-usd"></i> Gérer les Salaires</a>
            <?php endif; ?>

            <?php if (Auth::can('manage_users') || Auth::can('manage_roles') || Auth::can('manage_own_lycee_settings')): ?>
                <hr class="text-secondary">
                <h6 class="text-secondary ps-3">Administration</h6>
                <?php if (Auth::can('manage_users')): ?>
                    <a class="nav-link text-white" href="/users"><i class="fas fa-users-cog"></i> Utilisateurs</a>
                    <a class="nav-link text-white" href="/contrats"><i class="fas fa-file-signature"></i> Gérer les Contrats</a>
                <?php endif; ?>
                <?php if (Auth::can('manage_roles')): ?>
                    <a class="nav-link text-white" href="/roles"><i class="fas fa-user-tag"></i> Gérer les Rôles</a>
                <?php endif; ?>
                 <?php if (Auth::can('manage_frais')): ?>
                    <a class="nav-link text-white" href="/frais"><i class="fas fa-cogs"></i> Grille Tarifaire</a>
                <?php endif; ?>
                <?php if (Auth::can('manage_own_lycee_settings')): ?>
                    <a class="nav-link text-white" href="/settings"><i class="fas fa-cog"></i> Paramètres</a>
                    <a class="nav-link text-white" href="/modele-carte/edit"><i class="fas fa-id-card"></i> Éditeur de Carte</a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (in_array(Auth::get('role'), ['admin_local', 'super_admin_national'])): ?>
                 <a class="nav-link text-white" href="/boutique/articles"><i class="fas fa-store"></i> Gérer la Boutique</a>
            <?php endif; ?>

            <?php if (Auth::get('role') === 'super_admin_national'): ?>
                 <a class="nav-link text-white" href="/lycees"><i class="fas fa-school"></i> Gérer les Lycées</a>
            <?php endif; ?>

            <?php if (Auth::get('role') === 'super_admin_createur'): ?>
                <a class="nav-link text-white" href="/licences"><i class="fas fa-id-badge"></i> Gérer les Licences</a>
            <?php endif; ?>

        <?php endif; ?>
    </nav>
</aside>