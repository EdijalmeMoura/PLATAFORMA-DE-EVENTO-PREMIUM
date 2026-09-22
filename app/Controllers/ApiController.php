<?php
namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Event;
use App\Core\RateLimiter;
use App\Core\Validator;
use App\Services\MessageService;
use App\Services\RegistrationService;
use App\Services\TicketService;

defined('APP') or exit;

/** API pública (JSON). */
class ApiController {
    /** POST /api/register — cria inscrição */
    public static function register() {
        $ip = client_ip();
        if (!RateLimiter::check('reg:' . $ip, 10, 3600)) {
            json_response(['ok' => false, 'errors' => ['_global' => 'Muitas tentativas. Aguarde e tente novamente.']], 429);
        }
        $input = array_merge($_POST, json_input());
        if (!Csrf::validate($input['csrf_token'] ?? '')) {
            json_response(['ok' => false, 'errors' => ['_global' => 'Sessão expirada. Recarregue a página.']], 419);
        }
        $eventId = Event::id();
        $ticketId = (int) ($input['ticket_type_id'] ?? 0);
        $source = isset($input['source']) ? mb_substr(trim($input['source']), 0, 60) : 'site';
        list($ok, $result) = RegistrationService::create($eventId, $input, $ticketId, $source);
        if (!$ok) {
            json_response(['ok' => false, 'errors' => $result], 422);
        }
        RateLimiter::hit('reg:' . $ip, 3600);
        json_response([
            'ok' => true,
            'code' => $result['code'],
            'ticket_url' => TicketService::ticketUrl($result['qr_token']),
            'redirect' => url('inscricao/sucesso?code=' . $result['code']),
        ]);
    }

    /** GET /api/event — dados públicos (contagem, ingressos) */
    public static function event() {
        $event = Event::current();
        $tickets = [];
        foreach (Event::tickets(true) as $t) {
            $remaining = max(0, (int) $t['quantity'] - (int) $t['sold']);
            $tickets[] = [
                'id' => (int) $t['id'],
                'name' => $t['name'],
                'price' => (float) $t['price'],
                'remaining' => (int) $t['quantity'] > 0 ? $remaining : null,
                'sold_out' => (int) $t['quantity'] > 0 && $remaining <= 0,
            ];
        }
        json_response([
            'ok' => true,
            'event' => [
                'name' => $event['name'],
                'countdown_target' => $event['countdown_target'],
                'date_start' => $event['date_start'],
            ],
            'tickets' => $tickets,
        ]);
    }

    /** POST /api/ticket/resend — reenvia ticket (limitado) */
    public static function resendTicket() {
        $input = array_merge($_POST, json_input());
        if (!Csrf::validate($input['csrf_token'] ?? '')) {
            json_response(['ok' => false, 'error' => 'Sessão expirada.'], 419);
        }
        $code = strtoupper(trim($input['code'] ?? ''));
        $channel = ($input['channel'] ?? 'email') === 'whatsapp' ? 'whatsapp' : 'email';
        if ($code === '') json_response(['ok' => false, 'error' => 'Código inválido.'], 422);
        if (!RateLimiter::check('resend:' . $code, 3, 3600)) {
            json_response(['ok' => false, 'error' => 'Limite de reenvios atingido. Tente mais tarde.'], 429);
        }
        $eventId = Event::id();
        $reg = Database::fetch(
            'SELECT * FROM ' . Database::table('registrations') . ' WHERE event_id = ? AND code = ? AND status != \'CANCELLED\'',
            [$eventId, $code]
        );
        if (!$reg) json_response(['ok' => false, 'error' => 'Inscrição não encontrada.'], 404);
        $tpl = Database::fetch(
            'SELECT * FROM ' . Database::table('message_templates') . ' WHERE event_id = ? AND tkey = \'ticket\' LIMIT 1',
            [$eventId]
        );
        $subject = $tpl['subject'] ?? 'Seu ticket';
        $body = $tpl['body'] ?? "Olá {{nome}}!\nAqui está seu ticket: {{ticket_url}}";
        $n = MessageService::enqueue($eventId, (int) $reg['id'], $channel, $subject, $body);
        if ($n > 0) {
            RateLimiter::hit('resend:' . $code, 3600);
            // Tenta processar imediatamente (melhor UX)
            MessageService::processQueue($eventId, 5);
            json_response(['ok' => true, 'message' => 'Ticket reenviado!']);
        }
        json_response(['ok' => false, 'error' => 'Não foi possível reenviar agora.'], 500);
    }

    /** GET /api/open/{queueId}.gif — beacon de abertura de e-mail */
    public static function openBeacon($queueId) {
        try {
            $log = Database::fetch(
                'SELECT id FROM ' . Database::table('message_logs') . ' WHERE queue_id = ? AND channel = \'email\' LIMIT 1',
                [(int) $queueId]
            );
            if ($log) {
                Database::update('message_logs', ['opened_at' => now()], 'id = :id AND opened_at IS NULL', ['id' => $log['id']]);
            }
        } catch (\Exception $e) { /* ignora */ }
        header('Content-Type: image/gif');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo base64_decode('R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
        exit;
    }
}
