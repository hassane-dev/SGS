<?php

require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/FinancialStatusService.php';
require_once __DIR__ . '/../models/FinanceService.php';
require_once __DIR__ . '/../models/Inscription.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class RapportFinancierController {

    private function checkAccess($action = 'view', $resource = 'paiement') {
        if (!Auth::can($action, $resource)) {
            if (PHP_SAPI === 'cli') {
                throw new Exception("FORBIDDEN");
            }
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess('view');
        $lycee_id = Auth::getLyceeId();
        $db = Database::getInstance();

        $activeYear = AnneeAcademique::findActive();

        $date_debut = $_GET['date_debut'] ?? date('Y-m-01');
        $date_fin = $_GET['date_fin'] ?? date('Y-m-d');

        // 1. Totaux par type
        $stmt = $db->prepare("
            SELECT type, SUM(montant) as total
            FROM (
                SELECT 'Inscription' as type, montant_verse as montant FROM inscriptions WHERE lycee_id = :l1 AND DATE(date_inscription) BETWEEN :d1 AND :d2 AND statut = 'valide'
                UNION ALL
                SELECT 'Mensualité' as type, md.montant FROM mensualite_details md JOIN mensualites m ON md.mensualite_id = m.id_mensualite WHERE m.lycee_id = :l2 AND DATE(md.date_paiement) BETWEEN :d3 AND :d4 AND md.statut = 'valide'
            ) as t
            GROUP BY type
        ");
        $stmt->execute(['l1' => $lycee_id, 'd1' => $date_debut, 'd2' => $date_fin, 'l2' => $lycee_id, 'd3' => $date_debut, 'd4' => $date_fin]);
        $statsType = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Totaux par mode de paiement
        $stmt = $db->prepare("
            SELECT mode, SUM(montant) as total
            FROM (
                SELECT 'Espèces' as mode, montant_verse as montant FROM inscriptions WHERE lycee_id = :l1 AND DATE(date_inscription) BETWEEN :d1 AND :d2 AND statut = 'valide'
                UNION ALL
                SELECT mode_paiement as mode, montant FROM mensualite_details md JOIN mensualites m ON md.mensualite_id = m.id_mensualite WHERE m.lycee_id = :l2 AND DATE(md.date_paiement) BETWEEN :d3 AND :d4 AND md.statut = 'valide'
            ) as t
            GROUP BY mode
        ");
        $stmt->execute(['l1' => $lycee_id, 'd1' => $date_debut, 'd2' => $date_fin, 'l2' => $lycee_id, 'd3' => $date_debut, 'd4' => $date_fin]);
        $statsMode = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Évolution quotidienne
        $stmt = $db->prepare("
            SELECT date, SUM(montant) as total
            FROM (
                SELECT DATE(date_inscription) as date, montant_verse as montant FROM inscriptions WHERE lycee_id = :l1 AND DATE(date_inscription) BETWEEN :d1 AND :d2 AND statut = 'valide'
                UNION ALL
                SELECT DATE(md.date_paiement) as date, md.montant FROM mensualite_details md JOIN mensualites m ON md.mensualite_id = m.id_mensualite WHERE m.lycee_id = :l2 AND DATE(md.date_paiement) BETWEEN :d3 AND :d4 AND md.statut = 'valide'
            ) as t
            GROUP BY date
            ORDER BY date ASC
        ");
        $stmt->execute(['l1' => $lycee_id, 'd1' => $date_debut, 'd2' => $date_fin, 'l2' => $lycee_id, 'd3' => $date_debut, 'd4' => $date_fin]);
        $evolutionQuotidienne = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Financial Situation per Class and Annual Situation
        $classesFinances = [];
        $grandExpected = 0.00;
        $grandPaid = 0.00;
        $grandRemaining = 0.00;

        if ($activeYear) {
            $classes = Classe::findAll($lycee_id);

            foreach ($classes as $c) {
                $classeId = $c['id_classe'];

                // Get students in this class for the active year
                $stmt = $db->prepare("
                    SELECT e.id_eleve
                    FROM eleves e
                    JOIN etudes et ON e.id_eleve = et.eleve_id
                    WHERE et.classe_id = :classe_id
                    AND et.annee_academique_id = :annee_id
                    AND (et.status = 'active' OR et.status = 'en_attente_paiement')
                ");
                $stmt->execute(['classe_id' => $classeId, 'annee_id' => $activeYear['id']]);
                $studentsInClass = $stmt->fetchAll(PDO::FETCH_COLUMN);

                $expectedClass = 0.00;
                $remainingClass = 0.00;
                $paidClass = 0.00;

                foreach ($studentsInClass as $sId) {
                    $status = FinancialStatusService::getStudentFinancialStatus($sId, $activeYear['id']);
                    if ($status) {
                        $fees = FinanceService::calculateStudentFees($sId);
                        if ($fees) {
                            $monthlyPaid = 0.00;
                            foreach ($status['details_mensualites'] as $dm) {
                                $monthlyPaid += (float)$dm['verse'];
                            }

                            $inscriptionExpected = $fees['inscription_ajustee'];
                            $inscription = Inscription::findByEleveAndAnnee($sId, $activeYear['id'], $lycee_id);
                            $options = $inscription ? json_decode($inscription['details_frais'] ?? '[]', true) : [];
                            if (!empty($options['logo'])) $inscriptionExpected += $fees['logo_ajustee'];
                            if (!empty($options['carte'])) $inscriptionExpected += $fees['carte_ajustee'];

                            $inscriptionPaid = ($inscription && ($inscription['statut'] ?? 'valide') === 'valide') ? (float)$inscription['montant_verse'] : 0.00;

                            $studentPaid = $inscriptionPaid + $monthlyPaid;
                            $studentReste = (float)$status['total_reste'];
                            $studentExpected = $studentPaid + $studentReste;

                            $expectedClass += $studentExpected;
                            $paidClass += $studentPaid;
                            $remainingClass += $studentReste;
                        }
                    }
                }

                if ($expectedClass > 0 || $paidClass > 0 || $remainingClass > 0) {
                    $classesFinances[] = [
                        'classe_id' => $classeId,
                        'nom_classe' => Classe::getFormattedName($c),
                        'expected' => $expectedClass,
                        'paid' => $paidClass,
                        'remaining' => $remainingClass,
                        'rate' => $expectedClass > 0 ? ($paidClass / $expectedClass) * 100 : 100
                    ];

                    $grandExpected += $expectedClass;
                    $grandPaid += $paidClass;
                    $grandRemaining += $remainingClass;
                }
            }
        }

        View::render('comptabilite/rapports/index', [
            'title' => _("Rapports Financiers & États Comptables"),
            'statsType' => $statsType,
            'statsMode' => $statsMode,
            'evolution' => $evolutionQuotidienne,
            'classesFinances' => $classesFinances,
            'grandExpected' => $grandExpected,
            'grandPaid' => $grandPaid,
            'grandRemaining' => $grandRemaining,
            'activeYear' => $activeYear,
            'filters' => [
                'date_debut' => $date_debut,
                'date_fin' => $date_fin
            ]
        ]);
    }
}
?>