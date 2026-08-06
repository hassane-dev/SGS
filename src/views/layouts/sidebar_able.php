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
        'condition' => Auth::can('manage', 'user') || Auth::can('view', 'class') || Auth::can('view_all', 'role') || Auth::can('manage', 'inscription'),
    ],
    [
        'url' => '/users',
        'icon' => 'ph-duotone ph-users',
        'text' => _('Personnel'),
        'title' => _('Gérer les membres du personnel, leurs rôles et leurs accès.'),
        'condition' => Auth::can('view_all', 'user'),
    ],
    [
        'url' => '/roles',
        'icon' => 'ph-duotone ph-user-list',
        'text' => _('Rôles'),
        'title' => _('Gérer les rôles et les permissions associées.'),
        'condition' => Auth::can('view_all', 'role'),
    ],
    [
        'url' => '/eleves',
        'icon' => 'ph-duotone ph-student',
        'text' => _('Élèves'),
        'title' => _('Gérer les dossiers des élèves, inscriptions et archives.'),
        'condition' => Auth::can('view_all', 'eleve'),
    ],
    [
        'url' => '/inscriptions',
        'icon' => 'ph-duotone ph-user-plus',
        'text' => _('Inscriptions'),
        'title' => _('Gérer les inscriptions des nouveaux élèves.'),
        'condition' => Auth::can('manage', 'inscription'),
    ],
    [
        'url' => '/reinscription',
        'icon' => 'ph-duotone ph-user-switch',
        'text' => _('Réinscriptions'),
        'title' => _('Gérer les réinscriptions des élèves existants.'),
        'condition' => Auth::can('manage', 'inscription'),
    ],
    [
        'url' => '/classes',
        'icon' => 'ph-duotone ph-chalkboard-teacher',
        'text' => _('Classes'),
        'title' => _('Gérer les classes, les matières et les enseignants associés.'),
        'condition' => Auth::can('view', 'class'),
    ],
    [
        'url' => '/series',
        'icon' => 'ph-duotone ph-books',
        'text' => _('Séries'),
        'title' => _('Gérer les séries d\'enseignement.'),
        'condition' => Auth::can('manage', 'series'),
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
        'condition' => Auth::get('role_name') === 'enseignant' || Auth::can('note', 'view_all') || Auth::can('bulletin', 'generate'),
    ],
    [
        'url' => '/emploi-du-temps',
        'icon' => 'ph-duotone ph-calendar',
        'text' => _('Emploi du Temps'),
        'title' => _('Configurer et consulter les emplois du temps.'),
        'condition' => Auth::can('manage', 'timetable'),
    ],
    [
        'url' => '/cahier-texte',
        'icon' => 'ph-duotone ph-book-open-text',
        'text' => _('Cahier de Texte'),
        'title' => _('Remplir et consulter le cahier de texte.'),
        'condition' => Auth::get('role_name') === 'enseignant' || Auth::can('view_all', 'cahier_texte'),
    ],
    [
        'url' => '/evaluations/select_class',
        'icon' => 'ph-duotone ph-graduation-cap',
        'text' => _('Notes'),
        'title' => _('Saisir et consulter les notes des élèves.'),
        'condition' => Auth::can('view_all', 'note'),
    ],
    [
        'url' => '/evaluations/deblocage',
        'icon' => 'ph-duotone ph-lock-key-open',
        'text' => _('Déblocage Notes'),
        'title' => _('Gérer les déblocages exceptionnels pour la saisie des notes.'),
        'condition' => Auth::can('manage_settings', 'evaluation'),
    ],
    [
        'url' => '/bulletins',
        'icon' => 'ph-duotone ph-file-text',
        'text' => _('Bulletins'),
        'title' => _('Générer et consulter les bulletins de notes.'),
        'condition' => Auth::can('generate', 'bulletin'),
    ],
    [
        'label' => _('Administration'),
        'is_caption' => true,
        'condition' => Auth::can('view_all_lycees', 'lycee') || Auth::can('manage', 'user') || Auth::can('manage', 'annee_academique'),
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
        'condition' => Auth::can('manage', 'annee_academique') || Auth::can('view_all_lycees', 'lycee'),
    ],
    [
        'url' => '/sequences',
        'icon' => 'ph-duotone ph-flag',
        'text' => _('Séquences'),
        'title' => _('Gérer les séquences et les périodes d\'évaluation.'),
        'condition' => Auth::can('manage', 'sequence') || Auth::can('view_all_lycees', 'lycee'),
    ],
    [
        'url' => '/cycles',
        'icon' => 'ph-duotone ph-arrows-clockwise',
        'text' => _('Cycles'),
        'title' => _('Gérer les cycles d\'enseignement (collège, lycée).'),
        'condition' => false,
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
        'label' => _('Comptabilité'),
        'is_caption' => true,
        'condition' => Auth::can('manage', 'paiement') || Auth::can('manage', 'salaire') || Auth::can('view', 'depense') || Auth::can('create', 'depense') || Auth::can('create', 'sessions_caisse') || Auth::can('edit', 'sessions_caisse') || Auth::can('validate', 'sessions_caisse') || Auth::can('view', 'comptes_financiers'),
    ],
    [
        'url' => '/paiements',
        'icon' => 'ph-duotone ph-chart-pie',
        'text' => _('Tableau de bord'),
        'title' => _('Tableau de bord financier.'),
        'condition' => Auth::can('view', 'paiement'),
    ],
    [
        'url' => '/treasury/sessions',
        'icon' => 'ph-duotone ph-safe',
        'text' => _('Sessions de caisse'),
        'title' => _('Gérer les sessions de caisse journalières.'),
        'condition' => Auth::can('view', 'sessions_caisse') || Auth::can('create', 'sessions_caisse') || Auth::can('edit', 'sessions_caisse') || Auth::can('validate', 'sessions_caisse'),
    ],
    [
        'url' => '/finance/policy',
        'icon' => 'ph-duotone ph-shield-check',
        'text' => _('Politique financière'),
        'title' => _('Gérer la politique financière globale du lycée.'),
        'condition' => Auth::can('view_policy', 'finance') || Auth::can('edit_policy', 'finance') || Auth::can('edit', 'param_lycee'),
    ],
    [
        'url' => '/finance/control',
        'icon' => 'ph-duotone ph-info',
        'text' => _('Contrôle financier'),
        'title' => _('Vérifier et contrôler la situation financière des élèves.'),
        'condition' => Auth::can('view_control', 'finance') || Auth::can('view', 'paiement'),
    ],
    [
        'text' => _('Budgets'),
        'icon' => 'ph-duotone ph-coins',
        'is_dropdown' => true,
        'condition' => Auth::can('view', 'budget') || Auth::can('report', 'budget') || Auth::can('adjust', 'budget'),
        'submenu' => [
            ['url' => '/budgets', 'text' => _('Budgets'), 'condition' => Auth::can('view', 'budget')],
            ['url' => '/budgets/report', 'text' => _('Exécution budgétaire'), 'condition' => Auth::can('report', 'budget')],
            ['url' => '/budgets/adjustment', 'text' => _('Ajustements de crédits'), 'condition' => Auth::can('adjust', 'budget')],
            ['url' => '/budgets/engagements', 'text' => _('Engagements budgétaires'), 'condition' => Auth::can('view', 'budget')],
            ['url' => '/budgets/report', 'text' => _('Rapports budgétaires'), 'condition' => Auth::can('report', 'budget')],
        ]
    ],
    [
        'text' => _('Dépenses'),
        'icon' => 'ph-duotone ph-hand-coins',
        'is_dropdown' => true,
        'condition' => Auth::can('view', 'depense') || Auth::can('create', 'depense'),
        'submenu' => [
            ['url' => '/depenses', 'text' => _('Demandes de dépenses'), 'condition' => Auth::can('view', 'depense')],
            ['url' => '/depenses/validation', 'text' => _('Validation'), 'condition' => Auth::can('validate', 'depense')],
            ['url' => '/depenses/payments', 'text' => _('Paiements'), 'condition' => Auth::can('pay', 'depense')],
            ['url' => '/depenses/history', 'text' => _('Historique'), 'condition' => Auth::can('view', 'depense')],
        ]
    ],
    [
        'text' => _('Recettes'),
        'icon' => 'ph-duotone ph-trend-up',
        'is_dropdown' => true,
        'condition' => Auth::can('view', 'paiement') || Auth::can('manage', 'paiement'),
        'submenu' => [
            ['url' => '/paiements/pending', 'text' => _('Paiement inscription'), 'condition' => Auth::can('manage', 'paiement')],
            ['url' => '/mensualites', 'text' => _('Gestion mensualités'), 'condition' => Auth::can('manage', 'paiement')],
            ['url' => '/paiements/restes', 'text' => _('Gestion des restes'), 'condition' => Auth::can('manage', 'paiement')],
            ['url' => '/paiements/historique', 'text' => _('Historique paiements'), 'condition' => Auth::can('view', 'paiement')],
            ['url' => '/paiements/recus', 'text' => _('Reçus'), 'condition' => Auth::can('view', 'paiement')],
        ]
    ],
    [
        'text' => _('Comptabilité générale'),
        'icon' => 'ph-duotone ph-book-open',
        'is_dropdown' => true,
        'condition' => Auth::can('view', 'journal') || Auth::can('view_reports', 'finance') || Auth::can('view', 'comptes_financiers'),
        'submenu' => [
            ['url' => '/journal', 'text' => _('Journal comptable'), 'condition' => Auth::can('view', 'journal')],
            ['url' => '/grand-livre', 'text' => _('Grand Livre'), 'condition' => Auth::can('view', 'journal')],
            ['url' => '/balance', 'text' => _('Balance'), 'condition' => Auth::can('view', 'journal')],
            ['url' => '/reports/financial', 'text' => _('Rapports financiers'), 'condition' => Auth::can('view_reports', 'finance')],
        ]
    ],
    [
        'url' => '/salaires',
        'icon' => 'ph-duotone ph-wallet',
        'text' => _('Salaires'),
        'title' => _('Gérer la paie des employés.'),
        'condition' => Auth::can('manage', 'salaire'),
    ],
    [
        'text' => _('Configuration'),
        'icon' => 'ph-duotone ph-gear',
        'is_dropdown' => true,
        'condition' => Auth::can('manage', 'frais') || Auth::can('manage', 'depense'),
        'submenu' => [
            ['url' => '/frais', 'text' => _('Configuration Frais'), 'condition' => Auth::can('manage', 'frais')],
            ['url' => '/depenses/categories', 'text' => _('Catégories de dépenses'), 'condition' => Auth::can('manage', 'depense')],
            ['url' => '/depenses/centres-couts', 'text' => _('Centres de coûts'), 'condition' => Auth::can('manage', 'depense')],
            ['url' => '/depenses/beneficiaires', 'text' => _('Bénéficiaires'), 'condition' => Auth::can('manage', 'depense')],
        ]
    ],
    [
        'url' => '/comptes-financiers',
        'icon' => 'ph-duotone ph-wallet',
        'text' => _('Comptes financiers'),
        'title' => _('Consulter et gérer les comptes financiers.'),
        'condition' => Auth::can('view', 'comptes_financiers'),
    ],
    [
        'url' => '/boutique/articles',
        'icon' => 'ph-duotone ph-shopping-cart',
        'text' => _('Boutique'),
        'title' => _('Gérer les articles et les achats de la boutique.'),
        'condition' => Auth::can('manage', 'boutique'),
    ],
    [
        'label' => _('Paramètres'),
        'is_caption' => true,
        'condition' => Auth::can('edit', 'param_lycee') || Auth::can('manage', 'bulletin_template'),
    ],
    [
        'url' => '/settings',
        'icon' => 'ph-duotone ph-gear',
        'text' => _('Paramètres Lycée'),
        'title' => _('Configurer les paramètres spécifiques au lycée.'),
        'condition' => Auth::can('edit', 'param_lycee'),
    ],
    [
        'url' => '/param-general/edit',
        'icon' => 'ph-duotone ph-wrench',
        'text' => _('Paramètres Généraux'),
        'title' => _('Configurer les paramètres globaux de l\'établissement.'),
        'condition' => Auth::can('edit', 'param_general'),
    ],
    [
        'url' => '/param-devoir',
        'icon' => 'ph-duotone ph-sliders-horizontal',
        'text' => _('Paramètres Devoirs'),
        'title' => _('Configurer les paramètres pour les devoirs.'),
        'condition' => Auth::can('edit', 'param_devoir'),
    ],
    [
        'url' => '/param-composition',
        'icon' => 'ph-duotone ph-exam',
        'text' => _('Paramètres Compositions'),
        'title' => _('Configurer les paramètres pour les compositions.'),
        'condition' => Auth::can('edit', 'param_composition'),
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
        <img src="/assets/img/placeholder-photo.png" alt="logo image" class="logo-lg" style="height: 40px;"/>
        <span class="badge bg-light-success rounded-pill ms-2 theme-version">v1.0</span>
      </a>
    </div>
    <div class="navbar-content">
      <ul class="pc-navbar">
        <?php
        $active_url = strtok($_SERVER['REQUEST_URI'], '?');
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
        <?php elseif (isset($item['is_dropdown']) && $item['is_dropdown']): ?>
            <?php
            // Filter submenu items by permission
            $sub_items = [];
            foreach ($item['submenu'] as $sub) {
                if (!isset($sub['condition']) || $sub['condition']) {
                    $sub_items[] = $sub;
                }
            }
            if (empty($sub_items)) {
                continue;
            }
            // Check if active
            $is_active = false;
            foreach ($sub_items as $sub) {
                if ($active_url == $sub['url']) {
                    $is_active = true;
                }
            }
            ?>
            <li class="pc-item pc-hasmenu <?= $is_active ? 'pc-trigger active' : '' ?>">
              <a href="#!" class="pc-link">
                <span class="pc-micon" style="pointer-events: none;">
                  <i class="<?= $item['icon'] ?>" style="pointer-events: none;"></i>
                </span>
                <span class="pc-mtext" style="pointer-events: none;"><?= $item['text'] ?></span>
                <span class="pc-arrow" style="pointer-events: none;"><i class="ti ti-chevron-right" style="pointer-events: none;"></i></span>
              </a>
              <ul class="pc-submenu" style="<?= $is_active ? 'display: block;' : 'display: none;' ?>">
                <?php foreach ($sub_items as $sub): ?>
                  <li class="pc-item <?= ($active_url == $sub['url']) ? 'active' : '' ?>">
                    <a class="pc-link" href="<?= $sub['url'] ?>"><?= $sub['text'] ?></a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </li>
        <?php else: ?>
            <li class="pc-item <?= ($active_url == $item['url']) ? 'active' : '' ?>">
              <a href="<?= $item['url'] ?>" class="pc-link" title="<?= $item['title'] ?? '' ?>">
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
