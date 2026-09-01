<?php

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';

function run_sequence_active_date_window_tests() {
    echo "=================================================================\n";
    echo "Running Sequence Active Date Window Test Suite (Tests A - J)\n";
    echo "=================================================================\n";

    $db = Database::getInstance();
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $db->beginTransaction();

    try {
        $lycee_id = 99;
        $annee_id = 999;
        $today = date('Y-m-d');

        // Clean up test data for lycee_id = 99
        $db->exec("DELETE FROM sequences WHERE lycee_id = {$lycee_id}");
        $db->exec("DELETE FROM annees_academiques WHERE id = {$annee_id}");

        // Create test active academic year
        $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee)
                   VALUES ({$annee_id}, '2025-2026', '2025-09-01', '2026-06-30', 1, 0)");

        $_SESSION['user'] = [
            'id' => 10,
            'lycee_id' => $lycee_id,
            'role_id' => 1,
            'role_name' => 'super_admin_createur',
            'permissions' => ['*']
        ];

        // ------------------------------------------------------------------
        // Test A — Séquence actuellement active
        // date_debut <= aujourd'hui <= date_fin, statut = ouverte, année active = 1, clôturée = 0
        // ------------------------------------------------------------------
        echo "Test A — Séquence actuellement active\n";
        $db->exec("DELETE FROM sequences WHERE lycee_id = {$lycee_id}");
        $dateStartA = date('Y-m-d', strtotime('-3 days'));
        $dateEndA = date('Y-m-d', strtotime('+3 days'));
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (101, {$lycee_id}, {$annee_id}, 'Seq A Current', 'trimestrielle', '{$dateStartA}', '{$dateEndA}', 'ouverte')");

        $seqA = Sequence::findActiveForYear($lycee_id, $annee_id);
        if (!$seqA || (int)$seqA['id'] !== 101) {
            throw new Exception("Test A failed: Expected sequence 101, got " . json_encode($seqA));
        }
        echo "  [PASS] Currently active sequence correctly returned.\n";

        // ------------------------------------------------------------------
        // Test B — Séquence expirée
        // date_debut = passé, date_fin = hier, statut = ouverte -> NON retournée
        // ------------------------------------------------------------------
        echo "Test B — Séquence expirée\n";
        $db->exec("DELETE FROM sequences WHERE lycee_id = {$lycee_id}");
        $dateStartB = date('Y-m-d', strtotime('-10 days'));
        $dateEndB = date('Y-m-d', strtotime('-1 day'));
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (102, {$lycee_id}, {$annee_id}, 'Seq B Expired', 'trimestrielle', '{$dateStartB}', '{$dateEndB}', 'ouverte')");

        $seqB = Sequence::findActiveForYear($lycee_id, $annee_id);
        if ($seqB !== false) {
            throw new Exception("Test B failed: Expired sequence was incorrectly returned: " . json_encode($seqB));
        }
        echo "  [PASS] Expired sequence correctly ignored (returns false).\n";

        // ------------------------------------------------------------------
        // Test C — Séquence future
        // date_debut = demain, date_fin = plusieurs jours dans le futur, statut = ouverte -> NON retournée
        // ------------------------------------------------------------------
        echo "Test C — Séquence future\n";
        $db->exec("DELETE FROM sequences WHERE lycee_id = {$lycee_id}");
        $dateStartC = date('Y-m-d', strtotime('+1 day'));
        $dateEndC = date('Y-m-d', strtotime('+10 days'));
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (103, {$lycee_id}, {$annee_id}, 'Seq C Future', 'trimestrielle', '{$dateStartC}', '{$dateEndC}', 'ouverte')");

        $seqC = Sequence::findActiveForYear($lycee_id, $annee_id);
        if ($seqC !== false) {
            throw new Exception("Test C failed: Future sequence was incorrectly returned: " . json_encode($seqC));
        }
        echo "  [PASS] Future sequence correctly ignored (returns false).\n";

        // ------------------------------------------------------------------
        // Test D — Date exactement égale à date_debut
        // date_debut = aujourd'hui, date_fin = futur -> ACTIVE (comparaison inclusive)
        // ------------------------------------------------------------------
        echo "Test D — Date exactement égale à date_debut\n";
        $db->exec("DELETE FROM sequences WHERE lycee_id = {$lycee_id}");
        $dateStartD = $today;
        $dateEndD = date('Y-m-d', strtotime('+5 days'));
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (104, {$lycee_id}, {$annee_id}, 'Seq D Exact Start', 'trimestrielle', '{$dateStartD}', '{$dateEndD}', 'ouverte')");

        $seqD = Sequence::findActiveForYear($lycee_id, $annee_id);
        if (!$seqD || (int)$seqD['id'] !== 104) {
            throw new Exception("Test D failed: Expected sequence 104 on exact start date, got " . json_encode($seqD));
        }
        echo "  [PASS] Sequence on exact start date boundary correctly returned as active.\n";

        // ------------------------------------------------------------------
        // Test E — Date exactement égale à date_fin
        // date_debut = passé, date_fin = aujourd'hui -> ACTIVE (comparaison inclusive)
        // ------------------------------------------------------------------
        echo "Test E — Date exactement égale à date_fin\n";
        $db->exec("DELETE FROM sequences WHERE lycee_id = {$lycee_id}");
        $dateStartE = date('Y-m-d', strtotime('-5 days'));
        $dateEndE = $today;
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (105, {$lycee_id}, {$annee_id}, 'Seq E Exact End', 'trimestrielle', '{$dateStartE}', '{$dateEndE}', 'ouverte')");

        $seqE = Sequence::findActiveForYear($lycee_id, $annee_id);
        if (!$seqE || (int)$seqE['id'] !== 105) {
            throw new Exception("Test E failed: Expected sequence 105 on exact end date, got " . json_encode($seqE));
        }
        echo "  [PASS] Sequence on exact end date boundary correctly returned as active.\n";

        // ------------------------------------------------------------------
        // Test F — Séquence expirée + Séquence actuelle
        // S1 : expirée et statut = 'ouverte'
        // S2 : actuellement active et statut = 'ouverte'
        // -> S2 doit être retournée, S1 ne doit jamais être retournée
        // ------------------------------------------------------------------
        echo "Test F — Séquence expirée + Séquence actuelle\n";
        $db->exec("DELETE FROM sequences WHERE lycee_id = {$lycee_id}");
        $dateStartF1 = date('Y-m-d', strtotime('-20 days'));
        $dateEndF1 = date('Y-m-d', strtotime('-1 day'));
        $dateStartF2 = date('Y-m-d', strtotime('-1 day'));
        $dateEndF2 = date('Y-m-d', strtotime('+20 days'));

        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (106, {$lycee_id}, {$annee_id}, 'S1 Expired Open', 'trimestrielle', '{$dateStartF1}', '{$dateEndF1}', 'ouverte')");
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (107, {$lycee_id}, {$annee_id}, 'S2 Current Open', 'trimestrielle', '{$dateStartF2}', '{$dateEndF2}', 'ouverte')");

        $seqF = Sequence::findActiveForYear($lycee_id, $annee_id);
        if (!$seqF || (int)$seqF['id'] !== 107) {
            throw new Exception("Test F failed: Expected current sequence 107, got " . json_encode($seqF));
        }
        echo "  [PASS] S2 returned, expired open S1 strictly excluded.\n";

        // ------------------------------------------------------------------
        // Test G — Séquence future + Séquence actuelle
        // S1 : actuellement active (statut = 'ouverte')
        // S2 : future (statut = 'ouverte')
        // -> S1 doit être retournée
        // ------------------------------------------------------------------
        echo "Test G — Séquence future + Séquence actuelle\n";
        $db->exec("DELETE FROM sequences WHERE lycee_id = {$lycee_id}");
        $dateStartG1 = date('Y-m-d', strtotime('-5 days'));
        $dateEndG1 = date('Y-m-d', strtotime('+5 days'));
        $dateStartG2 = date('Y-m-d', strtotime('+6 days'));
        $dateEndG2 = date('Y-m-d', strtotime('+20 days'));

        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (108, {$lycee_id}, {$annee_id}, 'S1 Current Open', 'trimestrielle', '{$dateStartG1}', '{$dateEndG1}', 'ouverte')");
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (109, {$lycee_id}, {$annee_id}, 'S2 Future Open', 'trimestrielle', '{$dateStartG2}', '{$dateEndG2}', 'ouverte')");

        $seqG = Sequence::findActiveForYear($lycee_id, $annee_id);
        if (!$seqG || (int)$seqG['id'] !== 108) {
            throw new Exception("Test G failed: Expected current sequence 108, got " . json_encode($seqG));
        }
        echo "  [PASS] Current sequence S1 correctly returned, future S2 ignored.\n";

        // ------------------------------------------------------------------
        // Test H — Séquence fermée mais période actuelle
        // date_debut <= aujourd'hui <= date_fin, statut = fermée -> NON retournée
        // ------------------------------------------------------------------
        echo "Test H — Séquence fermée mais période actuelle\n";
        $db->exec("DELETE FROM sequences WHERE lycee_id = {$lycee_id}");
        $dateStartH = date('Y-m-d', strtotime('-5 days'));
        $dateEndH = date('Y-m-d', strtotime('+5 days'));
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (110, {$lycee_id}, {$annee_id}, 'Seq H Closed Current', 'trimestrielle', '{$dateStartH}', '{$dateEndH}', 'fermee')");

        $seqH = Sequence::findActiveForYear($lycee_id, $annee_id);
        if ($seqH !== false) {
            throw new Exception("Test H failed: Closed sequence in current period was returned: " . json_encode($seqH));
        }
        echo "  [PASS] Closed sequence in current date period correctly ignored.\n";

        // ------------------------------------------------------------------
        // Test I — Année académique clôturée
        // Période couvre aujourd'hui, statut = 'ouverte', mais a.cloturee = 1 -> NON retournée
        // ------------------------------------------------------------------
        echo "Test I — Année académique clôturée\n";
        $db->exec("DELETE FROM sequences WHERE lycee_id = {$lycee_id}");
        $dateStartI = date('Y-m-d', strtotime('-5 days'));
        $dateEndI = date('Y-m-d', strtotime('+5 days'));
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (111, {$lycee_id}, {$annee_id}, 'Seq I Closed Year', 'trimestrielle', '{$dateStartI}', '{$dateEndI}', 'ouverte')");

        // Mark academic year closed
        $db->exec("UPDATE annees_academiques SET cloturee = 1 WHERE id = {$annee_id}");

        $seqI = Sequence::findActiveForYear($lycee_id, $annee_id);
        if ($seqI !== false) {
            throw new Exception("Test I failed: Sequence returned for closed academic year: " . json_encode($seqI));
        }
        // Restore academic year unclosed
        $db->exec("UPDATE annees_academiques SET cloturee = 0 WHERE id = {$annee_id}");
        echo "  [PASS] Closed academic year correctly prevents active sequence resolution.\n";

        // ------------------------------------------------------------------
        // Test J — Année académique inactive
        // Période couvre aujourd'hui, statut = 'ouverte', mais a.est_active = 0 -> NON retournée
        // ------------------------------------------------------------------
        echo "Test J — Année académique inactive\n";
        $db->exec("DELETE FROM sequences WHERE lycee_id = {$lycee_id}");
        $dateStartJ = date('Y-m-d', strtotime('-5 days'));
        $dateEndJ = date('Y-m-d', strtotime('+5 days'));
        $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
                   VALUES (112, {$lycee_id}, {$annee_id}, 'Seq J Inactive Year', 'trimestrielle', '{$dateStartJ}', '{$dateEndJ}', 'ouverte')");

        // Mark academic year inactive
        $db->exec("UPDATE annees_academiques SET est_active = 0 WHERE id = {$annee_id}");

        $seqJ = Sequence::findActiveForYear($lycee_id, $annee_id);
        if ($seqJ !== false) {
            throw new Exception("Test J failed: Sequence returned for inactive academic year: " . json_encode($seqJ));
        }
        // Restore academic year active
        $db->exec("UPDATE annees_academiques SET est_active = 1 WHERE id = {$annee_id}");
        echo "  [PASS] Inactive academic year correctly prevents active sequence resolution.\n";

        echo "\n=================================================================\n";
        echo "ALL TESTS (A - J) PASSED PERFECTLY!\n";
        echo "=================================================================\n";

    } catch (Exception $e) {
        echo "  [FAIL] Test failed: " . $e->getMessage() . "\n";
        exit(1);
    } finally {
        $db->rollBack();
    }
}

run_sequence_active_date_window_tests();
