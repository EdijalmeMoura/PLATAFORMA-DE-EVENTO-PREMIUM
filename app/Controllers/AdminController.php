<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Audit;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Event;
use App\Core\RateLimiter;
use App\Core\View;
use App\Services\CheckinService;
use App\Services\EmailService;
use App\Services\MessageService;
use App\Services\StatsService;

defined('APP') or exit;

/** Páginas do painel administrativo. */
class AdminController {
    private static function page($view, array $data = [], $perm = 'dashboard.view') {
        Auth::requireCan($perm);
        $data['admin_user'] = Auth::user();
        $data['event'] = Event::current();
        $data['csrf'] = Csrf::token();
        View::render('admin/' . $view, $data, 'admin/layout');
    }

    public static function login() {
        if (Auth::check()) redirect('admin');
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::requireValid();
            list($ok, $result) = Auth::attempt($_POST['email'] ?? '', $_POST['password'] ?? '');
            if ($ok) redirect('admin');
            $error = $result;
        }
        View::render('admin/login', [
            'error' => $error,
            'event' => Event::current() ?: ['name' => 'Evento Premium'],
        ]);
    }

    public static function logout() {
        Auth::logout();
        redirect('admin/login');
    }

    /** Esqueci a senha — solicita link por e-mail (anti-enumeração) */
    public static function forgot() {
        if (Auth::check()) redirect('admin');
        $sent = false;
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::requireValid();
            $email = mb_strtolower(trim($_POST['email'] ?? ''));
            $ip = client_ip();
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Informe um e-mail válido.';
            } elseif (!RateLimiter::check('pwreset:' . $ip, 5, 3600) || !RateLimiter::check('pwreset:' . $email, 3, 3600)) {
                $error = 'Muitas tentativas. Aguarde e tente novamente.';
            } else {
                RateLimiter::hit('pwreset:' . $ip, 3600);
                RateLimiter::hit('pwreset:' . $email, 3600);
                $u = Database::fetch(
                    'SELECT id, name FROM ' . Database::table('users') . ' WHERE email = ? AND active = 1',
                    [$email]
                );
                if ($u) {
                    $token = bin2hex(random_bytes(32));
                    Database::delete('password_resets', 'email = ?', [$email]);
                    Database::insert('password_resets', [
                        'email' => $email,
                        'token_hash' => hash('sha256', $token),
                        'expires_at' => date('Y-m-d H:i:s', time() + 3600),
                        'created_at' => now(),
                    ]);
                    $link = url('admin/redefinir-senha?token=' . $token . '&email=' . urlencode($email));
                    $event = Event::current();
                    list($ok, $err) = EmailService::send(
                        Event::id(),
                        $email,
                        'Redefinição de senha — ' . ($event['name'] ?? 'Painel'),
                        "Olá, " . ($u['name'] ?? '') . "!\n\n"
                        . "Recebemos um pedido de redefinição de senha do painel.\n"
                        . "Acesse o link abaixo (válido por 1 hora):\n" . $link . "\n\n"
                        . "Se não foi você, ignore este e-mail."
                    );
                    Audit::log((int) $u['id'], 'password_reset_requested', 'users', (int) $u['id'], ['sent' => $ok, 'error' => $ok ? null : $err]);
                }
                // Mensagem idêntica exista ou não (não revela e-mails cadastrados)
                $sent = true;
            }
        }
        View::render('admin/forgot', [
            'error' => $error,
            'sent' => $sent,
            'event' => Event::current() ?: ['name' => 'Evento Premium'],
        ]);
    }

    /** Redefinição via token (uso único, 1h) */
    public static function reset() {
        if (Auth::check()) redirect('admin');
        $error = '';
        $email = mb_strtolower(trim($_REQUEST['email'] ?? ''));
        $token = trim($_REQUEST['token'] ?? '');
        $row = null;
        if ($email !== '' && $token !== '') {
            $row = Database::fetch(
                'SELECT * FROM ' . Database::table('password_resets') . ' WHERE email = ? ORDER BY id DESC LIMIT 1',
                [$email]
            );
            if (!$row || !hash_equals($row['token_hash'], hash('sha256', $token)) || strtotime($row['expires_at']) < time()) {
                $row = null;
            }
        }
        if (!$row) {
            View::render('admin/reset', [
                'error' => 'Link inválido ou expirado. Solicite um novo.',
                'valid' => false,
                'event' => Event::current() ?: ['name' => 'Evento Premium'],
            ]);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::requireValid();
            $p1 = (string) ($_POST['password'] ?? '');
            $p2 = (string) ($_POST['password2'] ?? '');
            if (strlen($p1) < 8) {
                $error = 'A senha deve ter ao menos 8 caracteres.';
            } elseif ($p1 !== $p2) {
                $error = 'As senhas não conferem.';
            } else {
                $u = Database::fetch('SELECT id FROM ' . Database::table('users') . ' WHERE email = ? AND active = 1', [$email]);
                if ($u) {
                    Database::update('users', [
                        'password_hash' => password_hash($p1, PASSWORD_DEFAULT),
                        'must_change_password' => 0,
                        'updated_at' => now(),
                    ], 'id = :id', ['id' => $u['id']]);
                    Audit::log((int) $u['id'], 'password_reset_done', 'users', (int) $u['id']);
                }
                Database::delete('password_resets', 'email = ?', [$email]);
                redirect('admin/login?reset=ok');
            }
        }
        View::render('admin/reset', [
            'error' => $error,
            'valid' => true,
            'email' => $email,
            'token' => $token,
            'event' => Event::current() ?: ['name' => 'Evento Premium'],
        ]);
    }

    public static function dashboard() {
        $E = Event::id();
        $em = Database::fetch('SELECT active, host FROM ' . Database::table('email_settings') . ' WHERE event_id = ?', [$E]);
        $wa = Database::fetch('SELECT active, phone_number_id, business_number FROM ' . Database::table('whatsapp_settings') . ' WHERE event_id = ?', [$E]);
        $qt = Database::table('message_queue');
        $setup = [
            'smtp' => ($em && (int) $em['active'] === 1 && $em['host']) ? true : false,
            'whatsapp' => ($wa && (int) $wa['active'] === 1 && $wa['phone_number_id']) ? true : false,
            'queue_pending' => (int) Database::fetchColumn("SELECT COUNT(*) FROM $qt WHERE event_id = ? AND status IN ('queued','processing')", [$E]),
            'queue_failed' => (int) Database::fetchColumn("SELECT COUNT(*) FROM $qt WHERE event_id = ? AND status = 'failed'", [$E]),
        ];
        self::page('dashboard', [
            'page' => 'dashboard',
            'page_title' => 'Dashboard',
            'overview' => StatsService::overview($E),
            'recent' => StatsService::recent($E),
            'per_ticket' => StatsService::perTicket($E),
            'comms' => StatsService::comms($E),
            'checkin' => CheckinService::stats($E),
            'setup' => $setup,
        ]);
    }

    public static function registrations() {
        Auth::requireCan('registrations.view');
        self::page('inscricoes', [
            'page' => 'inscricoes',
            'page_title' => 'Inscritos',
            'tickets' => Event::tickets(false),
        ], 'registrations.view');
    }

    public static function registrationDetail($id) {
        Auth::requireCan('registrations.view');
        $E = Event::id();
        $reg = Database::fetch(
            'SELECT r.*, t.name AS ticket_name, t.price AS ticket_price, tk.qr_token
             FROM ' . Database::table('registrations') . ' r
             LEFT JOIN ' . Database::table('ticket_types') . ' t ON t.id = r.ticket_type_id
             LEFT JOIN ' . Database::table('tickets') . ' tk ON tk.registration_id = r.id
             WHERE r.id = ? AND r.event_id = ?',
            [$id, $E]
        );
        if (!$reg) { http_response_code(404); echo 'Inscrição não encontrada.'; exit; }
        $answers = Database::fetchAll(
            'SELECT a.fvalue, f.label FROM ' . Database::table('registration_answers') . ' a
             JOIN ' . Database::table('registration_fields') . ' f ON f.id = a.field_id
             WHERE a.registration_id = ?',
            [$id]
        );
        $logs = Database::fetchAll(
            'SELECT * FROM ' . Database::table('message_logs') . ' WHERE registration_id = ? ORDER BY id DESC LIMIT 30',
            [$id]
        );
        $checkins = Database::fetchAll(
            'SELECT c.*, u.name AS by_name FROM ' . Database::table('checkins') . ' c
             LEFT JOIN ' . Database::table('users') . ' u ON u.id = c.checked_in_by
             WHERE c.registration_id = ? ORDER BY c.id DESC',
            [$id]
        );
        self::page('inscricao-detalhe', [
            'page' => 'inscricoes',
            'page_title' => $reg['name'],
            'reg' => $reg,
            'answers' => $answers,
            'logs' => $logs,
            'checkins' => $checkins,
            'tickets' => Event::tickets(false),
            'templates' => Database::fetchAll('SELECT * FROM ' . Database::table('message_templates') . ' WHERE event_id = ? AND active = 1 ORDER BY name', [$E]),
        ], 'registrations.view');
    }

    public static function checkin() {
        self::page('checkin', [
            'page' => 'checkin',
            'page_title' => 'Check-in',
            'stats' => CheckinService::stats(Event::id()),
        ], 'checkin.use');
    }

    public static function comms() {
        $E = Event::id();
        // Sem cron: processa um lote ao abrir a página (máx. 1x a cada 5 min)
        try {
            if (\App\Core\RateLimiter::check('autoq:' . $E, 1, 300)) {
                \App\Core\RateLimiter::hit('autoq:' . $E, 300);
                MessageService::processQueue($E, 10);
            }
        } catch (\Throwable $e) { /* nunca quebra a página */ }
        self::page('comunicacao', [
            'page' => 'comunicacao',
            'page_title' => 'Comunicação',
            'templates' => Database::fetchAll('SELECT * FROM ' . Database::table('message_templates') . ' WHERE event_id = ? ORDER BY name', [$E]),
            'messages' => Database::fetchAll('SELECT * FROM ' . Database::table('messages') . ' WHERE event_id = ? ORDER BY id DESC LIMIT 50', [$E]),
            'automations' => Database::fetchAll('SELECT * FROM ' . Database::table('automations') . ' WHERE event_id = ? ORDER BY id', [$E]),
            'variables' => MessageService::variables(),
            'tickets' => Event::tickets(false),
            'email_cfg' => Database::fetch('SELECT * FROM ' . Database::table('email_settings') . ' WHERE event_id = ?', [$E]),
            'wa_cfg' => Database::fetch('SELECT * FROM ' . Database::table('whatsapp_settings') . ' WHERE event_id = ?', [$E]),
            'comms_stats' => StatsService::comms($E),
        ], 'comms.view');
    }

    public static function schedule() {
        self::page('programacao', [
            'page' => 'programacao',
            'page_title' => 'Programação',
            'items' => Event::schedule(),
        ], 'content.manage');
    }

    public static function speakers() {
        self::page('palestrantes', [
            'page' => 'palestrantes',
            'page_title' => 'Palestrantes & Convidados',
            'items' => Event::speakers(),
        ], 'content.manage');
    }

    public static function tickets() {
        self::page('ingressos', [
            'page' => 'ingressos',
            'page_title' => 'Ingressos',
            'items' => Database::fetchAll(
                'SELECT t.*, (SELECT COUNT(*) FROM ' . Database::table('registrations') . ' r WHERE r.ticket_type_id = t.id AND r.status != \'CANCELLED\') AS sold
                 FROM ' . Database::table('ticket_types') . ' t WHERE t.event_id = ? ORDER BY t.sort_order, t.id',
                [Event::id()]
            ),
        ], 'content.manage');
    }

    public static function formFields() {
        self::page('formulario', [
            'page' => 'formulario',
            'page_title' => 'Formulário de Inscrição',
            'items' => Database::fetchAll(
                'SELECT * FROM ' . Database::table('registration_fields') . ' WHERE event_id = ? ORDER BY
                 CASE section WHEN \'personal\' THEN 0 WHEN \'address\' THEN 1 ELSE 2 END, sort_order, id',
                [Event::id()]
            ),
        ], 'content.manage');
    }

    public static function content() {
        self::page('conteudo', [
            'page' => 'conteudo',
            'page_title' => 'Conteúdo da Landing Page',
            'content' => Event::allContent(),
            'faqs' => Event::faqs(),
        ], 'content.manage');
    }

    public static function reports() {
        $E = Event::id();
        self::page('relatorios', [
            'page' => 'relatorios',
            'page_title' => 'Relatórios',
            'overview' => StatsService::overview($E),
            'per_ticket' => StatsService::perTicket($E),
            'per_source' => StatsService::perSource($E),
            'per_city' => StatsService::perCity($E),
            'per_company' => StatsService::perCompany($E),
            'comms' => StatsService::comms($E),
        ], 'reports.view');
    }

    public static function users() {
        self::page('usuarios', [
            'page' => 'usuarios',
            'page_title' => 'Equipe & Usuários',
            'users' => Database::fetchAll(
                'SELECT u.id, u.name, u.email, u.active, u.last_login_at, u.created_at, r.name AS role_name, r.slug AS role_slug
                 FROM ' . Database::table('users') . ' u
                 JOIN ' . Database::table('roles') . ' r ON r.id = u.role_id ORDER BY u.id'
            ),
            'roles' => Database::fetchAll('SELECT * FROM ' . Database::table('roles') . ' ORDER BY id'),
        ], 'users.manage');
    }

    public static function settings() {
        self::page('configuracoes', [
            'page' => 'configuracoes',
            'page_title' => 'Configurações do Evento',
            'content' => Event::allContent(),
        ], 'event.manage');
    }

    public static function audit() {
        $logs = Database::fetchAll(
            'SELECT a.*, u.name AS user_name FROM ' . Database::table('audit_logs') . ' a
             LEFT JOIN ' . Database::table('users') . ' u ON u.id = a.user_id
             ORDER BY a.id DESC LIMIT 200'
        );
        self::page('auditoria', [
            'page' => 'auditoria',
            'page_title' => 'Auditoria & Logs',
            'logs' => $logs,
        ], 'audit.view');
    }
}
