<?php
namespace App\Core;

defined('APP') or exit;

/**
 * Autenticação, sessão e permissões.
 */
class Auth {
    public static function user() {
        if (empty($_SESSION['user_id'])) return null;
        $u = Database::fetch(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.permissions
             FROM ' . Database::table('users') . ' u
             JOIN ' . Database::table('roles') . ' r ON r.id = u.role_id
             WHERE u.id = ? AND u.active = 1',
            [(int) $_SESSION['user_id']]
        );
        if (!$u) {
            unset($_SESSION['user_id']);
            return null;
        }
        unset($u['password_hash']);
        return $u;
    }

    public static function check() {
        return self::user() !== null;
    }

    public static function id() {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    /** Permissões do usuário logado (papel + extras individuais) */
    public static function permissions() {
        $u = self::user();
        if (!$u) return [];
        $perms = json_decode($u['permissions'] ?? '[]', true);
        if (!is_array($perms)) $perms = [];
        $extras = Database::fetchAll(
            'SELECT permission FROM ' . Database::table('users_permissions') . ' WHERE user_id = ?',
            [(int) $u['id']]
        );
        foreach ($extras as $x) $perms[] = $x['permission'];
        return array_unique($perms);
    }

    public static function can($permission) {
        $perms = self::permissions();
        if (in_array('*', $perms, true)) return true;
        return in_array($permission, $perms, true);
    }

    public static function isAdmin() {
        $u = self::user();
        return $u && $u['role_slug'] === 'admin';
    }

    /** Catálogo de permissões (chave => rótulo). */
    public static function allPermissions() {
        return [
            'dashboard.view' => 'Ver dashboard',
            'registrations.view' => 'Ver inscritos',
            'registrations.edit' => 'Editar / cancelar inscritos',
            'registrations.export' => 'Exportar inscritos',
            'checkin.use' => 'Realizar check-in',
            'comms.view' => 'Ver comunicação',
            'comms.send' => 'Enviar mensagens e configurar canais',
            'content.manage' => 'Gerenciar conteúdo do site',
            'event.manage' => 'Configurações do evento',
            'reports.view' => 'Ver relatórios',
            'users.manage' => 'Gerenciar usuários e perfis',
            'audit.view' => 'Ver auditoria',
        ];
    }

    /** Tenta login. Retorna [ok, message|user] */
    public static function attempt($email, $password) {
        $email = mb_strtolower(trim($email));
        $key = 'login:' . client_ip() . ':' . $email;
        if (!RateLimiter::check($key, 8, 600)) {
            return [false, 'Muitas tentativas. Aguarde alguns minutos e tente novamente.'];
        }
        $u = Database::fetch(
            'SELECT u.*, r.slug AS role_slug FROM ' . Database::table('users') . ' u
             JOIN ' . Database::table('roles') . ' r ON r.id = u.role_id
             WHERE u.email = ?',
            [$email]
        );
        if (!$u || (int) $u['active'] !== 1 || !password_verify($password, $u['password_hash'])) {
            RateLimiter::hit($key, 600);
            Audit::log(null, 'login_failed', 'users', null, ['email' => $email]);
            return [false, 'E-mail ou senha inválidos.'];
        }
        // Rehash se necessário
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            Database::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = :id', ['id' => $u['id']]);
        }
        RateLimiter::clear($key);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $u['id'];
        $_SESSION['LAST_ACTIVITY'] = time();
        Database::update('users', ['last_login_at' => now()], 'id = :id', ['id' => $u['id']]);
        Audit::log((int) $u['id'], 'login', 'users', (int) $u['id']);
        unset($u['password_hash']);
        return [true, $u];
    }

    public static function logout() {
        $uid = self::id();
        if ($uid) Audit::log($uid, 'logout', 'users', $uid);
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
    }

    /** Exige login (redireciona ou 401 JSON) */
    public static function requireLogin() {
        if (!self::check()) {
            if (is_json_request()) json_response(['ok' => false, 'error' => 'Não autenticado.'], 401);
            redirect('admin/login');
        }
    }

    /** Exige permissão (403) */
    public static function requireCan($permission) {
        self::requireLogin();
        if (!self::can($permission)) {
            if (is_json_request()) json_response(['ok' => false, 'error' => 'Sem permissão.'], 403);
            http_response_code(403);
            echo 'Acesso negado.';
            exit;
        }
    }
}
