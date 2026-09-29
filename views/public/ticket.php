<?php
defined('APP') or exit;
use App\Core\Csrf;
use App\Core\Icons;
$ev = $event ?? [];
$initial = mb_strtoupper(mb_substr(trim($ev['name'] ?? 'E'), 0, 1));
$ts = !empty($ev['date_start']) ? strtotime($ev['date_start']) : time();
$meses = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
$dateLong = date('d', $ts) . ' de ' . $meses[(int) date('n', $ts)] . ' de ' . date('Y', $ts);
$gcal = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
    . '&text=' . urlencode($ev['name'] ?? 'Evento')
    . '&dates=' . date('Ymd', $ts) . 'T190000/' . date('Ymd', $ts) . 'T230000'
    . '&details=' . urlencode('Código de inscrição: ' . $reg['code'])
    . '&location=' . urlencode(($ev['venue_name'] ?? '') . ' — ' . ($ev['city'] ?? '') . '/' . ($ev['state'] ?? ''));
$waText = 'Meu ticket para ' . ($ev['name'] ?? 'o evento') . ': ' . $ticket_url . ' (código ' . $reg['code'] . ')';
$cancelled = $reg['status'] === 'CANCELLED';
$extra_css = '<link rel="stylesheet" href="' . e(url('assets/css/ticket.css')) . '?v=1.0.0">';
$extra_js = '<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>'
    . '<script src="' . e(url('assets/js/ticket.js')) . '?v=1.0.0" defer></script>';
?>
<header class="header scrolled no-print" id="header">
  <div class="container header-inner">
    <?= brand_html($ev) ?>
    <a href="<?= e(url('')) ?>" class="btn btn-ghost btn-sm header-cta" style="display:inline-flex;">Início</a>
  </div>
</header>

<section class="tk-page">
  <div class="container">
    <div class="tk-head no-print">
      <span class="kicker center">TICKET DIGITAL</span>
      <h1 class="sec-title">Sua entrada para a experiência</h1>
      <p class="sec-sub" style="margin:0 auto;">Apresente o QR Code na entrada. Ele é pessoal e intransferível.</p>
    </div>

    <?php if ($cancelled): ?>
    <div class="tk-cancelled">ESTA INSCRIÇÃO FOI CANCELADA — TICKET INVÁLIDO</div>
    <?php endif; ?>

    <!-- ============ TICKET ============ -->
    <div class="ticket <?= $cancelled ? 'is-cancelled' : '' ?>" id="ticketCard">
      <div class="tk-main">
        <div class="tk-event"><?= e($ev['name']) ?></div>
        <div class="tk-label">NOME DO PARTICIPANTE</div>
        <div class="tk-name"><?= e(mb_strtoupper($reg['social_name'] ?: $reg['name'])) ?></div>
        <div class="tk-ingresso"><?= e($reg['ticket_name'] ?? '—') ?></div>
        <div class="tk-grid">
          <div><span>CÓDIGO</span><strong><?= e($reg['code']) ?></strong></div>
          <div><span>DATA</span><strong><?= e($dateLong) ?></strong></div>
          <div><span>HORÁRIO</span><strong><?= e($ev['time_text'] ?? '—') ?></strong></div>
          <div><span>LOCAL</span><strong><?= e(($ev['venue_name'] ?? '') . ' · ' . ($ev['city'] ?? '') . '—' . ($ev['state'] ?? '')) ?></strong></div>
        </div>
        <div class="tk-addr"><?= e($ev['address'] ?? '') ?></div>
      </div>
      <div class="tk-divider"><span></span><span></span></div>
      <div class="tk-stub">
        <div class="tk-qr" id="qrBox" data-payload="<?= e($qr_payload) ?>">
          <div class="tk-qr-fallback"><?= e($reg['code']) ?></div>
        </div>
        <div class="tk-stub-code"><?= e($reg['code']) ?></div>
        <div class="tk-stub-hint">APRESENTE NA ENTRADA</div>
      </div>
    </div>

    <!-- ============ AÇÕES ============ -->
    <div class="tk-actions no-print">
      <button class="btn btn-gold" id="btnPrint"><?= Icons::get('printer') ?> Baixar meu ticket</button>
      <a class="btn btn-ghost" href="<?= e(url('ticket/' . $reg['qr_token'] . '/ics')) ?>"><?= Icons::get('calendar') ?> Adicionar ao celular</a>
      <button class="btn btn-ghost" id="btnEmail"><?= Icons::get('mail') ?> Enviar por e-mail</button>
      <a class="btn btn-ghost" href="https://wa.me/?text=<?= urlencode($waText) ?>" target="_blank" rel="noopener"><?= Icons::get('whatsapp') ?> Enviar pelo WhatsApp</a>
      <button class="btn btn-ghost" id="btnQrPng"><?= Icons::get('download') ?> Baixar QR Code</button>
      <a class="btn btn-ghost" href="<?= e($gcal) ?>" target="_blank" rel="noopener"><?= Icons::get('plus') ?> Google Agenda</a>
    </div>
    <p class="tk-note no-print">Problemas com seu ticket? Fale conosco: <?= e($ev['contact_email'] ?? '') ?></p>
  </div>
</section>

<div class="toast" id="toast"></div>
<script>
window.TICKET = {
  resendUrl: '<?= e(url('api/ticket/resend')) ?>',
  csrf: '<?= e(Csrf::token()) ?>',
  code: '<?= e($reg['code']) ?>'
};
</script>
