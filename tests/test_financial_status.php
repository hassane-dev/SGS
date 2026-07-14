<?php

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/FinancialStatusService.php';
require_once __DIR__ . '/../src/models/Lycee.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/Frais.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/Etude.php';
require_once __DIR__ . '/../src/models/Inscription.php';
require_once __DIR__ . '/../src/models/Mensualite.php';
require_once __DIR__ . '/../src/controllers/PaiementController.php';

function run_full_financial_test() {
    echo "Running end-to-end FinancialStatusService integration test...\n";

    $db = Database::getInstance();
    $db->beginTransaction();

    try {
        // 1. Create a school (Lycee)
        $lyceeId = Lycee::save([
            'nom_lycee' => 'Test Lycee Financier',
            'type_lycee' => 'prive',
            'sigle' => 'TLF',
            'tel' => '12345678',
            'email' => 'tlf@test.com',
            'ville' => 'Cotonou',
            'quartier' => 'Fidjrosse',
            'ruelle' => 'Ruelle 1',
            'boite_postale' => 'BP 123',
            'arrete' => 'Arr 123',
            'arrondissement' => 'Arrond 1',
            'devise' => 'XAF'
        ]);
        echo "Created Lycee ID: $lyceeId\n";

        // Set authenticated user context in session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user'] = [
            'id' => 1,
            'lycee_id' => $lyceeId,
            'role_name' => 'super_admin_createur',
            'permissions' => [
                'paiement' => ['view', 'manage', '*']
            ]
        ];

        // 2. Create academic year
        $stmt_year = $db->query("SELECT id FROM annees_academiques WHERE est_active = 1 LIMIT 1");
        $yearId = $stmt_year->fetchColumn();
        if (!$yearId) {
            AnneeAcademique::save([
                'libelle' => '2024-2025',
                'date_debut' => '2024-09-01',
                'date_fin' => '2025-06-30'
            ]);
            $yearId = $db->lastInsertId();
            AnneeAcademique::setActive($yearId);
        }
        echo "Active Academic Year ID: $yearId\n";

        // 3. Create Cycle and Class
        $cycle_id = Cycle::save([
            'nom_cycle' => 'CEG',
            'description' => 'College',
            'niveau_debut' => '6ème',
            'niveau_fin' => '3ème'
        ]);
        echo "Created Cycle ID: $cycle_id\n";

        Classe::save([
            'niveau' => '6ème',
            'serie' => '',
            'numero' => 1,
            'categorie' => 'General',
            'cycle_id' => $cycle_id,
            'lycee_id' => $lyceeId
        ]);
        $classeId = $db->lastInsertId();
        echo "Created Classe ID: $classeId\n";

        // 4. Create Sequence
        Sequence::save([
            'nom' => 'Séquence 1',
            'type' => 'trimestrielle',
            'date_debut' => '2024-09-01',
            'date_fin' => '2024-10-31',
            'statut' => 'ouverte'
        ]);
        $sequenceId = $db->lastInsertId();
        echo "Created open sequence ID: $sequenceId\n";

        // 5. Create Frais configuration
        $stmt_frais = $db->prepare("
            INSERT INTO frais (lycee_id, cycle, niveau_debut, niveau_fin, serie, frais_inscription, frais_mensuel, frais_logo, frais_carte, annee_academique_id)
            VALUES (:lycee_id, 'CEG', '6ème', '6ème', '', 50000.00, 15000.00, 2000.00, 3000.00, :annee_id)
        ");
        $stmt_frais->execute([
            'annee_id' => $yearId,
            'lycee_id' => $lyceeId
        ]);
        echo "Created Frais configuration\n";

        // 6. Create Student
        Eleve::save([
            'matricule' => 'MAT999',
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'date_naissance' => '2012-05-15',
            'lieu_naissance' => 'Cotonou',
            'sexe' => 'Masculin',
            'nationalite' => 'Béninoise',
            'quartier' => 'Fidjrossè',
            'lycee_id' => $lyceeId
        ]);
        $eleveId = $db->lastInsertId();
        echo "Created Student ID: $eleveId\n";

        // 7. Create Etude record
        Etude::create([
            'eleve_id' => $eleveId,
            'classe_id' => $classeId,
            'lycee_id' => $lyceeId,
            'annee_academique_id' => $yearId,
            'is_active' => 1,
            'status' => 'active'
        ]);
        echo "Created Etude enrollment\n";

        // 8. Test calculation when no payments are made yet
        $status = FinancialStatusService::getStudentFinancialStatus($eleveId, $yearId);
        if ($status['total_reste'] != 80000.0) {
            throw new Exception("Expected total remains to be 80000.0, got " . $status['total_reste']);
        }
        echo "Initial status checks pass.\n";

        // 9. Simulate a partial payment of 4,000 for monthly fees (Point 3)
        echo "Testing partial payment allocation...\n";
        $etude = Etude::findByEleveAndAnnee($eleveId, $yearId, $lyceeId);
        $userId = 1;
        $modePaiement = 'Espèces';
        $reference = 'REC-001';

        $montantMensualitesPool = 4000.00;
        foreach ($status['details_mensualites'] as $dm) {
            if ($montantMensualitesPool <= 0) break;
            $resteMonth = (float)$dm['reste'];
            if ($resteMonth <= 0) continue;
            $allocated = min($montantMensualitesPool, $resteMonth);
            $montantMensualitesPool -= $allocated;
            $m_cap = ucfirst($dm['mois']);

            $dataMensualite = [
                'etude_id' => $etude['id_etude'],
                'eleve_id' => $eleveId,
                'classe_id' => $etude['classe_id'],
                'lycee_id' => $lyceeId,
                'annee_academique_id' => $yearId,
                'mois_ou_sequence' => $m_cap,
                'montant_verse' => $allocated,
                'montant_attendu' => (float)$dm['attendu'],
                'user_id' => $userId
            ];

            $mensualiteId = Mensualite::findOrCreate($dataMensualite);
            Mensualite::addDetail([
                'mensualite_id' => $mensualiteId,
                'montant' => $allocated,
                'mode_paiement' => $modePaiement,
                'reference_transaction' => $reference,
                'recu_numero' => $reference
            ]);
        }

        // Assert: September (oldest month) should have 4,000 paid and 11,000 remaining
        $status2 = FinancialStatusService::getStudentFinancialStatus($eleveId, $yearId);
        echo "Status after 4,000 payment:\n";
        print_r($status2);

        if ($status2['details_mensualites'][0]['verse'] != 4000.0) {
            throw new Exception("Expected oldest month to have 4000.0 verse, got " . $status2['details_mensualites'][0]['verse']);
        }
        if ($status2['details_mensualites'][0]['reste'] != 11000.0) {
            throw new Exception("Expected oldest month to have 11000.0 remains, got " . $status2['details_mensualites'][0]['reste']);
        }
        if ($status2['details_mensualites'][1]['verse'] != 0.0) {
            throw new Exception("Expected second month to be unpaid, got " . $status2['details_mensualites'][1]['verse']);
        }

        // 10. Simulate a payment of 25,000 (Point 4)
        // Should complete September (11,000), complete October (14,000 remaining? No, wait: October expected is 15,000, so 14,000 of the 25,000 goes to October)
        // Leaving October with 1,000 remains.
        echo "Testing payment larger than one month...\n";
        $montantMensualitesPool = 25000.00;
        foreach ($status2['details_mensualites'] as $dm) {
            if ($montantMensualitesPool <= 0) break;
            $resteMonth = (float)$dm['reste'];
            if ($resteMonth <= 0) continue;
            $allocated = min($montantMensualitesPool, $resteMonth);
            $montantMensualitesPool -= $allocated;
            $m_cap = ucfirst($dm['mois']);

            $dataMensualite = [
                'etude_id' => $etude['id_etude'],
                'eleve_id' => $eleveId,
                'classe_id' => $etude['classe_id'],
                'lycee_id' => $lyceeId,
                'annee_academique_id' => $yearId,
                'mois_ou_sequence' => $m_cap,
                'montant_verse' => $allocated,
                'montant_attendu' => (float)$dm['attendu'],
                'user_id' => $userId
            ];

            $mensualiteId = Mensualite::findOrCreate($dataMensualite);
            Mensualite::addDetail([
                'mensualite_id' => $mensualiteId,
                'montant' => $allocated,
                'mode_paiement' => $modePaiement,
                'reference_transaction' => $reference,
                'recu_numero' => $reference
            ]);
        }

        $status3 = FinancialStatusService::getStudentFinancialStatus($eleveId, $yearId);
        echo "Status after 25,000 payment:\n";
        print_r($status3);

        if ($status3['details_mensualites'][0]['reste'] != 0.0) {
            throw new Exception("Expected oldest month to be fully paid (0.0 remains), got " . $status3['details_mensualites'][0]['reste']);
        }
        if ($status3['details_mensualites'][1]['verse'] != 14000.0) {
            throw new Exception("Expected second month to have 14000.0 verse, got " . $status3['details_mensualites'][1]['verse']);
        }
        if ($status3['details_mensualites'][1]['reste'] != 1000.0) {
            throw new Exception("Expected second month to have 1000.0 remains, got " . $status3['details_mensualites'][1]['reste']);
        }

        echo "\n[PASS] All end-to-end calculations are 100% correct!\n";

    } catch (Exception $e) {
        echo "\n[FAIL] Test failed with exception: " . $e->getMessage() . "\n";
        exit(1);
    } finally {
        $db->rollBack();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}

run_full_financial_test();
?>