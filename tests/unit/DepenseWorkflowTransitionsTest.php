<?php
// tests/unit/DepenseWorkflowTransitionsTest.php

require_once __DIR__ . '/../../src/services/DepenseWorkflowService.php';

function test_workflow_transitions($pdo) {
    echo "--- Running DepenseWorkflowTransitionsTest ---\n";

    // Create a categories, beneficiary, accounts, academic year first
    $pdo->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES (1, 'Lycee Test', 'public')");
    $pdo->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES (1, '2024-2025', '2024-01-01', '2024-12-31', 1)");
    $pdo->exec("INSERT INTO depense_categories (id, lycee_id, nom_categorie) VALUES (1, 1, 'Fournitures')");
    $pdo->exec("INSERT INTO depense_beneficiaires (id, lycee_id, nom_beneficiaire, type) VALUES (1, 1, 'Fournisseur A', 'externe')");
    $pdo->exec("INSERT INTO comptes_financiers (id, lycee_id, nom_compte, type_compte, solde_courant, statut) VALUES (1, 1, 'Caisse', 'caisse', 10000.00, 'actif')");

    // Inject mock user and permission for create / submit
    $_SESSION['user'] = [
        'id' => 1,
        'lycee_id' => 1,
        'permissions' => [
            'depense' => ['create', 'validate', 'reject', 'pay', 'cancel', 'view']
        ]
    ];

    // Create a draft
    $depenseId = Depense::create([
        'lycee_id' => 1,
        'numero_piece' => 'DEP-101',
        'categorie_id' => 1,
        'beneficiaire_id' => 1,
        'montant' => 500.00,
        'motif' => 'Achat stylos',
        'cree_par' => 1,
        'exercice_financier_id' => 1
    ]);

    $depense = Depense::findById($depenseId);
    assert_test($depense['statut'] === 'brouillon', "Initial status is 'brouillon'.");

    // Submit for approval
    DepenseWorkflowService::submitForApproval($depenseId, 1);
    $depense = Depense::findById($depenseId);
    assert_test($depense['statut'] === 'en_attente_approbation', "Status transitioned to 'en_attente_approbation'.");

    // Invalid transition: cannot pay directly from 'en_attente_approbation'
    try {
        DepenseWorkflowService::pay($depenseId, 1, 1);
        assert_test(false, "Should not allow paying a non-approved expense.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), 'Seule une dépense approuvée peut être payée') !== false, "Illegal transition block verified.");
    }

    // Approve
    DepenseWorkflowService::approve($depenseId, 1);
    $depense = Depense::findById($depenseId);
    assert_test($depense['statut'] === 'approuve', "Status transitioned to 'approuve'.");

    // Pay
    DepenseWorkflowService::pay($depenseId, 1, 1);
    $depense = Depense::findById($depenseId);
    assert_test($depense['statut'] === 'paye', "Status transitioned to 'paye'.");

    // Check account balance update
    $compte = CompteFinancier::findById(1);
    assert_test((float)$compte['solde_courant'] === 9500.00, "Account balance correctly updated (10000 - 500 = 9500).");
}
?>