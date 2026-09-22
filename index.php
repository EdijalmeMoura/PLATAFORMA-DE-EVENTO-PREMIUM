<?php
/**
 * Front controller + roteador.
 * Funciona com URLs amigáveis (.htaccess) ou fallback ?r=rota.
 */
require __DIR__ . '/config/config.php';

use App\Controllers\AdminApiController;
use App\Controllers\AdminCommsApiController;
use App\Controllers\AdminContentApiController;
use App\Controllers\AdminController;
use App\Controllers\ApiController;
use App\Controllers\PublicController;
use App\Core\Database;

function current_path() {
    if (!empty($_GET['r'])) return trim($_GET['r'], '/');
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $base = BASE_PATH;
    if ($base !== '' && strpos($uri, $base) === 0) {
        $uri = substr($uri, strlen($base));
    }
    $uri = trim($uri, '/');
    // Remove index.php do caminho, se presente
    if (strpos($uri, 'index.php') === 0) $uri = trim(substr($uri, strlen('index.php')), '/');
    return $uri;
}

$path = current_path();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ---------- Instalação ----------
if (!Database::isInstalled()) {
    if (strpos($path, 'install') !== 0) {
        redirect('install.php');
    }
}

// ---------- Rotas públicas ----------
if ($path === '' || $path === 'inicio') {
    PublicController::landing();
} elseif ($path === 'inscricao') {
    PublicController::register();
} elseif ($path === 'inscricao/sucesso') {
    PublicController::success();
} elseif ($path === 'meu-ticket') {
    PublicController::lookup();
} elseif (preg_match('#^ticket/([A-Za-z0-9-]+)/ics$#', $path, $m)) {
    PublicController::ics($m[1]);
} elseif (preg_match('#^ticket/([A-Za-z0-9-]+)$#', $path, $m)) {
    PublicController::ticket($m[1]);
} elseif ($path === 'termos') {
    PublicController::legal('terms');
} elseif ($path === 'privacidade') {
    PublicController::legal('privacy');
    // ---------- API pública ----------
} elseif ($path === 'api/register' && $method === 'POST') {
    ApiController::register();
} elseif ($path === 'api/event') {
    ApiController::event();
} elseif ($path === 'api/ticket/resend' && $method === 'POST') {
    ApiController::resendTicket();
} elseif (preg_match('#^api/open/(\d+)\.gif$#', $path, $m)) {
    ApiController::openBeacon($m[1]);
    // ---------- Admin: páginas ----------
} elseif ($path === 'admin/login') {
    AdminController::login();
} elseif ($path === 'admin/esqueci-senha') {
    AdminController::forgot();
} elseif ($path === 'admin/redefinir-senha') {
    AdminController::reset();
} elseif ($path === 'admin/logout') {
    AdminController::logout();
} elseif ($path === 'admin' || $path === 'admin/dashboard') {
    AdminController::dashboard();
} elseif ($path === 'admin/inscricoes') {
    AdminController::registrations();
} elseif (preg_match('#^admin/inscricoes/(\d+)$#', $path, $m)) {
    AdminController::registrationDetail((int) $m[1]);
} elseif ($path === 'admin/checkin') {
    AdminController::checkin();
} elseif ($path === 'admin/comunicacao') {
    AdminController::comms();
} elseif ($path === 'admin/programacao') {
    AdminController::schedule();
} elseif ($path === 'admin/palestrantes') {
    AdminController::speakers();
} elseif ($path === 'admin/galeria') {
    AdminController::gallery();
} elseif ($path === 'admin/ingressos') {
    AdminController::tickets();
} elseif ($path === 'admin/formulario') {
    AdminController::formFields();
} elseif ($path === 'admin/conteudo') {
    AdminController::content();
} elseif ($path === 'admin/relatorios') {
    AdminController::reports();
} elseif ($path === 'admin/usuarios') {
    AdminController::users();
} elseif ($path === 'admin/configuracoes') {
    AdminController::settings();
} elseif ($path === 'admin/auditoria') {
    AdminController::audit();
    // ---------- Admin: API ----------
} elseif (strpos($path, 'api/admin/') === 0) {
    $sub = substr($path, strlen('api/admin/'));
    if (strpos($sub, 'content/') === 0) {
        AdminContentApiController::handle(substr($sub, 8), $method);
    } elseif (strpos($sub, 'comms/') === 0) {
        AdminCommsApiController::handle(substr($sub, 6), $method);
    } else {
        AdminApiController::handle($sub, $method);
    }
} else {
    http_response_code(404);
    PublicController::notFound();
}
