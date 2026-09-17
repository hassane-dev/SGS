<?php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/CsrfService.php';

class CsrfServiceTest {

    public static function run(): void {
        echo "=========================================================\n";
        echo " RUNNING CENTRALIZED CSRF PROTECTION TEST SUITE\n";
        echo "=========================================================\n";

        // 1. Token Generation
        Auth::startSession();
        $token1 = CsrfService::token();
        if (empty($token1) || strlen($token1) !== 64) {
            throw new Exception("[FAIL] CsrfService::token() failed to generate 64-char hex token.");
        }
        echo " [PASS] Token generated successfully: " . substr($token1, 0, 10) . "...\n";

        // 2. Helper Functions
        $field = csrf_field();
        if (strpos($field, 'type="hidden"') === false || strpos($field, 'name="csrf_token"') === false || strpos($field, $token1) === false) {
            throw new Exception("[FAIL] csrf_field() helper output invalid: $field");
        }
        echo " [PASS] csrf_field() helper output valid.\n";

        if (csrf_token() !== $token1) {
            throw new Exception("[FAIL] csrf_token() helper does not match session token.");
        }
        echo " [PASS] csrf_token() helper matches session token.\n";

        // 3. Validation - Valid Token
        if (!CsrfService::validate($token1)) {
            throw new Exception("[FAIL] CsrfService::validate() rejected valid token.");
        }
        echo " [PASS] CsrfService::validate() accepted valid token.\n";

        // 4. Validation - Invalid Tokens
        if (CsrfService::validate("invalid_token_12345")) {
            throw new Exception("[FAIL] CsrfService::validate() accepted invalid token.");
        }
        if (CsrfService::validate("")) {
            throw new Exception("[FAIL] CsrfService::validate() accepted empty token.");
        }
        if (CsrfService::validate(null)) {
            throw new Exception("[FAIL] CsrfService::validate() accepted null token.");
        }
        echo " [PASS] CsrfService::validate() correctly rejected invalid, empty, and null tokens.\n";

        // 5. Extraction from POST & Headers
        $_POST['csrf_token'] = $token1;
        if (CsrfService::extractTokenFromRequest() !== $token1) {
            throw new Exception("[FAIL] Extraction from \$_POST failed.");
        }
        unset($_POST['csrf_token']);

        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token1;
        if (CsrfService::extractTokenFromRequest() !== $token1) {
            throw new Exception("[FAIL] Extraction from HTTP_X_CSRF_TOKEN header failed.");
        }
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
        echo " [PASS] Token extraction from POST body and HTTP headers succeeded.\n";

        echo "=========================================================\n";
        echo " SUCCESS: CENTRALIZED CSRF SERVICE IS 100% OPERATIONAL!\n";
        echo "=========================================================\n";
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    CsrfServiceTest::run();
}
