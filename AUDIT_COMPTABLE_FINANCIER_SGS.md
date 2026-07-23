# AUDIT ARCHITECTURAL COMPLET DU SYSTÈME COMPTABLE ET FINANCIER DE SGS

---

## 1. Résumé exécutif

L'audit architectural, fonctionnel et métier du système comptable et financier de **SGS (School Management System)** a été réalisé à partir de la structure réelle actuelle de sa base de données (`db/schema.sql`, `db/seeds.sql`) et de son code source PHP existant (`src/controllers/PaiementController.php`, `src/models/FinancialStatusService.php`, etc.).

### Constat Principal :
👉 **Non**, le système SGS dans sa version actuelle **ne permet pas** de savoir où se trouve l'argent, combien l'établissement possède réellement dans ses comptes de trésorerie, combien il a dépensé de façon consolidée, quelles sont ses dettes globales, ses actifs physiques ou son bilan comptable.

Bien que le workflow de **scolarité des élèves** (droits d'inscription, mensualités, avantages financiers et encaissements) soit **très mature, hautement centralisé et fonctionnel**, l'application souffre d'un manque fondamental de structure pour la **gestion de la trésorerie générale, des dépenses réelles de fonctionnement et des comptes financiers**.

L'argent est traité comme un attribut de suivi de la scolarité de l'élève (créances vs encaissements reçus), mais n'est pas versé ou tracé dans des entités de comptes financiers réels (Caisse, Banque, Mobile Money) possédant des soldes liquides dynamiques. Le journal comptable existant (`journal_comptable`) est immuable et documente fidèlement les transactions de scolarité, mais il n'a aucun lien de réciprocité avec des écritures comptables classiques à double entrée ou des écritures de dépenses de fonctionnement.

---

## 2. Ce qui fonctionne déjà

Les fonctionnalités suivantes sont **PRÉSENT ET OPÉRATIONNEL** (ou pleinement intégrées dans la logique applicative) :

### A. La scolarité et les inscriptions (`inscriptions`, `frais`, `etudes`)
* **Grille tarifaire dynamique :** Définition complète des frais par classe, cycle, niveau et série (`frais`).
* **Suivi de la scolarité annuelle :** Géré par le modèle `Etude` et matérialisé par la table `etudes`.
* **Règlement de l'inscription :** Enregistrement des frais d'inscriptions de base ajustés, incluant les options de carte scolaire informatisée et de logo d'uniforme.
* **Fichiers & Modèles concernés :**
  - Table : `frais`, `inscriptions`, `etudes`.
  - Modèle : `Frais`, `Inscription`, `Etude`.
  - Contrôleur : `PaiementController::processPayment`, `PaiementController::show`.

### B. Le suivi des mensualités et paiements partiels (`mensualites`, `mensualite_details`)
* **Séquencement chronologique :** Dérivation automatique des mensualités dues à partir des périodes définies par les séquences de l'année académique (`sequences`).
* **Fractionnement des versements :** Prise en charge des paiements partiels via `mensualite_details`. Chaque versement partiel est relié à une mensualité parent globale.
* **Fichiers & Modèles concernés :**
  - Table : `mensualites`, `mensualite_details`.
  - Modèle : `Mensualite`.
  - Contrôleur : `PaiementController::processPayment` (avec répartition chronologique automatique du pool de paiement sur les mois les plus anciens).

### C. La centralisation de la logique financière (`FinancialStatusService`)
* **Calculateur unifié :** `FinancialStatusService::getStudentFinancialStatus()` sert de point d'entrée unique pour calculer le montant total dû, le montant déjà payé, le reste à payer sur inscription et sur mensualités pour chaque élève. Cela évite les calculs redondants ou asynchrones.
* **Fichiers & Modèles concernés :**
  - Modèle : `FinancialStatusService`, `FinanceService`.

### D. Le journal comptable immuable (`journal_comptable`)
* **Livre d'audit :** Tout événement financier (encaissement, annulation, remboursement) génère une entrée automatique dans `journal_comptable`. Les suppressions physiques sont totalement proscrites de la base.
* **Fichiers & Modèles concernés :**
  - Table : `journal_comptable`.
  - Modèle : `JournalComptable`.

### E. Avantages financiers et Politiques d'accès (`parametres_financiers_eleves`, `politiques_financieres`)
* **Bourses, exonérations et réductions :** Logique complète de modération des frais d'inscription ou de mensualités (`ParametreFinancierEleve`).
* **Blocage automatique des notes/bulletins :** Contrôle d'accès basé sur la politique financière du lycée (`PolitiqueFinanciere`) et matérialisé par la table cache `etats_financiers_eleves`.
* **Fichiers & Modèles concernés :**
  - Table : `parametres_financiers_eleves`, `politiques_financieres`, `etats_financiers_eleves`.
  - Modèle : `ParametreFinancierEleve`, `PolitiqueFinanciere`, `FinanceService::updateFinancialState`.

---

## 3. Ce qui est partiellement implémenté

Les fonctionnalités suivantes sont **PARTIELLEMENT IMPLÉMENTÉES** :

### A. Gestion des salaires (`salaires`, `type_contrat`)
* **État actuel :** Il existe une table `salaires` et un contrôleur `SalaireController` permettant d'enregistrer des paiements de salaires fixes ou horaires pour le personnel. Une fiche de paie brute en PDF peut être générée via `genererFiche()`.
* **Insuffisance :** Ces enregistrements de salaires sont isolés. Ils ne déclenchent **aucune sortie de caisse ou de compte financier réel**. Ils ne sont pas non plus loggés dans le `journal_comptable` général, rendant impossible la production d'un compte de résultat ou d'un bilan unifié des dépenses de fonctionnement.
* **Fichiers concernés :**
  - Table : `salaires`, `type_contrat`.
  - Modèle : `Salaire`.
  - Contrôleur : `SalaireController`.

### B. Gestion de la Boutique (`boutique_articles`, `boutique_ventes`, `boutique_achats`)
* **État actuel :** Il existe un module boutique complet permettant de créer des articles, de gérer un stock brut et d'enregistrer des ventes d'articles aux élèves (uniformes, fournitures, etc.) avec génération de reçu `REC-B-XXXXXX`.
* **Insuffisance :** Bien que l'achat diminue le stock physique dans `boutique_articles`, l'encaissement de la vente **n'alimente aucun compte financier ni caisse**. Le modèle `BoutiqueVente` fait un appel défensif (`file_exists('Paiement.php')`) qui est inactif, et l'argent collecté ne passe **pas** dans le `journal_comptable` général.
* **Fichiers concernés :**
  - Table : `boutique_articles`, `boutique_ventes`, `boutique_achats`.
  - Modèle : `BoutiqueVente`.
  - Contrôleur : `BoutiqueAchatController`.

---

## 4. Ce qui manque totalement

Les fonctionnalités et concepts indispensables suivants sont **ABSENT** de l'application :

### A. La notion de Caisse Physique et de Comptes Financiers
* **Rien n'existe :** Il n'y a aucune table représentant des comptes (ex: Caisse Principale, Banque SG, Mobile Money), aucun concept de compte financier et aucun solde liquide courant. L'argent collecté s'évanouit virtuellement sous forme de données d'historique de paiement d'élèves.
* **Pas de mouvements de caisse généraux :** Impossible de faire des dépôts, des retraits ou des alimentations directes hors scolarité.

### B. Le workflow complet de gestion des Dépenses de Fonctionnement
* **Aucune table pour les dépenses ordinaires :** En dehors des salaires (qui sont isolés), il est impossible d'enregistrer l'achat de consommables, le règlement de factures d'électricité/eau, l'achat de craies ou des frais de déplacement.
* **Pas de processus d'approbation :** Aucun workflow distinguant "Dépense demandée", "Dépense validée", et "Dépense payée".

### C. La gestion des Transferts de Fonds Internes
* **Impossible d'exécuter un virement de compte à compte :** Déplacer de l'argent de la "Caisse principale" vers la "Banque" n'a aucun support de données. Faire cette opération aujourd'hui forcerait à créer une fausse dépense et une fausse recette, faussant complètement les statistiques.

### D. La gestion des Actifs / Immobilisations (Assets)
* **Pas de registre physique :** SGS ne possède aucun moyen de répertorier le parc informatique, les véhicules de l'école, les tables-bancs, ou les bâtiments. Aucun suivi des valeurs d'acquisition, amortissements, cessions ou pertes de matériel.

### E. La gestion des Passifs et Dettes Fournisseurs
* **Absence totale :** SGS ne gère pas les engagements financiers envers des tiers (fournisseurs de boutique, prestataires de travaux). Aucune distinction entre une dette contractée, validée, payée ou en souffrance.

### F. Clôtures de caisse quotidiennes et comptables mensuelles
* **Pas de garde-fou temporel :** Il n'existe aucun concept de clôture de caisse quotidienne (ouverture de caisse avec solde initial, fermeture, comptage physique, constat d'écart de caisse) ni de clôture comptable mensuelle verrouillant les modifications. Seule la clôture de l'année académique existe, mais elle est incomplète (voir section 12).

---

## 5. Les risques architecturaux actuels

1. **Risque de volatilité financière (Volatilité du Cash) :**
   En l'absence de comptes de trésorerie réels, il n'y a aucune corrélation entre les paiements déclarés dans la scolarité et les montants physiques réellement présents dans les coffres de l'école ou à la banque.
2. **Risque d'anomalies de modification et de fraude :**
   Même si les tables d'inscriptions et de scolarités possèdent une colonne de statut (valide, annule, rembourse), n'importe quel administrateur ou comptable local peut théoriquement modifier ou manipuler les flux financiers historiques sans qu'un rapprochement avec un solde de caisse journalier ne vienne bloquer l'opération.
3. **Erreur d'isolation multi-lycée (Multi-établissements) :**
   Puisque les statistiques de paiement du dashboard (`PaiementController::index`) ou du journal font des sommes globales basées sur `lycee_id`, mais que les dépenses (salaires) et les ventes boutique ne sont pas consolidées, le résultat financier d'un établissement est erroné.
4. **Utilisation détournée du Journal Comptable :**
   La table `journal_comptable` fait actuellement office de livre de recettes uniquement pour les élèves. L'utiliser pour des dépenses de fonctionnement ou des écritures de paie polluerait son format actuel qui requiert optionnellement un `eleve_id`.

---

## 6. La cartographie complète des flux financiers existants

Voici le cycle de vie de l'argent lors des opérations opérationnelles existantes :

```
[Opération Élève]
       │
       ▼
 [Vérification Grille Tarifaire] (Frais/Paramètres financiers individuels)
       │
       ▼
 [processPayment] (Transaction SQL) ───► Met à jour inscriptions ou mensualite_details
       │
       ▼
 [Journalisation] ─────────────────────► Ajoute une ligne dans journal_comptable (Immuable)
       │
       ▼
 [Calcul de statut] ───────────────────► Met à jour la table cache etats_financiers_eleves
       │
       ▼
 [Activation Élève] ───────────────────► Modifie eleves.statut à 'actif' et etudes.is_active à 1
```

### Détail par opération :

| Opération Financière | Table Source | Modèle PHP | Contrôleur | Utilisateur habilité | Journal alimenté | Rapprochement de Solde |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Paiement d'inscription** | `inscriptions` | `Inscription` | `PaiementController::processPayment` | Caissier, Comptable, Chef comptable, Admin local | `journal_comptable` | Aucun solde de compte |
| **Paiement de mensualité** | `mensualites` & `mensualite_details` | `Mensualite` | `PaiementController::processPayment` | Caissier, Comptable, Chef comptable, Admin local | `journal_comptable` | Aucun solde de compte |
| **Paiement partiel** | `mensualite_details` | `Mensualite` | `PaiementController::processPayment` | Caissier, Comptable, Chef comptable, Admin local | `journal_comptable` | Aucun solde de compte |
| **Remboursement** | `inscriptions` (déduit) / `mensualites` | `Inscription` / `Mensualite` | `PaiementController::rembourser` | Chef comptable, Admin local, Super admin | `journal_comptable` (valeur négative) | Aucun solde de compte |
| **Annulation de Reçu** | `inscriptions` (annulé) / `mensualite_details` | `Inscription` / `Mensualite` | `PaiementController::annulerRecu` | Chef comptable, Admin local, Super admin | `journal_comptable` (valeur négative) | Aucun solde de compte |
| **Vente boutique** | `boutique_ventes` & `boutique_achats` | `BoutiqueVente` | `BoutiqueAchatController::store` | Caissier, Comptable, Chef comptable, Admin local | **Aucun (ABSENT)** | Aucun solde de compte |
| **Paiement salaire** | `salaires` | `Salaire` | `SalaireController::store` | Comptable, Chef comptable, Admin local | **Aucun (ABSENT)** | Aucun solde de compte |

---

## 7. Cartographie de la trésorerie existante

👉 **ABSENT**

SGS ne possède aucune cartographie de trésorerie. Il n'existe pas de comptes liquides.
* L'argent n'est affecté à aucune caisse physique.
* Il n'y a aucun suivi de "Qui possède quoi" à un instant T.

---

## 8. Cartographie des recettes

Les recettes réelles de SGS proviennent de 3 sources distinctes, mais traitées de façon hétérogène :

1. **Frais d'inscriptions et scolarités :**
   * **Flux :** Élève ──► Enregistrement dans `inscriptions`/`mensualites` ──► `journal_comptable`.
   * **Traitement :** Tracé et audité.
2. **Frais d'options (Logo Uniforme, Carte Scolaire) :**
   * **Flux :** Élève ──► Ajouté au total de la scolarité ──► `details_frais` (JSON) ──► `journal_comptable`.
   * **Traitement :** Concaténé dans les écritures d'inscription, difficile à extraire pour des rapports analytiques isolés.
3. **Ventes de la Boutique :**
   * **Flux :** Élève ──► `boutique_ventes` / `boutique_achats`.
   * **Traitement :** Isolé, non comptabilisé dans le grand livre.

---

## 9. Cartographie des dépenses

Les dépenses sont extrêmement embryonnaires et non intégrées :

1. **Salaires du personnel (Partiellement implémenté) :**
   * **Flux :** Écriture purement déclarative dans `salaires`.
   * **Traitement :** Aucun impact sur les liquidités, aucune trace comptable unifiée.
2. **Dépenses de fonctionnement (Consommables, électricité, travaux) :**
   * **Flux :** **ABSENT**
3. **Achats de stocks pour la boutique :**
   * **Flux :** **ABSENT**

---

## 10. Cartographie des actifs

👉 **ABSENT**

Aucune table ni modèle ne gère les immobilisations (bâtiments, ordinateurs, matériel pédagogique, mobilier) ou la valeur immobilisée de l'établissement. Le stock de la boutique (`boutique_articles.stock * boutique_articles.prix`) est la seule donnée matérielle évaluable, mais elle n'est pas consolidée comptablement.

---

## 11. Cartographie des passifs

👉 **ABSENT**

Aucune gestion des dettes envers des tiers ou fournisseurs, ni des charges à payer à long terme.

---

## 12. Analyse des clôtures

| Type de clôture | Existence | Table concernée | Qui la déclenche | Qui la valide | Réouverture possible ? | Impact sur les opérations |
| :--- | :---: | :--- | :--- | :--- | :---: | :--- |
| **Clôture Quotidienne** | ❌ **ABSENT** | N/A | N/A | N/A | N/A | N/A |
| **Clôture Mensuelle** | ❌ **ABSENT** | N/A | N/A | N/A | N/A | N/A |
| **Clôture Comptable** | ❌ **ABSENT** | N/A | N/A | N/A | N/A | N/A |
| **Clôture Annuelle** | ⚠️ **PARTIEL** | `annees_academiques` | Chef comptable / Admin | Chef comptable / Admin | Oui | Bloque les créations/modifications de transactions pour cette année pour les utilisateurs normaux. |

### Focus sur la clôture annuelle existante :
La colonne `cloturee` dans `annees_academiques` agit comme un verrou défensif dans `PaiementController::processPayment`, `annulerRecu` et `rembourser` :
```php
if (!empty($anneeActive['cloturee'])) {
    if (!Auth::can('cloturer', 'annee_academique')) {
        throw new Exception("L'année académique active est clôturée...");
    }
}
```
* **Ce qu'elle protège :** Elle empêche les caissiers et les comptables de modifier, d'annuler ou de rembourser des règlements d'élèves rattachés à une année académique fermée.
* **Ce qu'elle ne protège pas :** Elle ne protège pas l'altération directe des données d'inscriptions ou d'études par des scripts externes, ne gèle pas les salaires ou la boutique, et permet à tout utilisateur disposant de la permission `cloturer` de contourner silencieusement le verrou à tout moment directement depuis l'interface de paiement sans journaliser l'autorisation d'écriture exceptionnelle.

---

## 13. Analyse du RBAC (Contrôle d'accès basé sur les rôles)

Le système implémente une vérification dynamique de permissions très propre via `Auth::can($action, $resource)` s'appuyant sur les tables `roles`, `permissions` et `role_permissions`.

### Cartographie des permissions financières définies dans `db/seeds.sql` :

* `paiement` / `manage` : Caissier, Comptable, Chef Comptable, Admin Local, Super Admin.
* `paiement` / `view` : Caissier, Comptable, Chef Comptable, Admin Local, Super Admin, Censeur, Surveillant.
* `paiement` / `cancel` (annuler_recu) : Chef Comptable, Super Admin.
* `paiement` / `refund` (rembourser_eleve) : Chef Comptable, Super Admin.
* `salaire` / `manage` : Comptable, Chef Comptable, Admin Local, Super Admin.
* `frais` / `manage` : Admin Local, Super Admin.
* `annee_academique` / `cloturer` : Chef Comptable, Super Admin.

### Risques critiques identifiés dans la séparation des responsabilités (SoD) :

1. **Rôle `admin_local` tout-puissant :**
   Un administrateur local possède à la fois les droits de configurer les frais (`frais / manage`), d'encaisser l'argent (`paiement / manage`), d'enregistrer des salaires (`salaire / manage`) et d'administrer les comptes. Bien qu'il n'ait pas théoriquement la permission `cancel` ou `refund` dans les seeds par défaut, son niveau d'accès lui permet d'altérer les grilles de frais pour blanchir ou camoufler des écarts de caisse.
2. **Absence de workflow de double validation des remboursements :**
   Le chef comptable peut initier un remboursement ET le finaliser sans validation d'un Directeur ou d'un administrateur indépendant.

---

## 14. Audit Multi-Lycée / Multi-Tenant

SGS est structurellement multi-établissement grâce à la colonne `lycee_id` présente sur presque toutes les entités importantes (`eleves`, `etudes`, `classes`, `inscriptions`, `mensualites`, `journal_comptable`, `boutique_articles`, `salaires`).

### Analyse d'étanchéité :

* **Inscriptions et Mensualités :** Les sélections SQL filtrent rigoureusement par `lycee_id` récupéré de la session de l'utilisateur connecté (`Auth::getLyceeId()`). Le risque de fuite de données d'élève ou de recette entre deux lycées au niveau de la scolarité est **très faible**.
* **Journal Comptable :** La méthode `JournalComptable::findAll` filtre correctement par `lycee_id`.
* **Salaires et Boutique :** Les modèles filtrent correctement par `lycee_id` s'il est transmis.
* **Risques de fuites :** Le risque majeur réside dans les rôles globaux (comme `super_admin_national` ou `super_admin_createur`) qui peuvent exécuter des rapports croisés, mais dont les filtres par défaut doivent être configurés de manière défensive pour éviter le mélange de trésorerie physique si un compte bancaire ou une caisse devenait partagé.

---

## 15. Audit des modes de paiement

SGS propose différents modes de paiement via la configuration textuelle `modalite_paiement` de la table `param_general` (ex: `'Espèces, Chèque, Mobile Money, Virement'`).

* **État de la distinction :** **ABSENT / TEXTUEL UNIQUEMENT**
* Le mode de paiement choisi lors d'un règlement de scolarité (`mode_paiement` dans `mensualite_details` ou `journal_comptable`) est enregistré comme une **simple chaîne de caractères**.
* **Le Risque :** Ce texte n'est relié à aucun compte financier physique de la base de données. Que l'utilisateur choisisse "Espèces" ou "Mobile Money", l'enregistrement est identique dans la table `journal_comptable`. Il n'y a aucun mécanisme automatique pour verser les espèces dans un registre de caisse et les règlements par Mobile Money sur le compte de l'opérateur télécom. Cela rend tout rapprochement bancaire ou contrôle de caisse impossible sans ressaisie manuelle sur un outil externe.

---

## 16. Incohérences et doublons de logique

1. **Double comptabilisation potentielle de la scolarité :**
   Pour obtenir le reste à payer d'un élève, `FinancialStatusService` interroge la table `frais` et les versements d'inscription/mensualités réelles. Cependant, la table `mensualites` possède sa propre colonne `reste_a_payer`. Si un paiement partiel est validé mais que cette colonne cache n'est pas resynchronisée de façon unanime, des divergences d'affichage apparaissent.
2. **Logique d'activation asynchrone :**
   L'élève passe au statut `actif` et son étude est activée (`Etude::activate`) à la fois lors du paiement dans `PaiementController::processPayment`, mais aussi potentiellement lors d'une inscription administrative manuelle dans d'autres contrôleurs, sans vérification cohérente de la politique financière.
3. **Le flou des "Autres Frais" :**
   La table `frais` comprend une colonne `autres_frais` au format JSON. Actuellement, cette colonne est totalement inutilisée par les algorithmes de calcul du statut financier global, qui ne prennent en charge que les frais fixes prédéterminés (inscription, mensualité, carte, logo).

---

## 17. Recommandations architecturales

Pour transformer SGS en un véritable ERP de gestion financière scolaire sans détruire l'excellent système d'encaissement de scolarité existant, nous recommandons :

1. **Introduire un module "Trésorerie et Comptes" :**
   Créer des comptes financiers réels (Caisse Physique, Comptes Bancaires, Mobile Money) rattachés à chaque lycée.
2. **Découpler le journal de scolarité de la comptabilité générale :**
   La table `journal_comptable` doit être conservée pour l'audit opérationnel de la scolarité. Cependant, toute opération financière validée (Scolarité, Dépense, Vente Boutique, Salaire) doit générer une écriture comptable normalisée dans un **Grand Livre Général (GL)**.
3. **Mettre en place un Registre des Dépenses de fonctionnement :**
   Créer un workflow d'enregistrement des dépenses hors paie, soumis à autorisation et déboursés depuis un compte financier spécifique.
4. **Implémenter la Clôture de Caisse quotidienne :**
   Verrouiller la caisse physique à la fin de chaque journée avec signature numérique du caissier et validation du comptable.

---

## 18. Architecture cible proposée

L'architecture cible repose sur un modèle à double impact : **Mouvement opérationnel ──► Écriture de Trésorerie**.

```
[Événement Comptable]
  │
  ├─► Recette Scolarité (Paiement élève)
  ├─► Vente Boutique (Articles)
  ├─► Paiement de Salaire (Régularisation du personnel)
  └─► Dépense de Fonctionnement (Facture, achat)
        │
        ▼
[Service de Trésorerie Centralisé] ────► Impacte le [Compte Financier] ciblé (Ajuste le solde liquide)
        │
        ▼
[Générateur d'Écritures du Grand Livre] ──► Écrit dans le [Grand Livre Comptable] (Débit/Crédit)
```

### Avantage de cette architecture :
Elle n'interfère pas avec les calculs complexes du `FinancialStatusService` de l'élève. Elle écoute simplement la validation des paiements et des dépenses pour mettre à jour les soldes de trésorerie réels.

---

## 19. Liste des tables potentiellement nécessaires (SANS LES CRÉER)

Pour l'implémentation future, les structures de tables suivantes seraient idéales :

### A. Comptes de Trésorerie (`comptes_financiers`)
* Représente les différents coffres-forts ou comptes bancaires de l'établissement.
* Colonnes : `id`, `lycee_id`, `nom_compte`, `type` (caisse, banque, mobile_money), `solde_courant`, `devise`, `responsable_id`, `statut` (actif, suspendu), `created_at`.

### B. Mouvements de Trésorerie (`mouvements_tresorerie`)
* Enregistre chaque flux d'entrée, de sortie ou de transfert de liquidités.
* Colonnes : `id`, `lycee_id`, `compte_id`, `type_mouvement` (entree, sortie, transfert), `montant`, `mode_paiement`, `reference_transaction`, `motif`, `date_mouvement`, `user_id`, `statut` (valide, annule).

### C. Sessions de Caisse (`sessions_caisse`)
* Permet aux caissiers d'ouvrir et de fermer leur caisse quotidiennement.
* Colonnes : `id`, `lycee_id`, `user_id`, `compte_id` (caisse concernée), `date_ouverture`, `date_fermeture`, `solde_ouverture`, `solde_theorique` (calculé), `solde_reel` (compté physiquement), `ecart`, `motif_ecart`, `statut` (ouverte, fermee), `valide_par_comptable_id`.

### D. Dépenses de fonctionnement (`depenses`)
* Suit le cycle de vie des achats et débours généraux.
* Colonnes : `id`, `lycee_id`, `categorie_depense_id`, `beneficiaire`, `montant`, `motif`, `compte_paiement_id` (compte financier débité), `date_facture`, `statut_validation` (en_attente, approuve, rejete, paye), `valide_par`, `paye_le`, `justificatif_chemin`, `user_id`.

### E. Catégories de Dépenses (`categories_depenses`)
* Nomenclature simple pour classer les dépenses (ex : Énergie, Matériel pédagogique, Maintenance, Fournitures).
* Colonnes : `id`, `lycee_id`, `libelle`, `code_comptable`.

---

## 20. Plan de migration progressive (SANS L'IMPLÉMENTER)

Une intégration sans rupture de service doit suivre 5 phases stratégiques :

```
┌────────────────────────┐      ┌────────────────────────┐      ┌────────────────────────┐
│  Phase 1 : Fondations  │ ───► │   Phase 2 : Trésor     │ ───► │  Phase 3 : Dépenses    │
│  Création des tables   │      │ Liaison des paiements  │      │ Intégration dépenses   │
│  comptes & catégories  │      │ scolaires aux caisses  │      │  et paies du personnel │
└────────────────────────┘      └────────────────────────┘      └────────────────────────┘
                                                                            │
┌────────────────────────┐      ┌────────────────────────┐                  │
│   Phase 5 : Clôture    │ ◄─── │   Phase 4 : Boutique   │ ◄────────────────┘
│ Automatisation rapports│      │ Enregistrement ventes  │
│ balance, journal caisse│      │ boutique dans trésor   │
└────────────────────────┘      └────────────────────────┘
```

1. **Phase 1 : Installation des Structures**
   * Exécuter la migration contenant les tables `comptes_financiers`, `categories_depenses`, `depenses`, `mouvements_tresorerie` et `sessions_caisse`. Seuls les super-administrateurs configurent les comptes par défaut.
2. **Phase 2 : Interception des Paiements Élèves**
   * Modifier `PaiementController::processPayment` pour qu'à la validation d'une inscription ou d'une mensualité, un enregistrement automatique soit créé dans `mouvements_tresorerie` sur le compte financier sélectionné (dérivé du mode de paiement ou de la session de caisse du caissier connecté).
3. **Phase 3 : Intégration des Dépenses et Salaires**
   * Implémenter le contrôleur `DepenseController`. Raccorder l'enregistrement des salaires (`SalaireController`) pour qu'à la bascule du statut de salaire en "payé", une écriture automatique de sortie de trésorerie soit générée sur le compte bancaire ou de caisse désigné.
4. **Phase 4 : Branchement de la Boutique**
   * Modifier le modèle `BoutiqueVente::create` pour y inclure l'écriture financière d'entrée en trésorerie dans `mouvements_tresorerie` rattachée à la caisse boutique ou caisse principale.
5. **Phase 5 : Automatisation des Rapports de Trésorerie**
   * Développer l'interface d'état financier global (Livre de Caisse, Journal de Banque, Balance des flux).

---

## 21. Liste des tests nécessaires

Pour garantir la résilience et la fiabilité absolue de la future architecture financière, les suites de tests unitaires et d'intégration suivantes devront être écrites :

### A. Tests de Trésorerie (Modèle & Service)
* **Test d'ajustement du solde :** Valider qu'un mouvement d'entrée de $100\ 000$ FCFA sur un compte de solde initial $0$ passe le solde à exactement $100\ 000$ FCFA.
* **Test d'interdiction de découvert :** Vérifier qu'une sortie de caisse supérieure au solde disponible lève une exception métier bloquante si le découvert n'est pas explicitement autorisé sur ce compte.
* **Test d'atomicité du transfert :** Valider qu'un transfert de $50\ 000$ FCFA de la Caisse vers la Banque débite l'un et crédite l'autre de manière atomique (en cas de plantage au milieu, l'opération entière doit être annulée).

### B. Tests d'Intégration du Workflow de Paiement
* **Test d'impact de paiement de scolarité :** Vérifier que l'appel à `PaiementController::processPayment` crée bien à la fois l'enregistrement d'inscription, l'entrée dans le journal d'audit ET le mouvement de trésorerie sur le compte financier ciblé.
* **Test d'annulation et contre-passation :** Valider que l'annulation d'un reçu de paiement de scolarité crée bien un mouvement de trésorerie inverse (négatif) automatique pour neutraliser le solde de la caisse.

### C. Tests de Cohérence Multi-Lycée
* **Test d'étanchéité des comptes :** Tenter de débiter ou de créditer un compte financier appartenant au Lycée B depuis une session d'utilisateur rattachée au Lycée A et s'assurer que le système lève une exception d'accès interdit.

---

## 22. Points de décision métier requis avant tout développement

Avant d'écrire la moindre ligne de code, la direction ou le porteur du projet SGS doit arbitrer les questions structurantes suivantes :

1. **Règles de gestion des écarts de caisse :**
   Lorsqu'un caissier ferme sa caisse quotidienne et déclare un écart physique (ex : moins d'argent dans le tiroir que le solde théorique calculé), le système doit-il bloquer la clôture en attendant l'avis du Chef Comptable, ou autoriser la clôture en enregistrant l'écart dans une catégorie spéciale "Pertes sur écart de caisse" ?
2. **Autorisation de solde négatif (Découvert) :**
   Les caisses physiques et comptes de Mobile Money peuvent-ils techniquement passer sous un solde de $0.00$ (en cas de retours ou d'écritures de correction), ou le système doit-il rejeter de manière absolue tout déboursement qui entraînerait un solde négatif ?
3. **Niveau d'approbation des dépenses :**
   Le workflow des dépenses doit-il être identique pour tous les lycées (ex: Créateur ──► Directeur pour approbation ──► Comptable pour paiement), ou doit-il s'ajuster dynamiquement selon la taille et la politique du lycée (mono-approbation ou multi-approbation) ?
4. **Gestion de la TVA et taxes locales :**
   L'établissement scolaire doit-il collecter et reverser des taxes sur ses ventes de boutique ou sur ses prestations annexes, nécessitant une gestion analytique des taxes dans le grand livre ?
5. **Commissions de transaction :**
   Pour les paiements par Mobile Money ou Virement bancaire, comment gère-t-on les frais de transaction bancaires ou télécoms ? Sont-ils à la charge de l'élève (ajoutés au reçu), ou absorbés comme charge financière par l'école (diminuant le net encaissé sur le compte) ?

---

### Fin du Rapport d'Audit Forensique.
*Aucun fichier source n'a été altéré, aucune table n'a été créée et aucune donnée n'a été modifiée.*

**Jules**
*Ingénieur Principal SGS*
