<?php
// tests/integration/DepenseConcurrencyTest.php

require_once __DIR__ . '/../../src/services/DepenseWorkflowService.php';

function test_depense_concurrency($pdo) {
    echo "--- Running DepenseConcurrencyTest ---\n";

    // Setup an expense in 'en_attente_approbation'
    $pdo->exec("DELETE FROM depenses;");
    $depenseId = Depense::create([
        'lycee_id' => 1,
        'numero_piece' => 'DEP-CONC-999',
        'categorie_id' => 1,
        'beneficiaire_id' => 1,
        'montant' => 400.00,
        'motif' => 'Concurrency test',
        'cree_par' => 1,
        'exercice_financier_id' => 1
    ]);
    DepenseWorkflowService::submitForApproval($depenseId, 1);

    // Simulate validator A reading and transitioning first
    // Since we are in a single thread, we simulate that they both fetch, but Validator A updates status first.
    // Validator B then attempts to update status on the same expense, which is now 'approuve' and should throw an exception.
    try {
        // Validator A approves
        DepenseWorkflowService::approve($depenseId, 1, "Approuvé par A");
        assert_test(true, "First approval transition succeeded.");

        // Validator B tries to approve, which should trigger a transition error (not allowed 'approuve' -> 'approuve')
        DepenseWorkflowService::approve($depenseId, 1, "Approuvé par B");
        assert_test(false, "Concurrent duplicate approval should fail.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), 'Transition de statut non autorisée') !== false, "Anti-collision concurrency check verified successfully.");
    }
}
?>