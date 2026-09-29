<?php
namespace App\Services;

use App\Core\Database;

defined('APP') or exit;

/**
 * Validação de tickets e registro de check-in.
 * Estados: VALID, ALREADY_USED, CANCELLED, NOT_FOUND.
 */
class CheckinService {
    /** Localiza inscrição por código OU token do QR */
    public static function lookup($eventId, $input) {
        $input = trim((string) $input);
        if ($input === '') return null;
        // Pode vir como URL completa do ticket
        if (preg_match('#/ticket/([A-Za-z0-9-]+)#', $input, $m)) $input = $m[1];
        $row = Database::fetch(
            'SELECT r.*, t.name AS ticket_name, tk.qr_token
             FROM ' . Database::table('registrations') . ' r
             LEFT JOIN ' . Database::table('ticket_types') . ' t ON t.id = r.ticket_type_id
             LEFT JOIN ' . Database::table('tickets') . ' tk ON tk.registration_id = r.id
             WHERE r.event_id = ? AND (r.code = ? OR tk.qr_token = ? OR tk.code = ?)
             LIMIT 1',
            [(int) $eventId, $input, $input, $input]
        );
        return $row ?: null;
    }

    /** Apenas valida, sem registrar */
    public static function validate($eventId, $input) {
        $reg = self::lookup($eventId, $input);
        if (!$reg) return ['state' => 'NOT_FOUND', 'registration' => null];
        if ($reg['status'] === 'CANCELLED') return ['state' => 'CANCELLED', 'registration' => $reg];
        if ($reg['status'] === 'CHECKED_IN') return ['state' => 'ALREADY_USED', 'registration' => $reg];
        return ['state' => 'VALID', 'registration' => $reg];
    }

    /** Registra o check-in. Retorna [state, registration] */
    public static function checkin($eventId, $input, $userId = null, $method = 'manual') {
        $v = self::validate($eventId, $input);
        if ($v['state'] !== 'VALID') return $v;
        $reg = $v['registration'];
        // UPDATE condicional = atômico: 2 portarias simultâneas não duplicam
        $affected = Database::update('registrations', [
            'status' => 'CHECKED_IN',
            'checked_in_at' => now(),
            'checked_in_by' => $userId,
            'updated_at' => now(),
        ], 'id = :id AND status != :st', ['id' => (int) $reg['id'], 'st' => 'CHECKED_IN']);
        if ($affected === 0) {
            // Outro operador confirmou entre a validação e o update
            return self::validate($eventId, $input);
        }
        Database::insert('checkins', [
            'registration_id' => (int) $reg['id'],
            'checked_in_at' => now(),
            'checked_in_by' => $userId,
            'method' => $method === 'qr' ? 'qr' : 'manual',
            'created_at' => now(),
        ]);
        try {
            MessageService::fireAutomation((int) $eventId, 'after_checkin', (int) $reg['id']);
        } catch (\Exception $e) { /* silencioso */ }
        $reg['status'] = 'CHECKED_IN';
        $reg['checked_in_at'] = now();
        return ['state' => 'VALID', 'registration' => $reg, 'just_checked_in' => true];
    }

    public static function stats($eventId) {
        $t = Database::table('registrations');
        $total = (int) Database::fetchColumn("SELECT COUNT(*) FROM $t WHERE event_id = ? AND status != 'CANCELLED'", [(int) $eventId]);
        $present = (int) Database::fetchColumn("SELECT COUNT(*) FROM $t WHERE event_id = ? AND status = 'CHECKED_IN'", [(int) $eventId]);
        return ['total' => $total, 'present' => $present, 'pending' => max(0, $total - $present)];
    }
}
