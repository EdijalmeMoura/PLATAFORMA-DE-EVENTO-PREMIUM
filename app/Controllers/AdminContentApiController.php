<?php
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Event;
use App\Core\Upload;

defined('APP') or exit;

/** API admin: evento, landing, programação, palestrantes, ingressos, FAQ, formulário. */
class AdminContentApiController {
    public static function handle($sub, $method) {
        header('Content-Type: application/json; charset=utf-8');
        Auth::requireCan('content.manage');
        if ($method === 'POST') Csrf::requireValid();
        $parts = explode('/', trim($sub, '/'));
        $action = $parts[0] ?? '';
        switch ($action) {
            case 'event-save': self::eventSave(); break;
            case 'content-save': self::contentSave(); break;
            case 'upload': self::upload(); break;
            case 'schedule': self::crud('schedule_items', ['stime', 'title', 'description', 'speaker', 'slocation', 'image'], $parts[1] ?? ''); break;
            case 'speakers': self::crud('speakers', ['name', 'role', 'company', 'bio', 'photo', 'instagram', 'linkedin'], $parts[1] ?? ''); break;
            case 'tickets': self::crud('ticket_types', ['name', 'description', 'price', 'quantity', 'benefits', 'sale_start', 'sale_end', 'status'], $parts[1] ?? ''); break;
            case 'faqs': self::crud('faqs', ['question', 'answer'], $parts[1] ?? ''); break;
            case 'fields': self::fields($parts[1] ?? ''); break;
            case 'reorder': self::reorder(); break;
            default: json_response(['ok' => false, 'error' => 'Rota inválida.'], 404);
        }
    }

    private static function eventSave() {
        $E = Event::id();
        $input = array_merge($_POST, json_input());
        $allowed = ['name', 'tagline', 'description', 'date_start', 'date_end', 'time_text',
            'countdown_target', 'venue_name', 'address', 'city', 'state', 'map_url', 'maps_embed',
            'contact_email', 'contact_whatsapp', 'contact_phone', 'instagram', 'linkedin', 'website', 'status'];
        $data = [];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $input)) {
                $v = trim((string) $input[$col]);
                $data[$col] = $v === '' ? null : $v;
            }
        }
        // Uploads de imagens do evento
        foreach (['logo' => 'logo', 'hero_image' => 'hero', 'about_image' => 'general', 'venue_image' => 'venue'] as $field => $folder) {
            if (!empty($_FILES[$field]['name'])) {
                list($ok, $res) = Upload::image($_FILES[$field], $folder);
                if (!$ok) json_response(['ok' => false, 'error' => $res], 422);
                $ev = Event::current();
                if (!empty($ev[$field])) Upload::delete($ev[$field]);
                $data[$field] = $res;
            }
        }
        if (empty($data['name'] ?? Event::current()['name'])) {
            json_response(['ok' => false, 'error' => 'Nome do evento é obrigatório.'], 422);
        }
        // datetime-local (2026-10-08T08:00) → DATETIME do banco
        if (!empty($data['countdown_target'])) {
            $data['countdown_target'] = str_replace('T', ' ', $data['countdown_target']);
            if (strlen($data['countdown_target']) === 16) $data['countdown_target'] .= ':00';
        }
        $data['updated_at'] = now();
        Database::update('events', $data, 'id = :id', ['id' => $E]);
        Audit::log(Auth::id(), 'event_update', 'events', $E);
        json_response(['ok' => true]);
    }

    private static function contentSave() {
        $input = array_merge($_POST, json_input());
        $section = preg_replace('/[^a-z_]/', '', $input['section'] ?? '');
        $values = $input['values'] ?? [];
        if ($section === '' || !is_array($values)) json_response(['ok' => false, 'error' => 'Dados inválidos.'], 422);
        $allowedSections = ['hero', 'countdown', 'about', 'experience', 'awards', 'schedule', 'speakers', 'venue', 'tickets', 'faq', 'register', 'cta_final', 'footer', 'legal', 'theme'];
        if (!in_array($section, $allowedSections, true)) json_response(['ok' => false, 'error' => 'Seção inválida.'], 422);
        foreach ($values as $k => $v) {
            $k = preg_replace('/[^a-z_]/', '', (string) $k);
            if ($k === '') continue;
            if ($section === 'legal' && in_array($k, ['terms', 'privacy'], true)) {
                $v = self::sanitizeHtml($v);
            } elseif (is_array($v)) {
                $v = json_encode($v, JSON_UNESCAPED_UNICODE);
            }
            Event::setContent($section, $k, (string) $v);
        }
        Audit::log(Auth::id(), 'content_update', 'event_content', null, ['section' => $section]);
        json_response(['ok' => true]);
    }

    private static function sanitizeHtml($html) {
        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><h3><h4><a>';
        $html = strip_tags((string) $html, $allowed);
        // Remove javascript: dos links
        $html = preg_replace('#(<a[^>]+href=["\'])\s*javascript:[^"\']*(["\'])#i', '$1#$2', $html);
        return $html;
    }

    private static function upload() {
        if (empty($_FILES['file']['name'])) json_response(['ok' => false, 'error' => 'Nenhum arquivo enviado.'], 422);
        $folder = preg_replace('/[^a-z0-9_-]/i', '', $_POST['folder'] ?? 'general') ?: 'general';
        list($ok, $res) = Upload::image($_FILES['file'], $folder);
        if (!$ok) json_response(['ok' => false, 'error' => $res], 422);
        json_response(['ok' => true, 'path' => $res, 'url' => url($res)]);
    }

    /** CRUD genérico para tabelas do evento */
    private static function crud($table, array $allowed, $op) {
        $E = Event::id();
        $input = array_merge($_POST, json_input());
        if ($op === 'save') {
            $id = (int) ($input['id'] ?? 0);
            $data = ['event_id' => $E];
            foreach ($allowed as $col) {
                if (array_key_exists($col, $input)) {
                    $v = is_string($input[$col]) ? trim($input[$col]) : $input[$col];
                    if ($col === 'price') $v = self::parseMoney($input[$col]);
                    if ($col === 'quantity') $v = max(0, (int) $input[$col]);
                    if (in_array($col, ['sale_start', 'sale_end'], true) && is_string($v) && $v !== '') {
                        $v = str_replace('T', ' ', $v);
                        if (strlen($v) === 16) $v .= ':00';
                    }
                    $data[$col] = ($v === '' && !in_array($col, ['price', 'quantity'], true)) ? null : $v;
                }
            }
            if ($table === 'ticket_types' && isset($data['status']) && !in_array($data['status'], ['ACTIVE', 'PAUSED', 'SOLD_OUT'], true)) {
                $data['status'] = 'ACTIVE';
            }
            if ($id > 0) {
                unset($data['event_id']);
                Database::update($table, $data, 'id = :id AND event_id = :e', ['id' => $id, 'e' => $E]);
                Audit::log(Auth::id(), $table . '_update', $table, $id);
            } else {
                $max = (int) Database::fetchColumn('SELECT COALESCE(MAX(sort_order),-1) FROM ' . Database::table($table) . ' WHERE event_id = ?', [$E]);
                if (in_array($table, ['schedule_items', 'speakers', 'ticket_types', 'faqs'], true)) $data['sort_order'] = $max + 1;
                if ($table === 'ticket_types') $data['created_at'] = now();
                $id = Database::insert($table, $data);
                Audit::log(Auth::id(), $table . '_create', $table, $id);
            }
            json_response(['ok' => true, 'id' => $id]);
        }
        if ($op === 'delete') {
            $id = (int) ($input['id'] ?? 0);
            if ($table === 'ticket_types') {
                $used = (int) Database::fetchColumn('SELECT COUNT(*) FROM ' . Database::table('registrations') . ' WHERE ticket_type_id = ?', [$id]);
                if ($used > 0) json_response(['ok' => false, 'error' => 'Este ingresso possui inscrições vinculadas e não pode ser excluído. Pause-o.'], 422);
            }
            Database::delete($table, 'id = ? AND event_id = ?', [$id, $E]);
            Audit::log(Auth::id(), $table . '_delete', $table, $id);
            json_response(['ok' => true]);
        }
        if ($op === 'get') {
            $id = (int) ($_GET['id'] ?? 0);
            $row = Database::fetch('SELECT * FROM ' . Database::table($table) . ' WHERE id = ? AND event_id = ?', [$id, $E]);
            json_response(['ok' => (bool) $row, 'row' => $row]);
        }
        json_response(['ok' => false, 'error' => 'Operação inválida.'], 404);
    }

    private static function parseMoney($v) {
        $v = trim((string) $v);
        if ($v === '') return 0;
        // Aceita "1.234,56" ou "1234.56"
        if (preg_match('/,\d{1,2}$/', $v)) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        }
        return round((float) $v, 2);
    }

    /** Campos do formulário */
    private static function fields($op) {
        $E = Event::id();
        $input = array_merge($_POST, json_input());
        if ($op === 'save') {
            $id = (int) ($input['id'] ?? 0);
            $label = trim($input['label'] ?? '');
            $ftype = $input['ftype'] ?? 'text';
            $allowedTypes = ['text', 'email', 'tel', 'number', 'date', 'select', 'radio', 'checkbox', 'textarea'];
            if (!in_array($ftype, $allowedTypes, true)) $ftype = 'text';
            if ($label === '') json_response(['ok' => false, 'error' => 'Rótulo é obrigatório.'], 422);
            $data = [
                'label' => mb_substr($label, 0, 120),
                'ftype' => $ftype,
                'options' => isset($input['options']) ? mb_substr(trim($input['options']), 0, 1000) : null,
                'placeholder' => isset($input['placeholder']) ? mb_substr(trim($input['placeholder']), 0, 160) : null,
                'help' => isset($input['help']) ? mb_substr(trim($input['help']), 0, 255) : null,
                'required' => !empty($input['required']) ? 1 : 0,
                'active' => isset($input['active']) ? (!empty($input['active']) ? 1 : 0) : 1,
                'section' => in_array($input['section'] ?? '', ['personal', 'address', 'extra'], true) ? $input['section'] : 'extra',
            ];
            if ($id > 0) {
                Database::update('registration_fields', $data, 'id = :id AND event_id = :e', ['id' => $id, 'e' => $E]);
                Audit::log(Auth::id(), 'field_update', 'registration_fields', $id);
            } else {
                $data['event_id'] = $E;
                $data['fname'] = 'custom_' . substr(bin2hex(random_bytes(4)), 0, 8);
                $data['map_column'] = null;
                $max = (int) Database::fetchColumn('SELECT COALESCE(MAX(sort_order),-1) FROM ' . Database::table('registration_fields') . ' WHERE event_id = ?', [$E]);
                $data['sort_order'] = $max + 1;
                $id = Database::insert('registration_fields', $data);
                Audit::log(Auth::id(), 'field_create', 'registration_fields', $id);
            }
            json_response(['ok' => true, 'id' => $id]);
        }
        if ($op === 'delete') {
            $id = (int) ($input['id'] ?? 0);
            $row = Database::fetch('SELECT * FROM ' . Database::table('registration_fields') . ' WHERE id = ? AND event_id = ?', [$id, $E]);
            if (!$row) json_response(['ok' => false, 'error' => 'Campo não encontrado.'], 404);
            if ($row['map_column']) json_response(['ok' => false, 'error' => 'Campos padrão não podem ser excluídos — apenas desativados.'], 422);
            Database::delete('registration_answers', 'field_id = ?', [$id]);
            Database::delete('registration_fields', 'id = ?', [$id]);
            Audit::log(Auth::id(), 'field_delete', 'registration_fields', $id);
            json_response(['ok' => true]);
        }
        if ($op === 'toggle') {
            $id = (int) ($input['id'] ?? 0);
            $row = Database::fetch('SELECT active FROM ' . Database::table('registration_fields') . ' WHERE id = ? AND event_id = ?', [$id, $E]);
            if (!$row) json_response(['ok' => false], 404);
            Database::update('registration_fields', ['active' => $row['active'] ? 0 : 1], 'id = :id', ['id' => $id]);
            json_response(['ok' => true, 'active' => !$row['active']]);
        }
        json_response(['ok' => false, 'error' => 'Operação inválida.'], 404);
    }

    /** Reordenação genérica */
    private static function reorder() {
        $input = array_merge($_POST, json_input());
        $table = $input['table'] ?? '';
        $ids = $input['ids'] ?? [];
        $allowed = ['schedule_items', 'speakers', 'ticket_types', 'faqs', 'registration_fields'];
        if (!in_array($table, $allowed, true) || !is_array($ids)) {
            json_response(['ok' => false, 'error' => 'Dados inválidos.'], 422);
        }
        $E = Event::id();
        $i = 0;
        foreach ($ids as $id) {
            Database::update($table, ['sort_order' => $i++], 'id = :id AND event_id = :e', ['id' => (int) $id, 'e' => $E]);
        }
        json_response(['ok' => true]);
    }
}
