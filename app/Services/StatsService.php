<?php
namespace App\Services;

use App\Core\Database;

defined('APP') or exit;

/** Agregações para dashboard e relatórios. */
class StatsService {
    public static function overview($eventId) {
        $t = Database::table('registrations');
        $E = (int) $eventId;
        $total = (int) Database::fetchColumn("SELECT COUNT(*) FROM $t WHERE event_id = ? AND status != 'CANCELLED'", [$E]);
        $confirmed = (int) Database::fetchColumn("SELECT COUNT(*) FROM $t WHERE event_id = ? AND status IN ('CONFIRMED','CHECKED_IN')", [$E]);
        $pending = (int) Database::fetchColumn("SELECT COUNT(*) FROM $t WHERE event_id = ? AND status = 'PENDING'", [$E]);
        $cancelled = (int) Database::fetchColumn("SELECT COUNT(*) FROM $t WHERE event_id = ? AND status = 'CANCELLED'", [$E]);
        $checkins = (int) Database::fetchColumn("SELECT COUNT(*) FROM $t WHERE event_id = ? AND status = 'CHECKED_IN'", [$E]);
        $today = (int) Database::fetchColumn("SELECT COUNT(*) FROM $t WHERE event_id = ? AND DATE(created_at) = DATE('now','localtime')", [$E]);
        if (Database::driver() === 'mysql') {
            $today = (int) Database::fetchColumn("SELECT COUNT(*) FROM $t WHERE event_id = ? AND DATE(created_at) = CURDATE()", [$E]);
        }
        // Capacidade total (soma das quantidades dos ingressos ativos)
        $tt = Database::table('ticket_types');
        $capacity = (int) Database::fetchColumn("SELECT COALESCE(SUM(quantity),0) FROM $tt WHERE event_id = ? AND status = 'ACTIVE'", [$E]);
        // Receita (aprovadas/free contam? receita = soma price das não-canceladas)
        $rev = Database::fetchColumn(
            "SELECT COALESCE(SUM(t.price),0) FROM $t r JOIN $tt t ON t.id = r.ticket_type_id WHERE r.event_id = ? AND r.status != 'CANCELLED'",
            [$E]
        );
        return [
            'total' => $total,
            'confirmed' => $confirmed,
            'pending' => $pending,
            'cancelled' => $cancelled,
            'checkins' => $checkins,
            'today' => $today,
            'capacity' => $capacity,
            'remaining' => max(0, $capacity - $total),
            'revenue' => (float) $rev,
            'checkin_rate' => $confirmed > 0 ? round($checkins / $confirmed * 100, 1) : 0,
        ];
    }

    public static function perDay($eventId, $days = 30) {
        $t = Database::table('registrations');
        if (Database::driver() === 'mysql') {
            return Database::fetchAll(
                "SELECT DATE(created_at) d, COUNT(*) c FROM $t WHERE event_id = ? AND status != 'CANCELLED' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY d",
                [(int) $eventId]
            );
        }
        return Database::fetchAll(
            "SELECT DATE(created_at) d, COUNT(*) c FROM $t WHERE event_id = ? AND status != 'CANCELLED' AND DATE(created_at) >= DATE('now','localtime','-$days days') GROUP BY DATE(created_at) ORDER BY d",
            [(int) $eventId]
        );
    }

    public static function perHour($eventId) {
        $t = Database::table('registrations');
        if (Database::driver() === 'mysql') {
            return Database::fetchAll(
                "SELECT HOUR(created_at) h, COUNT(*) c FROM $t WHERE event_id = ? AND status != 'CANCELLED' GROUP BY HOUR(created_at) ORDER BY h",
                [(int) $eventId]
            );
        }
        return Database::fetchAll(
            "SELECT CAST(strftime('%H', created_at) AS INT) h, COUNT(*) c FROM $t WHERE event_id = ? AND status != 'CANCELLED' GROUP BY h ORDER BY h",
            [(int) $eventId]
        );
    }

    public static function perTicket($eventId) {
        $t = Database::table('registrations');
        $tt = Database::table('ticket_types');
        return Database::fetchAll(
            "SELECT t.name, t.price, t.quantity, COUNT(r.id) sold
             FROM $tt t LEFT JOIN $t r ON r.ticket_type_id = t.id AND r.status != 'CANCELLED'
             WHERE t.event_id = ? GROUP BY t.id ORDER BY t.sort_order",
            [(int) $eventId]
        );
    }

    public static function perSource($eventId) {
        $t = Database::table('registrations');
        return Database::fetchAll(
            "SELECT COALESCE(NULLIF(source,''),'site') s, COUNT(*) c FROM $t WHERE event_id = ? AND status != 'CANCELLED' GROUP BY s ORDER BY c DESC",
            [(int) $eventId]
        );
    }

    public static function perCity($eventId, $limit = 15) {
        $t = Database::table('registrations');
        return Database::fetchAll(
            "SELECT COALESCE(NULLIF(city,''),'—') city, COUNT(*) c FROM $t WHERE event_id = ? AND status != 'CANCELLED' GROUP BY city ORDER BY c DESC LIMIT " . (int) $limit,
            [(int) $eventId]
        );
    }

    public static function perCompany($eventId, $limit = 15) {
        $t = Database::table('registrations');
        return Database::fetchAll(
            "SELECT COALESCE(NULLIF(company,''),'—') company, COUNT(*) c FROM $t WHERE event_id = ? AND status != 'CANCELLED' GROUP BY company ORDER BY c DESC LIMIT " . (int) $limit,
            [(int) $eventId]
        );
    }

    public static function checkinsPerHour($eventId) {
        $t = Database::table('checkins');
        $r = Database::table('registrations');
        if (Database::driver() === 'mysql') {
            return Database::fetchAll(
                "SELECT HOUR(c.checked_in_at) h, COUNT(*) n FROM $t c JOIN $r r ON r.id = c.registration_id WHERE r.event_id = ? GROUP BY h ORDER BY h",
                [(int) $eventId]
            );
        }
        return Database::fetchAll(
            "SELECT CAST(strftime('%H', c.checked_in_at) AS INT) h, COUNT(*) n FROM $t c JOIN $r r ON r.id = c.registration_id WHERE r.event_id = ? GROUP BY h ORDER BY h",
            [(int) $eventId]
        );
    }

    public static function comms($eventId) {
        $l = Database::table('message_logs');
        $q = Database::table('message_queue');
        $E = (int) $eventId;
        $sent = (int) Database::fetchColumn("SELECT COUNT(*) FROM $l WHERE event_id = ? AND status = 'sent'", [$E]);
        $failed = (int) Database::fetchColumn("SELECT COUNT(*) FROM $l WHERE event_id = ? AND status = 'failed'", [$E]);
        $queued = (int) Database::fetchColumn("SELECT COUNT(*) FROM $q WHERE event_id = ? AND status IN ('queued','processing')", [$E]);
        $opened = (int) Database::fetchColumn("SELECT COUNT(*) FROM $l WHERE event_id = ? AND status = 'sent' AND channel = 'email' AND opened_at IS NOT NULL", [$E]);
        $emailSent = (int) Database::fetchColumn("SELECT COUNT(*) FROM $l WHERE event_id = ? AND status = 'sent' AND channel = 'email'", [$E]);
        return [
            'sent' => $sent, 'failed' => $failed, 'queued' => $queued,
            'opened' => $opened, 'email_sent' => $emailSent,
            'open_rate' => $emailSent > 0 ? round($opened / $emailSent * 100, 1) : 0,
        ];
    }

    public static function recent($eventId, $limit = 8) {
        $t = Database::table('registrations');
        $tt = Database::table('ticket_types');
        return Database::fetchAll(
            "SELECT r.id, r.code, r.name, r.email, r.status, r.created_at, t.name ticket_name
             FROM $t r LEFT JOIN $tt t ON t.id = r.ticket_type_id
             WHERE r.event_id = ? ORDER BY r.id DESC LIMIT " . (int) $limit,
            [(int) $eventId]
        );
    }
}
