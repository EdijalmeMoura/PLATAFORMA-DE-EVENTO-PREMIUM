<?php
namespace App\Core;

defined('APP') or exit;

/** Proteção CSRF por token de sessão. */
class Csrf {
    public static function token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field() {
        return '<input type="hidden" name="csrf_token" value="' . e(self::token()) . '">';
    }

    public static function validate($token = null) {
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        }
        $valid = isset($_SESSION['csrf_token']) && is_string($token)
            && hash_equals((string) $_SESSION['csrf_token'], (string) $token);
        return $valid;
    }

    /** Valida ou aborta (403 / JSON) */
    public static function requireValid() {
        if (!self::validate()) {
            if (is_json_request()) json_response(['ok' => false, 'error' => 'Sessão expirada. Recarregue a página.'], 419);
            http_response_code(419);
            echo 'Sessão expirada. Volte e tente novamente.';
            exit;
        }
    }
}
