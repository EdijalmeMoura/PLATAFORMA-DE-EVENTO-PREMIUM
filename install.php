<?php
/**
 * Instalador assistido — acesse install.php após enviar os arquivos.
 * 1) Requisitos → 2) Banco de dados → 3) Admin → 4) Concluir
 */
require __DIR__ . '/config/config.php';

use App\Core\Database;
use App\Core\Seed;

if (is_file(APP_ROOT . '/storage/installed.lock') && empty($_GET['force'])) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><title>Instalação</title></head>'
        . '<body style="font-family:sans-serif;background:#080808;color:#e5e5e5;padding:60px;text-align:center;">'
        . '<h1 style="color:#C9A227;">Aplicação já instalada</h1>'
        . '<p>Para reinstalar, apague o arquivo <code>storage/installed.lock</code> e o banco de dados, e acesse novamente.</p>'
        . '<p><a href="' . e(url('admin/login')) . '" style="color:#C9A227;">Ir para o painel →</a></p>'
        . '</body></html>';
    exit;
}

$step = $_GET['step'] ?? '1';
$error = '';
$notice = '';

// ---------- Requisitos ----------
$reqs = [
    'PHP 7.4 ou superior' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'Extensão PDO' => extension_loaded('pdo'),
    'PDO SQLite ou MySQL' => extension_loaded('pdo_sqlite') || extension_loaded('pdo_mysql'),
    'mbstring' => extension_loaded('mbstring'),
    'openssl' => extension_loaded('openssl'),
    'curl' => extension_loaded('curl'),
    'json' => extension_loaded('json'),
    'fileinfo' => extension_loaded('fileinfo'),
    'Pasta storage/ gravável' => is_writable(APP_ROOT . '/storage') || @mkdir(APP_ROOT . '/storage', 0755, true),
    'Pasta uploads/ gravável' => is_writable(APP_ROOT . '/uploads') || @mkdir(APP_ROOT . '/uploads', 0755, true),
];
@mkdir(APP_ROOT . '/storage/logs', 0755, true);
@mkdir(APP_ROOT . '/uploads/general', 0755, true);
$allOk = !in_array(false, $reqs, true);

// ---------- Salva .env ----------
function write_env(array $pairs) {
    $file = APP_ROOT . '/.env';
    $lines = [];
    if (is_file($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES);
    } elseif (is_file(APP_ROOT . '/config/.env.example')) {
        $lines = file(APP_ROOT . '/config/.env.example', FILE_IGNORE_NEW_LINES);
    }
    $done = [];
    foreach ($lines as &$line) {
        foreach ($pairs as $k => $v) {
            if (strpos(trim($line), $k . '=') === 0) {
                $line = $k . '=' . $v;
                $done[$k] = true;
            }
        }
    }
    foreach ($pairs as $k => $v) {
        if (empty($done[$k])) $lines[] = $k . '=' . $v;
    }
    return @file_put_contents($file, implode("\n", $lines) . "\n") !== false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_db') {
    $driver = ($_POST['db_driver'] ?? 'sqlite') === 'mysql' ? 'mysql' : 'sqlite';
    $pairs = ['DB_DRIVER' => $driver];
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $pairs['APP_URL'] = ($https ? 'https://' : 'http://') . $host . BASE_PATH;
    if (empty(env('APP_KEY', ''))) $pairs['APP_KEY'] = bin2hex(random_bytes(24));
    if (empty(env('CRON_TOKEN', '')) || env('CRON_TOKEN') === 'troque-este-token-aqui') {
        $pairs['CRON_TOKEN'] = bin2hex(random_bytes(16));
    }
    if ($driver === 'mysql') {
        foreach (['DB_HOST' => 'host', 'DB_PORT' => 'port', 'DB_NAME' => 'name', 'DB_USER' => 'user'] as $ek => $pk) {
            $pairs[$ek] = trim($_POST[$pk] ?? '');
        }
        $pairs['DB_PASS'] = $_POST['pass'] ?? '';
        // Testa conexão
        try {
            $dsn = 'mysql:host=' . $pairs['DB_HOST'] . ';port=' . ($pairs['DB_PORT'] ?: '3306') . ';dbname=' . $pairs['DB_NAME'] . ';charset=utf8mb4';
            new PDO($dsn, $pairs['DB_USER'], $pairs['DB_PASS'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (PDOException $ex) {
            $error = 'Falha na conexão MySQL: ' . $ex->getMessage();
        }
    }
    if (!$error) {
        if (!write_env($pairs)) {
            $error = 'Não foi possível gravar o arquivo .env. Verifique as permissões.';
        } else {
            header('Location: install.php?step=2');
            exit;
        }
    }
    $step = '1';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'install') {
    $name = trim($_POST['admin_name'] ?? '');
    $email = trim($_POST['admin_email'] ?? '');
    $pass = (string) ($_POST['admin_pass'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Informe nome e e-mail válidos para o administrador.';
        $step = '2';
    } elseif (strlen($pass) < 8) {
        $error = 'A senha do administrador deve ter ao menos 8 caracteres.';
        $step = '2';
    } else {
        try {
            Database::migrate();
            Seed::run($name, $email, $pass);
            @file_put_contents(APP_ROOT . '/storage/installed.lock', date('c'));
            header('Location: install.php?step=done');
            exit;
        } catch (Exception $ex) {
            $error = 'Erro na instalação: ' . $ex->getMessage();
            $step = '2';
        }
    }
}
if (isset($_GET['step']) && $_GET['step'] === '2') $step = '2';
if (isset($_GET['step']) && $_GET['step'] === 'done') $step = 'done';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalação — Plataforma de Evento Premium</title>
<style>
  *{box-sizing:border-box} body{margin:0;background:#080808;color:#e5e5e5;font-family:Inter,Helvetica,Arial,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
  .card{width:100%;max-width:640px;background:#111;border:1px solid #262626;border-radius:16px;padding:36px;box-shadow:0 30px 80px rgba(0,0,0,.6)}
  .brand{color:#C9A227;letter-spacing:4px;font-size:12px;text-align:center}
  h1{font-family:Georgia,serif;text-align:center;font-weight:500;margin:10px 0 6px}
  p.sub{text-align:center;color:#888;font-size:14px;margin-top:0}
  .req{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #1e1e1e;font-size:14px}
  .ok{color:#7ddf8e}.fail{color:#ff7b7b}
  label{display:block;font-size:13px;color:#bbb;margin:14px 0 6px}
  input,select{width:100%;background:#0a0a0a;border:1px solid #2c2c2c;color:#fff;border-radius:8px;padding:11px 12px;font-size:15px}
  input:focus,select:focus{outline:none;border-color:#C9A227}
  .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .btn{display:block;width:100%;margin-top:22px;background:linear-gradient(135deg,#C9A227,#E7C766);color:#111;border:0;border-radius:10px;padding:14px;font-size:15px;font-weight:700;letter-spacing:1px;cursor:pointer}
  .btn:disabled{opacity:.4;cursor:not-allowed}
  .err{background:#2a1212;border:1px solid #5c2222;color:#ffb3b3;padding:12px;border-radius:8px;font-size:14px;margin-top:16px}
  .steps{display:flex;gap:8px;justify-content:center;margin:18px 0}
  .steps span{width:34px;height:4px;border-radius:2px;background:#2a2a2a}
  .steps span.on{background:#C9A227}
  a{color:#C9A227}
  @media(max-width:560px){.row{grid-template-columns:1fr}.card{padding:24px}}
</style>
</head>
<body>
<div class="card">
  <div class="brand">EVENTO PREMIUM</div>
  <h1>Instalação</h1>
  <p class="sub">Configure sua plataforma em menos de 2 minutos.</p>
  <div class="steps"><span class="<?= $step === '1' ? 'on' : '' ?>"></span><span class="<?= $step === '2' ? 'on' : '' ?>"></span><span class="<?= $step === 'done' ? 'on' : '' ?>"></span></div>

  <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>

  <?php if ($step === '1'): ?>
    <h3 style="font-size:14px;color:#C9A227;letter-spacing:2px;">REQUISITOS DO SERVIDOR</h3>
    <?php foreach ($reqs as $label => $ok): ?>
      <div class="req"><span><?= e($label) ?></span><span class="<?= $ok ? 'ok' : 'fail' ?>"><?= $ok ? '● OK' : '● FALHOU' ?></span></div>
    <?php endforeach; ?>
    <p style="font-size:12px;color:#777;">PHP atual: <?= e(PHP_VERSION) ?></p>
    <form method="post">
      <input type="hidden" name="action" value="save_db">
      <h3 style="font-size:14px;color:#C9A227;letter-spacing:2px;margin-top:26px;">BANCO DE DADOS</h3>
      <label>Tipo de banco</label>
      <select name="db_driver" id="db_driver" onchange="document.getElementById('mysql').style.display=this.value==='mysql'?'block':'none'">
        <option value="sqlite" <?= DB_DRIVER !== 'mysql' ? 'selected' : '' ?>>SQLite (recomendado — zero configuração)</option>
        <option value="mysql" <?= DB_DRIVER === 'mysql' ? 'selected' : '' ?>>MySQL (cPanel / hospedagem)</option>
      </select>
      <div id="mysql" style="display:<?= DB_DRIVER === 'mysql' ? 'block' : 'none' ?>;">
        <div class="row">
          <div><label>Host</label><input name="host" value="<?= e(DB_HOST) ?>"></div>
          <div><label>Porta</label><input name="port" value="<?= e(DB_PORT) ?>"></div>
        </div>
        <div class="row">
          <div><label>Banco</label><input name="name" value="<?= e(DB_NAME) ?>"></div>
          <div><label>Usuário</label><input name="user" value="<?= e(DB_USER) ?>"></div>
        </div>
        <label>Senha</label><input type="password" name="pass" value="<?= e(DB_PASS) ?>">
      </div>
      <button class="btn" <?= $allOk ? '' : 'disabled' ?>>CONTINUAR →</button>
      <?php if (!$allOk): ?><div class="err">Corrija os requisitos acima para continuar.</div><?php endif; ?>
    </form>
  <?php elseif ($step === '2'): ?>
    <form method="post">
      <input type="hidden" name="action" value="install">
      <h3 style="font-size:14px;color:#C9A227;letter-spacing:2px;">ADMINISTRADOR</h3>
      <label>Nome</label><input name="admin_name" required value="<?= e($_POST['admin_name'] ?? '') ?>">
      <label>E-mail de acesso</label><input type="email" name="admin_email" required value="<?= e($_POST['admin_email'] ?? 'admin@evento.com') ?>">
      <label>Senha (mín. 8 caracteres)</label><input type="password" name="admin_pass" required minlength="8">
      <p style="font-size:13px;color:#888;">Serão criados o evento de exemplo, ingressos, programação, palestrantes, FAQ, campos do formulário, templates de mensagem e automações. Tudo editável pelo painel.</p>
      <button class="btn">INSTALAR PLATAFORMA</button>
    </form>
  <?php else: ?>
    <div style="text-align:center;padding:20px 0;">
      <div style="font-size:52px;">✓</div>
      <h2 style="font-family:Georgia,serif;font-weight:500;">Instalação concluída</h2>
      <p style="color:#888;font-size:14px;">Sua plataforma está pronta. Acesse o painel e personalize o evento.</p>
      <a href="<?= e(url('admin/login')) ?>" class="btn" style="display:inline-block;width:auto;padding:14px 42px;text-decoration:none;">ACESSAR O PAINEL</a>
      <p style="margin-top:18px;font-size:13px;"><a href="<?= e(url('')) ?>">Ver a landing page →</a></p>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
