<?php
namespace App\Core;

defined('APP') or exit;

/** Rate limiting simples baseado no banco. */
class RateLimiter {
    public static function check($key, $limit, $windowSeconds) {
        $row = Database::fetch(
            'SELECT * FROM ' . Database::table('rate_limits') . ' WHERE rkey = ?',
            [$key]
        );
        if (!$row) return true;
        $start = strtotime($row['window_start']);
        if (time() - $start > $windowSeconds) return true;
        return ((int) $row['hits']) < $limit;
    }

    public static function hit($key, $windowSeconds = 3600) {
        $t = Database::table('rate_limits');
        $row = Database::fetch("SELECT * FROM $t WHERE rkey = ?", [$key]);
        if (!$row) {
            Database::query("INSERT INTO $t (rkey, hits, window_start) VALUES (?, 1, ?)", [$key, now()]);
            return 1;
        }
        if (time() - strtotime($row['window_start']) > $windowSeconds) {
            Database::query("UPDATE $t SET hits = 1, window_start = ? WHERE rkey = ?", [now(), $key]);
            return 1;
        }
        Database::query("UPDATE $t SET hits = hits + 1 WHERE rkey = ?", [$key]);
        return (int) $row['hits'] + 1;
    }

    public static function clear($key) {
        Database::query('DELETE FROM ' . Database::table('rate_limits') . ' WHERE rkey = ?', [$key]);
    }
}
