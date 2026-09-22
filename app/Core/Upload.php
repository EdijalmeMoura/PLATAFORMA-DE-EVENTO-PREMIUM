<?php
namespace App\Core;

defined('APP') or exit;

/** Uploads de imagens (logo, fotos, hero) com validação. */
class Upload {
    const MAX_SIZE = 5 * 1024 * 1024; // 5MB
    const ALLOWED = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif', 'svg' => 'image/svg+xml'];

    public static function image($file, $folder = 'general') {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return [false, 'Falha no envio do arquivo.'];
        }
        if ($file['size'] > self::MAX_SIZE) {
            return [false, 'Arquivo maior que 5MB.'];
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED[$ext])) {
            return [false, 'Formato não permitido. Use JPG, PNG, WEBP, GIF ou SVG.'];
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if ($mime !== self::ALLOWED[$ext] && !($ext === 'jpg' && $mime === 'image/jpeg')) {
            // SVG pode variar; valida conteúdo básico
            if ($ext !== 'svg' || stripos($mime, 'svg') === false && $mime !== 'text/xml' && $mime !== 'text/plain') {
                return [false, 'Arquivo inválido.'];
            }
        }
        if ($ext === 'svg') {
            $content = file_get_contents($file['tmp_name']);
            if (preg_match('#<\s*script#i', $content)) return [false, 'SVG com conteúdo bloqueado.'];
        }
        $folder = preg_replace('/[^a-z0-9_-]/i', '', $folder) ?: 'general';
        $dir = APP_ROOT . '/uploads/' . $folder;
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $name = date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return [false, 'Não foi possível salvar o arquivo.'];
        }
        return [true, 'uploads/' . $folder . '/' . $name];
    }

    public static function delete($relativePath) {
        if (!$relativePath) return;
        $full = APP_ROOT . '/' . ltrim($relativePath, '/');
        if (strpos(realpath($full) ?: $full, APP_ROOT . '/uploads') === 0 && is_file($full)) {
            @unlink($full);
        }
    }
}
