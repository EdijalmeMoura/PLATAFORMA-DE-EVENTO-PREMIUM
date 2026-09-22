<?php
namespace App\Core;

defined('APP') or exit;

/** Evento ativo + conteúdos da landing. */
class Event {
    private static $event = null;
    private static $content = null;

    public static function current() {
        if (self::$event === null) {
            self::$event = Database::fetch(
                'SELECT * FROM ' . Database::table('events') . ' ORDER BY id ASC LIMIT 1'
            ) ?: [];
        }
        return self::$event;
    }

    public static function id() {
        $e = self::current();
        return isset($e['id']) ? (int) $e['id'] : 0;
    }

    public static function allContent() {
        if (self::$content === null) {
            self::$content = [];
            $rows = Database::fetchAll(
                'SELECT section, ckey, cvalue FROM ' . Database::table('event_content') . ' WHERE event_id = ?',
                [self::id()]
            );
            foreach ($rows as $r) {
                self::$content[$r['section']][$r['ckey']] = $r['cvalue'];
            }
        }
        return self::$content;
    }

    public static function content($section, $key, $default = '') {
        $all = self::allContent();
        return isset($all[$section][$key]) ? $all[$section][$key] : $default;
    }

    public static function setContent($section, $key, $value) {
        $E = self::id();
        $exists = Database::fetch(
            'SELECT id FROM ' . Database::table('event_content') . ' WHERE event_id = ? AND section = ? AND ckey = ?',
            [$E, $section, $key]
        );
        if ($exists) {
            Database::update('event_content', ['cvalue' => $value, 'updated_at' => now()], 'id = :id', ['id' => $exists['id']]);
        } else {
            Database::insert('event_content', [
                'event_id' => $E, 'section' => $section, 'ckey' => $key,
                'cvalue' => $value, 'updated_at' => now(),
            ]);
        }
        self::$content = null;
    }

    public static function tickets($onlyActive = true) {
        $sql = 'SELECT t.*, (SELECT COUNT(*) FROM ' . Database::table('registrations') . ' r
                 WHERE r.ticket_type_id = t.id AND r.status != \'CANCELLED\') AS sold
                FROM ' . Database::table('ticket_types') . ' t WHERE t.event_id = ?';
        if ($onlyActive) $sql .= " AND t.status = 'ACTIVE'";
        $sql .= ' ORDER BY t.sort_order, t.id';
        return Database::fetchAll($sql, [self::id()]);
    }

    public static function schedule() {
        return Database::fetchAll(
            'SELECT * FROM ' . Database::table('schedule_items') . ' WHERE event_id = ? ORDER BY sort_order, stime',
            [self::id()]
        );
    }

    public static function speakers() {
        return Database::fetchAll(
            'SELECT * FROM ' . Database::table('speakers') . ' WHERE event_id = ? ORDER BY sort_order, id',
            [self::id()]
        );
    }

    public static function faqs() {
        return Database::fetchAll(
            'SELECT * FROM ' . Database::table('faqs') . ' WHERE event_id = ? ORDER BY sort_order, id',
            [self::id()]
        );
    }

    public static function gallery() {
        return Database::fetchAll(
            'SELECT * FROM ' . Database::table('event_gallery') . ' WHERE event_id = ? ORDER BY sort_order, id',
            [self::id()]
        );
    }
}
