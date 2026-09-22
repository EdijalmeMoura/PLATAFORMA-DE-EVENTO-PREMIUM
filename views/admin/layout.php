<?php
defined('APP') or exit;
use App\Core\Auth;
use App\Core\Icons;
$u = $admin_user ?? Auth::user();
$pg = $page ?? 'dashboard';
$can = function ($p) { return Auth::can($p); };
$menu = [
    ['dashboard', 'Dashboard', 'grid', 'admin', 'dashboard.view'],
    ['inscricoes', 'Inscrições', 'users', 'admin/inscricoes', 'registrations.view'],
    ['checkin', 'Check-in', 'scan', 'admin/checkin', 'checkin.use'],
    ['comunicacao', 'Comunicação', 'send', 'admin/comunicacao', 'comms.view'],
    ['programacao', 'Programação', 'list', 'admin/programacao', 'content.manage'],
    ['palestrantes', 'Palestrantes', 'mic', 'admin/palestrantes', 'content.manage'],
    ['galeria', 'Galeria', 'image', 'admin/galeria', 'content.manage'],
    ['ingressos', 'Ingressos', 'ticket', 'admin/ingressos', 'content.manage'],
    ['formulario', 'Formulário', 'doc', 'admin/formulario', 'content.manage'],
    ['conteudo', 'Conteúdo', 'image', 'admin/conteudo', 'content.manage'],
    ['relatorios', 'Relatórios', 'chart', 'admin/relatorios', 'reports.view'],
    ['usuarios', 'Usuários', 'users-cog', 'admin/usuarios', 'users.manage'],
    ['configuracoes', 'Configurações', 'cog', 'admin/configuracoes', 'event.manage'],
    ['auditoria', 'Auditoria', 'shield', 'admin/auditoria', 'audit.view'],
];
$initial = mb_strtoupper(mb_substr(trim($u['name'] ?? 'A'), 0, 1));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title ?? 'Painel') ?> — <?= e($event['name'] ?? 'Evento') ?></title>
<meta name="theme-color" content="#0a0a0a">
<meta name="csrf-token" content="<?= e($csrf ?? '') ?>">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%23080808'/%3E%3Ctext x='32' y='44' font-family='Georgia' font-size='30' fill='%23C9A227' text-anchor='middle'%3EP%3C/text%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Cormorant+Garamond:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>?v=1.0.0">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="admin">
  <!-- Sidebar -->
  <aside class="side" id="side">
    <a href="<?= e(url('admin')) ?>" class="side-brand">
      <span class="side-mark"><?= e(mb_strtoupper(mb_substr(trim($event['name'] ?? 'E'), 0, 1))) ?></span>
      <span class="side-brand-tx"><strong><?= e(strtok($event['name'] ?? 'Evento', '·—-')) ?></strong><small>PAINEL PREMIUM</small></span>
    </a>
    <nav class="side-nav">
      <?php foreach ($menu as $m): if (!$can($m[4])) continue; ?>
        <a href="<?= e(url($m[3])) ?>" class="<?= $pg === $m[0] ? 'active' : '' ?>"><?= Icons::get($m[2]) ?><span><?= e($m[1]) ?></span></a>
      <?php endforeach; ?>
    </nav>
    <div class="side-foot">
      <a href="<?= e(url('')) ?>" target="_blank"><?= Icons::get('globe') ?><span>Ver site</span></a>
      <a href="<?= e(url('admin/logout')) ?>"><?= Icons::get('logout') ?><span>Sair</span></a>
    </div>
  </aside>
  <div class="side-overlay" id="sideOverlay"></div>

  <!-- Main -->
  <div class="main">
    <header class="top">
      <button class="icon-btn only-mobile" id="btnSide" aria-label="Menu"><?= Icons::get('menu') ?></button>
      <div class="top-title">
        <h1><?= e($page_title ?? 'Painel') ?></h1>
        <span><?= e(greeting()) ?>, <?= e(strtok($u['name'] ?? '', ' ')) ?> · <?= e($u['role_name'] ?? '') ?></span>
      </div>
      <div class="top-actions">
        <?php if ($can('registrations.view')): ?>
        <div class="top-search"><?= Icons::get('search') ?><input id="globalSearch" placeholder="Buscar inscrito…" autocomplete="off"></div>
        <?php endif; ?>
        <div class="notif-wrap">
          <button class="icon-btn notif-btn" id="btnNotif" title="Notificações" style="position:relative;"><?= Icons::get('bell') ?><span class="notif-badge" id="notifBadge" style="display:none;">0</span></button>
          <div class="notif-drop" id="notifDrop">
            <div class="notif-head">Notificações</div>
            <div id="notifList"><div class="empty" style="padding:24px;">Carregando…</div></div>
          </div>
        </div>
        <button class="user-chip" id="userChip" title="Meu perfil">
          <span class="avatar"><?= e($initial) ?></span>
          <span class="user-chip-tx"><strong><?= e(strtok($u['name'] ?? '', ' ')) ?></strong><small><?= e($u['role_name'] ?? '') ?></small></span>
        </button>
      </div>
    </header>

    <?php if (!empty($u['must_change_password'])): ?>
    <div class="alert alert-warn container-pad">
      <?= Icons::get('alert') ?>
      <span><strong>Segurança:</strong> você está usando a senha inicial. <a href="#" id="linkChangePass">Defina uma nova senha agora</a>.</span>
    </div>
    <?php endif; ?>

    <main class="content">
      <?= $__content ?? '' ?>
    </main>
    <footer class="admin-foot"><?= e($event['name'] ?? '') ?> · Plataforma de Evento Premium</footer>
  </div>
</div>

<!-- Modal perfil -->
<div class="modal" id="modalProfile">
  <div class="modal-card">
    <div class="modal-head"><h3>Meu perfil</h3><button class="icon-btn" data-close><?= Icons::get('x') ?></button></div>
    <div class="modal-body">
      <div class="field"><label>Nome</label><input id="pf-name" value="<?= e($u['name'] ?? '') ?>"></div>
      <div class="field"><label>E-mail (login)</label><input value="<?= e($u['email'] ?? '') ?>" disabled></div>
      <div class="field"><label>Senha atual (para trocar)</label><input type="password" id="pf-current" autocomplete="current-password"></div>
      <div class="field"><label>Nova senha (mín. 8 caracteres)</label><input type="password" id="pf-new" autocomplete="new-password"></div>
    </div>
    <div class="modal-foot">
      <button class="btn-ad btn-ghost-ad" data-close>Cancelar</button>
      <button class="btn-ad btn-gold-ad" id="pf-save">Salvar</button>
    </div>
  </div>
</div>

<div class="modal" id="modalConfirm">
  <div class="modal-card modal-sm">
    <div class="modal-body" style="text-align:center;padding:34px 28px;">
      <div class="confirm-ic" id="confirmIcon"><?= Icons::get('alert') ?></div>
      <h3 id="confirmTitle" style="margin-bottom:8px;">Confirmar ação</h3>
      <p id="confirmText" style="color:var(--ad-muted);font-size:14.5px;"></p>
      <div style="display:flex;gap:10px;justify-content:center;margin-top:22px;">
        <button class="btn-ad btn-ghost-ad" data-close>Cancelar</button>
        <button class="btn-ad btn-gold-ad" id="confirmYes">Confirmar</button>
      </div>
    </div>
  </div>
</div>

<div class="toast-wrap" id="toasts"></div>
<script>
window.ADMIN = {
  base: '<?= e(url('')) ?>',
  api: '<?= e(url('api/admin')) ?>',
  csrf: '<?= e($csrf ?? '') ?>'
};
</script>
<script src="<?= e(url('assets/js/admin.js')) ?>?v=1.0.0"></script>
<?php if (!empty($extra_js)) echo $extra_js; ?>
</body>
</html>
