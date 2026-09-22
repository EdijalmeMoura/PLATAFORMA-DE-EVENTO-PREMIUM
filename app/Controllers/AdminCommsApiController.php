<?php
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Crypto;
use App\Core\Database;
use App\Core\Event;
use App\Services\EmailService;
use App\Services\MessageService;
use App\Services\WhatsAppService;

defined('APP') or exit;

/** API admin: mensagens, templates, automações, fila, SMTP, WhatsApp. */
class AdminCommsApiController {
    public static function handle($sub, $method) {
        header('Content-Type: application/json; charset=utf-8');
        Auth::requireCan('comms.view');
        if ($method === 'POST') Csrf::requireValid();
        $parts = explode('/', trim($sub, '/'));
        $action = $parts[0] ?? '';
        switch ($action) {
            case 'messages': self::messages($parts[1] ?? ''); break;
            case 'templates': self::templates($parts[1] ?? ''); break;
            case 'automations': self::automations($parts[1] ?? ''); break;
            case 'bulk-count': self::bulkCount(); break;
            case 'bulk-send': self::bulkSend(); break;
            case 'single-send': self::singleSend(); break;
            case 'preview': self::preview(); break;
            case 'queue': self::queue($parts[1] ?? ''); break;
            case 'logs': self::logs(); break;
            case 'email-save': self::emailSave(); break;
            case 'email-test': self::emailTest(); break;
            case 'wa-save': self::waSave(); break;
            case 'wa-test': self::waTest(); break;
            default: json_response(['ok' => false, 'error' => 'Rota inválida.'], 404);
        }
    }

    private static function needSend() {
        Auth::requireCan('comms.send');
    }

    // ---------- Mensagens salvas ----------
    private static function messages($op) {
        $E = Event::id();
        $input = array_merge($_POST, json_input());
        if ($op === 'save') {
            self::needSend();
            $id = (int) ($input['id'] ?? 0);
            $name = trim($input['name'] ?? '');
            $body = trim($input['body'] ?? '');
            if ($name === '' || $body === '') json_response(['ok' => false, 'error' => 'Nome e mensagem são obrigatórios.'], 422);
            $data = [
                'name' => mb_substr($name, 0, 120),
                'subject' => mb_substr(trim($input['subject'] ?? ''), 0, 160) ?: null,
                'channel' => in_array($input['channel'] ?? '', ['email', 'whatsapp', 'both'], true) ? $input['channel'] : 'email',
                'body' => $body,
                'status' => 'draft',
                'updated_at' => now(),
            ];
            if ($id > 0) {
                Database::update('messages', $data, 'id = :id AND event_id = :e', ['id' => $id, 'e' => $E]);
            } else {
                $data['event_id'] = $E;
                $data['created_by'] = Auth::id();
                $data['created_at'] = now();
                $id = Database::insert('messages', $data);
            }
            Audit::log(Auth::id(), 'message_save', 'messages', $id);
            json_response(['ok' => true, 'id' => $id]);
        }
        if ($op === 'delete') {
            self::needSend();
            Database::delete('messages', 'id = ? AND event_id = ?', [(int) ($input['id'] ?? 0), $E]);
            json_response(['ok' => true]);
        }
        if ($op === 'get') {
            $row = Database::fetch('SELECT * FROM ' . Database::table('messages') . ' WHERE id = ? AND event_id = ?', [(int) ($_GET['id'] ?? 0), $E]);
            json_response(['ok' => (bool) $row, 'row' => $row]);
        }
        json_response(['ok' => false], 404);
    }

    // ---------- Templates ----------
    private static function templates($op) {
        $E = Event::id();
        $input = array_merge($_POST, json_input());
        if ($op === 'save') {
            self::needSend();
            $id = (int) ($input['id'] ?? 0);
            $name = trim($input['name'] ?? '');
            $body = trim($input['body'] ?? '');
            if ($name === '' || $body === '') json_response(['ok' => false, 'error' => 'Nome e mensagem são obrigatórios.'], 422);
            $data = [
                'name' => mb_substr($name, 0, 120),
                'subject' => mb_substr(trim($input['subject'] ?? ''), 0, 160) ?: null,
                'channel' => in_array($input['channel'] ?? '', ['email', 'whatsapp', 'both'], true) ? $input['channel'] : 'both',
                'body' => $body,
                'active' => isset($input['active']) ? (!empty($input['active']) ? 1 : 0) : 1,
                'updated_at' => now(),
            ];
            if ($id > 0) {
                Database::update('message_templates', $data, 'id = :id AND event_id = :e', ['id' => $id, 'e' => $E]);
            } else {
                $data['event_id'] = $E;
                $data['tkey'] = 'custom_' . substr(bin2hex(random_bytes(4)), 0, 8);
                $data['created_at'] = now();
                $id = Database::insert('message_templates', $data);
            }
            Audit::log(Auth::id(), 'template_save', 'message_templates', $id);
            json_response(['ok' => true, 'id' => $id]);
        }
        if ($op === 'delete') {
            self::needSend();
            Database::delete('message_templates', 'id = ? AND event_id = ?', [(int) ($input['id'] ?? 0), $E]);
            json_response(['ok' => true]);
        }
        if ($op === 'get') {
            $row = Database::fetch('SELECT * FROM ' . Database::table('message_templates') . ' WHERE id = ? AND event_id = ?', [(int) ($_GET['id'] ?? 0), $E]);
            json_response(['ok' => (bool) $row, 'row' => $row]);
        }
        json_response(['ok' => false], 404);
    }

    // ---------- Automações ----------
    private static function automations($op) {
        $E = Event::id();
        $input = array_merge($_POST, json_input());
        if ($op === 'save') {
            self::needSend();
            $id = (int) ($input['id'] ?? 0);
            $body = trim($input['body'] ?? '');
            if ($body === '') json_response(['ok' => false, 'error' => 'Mensagem é obrigatória.'], 422);
            $data = [
                'name' => mb_substr(trim($input['name'] ?? 'Automação'), 0, 120),
                'channel' => in_array($input['channel'] ?? '', ['email', 'whatsapp', 'both'], true) ? $input['channel'] : 'both',
                'subject' => mb_substr(trim($input['subject'] ?? ''), 0, 160) ?: null,
                'body' => $body,
                'active' => !empty($input['active']) ? 1 : 0,
                'updated_at' => now(),
            ];
            if ($id > 0) {
                Database::update('automations', $data, 'id = :id AND event_id = :e', ['id' => $id, 'e' => $E]);
            } else {
                $data['event_id'] = $E;
                $data['trigger'] = 'custom_' . substr(bin2hex(random_bytes(4)), 0, 8);
                $data['created_at'] = now();
                $id = Database::insert('automations', $data);
            }
            Audit::log(Auth::id(), 'automation_save', 'automations', $id);
            json_response(['ok' => true, 'id' => $id]);
        }
        if ($op === 'toggle') {
            self::needSend();
            $id = (int) ($input['id'] ?? 0);
            $row = Database::fetch('SELECT active FROM ' . Database::table('automations') . ' WHERE id = ? AND event_id = ?', [$id, $E]);
            if (!$row) json_response(['ok' => false], 404);
            Database::update('automations', ['active' => $row['active'] ? 0 : 1], 'id = :id', ['id' => $id]);
            Audit::log(Auth::id(), 'automation_toggle', 'automations', $id, ['active' => !$row['active']]);
            json_response(['ok' => true, 'active' => (bool) !$row['active']]);
        }
        if ($op === 'get') {
            $row = Database::fetch('SELECT * FROM ' . Database::table('automations') . ' WHERE id = ? AND event_id = ?', [(int) ($_GET['id'] ?? 0), $E]);
            json_response(['ok' => (bool) $row, 'row' => $row]);
        }
        json_response(['ok' => false], 404);
    }

    // ---------- Disparo em massa ----------
    private static function filtersFromInput($input) {
        $f = [];
        if (!empty($input['ticket_type_id'])) $f['ticket_type_id'] = (int) $input['ticket_type_id'];
        if (!empty($input['status'])) $f['status'] = $input['status'];
        if (!empty($input['city'])) $f['city'] = trim($input['city']);
        if (!empty($input['company'])) $f['company'] = trim($input['company']);
        if (!empty($input['ids'])) {
            $f['ids'] = is_array($input['ids']) ? $input['ids'] : explode(',', $input['ids']);
        }
        return $f;
    }

    private static function bulkCount() {
        $input = array_merge($_GET, json_input());
        $ids = MessageService::segment(Event::id(), self::filtersFromInput($input));
        json_response(['ok' => true, 'count' => count($ids)]);
    }

    private static function bulkSend() {
        self::needSend();
        $input = array_merge($_POST, json_input());
        if (empty($input['confirmed'])) {
            json_response(['ok' => false, 'error' => 'Confirme o disparo.'], 422);
        }
        $channel = in_array($input['channel'] ?? '', ['email', 'whatsapp', 'both'], true) ? $input['channel'] : 'email';
        $subject = trim($input['subject'] ?? '');
        $body = trim($input['body'] ?? '');
        if ($body === '') json_response(['ok' => false, 'error' => 'Mensagem é obrigatória.'], 422);
        if ($channel !== 'whatsapp' && $subject === '') json_response(['ok' => false, 'error' => 'Assunto é obrigatório para e-mail.'], 422);
        $E = Event::id();
        // Salva como mensagem enviada
        $mid = Database::insert('messages', [
            'event_id' => $E, 'name' => mb_substr($input['name'] ?? ('Disparo ' . date('d/m/Y H:i')), 0, 120),
            'subject' => $subject ?: null, 'channel' => $channel, 'body' => $body,
            'status' => 'sent', 'created_by' => Auth::id(), 'created_at' => now(),
        ]);
        $r = MessageService::bulk($E, self::filtersFromInput($input), $channel, $subject, $body, $mid);
        Audit::log(Auth::id(), 'bulk_send', 'messages', $mid, ['recipients' => $r['recipients'], 'channel' => $channel]);
        // Processa um lote imediato para feedback rápido
        MessageService::processQueue($E, 10);
        json_response(['ok' => true, 'recipients' => $r['recipients'], 'queued' => $r['queued']]);
    }

    /** Envio individual (página do participante) */
    private static function singleSend() {
        self::needSend();
        $input = array_merge($_POST, json_input());
        $E = Event::id();
        $rid = (int) ($input['registration_id'] ?? 0);
        $channel = in_array($input['channel'] ?? '', ['email', 'whatsapp'], true) ? $input['channel'] : 'email';
        $reg = Database::fetch('SELECT id FROM ' . Database::table('registrations') . ' WHERE id = ? AND event_id = ?', [$rid, $E]);
        if (!$reg) json_response(['ok' => false, 'error' => 'Inscrição não encontrada.'], 404);
        $subject = trim($input['subject'] ?? '');
        $body = trim($input['body'] ?? '');
        // Permite enviar a partir de template
        if (!empty($input['template_id'])) {
            $tpl = Database::fetch('SELECT * FROM ' . Database::table('message_templates') . ' WHERE id = ? AND event_id = ?', [(int) $input['template_id'], $E]);
            if ($tpl) {
                $subject = $subject ?: ($tpl['subject'] ?? '');
                $body = $body ?: $tpl['body'];
            }
        }
        if ($body === '') json_response(['ok' => false, 'error' => 'Mensagem é obrigatória.'], 422);
        $n = MessageService::enqueue($E, $rid, $channel, $subject, $body);
        MessageService::processQueue($E, 5);
        Audit::log(Auth::id(), 'single_send', 'registrations', $rid, ['channel' => $channel]);
        json_response(['ok' => $n > 0, 'queued' => $n]);
    }

    /** Pré-visualização com variáveis renderizadas */
    private static function preview() {
        $input = array_merge($_POST, json_input());
        $E = Event::id();
        $rid = (int) ($input['registration_id'] ?? 0);
        $ctx = MessageService::context($E, $rid);
        if (!$ctx) {
            // Amostra genérica
            $event = Event::current();
            $reg = ['name' => 'Nome do Participante', 'social_name' => '', 'code' => 'EVT-2026-XXXXXX', 'email' => 'participante@email.com', 'company' => 'Empresa Exemplo', 'ticket_name' => 'VIP'];
            $ticketUrl = url('ticket/EXEMPLO');
        } else {
            list($reg, $event, $ticketUrl) = $ctx;
        }
        $subject = MessageService::render($input['subject'] ?? '', $reg, $event, $ticketUrl);
        $body = MessageService::render($input['body'] ?? '', $reg, $event, $ticketUrl);
        json_response(['ok' => true, 'subject' => $subject, 'body' => $body]);
    }

    // ---------- Fila ----------
    private static function queue($op) {
        $E = Event::id();
        if ($op === 'list') {
            $status = $_GET['status'] ?? '';
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $per = 25;
            $t = Database::table('message_queue');
            $where = 'q.event_id = :e';
            $params = ['e' => $E];
            if (in_array($status, ['queued', 'processing', 'sent', 'failed'], true)) {
                $where .= ' AND q.status = :st';
                $params['st'] = $status;
            }
            $total = (int) Database::fetchColumn("SELECT COUNT(*) FROM $t q WHERE $where", $params);
            $rows = Database::fetchAll(
                "SELECT q.*, r.name AS reg_name FROM $t q LEFT JOIN " . Database::table('registrations') . " r ON r.id = q.registration_id
                 WHERE $where ORDER BY q.id DESC LIMIT $per OFFSET " . (($page - 1) * $per),
                $params
            );
            json_response(['ok' => true, 'rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $per))]);
        }
        if ($op === 'process') {
            self::needSend();
            $r = MessageService::processQueue($E, 50);
            json_response(['ok' => true, 'result' => $r]);
        }
        if ($op === 'retry') {
            self::needSend();
            $input = array_merge($_POST, json_input());
            Database::update('message_queue', ['status' => 'queued', 'attempts' => 0, 'error' => null], 'id = :id AND event_id = :e', ['id' => (int) ($input['id'] ?? 0), 'e' => $E]);
            json_response(['ok' => true]);
        }
        if ($op === 'cancel') {
            self::needSend();
            $input = array_merge($_POST, json_input());
            Database::update('message_queue', ['status' => 'failed', 'error' => 'Cancelado pelo operador'], 'id = :id AND event_id = :e AND status IN (\'queued\',\'processing\')', ['id' => (int) ($input['id'] ?? 0), 'e' => $E]);
            json_response(['ok' => true]);
        }
        json_response(['ok' => false], 404);
    }

    // ---------- Logs ----------
    private static function logs() {
        $E = Event::id();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = 25;
        $channel = $_GET['channel'] ?? '';
        $t = Database::table('message_logs');
        $where = 'l.event_id = :e';
        $params = ['e' => $E];
        if (in_array($channel, ['email', 'whatsapp'], true)) {
            $where .= ' AND l.channel = :ch';
            $params['ch'] = $channel;
        }
        $total = (int) Database::fetchColumn("SELECT COUNT(*) FROM $t l WHERE $where", $params);
        $rows = Database::fetchAll(
            "SELECT l.*, r.name AS reg_name FROM $t l LEFT JOIN " . Database::table('registrations') . " r ON r.id = l.registration_id
             WHERE $where ORDER BY l.id DESC LIMIT $per OFFSET " . (($page - 1) * $per),
            $params
        );
        json_response(['ok' => true, 'rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $per))]);
    }

    // ---------- E-mail SMTP ----------
    private static function emailSave() {
        self::needSend();
        $E = Event::id();
        $input = array_merge($_POST, json_input());
        $data = [
            'host' => mb_substr(trim($input['host'] ?? ''), 0, 160) ?: null,
            'port' => max(1, (int) ($input['port'] ?? 587)),
            'username' => mb_substr(trim($input['username'] ?? ''), 0, 160) ?: null,
            'encryption' => in_array($input['encryption'] ?? '', ['tls', 'ssl', 'none'], true) ? $input['encryption'] : 'tls',
            'from_email' => mb_substr(trim($input['from_email'] ?? ''), 0, 160) ?: null,
            'from_name' => mb_substr(trim($input['from_name'] ?? ''), 0, 120) ?: null,
            'active' => !empty($input['active']) ? 1 : 0,
            'updated_at' => now(),
        ];
        if (!empty($input['password'])) {
            $data['password_enc'] = Crypto::encrypt($input['password']);
        }
        Database::update('email_settings', $data, 'event_id = :e', ['e' => $E]);
        Audit::log(Auth::id(), 'email_settings', 'email_settings', $E);
        json_response(['ok' => true]);
    }

    private static function emailTest() {
        self::needSend();
        $input = array_merge($_POST, json_input());
        $to = trim($input['to'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) json_response(['ok' => false, 'error' => 'Informe um e-mail válido para o teste.'], 422);
        $cfg = EmailService::settings(Event::id());
        if (!$cfg || !$cfg['host']) json_response(['ok' => false, 'error' => 'SMTP não configurado.'], 422);
        list($ok, $err) = EmailService::send(Event::id(), $to, 'Teste de e-mail — ' . Event::current()['name'], "Este é um e-mail de teste da plataforma.\n\nSe você recebeu, a configuração SMTP está funcionando.");
        json_response(['ok' => $ok, 'error' => $err]);
    }

    // ---------- WhatsApp ----------
    private static function waSave() {
        self::needSend();
        $E = Event::id();
        $input = array_merge($_POST, json_input());
        $data = [
            'phone_number_id' => mb_substr(trim($input['phone_number_id'] ?? ''), 0, 80) ?: null,
            'business_number' => mb_substr(trim($input['business_number'] ?? ''), 0, 40) ?: null,
            'template_lang' => 'pt_BR',
            'active' => !empty($input['active']) ? 1 : 0,
            'updated_at' => now(),
        ];
        if (!empty($input['access_token'])) {
            $data['access_token_enc'] = Crypto::encrypt($input['access_token']);
        }
        Database::update('whatsapp_settings', $data, 'event_id = :e', ['e' => $E]);
        Audit::log(Auth::id(), 'wa_settings', 'whatsapp_settings', $E);
        json_response(['ok' => true, 'status' => WhatsAppService::status($E)]);
    }

    private static function waTest() {
        self::needSend();
        $input = array_merge($_POST, json_input());
        $to = trim($input['to'] ?? '');
        if (!$to) json_response(['ok' => false, 'error' => 'Informe um número com DDD para o teste.'], 422);
        list($ok, $err) = WhatsAppService::send(Event::id(), $to, 'Teste da plataforma ' . (Event::current()['name'] ?? '') . ': integração WhatsApp funcionando.');
        json_response(['ok' => $ok, 'error' => $err]);
    }
}
