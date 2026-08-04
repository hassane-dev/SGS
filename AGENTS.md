# Baseline d'Architecture v1.0 - Phase 4 (Pilotage et Contrôle Budgétaire)

Ce document fige les règles, invariants, architectures, contrats de services, permissions RBAC, routes, interfaces, événements et critères de validation pour l'implémentation de la Phase 4.

## 1. Politiques Budgétaires

Le cycle de vie d'une dépense interagit avec le budget selon les règles strictes suivantes :

| Étape de la dépense | Transition de statut de dépense | Impact budgétaire | Statut de l'engagement |
| :--- | :--- | :--- | :--- |
| **Soumission** | `brouillon` -> `en_attente_approbation` | Réserve le montant sur la ligne budgétaire (`montant_engage` + montant) | `reserve` |
| **Approbation** | `en_attente_approbation` -> `approuve` | Conserve la réservation (engagement validé) | `engage` |
| **Rejet** | `en_attente_approbation` -> `rejete` | Libère le montant engagé (`montant_engage` - montant) | `libere` |
| **Paiement** | `approuve` -> `paye` | Consomme le budget (`montant_engage` - montant, `montant_consomme` + montant) | `consomme` |
| **Annulation (Contre-passation)** | `paye` -> `annule` | Restaure le budget consommé (`montant_consomme` - montant) | `annule` |

## 2. États Budgétaires d'un Engagement

La table `budget_engagements` suit l'état d'un engagement avec la colonne `statut` prenant les valeurs :
* `reserve` : Réservé temporairement lors de la soumission de la demande de dépense.
* `engage` : Confirmé suite à l'approbation de la dépense.
* `consomme` : Définitif après paiement de la dépense.
* `libere` : Libéré et annulé suite au rejet de la dépense.
* `annule` : Annulé rétroactivement après contre-passation de la dépense.

## 3. Invariants Budgétaires

* **Invariant 1 : Solde non négatif.** Le budget disponible d'une ligne (`allocation_initiale + ajustements - montant_engage - montant_consomme`) ne peut jamais devenir négatif, sauf si l'autorisation exceptionnelle de dépassement est activée sur la dépense.
* **Invariant 2 : Unicité de l'engagement.** Un engagement budgétaire est lié à exactement une dépense et une seule ligne budgétaire.
* **Invariant 3 : Réciprocité des opérations.** Toute opération d'annulation (rejet ou contre-passation) doit impérativement libérer ou restaurer les montants correspondants au centime près.
* **Invariant 4 : Isolation par Lycée.** Un budget ou ajustement est strictement étanche à un `lycee_id`.

## 4. ADR (Architectural Decision Records) de la Phase 4

* **ADR-001 (Unique Authority) :** `BudgetService` est la seule autorité responsable de la manipulation directe des soldes et écritures dans les tables budgétaires.
* **ADR-002 (No direct writes) :** Les contrôleurs et autres services (y compris `DepenseWorkflowService`) ne modifient jamais directement les lignes budgétaires. Ils délèguent cette responsabilité à `BudgetService`.
* **ADR-003 (Strict Adjustments) :** Les transferts de crédits entre lignes s'effectuent exclusivement via `BudgetAdjustmentService` avec traçabilité complète.
* **ADR-004 (Reporting Separation) :** Les agrégations visuelles et les indicateurs de performance budgétaire sont fournis par `BudgetReportingService` pour découpler les opérations OLTP et analytiques.

## 5. Contrats des Services

### `BudgetService`
* `createBudget(array $data): int`
* `createBudgetLine(array $data): int`
* `reserve(int $depenseId, float $amount, int $ligneId): int`
* `consume(int $depenseId): bool`
* `release(int $depenseId): bool`
* `restore(int $depenseId): bool`

### `BudgetControlService`
* `checkAvailability(int $ligneId, float $amount): array`  - Retourne `['disponible' => bool, 'solde_restant' => float]`
* `canSubmit(int $depenseId): bool` - Analyse si la dépense respecte les limites budgétaires.

### `BudgetAdjustmentService`
* `transferBudget(int $sourceLineId, int $destLineId, float $amount, int $userId, string $reason): int`
* `allocateExtra(int $ligneId, float $amount, int $userId, string $reason): int`

### `BudgetReportingService`
* `getBudgetSummary(int $budgetId): array`
* `getLigneDetails(int $ligneId): array`
* `getGlobalLignes(int $lyceeId, int $exerciceId): array`

## 6. Permissions RBAC (Seeding additionnel)

Les permissions suivantes doivent être insérées dans la table `permissions` :
* `budget.view` : Consulter la liste des budgets et lignes budgétaires.
* `budget.create` : Configurer un nouveau budget annuel pour l'établissement.
* `budget.update` : Modifier ou ajouter des lignes de budget.
* `budget.approve` : Valider officiellement un budget annuel.
* `budget.adjust` : Ajouter une allocation supplémentaire d'urgence sur une ligne.
* `budget.transfer` : Effectuer un virement de crédits entre deux lignes budgétaires.
* `budget.close` : Clôturer un exercice budgétaire.

## 7. Routes Enregistrées

* `/budgets` (GET) : Liste des budgets par lycée
* `/budgets/create` (GET/POST) : Formulaire et soumission de création de budget
* `/budgets/show/{id}` (GET) : Visualisation des lignes budgétaires
* `/budgets/lines/create` (GET/POST) : Ajout d'une ligne budgétaire
* `/budgets/adjustment` (GET/POST) : Transfert de crédits et suppléments
* `/budgets/report` (GET) : Synthèse visuelle et graphiques d'exécution

## 8. Interfaces d'Administration

Les interfaces sont construites en respectant le thème **Able Pro** (header/footer, pc-container, pc-content, icônes Phosphor) :
* **Liste des budgets** : Tableau synthétique avec statut (Brouillon, Approuvé, Clôturé).
* **Fiche Budget** : Liste des lignes budgétaires affichant pour chacune : Allocation Initiale, Ajustements, Engagé, Consommé, Restant, et une barre de progression colorée (Verte < 70%, Orange 70-90%, Rouge > 90%).
* **Formulaire d'ajustement** : Permet de choisir soit un "Virement entre lignes", soit une "Dotation supplémentaire".
* **Rapport budgétaire** : Graphiques et indicateurs clés d'exécution (Taux de consommation globale, top des catégories dépensières).

## 9. Événements et Audit Trail

Chaque événement métier écrit dans la table `budget_historique` :
* `BudgetCreated`
* `BudgetApproved`
* `BudgetAdjusted`
* `BudgetExceeded`
* `BudgetConsumed`
* `BudgetReleased`

## 10. Tables & Migrations

Cinq nouvelles tables à créer :

### `budgets`
* `id` PRIMARY KEY
* `lycee_id` INT FK
* `exercice_financier_id` INT FK
* `libelle` VARCHAR(150)
* `statut` ENUM('brouillon', 'approuve', 'cloture') DEFAULT 'brouillon'
* `cree_par` INT FK
* `date_creation` TIMESTAMP

### `budget_lignes`
* `id` PRIMARY KEY
* `budget_id` INT FK ON DELETE CASCADE
* `categorie_id` INT FK
* `centre_cout_id` INT DEFAULT NULL FK
* `allocation_initiale` DECIMAL(15,2) NOT NULL
* `montant_ajustements` DECIMAL(15,2) DEFAULT 0.00
* `montant_engage` DECIMAL(15,2) DEFAULT 0.00
* `montant_consomme` DECIMAL(15,2) DEFAULT 0.00
* UNIQUE KEY (budget_id, categorie_id, centre_cout_id)

### `budget_ajustements`
* `id` PRIMARY KEY
* `lycee_id` INT FK
* `type_ajustement` ENUM('dotation_supplementaire', 'transfert')
* `ligne_source_id` INT FK (NULL si dotation)
* `ligne_destination_id` INT FK
* `montant` DECIMAL(15,2) NOT NULL
* `motif` TEXT NOT NULL
* `execute_par` INT FK
* `date_ajustement` TIMESTAMP

### `budget_engagements`
* `id` PRIMARY KEY
* `depense_id` INT FK ON DELETE CASCADE
* `budget_ligne_id` INT FK
* `montant` DECIMAL(15,2) NOT NULL
* `statut` ENUM('reserve', 'engage', 'consomme', 'libere', 'annule')
* `date_engagement` TIMESTAMP

### `budget_historique`
* `id` PRIMARY KEY
* `lycee_id` INT FK
* `evenement` VARCHAR(100)
* `details` TEXT
* `execute_par` INT FK
* `date_evenement` TIMESTAMP

## 11. Definition of Done (DoD)

1. **Migrations réussies** : 100% exécutées sur SQLite et MySQL sans erreur.
2. **Compatibilité ascendante** : Les dépenses créées en Phase 3 n'échouent pas, et fonctionnent si aucun budget n'est configuré (comportement dégradé ou contrôle optionnel).
3. **Zéro régression** : `tests/test_phase_3.php` et autres tests de validation s'exécutent avec un succès de 100%.
4. **Couverture de tests d'intégration** : Couverture complète de tous les cas (engager, consommer, rejeter, annuler, virement de crédits, dépassements autorisés ou bloqués).
5. **Validation visuelle** : Captures d'écrans ou scripts Playwright d'intégration validant les vues Able Pro de la Phase 4.
