<?php
// tests/unit/DepensePieceTest.php

require_once __DIR__ . '/../../src/models/DepensePiece.php';

function test_depense_piece_validation() {
    echo "--- Running DepensePieceTest ---\n";

    // 1. Valid attachment verification
    try {
        DepensePiece::validateAttachment([
            'taille_octets' => 1024 * 50,
            'sha256_hash' => str_repeat('a', 64),
            'type_mime' => 'application/pdf'
        ]);
        assert_test(true, "Valid attachment validated successfully.");
    } catch (Exception $e) {
        assert_test(false, "Valid attachment failed validation: " . $e->getMessage());
    }

    // 2. Max size limit verification
    try {
        DepensePiece::validateAttachment([
            'taille_octets' => 6 * 1024 * 1024, // 6MB
            'sha256_hash' => str_repeat('a', 64),
            'type_mime' => 'application/pdf'
        ]);
        assert_test(false, "Fichier dépassant 5Mo should fail.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), 'dépasse la taille maximale') !== false, "File size exceeding limit validation verified.");
    }

    // 3. Invalid SHA-256 validation
    try {
        DepensePiece::validateAttachment([
            'taille_octets' => 1024,
            'sha256_hash' => 'invalid_hash_too_short',
            'type_mime' => 'application/pdf'
        ]);
        assert_test(false, "Invalid SHA-256 hash format should fail.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), 'SHA-256 invalide') !== false, "SHA-256 format validation verified.");
    }

    // 4. Invalid mime type validation
    try {
        DepensePiece::validateAttachment([
            'taille_octets' => 1024,
            'sha256_hash' => str_repeat('b', 64),
            'type_mime' => 'application/javascript' // Executable/JS not allowed
        ]);
        assert_test(false, "Unallowed mime type should fail.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), 'Type de fichier non autorisé') !== false, "Mime type block verified.");
    }
}
?>