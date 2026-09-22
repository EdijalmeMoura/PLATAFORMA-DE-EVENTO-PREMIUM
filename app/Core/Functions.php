<?php
/**
 * Funções auxiliares globais.
 */
defined('APP') or exit;

/** URL absoluta da aplicação + caminho */
function url($path = '') {
    $path = ltrim($path, '/');
    return rtrim(APP_URL, '/') . ($path !== '' ? '/' . $path : '');
}

/** Escape HTML (proteção XSS) */
function e($value) {
    if ($value === null) return '';
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Data/hora atual no formato do banco */
function now($format = 'Y-m-d H:i:s') {
    return date($format);
}

/** Formata data pt-BR */
function fdate($date, $withTime = false) {
    if (!$date) return '—';
    $ts = is_numeric($date) ? (int) $date : strtotime((string) $date);
    if (!$ts) return '—';
    return $withTime ? date('d/m/Y H:i', $ts) : date('d/m/Y', $ts);
}

/** Moeda BRL */
function money($value) {
    if ($value === null || $value === '') return '—';
    if ((float) $value <= 0) return 'Grátis';
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

/** Gera string aleatória segura */
function random_string($length = 32) {
    $bytes = random_bytes((int) ceil($length / 2));
    return substr(bin2hex($bytes), 0, $length);
}

/** Slug simples */
function slug($text) {
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $text);
    $text = strtolower((string) $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/** IP do visitante (básico) */
function client_ip() {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = trim(explode(',', $_SERVER[$k])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

/** É requisição AJAX/fetch JSON? */
function is_json_request() {
    $ct = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
    if (stripos($ct, 'application/json') !== false) return true;
    return (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
        || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
}

/** Lê corpo JSON */
function json_input() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/** Redireciona */
function redirect($path, $status = 302) {
    $to = preg_match('#^https?://#i', $path) ? $path : url($path);
    header('Location: ' . $to, true, $status);
    exit;
}

/** Resposta JSON + exit */
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Trunca texto */
function excerpt($text, $len = 120) {
    $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)));
    if (mb_strlen($text) <= $len) return $text;
    return mb_substr($text, 0, $len - 1) . '…';
}

/** Status da inscrição → rótulo */
function registration_status_label($status) {
    $map = [
        'PENDING' => 'Pendente',
        'CONFIRMED' => 'Confirmada',
        'CANCELLED' => 'Cancelada',
        'CHECKED_IN' => 'Check-in realizado',
    ];
    return $map[$status] ?? $status;
}

/** Status pagamento → rótulo */
function payment_status_label($status) {
    $map = [
        'PENDING' => 'Pendente',
        'APPROVED' => 'Aprovado',
        'REFUSED' => 'Recusado',
        'CANCELLED' => 'Cancelado',
        'FREE' => 'Isento',
    ];
    return $map[$status] ?? $status;
}

/** Saudação por horário */
function greeting() {
    $h = (int) date('H');
    if ($h >= 5 && $h < 12) return 'Bom dia';
    if ($h >= 12 && $h < 18) return 'Boa tarde';
    return 'Boa noite';
}

/**
 * HTML da logomarca do evento.
 * SVG é embutido inline (nitidez total); demais formatos via <img>.
 */
function brand_logo($logoPath, $fallbackInitial = 'E', $height = 44) {
    $logoPath = trim((string) $logoPath);
    if ($logoPath === '') {
        return '<span class="brand-mark">' . e($fallbackInitial) . '</span>';
    }
    $h = (int) $height;
    if (substr(strtolower($logoPath), -4) === '.svg') {
        $full = APP_ROOT . '/' . ltrim($logoPath, '/');
        if (is_file($full) && filesize($full) < 200000) {
            $svg = (string) @file_get_contents($full);
            $svg = preg_replace('#<\s*script[^>]*>.*?<\s*/\s*script\s*>#is', '', $svg);
            $svg = preg_replace('/<svg\b/', '<svg height="' . $h . '"', $svg, 1);
            return '<span class="brand-logo">' . $svg . '</span>';
        }
    }
    return '<span class="brand-logo"><img src="' . e(url($logoPath)) . '" alt="Logo" style="height:' . $h . 'px;"></span>';
}

/** Marca completa do cabeçalho/rodapé (logo ou monograma + nome). */
function brand_html($ev) {
    $name = $ev['name'] ?? 'Evento';
    $initial = mb_strtoupper(mb_substr(trim($name), 0, 1));
    $html = '<a href="' . e(url('')) . '" class="brand" aria-label="' . e($name) . '">';
    if (!empty($ev['logo'])) {
        $html .= brand_logo($ev['logo'], $initial, 46);
    } else {
        $html .= '<span class="brand-mark">' . e($initial) . '</span>'
            . '<span class="brand-name">' . e(strtok($name, '·—-')) . '<small>EXPERIÊNCIA PREMIUM</small></span>';
    }
    return $html . '</a>';
}
