# Architecture de Sécurité & Contrat RBAC (Baseline v1.0)

Ce document décrit de manière officielle l'architecture de sécurité du système RBAC, la couche d'autorisation dans les contrôleurs, la structure de la navigation et les règles d'évolution de l'ERP GestSchool.

---

## 1. Cartographie du Système RBAC (La Source de Vérité)

Toutes les autorisations de l'application sont structurées autour du triptyque **Ressource**, **Action** et **Rôle**.

### A. Les Ressources Comptables & Financières
*   `sessions_caisse` : Gestion des sessions de caisse physiques journalières.
*   `comptes_financiers` : Comptes de trésorerie (banques, caisses, mobile money).
*   `finance` : Politiques financières, bourses, remises, et indicateurs analytiques.
*   `journal` : Journal comptable d'audit unique.
*   `depense` : Demandes d'engagements, validations et règlements de dépenses.
*   `budget` : Pilotage budgétaire, dotations et virements de crédits.
*   `paiement` : Recettes d'inscriptions, mensualités, et gestion des restes à payer.
*   `salaire` : Gestion de la paie du personnel.
*   `frais` : Structure tarifaire des classes.

### B. Les Actions et leur Hiérarchie
Le moteur d'autorisation implémente une cascade hiérarchique pour simplifier l'attribution des droits tout en protégeant les actions comptables critiques :

*   `*` ➔ Accès universel et absolu à toutes les actions de la ressource.
*   `manage` ➔ Englobe implicitement le CRUD standard : `view`, `create`, `edit`, `delete`, `view_all`, `view_one`.
*   **Actions indépendantes** : Pour des raisons réglementaires de séparation des tâches, les actions suivantes ne sont jamais englobées implicitement par une autre action et doivent être attribuées de manière explicite :
    *   `validate` (Approbation budgétaire ou signature de dépenses).
    *   `pay` (Règlement de dépenses).
    *   `report` (Consultation des synthèses d'exécution analytiques).
    *   `reopen` (Réouverture de caisse).

---

## 2. Le Guard Central d'Autorisation dans les Contrôleurs

Pour éliminer définitivement les erreurs de copier-coller et les fallbacks implicites défaillants, l'accès est gouverné par la structure suivante :

### A. Déclaration de Ressource Explicite
Chaque contrôleur déclare explicitement la ressource d'ancrage qu'il protège via une propriété de classe, par exemple :
```php
class JournalComptableController {
    // Protège strictement le journal comptable
    private function checkAccess($action = 'view', $resource = 'journal') { ... }
}
```
L'utilisation de paramètres par défaut pouvant conduire à une ressource autre que celle du domaine du contrôleur est bannie.

---

## 3. Matrice de Concordance RBAC / IHM

| Module | Ressource | Action Requise | Route Enregistrée | Contrôleur Associé | Vue Correspondante |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Tableau de Bord** | `paiement` | `view` | `/paiements` | `PaiementController::index` | `src/views/paiements/index.php` |
| **Sessions caisse** | `sessions_caisse` | `view` | `/treasury/sessions` | `SessionCaisseController::index` | `src/views/treasury/sessions/index.php` |
| **Politique fin.** | `finance` | `view_policy` | `/finance/policy` | `FinancePolicyController::edit` | `src/views/politiques_financieres/edit.php` |
| **Contrôle fin.** | `finance` | `view_control` | `/finance/control` | `ControleFinancierController::index` | `src/views/comptabilite/controle_financier/index.php` |
| **Budgets** | `budget` | `view` | `/budgets` | `BudgetController::index` | `src/views/budgets/index.php` |
| **Exécution budg.**| `budget` | `report` | `/budgets/report` | `BudgetController::reportGlobal` | `src/views/budgets/report.php` |
| **Ajustements** | `budget` | `adjust` | `/budgets/adjustment` | `BudgetController::adjustment` | `src/views/budgets/adjustment.php` |
| **Engagements** | `budget` | `view` | `/budgets/engagements` | `BudgetController::engagements` | `src/views/budgets/engagements.php` |
| **Demandes dép.** | `depense` | `view` | `/depenses` | `DepenseController::index` | `src/views/depenses/index.php` |
| **Validation dép.**| `depense` | `validate` | `/depenses/validation` | `DepenseController::validation` | `src/views/depenses/validation.php` |
| **Paiements dép.** | `depense` | `pay` | `/depenses/payments` | `DepenseController::payments` | `src/views/depenses/payments.php` |
| **Historique dép.**| `depense` | `view` | `/depenses/history` | `DepenseController::history` | `src/views/depenses/history.php` |
| **Journal** | `journal` | `view` | `/journal` | `JournalComptableController::index` | `src/views/comptabilite/journal/index.php` |
| **Grand Livre** | `journal` | `view` | `/grand-livre` | `JournalComptableController::grandLivre` | `src/views/comptabilite/journal/grand_livre.php` |
| **Balance** | `journal` | `view` | `/balance` | `JournalComptableController::balance` | `src/views/comptabilite/journal/balance.php` |
| **Rapports fin.** | `finance` | `view_reports` | `/reports/financial` | `RapportFinancierController::index` | `src/views/comptabilite/rapports/index.php` |
| **Salaires** | `salaire` | `manage` | `/salaires` | `SalaireController::index` | `src/views/salaires/index.php` |
| **Config. Frais** | `frais` | `manage` | `/frais` | `FraisController::index` | `src/views/frais/index.php` |
| **Catégories** | `depense` | `manage` | `/depenses/categories` | `DepenseController::categories` | `src/views/depenses/categories.php` |
| **Centres de coûts**| `depense` | `manage` | `/depenses/centres-couts` | `DepenseController::centresCouts` | `src/views/depenses/centres_couts.php` |
| **Bénéficiaires** | `depense` | `manage` | `/depenses/beneficiaires` | `DepenseController::beneficiaires` | `src/views/depenses/beneficiaires.php` |
| **Périodes Paie** | `paie` | `view` | `/paie/periodes` | `PaiePeriodesController::index` | `src/views/paie/periodes/index.php` |
| **Édition Période**| `paie` | `edit` | `/paie/periodes/{id}/edit` | `PaiePeriodesController::edit` | `src/views/paie/periodes/edit.php` |
| **Règles de Paie**| `paie` | `config` | `/paie/regles` | `PaieReglesController::index` | `src/views/paie/regles/index.php` |

---

## 4. Règle d'Évolution d'un Nouveau Module

Tout développement d'un nouveau module doit respecter le cycle complet d'évolution et d'intégration :

```
Permission ➔ Seeder ➔ Migration (Optionnelle) ➔ Route ➔ Contrôleur ➔ Service ➔ Vue ➔ Sidebar ➔ Tests ➔ Documentation
```

Aucun composant d'IHM ou de Sidebar ne peut être publié sans que sa chaîne de contrôle d'accès sous-jacente n'ait été auditée et intégrée dans la suite de tests automatique de non-régression.
