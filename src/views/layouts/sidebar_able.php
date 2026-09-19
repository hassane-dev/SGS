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
        'text' => _('Scolarité'),
        'icon' => 'ph-duotone ph-student',
        'is_dropdown' => true,
        'condition' => Auth::can('view_all', 'eleve') || Auth::can('manage', 'inscription') || Auth::can('view', 'class') || Auth::can('manage', 'series') || Auth::can('view', 'matiere') || Auth::can('view_incidents', 'discipline') || Auth::can('view_sanctions', 'discipline') || Auth::can('view_councils', 'discipline'),
        'submenu' => [
            [
                'url' => '/eleves',
                'text' => _('Élèves'),
                'title' => _('Gérer les dossiers des élèves, inscriptions et archives.'),
                'condition' => Auth::can('view_all', 'eleve'),
            ],
            [
                'url' => '/discipline/dashboard',
                'text' => _('Discipline & Vie Scolaire'),
                'title' => _('Tableau de bord disciplinaire, incidents, sanctions et conseils.'),
                'condition' => Auth::can('view_incidents', 'discipline') || Auth::can('view_sanctions', 'discipline') || Auth::can('view_councils', 'discipline'),
            ],
            [
                'url' => '/inscriptions',
                'text' => _('Inscriptions'),
                'title' => _('Gérer les inscriptions des nouveaux élèves.'),
                'condition' => Auth::can('manage', 'inscription'),
            ],
            [
                'url' => '/reinscription',
                'text' => _('Réinscriptions'),
                'title' => _('Gérer les réinscriptions des élèves existants.'),
                'condition' => Auth::can('manage', 'inscription'),
            ],
            [
                'url' => '/classes',
                'text' => _('Classes'),
                'title' => _('Gérer les classes, les matières et les enseignants associés.'),
                'condition' => Auth::can('view', 'class'),
            ],
            [
                'url' => '/series',
                'text' => _('Séries'),
                'title' => _('Gérer les séries d\'enseignement.'),
                'condition' => Auth::can('manage', 'series'),
            ],
            [
                'url' => '/matieres',
                'text' => _('Matières'),
                'title' => _('Gérer la liste des matières enseignées.'),
                'condition' => Auth::can('view', 'matiere'),
            ],
        ],
    ],
    [
        'text' => _('Pédagogie'),
        'icon' => 'ph-duotone ph-chalkboard-teacher',
        'is_dropdown' => true,
        'condition' => Auth::can('view_affectations', 'pedagogy') || Auth::can('view_my_affectations', 'pedagogy') || Auth::can('manage_affectations', 'pedagogy') || Auth::can('manage', 'timetable') || Auth::get('role_name') === 'enseignant' || Auth::can('view_all', 'cahier_texte') || Auth::can('manage', 'cahier_texte') || Auth::can('view_all', 'note') || Auth::can('create_own', 'note') || Auth::can('manage_settings', 'evaluation') || Auth::can('generate', 'bulletin') || Auth::can('edit_appreciation_conseil', 'bulletin'),
        'submenu' => [
            [
                'url' => '/affectations-pedagogiques',
                'text' => _('Affectations Enseignants'),
                'title' => _('Gérer les affectations pédagogiques des enseignants.'),
                'condition' => Auth::can('view_affectations', 'pedagogy') || Auth::can('view_my_affectations', 'pedagogy') || Auth::can('manage_affectations', 'pedagogy'),
            ],
            [
                'url' => '/emploi-du-temps',
                'text' => _('Emploi du Temps'),
                'title' => _('Configurer et consulter les emplois du temps.'),
                'condition' => Auth::can('manage', 'timetable'),
            ],
            [
                'url' => '/cahier-texte',
                'text' => _('Cahier de Texte'),
                'title' => _('Remplir et consulter le cahier de texte.'),
                'condition' => Auth::get('role_name') === 'enseignant' || Auth::can('view_all', 'cahier_texte') || Auth::can('manage', 'cahier_texte'),
            ],
            [
                'url' => '/evaluations/dashboard',
                'text' => _('Notes & Évaluations'),
                'title' => _('Tableau de bord des notes, complétude, saisie et déblocages.'),
                'condition' => Auth::can('view_all', 'note') || Auth::can('create_own', 'note') || Auth::can('generate', 'bulletin'),
            ],
            [
                'url' => '/appreciation-conseil',
                'text' => _('Appréciation Conseil de Classe'),
                'title' => _('Saisir les appréciations du conseil de classe pour sa classe principale.'),
                'condition' => Auth::can('edit_appreciation_conseil', 'bulletin'),
            ],
            [
                'url' => '/bulletins',
                'text' => _('Bulletins & Impression'),
                'title' => _('Générer, valider et imprimer les bulletins de notes.'),
                'condition' => Auth::can('generate', 'bulletin') || Auth::can('validate', 'bulletin') || Auth::can('print', 'bulletin'),
            ],
        ],
    ],
    [
        'text' => _('Ressources Humaines'),
        'icon' => 'ph-duotone ph-users-three',
        'is_dropdown' => true,
        'condition' => Auth::can('view_all', 'drh') || Auth::can('create', 'drh') || Auth::can('manage', 'user') || Auth::can('manage_contrats', 'drh'),
        'submenu' => [
            [
                'url' => '/drh/dashboard',
                'text' => _('Personnel & DRH'),
                'title' => _('Cockpit RH 360°, annuaire du personnel et dossiers.'),
                'condition' => Auth::can('view_all', 'drh'),
            ],
            [
                'url' => '/contrats',
                'text' => _('Types de Contrats'),
                'title' => _('Gérer les types de contrats du personnel.'),
                'condition' => Auth::can('manage', 'user') || Auth::can('manage_contrats', 'drh'),
            ],
        ],
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
                'url' => '/paie/bulletins/prepare',
                'text' => _('Préparation des bulletins'),
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
                'url' => '/paie/historique',
                'text' => _('Historique des salaires'),
                'condition' => Auth::can('view', 'paie'),
            ],
            [
                'url' => '/paie/regles',
                'text' => _('Configuration des règles'),
                'condition' => Auth::can('config', 'paie'),
            ],
        ],
    ],
    [
        'text' => _('Finances & Comptabilité'),
        'icon' => 'ph-duotone ph-chart-pie',
        'is_dropdown' => true,
        'condition' => Auth::can('view', 'paiement') || Auth::can('manage', 'paiement') || Auth::can('view', 'depense') || Auth::can('validate', 'depense') || Auth::can('pay', 'depense') || Auth::can('manage', 'depense') || Auth::can('view', 'sessions_caisse') || Auth::can('create', 'sessions_caisse') || Auth::can('edit', 'sessions_caisse') || Auth::can('validate', 'sessions_caisse') || Auth::can('view', 'comptes_financiers') || Auth::can('view', 'comptabilite') || Auth::can('view', 'budget') || Auth::can('report', 'budget') || Auth::can('adjust', 'budget') || Auth::can('view', 'comptes_comptables') || Auth::can('view', 'journal') || Auth::can('view_policy', 'finance') || Auth::can('edit_policy', 'finance') || Auth::can('view_control', 'finance') || Auth::can('view_reports', 'finance') || Auth::can('view', 'reporting') || Auth::can('manage', 'frais'),
        'submenu' => [
            [
                'url' => '/paiements',
                'text' => _('Recettes & Caisse'),
                'title' => _('Cockpit financier des encaissements, mensualités et restes.'),
                'condition' => Auth::can('view', 'paiement') || Auth::can('manage', 'paiement'),
            ],
            [
                'url' => '/treasury/sessions',
                'text' => _('Sessions de caisse'),
                'title' => _('Gérer les sessions de caisse journalières.'),
                'condition' => Auth::can('view', 'sessions_caisse') || Auth::can('create', 'sessions_caisse') || Auth::can('edit', 'sessions_caisse') || Auth::can('validate', 'sessions_caisse'),
            ],
            [
                'url' => '/depenses',
                'text' => _('Gestion des dépenses'),
                'title' => _('Demandes, validations, paiements et historique des dépenses.'),
                'condition' => Auth::can('view', 'depense') || Auth::can('validate', 'depense') || Auth::can('pay', 'depense'),
            ],
            [
                'url' => '/budgets',
                'text' => _('Gestion budgétaire'),
                'title' => _('Budgets, exécution, ajustements et engagements.'),
                'condition' => Auth::can('view', 'budget') || Auth::can('report', 'budget') || Auth::can('adjust', 'budget'),
            ],
            [
                'url' => '/comptes-financiers',
                'text' => _('Comptes financiers'),
                'title' => _('Consulter et gérer les comptes financiers.'),
                'condition' => Auth::can('view', 'comptes_financiers'),
            ],
            [
                'url' => '/journal',
                'text' => _('Comptabilité générale'),
                'title' => _('Journal, Grand Livre, Balance et Plan de comptes OHADA.'),
                'condition' => Auth::can('view', 'journal') || Auth::can('view', 'comptes_comptables') || Auth::can('view', 'comptabilite'),
            ],
            [
                'url' => '/comptabilite/exercices',
                'text' => _('Exercices Financiers'),
                'title' => _('Consulter et gérer les exercices financiers.'),
                'condition' => Auth::can('view', 'comptabilite'),
            ],
            [
                'url' => '/comptabilite/periodes',
                'text' => _('Périodes Comptables'),
                'title' => _('Consulter et gérer les périodes comptables.'),
                'condition' => Auth::can('view', 'comptabilite'),
            ],
            [
                'url' => '/reporting',
                'text' => _('Reporting Décisionnel'),
                'title' => _('Cockpit décisionnel de pilotage stratégique et prévisions.'),
                'condition' => Auth::can('view', 'reporting'),
            ],
            [
                'url' => '/frais',
                'text' => _('Configuration Frais'),
                'title' => _('Gérer la structure des frais d\'inscription et des mensualités.'),
                'condition' => Auth::can('manage', 'frais'),
            ],
        ],
    ],
    [
        'text' => _('Achats & Fournisseurs'),
        'icon' => 'ph-duotone ph-truck',
        'is_dropdown' => true,
        'condition' => Auth::can('view', 'fournisseur') || Auth::can('manage', 'achat_categorie') || Auth::can('view', 'achat_article') || Auth::can('view', 'achat_demande') || Auth::can('create', 'achat_demande') || Auth::can('view', 'achat_commande') || Auth::can('create', 'achat_commande') || Auth::can('view', 'achat_reception') || Auth::can('create', 'achat_reception') || Auth::can('view', 'achat_facture') || Auth::can('create', 'achat_facture'),
        'submenu' => [
            [
                'url' => '/achats/fournisseurs',
                'text' => _('Fournisseurs'),
                'title' => _('Gérer les fiches et coordonnées des fournisseurs.'),
                'condition' => Auth::can('view', 'fournisseur'),
            ],
            [
                'url' => '/achats/demandes',
                'text' => _('Achats & Commandes'),
                'title' => _('Demandes d\'achats, bons de commande, réceptions et factures.'),
                'condition' => Auth::can('view', 'achat_demande') || Auth::can('view', 'achat_commande') || Auth::can('view', 'achat_reception') || Auth::can('view', 'achat_facture'),
            ],
            [
                'url' => '/achats/categories',
                'text' => _('Catégories d\'achats'),
                'title' => _('Gérer les catégories d\'achats et comptes de charges.'),
                'condition' => Auth::can('manage', 'achat_categorie'),
            ],
            [
                'url' => '/achats/articles',
                'text' => _('Articles & Prestations'),
                'title' => _('Consulter et gérer le catalogue d\'articles et services.'),
                'condition' => Auth::can('view', 'achat_article'),
            ],
        ],
    ],
    [
        'url' => '/boutique/articles',
        'icon' => 'ph-duotone ph-shopping-bag',
        'text' => _('Boutique'),
        'title' => _('Gérer les articles et les achats de la boutique.'),
        'condition' => Auth::can('manage', 'boutique'),
    ],
    [
        'text' => _('Administration & Paramètres'),
        'icon' => 'ph-duotone ph-gear',
        'is_dropdown' => true,
        'condition' => Auth::can('view_all', 'user') || Auth::can('view_all', 'role') || Auth::can('view_all_lycees', 'lycee') || Auth::can('manage', 'annee_academique') || Auth::can('manage', 'sequence') || Auth::can('manage', 'cycle') || Auth::can('edit', 'param_lycee') || Auth::can('edit', 'param_general') || Auth::can('edit', 'param_devoir') || Auth::can('edit', 'param_composition') || Auth::can('manage', 'bulletin_template') || Auth::can('view_config', 'discipline') || Auth::can('manage_config', 'discipline') || Auth::get('role_name') === 'super_admin_createur',
        'submenu' => [
            [
                'url' => '/discipline/settings',
                'text' => _('Discipline & Sanctions'),
                'title' => _('Configurer les référentiels des d\'incidents et sanctions.'),
                'condition' => Auth::can('view_config', 'discipline') || Auth::can('manage_config', 'discipline'),
            ],
            [
                'url' => '/users',
                'text' => _('Comptes Utilisateurs'),
                'title' => _('Gérer les identifiants et rôles des membres du personnel.'),
                'condition' => Auth::can('view_all', 'user'),
            ],
            [
                'url' => '/roles',
                'text' => _('Rôles'),
                'title' => _('Gérer les rôles et les permissions associées.'),
                'condition' => Auth::can('view_all', 'role'),
            ],
            [
                'url' => '/lycees',
                'text' => _('Lycées'),
                'title' => _('Gérer les différents établissements scolaires.'),
                'condition' => Auth::can('view_all_lycees', 'lycee'),
            ],
            [
                'url' => '/annees-academiques',
                'text' => _('Années Académiques'),
                'title' => _('Gérer les années académiques et définir l\'année active.'),
                'condition' => Auth::can('manage', 'annee_academique') || Auth::can('view_all_lycees', 'lycee'),
            ],
            [
                'url' => '/sequences',
                'text' => _('Séquences'),
                'title' => _('Gérer les séquences et les périodes d\'évaluation.'),
                'condition' => Auth::can('manage', 'sequence') || Auth::can('view_all_lycees', 'lycee'),
            ],
            [
                'url' => '/cycles',
                'text' => _('Cycles'),
                'title' => _('Gérer les cycles d\'enseignement (collège, lycée).'),
                'condition' => Auth::can('manage', 'cycle'),
            ],
            [
                'url' => '/settings',
                'text' => _('Paramètres Lycée'),
                'title' => _('Configurer les paramètres spécifiques au lycée.'),
                'condition' => Auth::can('edit', 'param_lycee'),
            ],
            [
                'url' => '/param-general/edit',
                'text' => _('Paramètres Généraux'),
                'title' => _('Configurer les paramètres globaux de l\'établissement.'),
                'condition' => Auth::can('edit', 'param_general'),
            ],
            [
                'url' => '/modele-carte/edit',
                'text' => _('Éditeur de Carte'),
                'title' => _('Personnaliser le modèle de la carte d\'identité scolaire.'),
                'condition' => Auth::can('edit', 'param_lycee'),
            ],
            [
                'url' => '/modele-bulletin/edit',
                'text' => _('Éditeur de Bulletin'),
                'title' => _('Personnaliser le modèle du bulletin de notes.'),
                'condition' => Auth::can('manage', 'bulletin_template'),
            ],
            [
                'url' => '/licences',
                'text' => _('Licences'),
                'title' => _('Gérer les licences de l\'application.'),
                'condition' => Auth::get('role_name') === 'super_admin_createur',
            ],
        ],
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
            // Check if active and find best matching sub-item
            $best_match_url = null;
            $best_match_len = -1;
            foreach ($sub_items as $sub) {
                if ($active_url == $sub['url']) {
                    $best_match_url = $sub['url'];
                    $best_match_len = 9999;
                    break;
                }
                if (strpos($active_url, $sub['url'] . '/') === 0 || $active_url == $sub['url']) {
                    $len = strlen($sub['url']);
                    if ($len > $best_match_len) {
                        $best_match_len = $len;
                        $best_match_url = $sub['url'];
                    }
                }
            }
            $is_active = ($best_match_url !== null);
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
                  <li class="pc-item <?= ($sub['url'] === $best_match_url) ? 'active' : '' ?>">
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
