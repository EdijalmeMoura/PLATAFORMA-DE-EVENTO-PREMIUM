<?php
namespace App\Core;

defined('APP') or exit;

/**
 * Ícones SVG minimalistas (stroke 1.5, 24x24).
 * Uso: Icons::get('users', 'cls')
 */
class Icons {
    private static $paths = [
        'spark' => '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/><path d="M19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9z"/>',
        'users' => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19c.6-3.2 2.8-5 5.5-5s4.9 1.8 5.5 5"/><circle cx="17" cy="9" r="2.4"/><path d="M15.5 14.2c2.9-.4 5 1.2 5.5 4"/>',
        'book' => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5z"/><path d="M4 20.5V5.5"/><path d="M20 18v3H6.5"/>',
        'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r="1" fill="currentColor"/>',
        'mic' => '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0"/><path d="M12 18v3"/>',
        'star' => '<path d="M12 3.5l2.6 5.3 5.9.9-4.2 4.1 1 5.8-5.3-2.8-5.3 2.8 1-5.8L3.5 9.7l5.9-.9z"/>',
        'dish' => '<circle cx="12" cy="12" r="3"/><path d="M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20M2 12h2M20 12h2"/>',
        'diamond' => '<path d="M7 3h10l4 6-9 12L3 9z"/><path d="M3 9h18M9.5 3L12 9l2.5-6M12 9v12"/>',
        'ticket' => '<path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4z"/><path d="M14 6v2M14 11v2M14 16v2"/>',
        'calendar' => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M8 3v4M16 3v4"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3.5 2"/>',
        'pin' => '<path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'check' => '<path d="M4.5 12.5l5 5 10-11"/>',
        'check-circle' => '<circle cx="12" cy="12" r="8.5"/><path d="M8.5 12.2l2.5 2.5 4.5-5"/>',
        'x' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'x-circle' => '<circle cx="12" cy="12" r="8.5"/><path d="M9 9l6 6M15 9l-6 6"/>',
        'alert' => '<path d="M12 3.5L22 20H2z"/><path d="M12 10v4M12 17.2v.3"/>',
        'info' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 7.8v.3"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 7l8.5 6 8.5-6"/>',
        'chat' => '<path d="M4 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H9l-5 4z"/>',
        'send' => '<path d="M21 3L10 14"/><path d="M21 3l-7 18-4-7-7-4z"/>',
        'qr' => '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><path d="M13 13h3v3h-3zM16 16h4v4h-4z"/>',
        'scan' => '<path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3"/><path d="M4 12h16"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="M16 16l5 5"/>',
        'filter' => '<path d="M4 5h16l-6 7v5l-4 2v-7z"/>',
        'download' => '<path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M4 19h16"/>',
        'upload' => '<path d="M12 15V3"/><path d="M7 8l5-5 5 5"/><path d="M4 19h16"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'edit' => '<path d="M4 20l1-4L16.5 4.5a2.1 2.1 0 0 1 3 3L8 19z"/><path d="M14.5 6.5l3 3"/>',
        'trash' => '<path d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"/>',
        'eye' => '<path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12z"/><circle cx="12" cy="12" r="2.8"/>',
        'user' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c.8-3.8 3.4-6 7-6s6.2 2.2 7 6"/>',
        'users-cog' => '<circle cx="9" cy="8" r="3"/><path d="M3.5 19c.6-3 2.6-4.7 5.5-4.7 1 0 2 .2 2.8.6"/><circle cx="17" cy="17" r="2.5"/><path d="M17 12.5v2M17 19.5v2M12.5 17h2M19.5 17h2"/>',
        'grid' => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-8M21 20H3"/>',
        'list' => '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="5" cy="6" r="1.2"/><circle cx="5" cy="12" r="1.2"/><circle cx="5" cy="18" r="1.2"/>',
        'cog' => '<circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1.2l2-1.5-2-3.4-2.3 1a7 7 0 0 0-2-1.2L14.2 3h-4L9.7 5.7a7 7 0 0 0-2 1.2l-2.3-1-2 3.4 2 1.5a7 7 0 0 0 0 2.4l-2 1.5 2 3.4 2.3-1a7 7 0 0 0 2 1.2l.4 2.7h4l.4-2.7a7 7 0 0 0 2-1.2l2.3 1 2-3.4-2-1.5c.1-.4.1-.8.1-1.2z"/>',
        'logout' => '<path d="M14 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8"/><path d="M10 12h11M18 8l4 4-4 4"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'chev-down' => '<path d="M6 9l6 6 6-6"/>',
        'chev-right' => '<path d="M9 6l6 6-6 6"/>',
        'arrow-right' => '<path d="M4 12h15M13 6l6 6-6 6"/>',
        'arrow-left' => '<path d="M20 12H5M11 6l-6 6 6 6"/>',
        'bell' => '<path d="M6 16v-5a6 6 0 0 1 12 0v5l1.5 2.5h-15z"/><path d="M10 21a2 2 0 0 0 4 0"/>',
        'shield' => '<path d="M12 3l7.5 3v6c0 4.5-3 7.5-7.5 9-4.5-1.5-7.5-4.5-7.5-9V6z"/><path d="M9 12l2 2 4-4"/>',
        'doc' => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 16h6"/>',
        'image' => '<rect x="4" y="4" width="16" height="16" rx="2"/><circle cx="9" cy="9" r="1.8"/><path d="M4 17l5-5 3 3 3-3 5 5"/>',
        'link' => '<path d="M10 14a4 4 0 0 0 6 0l3-3a4 4 0 0 0-6-6l-1.5 1.5"/><path d="M14 10a4 4 0 0 0-6 0l-3 3a4 4 0 0 0 6 6L12.5 18"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
        'clock-alert' => '<circle cx="12" cy="13" r="7.5"/><path d="M12 9.5V13l2.5 1.5M12 2.5v2M9 3.5v.5M15 3.5v.5"/>',
        'refresh' => '<path d="M20 12a8 8 0 1 1-2.3-5.6"/><path d="M20 3v4h-4"/>',
        'drag' => '<circle cx="9" cy="6" r="1.2"/><circle cx="15" cy="6" r="1.2"/><circle cx="9" cy="12" r="1.2"/><circle cx="15" cy="12" r="1.2"/><circle cx="9" cy="18" r="1.2"/><circle cx="15" cy="18" r="1.2"/>',
        'instagram' => '<rect x="4" y="4" width="16" height="16" rx="4.5"/><circle cx="12" cy="12" r="3.5"/><circle cx="16.8" cy="7.2" r="1" fill="currentColor"/>',
        'linkedin' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 10.5V17M8 7.5v.3M12 17v-3.8a1.8 1.8 0 0 1 3.6 0V17"/>',
        'whatsapp' => '<path d="M12 3.5a8.5 8.5 0 0 0-7.3 12.8L3.5 20.5l4.3-1.1A8.5 8.5 0 1 0 12 3.5z"/><path d="M9 9.2c.3 2.6 3.2 5.5 5.8 5.8l1-1.7 2 1c-.3 1.7-1.4 2.4-3 2-3.4-.9-6.9-4.4-7.8-7.8-.4-1.6.3-2.7 2-3l1 2z"/>',
        'copy' => '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a1 1 0 0 1 1-1h9"/>',
        'printer' => '<path d="M7 8V3h10v5"/><rect x="4" y="8" width="16" height="9" rx="2"/><rect x="7" y="14" width="10" height="7"/>',
        'file-csv' => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4"/>',
        'ban' => '<circle cx="12" cy="12" r="8.5"/><path d="M6 6l12 12"/>',
        'history' => '<path d="M4 12a8 8 0 1 1 2.3 5.6"/><path d="M4 12H2.5M4 12l-2-3"/><path d="M12 8v4l3 2"/>',
        'zap' => '<path d="M13 2L4 14h6l-1 8 9-12h-6z"/>',
        'globe' => '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17M12 3.5c-4.5 4.5-4.5 12.5 0 17M12 3.5c4.5 4.5 4.5 12.5 0 17"/>',
        'lock' => '<rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
    ];

    public static function get($name, $class = '') {
        $p = self::$paths[$name] ?? self::$paths['info'];
        $cls = $class !== '' ? ' ' . $class : '';
        return '<svg class="ic' . e($cls) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
    }
}
