<?php
// tests/unit/DepenseModelTest.php

require_once __DIR__ . '/../../src/models/Depense.php';

function test_depense_model($pdo) {
    echo "--- Running DepenseModelTest ---\n";

    // 1. Check direct status modification block
    try {
        Depense::updateStatus(1, 'paye', 'INVALID_TOKEN');
        assert_test(false, "Direct updateStatus should be blocked without correct guard token.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), 'La modification directe de l\'état d\'une dépense est interdite') !== false, "Direct updateStatus block verified.");
    }

    // 2. Validate empty parameters checking
    try {
        Depense::create([
            'lycee_id' => 1,
            'numero_piece' => '',
            'categorie_id' => 1,
            'beneficiaire_id' => 1,
            'montant' => 100,
            'motif' => 'Test',
            'cree_par' => 1,
            'exercice_financier_id' => 1
        ]);
        assert_test(false, "Creation with empty piece number should fail.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), 'Le numéro de pièce est requis') !== false, "Piece number requirement verified.");
    }

    try {
        Depense::create([
            'lycee_id' => 1,
            'numero_piece' => 'DEP-001',
            'categorie_id' => 1,
            'beneficiaire_id' => 1,
            'montant' => -50,
            'motif' => 'Test',
            'cree_par' => 1,
            'exercice_financier_id' => 1
        ]);
        assert_test(false, "Creation with non-positive amount should fail.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), 'Le montant doit être strictement positif') !== false, "Non-positive amount validation verified.");
    }
}
?>