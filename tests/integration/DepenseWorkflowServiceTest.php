<?php
// tests/integration/DepenseWorkflowServiceTest.php

require_once __DIR__ . '/../../src/services/DepenseWorkflowService.php';

function test_workflow_service_integration($pdo) {
    echo "--- Running DepenseWorkflowServiceTest ---\n";

    // Setup active session, year, categories, beneficiary, accounts
    $pdo->exec("DELETE FROM depenses; DELETE FROM mouvements_tresorerie;");
    $pdo->exec("UPDATE comptes_financiers SET solde_courant = 10000.00 WHERE id = 1");

    $_SESSION['user'] = [
        'id' => 1,
        'lycee_id' => 1,
        'permissions' => [
            'depense' => ['create', 'validate', 'pay']
        ]
    ];

    // Create a new expense
    $depenseId = Depense::create([
        'lycee_id' => 1,
        'numero_piece' => 'DEP-201',
        'categorie_id' => 1,
        'beneficiaire_id' => 1,
        'montant' => 1200.00,
        'motif' => 'Achat tableau',
        'cree_par' => 1,
        'exercice_financier_id' => 1
    ]);

    // Submit and approve
    DepenseWorkflowService::submitForApproval($depenseId, 1);
    DepenseWorkflowService::approve($depenseId, 1);

    // Try paying with insufficient balance (amount > balance)
    $pdo->exec("UPDATE comptes_financiers SET solde_courant = 1000.00 WHERE id = 1");
    try {
        DepenseWorkflowService::pay($depenseId, 1, 1);
        assert_test(false, "Should fail payment with insufficient balance.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), 'Solde insuffisant') !== false, "Payment failure due to insufficient funds handled correctly.");
    }

    // Verify status was rolled back and is still 'approuve' (not 'paye')
    $depense = Depense::findById($depenseId);
    assert_test($depense['statut'] === 'approuve', "ACID Rollback confirmed : Expense state was restored.");

    // Verify balance was not touched
    $compte = CompteFinancier::findById(1);
    assert_test((float)$compte['solde_courant'] === 1000.00, "ACID Rollback confirmed : Account balance untouched.");
}
?>