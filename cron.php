<?php
/**
 * Cron — processa fila de mensagens + lembretes por data.
 * Configure no painel da hospedagem (cPanel → Cron Jobs), ex. a cada 5 min:
 *   php /caminho/do/site/cron.php SEU_TOKEN
 * ou via URL: https://seudominio.com/cron.php?token=SEU_TOKEN
 */
require __DIR__ . '/config/config.php';

use App\Core\Database;
use App\Services\MessageService;

$token = $_GET['token'] ?? ($argv[1] ?? '');
if (CRON_TOKEN === '' || !hash_equals(CRON_TOKEN, (string) $token)) {
    http_response_code(403);
    echo "Token inválido.\n";
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
$event = Database::fetch('SELECT id FROM ' . Database::table('events') . ' ORDER BY id ASC LIMIT 1');
if (!$event) { echo "Nenhum evento.\n"; exit; }
$E = (int) $event['id'];

$reminders = MessageService::scheduleDateReminders($E);
$r = MessageService::processQueue($E, 100);

// Limpeza: rate limits antigos
try {
    Database::query("DELETE FROM " . Database::table('rate_limits') . " WHERE window_start < datetime('now','-1 day')");
} catch (Exception $e) {
    try {
        Database::query("DELETE FROM " . Database::table('rate_limits') . " WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    } catch (Exception $e2) { /* ignora */ }
}

echo "Lembretes agendados: $reminders\n";
echo "Fila — processados: {$r['processed']}, enviados: {$r['sent']}, falhas: {$r['failed']}\n";
