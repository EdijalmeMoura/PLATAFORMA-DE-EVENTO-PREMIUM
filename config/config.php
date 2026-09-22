<?php
/**
 * ============================================================
 *  Bootstrap da aplicação
 *  - Carrega .env, define constantes e helpers globais
 *  - Configura sessão segura, timezone e autoload PSR-4 simples
 *  Compatível com PHP 7.4+
 * ============================================================
 */

define('APP', true);
define('APP_ROOT', dirname(__DIR__));
define('APP_START', microtime(true));

// ---------- .env ----------
$envFile = APP_ROOT . '/.env';
$ENV = [];
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $pos = strpos($line, '=');
        if ($pos === false) continue;
        $k = trim(substr($line, 0, $pos));
        $v = trim(substr($line, $pos + 1));
        if (strlen($v) >= 2 && (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'"))) {
            $v = substr($v, 1, -1);
        }
        $ENV[$k] = $v;
    }
}

function env($key, $default = null) {
    global $ENV;
    if (array_key_exists($key, $ENV)) return $ENV[$key];
    $v = getenv($key);
    return $v === false ? $default : $v;
}

// ---------- Constantes ----------
define('APP_NAME', env('APP_NAME', 'Plataforma de Evento Premium'));
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', APP_ENV !== 'production');
define('APP_TIMEZONE', env('APP_TIMEZONE', 'America/Sao_Paulo'));
define('APP_KEY', env('APP_KEY', ''));
define('DB_DRIVER', strtolower(env('DB_DRIVER', 'sqlite')));
define('DB_PATH', env('DB_PATH', 'storage/database.sqlite'));
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'evento_premium'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_PREFIX', env('DB_PREFIX', ''));
define('CRON_TOKEN', env('CRON_TOKEN', ''));
define('SESSION_LIFETIME', (int) env('SESSION_LIFETIME', 120));

date_default_timezone_set(APP_TIMEZONE);
mb_internal_encoding('UTF-8');

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    @ini_set('error_log', APP_ROOT . '/storage/logs/php-error.log');
}

// ---------- URL base (suporta subdiretório) ----------
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') $scriptDir = '';
define('BASE_PATH', $scriptDir);

$envUrl = rtrim(env('APP_URL', ''), '/');
if ($envUrl !== '') {
    define('APP_URL', $envUrl);
} else {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
        || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    // Host vindo do cabeçalho: permite só caracteres válidos (anti host-injection)
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $_SERVER['HTTP_HOST'] ?? '') ?: 'localhost';
    define('APP_URL', ($https ? 'https://' : 'http://') . $host . BASE_PATH);
}

// ---------- Sessão segura ----------
$secure = (strpos(APP_URL, 'https://') === 0);
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME * 60,
    'path' => BASE_PATH . '/' ?: '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timeout por inatividade
$now = time();
if (isset($_SESSION['LAST_ACTIVITY']) && ($now - $_SESSION['LAST_ACTIVITY'] > SESSION_LIFETIME * 60)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['LAST_ACTIVITY'] = $now;

// ---------- Headers de segurança ----------
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(self), microphone=()');
    if ($secure) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// ---------- Autoload ----------
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') !== 0) return;
    $rel = str_replace('\\', '/', substr($class, 4));
    $file = APP_ROOT . '/app/' . $rel . '.php';
    if (is_file($file)) require $file;
});

require APP_ROOT . '/app/Core/Functions.php';

// ---------- Migração automática (bancos já instalados) ----------
// migrate() é idempotente (IF NOT EXISTS); roda 1x por versão de schema.
try {
    if (\App\Core\Database::getSetting('schema_v', '1') !== '3') {
        \App\Core\Database::migrate();
        \App\Core\Database::setSetting('schema_v', '3');
    }
} catch (\Exception $e) { /* banco ainda não instalado */ }
