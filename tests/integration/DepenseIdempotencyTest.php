<?php
// tests/integration/DepenseIdempotencyTest.php

require_once __DIR__ . '/../../src/models/Depense.php';

function test_depense_idempotency($pdo) {
    echo "--- Running DepenseIdempotencyTest ---\n";

    // Setup
    $pdo->exec("DELETE FROM depenses;");

    $data = [
        'lycee_id' => 1,
        'numero_piece' => 'DEP-UNIQUE-123',
        'categorie_id' => 1,
        'beneficiaire_id' => 1,
        'montant' => 2000.00,
        'motif' => 'Idempotency test',
        'cree_par' => 1,
        'exercice_financier_id' => 1
    ];

    // First request
    $depenseId = Depense::create($data);
    assert_test($depenseId > 0, "First creation request succeeded.");

    // Duplicate request
    try {
        Depense::create($data);
        assert_test(false, "Duplicate creation with same piece number should have failed.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), 'IDEMPOTENCY_CONFLICT') !== false, "Duplicate block with IDEMPOTENCY_CONFLICT verified.");
    }
}
?>