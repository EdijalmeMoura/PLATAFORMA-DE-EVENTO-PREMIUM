<?php
namespace App\Core;

defined('APP') or exit;

/** Validações comuns. */
class Validator {
    public static function email($v) {
        return filter_var(trim((string) $v), FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function required($v) {
        if (is_array($v)) return count($v) > 0;
        return trim((string) $v) !== '';
    }

    public static function cpf($v) {
        $c = preg_replace('/\D/', '', (string) $v);
        if (strlen($c) !== 11 || preg_match('/^(\d)\1{10}$/', $c)) return false;
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($i = 0; $i < $t; $i++) $d += (int) $c[$i] * (($t + 1) - $i);
            $d = ((10 * $d) % 11) % 10;
            if ((int) $c[$t] !== $d) return false;
        }
        return true;
    }

    public static function phone($v) {
        $d = preg_replace('/\D/', '', (string) $v);
        return strlen($d) >= 10 && strlen($d) <= 13;
    }

    public static function date($v) {
        if (!preg_match('#^\d{4}-\d{2}-\d{2}$#', (string) $v) && !preg_match('#^\d{2}/\d{2}/\d{4}$#', (string) $v)) return false;
        $v = str_replace('/', '-', (string) $v);
        if (preg_match('#^\d{2}-\d{2}-\d{4}$#', $v)) {
            $p = explode('-', $v);
            $v = $p[2] . '-' . $p[1] . '-' . $p[0];
        }
        $p = explode('-', $v);
        return checkdate((int) $p[1], (int) $p[2], (int) $p[0]);
    }

    public static function toDbDate($v) {
        $v = trim((string) $v);
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $v, $m)) return $m[3] . '-' . $m[2] . '-' . $m[1];
        return $v;
    }

    /** Normaliza WhatsApp para E.164 BR */
    public static function normalizePhone($v) {
        $d = preg_replace('/\D/', '', (string) $v);
        if ($d === '') return '';
        if (strlen($d) <= 11 && substr($d, 0, 2) !== '55') $d = '55' . $d;
        return '+' . $d;
    }

    public static function clean($v) {
        if (is_array($v)) return array_map([self::class, 'clean'], $v);
        $v = trim((string) $v);
        $v = preg_replace('/\x00/', '', $v);
        return $v;
    }
}
