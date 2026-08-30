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
        'condition' => Auth::can('view_all', 'eleve') || Auth::can('manage', 'inscription') || Auth::can('view', 'class') || Auth::can('manage', 'series') || Auth::can('view', 'matiere'),
        'submenu' => [
            [
                'url' => '/eleves',
                'text' => _('Élèves'),
                'title' => _('Gérer les dossiers des élèves, inscriptions et archives.'),
                'condition' => Auth::can('view_all', 'eleve'),
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
        'condition' => Auth::can('view_affectations', 'pedagogy') || Auth::can('view_my_affectations', 'pedagogy') || Auth::can('manage_affectations', 'pedagogy') || Auth::can('manage', 'timetable') || Auth::get('role_name') === 'enseignant' || Auth::can('view_all', 'cahier_texte') || Auth::can('manage', 'cahier_texte') || Auth::can('view_all', 'note') || Auth::can('manage_settings', 'evaluation') || Auth::can('generate', 'bulletin'),
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
                'url' => '/evaluations/select_class',
                'text' => _('Notes'),
                'title' => _('Saisir et consulter les notes des élèves.'),
                'condition' => Auth::can('view_all', 'note'),
            ],
            [
                'url' => '/evaluations/deblocage',
                'text' => _('Déblocage Notes'),
                'title' => _('Gérer les déblocages exceptionnels pour la saisie des notes.'),
                'condition' => Auth::can('manage_settings', 'evaluation'),
            ],
            [
                'url' => '/bulletins',
                'text' => _('Bulletins'),
                'title' => _('Générer et consulter les bulletins de notes.'),
                'condition' => Auth::can('generate', 'bulletin'),
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
                'text' => _('Cockpit DRH'),
                'title' => _('Affiche le tableau de bord des ressources humaines.'),
                'condition' => Auth::can('view_all', 'drh'),
            ],
            [
                'url' => '/drh',
                'text' => _('Annuaire DRH'),
                'title' => _('Gérer l\'annuaire centralisé 360° du personnel.'),
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
                'url' => '/paie/legacy/conflits',
                'text' => _('Reprises historiques'),
                'condition' => Auth::can('create', 'paie') || Auth::can('audit', 'paie'),
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
        'condition' => Auth::can('view', 'paiement') || Auth::can('manage', 'paiement') || Auth::can('manage', 'salaire') || Auth::can('view', 'depense') || Auth::can('create', 'depense') || Auth::can('validate', 'depense') || Auth::can('pay', 'depense') || Auth::can('manage', 'depense') || Auth::can('view', 'sessions_caisse') || Auth::can('create', 'sessions_caisse') || Auth::can('edit', 'sessions_caisse') || Auth::can('validate', 'sessions_caisse') || Auth::can('view', 'comptes_financiers') || Auth::can('view', 'comptabilite') || Auth::can('view', 'budget') || Auth::can('report', 'budget') || Auth::can('adjust', 'budget') || Auth::can('view', 'comptes_comptables') || Auth::can('view', 'journal') || Auth::can('view_policy', 'finance') || Auth::can('edit_policy', 'finance') || Auth::can('view_control', 'finance') || Auth::can('view_reports', 'finance') || Auth::can('view', 'reporting') || Auth::can('manage', 'frais'),
        'submenu' => [
            [
                'url' => '/paiements',
                'text' => _('Tableau de bord financier'),
                'title' => _('Tableau de bord financier.'),
                'condition' => Auth::can('view', 'paiement'),
            ],
            [
                'url' => '/treasury/sessions',
                'text' => _('Sessions de caisse'),
                'title' => _('Gérer les sessions de caisse journalières.'),
                'condition' => Auth::can('view', 'sessions_caisse') || Auth::can('create', 'sessions_caisse') || Auth::can('edit', 'sessions_caisse') || Auth::can('validate', 'sessions_caisse'),
            ],
            [
                'url' => '/paiements/pending',
                'text' => _('Paiement inscription'),
                'title' => _('Enregistrer les paiements des inscriptions.'),
                'condition' => Auth::can('manage', 'paiement'),
            ],
            [
                'url' => '/mensualites',
                'text' => _('Gestion mensualités'),
                'title' => _('Gérer les mensualités et frais scolaires réguliers.'),
                'condition' => Auth::can('manage', 'paiement'),
            ],
            [
                'url' => '/paiements/restes',
                'text' => _('Gestion des restes'),
                'title' => _('Gérer les restes à payer.'),
                'condition' => Auth::can('manage', 'paiement'),
            ],
            [
                'url' => '/paiements/historique',
                'text' => _('Historique paiements'),
                'title' => _('Consulter l\'historique global des paiements.'),
                'condition' => Auth::can('view', 'paiement'),
            ],
            [
                'url' => '/paiements/recus',
                'text' => _('Reçus'),
                'title' => _('Consulter et réimprimer les reçus.'),
                'condition' => Auth::can('view', 'paiement'),
            ],
            [
                'url' => '/comptes-financiers',
                'text' => _('Comptes financiers'),
                'title' => _('Consulter et gérer les comptes financiers.'),
                'condition' => Auth::can('view', 'comptes_financiers'),
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
                'url' => '/depenses',
                'text' => _('Demandes de dépenses'),
                'title' => _('Créer et consulter les demandes de dépenses.'),
                'condition' => Auth::can('view', 'depense'),
            ],
            [
                'url' => '/depenses/validation',
                'text' => _('Validation dépenses'),
                'title' => _('Valider ou rejeter les demandes de dépenses.'),
                'condition' => Auth::can('validate', 'depense'),
            ],
            [
                'url' => '/depenses/payments',
                'text' => _('Paiements dépenses'),
                'title' => _('Payer les dépenses validées.'),
                'condition' => Auth::can('pay', 'depense'),
            ],
            [
                'url' => '/depenses/history',
                'text' => _('Historique dépenses'),
                'title' => _('Consulter l\'historique des dépenses.'),
                'condition' => Auth::can('view', 'depense'),
            ],
            [
                'url' => '/budgets',
                'text' => _('Budgets'),
                'title' => _('Consulter et gérer les budgets.'),
                'condition' => Auth::can('view', 'budget'),
            ],
            [
                'url' => '/budgets/report',
                'text' => _('Exécution budgétaire'),
                'title' => _('Suivi et rapports d\'exécution du budget.'),
                'condition' => Auth::can('report', 'budget'),
            ],
            [
                'url' => '/budgets/adjustment',
                'text' => _('Ajustements de crédits'),
                'title' => _('Gérer les ajustements de crédits budgétaires.'),
                'condition' => Auth::can('adjust', 'budget'),
            ],
            [
                'url' => '/budgets/engagements',
                'text' => _('Engagements budgétaires'),
                'title' => _('Gérer les engagements de dépenses sur budget.'),
                'condition' => Auth::can('view', 'budget'),
            ],
            [
                'url' => '/comptes-comptables',
                'text' => _('Plan de comptes'),
                'title' => _('Consulter et gérer le plan de comptes comptables OHADA.'),
                'condition' => Auth::can('view', 'comptes_comptables'),
            ],
            [
                'url' => '/journal',
                'text' => _('Journal comptable'),
                'title' => _('Consulter le journal comptable de l\'établissement.'),
                'condition' => Auth::can('view', 'journal'),
            ],
            [
                'url' => '/grand-livre',
                'text' => _('Grand Livre'),
                'title' => _('Consulter le grand livre comptable.'),
                'condition' => Auth::can('view', 'journal'),
            ],
            [
                'url' => '/balance',
                'text' => _('Balance'),
                'title' => _('Consulter la balance des comptes comptables.'),
                'condition' => Auth::can('view', 'journal'),
            ],
            [
                'url' => '/finance/policy',
                'text' => _('Politique financière'),
                'title' => _('Gérer la politique financière globale du lycée.'),
                'condition' => Auth::can('view_policy', 'finance') || Auth::can('edit_policy', 'finance') || Auth::can('edit', 'param_lycee'),
            ],
            [
                'url' => '/finance/control',
                'text' => _('Contrôle financier'),
                'title' => _('Vérifier et contrôler la situation financière des élèves.'),
                'condition' => Auth::can('view_control', 'finance') || Auth::can('view', 'paiement'),
            ],
            [
                'url' => '/reports/financial',
                'text' => _('Rapports financiers'),
                'title' => _('Générer et consulter les rapports financiers globaux.'),
                'condition' => Auth::can('view_reports', 'finance'),
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
            [
                'url' => '/depenses/categories',
                'text' => _('Catégories de dépenses'),
                'title' => _('Gérer les catégories analytiques des dépenses.'),
                'condition' => Auth::can('manage', 'depense'),
            ],
            [
                'url' => '/depenses/centres-couts',
                'text' => _('Centres de coûts'),
                'title' => _('Gérer les centres de coûts budgétaires.'),
                'condition' => Auth::can('manage', 'depense'),
            ],
            [
                'url' => '/depenses/beneficiaires',
                'text' => _('Bénéficiaires'),
                'title' => _('Gérer les bénéficiaires autorisés pour les dépenses.'),
                'condition' => Auth::can('manage', 'depense'),
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
            [
                'url' => '/achats/demandes',
                'text' => _('Demandes d\'achats'),
                'title' => _('Formuler et approuver les demandes d\'achats.'),
                'condition' => Auth::can('view', 'achat_demande'),
            ],
            [
                'url' => '/achats/commandes',
                'text' => _('Bons de commande'),
                'title' => _('Émettre et suivre les bons de commande.'),
                'condition' => Auth::can('view', 'achat_commande'),
            ],
            [
                'url' => '/achats/receptions',
                'text' => _('Bons de réception'),
                'title' => _('Gérer et valider les réceptions d\'achats.'),
                'condition' => Auth::can('view', 'achat_reception'),
            ],
            [
                'url' => '/achats/factures',
                'text' => _('Factures d\'achats'),
                'title' => _('Rapprocher et régler les factures fournisseurs.'),
                'condition' => Auth::can('view', 'achat_facture'),
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
        'condition' => Auth::can('view_all', 'user') || Auth::can('view_all', 'role') || Auth::can('view_all_lycees', 'lycee') || Auth::can('manage', 'annee_academique') || Auth::can('manage', 'sequence') || Auth::can('manage', 'cycle') || Auth::can('edit', 'param_lycee') || Auth::can('edit', 'param_general') || Auth::can('edit', 'param_devoir') || Auth::can('edit', 'param_composition') || Auth::can('manage', 'bulletin_template') || Auth::get('role_name') === 'super_admin_createur',
        'submenu' => [
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
                'url' => '/licences',
                'text' => _('Licences'),
                'title' => _('Gérer les licences de l\'application.'),
                'condition' => Auth::get('role_name') === 'super_admin_createur',
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
                'url' => '/param-devoir',
                'text' => _('Paramètres Devoirs'),
                'title' => _('Configurer les paramètres pour les devoirs.'),
                'condition' => Auth::can('edit', 'param_devoir'),
            ],
            [
                'url' => '/param-composition',
                'text' => _('Paramètres Compositions'),
                'title' => _('Configurer les paramètres pour les compositions.'),
                'condition' => Auth::can('edit', 'param_composition'),
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
