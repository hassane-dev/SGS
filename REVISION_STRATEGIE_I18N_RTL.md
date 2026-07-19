# Révision de la Stratégie Technique i18n & RTL (Thème Able Pro) — Version Finalisée et Validée

Ce document présente l'ajustement de la stratégie d'implémentation suite aux retours de l'équipe et à l'examen approfondi du code d'Able Pro.

---

## 1. Métriques Précises de l'Architecture Gettext (msgid)

Une analyse automatisée du code source de l'application a été menée pour identifier toutes les clés de traduction enveloppées dans les fonctions gettext `_('...')` ou `_("...")`.

- **Nombre d'occurrences totales de gettext (appels dans le code)** : **1162**
- **Estimation des `msgid` uniques** : **~210 à 300** (les répétitions d'étiquettes comme "Annuler", "Enregistrer", "Modifier", "Supprimer", "Classe", "Statut" représentent plus de 75 % du volume total des appels).
- **Nombre de clés présentes dans le catalogue `messages.po` d'origine** : **210** (toutes écrites en anglais comme `msgid "Cycle Name"`).
- **Stratégie d'extraction et de réconciliation** :
  Avant la génération finale du catalogue, un script d'extraction automatique sera lancé pour établir la statistique exacte du type :
  ```text
  Appels gettext totaux : X
  msgid uniques : Y
  msgid déjà présents dans messages.po : Z
  msgid nouveaux : N
  ```
  La fusion des traductions existantes se fera de manière conservatrice sans perturber les traductions orphelines.

### Exemples réels de clés Pivot en Français présentées dans le code
Toutes les pages clés migrées vers Able Pro s'appuient désormais sur des clés de gettext exclusivement écrites en français. En voici des exemples concrets extraits de l'analyse du code :
- `_('Cachet Nominatif / Tampon officiel (PNG transparent)')`
- `_('Format recommandé : Portrait (3:4). La photo sera automatiquement redimensionnée.')`
- `_('Il y a %d élève(s) en attente de paiement initial.')`
- `_('Gérer les déblocages exceptionnels pour la saisie des notes.')`
- `_('Aucun élève actif ou en attente trouvé.')`

### Recommandation validée
Nous confirmons que le français sera la **langue pivot** (langue source) de l'application. Les clés du code source ne seront pas modifiées (elles resteront en français).
- Le catalogue `fr_FR` n'aura pas besoin de traduire le français vers le français (il s'appuiera sur le fallback naturel de gettext lorsque `msgstr` est vide ou absent).
- Les catalogues `en_US` et `ar` utiliseront ces `msgid` français pour fournir leurs traductions respectives.

---

## 2. Unification Canonique des Sessions Linguistiques

Afin d'éviter tout conflit ou incohérence de session (entre `$_SESSION['lang']` et `$_SESSION['locale']`), nous mettons en place une **représentation canonique unique** stockée dans la session :

```php
$_SESSION['lang'] = 'fr_FR'; // ou 'en_US' ou 'ar'
```

### Mécanisme de Normalisation de l'URL
Le bootstrapper acceptera les paramètres URL courts (comme `?lang=en` ou `?lang=fr`) mais les normalisera immédiatement avant de les stocker de manière canonique en session. Le sélecteur de langue présent dans le header Able Pro générera directement les liens avec les codes canoniques :
- `/settings/change-language?lang=fr_FR`
- `/settings/change-language?lang=en_US`
- `/settings/change-language?lang=ar`

---

## 3. Priorisation Logique de la Langue Utilisateur

La langue préférée de l'utilisateur (définie dans son profil via `ParametreUtilisateur->langue_preferee`) sera chargée **une seule fois** lors de l'authentification (connexion) et injectée dans `$_SESSION['lang']`.

### Ordre de priorité de résolution
1. Choix explicite actuel de l'utilisateur (via sélecteur d'URL `?lang=...`).
2. Session active de navigation (`$_SESSION['lang']`).
3. Préférence sauvegardée de l'utilisateur (`langue_preferee` dans `parametres_utilisateurs`).
4. Préférence de l'établissement d'enseignement (`langue_1` dans `param_general`).
5. Fallback par défaut (`fr_FR`).

Une fois la session initialisée, gettext lira uniquement `$_SESSION['lang']`. Cela empêche toute surcharge intempestive à chaque requête tout en respectant l'autonomie de choix de l'utilisateur.

---

## 4. Analyse et Stratégie RTL Propre au Thème Able Pro (Sécurité Visuelle)

L'utilisation sauvage de `bootstrap.rtl.min.css` présente des risques élevés d'écrasement ou de conflits avec la feuille de style globale de 1,9 Mo d'Able Pro (`style.css`).

Une inspection minutieuse de la structure CSS d'Able Pro a révélé des règles physiques strictes positionnant les éléments principaux :
- **Sidebar (`.pc-sidebar`)** : `position: fixed; width: 280px; border-right: var(--pc-sidebar-border);`
- **Conteneur Principal (`.pc-container`)** : `position: relative; margin-left: 280px;`
- **En-tête de page (`.page-header`)** : `position: flex; left: 280px; right: 0;`

### Utilisation de Variables CSS Logiques
Au lieu de hardcoder des valeurs de pixels comme `280px` dans le fichier RTL, nous exploiterons directement les variables CSS d'Able Pro si elles existent (comme `--pc-sidebar-width` ou équivalent), rendant l'architecture robuste à toute modification ultérieure du design thématique :

```css
html[dir="rtl"] .pc-container {
    margin-left: 0;
    margin-right: var(--pc-sidebar-width, 280px);
}

html[dir="rtl"] .page-header {
    left: 0;
    right: var(--pc-sidebar-width, 280px);
}
```

### Stratégie validée : Feuille de style dédiée `able-pro-rtl.css`
Au lieu de remplacer Bootstrap, nous allons créer un fichier d'adaptation minimal et ciblé situé dans :

```text
public/assets/css/able-pro-rtl.css
```

Ce fichier se chargera de surcharger proprement et de retourner logiquement les conteneurs d'Able Pro lorsque la balise html possède l'attribut `dir="rtl"` :

```css
html[dir="rtl"] .pc-sidebar {
    right: 0;
    left: auto;
    border-left: var(--pc-sidebar-border);
    border-right: none;
}
```

Ce fichier ne sera chargé dans `header_able.php` **que si** la direction détectée est `rtl`.

---

## 5. Préservation des Classes Logiques de Bootstrap 5

**Ajustement très important** : Les classes de marge et padding de Bootstrap 5 telles que `.ms-*` (margin-start) et `.me-*` (margin-end) ou `.ps-*` (padding-start) et `.pe-*` (padding-end) sont **déjà logiques** et gèrent nativement l'inversion de sens en RTL lorsqu'un interpréteur RTL de base ou une structure compatible est active.
- Il est donc formellement **interdit d'écrire des inversions manuelles systématiques** sur ces classes (comme `html[dir="rtl"] .me-2 { margin-right: 0; margin-left: 0.5rem; }`), sous peine de générer des doubles inversions perturbatrices.
- L'audit et les corrections se concentreront **exclusivement** sur les styles physiques écrits en dur (fichiers CSS propres à l'application ou règles internes d'Able Pro qui utilisent directement `margin-left`, `margin-right`, `padding-left`, `padding-right`, `left`, `right`).

---

## 6. Saisie Spécifique pour les Documents et PDF

Conformément à la séparation des chantiers préconisée, l'internationalisation des documents sera traitée en deux phases distinctes :

- **Phase A (Traduction des Textes de Vue et Reçus HTML)** :
  - Envelopper tous les libellés statiques des reçus d'inscription et de mensualité dans la fonction de traduction `_()`.
  - Faire de même pour les blocs des bulletins (`_info_eleve.php`, `_header.php`, `_tableau_notes.php`, `_resume_moyennes.php`).
- **Phase B (Génération PDF & Support Technique Arabe)** :
  - Intégrer une police de caractères Unicode compatible avec gettext/arabe.
  - Implémenter un algorithme de shaping arabe (liaison des caractères arabes) et d'inversion bidirectionnelle des chaînes de caractères (Bidi) spécifiquement pour la librairie de génération PDF, afin de garantir la lisibilité graphique, indépendamment de gettext.

---

## 7. Garanties de Non-Régression Financière et Métier

Afin de préserver l'intégrité absolue de la gestion comptable de GestSchool :
1. **Sanctuarisation des Services Financiers** : Les modules de calcul et de vérification (`FinancialStatusService.php`, `FinanceService.php`, ainsi que la logique de validation de `PaiementController.php`) ne subiront **aucune modification fonctionnelle ni altération de calcul**. Les interventions i18n se borneront à la traduction des chaînes d'affichage (messages flash) sans toucher aux données numériques.
2. **Protection des Données Dynamiques** : Les données issues de la base de données (noms et prénoms des élèves, intitulés de classes, dénominations des séries, références de transactions, montants ou dates de paiement) **ne seront pas soumises au moteur de traduction gettext**. Seuls les libellés de structure fixe (comme "Scolarité - Mois de :", "Total dû", "Reste à payer") seront enveloppés dans `_()`.
3. **Respect Strict des Règles Métier** : Les verrous applicatifs sur les années académiques clôturées (`cloturee = 1`), les règles de contrôle d'accès RBAC (permissions d'annulation et de remboursement) et la traçabilité du journal comptable resteront intacts et inchangés.

---

## 8. Protocole de Test de Session Dynamique et Reconnexion

Pour valider l'intégrité de la synchronisation de l'état linguistique et de la direction, nous exécuterons un protocole d'essai en chaîne au sein d'une même session utilisateur.

### Matrice de Validation des États Applicatifs
À chaque changement dynamique d'état, nous vérifierons la correspondance exacte des valeurs suivantes :

| Langue Sélectionnée | Valeur de `$_SESSION['lang']` | Attribut `lang` de `<html>` | Attribut `dir` de `<html>` | Attribut `data-pc-direction` de `<body>` |
| --- | --- | --- | --- | --- |
| **Français** | `fr_FR` | `fr` ou `fr-FR` | `ltr` | `ltr` |
| **Anglais** | `en_US` | `en` ou `en-US` | `ltr` | `ltr` |
| **Arabe** | `ar` | `ar` | `rtl` | `rtl` |

### Test d'Enchaînement de Session Unique
1. **Connexion initiale** (Français) -> L'interface est en français, orientation LTR.
2. **Action Sélecteur 1** -> Clic sur Anglais -> L'interface bascule en anglais, orientation LTR, `$_SESSION['lang'] = 'en_US'`.
3. **Action Sélecteur 2** -> Clic sur Arabe -> L'interface bascule en arabe, orientation RTL, `$_SESSION['lang'] = 'ar'`.
4. **Action Sélecteur 3** -> Clic sur Français -> Retour en français, orientation LTR.

### Test de Déconnexion / Reconnexion (Restauration des préférences utilisateurs)
1. L'utilisateur connecté choisit la langue **Arabe** depuis son profil et enregistre sa préférence.
2. La préférence est stockée dans la table `parametres_utilisateurs`.
3. L'utilisateur se déconnecte (`/logout`).
4. L'utilisateur se reconnecte (`/login`).
5. **Critère de succès** : Le système charge automatiquement la préférence de l'utilisateur, configure `$_SESSION['lang'] = 'ar'` et affiche immédiatement l'interface en Arabe (RTL), sans aucune action manuelle sur le sélecteur.

---

## 9. Plan de Test Unifié (i18n & RTL)

Un protocole de test sera mené sur les 3 configurations cibles de l'application :

| Module / Écran | Test Français (LTR) | Test Anglais (LTR) | Test Arabe (RTL) |
| --- | --- | --- | --- |
| **Connexion / Login** | Interface FR | Interface EN | Interface AR + Sidebar à droite |
| **Tableau de bord** | Stats FR | Stats EN | Stats AR + Menu inversé |
| **Gestion des Élèves** | Noms non altérés | Noms non altérés | Noms non altérés + Formulaire inversé |
| **Gestion des Classes** | Listes FR | Listes EN | Listes AR |
| **Paiements (Show)** | Scolarité FR | Scolarité EN | Scolarité AR |
| **Comptabilité / Journal**| Chiffres inchangés | Chiffres inchangés | Chiffres inchangés |
| **Paramètres Généraux** | Formulaire FR | Formulaire EN | Formulaire AR |
| **Reçus / Factures** | En-tête FR | En-tête EN | En-tête AR + Bloc Signature inversé |
| **Bulletins de notes** | Tableau FR | Tableau EN | Tableau AR |
