<?php
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Event;
use App\Services\CheckinService;
use App\Services\ExportService;
use App\Services\MessageService;
use App\Services\StatsService;

defined('APP') or exit;

/**
 * API admin: dashboard, inscrições, check-in, usuários, exportação.
 * Rotas: /api/admin/{sub}
 */
class AdminApiController {
    public static function handle($sub, $method) {
        header('Content-Type: application/json; charset=utf-8');
        $parts = explode('/', trim($sub, '/'));
        $action = $parts[0] ?? '';

        switch ($action) {
            case 'dashboard-charts':
                Auth::requireCan('dashboard.view');
                self::dashboardCharts();
                break;
            case 'registrations':
                Auth::requireCan('registrations.view');
                if (isset($parts[1]) && $parts[1] !== '') {
                    self::registrationAction((int) $parts[1], $parts[2] ?? '', $method);
                } else {
                    self::registrationsList();
                }
                break;
            case 'export':
                Auth::requireCan('registrations.export');
                self::export();
                break;
            case 'checkin-lookup':
                Auth::requireCan('checkin.use');
                self::checkinLookup();
                break;
            case 'checkin-confirm':
                Auth::requireCan('checkin.use');
                Csrf::requireValid();
                self::checkinConfirm();
                break;
            case 'checkin-stats':
                Auth::requireCan('checkin.use');
                json_response(['ok' => true, 'stats' => CheckinService::stats(Event::id())]);
                break;
            case 'users':
                Auth::requireCan('users.manage');
                Csrf::requireValid();
                self::users($parts[1] ?? '', $method);
                break;
            case 'roles':
                Auth::requireCan('users.manage');
                Csrf::requireValid();
                self::roles();
                break;
            case 'notifications':
                Auth::requireCan('dashboard.view');
                self::notifications();
                break;
            case 'profile':
                Auth::requireLogin();
                Csrf::requireValid();
                self::profile();
                break;
            default:
                json_response(['ok' => false, 'error' => 'Rota inválida.'], 404);
        }
    }

    // ---------- Dashboard ----------
    private static function dashboardCharts() {
        $E = Event::id();
        json_response(['ok' => true, 'data' => [
            'overview' => StatsService::overview($E),
            'per_day' => StatsService::perDay($E),
            'per_hour' => StatsService::perHour($E),
            'per_ticket' => StatsService::perTicket($E),
            'per_source' => StatsService::perSource($E),
            'checkins_hour' => StatsService::checkinsPerHour($E),
            'comms' => StatsService::comms($E),
        ]]);
    }

    // ---------- Inscrições: lista com busca/filtros ----------
    private static function registrationsList() {
        $E = Event::id();
        $q = trim($_GET['q'] ?? '');
        $status = $_GET['status'] ?? '';
        $ticket = (int) ($_GET['ticket'] ?? 0);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = min(100, max(10, (int) ($_GET['per'] ?? 25)));
        $t = Database::table('registrations');
        $tt = Database::table('ticket_types');
        $where = ['r.event_id = :e'];
        $params = ['e' => $E];
        if ($q !== '') {
            $where[] = '(r.name LIKE :q OR r.code LIKE :q OR r.email LIKE :q OR r.whatsapp LIKE :q OR r.phone LIKE :q OR r.cpf LIKE :q OR r.company LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }
        if ($status === 'NO_CHECKIN') $where[] = "r.status IN ('CONFIRMED','PENDING')";
        elseif (in_array($status, ['PENDING', 'CONFIRMED', 'CANCELLED', 'CHECKED_IN'], true)) {
            $where[] = 'r.status = :st';
            $params['st'] = $status;
        }
        if ($ticket > 0) {
            $where[] = 'r.ticket_type_id = :tt';
            $params['tt'] = $ticket;
        }
        $w = implode(' AND ', $where);
        $total = (int) Database::fetchColumn("SELECT COUNT(*) FROM $t r WHERE $w", $params);
        $pages = max(1, (int) ceil($total / $per));
        $page = min($page, $pages);
        $offset = ($page - 1) * $per;
        $rows = Database::fetchAll(
            "SELECT r.id, r.code, r.name, r.email, r.whatsapp, r.phone, r.company, r.status, r.payment_status, r.created_at, r.checked_in_at, t.name AS ticket_name
             FROM $t r LEFT JOIN $tt t ON t.id = r.ticket_type_id
             WHERE $w ORDER BY r.id DESC LIMIT $per OFFSET $offset",
            $params
        );
        json_response(['ok' => true, 'rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per' => $per]);
    }

    // ---------- Inscrição: ações ----------
    private static function registrationAction($id, $action, $method) {
        $E = Event::id();
        $reg = Database::fetch(
            'SELECT * FROM ' . Database::table('registrations') . ' WHERE id = ? AND event_id = ?',
            [$id, $E]
        );
        if (!$reg) json_response(['ok' => false, 'error' => 'Inscrição não encontrada.'], 404);
        $input = array_merge($_POST, json_input());
        $uid = Auth::id();

        switch ($action) {
            case 'confirm':
                Auth::requireCan('registrations.edit');
                Csrf::requireValid();
                Database::update('registrations', ['status' => 'CONFIRMED', 'updated_at' => now()], 'id = :id', ['id' => $id]);
                Audit::log($uid, 'registration_confirm', 'registrations', $id);
                json_response(['ok' => true]);
                break;
            case 'cancel':
                Auth::requireCan('registrations.edit');
                Csrf::requireValid();
                $reason = mb_substr(trim($input['reason'] ?? ''), 0, 255);
                Database::update('registrations', [
                    'status' => 'CANCELLED', 'cancelled_at' => now(),
                    'cancel_reason' => $reason ?: null, 'updated_at' => now(),
                ], 'id = :id', ['id' => $id]);
                Audit::log($uid, 'registration_cancel', 'registrations', $id, ['reason' => $reason]);
                MessageService::fireAutomation($E, 'on_cancel', $id);
                json_response(['ok' => true]);
                break;
            case 'checkin':
                Auth::requireCan('checkin.use');
                Csrf::requireValid();
                $r = CheckinService::checkin($E, $reg['code'], $uid, 'manual');
                Audit::log($uid, 'checkin_manual', 'registrations', $id, ['state' => $r['state']]);
                json_response(['ok' => $r['state'] === 'VALID', 'state' => $r['state']]);
                break;
            case 'payment':
                Auth::requireCan('registrations.edit');
                Csrf::requireValid();
                $st = strtoupper(trim($input['payment_status'] ?? ''));
                if (!in_array($st, ['PENDING', 'APPROVED', 'REFUSED', 'CANCELLED', 'FREE'], true)) {
                    json_response(['ok' => false, 'error' => 'Status inválido.'], 422);
                }
                Database::update('registrations', ['payment_status' => $st, 'updated_at' => now()], 'id = :id', ['id' => $id]);
                Audit::log($uid, 'payment_update', 'registrations', $id, ['status' => $st]);
                if ($st === 'APPROVED') MessageService::fireAutomation($E, 'after_payment', $id);
                json_response(['ok' => true]);
                break;
            case 'update':
                Auth::requireCan('registrations.edit');
                Csrf::requireValid();
                $allowed = ['name', 'social_name', 'email', 'phone', 'whatsapp', 'cpf', 'birthdate', 'company', 'role', 'city', 'state', 'cep', 'street', 'number', 'complement', 'district', 'addr_city', 'addr_state', 'notes', 'ticket_type_id'];
                $data = [];
                foreach ($allowed as $col) {
                    if (array_key_exists($col, $input)) {
                        $v = trim((string) $input[$col]);
                        $data[$col] = $v === '' ? null : mb_substr($v, 0, 255);
                    }
                }
                if (isset($data['email']) && $data['email'] && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    json_response(['ok' => false, 'error' => 'E-mail inválido.'], 422);
                }
                if ($data) {
                    $data['updated_at'] = now();
                    Database::update('registrations', $data, 'id = :id', ['id' => $id]);
                    Audit::log($uid, 'registration_update', 'registrations', $id);
                }
                json_response(['ok' => true]);
                break;
            case 'delete':
                Auth::requireCan('registrations.edit');
                Csrf::requireValid();
                if ($method !== 'POST') json_response(['ok' => false], 405);
                Database::delete('registration_answers', 'registration_id = ?', [$id]);
                Database::delete('tickets', 'registration_id = ?', [$id]);
                Database::delete('checkins', 'registration_id = ?', [$id]);
                Database::delete('message_queue', 'registration_id = ?', [$id]);
                Database::delete('message_logs', 'registration_id = ?', [$id]);
                Database::delete('registrations', 'id = ?', [$id]);
                Audit::log($uid, 'registration_delete', 'registrations', $id);
                json_response(['ok' => true]);
                break;
            default:
                json_response(['ok' => false, 'error' => 'Ação inválida.'], 404);
        }
    }

    // ---------- Exportação ----------
    private static function export() {
        $E = Event::id();
        $format = $_GET['format'] ?? 'csv';
        $filters = [
            'status' => $_GET['status'] ?? '',
            'ticket_type_id' => (int) ($_GET['ticket'] ?? 0),
            'q' => trim($_GET['q'] ?? ''),
        ];
        if (!empty($_GET['ids'])) {
            $filters['ids'] = array_map('intval', explode(',', $_GET['ids']));
        }
        $rows = ExportService::rows($E, $filters);
        Audit::log(Auth::id(), 'export', 'registrations', null, ['format' => $format, 'count' => count($rows)]);
        if ($format === 'xls' || $format === 'excel') {
            ExportService::xls($rows, Event::current()['name'] ?? 'Evento');
        }
        ExportService::csv($rows);
    }

    // ---------- Check-in ----------
    private static function checkinLookup() {
        $input = trim($_GET['code'] ?? '');
        $v = CheckinService::validate(Event::id(), $input);
        json_response(['ok' => true, 'state' => $v['state'], 'registration' => self::publicReg($v['registration'])]);
    }

    private static function checkinConfirm() {
        Auth::requireCan('checkin.use');
        $input = array_merge($_POST, json_input());
        $code = trim($input['code'] ?? '');
        $r = CheckinService::checkin(Event::id(), $code, Auth::id(), ($input['method'] ?? '') === 'qr' ? 'qr' : 'manual');
        Audit::log(Auth::id(), 'checkin', 'registrations', $r['registration']['id'] ?? null, ['state' => $r['state']]);
        json_response(['ok' => $r['state'] === 'VALID', 'state' => $r['state'], 'registration' => self::publicReg($r['registration'])]);
    }

    private static function publicReg($reg) {
        if (!$reg) return null;
        return [
            'id' => $reg['id'],
            'code' => $reg['code'],
            'name' => $reg['social_name'] ?: $reg['name'],
            'email' => $reg['email'],
            'company' => $reg['company'],
            'ticket' => $reg['ticket_name'] ?? null,
            'status' => $reg['status'],
            'checked_in_at' => $reg['checked_in_at'] ?? null,
        ];
    }

    // ---------- Usuários ----------
    private static function users($action, $method) {
        $input = array_merge($_POST, json_input());
        if ($action === '' || $action === 'save') {
            $id = (int) ($input['id'] ?? 0);
            $name = trim($input['name'] ?? '');
            $email = mb_strtolower(trim($input['email'] ?? ''));
            $roleId = (int) ($input['role_id'] ?? 0);
            $active = !empty($input['active']) ? 1 : 0;
            $pass = (string) ($input['password'] ?? '');
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$roleId) {
                json_response(['ok' => false, 'error' => 'Preencha nome, e-mail válido e perfil.'], 422);
            }
            $dup = Database::fetch('SELECT id FROM ' . Database::table('users') . ' WHERE email = ? AND id != ?', [$email, $id]);
            if ($dup) json_response(['ok' => false, 'error' => 'Este e-mail já está em uso.'], 422);
            if ($id > 0) {
                if ($id === Auth::id() && !$active) json_response(['ok' => false, 'error' => 'Você não pode desativar seu próprio acesso.'], 422);
                $data = ['name' => $name, 'email' => $email, 'role_id' => $roleId, 'active' => $active, 'updated_at' => now()];
                if ($pass !== '') {
                    if (strlen($pass) < 8) json_response(['ok' => false, 'error' => 'Senha com mínimo de 8 caracteres.'], 422);
                    $data['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
                    $data['must_change_password'] = 0;
                }
                Database::update('users', $data, 'id = :id', ['id' => $id]);
                Audit::log(Auth::id(), 'user_update', 'users', $id);
            } else {
                if (strlen($pass) < 8) json_response(['ok' => false, 'error' => 'Senha com mínimo de 8 caracteres.'], 422);
                $id = Database::insert('users', [
                    'role_id' => $roleId, 'name' => $name, 'email' => $email,
                    'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
                    'active' => $active, 'must_change_password' => 0, 'created_at' => now(),
                ]);
                Audit::log(Auth::id(), 'user_create', 'users', $id);
            }
            json_response(['ok' => true, 'id' => $id]);
        }
        if ($action === 'delete' && $method === 'POST') {
            $id = (int) ($input['id'] ?? 0);
            if ($id === Auth::id()) json_response(['ok' => false, 'error' => 'Você não pode excluir seu próprio acesso.'], 422);
            Database::delete('users_permissions', 'user_id = ?', [$id]);
            Database::delete('users', 'id = ?', [$id]);
            Audit::log(Auth::id(), 'user_delete', 'users', $id);
            json_response(['ok' => true]);
        }
        json_response(['ok' => false, 'error' => 'Ação inválida.'], 404);
    }

    // ---------- Perfis: permissões ----------
    private static function roles() {
        $input = array_merge($_POST, json_input());
        $roleId = (int) ($input['role_id'] ?? 0);
        $role = Database::fetch('SELECT * FROM ' . Database::table('roles') . ' WHERE id = ?', [$roleId]);
        if (!$role) json_response(['ok' => false, 'error' => 'Perfil não encontrado.'], 404);
        if ($role['slug'] === 'admin') {
            json_response(['ok' => false, 'error' => 'O perfil Administrador sempre tem acesso total.'], 422);
        }
        $valid = array_keys(Auth::allPermissions());
        $perms = array_values(array_intersect((array) ($input['permissions'] ?? []), $valid));
        Database::update('roles', ['permissions' => json_encode($perms, JSON_UNESCAPED_UNICODE)], 'id = :id', ['id' => $roleId]);
        Audit::log(Auth::id(), 'role_permissions', 'roles', $roleId, ['permissions' => $perms]);
        json_response(['ok' => true]);
    }

    // ---------- Notificações ----------
    private static function notifications() {
        $E = Event::id();
        $items = [];
        $alerts = 0;
        $q = Database::table('message_queue');
        $failed = (int) Database::fetchColumn("SELECT COUNT(*) FROM $q WHERE event_id = ? AND status = 'failed'", [$E]);
        $queued = (int) Database::fetchColumn("SELECT COUNT(*) FROM $q WHERE event_id = ? AND status IN ('queued','processing')", [$E]);
        if ($failed > 0) {
            $alerts++;
            $items[] = ['icon' => 'alert', 'cls' => 'err', 'text' => $failed . ' mensagem(ns) falharam na fila.', 'link' => 'admin/comunicacao'];
        }
        if ($queued > 0) {
            $items[] = ['icon' => 'clock', 'cls' => '', 'text' => $queued . ' mensagem(ns) aguardando na fila.', 'link' => 'admin/comunicacao'];
        }
        $today = StatsService::overview($E);
        if (($today['today'] ?? 0) > 0) {
            $items[] = ['icon' => 'users', 'cls' => 'ok', 'text' => $today['today'] . ' nova(s) inscrição(ões) hoje.', 'link' => 'admin/inscricoes'];
        }
        $em = Database::fetch('SELECT active, host FROM ' . Database::table('email_settings') . ' WHERE event_id = ?', [$E]);
        if (!$em || !(int) $em['active'] || !$em['host']) {
            $alerts++;
            $items[] = ['icon' => 'mail', 'cls' => 'err', 'text' => 'SMTP não configurado — e-mails não serão enviados.', 'link' => 'admin/comunicacao'];
        }
        $wa = Database::fetch('SELECT active, phone_number_id FROM ' . Database::table('whatsapp_settings') . ' WHERE event_id = ?', [$E]);
        if (!$wa || !(int) $wa['active'] || !$wa['phone_number_id']) {
            $items[] = ['icon' => 'whatsapp', 'cls' => '', 'text' => 'WhatsApp desconectado (opcional).', 'link' => 'admin/comunicacao'];
        }
        if (empty($items)) {
            $items[] = ['icon' => 'check', 'cls' => 'ok', 'text' => 'Tudo certo! Nenhuma pendência.', 'link' => 'admin'];
        }
        json_response(['ok' => true, 'items' => $items, 'alerts' => $alerts, 'total' => count($items)]);
    }

    // ---------- Perfil próprio ----------
    private static function profile() {
        $input = array_merge($_POST, json_input());
        $u = Auth::user();
        $name = trim($input['name'] ?? $u['name']);
        if ($name === '') json_response(['ok' => false, 'error' => 'Nome inválido.'], 422);
        $data = ['name' => mb_substr($name, 0, 120), 'updated_at' => now()];
        if (!empty($input['new_password'])) {
            if (!password_verify($input['current_password'] ?? '', self::currentHash($u['id']))) {
                json_response(['ok' => false, 'error' => 'Senha atual incorreta.'], 422);
            }
            if (strlen($input['new_password']) < 8) json_response(['ok' => false, 'error' => 'Nova senha com mínimo de 8 caracteres.'], 422);
            $data['password_hash'] = password_hash($input['new_password'], PASSWORD_DEFAULT);
            $data['must_change_password'] = 0;
        }
        Database::update('users', $data, 'id = :id', ['id' => $u['id']]);
        Audit::log($u['id'], 'profile_update', 'users', $u['id']);
        json_response(['ok' => true]);
    }

    private static function currentHash($id) {
        return (string) Database::fetchColumn('SELECT password_hash FROM ' . Database::table('users') . ' WHERE id = ?', [(int) $id]);
    }
}
