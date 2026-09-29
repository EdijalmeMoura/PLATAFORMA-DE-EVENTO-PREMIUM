<?php
namespace App\Services;

use App\Core\Database;

defined('APP') or exit;

/** Exportação de inscritos: CSV, Excel (.xls) e HTML imprimível (PDF). */
class ExportService {
    public static function columns() {
        return [
            'code' => 'Código',
            'name' => 'Nome',
            'social_name' => 'Nome social',
            'email' => 'E-mail',
            'phone' => 'Telefone',
            'whatsapp' => 'WhatsApp',
            'cpf' => 'CPF',
            'birthdate' => 'Nascimento',
            'company' => 'Empresa',
            'role' => 'Cargo',
            'city' => 'Cidade',
            'state' => 'UF',
            'ticket_name' => 'Ingresso',
            'status' => 'Status',
            'payment_status' => 'Pagamento',
            'created_at' => 'Inscrição em',
            'checked_in_at' => 'Check-in em',
        ];
    }

    public static function rows($eventId, array $filters = []) {
        $t = Database::table('registrations');
        $tt = Database::table('ticket_types');
        $where = ['r.event_id = :e'];
        $params = ['e' => (int) $eventId];
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'CHECKED_IN') $where[] = "r.status = 'CHECKED_IN'";
            elseif ($filters['status'] === 'NO_CHECKIN') $where[] = "r.status IN ('CONFIRMED','PENDING')";
            else { $where[] = 'r.status = :st'; $params['st'] = $filters['status']; }
        }
        if (!empty($filters['ticket_type_id'])) {
            $where[] = 'r.ticket_type_id = :tt';
            $params['tt'] = (int) $filters['ticket_type_id'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(r.name LIKE :q OR r.code LIKE :q OR r.email LIKE :q OR r.whatsapp LIKE :q OR r.cpf LIKE :q OR r.company LIKE :q OR r.phone LIKE :q)';
            $params['q'] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['ids']) && is_array($filters['ids'])) {
            $ids = array_values(array_filter(array_map('intval', $filters['ids'])));
            if ($ids) $where[] = 'r.id IN (' . implode(',', $ids) . ')';
        }
        return Database::fetchAll(
            'SELECT r.*, t.name AS ticket_name FROM ' . $t . ' r
             LEFT JOIN ' . $tt . ' t ON t.id = r.ticket_type_id
             WHERE ' . implode(' AND ', $where) . ' ORDER BY r.id DESC LIMIT 20000',
            $params
        );
    }

    /**
     * Neutraliza CSV formula injection: células iniciadas com = + - @
     * executariam fórmulas ao abrir no Excel. Prefixo ' força texto.
     */
    private static function cell($v) {
        $v = (string) $v;
        if ($v !== '' && strpos('=+-@', $v[0]) !== false) $v = "'" . $v;
        return $v;
    }

    public static function csv($rows) {
        $cols = self::columns();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="inscritos-' . date('Ymd-His') . '.csv"');
        echo "\xEF\xBB\xBF"; // BOM
        $out = fopen('php://output', 'w');
        fputcsv($out, array_values($cols), ';');
        foreach ($rows as $r) {
            $line = [];
            foreach ($cols as $k => $label) {
                $v = $r[$k] ?? '';
                if ($k === 'status') $v = registration_status_label($v);
                if ($k === 'payment_status') $v = payment_status_label($v);
                $line[] = self::cell($v);
            }
            fputcsv($out, $line, ';');
        }
        fclose($out);
        exit;
    }

    /** Excel compatível via tabela HTML (.xls) — sem dependências */
    public static function xls($rows, $eventName) {
        $cols = self::columns();
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="inscritos-' . date('Ymd-His') . '.xls"');
        echo "\xEF\xBB\xBF";
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body>';
        echo '<h3>' . e($eventName) . ' — Inscritos (' . count($rows) . ')</h3>';
        echo '<table border="1"><tr>';
        foreach ($cols as $label) echo '<th>' . e($label) . '</th>';
        echo '</tr>';
        foreach ($rows as $r) {
            echo '<tr>';
            foreach ($cols as $k => $label) {
                $v = $r[$k] ?? '';
                if ($k === 'status') $v = registration_status_label($v);
                if ($k === 'payment_status') $v = payment_status_label($v);
                // mso-number-format:\@ preserva zeros à esquerda (CPF, telefone, código)
                echo '<td style="mso-number-format:\\@">' . e(self::cell($v)) . '</td>';
            }
            echo '</tr>';
        }
        echo '</table></body></html>';
        exit;
    }
}
