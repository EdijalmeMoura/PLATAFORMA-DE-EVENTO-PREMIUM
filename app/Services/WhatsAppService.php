<?php
namespace App\Services;

use App\Core\Crypto;
use App\Core\Database;
use App\Core\Validator;

defined('APP') or exit;

/**
 * Integração com WhatsApp Business — API oficial (Meta Cloud API).
 * Nenhuma automação via WhatsApp Web / scraping.
 */
class WhatsAppService {
    const API_VERSION = 'v21.0';

    public static function settings($eventId) {
        $row = Database::fetch(
            'SELECT * FROM ' . Database::table('whatsapp_settings') . ' WHERE event_id = ?',
            [(int) $eventId]
        );
        return $row ?: null;
    }

    public static function status($eventId) {
        $cfg = self::settings($eventId);
        if (!$cfg || (int) $cfg['active'] !== 1) {
            return ['connected' => false, 'label' => 'Desconectado', 'number' => null];
        }
        if (!$cfg['phone_number_id'] || !Crypto::decrypt($cfg['access_token_enc'] ?? '')) {
            return ['connected' => false, 'label' => 'Configuração incompleta', 'number' => $cfg['business_number']];
        }
        return ['connected' => true, 'label' => 'Conectado', 'number' => $cfg['business_number']];
    }

    /**
     * Envia mensagem de texto transacional.
     * Retorna [ok, error].
     * Nota: mensagens iniciadas pela empresa fora da janela de 24h
     * exigem template aprovado na Meta — use $template nesses casos.
     */
    public static function send($eventId, $toPhone, $bodyText, $template = null) {
        if (!function_exists('curl_init')) {
            return [false, 'Extensão cURL do PHP indisponível na hospedagem.'];
        }
        $cfg = self::settings($eventId);
        if (!$cfg || (int) $cfg['active'] !== 1) {
            return [false, 'WhatsApp não configurado. Configure em Comunicação → WhatsApp.'];
        }
        $token = Crypto::decrypt($cfg['access_token_enc'] ?? '');
        $phoneId = trim($cfg['phone_number_id'] ?? '');
        if (!$token || !$phoneId) return [false, 'Credenciais do WhatsApp incompletas.'];
        $to = preg_replace('/\D/', '', Validator::normalizePhone($toPhone));
        if (strlen($to) < 12) return [false, 'Número de WhatsApp inválido.'];

        if ($template) {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => $cfg['template_lang'] ?: 'pt_BR'],
                ],
            ];
        } else {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['preview_url' => true, 'body' => mb_substr($bodyText, 0, 4000)],
            ];
        }

        $ch = curl_init('https://graph.facebook.com/' . self::API_VERSION . '/' . $phoneId . '/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 20,
        ]);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false) return [false, 'Falha de rede: ' . $err];
        $data = json_decode($resp, true);
        if ($http >= 200 && $http < 300 && empty($data['error'])) {
            Database::update('whatsapp_settings', ['last_status' => 'OK ' . now(), 'updated_at' => now()], 'event_id = :e', ['e' => (int) $eventId]);
            return [true, null];
        }
        $msg = $data['error']['message'] ?? ('HTTP ' . $http);
        Database::update('whatsapp_settings', ['last_status' => 'ERRO: ' . mb_substr($msg, 0, 200), 'updated_at' => now()], 'event_id = :e', ['e' => (int) $eventId]);
        return [false, 'WhatsApp: ' . $msg];
    }

    /** Link wa.me para compartilhamento (lado do participante, sem API) */
    public static function shareLink($phone, $text) {
        $to = preg_replace('/\D/', '', Validator::normalizePhone($phone));
        return 'https://wa.me/' . $to . '?text=' . rawurlencode($text);
    }
}
