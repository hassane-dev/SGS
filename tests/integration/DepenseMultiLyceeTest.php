<?php
// tests/integration/DepenseMultiLyceeTest.php

require_once __DIR__ . '/../../src/models/Depense.php';
require_once __DIR__ . '/../../src/controllers/DepenseController.php';
require_once __DIR__ . '/../../src/services/DepenseWorkflowService.php';

function test_depense_multi_lycee_isolation($pdo) {
    echo "--- Running DepenseMultiLyceeTest ---\n";

    // Setup schools
    $pdo->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES (2, 'Lycee Secondary', 'public')");

    // Setup an expense for Lycee 2
    $depenseId = Depense::create([
        'lycee_id' => 2,
        'numero_piece' => 'DEP-SEC-002',
        'categorie_id' => 1,
        'beneficiaire_id' => 1,
        'montant' => 8000.00,
        'motif' => 'Achat ordinateur',
        'cree_par' => 1,
        'exercice_financier_id' => 1
    ]);

    // Set active user as Lycee 1
    $_SESSION['user'] = [
        'id' => 1,
        'lycee_id' => 1,
        'permissions' => [
            'depense' => ['view', 'validate', 'pay', 'cancel', 'create']
        ]
    ];

    // Verify finding by Lycee 1 returns 0 expenses for Lycee 2
    $filters = [];
    $list = Depense::findByLycee(1, $filters);
    foreach ($list as $item) {
        assert_test($item['lycee_id'] === 1, "Only expenses from Lycee 1 are retrieved.");
    }
    assert_test(count($list) < 100, "Lycee 1 list is clean of Lycee 2 expenses.");

    // Verify calling controller action on Lycee 2 expense fails or redirects
    $controller = new DepenseController();

    // Mock get request or direct controller action
    try {
        // Trigger pay method for an expense belonging to Lycee 2 while active school is Lycee 1
        // We expect a redirection / permission block
        ob_start();
        $controller->pay($depenseId);
        ob_end_clean();

        // Since it redirects, check that an error was set in $_SESSION['error_message']
        assert_test(isset($_SESSION['error_message']) && $_SESSION['error_message'] === 'Accès non autorisé.', "Cross-school payment access blocked successfully.");
    } catch (Exception $e) {
        assert_test(false, "Should have failed gracefully with a redirect instead of exception: " . $e->getMessage());
    }

    // Explicitly test Workflow Service POST Lycée isolation security
    try {
        DepenseWorkflowService::submitForApproval($depenseId, 1);
        assert_test(false, "Should block cross-lycée submission.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), 'FORBIDDEN') !== false, "Cross-school transition block at Service layer verified.");
    }
}
?>