<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Validator;

defined('APP') or exit;

/**
 * Criação e validação de inscrições a partir dos campos
 * configurados no painel.
 */
class RegistrationService {
    /** Colunas de registrations preenchíveis via formulário */
    const MAPPABLE = [
        'name', 'social_name', 'email', 'phone', 'whatsapp', 'cpf',
        'birthdate', 'company', 'role', 'city', 'state',
        'cep', 'street', 'number', 'complement', 'district',
        'addr_city', 'addr_state',
    ];

    public static function activeFields($eventId) {
        return Database::fetchAll(
            'SELECT * FROM ' . Database::table('registration_fields') .
            ' WHERE event_id = ? AND active = 1 ORDER BY section, sort_order, id',
            [(int) $eventId]
        );
    }

    /**
     * Valida dados do formulário. Retorna [errors, clean, answers]
     * $data: array plano (POST). $ticketTypeId validado separadamente.
     */
    public static function validate($eventId, array $data, $ticketTypeId) {
        $errors = [];
        $clean = [];
        $answers = [];
        $fields = self::activeFields($eventId);

        // Ingresso
        $ticket = Database::fetch(
            'SELECT * FROM ' . Database::table('ticket_types') . ' WHERE id = ? AND event_id = ?',
            [(int) $ticketTypeId, (int) $eventId]
        );
        if (!$ticket || $ticket['status'] !== 'ACTIVE') {
            $errors['ticket_type_id'] = 'Selecione um ingresso válido.';
        } else {
            $sold = (int) Database::fetchColumn(
                'SELECT COUNT(*) FROM ' . Database::table('registrations') .
                ' WHERE event_id = ? AND ticket_type_id = ? AND status != ?',
                [(int) $eventId, (int) $ticket['id'], 'CANCELLED']
            );
            if ((int) $ticket['quantity'] > 0 && $sold >= (int) $ticket['quantity']) {
                $errors['ticket_type_id'] = 'Este ingresso está esgotado.';
            }
            $now = now();
            if ($ticket['sale_start'] && $now < $ticket['sale_start']) $errors['ticket_type_id'] = 'As vendas deste ingresso ainda não começaram.';
            if ($ticket['sale_end'] && $now > $ticket['sale_end']) $errors['ticket_type_id'] = 'As vendas deste ingresso foram encerradas.';
        }

        foreach ($fields as $f) {
            $key = 'f_' . $f['fname'];
            $raw = $data[$key] ?? ($data[$f['fname']] ?? null);
            $val = is_array($raw) ? array_map('trim', $raw) : trim((string) ($raw ?? ''));
            $label = $f['label'];

            if ((int) $f['required'] === 1 && !Validator::required($val)) {
                $errors[$key] = $label . ' é obrigatório.';
                continue;
            }
            if (!Validator::required($val)) {
                if ($f['map_column']) $clean[$f['map_column']] = null;
                else $answers[(int) $f['id']] = null;
                continue;
            }
            $type = $f['ftype'];
            if ($type === 'email' && !Validator::email($val)) {
                $errors[$key] = 'Informe um e-mail válido.';
                continue;
            }
            if ($type === 'tel' && !Validator::phone($val)) {
                $errors[$key] = 'Informe um telefone válido com DDD.';
                continue;
            }
            if ($type === 'date') {
                if (!Validator::date($val)) { $errors[$key] = 'Data inválida.'; continue; }
                $val = Validator::toDbDate($val);
            }
            if ($f['fname'] === 'cpf' && !Validator::cpf($val)) {
                $errors[$key] = 'CPF inválido.';
                continue;
            }
            if ($type === 'number' && !is_numeric($val)) {
                $errors[$key] = $label . ' deve ser um número.';
                continue;
            }
            if (in_array($type, ['select', 'radio'], true) && $f['options']) {
                $opts = array_map('trim', explode('|', $f['options']));
                if (!in_array($val, $opts, true)) { $errors[$key] = 'Opção inválida.'; continue; }
            }
            if ($type === 'checkbox' && $f['options']) {
                $opts = array_map('trim', explode('|', $f['options']));
                $vals = is_array($val) ? $val : [$val];
                foreach ($vals as $vv) {
                    if (!in_array($vv, $opts, true)) { $errors[$key] = 'Opção inválida.'; continue 2; }
                }
                $val = implode(', ', $vals);
            }
            if (is_array($val)) $val = implode(', ', $val);
            if ($f['map_column'] && in_array($f['map_column'], self::MAPPABLE, true)) {
                $clean[$f['map_column']] = mb_substr($val, 0, 255);
            } else {
                $answers[(int) $f['id']] = $val;
            }
        }

        // E-mail é sempre obrigatório (comunicação + ticket)
        if (empty($clean['email']) || !Validator::email($clean['email'] ?? '')) {
            $errors['f_email'] = 'Informe um e-mail válido.';
        }
        if (empty($clean['name'])) {
            $errors['f_name'] = 'Nome completo é obrigatório.';
        }

        // Duplicidade: mesmo e-mail + evento (exceto canceladas)
        if (empty($errors) && !empty($clean['email'])) {
            $dup = Database::fetch(
                'SELECT id, code FROM ' . Database::table('registrations') .
                ' WHERE event_id = ? AND email = ? AND status != ? LIMIT 1',
                [(int) $eventId, mb_strtolower($clean['email']), 'CANCELLED']
            );
            if ($dup) {
                $errors['f_email'] = 'Este e-mail já possui inscrição (código ' . $dup['code'] . ').';
            }
        }

        // Aceites (LGPD) — obrigatórios e nunca pré-marcados
        foreach (['consent_terms' => 'os termos de inscrição', 'consent_privacy' => 'a política de privacidade'] as $ck => $label) {
            if (empty($data[$ck])) $errors[$ck] = 'É necessário aceitar ' . $label . '.';
        }

        return [$errors, $clean, $answers, $ticket ?? null];
    }

    /** Cria inscrição + ticket. Retorna [ok, data|errors] */
    public static function create($eventId, array $data, $ticketTypeId, $source = 'site') {
        list($errors, $clean, $answers, $ticket) = self::validate($eventId, $data, $ticketTypeId);
        if (!empty($errors)) return [false, $errors];

        $code = TicketService::generateCode();
        $consents = [
            'terms' => !empty($data['consent_terms']) ? now() : null,
            'privacy' => !empty($data['consent_privacy']) ? now() : null,
            'comms' => !empty($data['consent_comms']) ? now() : null,
            'ip' => client_ip(),
        ];
        $row = [
            'event_id' => (int) $eventId,
            'ticket_type_id' => (int) $ticketTypeId,
            'code' => $code,
            'name' => $clean['name'],
            'social_name' => $clean['social_name'] ?? null,
            'email' => mb_strtolower($clean['email']),
            'phone' => $clean['phone'] ?? null,
            'whatsapp' => $clean['whatsapp'] ?? null,
            'cpf' => $clean['cpf'] ?? null,
            'birthdate' => $clean['birthdate'] ?? null,
            'company' => $clean['company'] ?? null,
            'role' => $clean['role'] ?? null,
            'city' => $clean['city'] ?? null,
            'state' => $clean['state'] ?? null,
            'cep' => $clean['cep'] ?? null,
            'street' => $clean['street'] ?? null,
            'number' => $clean['number'] ?? null,
            'complement' => $clean['complement'] ?? null,
            'district' => $clean['district'] ?? null,
            'addr_city' => $clean['addr_city'] ?? null,
            'addr_state' => $clean['addr_state'] ?? null,
            'status' => 'CONFIRMED',
            'payment_status' => ((float) ($ticket['price'] ?? 0) > 0) ? 'PENDING' : 'FREE',
            'consents' => json_encode($consents, JSON_UNESCAPED_UNICODE),
            'source' => mb_substr($source, 0, 60),
            'ip' => client_ip(),
            'user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'created_at' => now(),
        ];
        $id = Database::insert('registrations', $row);

        foreach ($answers as $fieldId => $val) {
            if ($val === null || $val === '') continue;
            Database::insert('registration_answers', [
                'registration_id' => $id,
                'field_id' => $fieldId,
                'fvalue' => $val,
            ]);
        }

        $qrToken = TicketService::generateToken();
        Database::insert('tickets', [
            'registration_id' => $id,
            'code' => $code,
            'qr_token' => $qrToken,
            'created_at' => now(),
        ]);

        // Automação de boas-vindas
        try {
            MessageService::fireAutomation((int) $eventId, 'after_register', $id);
        } catch (\Exception $e) { /* não bloqueia a inscrição */ }

        return [true, ['id' => $id, 'code' => $code, 'qr_token' => $qrToken, 'ticket' => $ticket]];
    }

    public static function findByCode($eventId, $code) {
        return Database::fetch(
            'SELECT r.*, t.name AS ticket_name, t.price AS ticket_price
             FROM ' . Database::table('registrations') . ' r
             LEFT JOIN ' . Database::table('ticket_types') . ' t ON t.id = r.ticket_type_id
             WHERE r.event_id = ? AND r.code = ?',
            [(int) $eventId, trim($code)]
        );
    }
}
