# DOSSIER DE CONCEPTION ET D'ARCHITECTURE FINANCIÈRE DÉTAILLÉE DE SGS (PHASE 2)

Ce document formalise la conception détaillée, les flux, les transitions d'états, les règles métier et la séparation des responsabilités pour le futur système de gestion financière et de trésorerie de SGS.

---

## 1. Réponses précises aux 19 questions architecturales et métiers

### Q1. Comment les paiements scolaires existants seront-ils reliés aux comptes financiers ?
Chaque mode de paiement proposé à l'encaissement sera lié à un compte financier cible défini au niveau du lycée :
- **Espèces :** Rattaché à la caisse physique ouverte du caissier connecté (via `sessions_caisse`).
- **Chèque, Virement :** Directement rattaché au compte financier de type `banque`.
- **Mobile Money :** Rattaché au compte de type `mobile_money` de l'opérateur concerné.

Lors de la soumission de la requête dans `PaiementController::processPayment`, le système déterminera automatiquement le compte financier destinataire. Un **mouvement de trésorerie** (`mouvements_tresorerie`) sera enregistré de manière atomique sous la même transaction de base de données que l'inscription ou la mensualité.

### Q2. Comment éviter qu'une modification du nouveau système casse le workflow actuel des inscriptions et des scolarités ?
Le principe fondamental de non-régression est de **ne jamais altérer la logique opérationnelle interne** de calcul des dettes et frais des élèves (`FinancialStatusService`).
- Le nouveau système financier sera conçu comme un **observateur passif** (via un service ou un intercepteur applicatif déclenché à la fin de la transaction opérationnelle).
- Les tables existantes (`inscriptions`, `mensualites`, `mensualite_details`) resteront l'unique source de vérité pour le statut de scolarité de l'élève.
- La liaison se fera uniquement par référence d'ID d'origine (`reference_origine`) dans les mouvements de trésorerie (ex: `inscriptions:45` ou `mensualite_details:108`).

### Q3. Comment seront gérées les annulations de paiements ?
Toute annulation de reçu (`PaiementController::annulerRecu`) générera une écriture de contre-passation (mouvement inverse) :
- Le statut du paiement d'origine passe à `annule` (préservant la trace de la transaction d'origine).
- Un mouvement de trésorerie négatif compensatoire est créé pour le montant exact annulé, lié à la même caisse ou compte financier d'origine.
- La session de caisse courante (si non clôturée) ou le compte bancaire est crédité d'un montant négatif, ajustant instantanément le solde théorique.

### Q4. Comment seront gérés les remboursements aux élèves ?
Le remboursement (`PaiementController::rembourser`) est un décaissement d'argent physique ou scriptural :
- Il est initié dans l'interface de l'élève en saisissant le montant et le motif obligatoire.
- Il fait l'objet d'un flux d'approbation (Comptable ──► Chef comptable/Directeur).
- Une fois approuvé et décaissé, il génère un mouvement de trésorerie de type `sortie` (valeur négative dans le grand livre) impactant le compte sélectionné pour le remboursement.

### Q5. Comment un remboursement impactera-t-il concrètement la caisse ou le compte financier concerné ?
- **Impact sur le solde :** Le solde courant (`solde_courant`) du compte financier débité est immédiatement décrémenté du montant remboursé.
- **Impact sur le grand livre :** Une entrée de type `remboursement` (montant négatif) est journalisée, référençant l'élève bénéficiaire et la pièce comptable de sortie de fonds.
- **Impact sur la scolarité :** Les statuts de l'inscription ou des mensualités de l'élève sont recalculés à la hausse (son reste à payer réaugmente).

### Q6. Comment seront gérés les transferts de fonds internes ?
Un transfert est un mouvement inter-comptes bilatéral unifié et atomique :
- Il est enregistré dans une table dédiée `transferts_financiers`.
- Il requiert deux mouvements de trésorerie corrélés au sein d'une même transaction de base de données :
  1. Une **sortie** (débit) du compte source (ex: Caisse principale).
  2. Une **entrée** (crédit) sur le compte destinataire (ex: Banque).
- **Règle métier stricte :** Un transfert interne n'est jamais classé comme une recette ni comme une dépense de fonctionnement, évitant ainsi de gonfler artificiellement les états de résultat de l'école.

### Q7. Comment seront gérées les dépenses générales de l'établissement ?
Elles seront gérées via un module complet de **Dépenses de Fonctionnement** comprenant :
- Un registre de catégories de dépenses (ex : Énergie, Fournitures, Maintenance).
- Un workflow d'autorisation structuré (Demande ──► Approbation ──► Paiement ──► Sortie de caisse/banque).
- L'obligation de joindre un justificatif numérique (facture, reçu de caisse).

### Q8. Comment seront gérés les salaires et les paiements de salaires ?
La table `salaires` servira de pièce justificative de dette de personnel.
- Lors de l'exécution du paiement du salaire (`SalaireController::store`), au lieu de simplement basculer l'état à `paye`, le contrôleur appellera le service de trésorerie.
- L'argent sera effectivement retiré du compte financier sélectionné (généralement le compte bancaire de fonctionnement).
- Un mouvement de trésorerie de type `sortie` sous la catégorie "Charges de personnel" sera enregistré de façon synchrone.

### Q9. Comment les ventes de la boutique seront-elles intégrées à la trésorerie et au journal comptable ?
Le modèle `BoutiqueVente::create` sera modifié pour :
- Identifier le compte financier associé aux ventes boutique (ex : "Caisse Boutique" ou "Caisse Principale").
- Créer un mouvement d'entrée de trésorerie pour le montant total de la vente.
- Ajouter une entrée automatique dans le journal comptable général sous l'opération `vente_boutique`, référençant le numéro de reçu de vente `REC-B-XXXXXX`.

### Q10. Comment fonctionneront exactement les sessions de caisse ?
La session de caisse lie physiquement un caissier à un compte de type "Caisse" pour une période de travail (généralement une journée) :
- **Ouverture :** Le caissier déclare le solde de démarrage physique (solde d'ouverture). Le système valide que ce solde correspond exactement au solde de fermeture de la session précédente.
- **Période active :** Tous les encaissements ou décaissements en espèces effectués par ce caissier sont taggués avec l'ID de sa session de caisse ouverte.
- **Fermeture :** Le caissier effectue un inventaire physique des billets et pièces, puis saisit le montant réel compté. La session est alors verrouillée.

### Q11. Comment seront calculés les soldes théoriques et réels ?
- **Solde Théorique :** $Solde\_Initial + \sum(Entrees) - \sum(Sorties)$ enregistrées informatiquement durant la session de caisse.
- **Solde Réel :** Somme de l'argent physique déclarée par le caissier lors de la fermeture de sa caisse.
- **Écart de Caisse :** $Solde\_Reel - Solde\_Theorique$.

### Q12. Comment seront gérés les écarts de caisse ?
Si l'écart est non nul lors de la fermeture de la caisse :
- L'écart est enregistré de manière permanente dans la table `sessions_caisse` (qu'il soit positif ou négatif).
- Le caissier doit obligatoirement saisir une note justificative décrivant l'origine suspectée de l'écart.
- La session passe à l'état `fermee_a_valider` en attendant l'audit du comptable ou du chef comptable.

### Q13. Qui pourra justifier, approuver ou corriger un écart de caisse ?
- **Caissier :** Peut uniquement justifier (rédiger un motif) l'écart lors de la demande de fermeture.
- **Comptable :** Peut auditer les lignes de transactions de la session pour identifier une omission d'écriture.
- **Chef comptable / Administrateur :** Seuls ces rôles peuvent **approuver la fermeture** définitive de la caisse et enregistrer une écriture de régularisation comptable (passant l'écart en perte ou en profit exceptionnel).

### Q14. Comment fonctionneront les clôtures quotidiennes, mensuelles et annuelles ?
- **Clôture quotidienne (Caisse) :** Verrouille la session de caisse d'un utilisateur. Plus aucun encaissement/décaissement en espèces ne peut être rattaché à cette session.
- **Clôture mensuelle (Comptable) :** Verrouille toutes les transactions du mois écoulé. Aucune modification, annulation ou dérogation rétroactive n'est autorisée. Le grand livre du mois est figé.
- **Clôture annuelle (Exercice financier) :** Clôture complète de l'année financière de l'établissement. Elle calcule le résultat net annuel et génère les soldes d'ouverture pour l'exercice suivant. La réouverture requiert un code de déblocage d'urgence hautement audité.

### Q15. Comment les actifs et immobilisations seront-ils intégrés ?
- Une table `actifs_immobilisations` recensera les biens de l'école (bâtiments, ordinateurs, véhicules, matériel pédagogique).
- Chaque actif aura une valeur d'acquisition historique, une durée de vie et une méthode d'amortissement (linéaire par défaut).
- La dépréciation annuelle générera des écritures d'amortissement automatiques dans le grand livre (charges non décaissables).

### Q16. Comment les passifs, dettes et obligations envers des tiers seront-ils intégrés ?
- Une table `dettes_passifs` suivra les engagements financiers (emprunts bancaires, dettes fournisseurs de boutique).
- Lors de la réception d'une facture fournisseur, la dette est enregistrée. Le paiement de cette facture générera une sortie de fonds d'un compte financier, ramenant le solde de la dette à zéro.

### Q17. Comment le RBAC séparera-t-il clairement les responsabilités ?
Chaque action financière suit une matrice de séparation des tâches (voir section 8 pour la matrice détaillée) pour empêcher qu'un seul utilisateur puisse réaliser l'intégralité d'un flux financier.

### Q18. Comment seront auditées les opérations exceptionnelles et les dérogations ?
- Toutes les actions exceptionnelles (réouverture de caisse, réouverture d'année clôturée, modification manuelle de solde) seront historisées dans une table `journal_audit_systeme`.
- Ce journal stockera : l'ID de l'auteur, l'horodatage précis, l'adresse IP, l'ancienne valeur, la nouvelle valeur et le motif obligatoire.

### Q19. Comment garantir une séparation complète des données et des flux financiers entre plusieurs lycées ?
- **Clé de cloisonnement :** La colonne `lycee_id` est obligatoire sur toutes les nouvelles tables financières.
- **Contrainte au niveau des requêtes (Multi-Tenant) :** Chaque requête SQL d'écriture ou de lecture appliquera une clause restrictive `WHERE lycee_id = :session_lycee_id`, interdisant l'affichage ou l'altération des comptes d'un lycée tiers.
- Les virements inter-lycées seront traités comme des transferts externes (soumis à un accord inter-établissements).

---

## 2. Distinction fondamentale des concepts

SGS respectera une étanchéité absolue entre les 5 couches conceptuelles de sa gestion financière :

```
┌───────────────────────────────────────────────────────────────────────────┐
│ 1. OPÉRATION MÉTIER (Niveau Scolarité / Élève)                           │
│    Exemple: Enregistrement d'un règlement de scolarité de 50 000 FCFA.    │
│    Tables concernées: `mensualites`, `mensualite_details`                 │
└─────────────────────────────────────┬─────────────────────────────────────┘
                                      │ (Déclenche)
                                      ▼
┌───────────────────────────────────────────────────────────────────────────┐
│ 2. ÉCRITURE COMPTABLE (Niveau Grand Livre / Audit)                        │
│    Exemple: Journalisation de l'événement financier d'encaissement.       │
│    Tables concernées: `journal_comptable`, `grand_livre` (débit/crédit)    │
└─────────────────────────────────────┬─────────────────────────────────────┘
                                      │ (Impacte simultanément)
                                      ▼
┌───────────────────────────────────────────────────────────────────────────┐
│ 3. MOUVEMENT DE TRÉSORERIE (Niveau Flux Financier)                         │
│    Exemple: Entrée de 50 000 FCFA de type "Recette Scolarité".            │
│    Tables concernées: `mouvements_tresorerie`                             │
└─────────────────────────────────────┬─────────────────────────────────────┘
                                      │ (Verse dans)
                                      ▼
┌───────────────────────────────────────────────────────────────────────────┐
│ 4. COMPTE FINANCIER (Niveau Liquidités / Emplacement)                     │
│    Exemple: Affectation physique à la "Caisse Principale N°1".            │
│    Tables concernées: `comptes_financiers` (Mise à jour du solde)         │
└─────────────────────────────────────┬─────────────────────────────────────┘
                                      │ (Tracé par)
                                      ▼
┌───────────────────────────────────────────────────────────────────────────┐
│ 5. SESSION DE CAISSE (Niveau Opérationnel Caissier)                       │
│    Exemple: Session N°345 ouverte par Marie, fermée avec écart de 0 FCFA.  │
│    Tables concernées: `sessions_caisse`                                   │
└───────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Diagrammes complets des flux financiers attendus

### A. Flux pour un paiement scolaire (Inscription ou Mensualité)

```
[Élève apporte l'argent]
          │
          ▼
┌────────────────────────────────┐
│ 1. OPÉRATION MÉTIER            │ ──► Table: `mensualite_details`
│    Saisie du versement         │     Service: `FinancialStatusService`
│    par le Caissier             │     Contrôleur: `PaiementController::processPayment`
└────────────────┬───────────────┘
                 │
                 ▼ (Si validation OK)
┌────────────────────────────────┐
│ 2. ÉCRITURE COMPTABLE          │ ──► Table: `journal_comptable`
│    Journalisation immédiate    │     Modèle: `JournalComptable::log()`
└────────────────┬───────────────┘
                 │
                 ▼ (Génération automatique)
┌────────────────────────────────┐
│ 3. MOUVEMENT DE TRÉSORERIE     │ ──► Table: `mouvements_tresorerie`
│    Création d'une entrée       │     Service: `TreasuryService::registerMovement()`
│    liée à la session de caisse │
└────────────────┬───────────────┘
                 │
                 ▼ (Mise à jour du tiroir)
┌────────────────────────────────┐
│ 4. COMPTE FINANCIER            │ ──► Table: `comptes_financiers`
│    Incrémentation dynamique    │     Modèle: `CompteFinancier::updateSolde()`
│    du solde courant            │
└────────────────┬───────────────┘
                 │
                 ▼ (Contrôle journalier)
┌────────────────────────────────┐
│ 5. SESSION DE CAISSE & CLÔTURE │ ──► Table: `sessions_caisse`
│    Rapprochement en fin de     │     Contrôleur: `CaisseController::cloturerSession`
│    journée (Solde réel/théorique)│
└────────────────────────────────┘
```

---

### B. Flux pour une dépense de fonctionnement

```
[Besoin de l'école] ──► Demande initiée par un agent de l'établissement
          │
          ▼
┌────────────────────────────────┐
│ 1. DEMANDE DE DÉPENSE          │ ──► Table: `depenses` (Statut: 'en_attente')
│    Saisie du montant et motif  │     Contrôleur: `DepenseController::create`
└────────────────┬───────────────┘
                 │
                 ▼
┌────────────────────────────────┐
│ 2. APPROBATION                 │ ──► Table: `depenses` (Statut: 'approuve')
│    Vérification par le         │     Contrôleur: `DepenseController::approve`
│    Comptable / Chef Comptable  │
└────────────────┬───────────────┘
                 │
                 ▼
┌────────────────────────────────┐
│ 3. AUTORISATION ET PAIEMENT    │ ──► Table: `depenses` (Statut: 'paye')
│    Validation du Directeur et  │     Sélection du compte financier de déboursement
│    décaissement réel           │
└────────────────┬───────────────┘
                 │
                 ▼ (Écriture financière)
┌────────────────────────────────┐
│ 4. MOUVEMENT DE TRÉSORERIE     │ ──► Table: `mouvements_tresorerie` (Type: 'sortie')
│    Création du flux de sortie  │     Lien direct vers la dépense payée
│    et mise à jour du solde     │
└────────────────┬───────────────┘
                 │
                 ▼ (Grand Livre)
┌────────────────────────────────┐
│ 5. JOURNALISATION              │ ──► Table: `journal_comptable`
│    Trace de la dépense dans    │     Opération: 'depense_fonctionnement'
│    le livre des charges        │
└────────────────┬───────────────┘
                 │
                 ▼
┌────────────────────────────────┐
│ 6. CLÔTURE COMPTABLE           │ ──► Verrouillage de la transaction lors de la
│    Mensuelle                   │     clôture mensuelle du compte concerné
└────────────────────────────────┘
```

---

### C. Flux pour un transfert interne (Déplacement neutre de fonds)

```
[Trésorier décide de déposer des espèces à la Banque]
          │
          ▼
┌────────────────────────────────┐
│ 1. COMPTE SOURCE               │ ──► Sélection de la caisse émettrice
│    Saisie du transfert         │     (ex: "Caisse Principale")
└────────────────┬───────────────┘
                 │
                 ▼
┌────────────────────────────────┐
│ 2. DEMANDE ET AUTORISATION     │ ──► Table: `transferts_financiers` (Statut: 'en_attente')
│    Validation du Chef Comptable│     Contrôleur: `TransfertController::approve`
└────────────────┬───────────────┘
                 │
                 ▼ (Double Écriture Atomique)
┌────────────────────────────────┐
│ 3. SORTIE COMPTE SOURCE        │ ──► Table: `mouvements_tresorerie` (Type: 'sortie')
│    Débit immédiat de la Caisse │     Impacte le solde de la Caisse source
└────────────────┬───────────────┘
                 │
                 ▼
┌────────────────────────────────┐
│ 4. ENTRÉE COMPTE DESTINATION   │ ──► Table: `mouvements_tresorerie` (Type: 'entree')
│    Crédit du compte bancaire   │     Impacte le solde du compte Banque destinataire
└────────────────┬───────────────┘
                 │
                 ▼
┌────────────────────────────────┐
│ 5. JOURNAL D'AUDIT COMPTABLE   │ ──► Table: `journal_comptable` (Opération: 'transfert')
│    Tracé neutre d'audit        │     Aucun impact sur les indicateurs de profits/pertes
└────────────────────────────────┘
```

---

## 4. Intégration avec les fonctionnalités existantes

La mise en place de la trésorerie se fera par **greffe non intrusive** :

1.  **Scolarité (Inscriptions / Scolarités) :**
    *   Les modèles `Inscription` et `Mensualite` continueront à être les garants exclusifs de l'historique de paiement des élèves.
    *   La méthode `processPayment` appellera un écouteur d'événement (`TreasuryListener::onPaymentValidated`) à la toute fin de sa transaction de base de données. Cet écouteur prendra en charge la création du mouvement de trésorerie sans modifier les calculs d'avantages financiers ou d'états d'accès aux notes/bulletins.
2.  **Boutique (Ventes) :**
    *   Lors de l'achat d'un article (`BoutiqueVente::create`), le système effectuera un mouvement d'entrée sur le compte financier configuré pour la boutique.
3.  **Salaires (Décaissements) :**
    *   Le changement de l'état d'un salaire à `paye` dans `salaires` déclenchera un appel au service de trésorerie pour débiter le compte financier concerné de la somme correspondante.
4.  **Dépenses Générales :**
    *   Introduira un écran indépendant permettant de gérer les dépenses, n'entrant jamais en conflit avec les fiches d'élèves.

---

## 5. Analyse détaillée des annulations et remboursements

Pour assurer la traçabilité absolue requise par un système comptable d'audit (sans aucune suppression physique), voici le cycle exact de ces opérations complexes :

### A. Scénario : Annulation d'un reçu avant clôture de la caisse

```
[Reçu d'origine N° 1024 d'un montant de 50 000 FCFA]
  │
  ▼
  Action d'annulation initiée par le Chef Comptable
  │
  ├─► ÉCRITURE COMPTABLE :
  │   La ligne d'origine de `journal_comptable` reste intacte. Une nouvelle ligne d'annulation
  │   est ajoutée avec un montant de -50 000 FCFA, liée à l'originale.
  │
  ├─► MOUVEMENT DE TRÉSORERIE :
  │   Ajout d'un mouvement de sortie de -50 000 FCFA dans `mouvements_tresorerie`
  │   sur la même session de caisse ou compte d'origine.
  │
  ├─► SOLDE DU COMPTE :
  │   Le solde courant de la Caisse est décrémenté de 50 000 FCFA (réajustement du solde théorique).
  │
  └─► SESSION DE CAISSE :
      Le total théorique attendu de la session de caisse en cours diminue de 50 000 FCFA.
```

### B. Scénario : Remboursement d'un élève après clôture de la caisse d'origine

```
[Demande de remboursement d'un paiement d'inscription validée]
  │
  ▼
  ─► ÉCRITURE DE REMBOURSEMENT :
     Génération d'une nouvelle pièce comptable de décaissement dans `journal_comptable`
     (opération: 'remboursement', montant: -X FCFA) liée à l'élève.

  ─► SORTIE DE TRÉSORERIE :
     Un mouvement de trésorerie de type "sortie" est généré sur le compte financier actif
     sélectionné pour rembourser l'élève (ex: "Caisse Principale" ou "Banque").

  ─► SOLDE COMPTE :
     Le solde de ce compte est décrémenté du montant remboursé.

  ─► SITUATION FINANCIÈRE DE L'ÉLÈVE :
     Le statut financier global de l'élève est instantanément mis à jour. Son reste à payer
     augmente de la valeur remboursée, et ses accès aux notes ou bulletins peuvent être
     suspendus si le seuil de politique financière n'est plus respecté.
```

---

## 6. Sessions de caisse : Cycle de vie et gouvernance

Le cycle opérationnel d'une caisse physique suit une logique séquentielle inviolable :

```
┌─────────────────┐      ┌─────────────────┐      ┌─────────────────┐
│  1. OUVERTURE   │ ───► │ 2. TRANSACTION  │ ───► │  3. COMPTAGE    │
│  Déclaration    │      │  Encaissements, │      │     PHYSIQUE    │
│  solde initial  │      │  menues dépenses│      │ (Espèces réelles)│
└─────────────────┘      └─────────────────┘      └─────────────────┘
                                                           │
┌─────────────────┐      ┌─────────────────┐               │
│  6. FERMETURE   │ ◄─── │  5. APPROBATION │ ◄─────────────┘
│   DÉFINITIVE    │      │  Régularisation │ (Si écart détecté)
│ Solde verrouillé│      │   de l'écart    │
└─────────────────┘      └─────────────────┘
```

### Règles de Gouvernance :
*   **Qui peut ouvrir une caisse ?** Tout utilisateur disposant du rôle `Caissier` ou `Comptable`. L'ouverture requiert la saisie d'un solde d'ouverture physique.
*   **Qui peut l'utiliser ?** Seul le caissier qui a ouvert la session peut y rattacher des encaissements (session strictement nominative et isolée).
*   **Qui peut la clôturer ?** Le caissier lui-même (demande de fermeture) ou le Comptable de l'établissement.
*   **Gestion des écarts de caisse :**
    *   Si $Solde\_Reel \neq Solde\_Theorique$, le caissier saisit un motif obligatoire.
    *   La session de caisse passe au statut `fermee_en_attente`.
    *   Le **Comptable** ou **Chef comptable** analyse les écarts, puis clique sur "Approuver la fermeture". Le système génère automatiquement une écriture de correction comptable d'écart dans une catégorie spéciale de perte ou profit comptable pour forcer la remise à niveau du solde de la caisse.
*   **Réouverture de caisse :** Strictement interdite pour les caissiers et comptables. Seul un **Chef comptable** ou un **Administrateur** peut rouvrir exceptionnellement une caisse fermée de la journée, générant une entrée d'audit système obligatoire.

---

## 7. Niveaux de clôtures et règles de verrouillage

Le système implémentera quatre verrous temporels distincts :

1.  **Clôture quotidienne de caisse (Opérationnelle) :**
    *   *But :* Figer le tiroir-caisse d'un utilisateur à la fin de son service.
    *   *Conséquence :* Plus aucun paiement en espèces ne peut être inséré dans cette session.
2.  **Clôture mensuelle (Comptable) :**
    *   *But :* Arrêter les comptes financiers à la fin du mois.
    *   *Conséquence :* Impossible d'éditer, d'annuler ou de rembourser des écritures ou mouvements datant de ce mois. Toutes les balances comptables mensuelles sont scellées.
3.  **Clôture annuelle (Exercice comptable) :**
    *   *But :* Consolider le résultat financier annuel de l'école.
    *   *Conséquence :* Verrouille l'intégralité de l'exercice comptable. Génère le bilan annuel et transfère les soldes à l'exercice comptable suivant.
4.  **Clôture de l'Année Académique (Métier) :**
    *   *But :* Figer la scolarité et les inscriptions de l'année scolaire de façon logique.

---

## 8. Séparation des responsabilités (Matrice de contrôle interne SoD)

Pour éviter qu'une seule personne puisse concevoir, exécuter et camoufler une fraude financière, la matrice des droits est structurée ainsi :

| Action / Fonction | Caissier | Comptable | Chef Comptable | Directeur | Administrateur |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Encaisser scolarité** | **Émetteur** | Non | Non | Non | Non |
| **Créer demande dépense** | Oui | **Émetteur** | Oui | Oui | Oui |
| **Approuver dépense** | Non | **Validateur 1** | **Validateur 2** | Non | Non |
| **Autoriser paiement dépense** | Non | Non | Non | **Ordonnateur** | Non |
| **Exécuter paiement dépense** | Non | **Payeur** | Non | Non | Non |
| **Annuler Reçu / Contre-passation** | Non | Non | **Autorisé** | Non | **Super-dérogation** |
| **Initier un remboursement** | Non | **Iniciateur** | Non | Non | Non |
| **Approuver un remboursement** | Non | Non | **Validateur** | **Ordonnateur** | Non |
| **Ouvrir / Clôturer sa caisse** | **Caissier** | Oui | Non | Non | Non |
| **Approuver écart de caisse** | Non | Non | **Autorisé** | **Autorisé** | Non |
| **Clôturer le mois / l'année** | Non | Non | **Autorisé** | Non | Non |
| **Réouvrir une période close** | Non | Non | Non | Non | **Autorisé (Audité)** |

---

## 9. Architecture Multi-Lycée (Multi-Establishment)

Pour garantir une étanchéité complète et empêcher toute fuite de fonds ou de données entre les établissements :

*   **Isolation des Comptes Financiers :** Chaque compte financier appartient exclusivement à un lycée (`lycee_id`). Les soldes sont calculés de manière autonome.
*   **Sessions de caisse isolées :** Un caissier d'un lycée ne peut ouvrir de session que sur une caisse appartenant à son lycée.
*   **Filtrage SQL strict :** Les contrôleurs financiers appliqueront un filtre obligatoire :
    ```sql
    SELECT * FROM comptes_financiers WHERE lycee_id = :user_lycee_id
    ```
*   **Consolidation nationale :** Seul le rôle global `super_admin_national` ou `super_admin_createur` dispose d'une permission spéciale permettant de bypasser ce filtre pour exécuter des rapports comparatifs consolidés, mais sans jamais pouvoir initier d'écritures directes d'un lycée vers un autre de manière asymétrique.

---

## 10. Résolution de la question fondamentale de l'établissement

À tout moment, le futur tableau de bord financier centralisé permettra de répondre aux 8 questions cruciales du Directeur :

1.  **« Combien l'établissement possède-t-il réellement ? »**
    *   *Réponse :* Somme des soldes courants de tous les comptes financiers actifs du lycée.
2.  **« Où se trouve cet argent ? »**
    *   *Réponse :* Répartition détaillée par compte (ex : $1\ 200\ 000$ FCFA dans la Caisse 1, $14\ 500\ 000$ FCFA sur le compte Banque SG).
3.  **« D'où vient-il ? »**
    *   *Réponse :* Analyse par catégorie de recettes dans `mouvements_tresorerie` (Inscription, Mensualité, Ventes Boutique, Autres Recettes).
4.  **« Où est-il allé ? »**
    *   *Réponse :* Analyse par catégorie de dépenses et paiements de salaires.
5.  **« Qui l'a encaissé ? »**
    *   *Réponse :* Liaison des mouvements d'entrée avec l'utilisateur et sa session de caisse.
6.  **« Qui l'a dépensé ? »**
    *   *Réponse :* Identifiant du comptable ayant exécuté la dépense dans la table `depenses`.
7.  **« Qui a autorisé chaque mouvement ? »**
    *   *Réponse :* Identifiant du Directeur ou du Chef Comptable enregistré dans les workflows d'approbation et les journaux d'audit de dérogation.
8.  **« Quelle était la situation de la caisse à la fin de chaque journée ? »**
    *   *Réponse :* Historique des états de fermeture dans `sessions_caisse`.

---

## 11. Plan de développement et de livraison progressif

L'intégration sera réalisée de façon modulaire et itérative en 7 phases distinctes :

*   **Phase 1 : Comptes financiers et mouvements de trésorerie** (Création des tables de comptes de base et implémentation du service centralisé de gestion de trésorerie).
*   **Phase 2 : Intégration des paiements scolaires existants** (Greffe non intrusive du service sur `PaiementController::processPayment` pour enregistrer automatiquement les mouvements de trésorerie associés aux inscriptions et mensualités).
*   **Phase 3 : Sessions et clôtures de caisse** (Mise en œuvre du cycle d'ouverture, d'inventaire, de calcul d'écart et d'approbation des clôtures journalières).
*   **Phase 4 : Dépenses et sorties de trésorerie** (Déploiement du module de gestion et de validation des dépenses de fonctionnement).
*   **Phase 5 : Salaires et boutique** (Branchement des paiements de salaires de `SalaireController` et des ventes de `BoutiqueAchatController` sur le flux comptable).
*   **Phase 6 : Actifs et passifs** (Mise en place de l'inventaire patrimonial de l'école).
*   **Phase 7 : Clôtures financières et rapports complets** (Développement des outils de génération de la Balance des comptes, du Livre de caisse et du Compte de Résultat de l'école).

---

### Conclusion et Demande de Validation :
*Cette architecture conceptuelle et ce dossier de spécifications fonctionnelles détaillées constituent la fondation indispensable pour un développement stable et sans régression de SGS.*

**Veuillez valider formellement ce document de conception afin que nous puissions planifier et démarrer le développement de la Phase 1.**
