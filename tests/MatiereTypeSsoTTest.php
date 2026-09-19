<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/models/Matiere.php';

function test_matiere_ssot_types() {
    echo "Testing Matiere::getTypes() SSoT definition...\n";

    $expected = [
        'Littéraire',
        'Scientifique',
        'Sciences humaines et sociales',
        'Économique / Gestion / Commercial',
        'Technique / Technologique',
        'Artistique / Arts',
        'Agricole',
        'Autre',
    ];

    $types = Matiere::getTypes();

    assert(count($types) === 8, "Expected 8 types, got " . count($types));
    assert($types === $expected, "Types array does not match expected exact order.");
    assert(Matiere::TYPES === $expected, "Matiere::TYPES constant does not match expected exact order.");

    echo "  [OK] Matiere::getTypes() matches exact 8 families in specified order.\n";
}

function test_matiere_form_rendering_new_and_legacy() {
    echo "Testing _form.php rendering for standard and legacy types...\n";

    // Standard type test: 'Sciences humaines et sociales'
    $cycles = [['nom_cycle' => 'Secondaire']];
    $matiere = [
        'nom_matiere' => 'Histoire-Géo',
        'description' => 'Cours d\'histoire',
        'type' => 'Sciences humaines et sociales',
        'cycle_concerne' => 'Secondaire',
        'statut' => 'principale',
    ];
    $types = Matiere::getTypes();

    ob_start();
    include __DIR__ . '/../src/views/matieres/_form.php';
    $outputStandard = ob_get_clean();

    assert(strpos($outputStandard, 'value="Sciences humaines et sociales" selected') !== false, "Expected 'Sciences humaines et sociales' to be selected in form.");
    assert(strpos($outputStandard, 'value="Littéraire"') !== false, "Expected 'Littéraire' option to be present.");
    assert(strpos($outputStandard, 'value="Agricole"') !== false, "Expected 'Agricole' option to be present.");
    assert(strpos($outputStandard, 'value="Autre"') !== false, "Expected 'Autre' option to be present.");

    echo "  [OK] Standard family correctly rendered and selected in _form.php.\n";

    // Legacy/Custom type test: 'AncienTypeCustom'
    $matiereLegacy = [
        'nom_matiere' => 'Matière Ancienne',
        'description' => 'Test legacy',
        'type' => 'AncienTypeCustom',
        'cycle_concerne' => 'Secondaire',
        'statut' => 'principale',
    ];

    $matiere = $matiereLegacy;
    ob_start();
    include __DIR__ . '/../src/views/matieres/_form.php';
    $outputLegacy = ob_get_clean();

    assert(strpos($outputLegacy, 'value="AncienTypeCustom" selected') !== false, "Expected legacy type 'AncienTypeCustom' to be retained and selected.");

    echo "  [OK] Legacy saved family value correctly preserved and selected in edit form.\n";
}

function test_matiere_database_save_with_ssot_types() {
    echo "Testing Matiere::save() with SQLite mock DB...\n";

    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE TABLE matieres (
        id_matiere INTEGER PRIMARY KEY AUTOINCREMENT,
        nom_matiere TEXT NOT NULL,
        description TEXT,
        type TEXT,
        cycle_concerne TEXT,
        statut TEXT NOT NULL,
        lycee_id INTEGER NOT NULL
    )");

    Database::setInstance($pdo);

    $_SESSION['user'] = ['id_user' => 1, 'lycee_id' => 10];

    $data = [
        'nom_matiere' => 'Économie Générale',
        'description' => 'Introduction à l\'économie',
        'type' => 'Économique / Gestion / Commercial',
        'cycle_concerne' => 'Lycée',
        'statut' => 'principale',
    ];

    $success = Matiere::save($data);
    assert($success === true, "Matiere::save failed.");

    $stmt = $pdo->query("SELECT * FROM matieres WHERE nom_matiere = 'Économie Générale'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    assert($row !== false, "Saved matiere row not found.");
    assert($row['type'] === 'Économique / Gestion / Commercial', "Saved type mismatch: got " . $row['type']);

    echo "  [OK] Matiere record created with SSoT type 'Économique / Gestion / Commercial'.\n";

    // Update with another SSoT type
    $dataUpdate = [
        'id_matiere' => $row['id_matiere'],
        'nom_matiere' => 'Économie & Droit',
        'description' => 'Économie et Droit',
        'type' => 'Technique / Technologique',
        'cycle_concerne' => 'Lycée',
        'statut' => 'principale',
    ];

    $updateSuccess = Matiere::save($dataUpdate);
    assert($updateSuccess === true, "Matiere::save update failed.");

    $stmtUp = $pdo->query("SELECT * FROM matieres WHERE id_matiere = " . (int)$row['id_matiere']);
    $rowUp = $stmtUp->fetch(PDO::FETCH_ASSOC);

    assert($rowUp['type'] === 'Technique / Technologique', "Updated type mismatch: got " . $rowUp['type']);

    echo "  [OK] Matiere record updated with SSoT type 'Technique / Technologique'.\n";

    Database::setInstance(null);
}

test_matiere_ssot_types();
test_matiere_form_rendering_new_and_legacy();
test_matiere_database_save_with_ssot_types();

echo "\nAll MatiereTypeSsoTTest tests completed successfully!\n";
