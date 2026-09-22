<?php
namespace App\Services;

use App\Core\Crypto;
use App\Core\Database;

defined('APP') or exit;

/**
 * Envio de e-mail via SMTP próprio (sem dependências),
 * com fallback para mail() nativo.
 */
class EmailService {
    public static function settings($eventId) {
        $row = Database::fetch(
            'SELECT * FROM ' . Database::table('email_settings') . ' WHERE event_id = ?',
            [(int) $eventId]
        );
        return $row ?: null;
    }

    /** Renderiza corpo em template HTML premium */
    public static function renderHtml($eventName, $subject, $bodyText, $openBeaconUrl = null) {
        $body = nl2br(e($bodyText));
        // Auto-link de URLs
        $body = preg_replace(
            '#(https?://[^\s<]+)#i',
            '<a href="$1" style="color:#C9A227;word-break:break-all;">$1</a>',
            $body
        );
        $beacon = $openBeaconUrl
            ? '<img src="' . e($openBeaconUrl) . '" width="1" height="1" alt="" style="display:block;">'
            : '';
        return '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"></head>'
            . '<body style="margin:0;padding:0;background:#0a0a0a;font-family:Helvetica,Arial,sans-serif;">'
            . '<div style="max-width:600px;margin:0 auto;padding:32px 20px;">'
            . '<div style="text-align:center;padding:24px 0;border-bottom:1px solid #2a2a2a;">'
            . '<div style="color:#C9A227;font-size:13px;letter-spacing:4px;">' . e(mb_strtoupper($eventName)) . '</div>'
            . '<div style="color:#fff;font-size:22px;margin-top:8px;font-family:Georgia,serif;">' . e($subject) . '</div>'
            . '</div>'
            . '<div style="color:#e5e5e5;font-size:15px;line-height:1.7;padding:28px 8px;">' . $body . '</div>'
            . '<div style="border-top:1px solid #2a2a2a;padding-top:16px;color:#888;font-size:12px;text-align:center;">'
            . 'Esta é uma mensagem transacional do evento. Por favor, não responda.<br>' . e($eventName)
            . '</div></div>' . $beacon . '</body></html>';
    }

    /** Envia e-mail. Retorna [ok, error] */
    public static function send($eventId, $toEmail, $subject, $bodyText, $beaconQueueId = null) {
        $toEmail = trim($toEmail);
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) return [false, 'E-mail de destino inválido.'];
        $cfg = self::settings($eventId);
        $beacon = $beaconQueueId ? url('api/open/' . $beaconQueueId . '.gif') : null;
        $event = Database::fetch('SELECT name FROM ' . Database::table('events') . ' WHERE id = ?', [(int) $eventId]);
        $html = self::renderHtml($event['name'] ?? 'Evento', $subject, $bodyText, $beacon);
        $text = $bodyText;

        if ($cfg && (int) $cfg['active'] === 1 && $cfg['host']) {
            return self::sendSmtp($cfg, $toEmail, $subject, $text, $html);
        }
        return [false, 'SMTP não configurado. Configure em Comunicação → E-mail.'];
    }

    /** Testa conexão SMTP sem enviar */
    public static function testConnection(array $cfg) {
        $sock = self::connect($cfg);
        if (!$sock['ok']) return $sock;
        fclose($sock['fp']);
        return [true, 'Conexão SMTP estabelecida com sucesso.'];
    }

    private static function connect(array $cfg) {
        $host = trim($cfg['host']);
        $port = (int) ($cfg['port'] ?: 587);
        $enc = strtolower($cfg['encryption'] ?? 'tls');
        $timeout = 15;
        $remote = ($enc === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $errno = 0; $errstr = '';
        $fp = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, stream_context_create([
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]));
        if (!$fp) return ['ok' => false, 'error' => "Falha ao conectar em $host:$port ($errstr)"];
        stream_set_timeout($fp, $timeout);
        $read = function () use ($fp) {
            $out = '';
            while (($line = fgets($fp, 515)) !== false) {
                $out .= $line;
                if (preg_match('/^\d{3} /', $line)) break;
            }
            return $out;
        };
        $greet = $read();
        if (strpos($greet, '220') !== 0) { fclose($fp); return ['ok' => false, 'error' => 'Servidor não respondeu: ' . trim($greet)]; }
        $hello = gethostname() ?: 'localhost';
        fwrite($fp, "EHLO $hello\r\n");
        $ehlo = $read();
        if ($enc === 'tls' && stripos($ehlo, 'STARTTLS') !== false) {
            fwrite($fp, "STARTTLS\r\n");
            $r = $read();
            if (strpos($r, '220') !== 0) { fclose($fp); return ['ok' => false, 'error' => 'STARTTLS recusado.']; }
            if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($fp); return ['ok' => false, 'error' => 'Falha ao ativar TLS.'];
            }
            fwrite($fp, "EHLO $hello\r\n");
            $read();
        }
        $user = trim($cfg['username'] ?? '');
        if ($user !== '') {
            $pass = Crypto::decrypt($cfg['password_enc'] ?? '');
            fwrite($fp, "AUTH LOGIN\r\n");
            $r = $read();
            if (strpos($r, '334') !== 0) { fclose($fp); return ['ok' => false, 'error' => 'AUTH não suportado.']; }
            fwrite($fp, base64_encode($user) . "\r\n");
            $read();
            fwrite($fp, base64_encode($pass) . "\r\n");
            $r = $read();
            if (strpos($r, '235') !== 0) { fclose($fp); return ['ok' => false, 'error' => 'Usuário/senha SMTP rejeitados.']; }
        }
        return ['ok' => true, 'fp' => $fp];
    }

    private static function sendSmtp(array $cfg, $to, $subject, $text, $html) {
        $c = self::connect($cfg);
        if (!$c['ok']) return [false, $c['error']];
        $fp = $c['fp'];
        $read = function () use ($fp) {
            $out = '';
            while (($line = fgets($fp, 515)) !== false) {
                $out .= $line;
                if (preg_match('/^\d{3} /', $line)) break;
            }
            return $out;
        };
        $from = trim($cfg['from_email'] ?? '') ?: trim($cfg['username'] ?? '');
        $fromName = trim($cfg['from_name'] ?? '') ?: 'Evento';
        $boundary = '=_' . md5(uniqid('', true));
        $headers = '';
        $headers .= 'From: ' . self::encodeHeader($fromName) . " <$from>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= 'Subject: ' . self::encodeHeader($subject) . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $body = "--$boundary\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n"
            . $text . "\r\n\r\n"
            . "--$boundary\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n"
            . $html . "\r\n\r\n--$boundary--\r\n";
        fwrite($fp, "MAIL FROM:<$from>\r\n"); $r = $read();
        if (strpos($r, '250') !== 0) { fclose($fp); return [false, 'MAIL FROM recusado.']; }
        fwrite($fp, "RCPT TO:<$to>\r\n"); $r = $read();
        if (strpos($r, '250') !== 0 && strpos($r, '251') !== 0) { fclose($fp); return [false, 'Destinatário recusado.']; }
        fwrite($fp, "DATA\r\n"); $r = $read();
        if (strpos($r, '354') !== 0) { fclose($fp); return [false, 'DATA recusado.']; }
        // Dot-stuffing
        $data = $headers . "\r\n" . $body;
        $data = preg_replace('/^\./m', '..', $data);
        fwrite($fp, $data . "\r\n.\r\n"); $r = $read();
        fwrite($fp, "QUIT\r\n");
        fclose($fp);
        if (strpos($r, '250') !== 0) return [false, 'Envio recusado: ' . trim($r)];
        return [true, null];
    }

    private static function encodeHeader($text) {
        if (!preg_match('/[^\x20-\x7E]/', $text)) return $text;
        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }
}
