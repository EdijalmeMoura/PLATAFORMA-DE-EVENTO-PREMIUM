<?php
namespace App\Services;

use App\Core\Database;

defined('APP') or exit;

/**
 * Geração de códigos únicos e tokens seguros de ticket.
 */
class TicketService {
    const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // sem ambíguos

    /** Gera código único estilo EVT-2026-A8K4P9 */
    public static function generateCode($year = null) {
        $year = $year ?: date('Y');
        $prefix = 'EVT-' . $year . '-';
        for ($i = 0; $i < 50; $i++) {
            $suffix = '';
            for ($j = 0; $j < 6; $j++) {
                $suffix .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $code = $prefix . $suffix;
            $exists = Database::fetchColumn(
                'SELECT id FROM ' . Database::table('registrations') . ' WHERE code = ?',
                [$code]
            );
            if (!$exists) return $code;
        }
        // Fallback extremamente improvável
        return $prefix . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    }

    /** Token seguro do QR (sem dados pessoais) */
    public static function generateToken() {
        return bin2hex(random_bytes(24)); // 48 chars
    }

    public static function ticketUrl($qrToken) {
        return url('ticket/' . $qrToken);
    }
}
