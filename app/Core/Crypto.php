<?php
namespace App\Core;

defined('APP') or exit;

/**
 * Criptografia simétrica (AES-256-CBC) para segredos
 * (senha SMTP, token WhatsApp). Nunca expõe valores em tela.
 */
class Crypto {
    private static function key() {
        if (APP_KEY !== '') return hash('sha256', APP_KEY, true);
        // Chave local gerada uma vez (fallback para hospedagem simples)
        $file = APP_ROOT . '/storage/.key';
        if (!is_file($file)) {
            @file_put_contents($file, bin2hex(random_bytes(32)));
            @chmod($file, 0600);
        }
        $k = trim((string) @file_get_contents($file));
        return hash('sha256', $k, true);
    }

    public static function encrypt($plain) {
        if ($plain === null || $plain === '') return '';
        $iv = random_bytes(16);
        $ct = openssl_encrypt((string) $plain, 'AES-256-CBC', self::key(), OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $ct);
    }

    public static function decrypt($encoded) {
        if (!$encoded) return '';
        $raw = base64_decode((string) $encoded, true);
        if ($raw === false || strlen($raw) < 17) return '';
        $iv = substr($raw, 0, 16);
        $ct = substr($raw, 16);
        $pt = openssl_decrypt($ct, 'AES-256-CBC', self::key(), OPENSSL_RAW_DATA, $iv);
        return $pt === false ? '' : $pt;
    }

    /** Mascaramento para exibição (••••1234) */
    public static function mask($encoded, $visible = 4) {
        $pt = self::decrypt($encoded);
        if ($pt === '') return '— não configurado —';
        $len = strlen($pt);
        if ($len <= $visible) return str_repeat('•', $len);
        return str_repeat('•', min(8, $len - $visible)) . substr($pt, -$visible);
    }
}
