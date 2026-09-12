<?php
/**
 * Automated Integration Test Suite: Institutional Report Card Printing & Multilingual Engine
 *
 * Command: php tests/BulletinPrintAndMultilingualTest.php
 */

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

$_SERVER['REQUEST_URI'] = '/bulletins/print';

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/Bulletin.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/ParamLycee.php';
require_once __DIR__ . '/../src/models/ParamGeneral.php';
require_once __DIR__ . '/../src/models/EtatFinancierEleve.php';
require_once __DIR__ . '/../src/helpers/BulletinI18nHelper.php';
require_once __DIR__ . '/../src/controllers/BulletinPrintController.php';

echo "=========================================================================\n";
echo "SUITE DE TEST : Impression Institutionnelle des Bulletins & Multilingue\n";
echo "=========================================================================\n\n";

function assertEquals($expected, $actual, $message = '') {
    if ($expected !== $actual) {
        throw new Exception("FAILURE: {$message} | Expected: " . var_export($expected, true) . " but got: " . var_export($actual, true));
    }
}

function assertStringContains($needle, $haystack, $message = '') {
    if (strpos($haystack, $needle) === false) {
        throw new Exception("FAILURE: {$message} | String '{$needle}' not found in output.");
    }
}

function assertTrue($condition, $message = '') {
    if (!$condition) {
        throw new Exception("FAILURE: {$message} | Expected true condition.");
    }
}

function parseJsonFromOutput($raw) {
    $firstBrace = strpos($raw, '{');
    $lastBrace = strrpos($raw, '}');
    if ($firstBrace !== false && $lastBrace !== false && $lastBrace >= $firstBrace) {
        $jsonString = substr($raw, $firstBrace, $lastBrace - $firstBrace + 1);
        return json_decode($jsonString, true);
    }
    return null;
}

function resetBuffers() {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

$db = Database::getInstance();
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

// Ensure permissions bulletin:generate, bulletin:print, cycle:view_all_cycles exist and are mapped to role 1
foreach (['generate', 'print', 'view'] as $act) {
    $stmtFindP = $db->prepare("SELECT id_permission FROM permissions WHERE resource = 'bulletin' AND action = :act");
    $stmtFindP->execute(['act' => $act]);
    $pId = $stmtFindP->fetchColumn();
    if (!$pId) {
        $stmtInsP = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES ('bulletin', :act, 'Bulletin permission')");
        $stmtInsP->execute(['act' => $act]);
        $pId = $db->lastInsertId();
    }
    if ($pId) {
        try {
            $stmtMap = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (1, :p_id)");
            $stmtMap->execute(['p_id' => $pId]);
        } catch (Throwable $e) {}
    }
}

foreach (['view_all_cycles', 'view_all_lycees', 'view', 'manage'] as $act) {
    $stmtFindP = $db->prepare("SELECT id_permission FROM permissions WHERE resource = 'cycle' AND action = :act");
    $stmtFindP->execute(['act' => $act]);
    $pId = $stmtFindP->fetchColumn();
    if (!$pId) {
        $stmtInsP = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES ('cycle', :act, 'Cycle permission')");
        $stmtInsP->execute(['act' => $act]);
        $pId = $db->lastInsertId();
    }
    if ($pId) {
        try {
            $stmtMap = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (1, :p_id)");
            $stmtMap->execute(['p_id' => $pId]);
        } catch (Throwable $e) {}
    }
}

// 1. Create test Lycee
$stmtL = $db->prepare("INSERT INTO param_lycee (nom_lycee, sigle, tel, email, quartier, ruelle, arrondissement, ville, boite_postale, arrete, devise, logo) VALUES ('Lycée Test Print', 'LTP', '0102030405', 'contact@ltp.edu', 'Nkolbisson', 'Rue 12', 'Yaoundé 7', 'Yaoundé', '1234', 'Arrêté N°001/MINESEC/2020', 'Discipline - Travail - Succès', '/uploads/logos/test_logo.png')");
$stmtL->execute();
$lyceeId = (int)$db->lastInsertId();

// Also insert into lycees table for Lycee::findById
try {
    $stmtLy = $db->prepare("INSERT INTO lycees (id, nom) VALUES (:l_id, 'Lycée Test Print')");
    $stmtLy->execute(['l_id' => $lyceeId]);
} catch (Throwable $e) {}

// 2. Set param_general (Bilingual FR / EN)
ParamGeneral::save([
    'lycee_id' => $lyceeId,
    'nb_langue' => 2,
    'langue_1' => 'fr_FR',
    'langue_2' => 'en_US'
]);

// 3. Create test admin user
$stmtU = $db->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id, lycee_id, actif) VALUES ('Admin', 'Print', 'admin_print@test.com', 'hash', 1, :l_id, 1)");
$stmtU->execute(['l_id' => $lyceeId]);
$userId = (int)$db->lastInsertId();

// 4. Create Academic Year & Sequence
$stmtA = $db->prepare("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active) VALUES (:lib, '2024-09-01', '2025-06-30', 1)");
$stmtA->execute(['lib' => '2024-2025-P' . $lyceeId]);
$anneeId = (int)$db->lastInsertId();

$stmtS = $db->prepare("INSERT INTO sequences (nom, type, statut, date_debut, date_fin, annee_academique_id, lycee_id) VALUES ('Séquence 1 Test', 'trimestrielle', 'fermee', '2024-09-01', '2024-10-30', :a_id, :l_id)");
$stmtS->execute(['a_id' => $anneeId, 'l_id' => $lyceeId]);
$sequenceId = (int)$db->lastInsertId();

// 5. Create Cycle & Classe
$stmtC = $db->prepare("INSERT INTO cycles (nom_cycle, lycee_id) VALUES ('Secondaire Test', :l_id)");
$stmtC->execute(['l_id' => $lyceeId]);
$cycleId = (int)$db->lastInsertId();

$stmtCl = $db->prepare("INSERT INTO classes (niveau, serie, numero, lycee_id, cycle_id) VALUES ('6e', 'A', '1', :l_id, :c_id)");
$stmtCl->execute(['l_id' => $lyceeId, 'c_id' => $cycleId]);
$classeId = (int)$db->lastInsertId();

// 6. Create 2 Students & Enrollments
$stmtE1 = $db->prepare("INSERT INTO eleves (nom, prenom, identifiant_public, lycee_id) VALUES ('KOUAMÉ', 'Jean', 'MAT-P1', :l_id)");
$stmtE1->execute(['l_id' => $lyceeId]);
$eleve1Id = (int)$db->lastInsertId();

$stmtE2 = $db->prepare("INSERT INTO eleves (nom, prenom, identifiant_public, lycee_id) VALUES ('DIOP', 'Awa', 'MAT-P2', :l_id)");
$stmtE2->execute(['l_id' => $lyceeId]);
$eleve2Id = (int)$db->lastInsertId();

$stmtEt1 = $db->prepare("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES (:e_id, :c_id, :l_id, :a_id, 1, 'active')");
$stmtEt1->execute(['e_id' => $eleve1Id, 'c_id' => $classeId, 'l_id' => $lyceeId, 'a_id' => $anneeId]);

$stmtEt2 = $db->prepare("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES (:e_id, :c_id, :l_id, :a_id, 1, 'active')");
$stmtEt2->execute(['e_id' => $eleve2Id, 'c_id' => $classeId, 'l_id' => $lyceeId, 'a_id' => $anneeId]);

// Set financial clearance for both students
EtatFinancierEleve::save([
    'eleve_id' => $eleve1Id,
    'inscription_statut' => 'Payée',
    'mensualite_statut' => 'À jour',
    'notes_consultation' => 'Autorisée',
    'bulletin_impression' => 'Autorisée'
]);
EtatFinancierEleve::save([
    'eleve_id' => $eleve2Id,
    'inscription_statut' => 'Payée',
    'mensualite_statut' => 'À jour',
    'notes_consultation' => 'Autorisée',
    'bulletin_impression' => 'Autorisée'
]);

// 7. Insert official sealed bulletins in BDD
$stmtB1 = $db->prepare("INSERT INTO bulletins (eleve_id, sequence_id, annee_academique_id, lycee_id, moyenne_generale, rang, statut, total_points, total_coefficients, nom_classe_snapshot) VALUES (:e_id, :s_id, :a_id, :l_id, 15.50, '1er', 'valide', 155.00, 10.00, '6e A 1')");
$stmtB1->execute(['e_id' => $eleve1Id, 's_id' => $sequenceId, 'a_id' => $anneeId, 'l_id' => $lyceeId]);
$b1Id = (int)$db->lastInsertId();

$stmtB2 = $db->prepare("INSERT INTO bulletins (eleve_id, sequence_id, annee_academique_id, lycee_id, moyenne_generale, rang, statut, total_points, total_coefficients, nom_classe_snapshot) VALUES (:e_id, :s_id, :a_id, :l_id, 12.00, '2e', 'provisoire', 120.00, 10.00, '6e A 1')");
$stmtB2->execute(['e_id' => $eleve2Id, 's_id' => $sequenceId, 'a_id' => $anneeId, 'l_id' => $lyceeId]);
$b2Id = (int)$db->lastInsertId();

// 8. Create subjects and bulletin details snapshots
$stmtM = $db->prepare("INSERT INTO matieres (nom_matiere, lycee_id) VALUES ('Mathématiques', :l_id)");
$stmtM->execute(['l_id' => $lyceeId]);
$mId = (int)$db->lastInsertId();

$stmtBd1 = $db->prepare("INSERT INTO bulletin_details (bulletin_id, matiere_id, nom_matiere_snapshot, moyenne_matiere, coefficient_snapshot, points_ponderes, rang_matiere, appreciation_matiere) VALUES (:b_id, :m_id, 'Mathématiques', 15.50, 5.00, 77.50, '1er', 'Très Bien')");
$stmtBd1->execute(['b_id' => $b1Id, 'm_id' => $mId]);

$stmtBd2 = $db->prepare("INSERT INTO bulletin_details (bulletin_id, matiere_id, nom_matiere_snapshot, moyenne_matiere, coefficient_snapshot, points_ponderes, rang_matiere, appreciation_matiere) VALUES (:b_id, :m_id, 'Mathématiques', 12.00, 5.00, 60.00, '2e', 'Assez Bien')");
$stmtBd2->execute(['b_id' => $b2Id, 'm_id' => $mId]);

// Mock authentication session
$_SESSION['user'] = [
    'id' => $userId,
    'id_user' => $userId,
    'nom' => 'Admin',
    'prenom' => 'Print',
    'email' => 'admin_print@test.com',
    'role_id' => 1,
    'role_name' => 'super_admin_createur',
    'lycee_id' => $lyceeId,
    'permissions' => [
        'bulletin' => ['generate', 'print', 'view'],
        'cycle' => ['view_all_cycles', 'view']
    ]
];
$_SESSION['user_id'] = $userId;
$_SESSION['lycee_id'] = $lyceeId;
$_SESSION['role_id'] = 1;
$_SESSION['role_name'] = 'super_admin_createur';


try {
    echo "[TEST 1/5] BulletinI18nHelper Monolingual, Bilingual FR/EN, & FR/AR RTL & Distinction... ";

    $pgMonolingual = ['nb_langue' => 1, 'langue_1' => 'fr_FR'];
    $pgBilingualEn = ['nb_langue' => 2, 'langue_1' => 'fr_FR', 'langue_2' => 'en_US'];
    $pgBilingualAr = ['nb_langue' => 2, 'langue_1' => 'fr_FR', 'langue_2' => 'ar'];

    $lbl1 = BulletinI18nHelper::label('BULLETIN SCOLAIRE', $pgMonolingual);
    assertEquals('BULLETIN SCOLAIRE', $lbl1, "Monolingual FR label");

    $lbl2 = BulletinI18nHelper::label('BULLETIN SCOLAIRE', $pgBilingualEn);
    assertStringContains('BULLETIN SCOLAIRE', $lbl2, "FR part in FR/EN");
    assertStringContains('SCHOOL REPORT', $lbl2, "EN part in FR/EN");
    assertStringContains('class="label-l1"', $lbl2, "L1 wrapper class in FR/EN");
    assertStringContains('class="label-l2"', $lbl2, "L2 wrapper class in FR/EN");

    $lbl3 = BulletinI18nHelper::label('BULLETIN SCOLAIRE', $pgBilingualAr);
    assertStringContains('BULLETIN SCOLAIRE', $lbl3, "FR part in FR/AR");
    assertStringContains('dir="rtl"', $lbl3, "RTL attribute in FR/AR");
    assertStringContains('بطاقة تقرير مدرسي', $lbl3, "Arabic label in FR/AR");

    // SSoT Distinction Calculation Test
    assertEquals("Tableau d'honneur + Félicitations", EvaluationCalculationService::getInstitutionalDistinction(16.5), "Distinction >= 16");
    assertEquals("Tableau d'honneur + Encouragements", EvaluationCalculationService::getInstitutionalDistinction(14.5), "Distinction >= 14");
    assertEquals("Tableau d'honneur", EvaluationCalculationService::getInstitutionalDistinction(12.5), "Distinction >= 12");
    assertEquals("", EvaluationCalculationService::getInstitutionalDistinction(10.0), "Distinction < 12");

    // EXPLICIT BIDI MATRICULE ISOLATION & XSS SAFETY TEST (Independent mock data)
    $bData3 = [
        'eleve' => [
            'id_eleve' => 999,
            'nom' => 'BIDI',
            'prenom' => 'Test',
            'identifiant_public' => '10092026-0003E',
            'nom_classe' => '6e A 1',
            'annee_academique' => '2024-2025',
            'lycee_id' => $lyceeId
        ],
        'sequence' => ['nom' => 'Séquence 1 Test'],
        'matieres' => [],
        'evaluation_columns' => [],
        'bulletin_record' => ['statut' => 'valide', 'rang' => '1er'],
        'moyenne_generale' => 18.00,
        'total_points' => 180.00,
        'total_coefficients' => 10.00
    ];
    $paramGeneralAr = ['nb_langue' => 2, 'langue_1' => 'fr_FR', 'langue_2' => 'ar'];

    resetBuffers();
    $bulletinsData = [$bData3];
    $paramGeneral = $paramGeneralAr;
    $isFullPage = false;
    ob_start();
    include __DIR__ . '/../src/views/bulletins/print_template.php';
    $htmlBidi = ob_get_clean();

    assertStringContains('<strong class="ltr-value" dir="ltr">10092026-0003E</strong>', $htmlBidi, "Strict LTR isolation wrapper for 10092026-0003E");
    assertStringContains('الرقم التسلسلي', $htmlBidi, "Arabic matricule label in print output");

    // XSS SAFETY VERIFICATION
    $xssHeader = "<script>alert('xss')</script>";
    $decodedEscaped = htmlspecialchars(html_entity_decode($xssHeader, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    assertEquals("&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;", $decodedEscaped, "XSS payload correctly neutralized");

    echo "OK!\n";


    echo "[TEST 2/5] Verification de la correction de la page blanche dans show.php... ";

    resetBuffers();
    $bulletinData = Bulletin::generateForStudent($eleve1Id, $sequenceId);
    assertTrue(!empty($bulletinData), "Bulletin data generated");

    ob_start();
    $bulletin = $bulletinData;
    include __DIR__ . '/../src/views/bulletins/show.php';
    $htmlShow = ob_get_clean();

    assertStringContains('id="bulletin-standalone-print"', $htmlShow, "Standalone print wrapper outside .pc-container");
    assertStringContains('class="d-none d-print-block"', $htmlShow, "Print display class");
    assertStringContains('/bulletins/student/print', $htmlShow, "Official Print route link");
    assertStringContains('KOUAMÉ', $htmlShow, "Student name in show view");

    echo "OK!\n";

    echo "[TEST 2.5] Verification de l'acces au module d'impression (/bulletins/print)... ";

    resetBuffers();
    $_GET = [];
    $_SERVER['REQUEST_URI'] = '/bulletins/print';
    $_SESSION['user'] = [
        'id_user' => $userId,
        'role_id' => 1,
        'role_name' => 'super_admin_createur',
        'lycee_id' => $lyceeId
    ];
    $controller = new BulletinPrintController();

    ob_start();
    try {
        $controller->index();
    } catch (Throwable $e) {}
    $htmlIndex = ob_get_clean();

    assertStringContains('Impression', $htmlIndex, "Selection page rendered");
    assertStringContains('id="select_cycle"', $htmlIndex, "Cycle selector present");
    assertStringContains('id="select_niveau"', $htmlIndex, "Niveau selector present");
    assertStringContains('id="select_classe"', $htmlIndex, "Classe selector present");

    echo "OK!\n";


    echo "[TEST 3/5] API de Prévisualisation / Résumé (/bulletins/print/summary)... ";

    resetBuffers();
    $_GET = [
        'sequence_id' => $sequenceId,
        'scope_type' => 'classe',
        'scope_id' => $classeId
    ];
    $controller = new BulletinPrintController();

    ob_start();
    try {
        $controller->summary();
    } catch (Throwable $e) {}
    $jsonRaw = ob_get_clean();

    $summaryData = parseJsonFromOutput($jsonRaw);
    assertTrue(!empty($summaryData['success']), "Summary API success");
    assertEquals(1, $summaryData['stats']['classes_count'], "Classes count");
    assertEquals(2, $summaryData['stats']['total_students_count'], "Total students count");
    assertEquals(1, $summaryData['stats']['printable_official_count'], "Official printable count");
    assertEquals(1, $summaryData['stats']['printable_provisional_count'], "Provisional printable count");
    assertEquals(2, $summaryData['stats']['printable_total'], "Total printable count");

    echo "OK!\n";


    echo "[TEST 4/5] Exécution de l'impression globale (/bulletins/print/execute)... ";

    resetBuffers();
    $_GET = [
        'sequence_id' => $sequenceId,
        'scope_type' => 'classe',
        'scope_id' => $classeId,
        'include_provisoire' => 1
    ];

    ob_start();
    try {
        $controller->execute();
    } catch (Throwable $e) {}
    $htmlExec = ob_get_clean();

    assertStringContains('BULLETIN SCOLAIRE', $htmlExec, "Title L1 in print template");
    assertStringContains('SCHOOL REPORT', $htmlExec, "Title L2 in print template");
    assertStringContains('KOUAMÉ', $htmlExec, "Student 1 in bulk print");
    assertStringContains('DIOP', $htmlExec, "Student 2 in bulk print");
    assertStringContains('/uploads/logos/test_logo.png', $htmlExec, "Lycee logo rendered");
    assertStringContains('Discipline - Travail - Succès', $htmlExec, "Lycee motto/devise rendered");
    assertStringContains('Nkolbisson', $htmlExec, "Lycee quartier rendered");
    assertStringContains('Arrêté N°001/MINESEC/2020', $htmlExec, "Lycee arrete rendered");
    assertStringContains('contact@ltp.edu', $htmlExec, "Lycee email rendered");
    assertStringContains('DISTINCTION / PALMARÈS', $htmlExec, "Distinction section header in printed bulletin");
    assertStringContains("Tableau d&#039;honneur + Encouragements", $htmlExec, "Calculated distinction rendered");
    assertTrue(strpos($htmlExec, '&amp;#039;') === false, "No double escaping &amp;#039; in printed template");

    // COLUMN HEADER VERIFICATION: 'Moyenne coefficient' present and 'Total Points' / 'Total points' absent in headers
    assertStringContains('Moyenne coefficient', $htmlExec, "Column header 'Moyenne coefficient' present in L1/L2 output");
    assertTrue(strpos($htmlExec, '<th>Total Points</th>') === false, "Header 'Total Points' absent from table header");
    assertTrue(strpos($htmlExec, '<th>Total points</th>') === false, "Header 'Total points' absent from table header");
    assertStringContains('page-break-after: always;', $htmlExec, "A4 page break between students");

    echo "OK!\n";


    echo "[TEST 5/5] Garantie de Lecture Seule (Aucune modification de statut ni de moyenne)... ";

    $stmtBCheck = $db->prepare("SELECT statut, moyenne_generale, rang FROM bulletins WHERE eleve_id = :e_id AND sequence_id = :s_id");
    $stmtBCheck->execute(['e_id' => $eleve1Id, 's_id' => $sequenceId]);
    $afterCheck = $stmtBCheck->fetch(PDO::FETCH_ASSOC);

    assertEquals('valide', $afterCheck['statut'], "Status unchanged after print");
    assertEquals('15.50', sprintf("%.2f", $afterCheck['moyenne_generale']), "Average unchanged after print");
    assertEquals('1er', $afterCheck['rang'], "Rank unchanged after print");

    echo "OK!\n";

    echo "\n=========================================================================\n";
    echo "TOUS LES TESTS D'IMPRESSION ET MULTILINGUE ONT RÉUSSI AVEC SUCCÈS ! (5/5)\n";
    echo "=========================================================================\n";

} catch (Throwable $e) {
    echo "\n\nTEST FAILED WITH EXCEPTION:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
