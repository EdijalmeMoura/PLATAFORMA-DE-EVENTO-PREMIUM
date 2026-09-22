<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Event;
use App\Core\View;
use App\Services\TicketService;

defined('APP') or exit;

/** Páginas públicas: landing, inscrição, ticket, legal. */
class PublicController {
    private static function publicData() {
        $event = Event::current();
        if (!$event) {
            redirect('install.php');
        }
        return [
            'event' => $event,
            'content' => Event::allContent(),
            'tickets' => Event::tickets(true),
            'schedule' => Event::schedule(),
            'speakers' => Event::speakers(),
            'faqs' => Event::faqs(),
        ];
    }

    public static function landing() {
        $d = self::publicData();
        $d['page_title'] = $d['event']['name'];
        View::render('public/landing', $d, 'public/layout');
    }

    public static function register() {
        $d = self::publicData();
        $d['fields'] = Database::fetchAll(
            'SELECT * FROM ' . Database::table('registration_fields') .
            ' WHERE event_id = ? AND active = 1 ORDER BY
              CASE section WHEN \'personal\' THEN 0 WHEN \'address\' THEN 1 ELSE 2 END, sort_order, id',
            [Event::id()]
        );
        $d['selected_ticket'] = (int) ($_GET['ingresso'] ?? 0);
        $d['page_title'] = 'Inscrição — ' . $d['event']['name'];
        View::render('public/register', $d, 'public/layout');
    }

    public static function success() {
        $d = self::publicData();
        $code = trim($_GET['code'] ?? '');
        $reg = Database::fetch(
            'SELECT r.*, t.name AS ticket_name, tk.qr_token
             FROM ' . Database::table('registrations') . ' r
             LEFT JOIN ' . Database::table('ticket_types') . ' t ON t.id = r.ticket_type_id
             LEFT JOIN ' . Database::table('tickets') . ' tk ON tk.registration_id = r.id
             WHERE r.event_id = ? AND r.code = ?',
            [Event::id(), $code]
        );
        if (!$reg) redirect('inscricao');
        $d['reg'] = $reg;
        $d['ticket_url'] = !empty($reg['qr_token']) ? TicketService::ticketUrl($reg['qr_token']) : '';
        $d['page_title'] = 'Inscrição confirmada — ' . $d['event']['name'];
        View::render('public/success', $d, 'public/layout');
    }

    /** Página do ticket digital (aceita token do QR ou código) */
    public static function ticket($identifier) {
        $d = self::publicData();
        $identifier = trim($identifier);
        $reg = Database::fetch(
            'SELECT r.*, t.name AS ticket_name, t.price AS ticket_price, tk.qr_token
             FROM ' . Database::table('registrations') . ' r
             LEFT JOIN ' . Database::table('ticket_types') . ' t ON t.id = r.ticket_type_id
             LEFT JOIN ' . Database::table('tickets') . ' tk ON tk.registration_id = r.id
             WHERE r.event_id = ? AND (tk.qr_token = ? OR r.code = ? OR tk.code = ?)',
            [Event::id(), $identifier, $identifier, $identifier]
        );
        if (!$reg) {
            http_response_code(404);
            $d['page_title'] = 'Ticket não encontrado';
            View::render('public/not-found', $d, 'public/layout');
            return;
        }
        $d['reg'] = $reg;
        $d['ticket_url'] = !empty($reg['qr_token']) ? TicketService::ticketUrl($reg['qr_token']) : url('ticket/' . $reg['code']);
        $d['qr_payload'] = $d['ticket_url'];
        $d['page_title'] = 'Meu ticket — ' . $d['event']['name'];
        View::render('public/ticket', $d, 'public/layout');
    }

    /** Recuperação de ticket por e-mail + código */
    public static function lookup() {
        $d = self::publicData();
        $d['page_title'] = 'Recuperar ticket — ' . $d['event']['name'];
        $d['error'] = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = mb_strtolower(trim($_POST['email'] ?? ''));
            $code = trim($_POST['code'] ?? '');
            $reg = Database::fetch(
                'SELECT r.*, tk.qr_token FROM ' . Database::table('registrations') . ' r
                 LEFT JOIN ' . Database::table('tickets') . ' tk ON tk.registration_id = r.id
                 WHERE r.event_id = ? AND r.email = ? AND r.code = ?',
                [Event::id(), $email, strtoupper($code)]
            );
            if ($reg && !empty($reg['qr_token'])) {
                redirect('ticket/' . $reg['qr_token']);
            }
            $d['error'] = 'Não encontramos inscrição com esses dados. Confira e tente novamente.';
        }
        View::render('public/lookup', $d, 'public/layout');
    }

    public static function legal($which) {
        $d = self::publicData();
        $d['which'] = $which;
        $d['page_title'] = ($which === 'terms' ? 'Termos de Inscrição' : 'Política de Privacidade') . ' — ' . $d['event']['name'];
        View::render('public/legal', $d, 'public/layout');
    }

    /** Arquivo .ics (adicionar à agenda) */
    public static function ics($identifier) {
        $event = Event::current();
        $identifier = trim($identifier);
        $reg = Database::fetch(
            'SELECT r.code FROM ' . Database::table('registrations') . ' r
             LEFT JOIN ' . Database::table('tickets') . ' tk ON tk.registration_id = r.id
             WHERE r.event_id = ? AND (tk.qr_token = ? OR r.code = ?)',
            [Event::id(), $identifier, $identifier]
        );
        if (!$reg) { http_response_code(404); exit; }
        $date = $event['date_start'] ?: date('Y-m-d');
        $start = str_replace('-', '', $date) . 'T190000';
        $end = str_replace('-', '', $event['date_end'] ?: $date) . 'T230000';
        $loc = ($event['venue_name'] ?? '') . ', ' . ($event['address'] ?? '') . ' - ' . ($event['city'] ?? '') . '/' . ($event['state'] ?? '');
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="evento.ics"');
        echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//EventoPremium//PT\r\nBEGIN:VEVENT\r\n"
            . 'UID:' . $reg['code'] . "@evento\r\n"
            . 'DTSTAMP:' . gmdate('Ymd\THis\Z') . "\r\n"
            . 'DTSTART:' . $start . "\r\n"
            . 'DTEND:' . $end . "\r\n"
            . 'SUMMARY:' . self::icsEscape($event['name']) . "\r\n"
            . 'LOCATION:' . self::icsEscape($loc) . "\r\n"
            . 'DESCRIPTION:' . self::icsEscape('Código de inscrição: ' . $reg['code']) . "\r\n"
            . "END:VEVENT\r\nEND:VCALENDAR\r\n";
        exit;
    }

    private static function icsEscape($s) {
        return str_replace(["\\", ";", ",", "\n", "\r"], ["\\\\", "\\;", "\\,", "\\n", ""], (string) $s);
    }

    public static function notFound() {
        $d = ['event' => Event::current() ?: ['name' => 'Evento'], 'content' => [], 'page_title' => 'Página não encontrada'];
        View::render('public/not-found', $d, 'public/layout');
    }
}
