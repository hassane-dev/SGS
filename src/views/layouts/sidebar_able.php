<?php
// Define the navigation menu structure
$navItems = [
    [
        'label' => _('Navigation'),
        'is_caption' => true,
    ],
    [
        'url' => '/',
        'icon' => 'ph-duotone ph-house',
        'text' => _('Tableau de Bord'),
        'title' => _('Affiche la page d\'accueil avec les statistiques principales.'),
        'condition' => Auth::check(),
    ],
    [
        'label' => _('Gestion'),
        'is_caption' => true,
        'condition' => Auth::can('manage', 'user') || Auth::can('manage', 'class') || Auth::can('manage', 'role') || Auth::can('create', 'inscription'),
    ],
    [
        'url' => '/users',
        'icon' => 'ph-duotone ph-users',
        'text' => _('Personnel'),
        'title' => _('Gérer les membres du personnel, leurs rôles et leurs accès.'),
        'condition' => Auth::can('view', 'user'),
    ],
    [
        'url' => '/roles',
        'icon' => 'ph-duotone ph-user-list',
        'text' => _('Rôles'),
        'title' => _('Gérer les rôles et les permissions associées.'),
        'condition' => Auth::can('manage', 'role'),
    ],
    [
        'url' => '/eleves',
        'icon' => 'ph-duotone ph-student',
        'text' => _('Élèves'),
        'title' => _('Gérer les dossiers des élèves, inscriptions et archives.'),
        'condition' => Auth::can('view', 'eleve'),
    ],
    [
        'url' => '/inscriptions',
        'icon' => 'ph-duotone ph-user-plus',
        'text' => _('Inscriptions'),
        'title' => _('Gérer les inscriptions des nouveaux élèves.'),
        'condition' => Auth::can('create', 'inscription'),
    ],
    [
        'url' => '/reinscription',
        'icon' => 'ph-duotone ph-user-switch',
        'text' => _('Réinscriptions'),
        'title' => _('Gérer les réinscriptions des élèves existants.'),
        'condition' => Auth::can('create', 'inscription'),
    ],
    [
        'url' => '/classes',
        'icon' => 'ph-duotone ph-chalkboard-teacher',
        'text' => _('Classes'),
        'title' => _('Gérer les classes, les matières et les enseignants associés.'),
        'condition' => Auth::can('view', 'class'),
    ],
    [
        'url' => '/matieres',
        'icon' => 'ph-duotone ph-books',
        'text' => _('Matières'),
        'title' => _('Gérer la liste des matières enseignées.'),
        'condition' => Auth::can('view', 'matiere'),
    ],
    [
        'label' => _('Pédagogie'),
        'is_caption' => true,
        'condition' => Auth::get('role_name') === 'enseignant' || Auth::can('manage', 'class') || Auth::can('view', 'bulletin'),
    ],
    [
        'url' => '/emploi-du-temps',
        'icon' => 'ph-duotone ph-calendar',
        'text' => _('Emploi du Temps'),
        'title' => _('Configurer et consulter les emplois du temps.'),
        'condition' => Auth::can('manage', 'class'),
    ],
    [
        'url' => '/cahier-texte',
        'icon' => 'ph-duotone ph-book-open-text',
        'text' => _('Cahier de Texte'),
        'title' => _('Remplir et consulter le cahier de texte.'),
        'condition' => Auth::get('role_name') === 'enseignant' || Auth::can('view', 'cahier_texte'),
    ],
    [
        'url' => '/evaluations',
        'icon' => 'ph-duotone ph-graduation-cap',
        'text' => _('Évaluations'),
        'title' => _('Saisir et consulter les notes des élèves.'),
        'condition' => Auth::can('create', 'evaluation') || Auth::can('view', 'evaluation'),
    ],
    [
        'url' => '/bulletins',
        'icon' => 'ph-duotone ph-file-text',
        'text' => _('Bulletins'),
        'title' => _('Générer et consulter les bulletins de notes.'),
        'condition' => Auth::can('view', 'bulletin'),
    ],
    [
        'label' => _('Administration'),
        'is_caption' => true,
        'condition' => Auth::can('view_all_lycees', 'lycee') || Auth::can('manage', 'user') || Auth::can('manage', 'settings'),
    ],
    [
        'url' => '/lycees',
        'icon' => 'ph-duotone ph-school',
        'text' => _('Lycées'),
        'title' => _('Gérer les différents établissements scolaires.'),
        'condition' => Auth::can('view_all_lycees', 'lycee'),
    ],
    [
        'url' => '/annees-academiques',
        'icon' => 'ph-duotone ph-calendar-check',
        'text' => _('Années Académiques'),
        'title' => _('Gérer les années académiques et définir l\'année active.'),
        'condition' => Auth::can('manage', 'settings'),
    ],
    [
        'url' => '/sequences',
        'icon' => 'ph-duotone ph-flag',
        'text' => _('Séquences'),
        'title' => _('Gérer les séquences et les périodes d\'évaluation.'),
        'condition' => Auth::can('manage', 'settings'),
    ],
    [
        'url' => '/cycles',
        'icon' => 'ph-duotone ph-arrows-clockwise',
        'text' => _('Cycles'),
        'title' => _('Gérer les cycles d\'enseignement (collège, lycée).'),
        'condition' => Auth::can('manage', 'settings'),
    ],
    [
        'url' => '/contrats',
        'icon' => 'ph-duotone ph-file-text',
        'text' => _('Contrats'),
        'title' => _('Gérer les types de contrats du personnel.'),
        'condition' => Auth::can('manage', 'user'),
    ],
    [
        'url' => '/licences',
        'icon' => 'ph-duotone ph-key',
        'text' => _('Licences'),
        'title' => _('Gérer les licences de l\'application.'),
        'condition' => Auth::get('role_name') === 'super_admin_createur',
    ],
    [
        'label' => _('Finances'),
        'is_caption' => true,
        'condition' => Auth::can('create', 'paiement') || Auth::can('view', 'finance'),
    ],
    [
        'url' => '/frais',
        'icon' => 'ph-duotone ph-money',
        'text' => _('Frais Scolaires'),
        'title' => _('Configurer les différents types de frais scolaires.'),
        'condition' => Auth::can('manage', 'finance'),
    ],
    [
        'url' => '/paiements',
        'icon' => 'ph-duotone ph-receipt',
        'text' => _('Paiements'),
        'title' => _('Enregistrer les paiements des frais scolaires.'),
        'condition' => Auth::can('create', 'paiement'),
    ],
    [
        'url' => '/salaires',
        'icon' => 'ph-duotone ph-wallet',
        'text' => _('Salaires'),
        'title' => _('Gérer la paie des employés.'),
        'condition' => Auth::can('create', 'paiement'),
    ],
    [
        'url' => '/mensualites',
        'icon' => 'ph-duotone ph-calendar-plus',
        'text' => _('Mensualités'),
        'title' => _('Suivre le paiement des frais de scolarité mensuels.'),
        'condition' => Auth::can('view', 'finance'),
    ],
    [
        'url' => '/recus',
        'icon' => 'ph-duotone ph-printer',
        'text' => _('Reçus'),
        'title' => _('Consulter et imprimer les reçus de paiement.'),
        'condition' => Auth::can('view', 'finance'),
    ],
     [
        'url' => '/boutique',
        'icon' => 'ph-duotone ph-shopping-cart',
        'text' => _('Boutique'),
        'title' => _('Gérer la vente d\'articles scolaires.'),
        'condition' => Auth::can('manage', 'boutique'),
    ],
    [
        'label' => _('Paramètres'),
        'is_caption' => true,
        'condition' => Auth::can('manage', 'settings'),
    ],
    [
        'url' => '/settings',
        'icon' => 'ph-duotone ph-gear',
        'text' => _('Paramètres Généraux'),
        'title' => _('Configurer les paramètres globaux de l\'application.'),
        'condition' => Auth::can('manage', 'settings'),
    ],
    [
        'url' => '/param-devoir',
        'icon' => 'ph-duotone ph-sliders-horizontal',
        'text' => _('Paramètres Devoirs'),
        'title' => _('Configurer les paramètres pour les devoirs.'),
        'condition' => Auth::can('manage', 'settings'),
    ],
    [
        'url' => '/param-composition',
        'icon' => 'ph-duotone ph-exam',
        'text' => _('Paramètres Compositions'),
        'title' => _('Configurer les paramètres pour les compositions.'),
        'condition' => Auth::can('manage', 'settings'),
    ],
    [
        'url' => '/modele-carte/edit',
        'icon' => 'ph-duotone ph-identification-card',
        'text' => _('Éditeur de Carte'),
        'title' => _('Personnaliser le modèle de la carte d\'identité scolaire.'),
        'condition' => Auth::can('edit', 'param_lycee'),
    ],
    [
        'url' => '/modele-bulletin/edit',
        'icon' => 'ph-duotone ph-file-search',
        'text' => _('Éditeur de Bulletin'),
        'title' => _('Personnaliser le modèle du bulletin de notes.'),
        'condition' => Auth::can('manage', 'bulletin_template'),
    ],
];
?>
<nav class="pc-sidebar">
  <div class="navbar-wrapper">
    <div class="m-header">
      <a href="/" class="b-brand text-primary">
        <!-- ========   Change your logo from here   ============ -->
        <img src="/assets/img/logo-dark.svg" alt="logo image" class="logo-lg" />
        <span class="badge bg-light-success rounded-pill ms-2 theme-version">v1.0</span>
      </a>
    </div>
    <div class="navbar-content">
      <ul class="pc-navbar">
        <?php
        $active_url = $_SERVER['REQUEST_URI'];
        foreach ($navItems as $item):
            // Check if the item should be displayed
            if (isset($item['condition']) && !$item['condition']) {
                continue;
            }

            if (isset($item['is_caption']) && $item['is_caption']):
        ?>
            <li class="pc-item pc-caption">
              <label><?= $item['label'] ?></label>
            </li>
        <?php else: ?>
            <li class="pc-item <?= ($active_url == $item['url']) ? 'active' : '' ?>">
              <a href="<?= $item['url'] ?>" class="pc-link" title="<?= $item['title'] ?>">
                <span class="pc-micon">
                  <i class="<?= $item['icon'] ?>"></i>
                </span>
                <span class="pc-mtext"><?= $item['text'] ?></span>
              </a>
            </li>
        <?php
            endif;
        endforeach;
        ?>
      </ul>
    </div>
  </div>
</nav>
