# AUDIT ET RESOLUTION DES ANOMALIES DU MOTEUR DE SAISIE DES NOTES

## 1. DIAGNOSTIC INITIAL ET CAUSE RACINE

Lors des tests en environnement réel MySQL sur le moteur de contrôle de la saisie des notes (`EvaluationSaisieService`), deux anomalies majeures ont été identifiées :

1. **Résolution imprécise de la séquence active :**
   Le service utilisait `Sequence::findById($sequence_id)`, qui effectue uniquement `SELECT * FROM sequences WHERE id = :id AND lycee_id = :lycee_id`. Cette méthode générique ne vérifiait ni si l'année académique rattachée était active (`est_active = 1`), ni si l'année était clôturée (`cloturee = 0`), ni si le statut de la séquence était réellement ouverte (`statut = 'ouverte'`). Ainsi, un `sequence_id` transmis via l'URL pouvait contourner la séquence ouverte du contexte.

2. **Erreur PDO HY093 (`Invalid parameter number`) :**
   Dans `EvaluationSaisieService::findMatchingUnlock()`, la requête SQL réutilisait le même nom de placeholder nommé (ex. `:classe_id`, `:matiere_id`, `:sequence_id`) à plusieurs reprises dans les clauses `OR`. En mode préparation native PDO (`PDO::ATTR_EMULATE_PREPARES = false`), la réutilisation de placeholders nommés identiques provoque l'exception `SQLSTATE[HY093]: Invalid parameter number`.

---

## 2. CORRECTIONS APPORTÉES

### A. Nouvelle méthode spécialisée `Sequence::findActiveForYear()`
Fichier : `src/models/Sequence.php`

Création de la méthode `findActiveForYear($lycee_id, $annee_id)` tout en conservant `Sequence::findById()` intacte pour ses usages génériques dans l'application.

```sql
SELECT s.*
FROM sequences s
INNER JOIN annees_academiques a
    ON s.annee_academique_id = a.id
WHERE s.lycee_id = :lycee_id
  AND a.id = :annee_id
  AND a.est_active = 1
  AND a.cloturee = 0
  AND s.statut = 'ouverte'
ORDER BY s.date_debut ASC, s.id ASC
LIMIT 1
```

### B. Refonte de la résolution de séquence dans `EvaluationSaisieService`
Fichier : `src/services/EvaluationSaisieService.php`

Dans `canTeacherGradeContext()` :
- Récupération de l'année académique active via `AnneeAcademique::findActive()`.
- Résolution dynamique de la véritable séquence ouverte de l'année active au lieu de faire confiance au `$sequence_id` de la requête :
  ```php
  $sequence = Sequence::findActiveForYear($resolvedLyceeId, (int)$active_year['id']);
  ```
- Si aucune séquence n'est ouverte pour l'année active, le moteur retourne une décision explicite :
  `false`, code `DENIED_NO_OPEN_SEQUENCE`, message *"Aucune séquence n'est actuellement ouverte pour l'année académique active."*
- Une fois la séquence ouverte résolue, son véritable ID est propagé de manière unique dans :
  1. `$context['sequence_id']`
  2. `findMatchingUnlock()`
  3. la recherche dans `parametres_evaluations`
  4. les journaux et décisions du moteur.

### C. Élimination de l'erreur PDO HY093
Fichier : `src/services/EvaluationSaisieService.php`

Dans `findMatchingUnlock()` et la requête de recherche des règles de `parametres_evaluations`, remplacement de tous les placeholders répétés par des identifiants uniques :

```sql
-- Requête findMatchingUnlock
SELECT * FROM deblocages_notes
WHERE lycee_id = :lycee_id
AND annee_academique_id = :annee_id
AND (type_evaluation = :type_eval OR type_evaluation = 'tous')
AND (sequence_id IS NULL OR sequence_id = :sequence_id_unlock)
AND (
    type = 'global'
    OR (type = 'classe' AND classe_id = :classe_id_c)
    OR (type = 'matiere' AND matiere_id = :matiere_id_m)
    OR (type = 'classe_matiere' AND classe_id = :classe_id_cm AND matiere_id = :matiere_id_cm)
    OR (type = 'enseignant' AND classe_id = :classe_id_t AND matiere_id = :matiere_id_t AND enseignant_id = :enseignant_id)
)
```

Cette structure garantit un mapping 1-to-1 exact entre placeholders et tableau de paramètres PDO, annulant tout risque d'erreur HY093 sur MySQL/MariaDB avec des requêtes préparées natives.

### D. Cohérence Temporelle & Timezone
La comparaison de dates utilise la méthode `EvaluationSaisieService::normalizeDateTime()`, convertissant toute chaîne datetime (ex. ISO `Y-m-d\TH:i` ou format SQL) en timestamp canonique `Y-m-d H:i:s`. Les comparaisons `$tsNow >= $tsStart && $tsNow <= $tsEnd` conservent des bornes inclusives exactes.

---

## 3. RÉSULTATS DES TESTS DE NON-RÉGRESSION

L'ensemble de la suite de tests a été exécuté avec succès :

### Suite 1 — Mandatory Real Engine Tests (`tests/EvaluationEngineMandatoryTestsTest.php`)
- **Test 1 — Séquence actuelle :** `findActiveForYear(1, 1)` retourne `id = 1`, `nom = Trimestre 1`, `statut = ouverte` et jamais Trimestre 2 ou 3. `[PASS]`
- **Test 2 — Séquence fermée :** Quand toutes les séquences sont fermées, `findActiveForYear` renvoie `false` et le moteur retourne `DENIED_NO_OPEN_SEQUENCE`. `[PASS]`
- **Test 3 — Année académique :** Vérification stricte que `est_active = 1` et `cloturee = 0`. `[PASS]`
- **Test 4 — Paramètre global existant :** Retrouve la règle globale `id = 3` (`sequence_id = NULL`, `type_evaluation = devoir`). `[PASS]`
- **Test 5 — Date actuelle dans la période :** Décision `allowed = true`, `code = ALLOWED_PERIOD`. `[PASS]`
- **Test 6 — Avant ouverture :** Décision `allowed = false`, `code = DENIED_PERIOD_NOT_STARTED`. `[PASS]`
- **Test 7 — Après fermeture :** Décision `allowed = false`, `code = DENIED_PERIOD_EXPIRED`. `[PASS]`
- **Test 8 — Déblocage exceptionnel :** Exécution sans erreur `SQLSTATE[HY093]` avec `code = ALLOWED_DEBLOCAGE`. `[PASS]`

### Suites d'intégration complémentaires
- `tests/EvaluationSaisieServiceTest.php` (17 scénarios complets) : `[PASS]`
- `tests/EvaluationGradingWindowAndSecurityTest.php` : `[PASS]`
- `tests/EvaluationGradingWindowHierarchyTest.php` : `[PASS]`
- `tests/EvaluationTypeAndWindowRulesTest.php` : `[PASS]`
