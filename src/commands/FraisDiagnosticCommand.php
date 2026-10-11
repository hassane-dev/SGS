<?php
// src/commands/FraisDiagnosticCommand.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Frais.php';

class FraisDiagnosticCommand {

    public static function run() {
        echo "=== DIAGNOSTIC DES GRILLES TARIFAIRES (FRAIS) ===\n\n";

        $db = Database::getInstance();

        // 1. Inspecter toutes les grilles tarifaires
        $stmt = $db->query("SELECT * FROM frais ORDER BY lycee_id, annee_academique_id, id_frais");
        $allFrais = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $totalFrais = count($allFrais);
        $linkedCount = 0;
        $unlinkedCount = 0;
        $ambiguousCount = 0;

        echo "--- 1. Analyse des Règles de Frais ($totalFrais enregistrements) ---\n";

        foreach ($allFrais as $f) {
            $idFrais = $f['id_frais'] ?? null;
            $lyceeId = $f['lycee_id'] ?? null;
            $anneeId = $f['annee_academique_id'] ?? null;
            $cycleStr = $f['cycle'] ?? null;
            $cycleId = $f['cycle_id'] ?? null;
            $niveauDebut = $f['niveau_debut'] ?? null;
            $niveauFin = $f['niveau_fin'] ?? null;
            $serie = $f['serie'] ?? null;

            if (!empty($cycleId)) {
                $linkedCount++;
                echo "[OK] Frais #$idFrais: lycee_id=$lyceeId, annee=$anneeId -> cycle_id=$cycleId ('$cycleStr') [Lié en BDD]\n";
            } elseif (!empty($cycleStr)) {
                // Tenter une résolution en lecture seule
                $stmtC = $db->prepare("SELECT id_cycle, nom_cycle FROM cycles WHERE lycee_id = :lycee_id AND nom_cycle = :nom");
                $stmtC->execute(['lycee_id' => $lyceeId, 'nom' => $cycleStr]);
                $matches = $stmtC->fetchAll(PDO::FETCH_ASSOC);

                if (count($matches) === 1) {
                    $unlinkedCount++;
                    $resId = $matches[0]['id_cycle'];
                    echo "[AFFECTABLE] Frais #$idFrais: lycee_id=$lyceeId, annee=$anneeId -> cycle_id NULL, cycle_str='$cycleStr' => Résoluble sur cycle_id=$resId\n";
                } elseif (count($matches) > 1) {
                    $ambiguousCount++;
                    echo "[AMBIGU] Frais #$idFrais: lycee_id=$lyceeId, annee=$anneeId -> cycle_str='$cycleStr' correspond à " . count($matches) . " cycles en BDD !\n";
                } else {
                    $unlinkedCount++;
                    echo "[ORPHELIN] Frais #$idFrais: lycee_id=$lyceeId, annee=$anneeId -> cycle_str='$cycleStr' introuvable dans la BDD pour ce lycée !\n";
                }
            } else {
                echo "[PLAGE] Frais #$idFrais: lycee_id=$lyceeId, annee=$anneeId -> Plage {$niveauDebut} - {$niveauFin} (Série: {$serie})\n";
            }
        }

        echo "\nSynthèse Règles Frais: Total=$totalFrais | Liés=$linkedCount | À migrer=$unlinkedCount | Ambiguïtés=$ambiguousCount\n\n";

        // 2. Vérification des classes sans grille tarifaire
        echo "--- 2. Détection des Classes Actives sans Grille Tarifaire Applicable ---\n";
        $classes = Classe::findAll();

        $missingFeeClasses = 0;
        foreach ($classes as $classe) {
            $nomClasseFormatted = Classe::getFormattedName($classe);
            $feeRule = Frais::findForClasse($classe, $classe['annee_academique_id'] ?? 1);
            if (!$feeRule) {
                $missingFeeClasses++;
                echo "[ALERTE TARIFICATION] Classe ID #{$classe['id_classe']} '$nomClasseFormatted' (Lycée #{$classe['lycee_id']} '{$classe['nom_lycee']}') -> AUCUNE RÈGLE TARIFAIRE APPLICABLE !\n";
            }
        }

        if ($missingFeeClasses === 0) {
            echo "[OK] Toutes les classes configurées possèdent au moins une grille tarifaire valide.\n";
        } else {
            echo "\nATTENTION: $missingFeeClasses classe(s) n'ont aucune règle tarifaire configurée.\n";
        }

        echo "\n=== FIN DIAGNOSTIC ===\n";
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    FraisDiagnosticCommand::run();
}
?>
