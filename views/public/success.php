<?php
defined('APP') or exit;
use App\Core\Csrf;
use App\Core\Icons;
$ev = $event ?? [];
$c = function ($sec, $key, $d = '') use ($content) { return $content[$sec][$key] ?? $d; };
$initial = mb_strtoupper(mb_substr(trim($ev['name'] ?? 'E'), 0, 1));
$ts = !empty($ev['date_start']) ? strtotime($ev['date_start']) : time();
$meses = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
$dateLong = date('d', $ts) . ' de ' . $meses[(int) date('n', $ts)] . ' de ' . date('Y', $ts);
$gcal = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
    . '&text=' . urlencode($ev['name'] ?? 'Evento')
    . '&dates=' . date('Ymd', $ts) . 'T190000/' . date('Ymd', $ts) . 'T230000'
    . '&details=' . urlencode('Código de inscrição: ' . $reg['code'])
    . '&location=' . urlencode(($ev['venue_name'] ?? '') . ' — ' . ($ev['city'] ?? '') . '/' . ($ev['state'] ?? ''));
$waText = 'Acabei de me inscrever no ' . ($ev['name'] ?? 'evento') . '! Meu ticket: ' . $ticket_url;
?>
<header class="header scrolled" id="header">
  <div class="container header-inner">
    <?= brand_html($ev) ?>
  </div>
</header>

<section class="page-hero">
  <div class="container success-wrap">
    <div class="success-icon"><?= Icons::get('check') ?></div>
    <h1 class="success-title"><?= e($c('register', 'success_title', 'Inscrição confirmada')) ?></h1>
    <p class="success-sub"><?= e($c('register', 'success_message', 'Sua participação está confirmada.')) ?></p>

    <div class="success-code">
      <small>CÓDIGO DA INSCRIÇÃO</small>
      <strong><?= e($reg['code']) ?></strong>
    </div>

    <div class="success-meta">
      <div><small>PARTICIPANTE</small><?= e($reg['social_name'] ?: $reg['name']) ?></div>
      <div><small>INGRESSO</small><?= e($reg['ticket_name'] ?? '—') ?></div>
      <div><small>DATA</small><?= e($dateLong) ?></div>
      <div><small>LOCAL</small><?= e(($ev['venue_name'] ?? '') . ' · ' . ($ev['city'] ?? '') . '/' . ($ev['state'] ?? '')) ?></div>
    </div>

    <div class="success-btns">
      <a href="<?= e($ticket_url) ?>" class="btn btn-gold"><?= Icons::get('ticket') ?> Ver meu ticket</a>
      <a href="<?= e(url('ticket/' . $reg['qr_token'] . '/ics')) ?>" class="btn btn-ghost"><?= Icons::get('calendar') ?> Adicionar à agenda</a>
      <a href="https://wa.me/?text=<?= urlencode($waText) ?>" target="_blank" rel="noopener" class="btn btn-ghost"><?= Icons::get('whatsapp') ?> Enviar pelo WhatsApp</a>
      <button class="btn btn-ghost" id="btnEmail"><?= Icons::get('mail') ?> Enviar por e-mail</button>
    </div>

    <p class="success-note"><?= e($c('register', 'success_note', 'Enviamos os detalhes da sua inscrição para seu e-mail.')) ?></p>
    <p class="success-note" style="margin-top:6px;"><a href="<?= e($gcal) ?>" target="_blank" rel="noopener">＋ Adicionar ao Google Agenda</a></p>
  </div>
</section>

<footer>
  <div class="container">
    <div class="foot-bottom" style="margin-top:0;border:0;">
      <span><?= e($ev['name']) ?></span>
      <span><a href="<?= e(url('')) ?>">Voltar ao início</a></span>
    </div>
  </div>
</footer>

<div class="toast" id="toast"></div>
<script>
document.getElementById('btnEmail').addEventListener('click', function () {
  var btn = this;
  btn.disabled = true;
  var fd = new FormData();
  fd.append('csrf_token', '<?= e(Csrf::token()) ?>');
  fd.append('code', '<?= e($reg['code']) ?>');
  fd.append('channel', 'email');
  fetch('<?= e(url('api/ticket/resend')) ?>', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function (r) { return r.json(); })
    .then(function (j) { toast(j.ok ? 'E-mail enviado com sucesso!' : (j.error || 'Falha ao enviar.')); btn.disabled = false; })
    .catch(function () { toast('Falha de conexão.'); btn.disabled = false; });
});
</script>
