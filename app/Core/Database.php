<?php
namespace App\Core;

defined('APP') or exit;

/**
 * Camada de banco de dados (PDO) com suporte a SQLite e MySQL
 * + Migrations idempotentes (CREATE TABLE IF NOT EXISTS).
 */
class Database {
    /** @var \PDO|null */
    private static $pdo = null;
    /** @var string */
    private static $driver = 'sqlite';

    public static function driver() {
        return self::$driver;
    }

    /** Conecta (singleton) */
    public static function pdo() {
        if (self::$pdo instanceof \PDO) return self::$pdo;
        $driver = DB_DRIVER === 'mysql' ? 'mysql' : 'sqlite';
        self::$driver = $driver;
        try {
            if ($driver === 'mysql') {
                $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $pdo = new \PDO($dsn, DB_USER, DB_PASS, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
                // Alinha CURDATE()/NOW() com APP_TIMEZONE (offset; zonas nomeadas
                // raramente existem em hospedagem compartilhada)
                $pdo->exec("SET time_zone = '" . date('P') . "'");
            } else {
                $path = DB_PATH;
                if ($path[0] !== '/' && !preg_match('#^[A-Za-z]:\\\\#', $path)) {
                    $path = APP_ROOT . '/' . ltrim($path, '/');
                }
                $dir = dirname($path);
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                $pdo = new \PDO('sqlite:' . $path, null, null, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ]);
                $pdo->exec('PRAGMA journal_mode = WAL');
                $pdo->exec('PRAGMA foreign_keys = ON');
            }
        } catch (\PDOException $ex) {
            if (APP_DEBUG) throw $ex;
            http_response_code(500);
            echo 'Erro de conexão com o banco de dados. Verifique o arquivo .env ou execute install.php.';
            exit;
        }
        self::$pdo = $pdo;
        return $pdo;
    }

    public static function table($name) {
        return DB_PREFIX . $name;
    }

    // ---------- Atalhos ----------
    public static function query($sql, $params = []) {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    public static function fetch($sql, $params = []) {
        return self::query($sql, $params)->fetch();
    }

    public static function fetchAll($sql, $params = []) {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchColumn($sql, $params = []) {
        return self::query($sql, $params)->fetchColumn();
    }

    public static function insert($table, array $data) {
        $table = self::table($table);
        $cols = array_keys($data);
        $ph = array_map(function ($c) { return ':' . $c; }, $cols);
        $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', $ph) . ')';
        self::query($sql, $data);
        return (int) self::pdo()->lastInsertId();
    }

    public static function update($table, array $data, $where, array $params = []) {
        $table = self::table($table);
        $sets = [];
        $bind = [];
        foreach ($data as $c => $v) {
            $sets[] = $c . ' = :set_' . $c;
            $bind['set_' . $c] = $v;
        }
        $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
        $st = self::pdo()->prepare($sql);
        $st->execute(array_merge($bind, $params));
        return $st->rowCount();
    }

    public static function delete($table, $where, array $params = []) {
        $table = self::table($table);
        $st = self::pdo()->prepare('DELETE FROM ' . $table . ' WHERE ' . $where);
        $st->execute($params);
        return $st->rowCount();
    }

    // ---------- Schema ----------
    /**
     * Definição neutra das tabelas.
     * Tipos: pk, string:N, text, int, decimal, bool, date, datetime
     * Opções por coluna: null, default, unique
     */
    public static function schema() {
        $T = [
            'settings' => [
                'cols' => [
                    ['k', 'string:100', ['pk' => true]],
                    ['v', 'text', ['null' => true]],
                ],
            ],
            'roles' => [
                'cols' => [
                    ['id', 'pk'],
                    ['slug', 'string:40', ['unique' => true]],
                    ['name', 'string:80'],
                    ['permissions', 'text', ['null' => true]],
                    ['created_at', 'datetime'],
                ],
            ],
            'users' => [
                'cols' => [
                    ['id', 'pk'],
                    ['role_id', 'int'],
                    ['name', 'string:120'],
                    ['email', 'string:160', ['unique' => true]],
                    ['password_hash', 'string:255'],
                    ['active', 'bool', ['default' => 1]],
                    ['must_change_password', 'bool', ['default' => 0]],
                    ['last_login_at', 'datetime', ['null' => true]],
                    ['created_at', 'datetime'],
                    ['updated_at', 'datetime', ['null' => true]],
                ],
                'indexes' => [['role_id']],
            ],
            'events' => [
                'cols' => [
                    ['id', 'pk'],
                    ['name', 'string:160'],
                    ['tagline', 'string:255', ['null' => true]],
                    ['description', 'text', ['null' => true]],
                    ['date_start', 'date', ['null' => true]],
                    ['date_end', 'date', ['null' => true]],
                    ['time_text', 'string:80', ['null' => true]],
                    ['countdown_target', 'datetime', ['null' => true]],
                    ['venue_name', 'string:160', ['null' => true]],
                    ['address', 'string:255', ['null' => true]],
                    ['city', 'string:100', ['null' => true]],
                    ['state', 'string:10', ['null' => true]],
                    ['map_url', 'string:500', ['null' => true]],
                    ['maps_embed', 'text', ['null' => true]],
                    ['logo', 'string:255', ['null' => true]],
                    ['hero_image', 'string:255', ['null' => true]],
                    ['about_image', 'string:255', ['null' => true]],
                    ['venue_image', 'string:255', ['null' => true]],
                    ['contact_email', 'string:160', ['null' => true]],
                    ['contact_whatsapp', 'string:40', ['null' => true]],
                    ['contact_phone', 'string:40', ['null' => true]],
                    ['instagram', 'string:160', ['null' => true]],
                    ['linkedin', 'string:160', ['null' => true]],
                    ['website', 'string:160', ['null' => true]],
                    ['status', 'string:20', ['default' => 'ACTIVE']],
                    ['created_at', 'datetime'],
                    ['updated_at', 'datetime', ['null' => true]],
                ],
            ],
            'event_content' => [
                'cols' => [
                    ['id', 'pk'],
                    ['event_id', 'int'],
                    ['section', 'string:60'],
                    ['ckey', 'string:80'],
                    ['cvalue', 'text', ['null' => true]],
                    ['updated_at', 'datetime', ['null' => true]],
                ],
                'indexes' => [['event_id'], ['event_id', 'section']],
            ],
            'ticket_types' => [
                'cols' => [
                    ['id', 'pk'],
                    ['event_id', 'int'],
                    ['name', 'string:80'],
                    ['description', 'text', ['null' => true]],
                    ['price', 'decimal', ['default' => '0']],
                    ['quantity', 'int', ['default' => 0]],
                    ['benefits', 'text', ['null' => true]],
                    ['sale_start', 'datetime', ['null' => true]],
                    ['sale_end', 'datetime', ['null' => true]],
                    ['status', 'string:20', ['default' => 'ACTIVE']],
                    ['sort_order', 'int', ['default' => 0]],
                    ['created_at', 'datetime'],
                ],
                'indexes' => [['event_id']],
            ],
            'schedule_items' => [
                'cols' => [
                    ['id', 'pk'],
                    ['event_id', 'int'],
                    ['stime', 'string:30'],
                    ['title', 'string:160'],
                    ['description', 'text', ['null' => true]],
                    ['speaker', 'string:160', ['null' => true]],
                    ['slocation', 'string:160', ['null' => true]],
                    ['image', 'string:255', ['null' => true]],
                    ['sort_order', 'int', ['default' => 0]],
                ],
                'indexes' => [['event_id']],
            ],
            'speakers' => [
                'cols' => [
                    ['id', 'pk'],
                    ['event_id', 'int'],
                    ['name', 'string:120'],
                    ['role', 'string:120', ['null' => true]],
                    ['company', 'string:120', ['null' => true]],
                    ['bio', 'text', ['null' => true]],
                    ['photo', 'string:255', ['null' => true]],
                    ['instagram', 'string:160', ['null' => true]],
                    ['linkedin', 'string:160', ['null' => true]],
                    ['sort_order', 'int', ['default' => 0]],
                ],
                'indexes' => [['event_id']],
            ],
            'faqs' => [
                'cols' => [
                    ['id', 'pk'],
                    ['event_id', 'int'],
                    ['question', 'string:255'],
                    ['answer', 'text'],
                    ['sort_order', 'int', ['default' => 0]],
                ],
                'indexes' => [['event_id']],
            ],
            'registration_fields' => [
                'cols' => [
                    ['id', 'pk'],
                    ['event_id', 'int'],
                    ['fname', 'string:60'],
                    ['label', 'string:120'],
                    ['ftype', 'string:20', ['default' => 'text']],
                    ['options', 'text', ['null' => true]],
                    ['placeholder', 'string:160', ['null' => true]],
                    ['help', 'string:255', ['null' => true]],
                    ['required', 'bool', ['default' => 0]],
                    ['active', 'bool', ['default' => 1]],
                    ['section', 'string:20', ['default' => 'personal']],
                    ['map_column', 'string:40', ['null' => true]],
                    ['sort_order', 'int', ['default' => 0]],
                ],
                'indexes' => [['event_id']],
            ],
            'registrations' => [
                'cols' => [
                    ['id', 'pk'],
                    ['event_id', 'int'],
                    ['ticket_type_id', 'int', ['null' => true]],
                    ['code', 'string:32', ['unique' => true]],
                    ['name', 'string:160'],
                    ['social_name', 'string:160', ['null' => true]],
                    ['email', 'string:160'],
                    ['phone', 'string:40', ['null' => true]],
                    ['whatsapp', 'string:40', ['null' => true]],
                    ['cpf', 'string:20', ['null' => true]],
                    ['birthdate', 'date', ['null' => true]],
                    ['company', 'string:160', ['null' => true]],
                    ['role', 'string:120', ['null' => true]],
                    ['city', 'string:100', ['null' => true]],
                    ['state', 'string:10', ['null' => true]],
                    ['cep', 'string:12', ['null' => true]],
                    ['street', 'string:160', ['null' => true]],
                    ['number', 'string:20', ['null' => true]],
                    ['complement', 'string:80', ['null' => true]],
                    ['district', 'string:100', ['null' => true]],
                    ['addr_city', 'string:100', ['null' => true]],
                    ['addr_state', 'string:10', ['null' => true]],
                    ['status', 'string:20', ['default' => 'CONFIRMED']],
                    ['payment_status', 'string:20', ['default' => 'FREE']],
                    ['consents', 'text', ['null' => true]],
                    ['source', 'string:60', ['null' => true]],
                    ['ip', 'string:45', ['null' => true]],
                    ['user_agent', 'string:255', ['null' => true]],
                    ['checked_in_at', 'datetime', ['null' => true]],
                    ['checked_in_by', 'int', ['null' => true]],
                    ['cancelled_at', 'datetime', ['null' => true]],
                    ['cancel_reason', 'string:255', ['null' => true]],
                    ['notes', 'text', ['null' => true]],
                    ['created_at', 'datetime'],
                    ['updated_at', 'datetime', ['null' => true]],
                ],
                'indexes' => [['event_id'], ['event_id', 'status'], ['event_id', 'email'], ['ticket_type_id'], ['cpf']],
            ],
            'registration_answers' => [
                'cols' => [
                    ['id', 'pk'],
                    ['registration_id', 'int'],
                    ['field_id', 'int'],
                    ['fvalue', 'text', ['null' => true]],
                ],
                'indexes' => [['registration_id'], ['field_id']],
            ],
            'tickets' => [
                'cols' => [
                    ['id', 'pk'],
                    ['registration_id', 'int', ['unique' => true]],
                    ['code', 'string:32', ['unique' => true]],
                    ['qr_token', 'string:64', ['unique' => true]],
                    ['created_at', 'datetime'],
                ],
            ],
            'checkins' => [
                'cols' => [
                    ['id', 'pk'],
                    ['registration_id', 'int'],
                    ['checked_in_at', 'datetime'],
                    ['checked_in_by', 'int', ['null' => true]],
                    ['method', 'string:10', ['default' => 'manual']],
                    ['created_at', 'datetime'],
                ],
                'indexes' => [['registration_id']],
            ],
            'message_templates' => [
                'cols' => [
                    ['id', 'pk'],
                    ['event_id', 'int'],
                    ['tkey', 'string:60'],
                    ['name', 'string:120'],
                    ['channel', 'string:20', ['default' => 'both']],
                    ['subject', 'string:160', ['null' => true]],
                    ['body', 'text'],
                    ['active', 'bool', ['default' => 1]],
                    ['created_at', 'datetime'],
                    ['updated_at', 'datetime', ['null' => true]],
                ],
                'indexes' => [['event_id']],
            ],
            'messages' => [
                'cols' => [
                    ['id', 'pk'],
                    ['event_id', 'int'],
                    ['name', 'string:120'],
                    ['subject', 'string:160', ['null' => true]],
                    ['channel', 'string:20', ['default' => 'email']],
                    ['body', 'text'],
                    ['status', 'string:20', ['default' => 'draft']],
                    ['created_by', 'int', ['null' => true]],
                    ['created_at', 'datetime'],
                    ['updated_at', 'datetime', ['null' => true]],
                ],
                'indexes' => [['event_id']],
            ],
            'automations' => [
                'cols' => [
                    ['id', 'pk'],
                    ['event_id', 'int'],
                    ['trigger', 'string:30'],
                    ['name', 'string:120'],
                    ['channel', 'string:20', ['default' => 'email']],
                    ['subject', 'string:160', ['null' => true]],
                    ['body', 'text'],
                    ['days_before', 'int', ['default' => 0]],
                    ['active', 'bool', ['default' => 1]],
                    ['created_at', 'datetime'],
                    ['updated_at', 'datetime', ['null' => true]],
                ],
                'indexes' => [['event_id']],
            ],
            'message_queue' => [
                'cols' => [
                    ['id', 'pk'],
                    ['event_id', 'int'],
                    ['registration_id', 'int', ['null' => true]],
                    ['message_id', 'int', ['null' => true]],
                    ['automation', 'string:30', ['null' => true]],
                    ['channel', 'string:20'],
                    ['to_address', 'string:200'],
                    ['subject', 'string:160', ['null' => true]],
                    ['body', 'text'],
                    ['status', 'string:20', ['default' => 'queued']],
                    ['attempts', 'int', ['default' => 0]],
                    ['scheduled_at', 'datetime', ['null' => true]],
                    ['sent_at', 'datetime', ['null' => true]],
                    ['error', 'string:500', ['null' => true]],
                    ['created_at', 'datetime'],
                ],
                'indexes' => [['event_id', 'status'], ['registration_id'], ['status', 'scheduled_at']],
            ],
            'message_logs' => [
                'cols' => [
                    ['id', 'pk'],
                    ['event_id', 'int'],
                    ['queue_id', 'int', ['null' => true]],
                    ['registration_id', 'int', ['null' => true]],
                    ['automation', 'string:30', ['null' => true]],
                    ['channel', 'string:20'],
                    ['to_address', 'string:200'],
                    ['subject', 'string:160', ['null' => true]],
                    ['body_excerpt', 'string:500', ['null' => true]],
                    ['status', 'string:20'],
                    ['error', 'string:500', ['null' => true]],
                    ['opened_at', 'datetime', ['null' => true]],
                    ['created_at', 'datetime'],
                ],
                'indexes' => [['event_id'], ['registration_id'], ['event_id', 'automation'], ['created_at']],
            ],
            'email_settings' => [
                'cols' => [
                    ['id', 'pk'],
                    ['event_id', 'int', ['unique' => true]],
                    ['host', 'string:160', ['null' => true]],
                    ['port', 'int', ['default' => 587]],
                    ['username', 'string:160', ['null' => true]],
                    ['password_enc', 'string:500', ['null' => true]],
                    ['encryption', 'string:10', ['default' => 'tls']],
                    ['from_email', 'string:160', ['null' => true]],
                    ['from_name', 'string:120', ['null' => true]],
                    ['active', 'bool', ['default' => 0]],
                    ['updated_at', 'datetime', ['null' => true]],
                ],
            ],
            'whatsapp_settings' => [
                'cols' => [
                    ['id', 'pk'],
                    ['event_id', 'int', ['unique' => true]],
                    ['provider', 'string:40', ['default' => 'meta_cloud']],
                    ['phone_number_id', 'string:80', ['null' => true]],
                    ['access_token_enc', 'string:1000', ['null' => true]],
                    ['business_number', 'string:40', ['null' => true]],
                    ['template_lang', 'string:10', ['default' => 'pt_BR']],
                    ['active', 'bool', ['default' => 0]],
                    ['last_status', 'string:255', ['null' => true]],
                    ['updated_at', 'datetime', ['null' => true]],
                ],
            ],
            'users_permissions' => [
                'cols' => [
                    ['id', 'pk'],
                    ['user_id', 'int'],
                    ['permission', 'string:60'],
                ],
                'indexes' => [['user_id']],
            ],
            'audit_logs' => [
                'cols' => [
                    ['id', 'pk'],
                    ['user_id', 'int', ['null' => true]],
                    ['action', 'string:80'],
                    ['entity', 'string:60', ['null' => true]],
                    ['entity_id', 'int', ['null' => true]],
                    ['details', 'text', ['null' => true]],
                    ['ip', 'string:45', ['null' => true]],
                    ['created_at', 'datetime'],
                ],
                'indexes' => [['user_id'], ['entity', 'entity_id'], ['created_at']],
            ],
            'password_resets' => [
                'cols' => [
                    ['id', 'pk'],
                    ['email', 'string:160'],
                    ['token_hash', 'string:64'],
                    ['expires_at', 'datetime'],
                    ['created_at', 'datetime'],
                ],
                'indexes' => [['email'], ['token_hash']],
            ],
            'event_gallery' => [
                'cols' => [
                    ['id', 'pk'],
                    ['event_id', 'int'],
                    ['image', 'string:255'],
                    ['caption', 'string:160', ['null' => true]],
                    ['sort_order', 'int', ['default' => 0]],
                ],
                'indexes' => [['event_id']],
            ],
            'testimonials' => [
                'cols' => [
                    ['id', 'pk'],
                    ['event_id', 'int'],
                    ['name', 'string:120'],
                    ['role', 'string:120', ['null' => true]],
                    ['company', 'string:120', ['null' => true]],
                    ['photo', 'string:255', ['null' => true]],
                    ['text', 'text'],
                    ['sort_order', 'int', ['default' => 0]],
                ],
                'indexes' => [['event_id']],
            ],
            'rate_limits' => [
                'cols' => [
                    ['id', 'pk'],
                    ['rkey', 'string:160', ['unique' => true]],
                    ['hits', 'int', ['default' => 0]],
                    ['window_start', 'datetime'],
                ],
            ],
        ];
        return $T;
    }

    /** Traduz tipo neutro → DDL do driver */
    private static function colSql($col, $driver) {
        $name = $col[0];
        $type = $col[1];
        $opt = isset($col[2]) ? $col[2] : [];
        if ($type === 'pk') {
            return $driver === 'mysql'
                ? "`$name` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY"
                : "\"$name\" INTEGER PRIMARY KEY AUTOINCREMENT";
        }
        $q = $driver === 'mysql' ? "`$name`" : "\"$name\"";
        if (strpos($type, 'string:') === 0) {
            $len = (int) substr($type, 7);
            $sql = "$q VARCHAR($len)";
        } elseif ($type === 'text') {
            $sql = "$q TEXT";
        } elseif ($type === 'int') {
            $sql = $driver === 'mysql' ? "$q INT" : "$q INTEGER";
        } elseif ($type === 'decimal') {
            $sql = "$q DECIMAL(10,2)";
        } elseif ($type === 'bool') {
            $sql = $driver === 'mysql' ? "$q TINYINT(1)" : "$q INTEGER";
        } elseif ($type === 'date') {
            $sql = "$q DATE";
        } elseif ($type === 'datetime') {
            $sql = "$q DATETIME";
        } else {
            $sql = "$q TEXT";
        }
        if (!empty($opt['pk'])) {
            $sql .= ' PRIMARY KEY';
            return $sql;
        }
        $sql .= !empty($opt['null']) ? ' NULL' : ' NOT NULL';
        if (array_key_exists('default', $opt) && $opt['default'] !== null) {
            $d = $opt['default'];
            if (is_numeric($d) && $type !== 'text' && strpos($type, 'string') !== 0) $sql .= ' DEFAULT ' . $d;
            else $sql .= " DEFAULT '" . str_replace("'", "''", (string) $d) . "'";
        }
        if (!empty($opt['unique']) && $driver === 'mysql' && $type !== 'text') {
            // unique tratado via índice separado (compatível com ambos)
        }
        return $sql;
    }

    /** Cria/atualiza estrutura */
    public static function migrate() {
        $pdo = self::pdo();
        $driver = self::$driver;
        $prefix = DB_PREFIX;
        foreach (self::schema() as $name => $def) {
            $table = $prefix . $name;
            $cols = [];
            foreach ($def['cols'] as $c) $cols[] = self::colSql($c, $driver);
            $extra = $driver === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
            $q = $driver === 'mysql' ? "`$table`" : "\"$table\"";
            $pdo->exec("CREATE TABLE IF NOT EXISTS $q (" . implode(', ', $cols) . ")$extra");
            // Índices únicos de coluna
            foreach ($def['cols'] as $c) {
                if (!empty($c[2]['unique'])) {
                    $idx = "uq_{$table}_{$c[0]}";
                    try {
                        if ($driver === 'mysql') $pdo->exec("CREATE UNIQUE INDEX `$idx` ON `$table` (`{$c[0]}`)");
                        else $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS \"$idx\" ON \"$table\" (\"{$c[0]}\")");
                    } catch (\Exception $e) { /* índice já existe */ }
                }
            }
            // Índices compostos
            if (!empty($def['indexes'])) {
                foreach ($def['indexes'] as $i => $colsIdx) {
                    $idx = "ix_{$table}_" . $i;
                    $list = $driver === 'mysql'
                        ? implode(',', array_map(function ($c) { return "`$c`"; }, $colsIdx))
                        : implode(',', array_map(function ($c) { return "\"$c\""; }, $colsIdx));
                    try {
                        if ($driver === 'mysql') $pdo->exec("CREATE INDEX `$idx` ON `$table` ($list)");
                        else $pdo->exec("CREATE INDEX IF NOT EXISTS \"$idx\" ON \"$table\" ($list)");
                    } catch (\Exception $e) { /* índice já existe */ }
                }
            }
        }
        // Versão do schema
        self::setSetting('schema_version', '1');
        return true;
    }

    // ---------- Settings KV ----------
    public static function getSetting($key, $default = null) {
        try {
            $v = self::fetchColumn('SELECT v FROM ' . self::table('settings') . ' WHERE k = ?', [$key]);
            return $v === false ? $default : $v;
        } catch (\Exception $e) {
            return $default;
        }
    }

    public static function setSetting($key, $value) {
        $t = self::table('settings');
        try {
            $exists = self::fetchColumn("SELECT k FROM $t WHERE k = ?", [$key]);
            if ($exists === false) {
                self::query("INSERT INTO $t (k, v) VALUES (?, ?)", [$key, $value]);
            } else {
                self::query("UPDATE $t SET v = ? WHERE k = ?", [$value, $key]);
            }
        } catch (\Exception $e) { /* tabela ainda não existe */ }
    }

    public static function isInstalled() {
        if (is_file(APP_ROOT . '/storage/installed.lock')) return true;
        $v = self::getSetting('installed', null);
        return $v === '1';
    }
}
