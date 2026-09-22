<?php defined('APP') or exit; use App\Core\Icons;
$ov = $overview ?? [];
function bars($rows, $labelKey) {
  $max = 1;
  foreach ($rows as $r) $max = max($max, (int) $r['c']);
  $h = '';
  foreach ($rows as $r) {
    $w = round((int) $r['c'] / $max * 100);
    $h .= '<div class="report-bar"><span class="lbl">' . e($r[$labelKey]) . '</span><span class="track"><span class="fill" style="width:' . $w . '%;"></span></span><span class="val">' . (int) $r['c'] . '</span></div>';
  }
  return $h ?: '<div class="empty">Sem dados.</div>';
}
?>
<div class="toolbar no-print">
  <span style="color:var(--ad-muted);font-size:13.5px;">Visão consolidada do evento.</span>
  <span class="spacer"></span>
  <button class="btn-ad btn-ghost-ad btn-sm-ad" onclick="window.print()"><?= Icons::get('printer') ?> Imprimir / PDF</button>
</div>

<div class="grid g4" style="margin-bottom:16px;">
  <div class="stat"><div class="stat-top"><span class="stat-lbl">INSCRITOS</span></div><div class="stat-num"><?= $ov['total'] ?? 0 ?></div></div>
  <div class="stat"><div class="stat-top"><span class="stat-lbl">CHECK-INS</span></div><div class="stat-num"><?= $ov['checkins'] ?? 0 ?> <small style="font-size:14px;color:var(--ad-muted);">(<?= $ov['checkin_rate'] ?? 0 ?>%)</small></div></div>
  <div class="stat"><div class="stat-top"><span class="stat-lbl">CANCELADOS</span></div><div class="stat-num"><?= $ov['cancelled'] ?? 0 ?></div></div>
  <div class="stat"><div class="stat-top"><span class="stat-lbl">RECEITA POTENCIAL</span></div><div class="stat-num" style="font-size:26px;"><?= money($ov['revenue'] ?? 0) ?></div></div>
</div>

<div class="grid g2">
  <div class="card"><h3>Inscrições por ingresso</h3><p class="card-sub">Vendidos por tipo</p>
    <?php
    $max = 1;
    foreach ($per_ticket as $r) $max = max($max, (int) $r['sold']);
    foreach ($per_ticket as $r) {
      $w = round((int) $r['sold'] / $max * 100);
      echo '<div class="report-bar"><span class="lbl">' . e($r['name']) . '</span><span class="track"><span class="fill" style="width:' . $w . '%;"></span></span><span class="val">' . (int) $r['sold'] . '</span></div>';
    }
    ?>
  </div>
  <div class="card"><h3>Inscrições por origem</h3><p class="card-sub">Canal de origem</p><?= bars($per_source, 's') ?></div>
  <div class="card"><h3>Inscrições por cidade</h3><p class="card-sub">Top 15</p><?= bars($per_city, 'city') ?></div>
  <div class="card"><h3>Inscrições por empresa</h3><p class="card-sub">Top 15</p><?= bars($per_company, 'company') ?></div>
  <div class="card"><h3>Comunicação</h3><p class="card-sub">Status dos envios</p>
    <div class="report-bar"><span class="lbl">Enviadas</span><span class="track"><span class="fill" style="width:100%;"></span></span><span class="val"><?= $comms['sent'] ?? 0 ?></span></div>
    <div class="report-bar"><span class="lbl">Na fila</span><span class="track"><span class="fill" style="width:<?= ($comms['queued'] ?? 0) ? 60 : 2 ?>%;"></span></span><span class="val"><?= $comms['queued'] ?? 0 ?></span></div>
    <div class="report-bar"><span class="lbl">Falhas</span><span class="track"><span class="fill" style="width:<?= ($comms['failed'] ?? 0) ? 40 : 2 ?>%;"></span></span><span class="val"><?= $comms['failed'] ?? 0 ?></span></div>
    <div class="report-bar"><span class="lbl">Abertura e-mail</span><span class="track"><span class="fill" style="width:<?= $comms['open_rate'] ?? 0 ?>%;"></span></span><span class="val"><?= $comms['open_rate'] ?? 0 ?>%</span></div>
  </div>
  <div class="card"><h3>Funil de presença</h3><p class="card-sub">Da inscrição ao check-in</p>
    <?php
    $tot = max(1, (int) ($ov['total'] ?? 0));
    $rows = [
      ['Inscritos', (int) ($ov['total'] ?? 0)],
      ['Confirmados', (int) ($ov['confirmed'] ?? 0)],
      ['Presentes', (int) ($ov['checkins'] ?? 0)],
    ];
    foreach ($rows as $r) {
      echo '<div class="report-bar"><span class="lbl">' . $r[0] . '</span><span class="track"><span class="fill" style="width:' . round($r[1] / $tot * 100) . '%;"></span></span><span class="val">' . $r[1] . '</span></div>';
    }
    ?>
  </div>
</div>
