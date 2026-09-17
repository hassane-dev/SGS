<?php

require_once __DIR__ . '/Auth.php';

class CsrfService {

    /**
     * Generate or retrieve the CSRF token for the current session.
     */
    public static function token(): string {
        Auth::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate the provided CSRF token against the session token.
     */
    public static function validate(?string $token): bool {
        Auth::startSession();
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        if (empty($sessionToken) || empty($token)) {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }

    /**
     * Extract token from request payload (POST body, JSON payload, or HTTP Header).
     */
    public static function extractTokenFromRequest(): ?string {
        if (!empty($_POST['csrf_token'])) {
            return (string)$_POST['csrf_token'];
        }
        if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        // Check raw JSON body if applicable
        $input = file_get_contents('php://input');
        if ($input) {
            $json = json_decode($input, true);
            if (is_array($json) && !empty($json['csrf_token'])) {
                return (string)$json['csrf_token'];
            }
        }
        return null;
    }

    /**
     * Enforce CSRF protection on mutating requests (POST, PUT, PATCH, DELETE).
     */
    public static function requireValid(): void {
        // Skip check in CLI / automated test execution unless explicitly requested
        if (defined('TEST_MODE') && TEST_MODE === true) {
            return;
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = self::extractTokenFromRequest();
            if (!self::validate($token)) {
                http_response_code(403);
                $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                          (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'error', 'message' => 'Jeton CSRF invalide ou expiré.']);
                } else {
                    require_once __DIR__ . '/View.php';
                    View::render('errors/403', ['message' => 'Action refusée : Jeton CSRF invalide ou expiré.']);
                }
                exit();
            }
        }
    }
}

/**
 * Global helper function to output HTML hidden CSRF input field.
 */
if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(CsrfService::token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

/**
 * Global helper function to return the raw CSRF token string.
 */
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return CsrfService::token();
    }
}
