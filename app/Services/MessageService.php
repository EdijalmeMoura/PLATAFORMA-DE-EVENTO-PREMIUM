<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Validator;

defined('APP') or exit;

/**
 * Comunicação: variáveis automáticas, fila de envio,
 * disparos em massa e automações.
 */
class MessageService {
    /** Variáveis disponíveis nos textos */
    public static function variables() {
        return [
            '{{nome}}' => 'Nome do participante',
            '{{codigo_inscricao}}' => 'Código da inscrição',
            '{{evento}}' => 'Nome do evento',
            '{{data_evento}}' => 'Data do evento',
            '{{horario}}' => 'Horário do evento',
            '{{local}}' => 'Local do evento',
            '{{tipo_ingresso}}' => 'Tipo de ingresso',
            '{{ticket_url}}' => 'Link do ticket digital',
            '{{email}}' => 'E-mail do participante',
            '{{empresa}}' => 'Empresa do participante',
        ];
    }

    /** Renderiza variáveis para um destinatário */
    public static function render($text, array $reg, array $event, $ticketUrl) {
        $date = '';
        if (!empty($event['date_start'])) {
            $ts = strtotime($event['date_start']);
            $meses = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
            $date = date('d', $ts) . ' de ' . $meses[(int) date('n', $ts)] . ' de ' . date('Y', $ts);
        }
        $local = trim(($event['venue_name'] ?? '') . ' — ' . ($event['city'] ?? '') . '/' . ($event['state'] ?? ''), ' —/');
        $map = [
            '{{nome}}' => $reg['social_name'] ?: $reg['name'],
            '{{codigo_inscricao}}' => $reg['code'],
            '{{evento}}' => $event['name'],
            '{{data_evento}}' => $date,
            '{{horario}}' => $event['time_text'] ?? '',
            '{{local}}' => $local,
            '{{tipo_ingresso}}' => $reg['ticket_name'] ?? '',
            '{{ticket_url}}' => $ticketUrl,
            '{{email}}' => $reg['email'],
            '{{empresa}}' => $reg['company'] ?? '',
        ];
        return str_replace(array_keys($map), array_values($map), (string) $text);
    }

    /** Dados prontos (reg + event + ticket) para render */
    public static function context($eventId, $registrationId) {
        $reg = Database::fetch(
            'SELECT r.*, t.name AS ticket_name, tk.qr_token
             FROM ' . Database::table('registrations') . ' r
             LEFT JOIN ' . Database::table('ticket_types') . ' t ON t.id = r.ticket_type_id
             LEFT JOIN ' . Database::table('tickets') . ' tk ON tk.registration_id = r.id
             WHERE r.id = ? AND r.event_id = ?',
            [(int) $registrationId, (int) $eventId]
        );
        if (!$reg) return null;
        $event = Database::fetch('SELECT * FROM ' . Database::table('events') . ' WHERE id = ?', [(int) $eventId]);
        $ticketUrl = !empty($reg['qr_token']) ? TicketService::ticketUrl($reg['qr_token']) : '';
        return [$reg, $event, $ticketUrl];
    }

    /** Enfileira 1 mensagem (expande both → email+whatsapp) */
    public static function enqueue($eventId, $registrationId, $channel, $subject, $body, $messageId = null, $automation = null, $scheduledAt = null) {
        $channels = $channel === 'both' ? ['email', 'whatsapp'] : [$channel];
        $ctx = self::context($eventId, $registrationId);
        if (!$ctx) return 0;
        list($reg, $event, $ticketUrl) = $ctx;
        $n = 0;
        foreach ($channels as $ch) {
            $to = $ch === 'email' ? $reg['email'] : ($reg['whatsapp'] ?: $reg['phone']);
            if (!$to) continue;
            if ($ch === 'email' && !Validator::email($to)) continue;
            if ($ch === 'whatsapp' && !Validator::phone($to)) continue;
            Database::insert('message_queue', [
                'event_id' => (int) $eventId,
                'registration_id' => (int) $registrationId,
                'message_id' => $messageId,
                'automation' => $automation,
                'channel' => $ch,
                'to_address' => mb_substr($to, 0, 200),
                'subject' => $subject ? self::render($subject, $reg, $event, $ticketUrl) : null,
                'body' => self::render($body, $reg, $event, $ticketUrl),
                'status' => 'queued',
                'attempts' => 0,
                'scheduled_at' => $scheduledAt,
                'created_at' => now(),
            ]);
            $n++;
        }
        return $n;
    }

    /** Dispara automação para 1 inscrição (respeita ativo/inativo) */
    public static function fireAutomation($eventId, $trigger, $registrationId) {
        $auto = Database::fetch(
            'SELECT * FROM ' . Database::table('automations') . ' WHERE event_id = ? AND trigger = ? AND active = 1 LIMIT 1',
            [(int) $eventId, $trigger]
        );
        if (!$auto) return 0;
        // Dedupe: mesma automação para o mesmo inscrito
        $done = Database::fetchColumn(
            'SELECT id FROM ' . Database::table('message_logs') .
            ' WHERE event_id = ? AND registration_id = ? AND automation = ? LIMIT 1',
            [(int) $eventId, (int) $registrationId, $trigger]
        );
        if ($done) return 0;
        $queued = Database::fetchColumn(
            'SELECT id FROM ' . Database::table('message_queue') .
            ' WHERE event_id = ? AND registration_id = ? AND automation = ? AND status IN (\'queued\',\'processing\') LIMIT 1',
            [(int) $eventId, (int) $registrationId, $trigger]
        );
        if ($queued) return 0;
        return self::enqueue($eventId, $registrationId, $auto['channel'], $auto['subject'], $auto['body'], null, $trigger);
    }

    /** Segmentação para disparo em massa. Retorna IDs */
    public static function segment($eventId, array $filters) {
        $where = ['r.event_id = :e', "r.status != 'CANCELLED'"];
        $params = ['e' => (int) $eventId];
        if (!empty($filters['ticket_type_id'])) {
            $where[] = 'r.ticket_type_id = :tt';
            $params['tt'] = (int) $filters['ticket_type_id'];
        }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'NO_CHECKIN') {
                $where[] = "r.status IN ('CONFIRMED','PENDING')";
            } else {
                $where[] = 'r.status = :st';
                $params['st'] = $filters['status'];
            }
        }
        if (!empty($filters['city'])) {
            $where[] = '(r.city LIKE :city OR r.addr_city LIKE :city)';
            $params['city'] = '%' . $filters['city'] . '%';
        }
        if (!empty($filters['company'])) {
            $where[] = 'r.company LIKE :co';
            $params['co'] = '%' . $filters['company'] . '%';
        }
        if (!empty($filters['ids']) && is_array($filters['ids'])) {
            $ids = array_values(array_filter(array_map('intval', $filters['ids'])));
            if ($ids) {
                $where[] = 'r.id IN (' . implode(',', $ids) . ')';
            }
        }
        $sql = 'SELECT r.id FROM ' . Database::table('registrations') . ' r WHERE ' . implode(' AND ', $where);
        $rows = Database::fetchAll($sql, $params);
        return array_column($rows, 'id');
    }

    /** Cria disparo em massa (enfileira em lotes) */
    public static function bulk($eventId, array $filters, $channel, $subject, $body, $messageId = null) {
        $ids = self::segment($eventId, $filters);
        $queued = 0;
        foreach (array_chunk($ids, 200) as $chunk) {
            foreach ($chunk as $rid) {
                $queued += self::enqueue($eventId, $rid, $channel, $subject, $body, $messageId);
            }
        }
        return ['recipients' => count($ids), 'queued' => $queued];
    }

    /**
     * Processa a fila (chamado pelo cron ou pelo painel).
     * Retorna [sent, failed].
     */
    public static function processQueue($eventId, $limit = 50) {
        $t = Database::table('message_queue');
        $items = Database::fetchAll(
            "SELECT * FROM $t WHERE event_id = ? AND status = 'queued'
             AND (scheduled_at IS NULL OR scheduled_at <= ?)
             ORDER BY id ASC LIMIT " . (int) $limit,
            [(int) $eventId, now()]
        );
        $sent = 0; $failed = 0;
        foreach ($items as $it) {
            Database::update('message_queue', ['status' => 'processing', 'attempts' => (int) $it['attempts'] + 1], 'id = :id', ['id' => $it['id']]);
            try {
                if ($it['channel'] === 'email') {
                    list($ok, $err) = EmailService::send($eventId, $it['to_address'], $it['subject'] ?: 'Mensagem do evento', $it['body'], $it['id']);
                } else {
                    list($ok, $err) = WhatsAppService::send($eventId, $it['to_address'], $it['body']);
                }
            } catch (\Throwable $e) {
                // 1 item com erro fatal não pode matar o lote/cron inteiro
                $ok = false;
                $err = 'Erro interno: ' . $e->getMessage();
            }
            if ($ok) {
                $sent++;
                Database::update('message_queue', ['status' => 'sent', 'sent_at' => now(), 'error' => null], 'id = :id', ['id' => $it['id']]);
                Database::insert('message_logs', [
                    'event_id' => (int) $eventId, 'queue_id' => $it['id'],
                    'registration_id' => $it['registration_id'], 'automation' => $it['automation'],
                    'channel' => $it['channel'], 'to_address' => $it['to_address'],
                    'subject' => $it['subject'], 'body_excerpt' => mb_substr($it['body'], 0, 500),
                    'status' => 'sent', 'error' => null, 'created_at' => now(),
                ]);
            } else {
                $failed++;
                $attempts = (int) $it['attempts'] + 1;
                $final = $attempts >= 3 ? 'failed' : 'queued';
                Database::update('message_queue', ['status' => $final, 'error' => mb_substr($err, 0, 500)], 'id = :id', ['id' => $it['id']]);
                if ($final === 'failed') {
                    Database::insert('message_logs', [
                        'event_id' => (int) $eventId, 'queue_id' => $it['id'],
                        'registration_id' => $it['registration_id'], 'automation' => $it['automation'],
                        'channel' => $it['channel'], 'to_address' => $it['to_address'],
                        'subject' => $it['subject'], 'body_excerpt' => mb_substr($it['body'], 0, 500),
                        'status' => 'failed', 'error' => mb_substr($err, 0, 500), 'created_at' => now(),
                    ]);
                }
            }
        }
        return ['sent' => $sent, 'failed' => $failed, 'processed' => count($items)];
    }

    /** Agenda lembretes por data (d7, d1, day_of). Chamado pelo cron. */
    public static function scheduleDateReminders($eventId) {
        $event = Database::fetch('SELECT * FROM ' . Database::table('events') . ' WHERE id = ?', [(int) $eventId]);
        if (!$event || empty($event['date_start'])) return 0;
        $diff = (int) floor((strtotime($event['date_start']) - strtotime(date('Y-m-d'))) / 86400);
        $map = [7 => 'd7_before', 1 => 'd1_before', 0 => 'day_of'];
        if (!isset($map[$diff])) return 0;
        $trigger = $map[$diff];
        $auto = Database::fetch(
            'SELECT * FROM ' . Database::table('automations') . ' WHERE event_id = ? AND trigger = ? AND active = 1 LIMIT 1',
            [(int) $eventId, $trigger]
        );
        if (!$auto) return 0;
        $regs = Database::fetchAll(
            'SELECT id FROM ' . Database::table('registrations') . " WHERE event_id = ? AND status IN ('CONFIRMED','PENDING')",
            [(int) $eventId]
        );
        $n = 0;
        foreach ($regs as $r) {
            $n += self::fireAutomation($eventId, $trigger, (int) $r['id']);
        }
        return $n;
    }
}
