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
        'label' => _('Ressources Humaines'),
        'is_caption' => true,
        'condition' => Auth::can('view_all', 'drh') || Auth::can('create', 'drh'),
    ],
    [
        'url' => '/drh/dashboard',
        'icon' => 'ph-duotone ph-chart-pie-slice',
        'text' => _('Cockpit DRH'),
        'title' => _('Affiche le tableau de bord des ressources humaines.'),
        'condition' => Auth::can('view_all', 'drh'),
    ],
    [
        'url' => '/drh',
        'icon' => 'ph-duotone ph-users-three',
        'text' => _('Annuaire DRH'),
        'title' => _('Gérer l\'annuaire centralisé 360° du personnel.'),
        'condition' => Auth::can('view_all', 'drh'),
    ],
    [
        'label' => _('Gestion'),
        'is_caption' => true,
        'condition' => Auth::can('manage', 'user') || Auth::can('view', 'class') || Auth::can('view_all', 'role') || Auth::can('manage', 'inscription'),
    ],
    [
        'url' => '/users',
        'icon' => 'ph-duotone ph-users',
        'text' => _('Comptes Utilisateurs'),
        'title' => _('Gérer les identifiants et rôles des membres du personnel.'),
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
        'label' => _('Achats & Fournisseurs'),
        'is_caption' => true,
        'condition' => Auth::can('view', 'fournisseur') || Auth::can('create', 'achat_demande') || Auth::can('create', 'achat_commande') || Auth::can('create', 'achat_reception') || Auth::can('create', 'achat_facture'),
    ],
    [
        'url' => '/achats/fournisseurs',
        'icon' => 'ph-duotone ph-truck',
        'text' => _('Fournisseurs'),
        'title' => _('Gérer les fiches et coordonnées des fournisseurs.'),
        'condition' => Auth::can('view', 'fournisseur'),
    ],
    [
        'url' => '/achats/categories',
        'icon' => 'ph-duotone ph-folders',
        'text' => _('Catégories d\'achats'),
        'title' => _('Gérer les catégories d\'achats et comptes de charges.'),
        'condition' => Auth::can('manage', 'achat_categorie'),
    ],
    [
        'url' => '/achats/articles',
        'icon' => 'ph-duotone ph-package',
        'text' => _('Articles & Prestations'),
        'title' => _('Consulter et gérer le catalogue d\'articles et services.'),
        'condition' => Auth::can('view', 'achat_article'),
    ],
    [
        'url' => '/achats/demandes',
        'icon' => 'ph-duotone ph-clipboard-text',
        'text' => _('Demandes d\'achats'),
        'title' => _('Formuler et approuver les demandes d\'achats.'),
        'condition' => Auth::can('view', 'achat_demande'),
    ],
    [
        'url' => '/achats/commandes',
        'icon' => 'ph-duotone ph-shopping-cart',
        'text' => _('Bons de commande'),
        'title' => _('Émettre et suivre les bons de commande.'),
        'condition' => Auth::can('view', 'achat_commande'),
    ],
    [
        'url' => '/achats/receptions',
        'icon' => 'ph-duotone ph-download-simple',
        'text' => _('Bons de réception'),
        'title' => _('Gérer et valider les réceptions d\'achats.'),
        'condition' => Auth::can('view', 'achat_reception'),
    ],
    [
        'url' => '/achats/factures',
        'icon' => 'ph-duotone ph-receipt',
        'text' => _('Factures d\'achats'),
        'title' => _('Rapprocher et régler les factures fournisseurs.'),
        'condition' => Auth::can('view', 'achat_facture'),
    ],
    [
        'label' => _('Comptabilité'),
        'is_caption' => true,
        'condition' => Auth::can('manage', 'paiement') || Auth::can('manage', 'salaire') || Auth::can('view', 'depense') || Auth::can('create', 'depense') || Auth::can('create', 'sessions_caisse') || Auth::can('edit', 'sessions_caisse') || Auth::can('validate', 'sessions_caisse') || Auth::can('view', 'comptes_financiers') || Auth::can('view', 'comptabilite'),
    ],
    [
        'url' => '/paiements',
        'icon' => 'ph-duotone ph-chart-pie',
        'text' => _('Tableau de bord'),
        'title' => _('Tableau de bord financier.'),
        'condition' => Auth::can('view', 'paiement'),
    ],
    [
        'url' => '/comptabilite/exercices',
        'icon' => 'ph-duotone ph-calendar',
        'text' => _('Exercices Financiers'),
        'title' => _('Consulter et gérer les exercices financiers.'),
        'condition' => Auth::can('view', 'comptabilite'),
    ],
    [
        'url' => '/comptabilite/periodes',
        'icon' => 'ph-duotone ph-clock',
        'text' => _('Périodes Comptables'),
        'title' => _('Consulter et gérer les périodes comptables.'),
        'condition' => Auth::can('view', 'comptabilite'),
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
        'url' => '/budgets',
        'icon' => 'ph-duotone ph-coins',
        'text' => _('Budgets'),
        'title' => _('Consulter et gérer les budgets.'),
        'condition' => Auth::can('view', 'budget'),
    ],
    [
        'url' => '/budgets/report',
        'icon' => 'ph-duotone ph-coins',
        'text' => _('Exécution budgétaire'),
        'title' => _('Suivi de l\'exécution du budget.'),
        'condition' => Auth::can('report', 'budget'),
    ],
    [
        'url' => '/budgets/adjustment',
        'icon' => 'ph-duotone ph-coins',
        'text' => _('Ajustements de crédits'),
        'title' => _('Gérer les ajustements de crédits budgétaires.'),
        'condition' => Auth::can('adjust', 'budget'),
    ],
    [
        'url' => '/budgets/engagements',
        'icon' => 'ph-duotone ph-coins',
        'text' => _('Engagements budgétaires'),
        'title' => _('Gérer les engagements de dépenses sur budget.'),
        'condition' => Auth::can('view', 'budget'),
    ],
    [
        'url' => '/budgets/report',
        'icon' => 'ph-duotone ph-coins',
        'text' => _('Rapports budgétaires'),
        'title' => _('Consulter les rapports d\'exécution budgétaire.'),
        'condition' => Auth::can('report', 'budget'),
    ],
    [
        'url' => '/depenses',
        'icon' => 'ph-duotone ph-hand-coins',
        'text' => _('Demandes de dépenses'),
        'title' => _('Créer et consulter les demandes de dépenses.'),
        'condition' => Auth::can('view', 'depense'),
    ],
    [
        'url' => '/depenses/validation',
        'icon' => 'ph-duotone ph-hand-coins',
        'text' => _('Validation'),
        'title' => _('Valider ou rejeter les demandes de dépenses.'),
        'condition' => Auth::can('validate', 'depense'),
    ],
    [
        'url' => '/depenses/payments',
        'icon' => 'ph-duotone ph-hand-coins',
        'text' => _('Paiements'),
        'title' => _('Payer les dépenses validées.'),
        'condition' => Auth::can('pay', 'depense'),
    ],
    [
        'url' => '/depenses/history',
        'icon' => 'ph-duotone ph-hand-coins',
        'text' => _('Historique'),
        'title' => _('Consulter l\'historique des dépenses.'),
        'condition' => Auth::can('view', 'depense'),
    ],
    [
        'url' => '/paiements/pending',
        'icon' => 'ph-duotone ph-trend-up',
        'text' => _('Paiement inscription'),
        'title' => _('Enregistrer les paiements des inscriptions.'),
        'condition' => Auth::can('manage', 'paiement'),
    ],
    [
        'url' => '/mensualites',
        'icon' => 'ph-duotone ph-trend-up',
        'text' => _('Gestion mensualités'),
        'title' => _('Gérer les mensualités et frais scolaires réguliers.'),
        'condition' => Auth::can('manage', 'paiement'),
    ],
    [
        'url' => '/paiements/restes',
        'icon' => 'ph-duotone ph-trend-up',
        'text' => _('Gestion des restes'),
        'title' => _('Gérer les restes à payer.'),
        'condition' => Auth::can('manage', 'paiement'),
    ],
    [
        'url' => '/paiements/historique',
        'icon' => 'ph-duotone ph-trend-up',
        'text' => _('Historique paiements'),
        'title' => _('Consulter l\'historique global des paiements.'),
        'condition' => Auth::can('view', 'paiement'),
    ],
    [
        'url' => '/paiements/recus',
        'icon' => 'ph-duotone ph-trend-up',
        'text' => _('Reçus'),
        'title' => _('Consulter et réimprimer les reçus.'),
        'condition' => Auth::can('view', 'paiement'),
    ],
    [
        'url' => '/comptes-comptables',
        'icon' => 'ph-duotone ph-list-numbers',
        'text' => _('Plan de comptes'),
        'title' => _('Consulter et gérer le plan de comptes comptables OHADA.'),
        'condition' => Auth::can('view', 'comptes_comptables'),
    ],
    [
        'url' => '/journal',
        'icon' => 'ph-duotone ph-book-open',
        'text' => _('Journal comptable'),
        'title' => _('Consulter le journal comptable de l\'établissement.'),
        'condition' => Auth::can('view', 'journal'),
    ],
    [
        'url' => '/grand-livre',
        'icon' => 'ph-duotone ph-book-open',
        'text' => _('Grand Livre'),
        'title' => _('Consulter le grand livre comptable.'),
        'condition' => Auth::can('view', 'journal'),
    ],
    [
        'url' => '/balance',
        'icon' => 'ph-duotone ph-book-open',
        'text' => _('Balance'),
        'title' => _('Consulter la balance des comptes comptables.'),
        'condition' => Auth::can('view', 'journal'),
    ],
    [
        'url' => '/reports/financial',
        'icon' => 'ph-duotone ph-book-open',
        'text' => _('Rapports financiers'),
        'title' => _('Générer et consulter les rapports financiers globaux.'),
        'condition' => Auth::can('view_reports', 'finance'),
    ],
    [
        'url' => '/reporting',
        'icon' => 'ph-duotone ph-chart-line-up',
        'text' => _('Reporting Décisionnel'),
        'title' => _('Cockpit décisionnel de pilotage stratégique et prévisions.'),
        'condition' => Auth::can('view', 'reporting'),
    ],
    [
        'text' => _('Paie'),
        'icon' => 'ph-duotone ph-money',
        'is_dropdown' => true,
        'condition' => Auth::can('view', 'paie'),
        'submenu' => [
            [
                'url' => '/paie/periodes',
                'text' => _('Périodes de paie'),
                'condition' => Auth::can('view', 'paie'),
            ],
            [
                'url' => '/paie/bulletins',
                'text' => _('Bulletins'),
                'condition' => Auth::can('view', 'paie'),
            ],
            [
                'url' => '/paie/cahier-texte',
                'text' => _('Cahier de texte / heures'),
                'condition' => Auth::can('view', 'paie'),
            ],
            [
                'url' => '/paie/regularisations',
                'text' => _('Régularisations'),
                'condition' => Auth::can('view', 'paie'),
            ],
            [
                'url' => '/paie/legacy/conflits',
                'text' => _('Reprises historiques'),
                'condition' => Auth::can('create', 'paie') || Auth::can('audit', 'paie'),
            ],
        ],
    ],
    [
        'url' => '/salaires',
        'icon' => 'ph-duotone ph-wallet',
        'text' => _('Salaires (Historique)'),
        'title' => _('Gérer la paie des employés.'),
        'condition' => Auth::can('manage', 'salaire'),
    ],
    [
        'url' => '/frais',
        'icon' => 'ph-duotone ph-gear',
        'text' => _('Configuration Frais'),
        'title' => _('Gérer la structure des frais d\'inscription et des mensualités.'),
        'condition' => Auth::can('manage', 'frais'),
    ],
    [
        'url' => '/depenses/categories',
        'icon' => 'ph-duotone ph-gear',
        'text' => _('Catégories de dépenses'),
        'title' => _('Gérer les catégories analytiques des dépenses.'),
        'condition' => Auth::can('manage', 'depense'),
    ],
    [
        'url' => '/depenses/centres-couts',
        'icon' => 'ph-duotone ph-gear',
        'text' => _('Centres de coûts'),
        'title' => _('Gérer les centres de coûts budgétaires.'),
        'condition' => Auth::can('manage', 'depense'),
    ],
    [
        'url' => '/depenses/beneficiaires',
        'icon' => 'ph-duotone ph-gear',
        'text' => _('Bénéficiaires'),
        'title' => _('Gérer les bénéficiaires autorisés pour les dépenses.'),
        'condition' => Auth::can('manage', 'depense'),
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
        $active_url = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
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
                if ($active_url == $sub['url'] || strpos($active_url, $sub['url']) === 0) {
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
                  <li class="pc-item <?= ($active_url == $sub['url'] || strpos($active_url, $sub['url']) === 0) ? 'active' : '' ?>">
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
