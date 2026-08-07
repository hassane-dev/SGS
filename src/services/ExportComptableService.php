<?php
// src/services/ExportComptableService.php

class ExportComptableService {

    /**
     * Formate les données au format CSV
     */
    public static function formatToCSV($data, $userId) {
        $output = fopen('php://temp', 'r+');

        // Add cryptographic sign / signature header
        $timestamp = date('Y-m-d H:i:s');
        $signature_hash = hash('sha256', $timestamp . $userId . 'PHASE_5_SECURE_EXPORT');
        fputcsv($output, ["# SGS SECURE COMPTABILITE EXPORT"]);
        fputcsv($output, ["# Genere le: " . $timestamp, "Utilisateur ID: " . $userId]);
        fputcsv($output, ["# Signature: " . $signature_hash]);
        fputcsv($output, []); // Empty line

        // Columns headers
        fputcsv($output, ['Journal', 'Date Piece', 'Numero Piece', 'Libelle Piece', 'Compte Numero', 'Libelle Compte', 'Debit', 'Credit', 'Libelle Ligne']);

        foreach ($data as $piece) {
            foreach ($piece['lignes'] as $ligne) {
                fputcsv($output, [
                    $piece['journal_code'],
                    $piece['date_piece'],
                    $piece['numero_piece'],
                    $piece['libelle_piece'],
                    $ligne['compte_numero'],
                    $ligne['compte_libelle'],
                    $ligne['debit'],
                    $ligne['credit'],
                    $ligne['libelle_ligne']
                ]);
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Formate au format JSON signé
     */
    public static function formatToJSON($data, $userId) {
        $timestamp = date('Y-m-d H:i:s');
        $signature_hash = hash('sha256', $timestamp . $userId . 'PHASE_5_SECURE_EXPORT');

        $export = [
            'metadata' => [
                'system' => 'SGS School Management System',
                'phase' => 'Phase 5 Accounting Export',
                'timestamp' => $timestamp,
                'user_id' => $userId,
                'signature' => $signature_hash
            ],
            'pieces' => $data
        ];

        return json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
